<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

   
class cron_tracking_number extends Front_Controller
{
	var $amazon_cofiguration=array();
	var $url_string;
	public function __construct()
	{
		parent::__construct();

		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		$this -> load -> helper('obj_array_xml');
		$this->load->library('email');
		
	}

	public function index()
	{
		$tracking_already_processed = array();
			
		$this -> db -> select('ot.*, o.orders_configuration_id ,o.orders_amazon_order_id');	
		$this -> db -> from('bf_orders_tracking AS ot');
		$this -> db -> where(array('orders_tracking_flag_shipping' => (string)0, 'orders_tracking_FeedSubmissionId !='=>''));
		$this -> db -> join('bf_orders_products AS op' , 'op.id = ot.id_orders_products');
		$this -> db -> join('bf_orders AS o' , 'op.products_amazon_order_id = o.id');
		$this -> db -> order_by('orders_tracking_Ship_date','DESC');
		$this -> db -> limit(15);
		$orders_review = $this -> db -> get();		
		
		/*
		 * $this->db->order_by('orders_tracking_Ship_date','DESC');
		 * $orders_review = $this -> db -> get_where('bf_orders_tracking', array('orders_tracking_flag_shipping' => (string)0, 'orders_tracking_FeedSubmissionId !='=>''), 15);
		*/		
		
		echo '<h3>'.$this->db->last_query().'</h3>'; 
		// die();
		foreach ($orders_review -> result() as $key => $order_traking){
			
			if(in_array($order_traking->orders_tracking_FeedSubmissionId,$tracking_already_processed)){
				echo '<pre>';
				print_r($tracking_already_processed);
				echo '</pre>';
				//die('in');
				continue;
			}
			echo '<pre>'; 
			print_r($order_traking);
			echo '</pre>';
			$response = $this->review_tracking((int)$order_traking -> orders_tracking_FeedSubmissionId, $order_traking->orders_configuration_id);
			//$response = $this->review_tracking(7616936740);
			
			$status = get_content_from_path($response, 'Message/ProcessingReport/StatusCode');
			
			if($status == 'Complete'){
				
				$processing_sumary = get_content_from_path($response, 'Message/ProcessingReport/ProcessingSummary');
				
				$tracking_already_processed[] =  $order_traking->orders_tracking_FeedSubmissionId;
				
				$string_query = 'SELECT o.id
				FROM  `bf_orders_tracking` AS ot
				INNER JOIN bf_orders_products AS op ON ot.id_orders_products = op.id
				INNER JOIN bf_orders AS o ON op.products_amazon_order_id = o.id
				WHERE ot.orders_tracking_FeedSubmissionId =  \''.$order_traking->orders_tracking_FeedSubmissionId.'\'';
				
				$result_list = $this->db->query($string_query);
				
				$query_string = "UPDATE bf_orders_tracking SET orders_tracking_flag_shipping = '1', orders_tracking_message_error = '' WHERE orders_tracking_FeedSubmissionId = '".$order_traking->orders_tracking_FeedSubmissionId."'" ;
				$this->db->query($query_string);
				
				foreach($result_list->result() as $row){
					$query_string = "UPDATE bf_orders SET orders_order_status = 'Shipped' WHERE id = '".$row->id."'; \n";
					$this->db->query($query_string);

					# add product to inventory_pruchases
					$product_query = "INSERT INTO bf_inventory_purchases(date_purchased, sku, cost, qty)
					SELECT o.orders_purchase_date, op.products_seller_sku_parent,op.products_price_cost, op.products_quantity_ordered FROM bf_orders_products as op LEFT JOIN bf_orders as o on op.products_amazon_order_id=o.id WHERE o.orders_status_on_hold='0' and o.id='". $row->id. "';";
					$this->db->query($product_query);
				}
				
				if($processing_sumary->MessagesWithError > 0 || $processing_sumary->MessagesWithWarning > 0){
					
					$result = get_content_from_path($response, 'Message/ProcessingReport/Result');
					
					echo '<pre><xmp>';
					print_r($result);
					echo '</xmp></pre>';
					
					//$tracking_already_processed[] =  $order_traking->orders_tracking_FeedSubmissionId;
					
					$data_required = array(
						'Severity' => 'ResultCode',
						'ErrorCode' => 'ResultMessageCode',
						'ErrorDescription' => 'ResultDescription',
						'orders_amazon_order_id' => 'AdditionalInfo/AmazonOrderID',
						);
					
					$all_data = get_data_from_object($result,$data_required);
					
					foreach($all_data as $row){
						if($row['Severity'] = 'Error'){
							
							if(is_null($row['orders_amazon_order_id']) || $row['orders_amazon_order_id'] ==''){
								$string_query = 'SELECT o.id
								FROM  `bf_orders_tracking` AS ot
								INNER JOIN bf_orders_products AS op ON ot.id_orders_products = op.id
								INNER JOIN bf_orders AS o ON op.products_amazon_order_id = o.id
								WHERE ot.orders_tracking_FeedSubmissionId =  \''.$order_traking->orders_tracking_FeedSubmissionId.'\'';
								//echo $string_query.'<hr />';
								
								$result_list_2 = $this->db->query($string_query);
								
								$query_string = "UPDATE bf_orders_tracking SET orders_tracking_flag_shipping = '2', orders_tracking_message_error = '' WHERE orders_tracking_FeedSubmissionId = '".$order_traking->orders_tracking_FeedSubmissionId."'" ;
								$this->db->query($query_string);
								
								foreach($result_list_2->result() as $row_2){
									//echo '<h3.>'.$row_2->id.'</h3>';
									$query_string = "UPDATE bf_orders SET orders_order_status = 'Failed' WHERE id = '".$row_2->id."'; \n";
									//echo '<h3.>'.$query_string.'</h3>';
									$this->db->query($query_string);
								}
								
								//send_email
								$message = 'Run Live error submission id '.$order_traking->orders_tracking_FeedSubmissionId.' - '.date("Y-m-d H:i:s");
								
								$config['mailtype'] = 'html';
								$this->email->initialize($config);
								$this->email->clear();
								
								ob_start();
								ob_clean();
								print_r($response);
								//echo "\n\n\n";
								//print_r($response_api);
								$buffer = ob_get_flush();
								ob_end_clean();
								
								$path = './downloads/tracking_confirm/submission_id_error_'.date('Y-m-d_H.i.s').'.txt';
								$fp = fopen($path,'w');
								if($fp){
									fputs($fp,$buffer);
								}else{
									die('<h2>fopen failed</h2>');
								}
								fclose($fp);
								if(file_exists($path)){
									$this->email->attach($path);
								}
								
								//$this->email->from('ben@puredigitalusa.com','Your Site Tester');
								$this->email->from('yoursitetester@gmail.com','LIVE Run email - etermart');
								$this->email->to('edwin@igniweb.com');
								$this->email->cc('florez@igniweb.com,david@igniweb.com'); 
								$this->email->subject('Run submission');
								$this->email->message($message);
								$this->email->send();
								
								exit;
							}
							
							
							$query_string = 'UPDATE bf_orders SET orders_order_status = \'Failed\' WHERE orders_amazon_order_id = \''.$row['orders_amazon_order_id'].'\'';
							$this->db->query($query_string);
							
							$query_string = 'SELECT ot.id_orders_tracking
							FROM  `bf_orders_tracking` AS ot
							INNER JOIN bf_orders_products AS op ON ot.id_orders_products = op.id
							INNER JOIN bf_orders AS o ON op.products_amazon_order_id = o.id
							WHERE o.orders_amazon_order_id =  \''.$row['orders_amazon_order_id'].'\'';
							$this->db->query($query_string);
							
							$to_update = $this->db->query($query_string);
							
							foreach($to_update->result() as $row_2){
								$query_string = "UPDATE bf_orders_tracking SET orders_tracking_flag_shipping = '2', orders_tracking_message_error = CONCAT(orders_tracking_message_error, '"."\n"."','".addslashes($row['ErrorDescription'])."') WHERE id_orders_tracking = '".$row_2->id_orders_tracking."'";
								$this->db->query($query_string);
							}
						}else{
							$query_string = 'SELECT ot.id_orders_tracking
							FROM  `bf_orders_tracking` AS ot
							INNER JOIN bf_orders_products AS op ON ot.id_orders_products = op.id
							INNER JOIN bf_orders AS o ON op.products_amazon_order_id = o.id
							WHERE o.orders_amazon_order_id =  \''.$row['orders_amazon_order_id'].'\'';
							$to_update = $this->db->query($query_string);
							
							foreach($to_update->result() as $row_2){
								$query_string = "UPDATE bf_orders_tracking SET orders_tracking_flag_shipping = '3', orders_tracking_message_error = CONCAT(orders_tracking_message_error, '"."\n"."','".addslashes($row['ErrorDescription'])."') WHERE id_orders_tracking = '".$row_2->id_orders_tracking."'";
								$this->db->query($query_string);
							}
						}
					}
				}
			}else{
				echo '<div style="background:red;"><pre>';
				print_r($response);
				echo '</pre></div>';
			}
			
			
			//=======================================================================================================================
			//=======================================================================================================================
			//=======================================================================================================================
			
			//This validation exist on old script,I include that just in case for a future
			
			//Add Edwin throttle control.
			
			//if ($order_traking -> orders_tracking_message_error != '' && $order_traking -> orders_tracking_message_error  != 'Request is throttled') 
			//{
			//	echo "impopsible";
			//	$message_error =  $this -> error_message($response);
			//	$this->db->where('id_orders_tracking', $order_traking -> id_orders_tracking);
			//	$this->db->update('bf_orders_tracking', array('orders_tracking_flag_shipping'=> 2,'orders_tracking_message_error'=> $message_error)); 
			//	
			//}
			//else {
			//	echo "prtimi";
			//	$message_error =  $this -> error_message($response);
			//	$this->db->where('id_orders_tracking', $order_traking -> id_orders_tracking);
			//	$this->db->update('bf_orders_tracking', array('orders_tracking_message_error'=> $message_error)); 
			//}
			
			//echo '<pre><xmp>';
			//print_r($processing_sumary);
			//echo '</xmp></pre><hr />';
			//die();
			//// $response = $this -> review_tracking((int)7208634970);
			//if ($this -> read_response_Successful($response)) 
			//{
			//	echo "yes";
			//	$this->db->where('id_orders_tracking', $order_traking -> id_orders_tracking);
			//	$this->db->update('bf_orders_tracking', array('orders_tracking_flag_shipping'=> 1, 'orders_tracking_message_error'=> '')); 
			//} 
			//else 
			//{
			//	echo "no";
			//	$message_error =  $this -> error_message($response);
			//	$this->db->where('id_orders_tracking', $order_traking -> id_orders_tracking);
			//	$this->db->update('bf_orders_tracking', array('orders_tracking_message_error'=> $message_error)); 
			//}
			//echo '<pre>';
			//print_r($response);
			//echo '</pre><hr><hr><hr>';
			
			//=======================================================================================================================
			//=======================================================================================================================
			//=======================================================================================================================
		}
		
		die('end');
		
		
		
	}
	
