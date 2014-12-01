<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class read_scale extends Front_Controller
{
	var $amazon_cofiguration=array();
	public function __construct()
	{
		parent::__construct();

		$this->load->library('email');		
	}

	
	public function post_read(){

		if (isset($_POST['weight'])) {
			$value = $this->input->post('weight');
			$value = preg_replace("/[^0-9,.]/", "", $value);
			$array_insert = array(
									'name' => 'test_one',
									'weight' => $value
							);
			
			$this->db->where('id', 1);
			$this->db->update('bf_read_scale' , $array_insert );
			
			// echo "<pre>";
			// print_r($array_insert);
			// echo "</pre>";
			$string = '';
			foreach ($_POST as $key => $value) {
				$string .= $key.'<r>'.$value.'<br>';
			}

			$this->email->from('yoursitetester@gmail.com','Run ');
			$this->email->to('carlos.cardenas@atlanticsoft.us');  
			$this->email->cc('edwin.hortua@atlanticsoft.us');
			$this->email->subject('Run script');
			$this->email->message($string);
			$this->email->send(); 
			echo "true";
		} else {
			echo "false";
		}
		
		// echo "<pre>";
		// print_r($_POST);
		// print_r($_GET);
		// echo "</pre>";
		die();
	}
	
	
	
	
	
	
}//end class