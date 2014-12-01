<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class cron_amazon_fee extends Front_Controller
{
	var $amazon_cofiguration=array();
	public function __construct()
	{
		parent::__construct();


		die('Review');
		
		
		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		
		$amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find('1');
		
		$this -> amazon_cofiguration['merchant_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_merchant_ID ;
		$this -> amazon_cofiguration['marketplace_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_marketplace_ID;
		$this -> amazon_cofiguration['AWS_Access_Key_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_AWS_Access_Key_ID;
		$this -> amazon_cofiguration['secret_key'] = $amazon_cofiguration_query -> amazon_cofiguration_secret_key;
			
	}

	public function get_listen_report()
	{
			
		$params_item = array(
		    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
		    'Action' => "GetReportRequestList",
		    'Marketplace' => $this -> amazon_cofiguration['marketplace_ID'],
		    'MaxCount'=>100,
		    'ReportProcessingStatusList.Status.1'=>'_DONE_',
		    'ReportTypeList.Type.1'=>'_GET_PAYMENT_SETTLEMENT_DATA_',
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
		// echo "<br/>".$url."<br/>";
		// echo '<pre>';
		// print_r($parsed_xml_p);
		// echo '</pre>';
		// die('(^_^)');
		
		return ($parsed_xml_p);
	}

	public function api_feed_amazon($ReportId=null)
	{
		if($ReportId == null)
		{
			$ReportId_url =  $this->uri->segment(3);	
		}
		
		
		$params_item = array
							(
							    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
							    'Action' => "GetReport",
							    'Marketplace' => $this -> amazon_cofiguration['marketplace_ID'],
							    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
							    'ReportId' => $ReportId,
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
		// print_r($parsed_xml_p);
		// echo '</pre>';
		
		return ($parsed_xml_p);
	}


	public function update_fee()
	{
		
		
		$resport = $this -> get_listen_report();
			
		$report_id= $resport -> GetReportRequestListResult -> ReportRequestInfo[0] -> GeneratedReportId;
		
		$result_api_fee = $this-> api_feed_amazon($report_id);
		
		$cont= 1 ; 
		foreach ($result_api_fee -> Message -> SettlementReport -> Order as $key => $result_api_fee_order) 
		{
			$result_query_order = $this -> orders_model ->find_by('orders_amazon_order_id' , (string)$result_api_fee_order -> AmazonOrderID);
			
			if ($result_query_order)
			{
				$amazon_total_fee_Commission = 0;
				$amazon_total_fee_Shipping = 0;
				echo "<hr><hr><hr>".$cont++;
				echo '<pre>';
				print_r($result_query_order);
				echo '</pre>';
				
				$result_query_order_product = $this -> Orders_products_model -> where('products_amazon_order_id',$result_query_order -> id)-> find_all();
				foreach ($result_query_order_product as $key => $order_product) 
				{
					echo '<pre>';
					print_r($order_product);
					echo '</pre>'; 
					echo '<pre>';
					print_r($result_api_fee_order);
					echo '</pre>';
					foreach ($result_api_fee_order -> Fulfillment -> Item  as $item_api) 
					{
						
						if ((string)$item_api -> SKU == $order_product -> products_seller_sku && (string)$item_api -> AmazonOrderItemCode == $order_product -> products_order_item_id) 
						{
							 $data_update =array();
							 
							foreach ($item_api -> ItemFees -> Fee as $fee_type) 
							{
								switch ((string)$fee_type -> Type) 
								{ 
									case 'Commission':
											$data_update['products_fee_Commission']	= abs((float)$fee_type -> Amount);
											$amazon_total_fee_Commission = $amazon_total_fee_Commission + abs((float)$fee_type -> Amount);
										break;
									case 'SalesTaxServiceFee':
											$data_update['products_fee_SalesTaxServiceFee']	= abs((float)$fee_type -> Amount);
										break;
									case 'ShippingHB':
											$data_update['products_fee_Shipping']	= abs((float)$fee_type -> Amount);
											$amazon_total_fee_Shipping = $amazon_total_fee_Shipping + abs((float)$fee_type -> Amount);
										break;
									
									default:
										
										break;
								}
							}
							echo '<pre>';
							print_r($data_update);
							echo '</pre>';
							$this -> Orders_products_model -> update($order_product -> id , $data_update);
							break;
						} 
						
					} 
					echo "<hr>";
				}
				// $this -> orders_model -> update($result_query_order -> id , array('amazon_fee_Commission'=> $amazon_total_fee_Commission, 'amazon_fee_Shipping'=> $amazon_total_fee_Shipping));
			} 
			
		}
		if ($cont == 1) 
		{
			echo "no encontro ordenes<br>";
		}
		die('(^_^)');
	} 
	
		
	
	
	
	
	
	
	
	
	
	
}//end class