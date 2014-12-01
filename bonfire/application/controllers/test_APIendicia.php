<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


ini_set("soap.wsdl_cache_enabled", "0");

class test_APIendicia extends Front_Controller
{

	public function __construct() {
		parent::__construct();
		$this -> load -> model('api_endicia');
	}
				
	public function index3() {

		$this -> api_endicia -> default_send();
		
		die();
		die('<hr><hr><hr><hr><hr><hr><hr>');

	}
	

	
	
	
}//end class