<?php

// Configure twilio to postback to this files url

class receivesms {

	function __construct(){
	
		if(FALSE !== $fp = fopen('requestLog.csv','a') ){
			fputcsv($fp,array(
				date('Y-m-d h:i:s'),
				$_SERVER['REMOTE_ADDR'],
				"POST:" . print_r($_POST,true),
				"GET:" . print_r($_GET,true),
				"SERVER:" . print_r($_SERVER,true)
			));
			fclose($fp);
		}
	
		if(isset($_GET['test'])){
			return $this->outputTestForm();
		}
		
		if(!isset($_POST['SmsMessageSid']) || !isset($_POST['Body']) ){
			echo "ohai ...  @ " . date('Y-m-d h:i:s');
			return false;
		}
		
		$target = 'light';
		
		if(false === stripos($_POST['Body'], $target) ){
			return $this->sendResponse('I need a target. Try: "light"');
		}
		
		$possibleActions = array('on','off','status','flip');
		$actionFound = '';
		foreach($possibleActions as $action){
			if(false !== stripos($_POST['Body'], $action) ){
				$actionFound = $action;
			}
		}
		
		switch($actionFound){
			case 'on':
				return $this->sendResponse("Current Status: ".$this->doCurlRequest('1') );
				break;
			case 'off':
				return $this->sendResponse("Current Status: ".$this->doCurlRequest('0') );
				break;
			case 'status':
				return $this->sendResponse("Current Status: ". $this->doCurlRequest('status') );
				break;
			case 'flip':
				return $this->sendResponse("Current Status: ". $this->doCurlRequest('flip') );
				break;
			case '':
				return $this->sendResponse('Ok, I need an action. Try: "light on". Also: on, off, flip, status');
				break;
		}

		return $this->sendResponse("Dunno what to do");

	}

	function interperetStatus($status){
		if($status == 1){
			$ledState = 'ON';
		}else{
			$ledState = 'OFF';
		}
		
		return $ledState;
	}

	function doCurlRequest($relayValue){
	
		$esp8266 = 'http://10.88.1.62/relayapi.json';
		
		$postData = array('relay'=>$relayValue);

		$curl = curl_init($esp8266);
		
		curl_setopt_array($curl,array(
			CURLOPT_RETURNTRANSFER => true
			,CURLOPT_POST => true
			,CURLOPT_POSTFIELDS => http_build_query($postData)
		));
		
		if(FALSE === $returnPage = curl_exec($curl) ){
			return $this->sendResponse("Connection problem to esp8266");
		}
		
		if(NULL === $returnPageJson = json_decode($returnPage,true)){
			return $this->sendResponse("Invalid JSON returned from esp8266");
		}

		return $this->interperetStatus($returnPageJson['relayState']);
	}
	function sendResponse($responseText){

		header('Content-Type:text/xml');

		echo <<<XMLRESPONSE
<?xml version="1.0" encoding="UTF-8"?><Response><Message>{$responseText}</Message></Response>
XMLRESPONSE;

		exit;
	}

}

new receivesms();

