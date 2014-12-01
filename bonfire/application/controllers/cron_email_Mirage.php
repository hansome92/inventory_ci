<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

//if($_SERVER['REMOTE_ADDR'] != '181.51.191.187'){
//	die('Under maintenance');
//}

error_reporting(0);
    
date_default_timezone_set("America/Los_Angeles");

class cron_email_Mirage extends Front_Controller
{
	var $data_email = array();
	public function __construct()
	{
		
		parent::__construct();

		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		$this->load->model('identify_carrier', null, true);
		
		$this->load->library('email');
		$this -> data_email['username'] = 'orders@etermart.com'; 
		$this -> data_email['password'] = 'orders.2014';
	 	$this -> load -> helper('obj_array_xml');
	}
	public function index()
	{
		//echo imap_timeout(IMAP_READTIMEOUT) ;
		$inbox =  imap_open("{mail.etermart.com:143/imap/novalidate-cert}INBOX", $this -> data_email['username'], $this -> data_email['password']) or die("can't connect: " . imap_last_error());
		//$inbox =  imap_open("{imap.secureserver.net:993/imap/ssl}miragepet", $this -> data_email['username'], $this -> data_email['password']) or die("can't connect: " . imap_last_error());
		
		echo imap_timeout(IMAP_READTIMEOUT, 360) ;
		//echo imap_timeout(IMAP_READTIMEOUT) ;
	
		//Get the period where you want to search in the mailbox
		$period = $this->uri->segment(3);
		//If that value is not valid or is missing set a default
		if(!is_numeric($period) || is_null($period)){
			$period = 7;
		}
		echo '<h1>'.$period.'</h1>';
		$emails = imap_search($inbox,'SINCE '. date('d-M-Y', strtotime("- $period days")));
		
		//$emails = imap_search($inbox,'SINCE '. date('d-M-Y', strtotime("-3 week")));
		//$emails = imap_search($inbox,'FROM "info@miragepetproducts.com" ');
		
		$con = 0;
		if ($emails)
		{
			$emails = array_reverse($emails);
			
			echo '<h1>Number total of emails'.sizeof($emails).'</h1>';
			//echo '<pre>';
			//print_r($emails);
			//echo '</pre>';
			//die();
			foreach ($emails as $value){
				
				$this->db->query('SHOW TABLES');
				
				if(!$over = imap_fetch_overview($inbox,$value,0)){
					echo '<h2>We could not get the email overview for the message number: '.$value.'</h2>';
					//$over_0 = imap_headerinfo($inbox,$value);
					//echo '<pre>';
					//print_r($over_0);
					//echo '</pre>';
					continue;
					//die();
				}
				
				//echo '<div style="background:black;color:yellow;"><pre><h1>'.$value.'</h1><xmp>';
				//print_r($over);
				//echo '</xmp><hr /><xmp>';
				
				//change the subject if it is mime header encoded
				if(stripos($over[0]->subject, '?UTF-8?') !== false){
					$over[0]->subject = iconv_mime_decode($over[0]->subject,0, "UTF-8");
				}else if(stripos($over[0]->subject, '?ISO-8859-1?') !== false){
					$over[0]->subject = iconv_mime_decode($over[0]->subject,0, "ISO-8859-1");
				}
				
				//Do the same thing if the from is mime header encoded too
				if(stripos($over[0]->from, '?UTF-8?') !== false){
					$over[0]->from = iconv_mime_decode($over[0]->from,0, "UTF-8");
				}else if(stripos($over[0]->from, '?ISO-8859-1?') !== false){
					$over[0]->from = iconv_mime_decode($over[0]->from,0, "ISO-8859-1");
				}
				
				//print_r($over);
				//echo '</xmp></pre></div>';
				//echo "<hr><hr>";
				//die();
				
				if (stripos((string)$over[0] -> from , 'miragepetproducts') !== false && stripos((string)$over[0] -> subject , 'Order Shipped') !== false){
					
					
					/*PFT*/ //2014-01-29 --this is just for testing purposes SOF
					//echo '<div style="background:black;color:yellow;"><pre><h1>'.$value.'</h1><xmp>';
					//print_r($over);
					//echo '</xmp></pre></div>';
					//echo "<hr><hr>";
					
					//$structure = imap_fetchstructure($inbox, $value);
					
					//echo '<pre>';
					//print_r($structure);
					//echo '</pre>';
					
					//$message_0 = imap_fetchbody($inbox,$value,'1.1');
					//echo '<div style="background:orange;color:black;"><xmp>'.$message_0.'</xmp></pre></div><hr />';
					
					//$message_1 = imap_fetchbody($inbox,$value,'1.2');
					//echo '<div style="background:orange;color:black;"><xmp>'.$message_1.'</xmp></pre></div><hr />';
					/*PFT*/ //2014-01-29 --this is just for testing purposes EOF
					
					$message = imap_fetchbody($inbox,$value,1);
					
					//echo '<div style="background:orange;color:black;"><xmp>'.$message.'</xmp></pre></div><hr />';
					//die();
					
					$ini_pos = strpos($message, '<td height="40" colspan="3"><strong>Shipping Confirmation</strong></td>');
					$message = substr($message, $ini_pos);
					$end_pos = strpos($message, '<strong>Checkout Questions</strong>');
					$message = substr($message, 0, $end_pos);
					
					$ini_order_number = "<strong>Order number:</strong>";
					$end_order_number = "<strong>Order Date:</strong>";
					
					$message = preg_replace("[\n|\r|\r\n!\t|\s\s]","", $message);
					$message = trim($message);
					//echo "<XMP>".$message."</XMP><br><br>";
					//echo $message;
					
					(string)$orders_number =  substr($message, ((int)strpos($message,$ini_order_number) + (int)strlen($ini_order_number)), (( (int)strlen(substr($message, 0, strpos($message, $end_order_number)))) -((int)strpos($message,$ini_order_number) +  (int)strlen($ini_order_number))));
					$orders_number = trim(str_replace('<br>', '', $orders_number));
					// echo "<h1><xmp>".(string)$orders_number."</xmp></h1>";
					
					
					$this -> db -> select('o.id , o.orders_amazon_order_id , op.id AS id_pro, op.products_order_item_id , op.products_name_product, o.orders_configuration_id');
					$this -> db -> from('bf_orders_products AS op');
					$this -> db -> where('products_Our_Order_ID', (string)$orders_number);
					$this -> db -> join('bf_orders AS o' , 'op.products_amazon_order_id = o.id');
					$result_order = $this -> db -> get();
					$result_order = $result_order -> result();
					
					
					if ($result_order && $orders_number !='' && $orders_number != null ) 
					{
						//echo "<pre>";
						//print_r($result_order);
						//// print_r($this -> db -> last_query());
						//// print_r($orders_number);
						//echo "</pre>";
						
						$ini_tracking = "/shiptracking.asp?trackno=";
						$end_tracking = "\">Click here<";
						$tracking_number = substr($message, ((int)strpos($message,$ini_tracking) + (int)strlen($ini_tracking)), (( (int)strlen(substr($message, 0, strpos($message, $end_tracking)))) -((int)strpos($message,$ini_tracking) + (int)strlen($ini_tracking))));
						//echo $tracking_number;
						
						if (!$carrierr = $this -> identify_carrier -> check_carrier($tracking_number)) {
							continue;
						}
						
						$ini_shipped_date = "<strong>Shipped Date</strong>: ";
						$end_shipped_date = "<!--END: oshippeddate-->";
						$shipped_date = substr($message, ((int)strpos($message,$ini_shipped_date) + (int)strlen($ini_shipped_date)), (( (int)strlen(substr($message, 0, strpos($message, $end_shipped_date)))) -((int)strpos($message,$ini_shipped_date) +   (int)strlen($ini_shipped_date))));
						// echo "<h1><xmp>".(string)$shipped_date."</xmp></h1>";
						$ship_data =date('Y-m-d\TH:i:s',strtotime($shipped_date));
						
						
						
						$ini_shipping_method = "<strong>Shipping Method</strong><br>";
						$end_shipping_method = "<br><!--START: trackingcode-->";
						$shipping_method = substr($message, ((int)strpos($message,$ini_shipping_method) + (int)strlen($ini_shipping_method)), (( (int)strlen(substr($message, 0, strpos($message, $end_shipping_method)))) -((int)strpos($message,$ini_shipping_method) +   (int)strlen($ini_shipping_method))));
						// echo "<h1><xmp>".(string)$shipping_method."</xmp></h1>";
						// $ship_data =date('Y-m-d\TH:i:s',strtotime($shipping_method));
						
						/*PFT 2013-07-22 BOF*/
						//The shipping method is always USPS when the carrier is USPS
						if(trim($carrierr) == 'USPS'){
							$shipping_method = 'USPS';
						}
						/*PFT 2013-07-22 EOF*/
						
						$ini_product = "<!--START: product-->";
						$end_product = "<!--END: product-->";
						$products = substr($message, ((int)strpos($message,$ini_product) + (int)strlen($ini_product)), (( (int)strlen(substr($message, 0, strpos($message, $end_product)))) -((int)strpos($message,$ini_product) + (int)strlen($ini_product))));
						$products = preg_replace("[\n|\r|\r\n!\t|\s\s]","", $products);
						$products = trim($products);
						
						$products = preg_split("/\<tr\>\<td\>\<\/td\>/i", $products);
						
						foreach ($products as $key => $product) 
						{
							if ((string)$product == "") 
								continue;
							
							$con = $con + 1 ;
							$ini_quentity = "</span></td><td width=\"23%\"><div align=\"left\">";
							$end_quentity = "</div></td><td width=\"22%\">";
							$quantity = substr($product, ((int)strpos($product,$ini_quentity) + (int)strlen($ini_quentity)), (( (int)strlen(substr($product, 0, strpos($product, $end_quentity)))) -((int)strpos($product,$ini_quentity) + (int)strlen($ini_quentity))));
							$quantity = substr($quantity, (strpos($quantity, 'x') + 2));
							// echo "<h1><xmp>".(string)$quentity."</xmp></h1>";
							
							$ini_tipro = "</td><td width=\"46%\"><span>";
							$end_tipro = "</span></td><td width";
							$tipro = substr($product, ((int)strpos($product,$ini_tipro) + (int)strlen($ini_tipro) + 8), (( (int)strlen(substr($product, 0, strpos($product, $end_tipro)))) -((int)strpos($product,$ini_tipro) +  8 +(int)strlen($ini_tipro))));
							// $tipro = substr($quentity, (strpos($quentity, 'x') + 2));
							// echo "<h1><xmp>".(string)$tipro."</xmp></h1>";
							$temp_por = 0 ;
							$id_pro ;
							foreach ($result_order as $key => $pro_ord) 
							{
								similar_text($pro_ord->products_name_product, $tipro, $por);
								
								$id_pro = $por > $temp_por ? $key : $id_pro;
								$temp_por = $por > $temp_por ? $por : $temp_por;
								
								// echo "<br>".$por."\t".$pro_ord->products_name_product;
							}
							
							if(!is_null($carrierr) && $carrierr != '' && !is_null($shipping_method) && $shipping_method !='' && !is_null($tracking_number) && $tracking_number != ''){
								
								$data_xml['@@'.$con.'_|Message'] = array(
									'v@lue' => array(
										'MessageID' => array('v@lue' => $con),
										'OrderFulfillment' => array('v@lue' => array( 
												'AmazonOrderID' => array('v@lue' => (string)$result_order[$id_pro]-> orders_amazon_order_id),
												'FulfillmentDate' => array('v@lue' => $ship_data),
												'FulfillmentData' => array('v@lue' => array(
														'CarrierCode' => array('v@lue' => $carrierr),
														'ShippingMethod' => array('v@lue' => trim((string)$shipping_method)),
														'ShipperTrackingNumber' => array('v@lue' => $tracking_number),
													),
												),
												'Item' => array('v@lue' =>array(
														'AmazonOrderItemCode' => array('v@lue' => (string)$result_order[$id_pro] -> products_order_item_id),
														'Quantity' => array('v@lue' => trim((string)$quantity)),		
													)
												),
											)
										)
									)
								);
								$data_update_traking[$con] =	array(
										'data_update' => array(
																'orders_tracking_Ship_date'=> $ship_data,
																'orders_tracking_carrier_code'=> $carrierr,
																'orders_tracking_ship_method'=>  trim((string)$shipping_method),
																'orders_tracking_Tracking_number'=> $tracking_number,   
																'id_orders_products'=>(int)$result_order[$id_pro] -> id_pro,
																'orders_tracking_quantity_shipped' => (int)$quantity,
																'orders_tracking_number_email' => $value,
																'orders_tracking_flag_shipping' => '0',
																'orders_tracking_n_mesage_api' => $con 
										),
										'data_relation' => array(
																'amazon_order_id'=>(string)$result_order[$id_pro] -> orders_amazon_order_id
										)	
													
								);
								unset($result_order[$id_pro]);
							}
						}
						//echo "<pre>";
						//print_r($data_xml);
						//echo "</pre>";
						
					}
					// if ($con > 1) {
						// break;
					// }
					//echo "<hr><hr><hr><hr><hr><hr>";
				}
			}
		}
		
		if(!isset($data_xml)){
			// $config['mailtype'] = 'html';
			// $this->email->initialize($config);
			// $this->email->clear();
			// $this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
			// $this->email->to('edwin@igniweb.com');
			// $this->email->cc('florez@igniweb.com,david@igniweb.com'); 
			// $this->email->subject('Run email Mirage'); 
			// $this->email->message('Cron ran on LIVE without messages (Mirage)');
			// $this->email->send();
			die('missing data');
		}
		
		if(sizeof($data_xml) < 1){
			// $config['mailtype'] = 'html';
			// $this->email->initialize($config);
			// $this->email->clear();
			// $this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
			// $this->email->to('edwin@igniweb.com');
			// $this->email->cc('florez@igniweb.com,david@igniweb.com'); 
			// $this->email->subject('Run email Mirage');
			// $this->email->message('Cron ran on LIVE without messages (Mirage)');
			// $this->email->send();
			die('without mails');		
		}
		
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

		//die('(^_^)');
		if(isset($data_update_traking))
		{
			$data_xml_result =object_2_xml($array2xml_complete,'<?xml version="1.0" encoding="UTF-8"?>',0);
			
			echo '<xmp>'.$data_xml_result.'</xmp>';
			
			$response_api = $this -> update_traking($data_xml_result);
			
			//echo '<pre><xmp>';
			//print_r($response_api);
			//echo '</xmp></pre>';
						
			$errors_on_sumbission = get_content_from_path($response_api, 'Error');
			
			$error = false;
			$buffer = null;
			
			if (is_null($errors_on_sumbission)){
				$sumission_id = get_content_from_path($response_api, 'SubmitFeedResult/FeedSubmissionInfo/FeedSubmissionId');
				if($sumission_id){
					foreach ($data_update_traking as $key => $orders){
						$orders['data_update']['orders_tracking_FeedSubmissionId'] = (string)$response_api -> SubmitFeedResult -> FeedSubmissionInfo -> FeedSubmissionId;
						$traking = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products']))->get('bf_orders_tracking');
						$traking = $traking -> result();
						
						if (!$traking) 
						{
							echo "new";
							$this -> db -> insert('bf_orders_tracking',$orders['data_update']);
						}
						else
						{
							echo "exist"; 
							$traking2 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ))->get('bf_orders_tracking');
							$traking2 = $traking2 -> result();
							if (!$traking2) 
							{
								$traking3 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number']))->get('bf_orders_tracking');
								if(!$traking3)
								{
									echo "<h1>case 1</h1>";
									$this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number'] ));	
								}
								else
								{
									echo "<h1>case 2</h1>";
									$this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products']));	
								}
								
							}
							else 
							{
								echo "<h1>case 3</h1>";
								$this-> db-> update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ));
							}
						}
						
						$status_data['orders_order_status'] = 'Waiting track confirm';
						$where_condition = array('orders_amazon_order_id' => $orders['data_relation']['amazon_order_id']);
						$this->db->update('bf_orders',$status_data, $where_condition);
						
						imap_mail_move($inbox , $orders['data_update']['orders_tracking_number_email'] , 'miragepet');
						imap_clearflag_full($inbox,$orders['data_update']['orders_tracking_number_email'],"\\Seen" );
						//echo "<pre>";
						//print_r($orders);
						//print_r($this -> db -> last_query());
						//print_r($traking);
						//echo "</pre><hr><hr><hr>";
						
					}
				}else{
					//send mail
					foreach($data_update_traking as $key => $orders){
						$status_data['orders_order_status'] = 'Failed';
						$this->db->update('bf_orders',$status_data, $orders['data_relation']);
					}
					$error = true;
					$message = 'We can\'t get a valid submission Id from API response (Live)'.date("Y-m-d H:i:s");
				}
			}else{
				$error = true;
				$message = 'We get an error from Amazon API (Live)'.date("Y-m-d H:i:s");
				//send_mail
			}
			
		if($message != ''){
			$message = 'Run LIVE Time '.date("Y-m-d H:i:s");
		}
		
		// $config['mailtype'] = 'html';
		// $this->email->initialize($config);
		// $this->email->clear();
