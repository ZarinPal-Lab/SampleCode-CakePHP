<?php

/**
 * ZarinpalComponent
 * 
 * @package lab.ritaco.net
 * @author Mohammad Saleh Souzanchi
 * @copyright lab.ritaco.net 2013
 * @version 0.0.1
 * @access public
 */
class ZarinpalComponent extends Component {
	
	protected $configs = array(
		'servers' 		=> false,
		'masterServer'	=> false,
		'getewayUrl'	=> false,
		'zarinGate'		=> true,
		'merchantID'	=> false,
		'verifyCallbak' => false,
		'use'			=> 'auto'   //[auto|soap|nusoap]
	);
	
	
	protected $client = false;
	
	/**
	 * ZarinpalComponent::__construct()
	 * 
	 * @param mixed $collection
	 * @param mixed $settings
	 * @return void
	 */
	public function __construct(ComponentCollection $collection, $settings = array()){
		$configs =  $this->_loadConfigFile();
		$this->configs = Hash::merge($this->configs,$configs,$settings );
	}

	
	
	public function payment($eventName,$amount,$extra= array()){
		
		l(array($eventName,$amount,$extra));	
		
	}



	/**
	 * ZarinpalComponent::_setupClient()
	 * setup client soap object
	 * @return void
	 */
	private function client(){
		$server = $this->_getServer();
		if($this->configs['user'] = 'auto'){
			$this->configs['use'] = extension_loaded('soap')? 'soap' : 'nusoap';
		}
		
		$client = null;
	
		if($this->configs['use'] == 'soap'){
			$client = new SoapClient($server, array('encoding' => 'UTF-8'));
			
		}else{
			$client = new SoapClient($server,'wsdl');
			$client->soap_defencoding = 'UTF-8';
		}
		return $client;
	}

/**
 * ZarinpalComponent::_setupServer()
 * get online server
 * @return
 */
	private function _getServer(){
		extract($this->configs);
		$orderServer = array();
		if(is_string($servers))	{
			$servers = array($servers);
		}
		
		if ( $masterServer !== false && is_string($masterServer)){
			$orderServer[] = $servers[$masterServer];
			unset($servers[$masterServer]);
		}
		$orderServer = array_merge($orderServer,$servers); 
		
		foreach($orderServer as $url ) {
			if($this->_checkServerIsOnline($url)){
				return $url;
			}
		}
	
				
		return false ;
	}
	
	
	
	/**
	 * ZarinpalComponent::_checkServerIsOnline()
	 * check server is online
	 * @param mixed $url
	 * @return
	 */
	private function _checkServerIsOnline($url){
		ini_set("default_socket_timeout","05");
       set_time_limit(5);
       $f=fopen($url,"r");
       $r=fread($f,1000);
       fclose($f);
       return (strlen($r)>1) ? true : false;
	}		
	
/**
 * ZarinpalComponent::_loadConfigFile()
 * 
 * @return
 */
	private function _loadConfigFile() {
		$file = CakePlugin::path('RitaZarinpalClient') . 'Config' . DS . 'zarinpal.php';
		if (!file_exists($file)) {
			return false;	
		}
		return  include_once $file;
	}
	
}


if (!extension_loaded('soap')) {
	require CakePlugin::path('RitaZarinpalClient') . 'Vendor' . DS . 'NuSoap' . DS . 'nusoap.php';	
	class SoapClient extends nusoap_client {
	}
}