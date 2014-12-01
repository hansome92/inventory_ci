<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");

    
class cron_orders_amazon_pending extends Front_Controller
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
			
			$response_orders  = $this -> order_peding($accouts -> id);
			
			if ($response_orders) {
				$response_api = $this -> ListOrders();
				echo '<hr><hr><br>';
				$this -> review_orders($response_orders, $response_api);
				echo "There are orders pending on the account ".$accouts -> id."<hr><hr><br>";
			} else {
				echo "There aren't orders pending on the account ".$accouts -> id."<hr><hr><br>";
			}			
		}
		
		die('');
	}//end index()

	//--------------------------------------------------------------------
	public function order_peding($account_id)
	{
		return $this -> orders_model -> where(array('orders_order_status' => 'Pending', 'orders_configuration_id' => $account_id)) -> find_all();
	}		
	
	//--------------------------------------------------------------------
	
	public function review_orders($orders, $response_api)
	{
		echo count($orders);
			
		foreach ($orders as $order) 
		{
			$flag_order = TRUE;
			foreach ($response_api -> ListOrdersResult -> Orders -> Order as $value_api) 
			{
								
				if ($order -> orders_amazon_order_id  == (string)$value_api -> AmazonOrderId && $order -> orders_order_status  == (string)$value_api -> OrderStatus ) 
				{
					echo "no " . (string) $value_api -> AmazonOrderId ."<br>";
					$flag_order = FALSE;
					break;
				} 
			}
			
			if ($flag_order) 
			{
				echo "si";
				$this -> update_order($order);
			}
			else 
			{
				echo "<hr>";	
			}
		}
	}
	
	public function update_order($order)
	{		
			echo "si";
					
			$response_api_get_order = $this -> get_order($order -> orders_amazon_order_id);
				
				
			if ( array_key_exists("GetOrderResult",$response_api_get_order)) 
			{
				$response_order =$response_api_get_order -> GetOrderResult -> Orders -> Order;
				
				if ((string)$response_order -> OrderStatus != 'Pending') 
				{
					$response_products = $this -> ListOrderItems((string)$order -> orders_amazon_order_id);
					
					
					if (array_key_exists("ListOrderItemsResult",$response_products)) 
					{
						$data_update_orders = array(
												'orders_purchase_date' => date('Y-m-d H:i:s',strtotime((string)$response_order->PurchaseDate)),
												'orders_last_update_date' => date('Y-m-d H:i:s',strtotime((string)$response_order->LastUpdateDate)),
												'orders_shipment_service_leve_category' => (string)$response_order->ShipmentServiceLevelCategory,
												'orders_fulfillment_channel' => (string)$response_order->FulfillmentChannel,
												'orders_order_status' => (string)$response_order->OrderStatus,
												'orders_amount' => (float)$response_order->OrderTotal->Amount,
												'orders_currency_code' => (string)$response_order->OrderTotal->CurrencyCode,
												'orders_buyer_name' => (string)$response_order->BuyerName,
												'orders_ship_service_level' => (string)$response_order->ShipServiceLevel,
												'orders_sales_channel' => (string)$response_order->SalesChannel,
												'orders_phone' => (string)$response_order->ShippingAddress->Phone,
												'orders_postal_code' => (string)$response_order->ShippingAddress->PostalCode , 
												'orders_name' => (string)$response_order->ShippingAddress->Name,
												'orders_country_code' => (string)$response_order->ShippingAddress->CountryCode,
												'orders_state_or_region' => (string)$response_order->ShippingAddress->StateOrRegion,
												'orders_address_line1' => (string)$response_order->ShippingAddress->AddressLine1,
												'orders_address_line2' => (string)$response_order->ShippingAddress->AddressLine2,
												'orders_city' => (string)$response_order->ShippingAddress->City,
												'orders_skus' => '',
												'orders_payment_method' => (string)$response_order->PaymentMethod
												);
						
						$this -> orders_model -> update($order -> id , $data_update_orders);
						
						$this -> update_items_orders($response_products -> ListOrderItemsResult, $order -> id);
						
						
					} 
					else 
					{
						echo '<pre>'; 
						echo "connexion with the api failure , products";
						echo '</pre>';
					}
				} 
				
			} 
			else 
			{
				for ($i=0; $i < 10000000; $i++) { 
						// echo "1";
					}
				echo '<pre>'; 
				echo "connexion with the api failure";
				echo '</pre>';
			}
			
					
	}
	
	public function sku_parent($sku) {
		$sku_parent ='';
		if(preg_match("/([a-zA-Z0-9].*)(\.)([0-9])(pk)/", (string)$sku, $resultpregm)) {
			$sku_parent =$resultpregm[1];
        } elseif(preg_match("/([a-zA-Z0-9].*)(\[\:\])(.*)/", (string)$sku, $resultpregm)) {
			$sku_parent =$resultpregm[1];
        } else  {
        	$sku_parent = $sku;
        }
        // echo $sku ." -> ".$sku_parent.'<br>';        
		return $sku_parent;			
	}
	//--------------------------------------------------------------------
	
	public function update_items_orders($items, $id_orders)
	{
		$shipping=0;
		$tax_cost = 0;
		$sku = '';
		$items_price = 0;

		foreach ($items -> OrderItems -> OrderItem as $key => $value)
  		{
  			$data_update_product = array(
									'asin' => (string)$value->ASIN, 
									'products_name_product' => (string)$value->Title, 
									'products_quantity_ordered' => (string)$value->QuantityOrdered,								
									'products_item_tax' => (string)$value->ItemTax->Amount,								
									'products_order_item_id' => (string)$value->OrderItemId, 
									'products_item_price' => (string)$value->ItemPrice->Amount,								
									'products_shipping_tax' => (string)$value->ShippingTax->Amount, 
									'products_shipping_price' => (string)$value->ShippingPrice->Amount,
									'products_shipping_discount' => (string)$value->ShippingDiscount->Amount,								 
									'products_seller_sku' => (string)$value->SellerSKU, 
									'products_seller_sku_parent' => $this->sku_parent((string)$value->SellerSKU), 
									'products_supplier' => $this -> supplier((string)$value->SellerSKU), 
									'products_condition_id' => (string)$value->ConditionId, 
									'products_quantity_shipped' => (string)$value->QuantityShipped, 
									'products_amazon_order_id' => $id_orders
								);
  			$result_product = $this->db->where(array('products_amazon_order_id' => $id_orders, 'asin' => (string)$value->ASIN)) -> get('bf_orders_products');
			$result_product = $result_product -> result();
			$result_product = $result_product[0];
			
			if ($result_product -> products_price_cost > 0 ) 
			{
				$data_update_product['product_Received_Amazon'] = ($data_update_product['products_item_price'] + $data_update_product['products_item_tax'] + $data_update_product['products_shipping_price'] + $data_update_product['products_shipping_tax'] ) - ($result_product -> products_fee_Commission + $result_product -> products_fee_Shipping + $result_product -> products_fee_SalesTaxServiceFee) ;
				
				$data_update_product['products_profit'] = $data_update_product['product_Received_Amazon'] - ($result_product -> products_price_cost + $result_product -> products_Shipping_cost + $data_update_product['products_item_tax'] + $data_update_product['products_shipping_tax']);
				
				$data_update_product['products_ROI'] = ($data_update_product['products_profit'] / ($result_product -> products_price_cost + $result_product -> products_Shipping_cost ))*100;

				$data_update_product['products_total_cost'] = $result_product -> products_price_cost + $result_product -> products_Shipping_cost ;
				
			}
			
			$items_price = $items_price + (float)$value->ItemPrice->Amount;
			$sku = (string)$value->SellerSKU;			
		 	$shipping = $shipping + (float)$value->ShippingPrice->Amount;
			$tax_cost = $tax_cost + (float)$value->ShippingTax->Amount + (float)$value->ItemTax->Amount;
			
			$this->db->where(array('products_amazon_order_id' => $id_orders, 'asin' => (string)$data_update_product['asin'])) -> update('bf_orders_products',$data_update_product); 
			
		}
		$update_order = array('amazon_fee_Shipping'=>$shipping, 'taxes_cost'=>$tax_cost ,'orders_supplier'=>$this -> supplier($sku), 'orders_items_prices'=>$items_price );
		
		$this -> review_order($id_orders);
		 
		$this -> orders_model -> update($id_orders , $update_order);
	}
	//--------------------------------------------------------------------
		
	public function get_order($AmazonOrderId) 
	{		
		$params_item = array(
		    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
		    'Action' => "GetOrder",
		    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
		    'AmazonOrderId.Id.1' => $AmazonOrderId,
		    'SignatureMethod' => "HmacSHA256",
		    'SignatureVersion' => "2",
			'Timestamp'=> gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
			'Version'=> "2011-01-01"
			);  
		 
		// Sort the URL parameters
		$url_parts = array();
		foreach(array_keys($params_item) as $key)
		    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
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
		$response_p = curl_exec($ch);
		
		$parsed_xml_p = simplexml_load_string($response_p);
		
		// echo "<br/>".$url."<br/>";
		
		return ($parsed_xml_p);
	}

	public function ListOrderItems($AmazonOrderId) 
	{		
		$params_item = array(
		    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
		    'Action' => "ListOrderItems",
		    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
		    'AmazonOrderId' => $AmazonOrderId,
		    'SignatureMethod' => "HmacSHA256",
		    'SignatureVersion' => "2",
			'Timestamp'=> gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time()),
			'Version'=> "2011-01-01"
			);  
		 
		// Sort the URL parameters
		$url_parts = array();
		foreach(array_keys($params_item) as $key)
		    $url_parts[] = $key . "=" . str_replace('%7E', '~', rawurlencode($params_item[$key]));
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
		$response_p = curl_exec($ch);
		
		$parsed_xml_p = simplexml_load_string($response_p);
		
		// echo "<br/>".$url."<br/>";
		
		return ($parsed_xml_p);
	}


	public function supplier($sku)
    {
                
        $supplier =  substr($sku, 0 ,2);
        $this -> db -> like('supplier_supplier_identifier', $supplier, 'after');
        $id_supplier = $this -> supplier_model -> limit(1) -> find_all();
                        
        if($id_supplier == null)
        {
            $id_supplier = "No Supplier"; 
        }
        else
        {
            $id_supplier = $id_supplier[0] -> supplier_supplier_name;
        }
                
        return $id_supplier;
        
    }
	
	public function ListOrders() 
	{
		$params = array(
	    'AWSAccessKeyId' => $this -> amazon_cofiguration['AWS_Access_Key_ID'] ,
	    'Action' => "ListOrders",
	    'MarketplaceId.Id.1' => $this -> amazon_cofiguration['marketplace_ID'],
	    'SellerId' =>  $this -> amazon_cofiguration['merchant_ID'],
   	    'OrderStatus.Status.1'=> "Pending",
	    'SignatureMethod' => "HmacSHA256",
	    'SignatureVersion' => "2",
		 'CreatedAfter'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (time()-(60*60*24*10))),
		//'CreatedAfter'	=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (strtotime('2013-05-07 00:00:00'))),
		'CreatedBefore'=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (time()-(60*5))),
		// 'CreatedBefore'	=>gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (strtotime('2013-05-07 23:59:59'))), 
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
		// print_r($parsed_xml->ListOrdersResult->CreatedBefore);
		// echo '</pre>';
		
		return ($parsed_xml);
	}
 	
 	public function review_order($id_order)
    {
        $result_orders_products = $this -> orders_products_model ->where('products_amazon_order_id',$id_order) -> find_all();
        foreach ($result_orders_products as $product) 
        {
            if ($product -> products_profit == '' || $product -> product_Received_Amazon == '' || $product -> products_ROI == 0) 
            {
                return false;
            } 
        }
        $this -> update_orders_totals($id_order, $result_orders_products);
        return true;    
    }
	
	public function update_orders_totals($id_order, $products)
    {
        $product_Received_Amazon = 0;
        $profit_product = 0;
        $roi = 0;
        $item_cost = 0;
        $shipping_cost = 0;
        $tax_cost = 0;
		
		$fees = 0;
        $shipping = 0;
        foreach ($products as $product) 
        {
            $product_Received_Amazon = $product_Received_Amazon + $product -> product_Received_Amazon;
            $profit_product = $profit_product + $product -> products_profit;
           
            $item_cost = $item_cost + $product -> products_price_cost;
            $shipping_cost = $shipping_cost +  $product -> products_Shipping_cost;  
            $tax_cost = $tax_cost + $product -> products_item_tax + $product -> products_shipping_tax;
			
			$fees = $fees +  $product -> products_fee_Shipping +  $product -> products_fee_Commission +  $product -> products_fee_SalesTaxServiceFee;  
            $shipping = $shipping + $product -> products_shipping_price ;
        }
		 $roi = ($profit_product /( $item_cost + $shipping_cost)) *100 ;
        $data_update = array(
                            'received_from_Amazon' => $product_Received_Amazon,
                            'profit' => $profit_product,
                            'orders_ROI' => $roi,
                            'item_cost' => $item_cost,
                            'shippin_cost' => $shipping_cost,
                            'taxes_cost' => $tax_cost ,
                            'amazon_fee_Shipping' => $shipping,
                            'amazon_fee_Commission' => $fees  
                            );
        $this -> orders_model -> update($id_order, $data_update);
        return true;
        
        
    }
}//end class