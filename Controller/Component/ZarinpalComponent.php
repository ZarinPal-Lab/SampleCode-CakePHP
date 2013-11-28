<?php
App::uses('Transaction','RitaZarinpalClient.Model');
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


/**
 * Settings for this Component
 *
 * @var array
 */	
	protected $configs = array(
		'servers' 		=> false,
		'masterServer'	=> false,
		'getewayUrl'	=> false,
		'zarinGate'		=> false,
		'merchantID'	=> false,
		'use'			=> 'auto'   //[auto|soap|nusoap]
	);
	
/**
 * Error Codes
 *
 * @var array
 */	
	public $errorCode = array(
		'-1'	=> 'اطلاعات ارسال شده ناقص می‌باشد.',
		'-2'	=> 'IP یا شناسه مرچنت صحیح نمی باشد.',
		'-3'	=> 'مقدار پرداختی می‌بایست بیشتر از 100 تومان باشد.',
		'-4'	=> 'سطح تائید پذیرنده پائین‌تر از سطح نقره‌ای قرار دارد.',
		'-11'	=> 'درخواست مورد نظر نامعتبر است.',
		'-21'	=> 'هیچ نوع عملیات مالی برای این تراکنش موجود نمی‌باشد.',
		'-22'	=> 'تراکنش ناموفق می باشد.',
		'-23'	=> 'رقم تراکنش با رقم پرداخت شده مطابقت ندارد.',
		'-54'	=> 'درخواست مورد نظر آرشیو شده است.',
		'100'	=> 'عملیات با موفقیت انجام شد است.',
		'101'	=> 'عملیات با موفقیت انجام شده است،ولی قبلا عملیات PaymentVerification بر روی این تراکنش انجام شده است.',
	);

/**
 * Settings for this Component
 *
 * @var array
 */
 	protected $controller = null;
 	
/**
 * $Transaction Model Object
 *
 * @var array
 */ 	
	public $TransactionModel;
	
	private $error = null;
	
/**
 * ZarinpalComponent::__construct()
 * 
 * @param mixed $collection
 * @param mixed $settings
 * @return void
 */
	public function __construct(ComponentCollection $collection, $settings = array()){
		parent::__construct($collection, $settings);
		$configs =  $this->_loadConfigFile();
		$this->configs = Hash::merge($this->configs, $configs, $settings );
	}

/**
 * ZarinpalComponent::initialize()
 * 
 * @param mixed $controller
 * @return void
 */
	public function initialize(Controller $controller) {
		$this->controller = $controller;
		//$this->TransactionModel = ClassRegistry::init('RitaZarinpalClient.Transaction');

	}
	
	
	
	public function getAuthorityPayment($amount,$description,$callback) {
	
		
		$callback = (is_array($callback))? Router::url($callback,true) : $callback;
		$clientParams = array(
			'MerchantID' => $this->configs['merchantID'],
			'Amount' => $amount,
			'Description' => $description,
			'CallbackURL' => $callback,
			'Email' => '',
			'Mobile' =>''
		);
		
		
		$server = $this->_getServer();
		
		if ($this->configs['use'] === 'auto') {
			$this->configs['use'] = extension_loaded('soap') ? 'soap' : 'nusoap';
		}

		
		if ($this->configs['use'] === 'soap') {
			$client = new SoapClient($server, array('encoding' => 'UTF-8'));
			$method = "__soapCall";
		} else {
			$client = new SoapClient($server, 'wsdl');
			$client->soap_defencoding = 'UTF-8';
			$method = 'call';
		}		
		
		
		$res = $client->{$method}('PaymentRequest',array($clientParams));	
		extract($res);
		if( $Status === '100' && strlen($Authority) === 36){
			return $Authority;
		}
		$this->error = $Status;
			
		return  false;	
	}
	
/**
 * ZarinpalComponent::payment()
 * 
 * @param mixed $amount
 * @param mixed $data
 * @param mixed $options
 * @return void
 */
	public function goToPayment($au){
	
		$gateway = $this->configs['getewayUrl'].$au;
		if($this->configs['zarinGate']) {
			$gateway .=  DS.'ZarinGate';
		}
		$this->log($gateway);
		$this->controller->response->header('Location', $gateway);
		$this->controller->response->send();
		$this->_stop();
		
	}


	public function verification($authority = null,$amount = null){
		if(!is_string($authority) and strlen($authority) !== 36){
			throw new ForbiddenException('کد تراکنش نا معتبر است');
		}

		

		
		$server = $this->_getServer();
		if ($this->configs['use'] === 'auto') {
			$this->configs['use'] = extension_loaded('soap') ? 'soap' : 'nusoap';
		}

		
		if ($this->configs['use'] === 'soap') {
			$client = new SoapClient($server, array('encoding' => 'UTF-8'));
			$method = "__soapCall";
		} else {
			$client = new SoapClient($server, 'wsdl');
			$client->soap_defencoding = 'UTF-8';
			$method = 'call';
		}		
		
		$clientParams = array(
			'MerchantID' => $this->configs['merchantID'],
			'Amount' => $amount,
			'Authority' => $authority
		);
		
		$res = $client->{$method}('PaymentVerification',array($clientParams));	
		CakeLog::error($res);
		extract($res);
		if( $Status === '100' ){
			return $RefID;
		}
		$this->error = $Status;
			
		return  false;				
	}

/**
 * ZarinpalComponent::options()
 * 
 * options parameter :
 * url:
 * 		ZarinpalComponent::payment(..,...,array('url' => 'http://'));
 * event:
 * 		ZarinpalComponent::payment(..,...,array('event' => 'Controller.Shop.afterPayment'));
 * empty: 
 * 		ZarinpalComponent::payment(..,...,array( url => $this->controller->referer)));
 * 		
 * @param mixed $options
 * @return void
 */
	private function options($options) {
		if (!is_array($options)) {
			throw new NotImplementedException('Option param be must array type');
		}
		
		$this->transaction['options'] = false;
		
		if (empty($options)) {
			$options = array('url' => $this->controller->referer());
			$this->transaction['options'] = serialize($options);
			return true;
		}
		
		
		if (isset($options['url'])) {
			$options['url']	= (is_string($options['url']))? $options['url'] : Router::url($options['url'],true);  
			$this->transaction['options'] = serialize($options);
			return true;
		}
		

		if (isset($options['event'])) {
			$this->transaction['options'] = serialize($options);
			return true;
		}
		
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
		return true;
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
	
/**
 * ZarinpalComponent::getErr()
 * 
 * @param mixed $code
 * @return
 */
	public function getErr() {
		return __d('rita_zarinpal_client', $this->errorCode[$this->error]);
	}

	
}


if (!extension_loaded('soap')) {
	require CakePlugin::path('RitaZarinpalClient') . 'Vendor' . DS . 'NuSoap' . DS . 'nusoap.php';	
	class SoapClient extends nusoap_client {
	}
}