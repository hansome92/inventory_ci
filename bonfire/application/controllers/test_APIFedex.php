<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


ini_set("soap.wsdl_cache_enabled", "0");

class test_APIFedex extends Front_Controller
{

	public function __construct() {
		parent::__construct();
		$this -> load -> model('api_fedex');
	}
				
	public function index3() {

		$this -> api_fedex -> smart_post();
		
		die('<hr>here');
	}
	public function indexff() {
		$this -> api_fedex -> Ground_US();
		die('<hr>here');
	}

		
	
	
	
	
	
}//end class