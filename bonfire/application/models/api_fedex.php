<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class api_fedex extends BF_Model { 
	 
	var $WebAuthentication = array();
	public $ClientDetail = array();
	public $TransactionDetail = array();
	public $Version = array();
	public $Shipper = array();
	public $Recipient = array();
	public $ShippingChargesPayment = array();
	public $HubId = 0;
	public $RequestedPackageLineItems = array();
	public $LabelSpecification = array();
	public $AccountNumber = '';
	public $Dimensions = array(); 

	public function __construct() {
		// test
		// $this->AccountNumber=510087801;
		// $this->setWebAuthenticationDetail( 'aH11g0xB1cvqOWGr', 'pCFbh3NW9AlLyo9MEOvtNfZG3');// Key  , Password 
		// $this->setClientDetail($this->AccountNumber , 118644754); // AccountNumber , MeterNumber
		
		$this->AccountNumber=346217359;
		$this->setWebAuthenticationDetail( 'qIn5TLZ6smXg9Kqu', 'cPhsoUr17GmwDkka3PDy6DqDR');// Key  , Password 
		$this->setClientDetail($this->AccountNumber , 107091190); // AccountNumber , MeterNumber
		
	}
	public function SmartPostRateService($info_order, $Weight) {
		

		$this->setTransactionDetail();
		$this->setVersion();
		$this->setShipperContact('Eternity Essentials' , 'Eternity Essentials' , $PhoneNumber = '3474095502'); // PersonName , CompanyName , PhoneNumber
		$this->setShipperAddress(array( 0 => '521 Hidden Lake Dr'), 'Prosper' , $StateOrProvinceCode = 'TX' , $PostalCode = '75078' , $CountryCode = 'US'); // StreetLines array(), City  , StateOrProvinceCode , PostalCode , CountryCode
		$this->setRecipientContact($info_order->orders_name , $info_order->orders_name , $info_order->orders_phone );
		// $this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , $info_order->orders_state_or_region  ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , substr($info_order->orders_state_or_region , 0 ,2) ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setShippingChargesPayment('SENDER' , $this->AccountNumber , 'US');
		$this->setHubId();
		$this->setRequestedPackageLineItems($Weight, true);

		$ServiceType = 'SMART_POST';
		 
		$path_to_wsdl = "uploads/wsdl/RateService_v16.wsdl";

		$client = new SoapClient($path_to_wsdl, array('trace' => 1)); 

		$request = array(
						'WebAuthenticationDetail' => $this->WebAuthentication,
						'ClientDetail' => $this->ClientDetail,
						'TransactionDetail' => $this->TransactionDetail,
						'Version' => $this->Version,
						'ReturnTransitAndCommit' => true,
						'RequestedShipment'=>array(
											 	'DropoffType' => 'REGULAR_PICKUP',
            									'ShipTimestamp' => date('c'),
									            'ServiceType' => $ServiceType,
									            'PackagingType' => 'YOUR_PACKAGING', 
									            'Shipper' => $this->Shipper,
									            'Recipient' => $this->Recipient,
									            'ShippingChargesPayment' => $this->ShippingChargesPayment,
									            'SmartPostDetail' => array(
													                    'Indicia' => 'PARCEL_SELECT',
													                    'AncillaryEndorsement' => 'CARRIER_LEAVE_IF_NO_RESPONSE',
													                    'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
												                    	'HubId' => $this->HubId, 
												                    	'CustomerManifestId' => 'XXX'
													                ),
									            'PackageCount' => 1,
									            'RequestedPackageLineItems' => $this->RequestedPackageLineItems,
							)
					);
		
		$response = $client -> getRates($request);
		
		if ($response -> Notifications -> Severity == 'SUCCESS') {
			return $response;
			echo "<pre>";
			print_r($request);
			echo "</pre><HR><HR>";
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			die('<hr><hr>');		
		} else {
			return false;
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			die('<hr>pailas SMART_POST<hr>');		
		}

	}

	public function GroundRateService($info_order, $Weight) {
		$this->setTransactionDetail();
		$this->setVersion();
		$this->setShipperContact('Eternity Essentials' , 'Eternity Essentials' , $PhoneNumber = '3474095502'); // PersonName , CompanyName , PhoneNumber
		$this->setShipperAddress(array( 0 => '521 Hidden Lake Dr'), 'Prosper' , $StateOrProvinceCode = 'TX' , $PostalCode = '75078' , $CountryCode = 'US'); // StreetLines array(), City  , StateOrProvinceCode , PostalCode , CountryCode
		$this->setRecipientContact($info_order->orders_name , $info_order->orders_name , $info_order->orders_phone );
		// $this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , $info_order->orders_state_or_region  ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , substr($info_order->orders_state_or_region , 0 ,2) ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setShippingChargesPayment('SENDER' , $this->AccountNumber , 'US');
		$this->setHubId();
		$this->setRequestedPackageLineItems($Weight);

		$ServiceType = 'FEDEX_GROUND';
		 
		$path_to_wsdl = "uploads/wsdl/RateService_v16.wsdl";

		$client = new SoapClient($path_to_wsdl, array('trace' => 1));  

		$request = array(
						'WebAuthenticationDetail' => $this->WebAuthentication,
						'ClientDetail' => $this->ClientDetail,
						'TransactionDetail' => $this->TransactionDetail,
						'Version' => $this->Version,
						'ReturnTransitAndCommit' => true,
						'RequestedShipment'=>array(
											 	'DropoffType' => 'REGULAR_PICKUP',
            									'ShipTimestamp' => date('c'),
									            'ServiceType' => $ServiceType,
									            'PackagingType' => 'YOUR_PACKAGING', 
									            'Shipper' => $this->Shipper,
									            'Recipient' => $this->Recipient,
									            'ShippingChargesPayment' => $this->ShippingChargesPayment,
									            'SmartPostDetail' => array(
													                    'Indicia' => 'PARCEL_SELECT',
													                    'AncillaryEndorsement' => 'CARRIER_LEAVE_IF_NO_RESPONSE',
													                    'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
												                    	'HubId' => $this->HubId, 
												                    	'CustomerManifestId' => 'XXX'
													                ),
									            'PackageCount' => 1,
									            'RequestedPackageLineItems' => $this->RequestedPackageLineItems,
							)
					);
		
		$response = $client -> getRates($request);

		if ($response -> Notifications -> Severity == 'SUCCESS') {
			return $response;
			echo "<pre>";
			print_r($request);
			echo "</pre><HR><HR>";
			echo "<pre>";
			print_r($response);
			echo "</pre>";
			die('<hr><hr>');		
		} else {
			// echo "<pre>";
			// print_r($response);
			// echo "</pre>";
			// die('<hr>pailas FEDEX_GROUND<hr>');		
			return false;
		}
		

	}
	public function setWebAuthenticationDetail($Key = 'xxx' , $Password = 'xxx') {
		$this->WebAuthentication['UserCredential']['Key'] = $Key;
		$this->WebAuthentication['UserCredential']['Password'] = $Password;
	}

	public function setClientDetail($AccountNumber = 'xxx' , $MeterNumber = 'xxx') {
		$this->ClientDetail['AccountNumber'] = $AccountNumber;
		$this->ClientDetail['MeterNumber'] = $MeterNumber;
	}

	public function setTransactionDetail($CustomerTransactionId = '*** Rate Request using PHP ***' ) {
		$this->TransactionDetail['CustomerTransactionId'] = $CustomerTransactionId;
	}

	public function setVersion($ServiceId = 'crs' ,$Major = '16' ,$Intermediate = '0' ,$Minor = '0' ) {
		$this->Version['ServiceId'] = $ServiceId;
		$this->Version['Major'] = $Major;
		$this->Version['Intermediate'] = $Intermediate;
		$this->Version['Minor'] = $Minor;
	}
		
	public function setShipperContact($PersonName = 'Sender Name' , $CompanyName = 'Sender Company Name' , $PhoneNumber = '9012638716' , $ContactId = false , $Title = false) {
		$this->Shipper['Contact'] = array(
										'PersonName' => $PersonName,
			                            'CompanyName' => $CompanyName,
			                            'PhoneNumber' => $PhoneNumber,
										);
		if ($ContactId) 
			$this->Shipper['Contact']['ContactId'] = 'freight1';
		 	
		if ($Title) 
			$this->Shipper['Contact']['Title'] = 'Manager';
	}

	public function setShipperAddress( $StreetLines =array( 0 => '10 Fed Ex Pkwy'), $City = 'Memphis' , $StateOrProvinceCode = 'TN' , $PostalCode = '38115' , $CountryCode = 'US' ) {
		
		$this->Shipper['Address'] = array(
										'StreetLines' => $StreetLines,
			                            'City' => $City,
			                            'StateOrProvinceCode' => $StateOrProvinceCode,
			                            'PostalCode' => $PostalCode,
			                            'CountryCode' => $CountryCode
		                            );
		
	}
	public function setRecipientContact($PersonName = 'Recipient Name' , $CompanyName = 'Company Name' , $PhoneNumber = '9012637906') {
		$this->Recipient['Contact'] = array(
										'PersonName' => $PersonName,
			                            'CompanyName' => $CompanyName,
			                            'PhoneNumber' => $PhoneNumber,
										);
		
	}
	public function setRecipientAddress( $StreetLines =array( 0 => '13450 Farmcrest Ct'), $City = 'Herndon' , $StateOrProvinceCode = 'VA' , $PostalCode = '20171' , $CountryCode = 'US' ) {

		$this->Recipient['Address'] = array(
										'StreetLines' => $StreetLines,
		                            	'City' => $City,
			                            'StateOrProvinceCode' => $StateOrProvinceCode,
			                            'PostalCode' => $PostalCode,
			                            'CountryCode' => $CountryCode
		                            );
	}

	public function setShippingChargesPayment($PaymentType = 'SENDER' , $AccountNumber = 'XXX' , $CountryCode = 'US' , $Contact = false , $Address = false ) {
		$this->ShippingChargesPayment = array(
                    						'PaymentType' => $PaymentType,
                    						'Payor' => array(
                            								'ResponsibleParty' => array(
	                                    									'AccountNumber' => $AccountNumber,
	                                    									'CountryCode' =>$CountryCode
																		)
                        									)
                    						);
		if ($Contact) {
			$this->ShippingChargesPayment['Payor']['ResponsibleParty']['Contact'] = $Contact;
		}
		if ($Address) {
			$this->ShippingChargesPayment['Payor']['ResponsibleParty']['Address'] = $Address;
		}
	}
	public function setHubId($HubId = 5531) {
		$this->HubId=$HubId;
	}
	public function setRequestedPackageLineItems($Weight, $min_Weight = false) {
		$Weight = $Weight * 0.0625;
		if ($min_Weight && $Weight <= 1) {
			$Weight = 1;
		}
		$this->RequestedPackageLineItems = array(
							                    'SequenceNumber' => 1,
							                    'GroupPackageCount' => 1,
							                    'Weight' => array(
									                            'Value' => $Weight,
									                            'Units' => 'LB',
							                        		),
							                    'Dimensions' => $this->Dimensions

							                );
	}
	public function setLabelSpecification($LabelFormatType = 'COMMON2D' , $ImageType = 'PDF' , $LabelStockType = 'PAPER_4X6'  ){
		$this->LabelSpecification =	array(
                    				'LabelFormatType' => $LabelFormatType, // valid values COMMON2D, LABEL_DATA_ONLY
									'ImageType' =>  $ImageType,  // valid values DPL, EPL2, PDF, ZPLII and PNG
									'LabelStockType' =>  $LabelStockType
                					);
	}
	
	public function SetDimensions($Length , $Width , $Height , $Units = 'IN' ){
		$this -> Dimensions =array(
					'Length' => $Length,
					'Width' => $Width,
					'Height' => $Height,
					'Units' => $Units
				);
	}

	public function send_Ground_US($info_order , $Weight = 10, $Dimensions ) {		
		// echo "<pre>"; 
		// print_r($info_order); 
		// echo "</pre>"; 
		$this->setTransactionDetail();
		$this->setVersion('ship' , 15 ,0,0); // ServiceId , Major , Intermediate , Minor  
		// $this->setShipperContact(); 
		// $this->setShipperAddress(); 
		$this->setShipperContact('Eternity Essentials' , 'Eternity Essentials' , $PhoneNumber = '3474095502'); // PersonName , CompanyName , PhoneNumber
		$this->setShipperAddress(array( 0 => '521 Hidden Lake Dr'), 'Prosper' , $StateOrProvinceCode = 'TX' , $PostalCode = '75078' , $CountryCode = 'US'); // StreetLines array(), City  , StateOrProvinceCode , PostalCode , CountryCode
		
		// $this->setRecipientContact();
		// $this->setRecipientAddress();
		$this->setRecipientContact($info_order->orders_name , $info_order->orders_name , $info_order->orders_phone );
		// $this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , $info_order->orders_state_or_region  ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , substr($info_order->orders_state_or_region , 0 ,2) ,$info_order->orders_postal_code , $CountryCode = 'US');
		
		$this->setShippingChargesPayment('SENDER' , $this->AccountNumber , 'US', '' , array('CountryCode' => 'US'));
		$this->setLabelSpecification('COMMON2D' , 'PNG' );

		$this->setHubId();
		$this->SetDimensions($Dimensions['Length'] , $Dimensions['Width'] , $Dimensions['Height']);
		$this->setRequestedPackageLineItems($Weight);

		$ServiceType = 'FEDEX_GROUND';
		 
		$path_to_wsdl = "uploads/wsdl/ShipService_v15.wsdl";

		$client = new SoapClient($path_to_wsdl, array('trace' => 1));  

		$request = array(
						'WebAuthenticationDetail' => $this->WebAuthentication,
						'ClientDetail' => $this->ClientDetail,
						'TransactionDetail' => $this->TransactionDetail,
						'Version' => $this->Version, 
						'RequestedShipment'=>array(
            									'ShipTimestamp' => date('c'),
											 	'DropoffType' => 'REGULAR_PICKUP',
									            'ServiceType' => $ServiceType,
									            'PackagingType' => 'YOUR_PACKAGING', 
									            'Shipper' => $this->Shipper,
									            'Recipient' => $this->Recipient,
									            'ShippingChargesPayment' => $this->ShippingChargesPayment,
									            'LabelSpecification' => $this->LabelSpecification,
									            'PackageCount' => 1,
									            'PackageDetail' => 'INDIVIDUAL_PACKAGES',
									            'RequestedPackageLineItems' => $this->RequestedPackageLineItems,
							)
					);
		
        return $response = $client->processShipment($request); // FedEx web service invocation

		echo "<pre>"; 
		print_r($request); 
		print_r($response); 
		echo "</pre>"; 
		echo "<hr><hr><hr><hr><hr><hr>";
		die();
		echo "<pre>"; 
		print_r($response); 
		echo "</pre>"; 
		
                   
		die();
	}
	public function send_SmartPost($info_order , $Weight = 10 , $Dimensions) {		
		// echo "<pre>"; 
		// print_r($info_order); 
		// echo "</pre>"; 
		$this->setTransactionDetail();
		$this->setVersion('ship' , 15 ,0,0); // ServiceId , Major , Intermediate , Minor  
		// $this->setShipperContact(); 
		// $this->setShipperAddress(); 
		$this->setShipperContact('Eternity Essentials' , 'Eternity Essentials' , $PhoneNumber = '3474095502'); // PersonName , CompanyName , PhoneNumber
		$this->setShipperAddress(array( 0 => '521 Hidden Lake Dr'), 'Prosper' , $StateOrProvinceCode = 'TX' , $PostalCode = '75078' , $CountryCode = 'US'); // StreetLines array(), City  , StateOrProvinceCode , PostalCode , CountryCode
		
		// $this->setRecipientContact();
		// $this->setRecipientAddress();
		$this->setRecipientContact($info_order->orders_name , $info_order->orders_name , $info_order->orders_phone );
		// $this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , $info_order->orders_state_or_region  ,$info_order->orders_postal_code , $CountryCode = 'US');
		$this->setRecipientAddress(array( 0 => $info_order->orders_address_line1 , 1 => $info_order->orders_address_line2 ), $info_order->orders_city , substr($info_order->orders_state_or_region , 0 ,2) ,$info_order->orders_postal_code , $CountryCode = 'US');
		
		$this->setShippingChargesPayment('SENDER' , $this->AccountNumber , 'US', '' , array('CountryCode' => 'US'));
		$this->setLabelSpecification('COMMON2D' , 'PNG' );

		$this->setHubId();
		$this->SetDimensions($Dimensions['Length'] , $Dimensions['Width'] , $Dimensions['Height']);
		$this->setRequestedPackageLineItems($Weight, true);

		$ServiceType = 'SMART_POST';
		 
		$path_to_wsdl = "uploads/wsdl/ShipService_v15.wsdl";

		$client = new SoapClient($path_to_wsdl, array('trace' => 1));  

		$request = array(
						'WebAuthenticationDetail' => $this->WebAuthentication,
						'ClientDetail' => $this->ClientDetail,
						'TransactionDetail' => $this->TransactionDetail,
						'Version' => $this->Version, 
						'RequestedShipment'=>array(
            									'ShipTimestamp' => date('c'),
											 	'DropoffType' => 'REGULAR_PICKUP',
									            'ServiceType' => $ServiceType,
									            'PackagingType' => 'YOUR_PACKAGING', 
									            'Shipper' => $this->Shipper,
									            'Recipient' => $this->Recipient,
									            'ShippingChargesPayment' => $this->ShippingChargesPayment,
									             'SmartPostDetail' => array(
													                    'Indicia' => 'PARCEL_SELECT',
													                    'AncillaryEndorsement' => 'CARRIER_LEAVE_IF_NO_RESPONSE',
													                    'SpecialServices' => 'USPS_DELIVERY_CONFIRMATION',
												                    	'HubId' => $this->HubId, 
												                    	'CustomerManifestId' => 'XXX'
													                ),
									            'LabelSpecification' => $this->LabelSpecification,
									            'PackageCount' => 1,
									            'PackageDetail' => 'INDIVIDUAL_PACKAGES',
									            'RequestedPackageLineItems' => $this->RequestedPackageLineItems,
							)
					);
		
        return $response = $client->processShipment($request); // FedEx web service invocation

		// echo "<pre>"; 
		// print_r($request); 
		// echo "</pre>"; 
		// echo "<hr><hr><hr><hr><hr><hr>";
		// echo "<pre>"; 
		// print_r($response); 
		// echo "</pre>"; 
		
                   
		die();
	}
	
}