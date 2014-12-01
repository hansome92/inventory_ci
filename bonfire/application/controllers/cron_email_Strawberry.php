<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

error_reporting(0);

//if ($_SERVER['REMOTE_ADDR'] != '181.51.191.187') {
//}

date_default_timezone_set("America/Los_Angeles");
    
class cron_email_Strawberry extends Front_Controller
{
	var $data_email = array();
	public function __construct()
	{ 
		parent::__construct();
		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		$this->load->library('email');
		
	 	$this -> load -> helper('obj_array_xml');
		
		$this -> data_email['username'] = 'orders@etermart.com'; 
		$this -> data_email['password'] = 'orders.2014'; 
				
	}
	public function index()
	{
		$data_xml = array();
		

		//echo '<h2>'.imap_timeout(IMAP_READTIMEOUT).'</h2>';
		$inbox =  imap_open("{mail.etermart.com:143/imap/novalidate-cert}INBOX", $this -> data_email['username'], $this -> data_email['password']) or die("can't connect: " . imap_last_error());
		//$inbox =  imap_open("{imap.secureserver.net:993/imap/ssl}strawberry", $this -> data_email['username'], $this -> data_email['password']) or die("can't connect: " . imap_last_error());
		imap_timeout(IMAP_READTIMEOUT, 540);
		//echo '<h2>'.imap_timeout(IMAP_READTIMEOUT).'</h2>' ;
		
		//Get the period where you want to search in the mailbox
		$period = $this->uri->segment(3);
		//If that value is not valid or is missing set a default
		if(!is_numeric($period) || is_null($period)){
			$period = 7;
		}
		echo '<h1>'.$period.'</h1>';
		$emails = imap_search($inbox,'SINCE '. date('d-M-Y', strtotime("- $period days")));
		
		
		if ($emails)
		{
			$emails = array_reverse($emails);
			$con = 0;
			
			echo '<h1>Number total of emails'.sizeof($emails).'</h1>';
			foreach ($emails as $value) 
			{
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
				
				
				//this weird mime header enconde "en_GB" is at the end really "UTF-8"
				$over[0]->subject = str_replace('en_GB','UTF-8',$over[0]->subject);
				$over[0]->from = str_replace('en_GB','UTF-8',$over[0]->from);
				
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
				
				//die();
				
				if (strpos((string)$over[0] -> from , 'StrawberryNET')) 
				{
					$message = imap_fetchbody($inbox,$value,1);
					$message = str_replace(array("=\r\n","=3D"), array("",'='), $message);
					$ini_pos = strpos($message, 'StrawberryNET Order number');
					$message = substr($message, $ini_pos);
					$end_pos = strpos($message, 'Questions');
					$message = substr($message, 0, $end_pos);
					
					
					$ini_order_number = "Order number:</td><td>";
					$end_order_number = "</td></tr><tr><td>Your";
					
					$orders_number =  substr($message, ((int)strpos($message,$ini_order_number) + 22), (( (int)strlen(substr($message, 0, strpos($message, $end_order_number)))) -((int)strpos($message,$ini_order_number) +  22)));
					$this -> db -> select('o.id , o.orders_amazon_order_id , op.id AS id_pro, op.products_order_item_id , op.products_name_product');
					$this -> db -> from('bf_orders_products AS op');
					$this -> db -> where('products_Our_Order_ID', (string)$orders_number);
					$this -> db -> join('bf_orders AS o' , 'op.products_amazon_order_id = o.id');
					$result = $this -> db -> get();
					//die($this->db->last_query());
					$result = $result -> result();
					
					if ($result && $orders_number != 0 && $orders_number !='' && $orders_number != null ) 
					{
						
						$ini_tracking = "Tracking number:&nbsp;&nbsp; </td><td>";
						$end_tracking = "</td></tr></table><br><h2 style=";
						$tracking_number = substr($message, ((int)strpos($message,$ini_tracking) + 38), (( (int)strlen(substr($message, 0, strpos($message, $end_tracking)))) -((int)strpos($message,$ini_tracking) + 38)));
						
						
						$ini_shipped_date = "Shipped date:&nbsp;&nbsp; </td><td>";
						$end_shipped_date = "</td></tr><tr><td>Shipping method";
						$shipped_date = substr($message, ((int)strpos($message,$ini_shipped_date) + 35), (( (int)strlen(substr($message, 0, strpos($message, $end_shipped_date)))) -((int)strpos($message,$ini_shipped_date) +  35)));
						
						
						
						$ini_shipped_address = "es</td><td valign=\"top\" style=\"border:solid 1px #BC90Cf;padding:5px;\">";
						$end_shipped_address = "</td></tr></table><br><br><h2 style=\"color:#6A0087;";
						$shipped_address = substr($message, ((int)strpos($message,$ini_shipped_address) + 70), (((int)strlen(substr($message, 0, strpos($message, $end_shipped_address)))) -((int)strpos($message,$ini_shipped_address) +  70)));
						
						
						
						$ini_product = '<h2 style="color:#6A0087; font-size:15px; margin: 0px 0px -15px;">Items shipped</h2><br>';
						$end_product = "<br><br><h2 style=\"color:#6A0087; font-size:15px; margin: 0px 0px -15px;\">";
						$products = substr($message, ((int)strpos($message,$ini_product) + strlen($ini_product)), (((int)strlen(substr($message, 0, strrpos($message, $end_product)))) - ((int)strpos($message,$ini_product) + strlen($ini_product))));
						
						
						$ship_data =date('Y-m-d\TH:i:s',strtotime($shipped_date));
						
						$products = preg_split("/\<tr\>/", $products);
						
						foreach ($products as $key => $product) 
						{
							if ($key < 2) 
								continue;
							// $con = $con + 1 ;
							$ini_quentity = '<td align="center" style="border:solid 1px #BC90Cf;padding:5px;">';
							$end_quentity = "</td></tr>";
							$quantity = substr($product, ((int)strpos($product,$ini_quentity) + (int)strlen($ini_quentity)), (( (int)strlen(substr($product, 0, strpos($product, $end_quentity)))) -((int)strpos($product,$ini_quentity) + (int)strlen($ini_quentity))));
							// echo "<h1><xmp>".(string)$quantity."</xmp></h1>";
// 							
							$ini_tipro = "<td style=\"border:solid 1px #BC90Cf;padding:5px;\">";
							$end_tipro = "</td><td align=";
							$tipro = substr($product, ((int)strpos($product,$ini_tipro) + (int)strlen($ini_tipro)), (( (int)strlen(substr($product, 0, strpos($product, $end_tipro)))) -((int)strpos($product,$ini_tipro) +  (int)strlen($ini_tipro))));
							// echo "<h1><xmp>".(string)$tipro."</xmp></h1>";
							$temp_por = 0 ;
							$id_pro ;
							
							foreach ($result as $key_pro => $pro_ord) 
							{
								similar_text($pro_ord->products_name_product, $tipro, $por);
								
								$id_pro = $por > $temp_por ? $key_pro : $id_pro;
								$temp_por = $por > $temp_por ? $por : $temp_por;
								
								// echo "<br>".$por."\t".$pro_ord->products_name_product;
							}
							// echo "<br>".$id_pro." ".$temp_por."<br>";
							
							if ($tracking_number != '' && !is_null($tracking_number) && $ship_data != '' && !is_null($ship_data) && $quantity != '' && !is_null($quantity)) 
							{
									
								$con = $con + 1;
								$data_xml['@@'.$con.'_|Message'] = array(
										'v@lue' => array(
											'MessageID' => array('v@lue' => $con),
											'OrderFulfillment' => array('v@lue' => array( 
													'AmazonOrderID' => array('v@lue' => (string)$result[$id_pro] -> orders_amazon_order_id),
													'FulfillmentDate' => array('v@lue' => $ship_data),
													'FulfillmentData' => array('v@lue' => array(
															'CarrierCode' => array('v@lue' => 'USPS'),
															'ShippingMethod' => array('v@lue' => 'x'),
															'ShipperTrackingNumber' => array('v@lue' => $tracking_number),
														),
													),
													'Item' => array('v@lue' =>array(
															'AmazonOrderItemCode' => array('v@lue' => (string)$result[$id_pro] -> products_order_item_id),
															'Quantity' => array('v@lue' => (string)$quantity),		
														)
													),
												)
											)
										)
									);
										
									$data_update_traking[$con] =	array(
										'data_update' => array(
											'orders_tracking_Ship_date'=> $ship_data,
											'orders_tracking_carrier_code'=> 'USPS',
											'orders_tracking_carrier_name'=> '',
											'orders_tracking_Tracking_number'=> $tracking_number,   
											'id_orders_products'=> (int)$result[$id_pro] -> id_pro,
											'orders_tracking_quantity_shipped' => (int)$quantity,
											'orders_tracking_number_email' => $value,
											'orders_tracking_flag_shipping' => '0',
											'orders_tracking_n_mesage_api' => $con 
										),
										'data_relation' => array(
											'amazon_order_id'=>(string)$result[$id_pro] -> orders_amazon_order_id
										)	
		                    		 );
							}
							else 
							{
								$config['mailtype'] = 'html';
								$this->email->initialize($config);
								$this->email->clear();
								//$this->email->from('ben@puredigitalusa.com','Your Site Tester');
								$this->email->from('yoursitetester@gmail.com','DEV Run email - etermart');
								$this->email->to('edwin@igniweb.com');
								$this->email->cc('florez@igniweb.com,david@igniweb.com'); 
								$this->email->subject('Impossible to get all parameters');
								$this->email->message('Subject is: '.$over[0]->subject);
								$this->email->send();
								echo "error fields imcomplet";
								echo "<hr><hr><hr><hr>";
							}
						}
					}
				}
				
			}
			
			if(sizeof($data_xml) > 0){
				
			}else{
				// $config['mailtype'] = 'html';
				// $this->email->initialize($config);
				// $this->email->clear();
				// $this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
				// $this->email->to('edwin@igniweb.com');
				// $this->email->cc('florez@igniweb.com,david@igniweb.com'); 
				// $this->email->subject('Run email strawberry');
				// $this->email->message('Cron ran on LIVE without messages (Strawberry)');
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
				
			if(isset($data_update_traking))
			{
				$data_xml_result = object_2_xml($array2xml_complete,'<?xml version="1.0" encoding="UTF-8"?>',0);
				
				echo '<pre><xmp>';
				print_r($data_xml_result);
				echo '</xmp></pre>';
				
				$response_api = $this -> update_traking($data_xml_result);
				
				$errors_on_sumbission = get_content_from_path($response_api, 'Error');
				
				$error = false;
				$buffer = null;
				
				if(is_null($errors_on_sumbission)){
					$sumission_id = get_content_from_path($response_api, 'SubmitFeedResult/FeedSubmissionInfo/FeedSubmissionId');
					if($sumission_id){
						foreach($data_update_traking as $key => $orders){
							
							$orders['data_update']['orders_tracking_FeedSubmissionId'] = (string)$sumission_id;
							$traking = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products']))->get('bf_orders_tracking');
							$traking = $traking -> result();
							
							if(!$traking){
								echo "new";
								$this -> db -> insert('bf_orders_tracking',$orders['data_update']);
							}else{
								echo "exist"; 
								$traking2 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ))->get('bf_orders_tracking');
								$traking2 = $traking2 -> result();
								if (!$traking2){
									$traking3 = $this -> db -> where( array('id_orders_products'=>(int)$orders['data_update']['id_orders_products'] , 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number']))->get('bf_orders_tracking');
									if(!$traking3){
										echo "<h1>case 1</h1>";
										$this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_Tracking_number' => $orders['data_update']['orders_tracking_Tracking_number'] ));	
									}else{
										echo "<h1>case 2</h1>";
										$this->db->update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products']));	
									}
								}else{
									echo "<h1>case 3</h1>";
									$this-> db-> update('bf_orders_tracking', $orders['data_update'], array('id_orders_products' => (string)$orders['data_update']['id_orders_products'], 'orders_tracking_number_email' => $orders['data_update']['orders_tracking_number_email'] ));
								}
							}
							
							$status_data['orders_order_status'] = 'Waiting track confirm';
							$where_condition = array('orders_amazon_order_id' => $orders['data_relation']['amazon_order_id']);
							$this->db->update('bf_orders',$status_data, $where_condition);
							
							imap_mail_move($inbox , $orders['data_update']['orders_tracking_number_email'] , 'strawberry');
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
						$message_2 = 'We can\'t get a valid submission Id from API response (Live)'.date("Y-m-d H:i:s");
					}
				}else{
					$error = true;
					$message_2 = 'We get an error from Amazon API (Live)'.date("Y-m-d H:i:s");
					//send_mail
				}
			}
			
			if($message_2 != '' || $message_2 != ' '){
				$message_2 = 'Run Live Time '.date("Y-m-d H:i:s");
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
				// $path = './downloads/tracking_confirm/strawberry_error_'.date('Y-m-d_H.i.s').'.txt';
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
// 			
			// $this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
			// $this->email->to('edwin@igniweb.com');
			// $this->email->cc('florez@igniweb.com,david@igniweb.com'); 
			// $this->email->subject('Run email strawberry');
			// $this->email->message($message_2);
			// $this->email->send();

			//echo '<pre>';
			//print_r($emails);
			//echo '</pre>';
			//die('End');
		}
		imap_close($inbox);
	}
	
	public function update_traking($feed)
    {
       $this -> db -> select('') -> from('bf_supplier');
		//$this -> db -> like('supplier_supplier_name', 'Mirage', 'after');
		$this -> db -> like('supplier_supplier_name', 'Strawberry', 'after');
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
		        
        //$feed = $data;
    
	// echo "<xmp>".$feed."</xmp>";
	// die("ee");
	
        $feedHandle = @fopen('php://temp', 'rw+');
        fwrite($feedHandle, $feed);
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

    public function covert_Signature($url_string, $secret_key)
    {
        
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