<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_endicia extends BF_Model { 
	 
	private $WebAuthentication = array();

	public $Dimensions = array();
	public $site_work = 'dev';
	public $PassPhrase = 'E1T2E3r4';
	public $AccountID = '959387';

	public function __construct() {
		// $this -> __datadefault();
		// data c
	}
	public function change_site($site) {
		$this->site_work = $site;
	}
	public function default_send($info_order ,  $ounces , $service_type, $data_store , $MailpieceShape = 'Parcel' , $mime = 'GIF' ) {
		if ($this->site_work == 'dev') {
			$client = new SoapClient("https://www.envmgr.com/LabelService/EwsLabelService.asmx?wsdl");
		} else {
			$client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");
		}
		// $client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");

		$service  = explode('-', $info_order -> orders_postal_code);
		$value = $info_order -> orders_items_prices;
		$to_fname = $info_order -> orders_name;
		$to_addr1 = $info_order -> orders_address_line1;
		$to_addr2 = $info_order -> orders_address_line2;
		$to_city = $info_order -> orders_city;
		$to_state = $info_order -> orders_state_or_region;
		$to_zip5 = $service[0];
		$to_country = $info_order -> orders_country_code;
		$to_phone = $info_order -> orders_phone;
		if($info_order -> orders_configuration_id == 1) {
			$from_fname = 'Eternity Essentials';
		} else {
			$from_fname = 'Nawty Virgin';
		} 
		$from_addr1 = '521 Hidden Lake Dr';
		$from_city = 'Prosper';
		$from_state = 'TX';
		$from_zip5 = '75078';
		$from_zip4 = '';
		$from_phone = '3474095502'; 
		$data = array(  
				'LabelRequest' => array(
									// 'Test' => 'YES',
									'LabelType' => 'Default',
									'LabelSubtype' => 'None',
									'LabelSize' => '4x6',
									'ImageFormat' => $mime,
									// 'ImageResolution' => '203',
									'RequesterID' => 'LETM',
									'AccountID' => $this->AccountID,
									'PassPhrase' => $this->PassPhrase,
									'MailClass' => $service_type,
									'MailpieceShape' => $MailpieceShape,
									'DateAdvance' => 0,
									'WeightOz' => $ounces ,
									'CostCenter' => 0,
									'Value' => $value,
									'Services' => array(
													'CertifiedMail' => 'OFF',
													'DeliveryConfirmation' => 'OFF',
													'ElectronicReturnReceipt' => 'OFF',
													// 'InsuredMail' => 'ENDICIA',
													'SignatureConfirmation' => 'OFF'
												),
									'Description' => '',
									'PartnerCustomerID' => 'LETM',
									'PartnerTransactionID' => 'LETM',
									'OriginCountry' => 'United States',
									'ToName' => $to_fname,
									'ToAddress1' => $to_addr1,
									'ToAddress2' => $to_addr2,
									'ToCity' => $to_city,
									'ToState' => $to_state,
									'ToPostalCode' => $to_zip5,
									'ToCountry' => $to_country,
									'ToPhone' => $to_phone,
									'FromName' => $from_fname,
									'ReturnAddress1' => $from_addr1,
									'FromCity' => $from_city,
									'FromState' => $from_state,
									'FromPostalCode' => $from_zip5,
									'FromPhone' => $from_phone
								)
				);
			$result = $client->GetPostageLabel($data);
			return $result;
			echo "<pre>";
			print_r($result);
			echo "</pre>"; 
			$response_service = $result -> LabelRequestResponse;
            $data_imagen = base64_decode($response_service -> Base64LabelImage);
            $im = imagecreatefromstring($data_imagen);
            imagegif($im, 'uploads/shippinglabel/testetstes.gif');
		die();
	}

	public function RecreditRequest($credit = 10) {
		if ($this->site_work == 'dev') {
				$client = new SoapClient("https://www.envmgr.com/LabelService/EwsLabelService.asmx?wsdl");
		} else {
			$client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");
		}

		$data = array(  
				'RecreditRequest' => array(
									'RequesterID' => 'LETM',
									'RequestID' => 'LETM',
									'CertifiedIntermediary' => array(
										'AccountID' => $this->AccountID,
										'PassPhrase' => $this->PassPhrase
										),
									'RecreditAmount' => $credit
								) 
				);
		$result = $client->BuyPostage($data);
		// echo "<pre>";
		// print_r($result);
		// echo "</pre>";
		return $result;
	}

	public function ChangePassPhrased() {
		if ($this->site_work == 'dev') {
			$client = new SoapClient("https://www.envmgr.com/LabelService/EwsLabelService.asmx?wsdl");
		} else {
			$client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");
		}
		$data = array(  
				'ChangePassPhraseRequest' => array(
									'RequesterID' => 'LETM',
									'RequestID' => 'LETM',
									'CertifiedIntermediary' => array(
										'AccountID' => $this->AccountID,
										'PassPhrase' => $this->PassPhrase
										),
									'NewPassPhrase' => 'E1T2E3r4'
								)
				);
		echo "<pre>"; 
		print_r($data);
		echo "</pre>"; 
		// $result = $client->ChangePassPhrase($data);
		// echo "<pre>"; 
		// print_r($result);
		// echo "</pre>"; 
		die();
	}

	public function CalculatePostageRatestt( $ounces = false , $from_zip5 = false, $to_zip5 = false ,$service_type = 'Parcel' ) {
		if (!$ounces || !$from_zip5 || !$to_zip5 ) {
			return false; 			
		}
		if ($this->site_work == 'dev') {
			$client = new SoapClient("https://www.envmgr.com/LabelService/EwsLabelService.asmx?wsdl");
		} else {
			$client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");
		}
		// $client = new SoapClient("https://labelserver.endicia.com/LabelService/EwsLabelService.asmx?WSDL");
		$MailClass = 'Domestic'; 
		$data = array(  
				'PostageRatesRequest' => array(
									'RequesterID' => 'LETM',
									'CertifiedIntermediary' => array(
										'AccountID' => $this->AccountID,
										'PassPhrase' => $this->PassPhrase
										),
									'MailClass' => $MailClass,
									'WeightOz' => $ounces ,
									'MailpieceShape' => $service_type ,
									'CODAmount' => '0.00',
									'InsuredValue' => '0.00',
									'RegisteredMailValue' => '0.00',
									'Services' => array(
													'@attributes' => array(
																'CertifiedMail' => 'OFF',
																'DeliveryConfirmation' => 'OFF',
																'ElectronicReturnReceipt' => 'OFF',
																// 'InsuredMail' => 'ENDICIA',
																'SignatureConfirmation' => 'OFF'
															),
													),
									'ToPostalCode' => $to_zip5,
									'FromPostalCode' => $from_zip5,
								)
				);
 		// echo "<pre>";
		// print_r($data);
		// echo "</pre>"; 
		$result =  $client->CalculatePostageRates($data);
		return $result;
		echo "<pre>"; 
		print_r($result);
		echo "</pre>"; 
		echo "<hr><hr><hr><hr><hr><hr>";
		// die();
	}
}