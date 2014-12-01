<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//if($_SERVER['REMOTE_ADDR'] != '181.51.191.187'){
//	die('Under maintenance');
//}

error_reporting(0);
    
date_default_timezone_set("America/Los_Angeles");

class cron_batch_tracking extends Front_Controller
{
    var $configuration = array();
    var $data_email = array();
    var $dates = null;
    var $data_conection = array();
	public function __construct()
	{
		
		parent::__construct();

		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		$this->load->model('identify_carrier', null, true);
	 	$this -> load -> helper('obj_array_xml');
        $this->load->helper('obj_array_xml_helper');
        $this->configuration['limit_confirmation'] = 30*24;
	}

	public function index(){


	}

	/*
		function serach in a data base orders with flag send in batch
	*/
	public function createxmlflagsendbatch(){
        $quantitysendtracking=0;
        $quantityproductssend=0;

        $account_amazon = $this -> db -> get('bf_amazon_cofiguration') -> result();
        // echo "<pre>";
        // print_r($account_amazon);
        // echo "<pre>";
        foreach ($account_amazon as $key => $value) {
            echo "<pre>";
            print_r($value->amazon_cofiguration_account_name);
            echo "<pre>";
            $this -> db -> where('orders_configuration_id',(string)$value -> id);
            $this -> db -> where('orders_tracking_flag_shipping', '3');
            $this -> db -> from('bf_orders_tracking AS ot', false);
            $this -> db -> join('bf_orders_products AS opp' , 'ot.id_orders_products=opp.id', 'left', false);
            $this -> db -> join('bf_orders AS o' , 'opp.products_amazon_order_id=o.id', 'left', false);
            $resultorderswithflag = $this -> db -> get() -> result_array();
            $this -> data_conection =  $value;
            //echo $this -> db -> last_query();
            echo "<hr><hr>";
            echo count($resultorderswithflag);
            echo "<hr><hr>";
            $this ->db->last_query();
            echo "<hr><hr>";
            if(empty($resultorderswithflag)) {
                echo "The orders with flag send batch not found";
            } else{
                $data_xml = array(); 
                $data_update_traking = array();
                //go over the result serach orders tracking with flag send batch
                for($i=0; $i<sizeof($resultorderswithflag);$i++) {
                //search orders products with id_orders_products getting of  result serach orders tracking with flag send batch
                    $this->db->where('id',$resultorderswithflag[$i]['id_orders_products']);
                    $ordersgetproductsid=$this->db->get('bf_orders_products')->result_array();
                    //search orders with id_orders_products getting of  result serach orders tracking with flag send batch
                    $this->db->where('id',$ordersgetproductsid[0]['products_amazon_order_id']);
                    $ordersgetamazonid=$this->db->get('bf_orders')->result_array();
                        
                    $this->dates['in']['purchase_date'] = $ordersgetamazonid[0]['orders_purchase_date'];
                    $this->dates['in']['current_date'] = date('Y-m-d H:i:s');//catch the current timestamp in the server, this is the Current date 
                    $this->dates['in']['confirmation_date'] = strtotime($resultorderswithflag[$i]['orders_tracking_Ship_date']);
                    //send all dates to UTC time zone and get it in Unix time (in that way is easier to compare dates)
                    $this->dates['out']['purchase_date'] = equivalen_times_by_zone($this->dates['in']['purchase_date'], 'PDT', 'UTC');
                    $this->dates['out']['current_date'] = equivalen_times_by_zone($this->dates['in']['current_date'], date_default_timezone_get(), 'UTC');
                    $this->dates['out']['confirmation_date'] = equivalen_times_by_zone($this->dates['in']['confirmation_date'], date_default_timezone_get(), 'UTC');
                    $this->dates['out']['limit_date'] = $this->dates['out']['purchase_date'] + (60*60*$this->configuration['limit_confirmation']); //Limit for this confirmation
                    //echo "apenas se usa el helper :".$this->dates['out']['confirmation_date']."<br>";
                    // echo"current date: ".$this->dates['out']['current_date']." confirmation date: ".$this->dates['out']['confirmation_date']."<br>";
                    if($this->dates['out']['current_date'] > $this->dates['out']['confirmation_date']){
                        $this->dates['out']['confirmation_date']=$this->dates['out']['confirmation_date'];
                    } else {
                        $this->dates['out']['confirmation_date']=$this->dates['out']['current_date'] ;
                    }
                    //Verify that you are not sending a future date
                    if($this->dates['out']['current_date'] < $this->dates['out']['confirmation_date']){
                        $arrayName['message'] = 'You are trying to send a "future date" (confirmation date is higher than now in the UTC time zone)';
                        echo json_encode($arrayName);
                        die('<hr>here die a');
                    }
                                            
                    //Verify if the confirmation date is under the purchase date
                    if($this->dates['out']['confirmation_date'] < $this->dates['out']['purchase_date']){
                        $arrayName['message'] = 'You are trying to confirm an order before the purchase (we are going to adjust the date)';
                        echo json_encode($arrayName);
                        $this->dates['out']['confirmation_date'] = $this->dates['out']['purchase_date'];
                        continue;
                        die('<hr>here die b'); 

                    }
                    
                    //Verify if the confirmation date is inside of the limit time
                    if($this->dates['out']['limit_date'] < $this->dates['out']['confirmation_date']){
                        $arrayName['message'] = 'Amazon has a time limit for order confirmations ('.$this->configuration['limit_confirmation'].')';
                        echo json_encode($arrayName);
                        continue;
                        die('<hr>here die c');
                    }
                    
                    //formatted all dates to UTC format (used for mws service 'Y-m-d\TH:i:s') 
                    foreach($this->dates['out'] as $kk => $vv) {
                        $this->dates['out'][$kk] = date('Y-m-d\TH:i:s', $this->dates['out'][$kk]);
                    }
                                           
                    $data_xml['@@'.($i+1).'_|Message'] = array(
                            'v@lue' => array(
                                'MessageID' => array('v@lue' => ($i+1)),
                                'OrderFulfillment' => array('v@lue' => array( 
                                        'AmazonOrderID' => array('v@lue' => $ordersgetamazonid[0]['orders_amazon_order_id']),
                                        'FulfillmentDate' => array('v@lue' => $this->dates['out']['confirmation_date'],
                                                                          'c@ment_line' => array(
                                                                                                            'Purchase Date (without UTC) '.$this->dates['in']['purchase_date'],
                                                                                                            'Current Date (UTC) '.$this->dates['out']['current_date'],
                                                                                                            'Confirmation Date (without UTC) '.$this->dates['in']['confirmation_date'],
                                                                                                            'Confirmation Date (UTC) '.$this->dates['out']['confirmation_date'],
                                                                                                            'Limit Date (UTC) '.$this->dates['out']['limit_date'],
                                                                                                        ),
                                                                          ),
                                        'FulfillmentData' => array('v@lue' => array(
                                                'CarrierCode' => array('v@lue' => $resultorderswithflag[$i]['orders_tracking_carrier_code']),
                                                'ShippingMethod' => array('v@lue' => $resultorderswithflag[$i]['orders_tracking_carrier_code']),
                                                'ShipperTrackingNumber' => array('v@lue' => $resultorderswithflag[$i]['orders_tracking_Tracking_number']),
                                            ),
                                        ),
                                        'Item' => array('v@lue' =>array(
                                                'AmazonOrderItemCode' => array('v@lue' => (string)$ordersgetproductsid[0]['products_order_item_id']),
                                                'Quantity' => array('v@lue' => $resultorderswithflag[$i]['orders_tracking_quantity_shipped']),        
                                            )
                                        ),
                                    )
                                )
                            )
                        );
                    $data_update_traking[($i+1)] =    array(
                            'data_update' => array(
                                                    'orders_tracking_Ship_date'=> $resultorderswithflag[$i]['orders_tracking_Ship_date'],
                                                    'orders_tracking_carrier_code'=> $resultorderswithflag[$i]['orders_tracking_carrier_code'],
                                                    'orders_tracking_ship_method'=> $resultorderswithflag[$i]['orders_tracking_carrier_code'],
                                                    'orders_tracking_Tracking_number'=> $resultorderswithflag[$i]['orders_tracking_Tracking_number'],   
                                                    'id_orders_products'=>(int)$ordersgetproductsid[0]['id'],
                                                    'orders_tracking_quantity_shipped' => (int)$resultorderswithflag[$i]['orders_tracking_quantity_shipped'],
                                                    'orders_tracking_number_email' => $resultorderswithflag[$i]['orders_tracking_number_email'],
                                                    'orders_tracking_flag_shipping' => '0',
                                                    'orders_tracking_n_mesage_api' => ($i +1)
                            ),
                            'data_relation' => array(
                                                    'amazon_order_id'=>(string)$ordersgetamazonid[0]['orders_amazon_order_id']
                            )           
                    );

                    $quantitysendtracking=$quantitysendtracking+1;
                    $quantityproductssend=$quantityproductssend+$resultorderswithflag[$i]['orders_tracking_quantity_shipped'];
              	} //end go over result  -- for    

                $array2xml_complete = array(
                        'AmazonEnvelope' => array(
                        'v@lue' => array(
                        'Header' => array(
                                'v@lue' => array(
                                    'DocumentVersion' => array('v@lue' => '1.01'),
                                'MerchantIdentifier' => array('v@lue' => 'M_ETERMART_99448173')
                                )
                          ),
                        'MessageType' => array('v@lue' => 'OrderFulfillment'),
                        ),
                        '@ttributes' => array(
                        'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
                        'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
                        )
                    )
                );

                $array2xml_complete['AmazonEnvelope']['v@lue'] = array_merge($array2xml_complete['AmazonEnvelope']['v@lue'], $data_xml);
                                
                if(isset($data_update_traking)) {
                   $data_xml_result =object_2_xml($array2xml_complete,'<?xml version="1.0" encoding="UTF-8"?>',0);
                    // echo '<xmp>'.$data_xml_result.'</xmp>';

                     //create a file to send with confirmation 
                    $namesendfile="xmlsendapi.xml";
                    $filexmlsend=fopen($namesendfile, "w+");
                    fwrite($filexmlsend,  $data_xml_result);
                    fclose($filexmlsend);
                    //end  create file to send with confirmation
                     //send data_xml to amazon.
                    $response_api=$this->update_traking($data_xml_result);
                    echo "<pre>";
                    print_r($response_api);
                    echo "</pre>";
                    $errors_on_sumbission = get_content_from_path($response_api, 'Error');
                
                    $error = false;
                    $buffer = null;
                                    
                    if (is_null($errors_on_sumbission)) {
                        $sumission_id = get_content_from_path($response_api, 'SubmitFeedResult/FeedSubmissionInfo/FeedSubmissionId');
                        if($sumission_id) {
                            foreach ($data_update_traking as $key => $orders) {
                                $orders['data_update']['orders_tracking_FeedSubmissionId'] = (string)$response_api -> SubmitFeedResult -> FeedSubmissionInfo -> FeedSubmissionId;
                                $traking = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products']))->get('bf_orders_tracking');
                                $traking = $traking -> result();
                                if (!$traking){
                                    echo "new";
                                    $this -> db -> insert('bf_orders_tracking',$orders['data_update']);
                                } else {
                                    echo "exist"; 
                                    $traking2 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ))->get('bf_orders_tracking');
                                    $traking2 = $traking2 -> result();
                                    if (!$traking2) {
                                        $traking3 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number']))->get('bf_orders_tracking');
                                        if(!$traking3) {
                                                echo "<h1>case 1</h1>";
                                                $this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number'] ));  
                                        } else {
                                            echo "<h1>case 2</h1>";
                                            $this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products']));   
                                        }
                                    
                                    } else{
                                        echo "<h1>case 3</h1>";
                                        $this-> db-> update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ));
                                    }
                                }
                
                                $status_data['orders_order_status'] = 'Waiting track confirm';
                                $where_condition = array('orders_amazon_order_id' => $orders['data_relation']['amazon_order_id']);
                                $this->db->update('bf_orders',$status_data, $where_condition);
                            }
                              // echo "<hr><hr>here<hr><hr>"; 
                            // create and send messaje
                            $descriptionmessagetosend="Description Send Batch Confirmation Report \n \n Quantity send tracking: ".$quantitysendtracking."\n Quantity send products: ".$quantityproductssend ."\n XML send date: ".date('NOW')."\n Response to amazon API: ".$response_api;
                              // echo "<hr><hr>here 2 <hr><hr>"; 

                            // $this->email->from('info@atlanticsoft.us', 'ETERMART');
                            // $this->email->to('edwin.hortua@atlanticsoft.us');
                            // $this->email->subject('Send Batch Confirmation Report');
                            // $this->email->message($descriptionmessagetosend);  
                            // $this->email->attach($namesendfile);
                            // $this->email->send();
                              // echo "<hr><hr>here 3 <hr><hr>"; 
                       } else {
                             //send mail
                            foreach($data_update_traking as $key => $orders){
                               $status_data['orders_order_status'] = 'Failed';
                               $this->db->update('bf_orders',$status_data, $orders['data_relation']);
                            }
                                $error = true;
                                $message = 'We can\'t get a valid submission Id from API response (Live)'.date("Y-m-d H:i:s");
                                //create and send messaje
                                // $this->email->from('info@atlanticsoft.us', 'ETERMART');
                                // $this->email->to('edwin.hortua@atlanticsoft.us');
                                // $this->email->subject('Send Batch Confirmation Report');
                                // $this->email->message($message);  
                                // $this->email->attach($namesendfile);
                                // $this->email->send();
                        }
                   } else {
                        $error = true;
                        $message = 'We get an error from Amazon API (Live)'.date("Y-m-d H:i:s");
                        //create and send messaje
                        // $this->email->from('info@atlanticsoft.us', 'ETERMART');
                        // $this->email->to('edwin.hortua@atlanticsoft.us');
                        // $this->email->subject('Send Batch Confirmation Report');
                        // $this->email->message($message);  
                        // $this->email->attach($namesendfile);
                        // $this->email->send();
                                    //die();
                    }
                }   
            }
        }    
            die('<hr>End');    
    }

	public function update_traking($data){
                $this -> db -> select('') -> from('bf_supplier');
                $this -> db -> like('supplier_supplier_name', 'Mirage', 'after');
                //$this -> db -> like('supplier_supplier_name', 'Strawberry', 'after');
                $supplier = $this -> db -> get() -> result();
                $amazon_account = $supplier[0] -> supplier_configuration_account_id;
        
                if($amazon_account == 0 || $amazon_account == ''){
                    $amazon_account = 1;
                }
        
                $amazon_cofiguration_query = $this -> data_conection;
        
                $merchant_ID = $amazon_cofiguration_query -> amazon_cofiguration_merchant_ID ;
                $marketplace_ID = $amazon_cofiguration_query -> amazon_cofiguration_marketplace_ID;
                $AWS_Access_Key_ID = $amazon_cofiguration_query -> amazon_cofiguration_AWS_Access_Key_ID;
                $secret_key  = $amazon_cofiguration_query -> amazon_cofiguration_secret_key;
         
                $feedHandle = @fopen('php://temp', 'rw+');
                fwrite($feedHandle, $data);
                rewind($feedHandle);
                    
                $params_item = array(
                    'MarketplaceIdList.Id.1' => $marketplace_ID,
                    'Action' => "SubmitFeed",
                    'SellerId' =>  $merchant_ID,
                    'FeedType'=>'_POST_ORDER_FULFILLMENT_DATA_',
                    'AWSAccessKeyId' => $AWS_Access_Key_ID,
                    'Version'=> "2009-01-01",
                    'SignatureMethod' => "HmacSHA256",
                    'SignatureVersion' => 2,
                    'Timestamp'=> gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
                    'PurgeAndReplace'=>'false'
                ); 
                //PurgeAndReplace  siempre debe de estar en false, si no ocaciona que se caigan los productos de amazon
        
                $contentMd5 = base64_encode(md5(stream_get_contents($feedHandle), true));
        
                $url_string = $this -> string_to_url($params_item);
                $signature = $this -> covert_Signature($url_string, $secret_key);
        
                $serviceUrl = "https://mws.amazonservices.com/";
        
                $serviceUrl .= '?' . $url_string.'&Signature='.$signature;
            
                $header[] = 'Expect: ';
                $header[] = 'Accept: ';
                $header[] = 'Transfer-Encoding: chunked';
                $header[] = 'Content-MD5: ' . $contentMd5;
      
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Client Library/2011-08-01 (Language=PHP5)');
                curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_URL,$serviceUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER,array_merge($header, array('Content-Type: application/octet-stream')));
                rewind($feedHandle);
                curl_setopt($ch, CURLOPT_INFILE, $feedHandle);
                curl_setopt($ch, CURLOPT_UPLOAD, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        
                $response_p = curl_exec($ch);
        
                @fclose($feedHandle);
                $parsed_xml_p = simplexml_load_string($response_p);
        
                return ($parsed_xml_p);
            }

          public function covert_Signature($url_string, $secret_key){
                $string_to_sign = "POST\nmws.amazonservices.com\n/\n" . $url_string;
                $signature = hash_hmac("sha256", $string_to_sign, $secret_key , TRUE);
                return urlencode(base64_encode($signature));
           }
    
           public function string_to_url($params_item){
                $url_parts = array();
                foreach(array_keys($params_item) as $key)
                    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
                    sort($url_parts);
                return implode("&", $url_parts);
           }
}//end class