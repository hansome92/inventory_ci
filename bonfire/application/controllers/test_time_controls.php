<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class test_time_controls extends Front_Controller
{
	var $amazon_cofiguration=array();
	public function __construct()
	{
		parent::__construct();

		
		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		
		$amazon_cofiguration_query = $this -> amazon_cofiguration_model -> find('1');
		
		$this -> amazon_cofiguration['merchant_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_merchant_ID ;
		$this -> amazon_cofiguration['marketplace_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_marketplace_ID;
		$this -> amazon_cofiguration['AWS_Access_Key_ID'] = $amazon_cofiguration_query -> amazon_cofiguration_AWS_Access_Key_ID;
		$this -> amazon_cofiguration['secret_key'] = $amazon_cofiguration_query -> amazon_cofiguration_secret_key;
		
		
		$string_date = date('Y-m-d H:i:s');
		
		$dates_in['order_date'] = '2014-04-13 19:11:18';
		$dates_in['confirmation_date'] = '2014-04-16 '.date('H:i:s');
		//$dates_in['confirmation_date'] = '2014-04-15 20:00:00';
		$dates_in['alternative_date'] = '04/16/2014 ' . date('H:i:s');
		$dates_in['current_date'] = $string_date;
		
		$time_zones =
		array(
			'orders' =>
				array(
					'time_zone' => 'PDT',
					'printable_format' => 'F j, Y h:i:s A \P\D\T',
					'debug_format' => 'Y-m-d H:i:s'
				),
			'server' =>
				array(
					'time_zone' => date_default_timezone_get(),
					//'time_zone' => 'America/New_York',
					'printable_format' => 'F j, Y h:i:s A \P\D\T',
					'debug_format' => 'Y-m-d H:i:s'
				),
			'mws' => 
				array(
					'time_zone' => 'UTC',
					'printable_format' => 'Y-m-d\TH:i:s',
					'debug_format' => 'Y-m-d H:i:s'
				),
		);
		
		
		//$dates_out['order_date'] =
		//	$this->equivalen_times_by_zone($dates_in['order_date'], $time_zones['orders']['time_zone'], $time_zones['mws']['time_zone'], $time_zones['orders']['debug_format']);
		//$dates_out['confirmation_date'] =
		//	$this->equivalen_times_by_zone($dates_in['confirmation_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone'], $time_zones['server']['debug_format']);
		//$dates_out['alternative_date'] =
		//	$this->equivalen_times_by_zone($dates_in['alternative_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone'], $time_zones['server']['debug_format']);
		//$dates_out['current_date'] =
		//	$this->equivalen_times_by_zone($dates_in['current_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone'], $time_zones['server']['debug_format']);
		
		$dates_out['order_date'] =
			$this->equivalen_times_by_zone($dates_in['order_date'], $time_zones['orders']['time_zone'], $time_zones['mws']['time_zone']);
		$dates_out['confirmation_date'] =
			$this->equivalen_times_by_zone($dates_in['confirmation_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone']);
		$dates_out['alternative_date'] =
			$this->equivalen_times_by_zone($dates_in['alternative_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone']);
		$dates_out['current_date'] =
			$this->equivalen_times_by_zone($dates_in['current_date'], $time_zones['server']['time_zone'], $time_zones['mws']['time_zone']);
			
		
		$dates_out['limit_date'] = $dates_out['order_date'] + (60*60*72);
		
		if($dates_out['confirmation_date'] > $dates_out['current_date']){
			die('back to the future');
			die('there are something weird with the date convertions, please try again in a minutes, if this problem presists get in contact with de developer team');
		}
		
		if($dates_out['confirmation_date'] < $dates_out['order_date']){
			//$dates_out['confirmation_date'] = (15*60) + $dates_out['order_date'];
			die('Date under purchase');
		}
		
		if($dates_out['confirmation_date'] > $dates_out['limit_date']){
			die('max 3 days');
		}
		
		foreach($dates_out as $k => $value){
			$dates_out[$k] = date($time_zones['mws']['printable_format'],$dates_out[$k]);
		}
		
		echo '<div style="background:black;color:white;"><pre>';
		print_r($dates_in);
		echo '</pre></div>';
		
		echo '<div style="background:orange;"><pre>';
		print_r($dates_out);
		echo '</pre></div>';
		
		$edwin_test = $this -> convert_timezome($dates_in['order_date'], 'UTC', 'Y-m-d\TH:i:s', 'America/Los_Angeles');
		
		echo '<hr /><hr /><hr />';
		echo '<h1>'.$edwin_test.'</h1>';
		
		$dates_in['order_date'] = '04/11/2014';
		$edwin_test = $this -> convert_timezome($dates_in['order_date'], 'UTC', 'Y-m-d\TH:i:s', 'America/Los_Angeles');
		echo '<hr /><hr /><hr />';
		echo '<h1>'.$edwin_test.'</h1>';
		
		$dates_in['order_date'] = '2014-04-16 20:00:00';
		$edwin_test = $this -> convert_timezome($dates_in['order_date'], 'PDT', 'Y-m-d H:i:s', 'EDT');
		echo '<hr /><hr /><hr />';
		echo '<h1>'.$edwin_test.'</h1>';
		
		//echo '<h1>'.$string_date.'</h1>';
		//echo '<h1>'.date_default_timezone_get().'</h1>';
		//$time_zone = new DateTimeZone('America/New_York');
		//$datetime = new DateTime($string_date , $time_zone);
		//echo '<h1>'.$datetime->format('Y-m-d H:i:s').'</h1>';
		//
		//$time_zone = new DateTimeZone('UTC');
		//$datetime = new DateTime($string_date , $time_zone);
		//echo '<h1>'.$datetime->format('Y-m-d H:i:s').'</h1>';
		die();
	}
	
	private function  convert_timezome($date, $time_zone , $format = false, $default_timezone = false){
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
	
	/*
	 *@method equivalen_times_by_zone converts an time zone string from a determinate origin zone to another different
	 *@param String $date_string is a valid string that represents a date
	 *@param String $zone_origin is the origin where we want to get the equivalent date
	 *@param String $zonde_destination is the destination date to get
	 *@param String $output_format is the format how we want to get the equivalent time, if it is missing, the unix time is retreive
	*/
	
	private function equivalen_times_by_zone($date_string, $zone_origin, $zonde_destination, $output_format = false){
		$return = false;
		//We need at least 3 parametters to work
		if($date_string && $zone_origin && $zonde_destination){
			//create a DateTime object using the $zone_origin
			$return = new DateTime($date_string, new DateTimeZone($zone_origin));
			if($return){
				//if the new DateTime was successful then try to convert
				$new_time_zone = new DateTimeZone($zonde_destination);
				if($return->setTimeZone($new_time_zone) !== false){
					//If it was possible return the date according with the output format
					if($output_format){
						//Return the time formatted
 						$return = $return->format($output_format);
					}else{
						//Return Unix time
						$return = strtotime($return->format('Y-m-d H:i:s'));
					}
				}
			}
		}
		return $return;
	}
}//end class