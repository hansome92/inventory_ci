<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");


class cron_orders_amazon_cancel extends Front_Controller
{
	var $amazon_cofiguration=array();
	public function __construct()
	{
		parent::__construct();
			 
		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		$this->load->model('supplier/supplier_model', null, true);
		$this->load->library('email');
		
		// $amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find('1');
// 		
		// $this -> amazon_cofiguration['merchant_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_merchant_ID ;
		// $this -> amazon_cofiguration['marketplace_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_marketplace_ID;
		// $this -> amazon_cofiguration['AWS_Access_Key_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_AWS_Access_Key_ID;
		// $this -> amazon_cofiguration['secret_key'] = $amazon_cofiguration_query -> amazon_cofiguration_secret_key;
			
	}
	
	public function index()
	{		
		$amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find_all();
		
		foreach ($amazon_cofiguration_query as $key => $accouts) {
			$this -> amazon_cofiguration['id'] = $accouts -> id;				
			$this -> amazon_cofiguration['merchant_ID'] = $accouts -> amazon_cofiguration_merchant_ID ;
			$this -> amazon_cofiguration['marketplace_ID'] = $accouts -> amazon_cofiguration_marketplace_ID;
			$this -> amazon_cofiguration['AWS_Access_Key_ID'] = $accouts -> amazon_cofiguration_AWS_Access_Key_ID;
			$this -> amazon_cofiguration['secret_key'] = $accouts -> amazon_cofiguration_secret_key;
				
			$response = $this -> ListOrders();
			$this -> review_order($response->ListOrdersResult->Orders);	
			
			echo 'Acount '.$accouts -> id.'<br><hr><hr>';
			
			
		}
		
		die('');
	}//end index()

	//--------------------------------------------------------------------
	public function ListOrders() 
	{
		$params = array(
	    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
	    'Action' => "ListOrders",
	    'MarketplaceId.Id.1' => $this -> amazon_cofiguration['marketplace_ID'],
	    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
	    'SignatureMethod' => "HmacSHA256",
	    'SignatureVersion' => "2",
	    'OrderStatus.Status.1'=> "Canceled",
		'CreatedAfter'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (time()-(60*60*24*15))),
		//'CreatedAfter'	=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (strtotime('2014-03-10 00:00:00'))),
		'CreatedBefore'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (time()-(60*5))),
		// 'CreatedBefore'	=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (strtotime('2013-10-28 23:59:59'))), 
		'Timestamp'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
		'Version'=> "2011-01-01"
		);  
		
		 
		// Sort the URL parameters 
		$url_parts = array();
		foreach(array_keys($params) as $key)
		    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params[$key]));
		sort($url_parts);
		
		// Construct the string to sign
		$url_string = implode("&", $url_parts);
		$string_to_sign = "GET\nmws.amazonservices.com\n/Orders/2011-01-01\n" . $url_string;
		
		// Sign the request
		$signature = hash_hmac("sha256", $string_to_sign, $this -> amazon_cofiguration['secret_key'] , TRUE);
		
		// Base64 encode the signature and make it URL safe
		$signature = urlencode(base64_encode($signature));
		
		$url = "https://mws.amazonservices.com/Orders/2011-01-01" . '?' . $url_string . "&Signature=" . $signature;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 250);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$response = curl_exec($ch);
		
		// echo $url;
		
		$parsed_xml = simplexml_load_string($response);
		// echo '<pre>';
		// print_r($parsed_xml);
		// echo '</pre>';
		// die('(^_^)');
			
		return ($parsed_xml);
	}

	public function review_order($orders)
	{
		if (is_object($orders)) 
		{			
			foreach ($orders->Order as $key => $value)
		  	{
		  		echo (string)$value -> AmazonOrderId;
				
		  		if($order_result = $this -> orders_model -> find_by(array('orders_amazon_order_id'=> (string)$value -> AmazonOrderId)))
				{
					if($order_result -> orders_order_status != 'Canceled' || $order_result -> orders_order_status == 'Canceled')
					{
						$data_update_order = array(
													'orders_order_status' => (string)$value -> OrderStatus,
													'profit' => 0,
													'orders_ROI' => 0
													);
						
						$this -> orders_model -> update($order_result -> id , $data_update_order);
						$this -> update_product_order($order_result -> id);
						echo '<pre>';
						print_r((string)$value -> OrderStatus);
						echo '</pre><hr>';
					}
				}
		  	}
		}
		else 
		{
			echo "failed conection api orders";	
		}
	}
	
	public function update_product_order($id_orden)
	{
		
		$products = $this -> orders_products_model -> where('products_amazon_order_id', (int)$id_orden) -> find_all();
		foreach ($products as  $value) 
		{
			$data_update_producto = array(
											'products_ROI' => 0,
											'products_profit' => 0
										);
										
			$this -> orders_products_model -> update((int)$value -> id, $data_update_producto);
		}
		
	}
	
	
	
}//end class