// 		
		// if($error){
		// //if(true){
			// ob_start();
			// ob_clean();
			// print_r($data_xml_result);
			// echo "\n\n\n";
			// print_r($response_api);
			// $buffer = ob_get_flush();
			// ob_end_clean();
// 			
			// $path = './downloads/tracking_confirm/mirage_error_'.date('Y-m-d_H.i.s').'.txt';
			// $fp = fopen($path,'w');
			// if($fp){
				// fputs($fp,$buffer);
			// }else{
				// die('<h2>fopen failed</h2>');
			// }
			// fclose($fp);
			// if(file_exists($path)){
				// $this->email->attach($path);
			// }
		// }
		// //$this->email->from('ben@puredigitalusa.com','Your Site Tester');
		// $this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
		// $this->email->to('edwin@igniweb.com');
		// $this->email->cc('florez@igniweb.com,david@igniweb.com'); 
		// $this->email->subject('Run email mirage LIVE');
		// $this->email->message($message);
		// $this->email->send();
		
		}
		imap_close($inbox);
	}
	
	public function update_traking($data)
    {
        $this -> db -> select('') -> from('bf_supplier');
		$this -> db -> like('supplier_supplier_name', 'Mirage', 'after');
		//$this -> db -> like('supplier_supplier_name', 'Strawberry', 'after');
		$supplier = $this -> db -> get() -> result();
		$amazon_account = $supplier[0] -> supplier_configuration_account_id;
		
		if($amazon_account == 0 || $amazon_account == ''){
			$amazon_account = 1;
		}
    	
        $amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find($amazon_account);
        
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
    
    public function string_to_url($params_item)
    {
        $url_parts = array();
        foreach(array_keys($params_item) as $key)
            $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
        sort($url_parts);
        return implode("&", $url_parts);
    }
}//end class