<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

date_default_timezone_set("America/Los_Angeles");
    
class cron_fee_gmail extends Front_Controller
{
	var $data_email = array();
	public function __construct()
	{
		
		parent::__construct();


		$this->load->model('orders/orders_model', null, true);
		$this->load->model('orders/Orders_products_model', null, true);
		$this->load->model('amazon_cofiguration/amazon_cofiguration_model', null, true);
		
		$this -> data_email['username'] = 'etermart1@gmail.com'; 
		$this -> data_email['password'] = 'swim1111'; 
					
	}
	public function index()
	{
		$inbox =  imap_open("{imap.gmail.com:993/imap/ssl}INBOX", $this -> data_email['username'], $this -> data_email['password']) or die("can't connect: " . imap_last_error());
		
		// $emails = imap_search($inbox,'FROM "Seller Notification"');
		$emails = imap_search($inbox,'FROM "Seller Notification" SUBJECT "Sold, ship now:"');
		
		if ($emails)
		{
			// $emails = array_reverse($emails);
			foreach ($emails as $value) 
			{
				$over = imap_fetch_overview($inbox,$value,0);
				
				$message = imap_fetchbody($inbox,$value,1);
						
				$data_insert_email =array();
				
				$cut_ini = stripos($message, 'Order ID:');
				$cut_fin = stripos($message, '- - - - - - - - - - - - - - - - - - ');
				
				$message = substr($message, $cut_ini, $cut_fin-$cut_ini);
				$result_message = explode("\n", $message);
				$cont = 0;
				
				foreach ($result_message as $key => $line_message) 
				{
					// echo "<xmp>".$line_message."</xmp>";
					if (stripos((string)$line_message, 'Order ID:') !== false) 
					{
						$review_email_order_amazon_id = str_replace("\r", "",  substr((string)$line_message, 10));
					}
					if (stripos((string)$line_message, 'Ship by:') !== false) 
					{
						$data_insert_email[$cont]['Ship_by'] = str_replace("\r", "", substr((string)$line_message, 9));
					}
					if (stripos((string)$line_message, 'Item:') !== false) 
					{
						$data_insert_email[$cont]['Item'] = str_replace("\r", "", substr((string)$line_message, 6));
					}
					if (stripos((string)$line_message, 'Listing ID:') !== false) 
					{
						$data_insert_email[$cont]['Listing_id'] = str_replace("\r", "", substr((string)$line_message, 11));
					}
					if (stripos((string)$line_message, 'SKU:') !== false) 
					{
						$data_insert_email[$cont]['SKU'] = str_replace("\r", "", substr((string)$line_message, 5));
					}
					if (stripos((string)$line_message, 'Quantity:') !== false) 
					{
						$data_insert_email[$cont]['Quantity'] = str_replace("\r", "", substr((string)$line_message, 10));
					}
					if (stripos((string)$line_message, 'Price:') !== false) 
					{
						$data_insert_email[$cont]['Price'] = str_replace("\r", "", substr((string)$line_message, 8));
					}
					if (stripos((string)$line_message, 'Shipping:') !== false) 
					{
						$data_insert_email[$cont]['Shipping'] = str_replace("\r", "", substr((string)$line_message, 11));
					}
					if (stripos((string)$line_message, 'Amazon fees:') !== false) 
					{
						$data_insert_email[$cont]['Amazon_fees'] = str_replace("\r", "", substr((string)$line_message, 15));
					}
					if (stripos((string)$line_message, 'Your earnings:') !== false) 
					{
						$data_insert_email[$cont]['Your_earnings'] = str_replace("\r", "", substr((string)$line_message, 16));
						$cont++;
					}
					
				}
								
				$orden = $this -> db -> get_where('bf_orders', array('orders_amazon_order_id' =>(string)$review_email_order_amazon_id, 'orders_order_status !='=> 'Pending'));
				// if(true)
				$orden = $orden -> result();
				
				if($orden)
				{
					$orden = $orden[0];
					$validation = $this -> db -> get_where('bf_review_email', array('review_email_order_amazon_id' => $review_email_order_amazon_id));
					if(!$validation -> result()) 
					{
							$products = $this -> db -> get_where('bf_orders_products', array('products_amazon_order_id' =>(int)$orden -> id ));
							$this -> update_items_orders($products -> result(), $orden , $data_insert_email);
							$this -> db -> insert('bf_review_email', array('review_email_order_amazon_id' => $review_email_order_amazon_id, 'review_email_review_date' => date('Y-m-d H:i:s'))); 
					}
					else
					{
						echo "the order was revised";
					}
					
				}
				else {
					echo "there is no order";
				}
				// echo '<pre>';
				// print_r($over);
				// echo '</pre>';
				
				// echo "<xmp>".($message)."</xmp>";
				
				imap_clearflag_full($inbox,$value,"\\Seen" );
				echo "<hr><hr><hr><hr>";
			}
			echo '<pre>';
			print_r($emails);
			echo '</pre>';
			die('(^_^)');
			echo '<pre>';
			print_r($imap);
			print_r($over);
			print_r($headers);
			echo '</pre>';
			die('(^_^)');
		}
	}
	
	
	public function update_items_orders($items, $id_orders, $value_order)
	{
		// echo '<pre>';
		// print_r($items);
		// print_r($id_orders);
		// print_r($value_order);
		// echo '</pre>';
		
		$shipping=0;
		$tax_cost = 0;
		$sku = '';
		$items_price = 0;
		foreach ($items as $key => $value)
  		{
			$data_update_product = array();
			foreach ($value_order as $key => $item_email) 
			{
				if ((string)$value -> products_seller_sku == $item_email['SKU']) 
				{
					echo '<pre>';
					print_r($id_orders);
					print_r($value);
					print_r($item_email);
					echo '</pre>';
					$data_update_product['products_fee_Commission'] = $item_email['Amazon_fees'];
					if ((float)$value -> products_price_cost != 0 || (float)$value -> products_price_cost != null) 
					{
						$data_update_product['product_Received_Amazon'] = ($value -> products_item_price + $value -> products_item_tax + $value -> products_shipping_price + $value -> products_shipping_tax) - ($item_email['Amazon_fees'] + $value -> products_fee_Shipping + $value -> products_fee_SalesTaxServiceFee) ;
				
						$data_update_product['products_profit'] = $data_update_product['product_Received_Amazon'] - ($value -> products_price_cost + $value -> products_Shipping_cost + $value -> products_item_tax + $value -> products_shipping_tax);
								
						@$data_update_product['products_ROI'] = ($data_update_product['products_profit'] / ($value -> products_price_cost + $value -> products_Shipping_cost ))*100;
				
						$data_update_product['products_total_cost'] = $value -> products_price_cost + $value -> products_Shipping_cost ;
					}
					
					$consulta_product = $this->db->where(array('id' => (int)$value -> id)) -> update('bf_orders_products',$data_update_product); 
					
					echo '<pre>';
					print_r($data_update_product);
					echo '</pre><hr>';

					break;
				} 				
			}
			
		}
		$this -> review_order((int)$id_orders->id);
	}
	
	public function review_order($id_order)
    {
        $result_orders_products = $this -> Orders_products_model ->where('products_amazon_order_id',$id_order) -> find_all();
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
		
		$roi = ($profit_product /( $item_cost + $shipping_cost)) * 100 ;
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