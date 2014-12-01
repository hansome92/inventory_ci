<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");
    
class test_script extends Front_Controller
{
	var $data_email = array();
	public function __construct()
	{
		
		parent::__construct();

				
	}
	public function index()
	{
			
		$date_send = '04/16/2014';// EDT
		$date_order ='2014-04-16 20:00:00'; // PDT 	
		$pattern = '/^\d{1,2}\/\d{1,2}\/\d{4}$/';
		// echo $this -> input -> get('orders_tracking_Ship_date');
		// echo "<hr>";
		if(!preg_match($pattern, $date_send))
		{
			$arrayName['message'] = "Invalid Date Format";
			echo json_encode($arrayName); 
			die();
		}
		$date_order_purchase_PDT = $date_order;
		
		$time_order_purchase_EDT = $this -> convert_timezome( $date_order_purchase_PDT, 'America/New_York','Y-m-d H:i:s', 'America/Los_Angeles');
		
		$time_order_purchase_UTC = $this -> convert_timezome($date_order_purchase_PDT, 'UTC', 'Y-m-d\TH:i:s', 'America/Los_Angeles');
		
		$date_limit_PDT = date('Y-m-d H:i:s', (strtotime($date_order_purchase_PDT) + (60*60*70)));
		
		$time_order = strtotime($time_order_purchase_EDT) - strtotime(date('Y-m-d 00:00:00' , strtotime($time_order_purchase_EDT))) ;
		
		$time_order = $time_order + strtotime($date_send);
		
		$date_send_EDT = date('Y-m-d H:i:s', $time_order);
		
		$date_send_PDT = $this -> convert_timezome($date_send_EDT, 'America/Los_Angeles');
		
		if (strtotime($date_order_purchase_PDT) > strtotime($date_send_PDT)) {
			$date_send_PDT = $date_order_purchase_PDT;
		}
		  
		if (time() < strtotime($date_send_EDT)) {
			$date_send_PDT =  $this -> convert_timezome(date('Y-m-d H:i:s'), 'America/Los_Angeles', 'Y-m-d\TH:i:s', 'America/New_York');
		}
		// if (strtotime($date_limit_PDT) < strtotime($date_send_PDT)) {
			// $date_send_PDT =  $date_limit_PDT;
// 			
		// }
		
		$date_send_UTC = $this -> convert_timezome($date_send_PDT, 'UTC', 'Y-m-d\TH:i:s', 'America/Los_Angeles');
		
        
       
		/*
		 * IGNIWEB Edwin H. 04/10/2013
		 * print hours in purchase, limit and the send
		 * America/New_York timeZone EDT
		 * America/Los_Angeles timeZone PDT	 		
		 * 
		 */
		echo "PDT O: " . $date_order_purchase_PDT;
		echo "<hr>";
		echo "EDT O: " . $time_order_purchase_EDT;
		echo "<hr>";
		echo "UTC O: " . $time_order_purchase_UTC;
		echo "<hr>";
		echo "PDT L: " . $date_limit_PDT;
		echo "<hr>";
		echo "EDT L: " . $this -> convert_timezome($date_limit_PDT, 'America/New_York', 'Y-m-d\TH:i:s', 'America/Los_Angeles');;
		echo "<hr>";
		echo "UTC L: " . $this -> convert_timezome($date_limit_PDT, 'UTC', 'Y-m-d\TH:i:s', 'America/Los_Angeles');;
		echo "<hr>";
		echo "EDT : " . $date_send_EDT;
		echo "<hr>";
		echo "PDT : " . $date_send_PDT;
		echo "<hr>";
		echo "UTC : " . $date_send_UTC;
		echo "<hr>";
		echo "EDT A: " . date('Y-m-d H:i:s' , time());
		
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