	public function read_response_Successful($response)
	{
		if (isset($response -> Message -> ProcessingReport -> ProcessingSummary -> MessagesSuccessful) && (int)$response -> Message -> ProcessingReport -> ProcessingSummary -> MessagesSuccessful === 1) 
			return true;
		else 
			return false;	
	}
	
	public function error_message($response)
	{
		if(isset($response -> Error))
		{
			return (string)$response -> Error -> Message ;			
		}
		elseif (isset($response -> Message -> ProcessingReport -> Result -> ResultDescription)) 
		{
			return (string)$response -> Message -> ProcessingReport -> Result -> ResultDescription;
		}
	}

	public function review_tracking($id_report, $amazon_configuration_id){
		
		$amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find($amazon_configuration_id);
		// echo '<pre>'; 
		// print_r($amazon_cofiguration_query);
		// echo '</pre>';
		$this -> amazon_cofiguration['merchant_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_merchant_ID ;
		$this -> amazon_cofiguration['marketplace_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_marketplace_ID;
		$this -> amazon_cofiguration['AWS_Access_Key_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_AWS_Access_Key_ID;
		$this -> amazon_cofiguration['secret_key'] = $amazon_cofiguration_query -> amazon_cofiguration_secret_key;
		
		$params_item = array(
		    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
		    'Action' => "GetFeedSubmissionResult",
		    'FeedSubmissionId' =>  $id_report,
		    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
		    'SignatureMethod' => "HmacSHA256",
		    'SignatureVersion' => "2",
			'Timestamp'=> gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
			'Version'=> "2009-01-01"
			);  
		 
		// Sort the URL parameters
		$url_parts = array();
		foreach(array_keys($params_item) as $key)
		    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
		sort($url_parts);
		
		// Construct the string to sign
		$url_string = implode("&", $url_parts);
		$string_to_sign = "GET\nmws.amazonservices.com\n/\n" . $url_string;
		
		// Sign the request
		$signature = hash_hmac("sha256", $string_to_sign, $this -> amazon_cofiguration['secret_key'] , TRUE);
		
		// Base64 encode the signature and make it URL safe
		$signature = urlencode(base64_encode($signature));
		
		$url = "https://mws.amazonservices.com/" . '?' . $url_string . "&Signature=" . $signature;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 250);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$response_p = curl_exec($ch);
		
		$parsed_xml_p = simplexml_load_string($response_p);
		// echo '<pre>'; 
		// print_r($response_p);
		// print_r($parsed_xml_p);
		// echo '</pre>';
		// die('eeeee'); 
		// echo "<br/>".$url."<br/>";
		
		return ($parsed_xml_p);
	}

	
	public function covert_Signature($params_item)
	{
		$url_parts = array();
		foreach(array_keys($params_item) as $key)
		    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
		sort($url_parts);
		$this -> url_string = implode("&", $url_parts);
		$string_to_sign = "POST\nmws.amazonservices.com\n/\n" . $this -> url_string;
		$signature = hash_hmac("sha256", $string_to_sign, $this -> amazon_cofiguration['secret_key'] , TRUE);
		return urlencode(base64_encode($signature));
	}
	
	
	
}//end class