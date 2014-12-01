<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");
    
class script_update_sku extends Front_Controller {
	var $data_email = array();
	public function __construct() {
		parent::__construct();
	}
	public function index() {
		$products = $this->db->select('id,products_seller_sku')->where('products_seller_sku_parent','')->get('bf_orders_products')->result();

		foreach ($products as $key => $product) {
			$sku_parent ='';
			// echo "<pre>";
			// print_r($product); 
			// echo "</pre>";
			if(preg_match("/([a-zA-Z0-9].*)(\.)([0-9])(pk)/", (string)$product->products_seller_sku, $resultpregm)) {
				$sku_parent =$resultpregm[1];
	           	echo $product->products_seller_sku ." -> ".$sku_parent;
	   //         	echo "opcion 1 <pre>";
				// print_r($resultpregm); 
				// echo "</pre>";

	        } elseif(preg_match("/([a-zA-Z0-9].*)(\[\:\])(.*)/", (string)$product->products_seller_sku, $resultpregm)) {
				$sku_parent =$resultpregm[1];
	           	echo $product->products_seller_sku ." -> ".$sku_parent;
	   //         	echo "opcion 2<pre>";ls
				// print_r($resultpregm); 
				// echo "</pre>";
	        } else  {
	        	$sku_parent = $product->products_seller_sku;
	        	echo "no found";
	        }
	        
	        $this->db->where('id', $product->id)->update('bf_orders_products', array('products_seller_sku_parent'=>$sku_parent ));
  

	       echo "<hr><hr>";
		}
		echo "<hr>";
		die('<hr>******');
			
	}
	public function  convert_timezome($date, $time_zone , $format = false, $default_timezone = false){
		// http://php.net/manual/es/timezones.php
		if (!$default_timezone) {
			$default_timezone = date_default_timezone_get();
		}
		$datetime = new DateTime($date , new DateTimeZone($default_timezone));
		$tz = new DateTimeZone($time_zone);
		
		$datetime->setTimeZone($tz);
				
		if ($format) 
			return $datetime->format($format);
		else
			return $datetime->format('Y-m-d H:i:s');
	}
}
