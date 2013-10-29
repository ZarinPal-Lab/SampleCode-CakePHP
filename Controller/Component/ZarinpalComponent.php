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
		'zarinGate'		=> true,
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
 * Error Codes
 *
 * @var boolean
 */		
	protected $client = false;
	
	
/**
 * Settings for this Component
 *
 * @var array
 */		
	public $data = array();
	
	protected $controller = null;
	protected $Transaction;
/**
 * ZarinpalComponent::__construct()
 * 
 * @param mixed $collection
 * @param mixed $settings
 * @return void
 */
	public function __construct(ComponentCollection $collection, $settings = array()){
		parent::__construct($collection,$settings);
		$configs =  $this->_loadConfigFile();
		$this->configs = Hash::merge($this->configs,$configs,$settings );
		$this->Transaction = ClassRegistry::init('RitaZarinpalClient.Transaction');
		
	}

	public function initialize(Controller $controller) {
		$this->controller = $controller;
	}
	
	public function getErr($code){
		return __d('rita_zarinpal_client',$this->errorCode[$code]);
	}
	
	/**
	 * ZarinpalComponent::payment()
	 * 
	 * @param mixed $amount
	 * @param mixed $data
	 * @param mixed $options
	 * @return void
	 */
	public function payment($amount,$data= array(),$options = array()){
		$_option = array(
			'type' => 'redirect'  // [redirect|event]
		);
		$transaction = array();
		
		$options = array_merge($_option,$options);
		
		
		if ($options['type'] === 'redirect') {
			
			if (!isset($options['url'])) {
				$options['url'] = $this->controller->referer();
			}elseif(is_array($options['url'])) {
				$options['url'] = Router::url($options['url']);
			}
		}
		
		if ($options['type'] === 'event' && !isset($options['name'])) {
			throw new  ForbiddenException('need to name of event');
		}
		
		
		
		$transactionId = $this->Transaction->createTransaction();

		$transaction['body']['option'] = $options;
		$transaction['body']['data'] = $data;
		$transaction['amount'] = $amount;
		$transaction['type'] = 'start';
		
		
		
		$data = array(
			'MerchantID' => $this->configs['merchantID'],
			'Amount' => $amount,
			'Description' => 'transaction #'.$transactionId,
			'CallbackURL' => Router::url(array( 'plugin' => 'RitaZarinpalClient', 'controller' => 'transactions','action'=>'verification'),true),
			'Email' => '',
			'Mobile' =>''
		);


		$server = $this->_getServer();
		if($this->configs['user'] = 'auto'){
			$this->configs['use'] = extension_loaded('soap')? 'soap' : 'nusoap';
		}

		
		if($this->configs['use'] === 'soap'){
			$client = new SoapClient($server, array('encoding' => 'UTF-8'));
			$method = "__soapCall";
			
		}else{
			$client = new SoapClient($server,'wsdl');
			$client->soap_defencoding = 'UTF-8';
			$method = 'call';
		}


		
				l($this->configs['use'],'type');
		l($data,'send data');
		l($method,'methgod');
		$res = $client->{$method}('PaymentRequest',array($data));	
		l($res);
			$status = $res['Status'];
		if($res['Status'] === '100' && strlen($res['Authority']) === 36){
			$transaction['authority'] = $res['Authority'];
		
			$res = $this->Transaction->createTransaction($transactionId,$transaction);
			if($res){
				$gateway = $this->configs['getewayUrl'].DS.$transaction['authority'];
				if($this->configs['zarinGate']) {
					$gateway .=  DS.'ZarinGate';
				}
				return $this->controller->redirect($gateway,true);
			}
		}else{
			
		return $this->errorCode[$status];
		}
		
	}



	/**
	 * ZarinpalComponent::_setupClient()
	 * setup client soap object
	 * @return void
	 */
	private function client(){
		
	
	
		
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