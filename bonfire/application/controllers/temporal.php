<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");

class temporal extends Front_Controller
{
	public function __construct()
	{
		parent::__construct();

		die('Review');

		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
	}

	public function update_Date()
	{
		$orders_confirmation = $this -> db -> select()
										   -> where('orders_tracking_Ship_date !=', '')
										   -> get('bf_orders_tracking')
										   -> result();
		
		foreach ($orders_confirmation as $key => $value) 
		{						
			$date = $value->orders_tracking_Ship_date;
			$date_str = date("Y-m-d\TH:i:s",strtotime($date));
			
			echo 'ID '.$value->id_orders_tracking.'<br>';
			echo "Original Date ".$date."<br>";
			echo "Convert Date ".$date_str.'<hr>';
			
			$this->db->where('id_orders_tracking', $value->id_orders_tracking);
			$this->db->update('bf_orders_tracking', array('orders_tracking_Ship_date' => $date_str)); 
		} 
					
		echo '<pre><xmp>';
		print_r($orders_confirmation);
		echo '</xmp></pre>';
		die('');
		
	}
	
	
	
}//end class