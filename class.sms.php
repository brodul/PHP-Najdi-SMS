<?php
/**
* Purpose: 
*	Sending SMS via Najdi.si Free SMS
*
* Description: 
*	Script logs in using your username and password for https://id.najdi.si and then it sends an SMS to a mobile phone number. 
*	There is a 160 character limit per message and daily limit of 40 sent messages.
*	The script tries to limit the number of requests to a page and tries to hold on to the session as long as possible. 
*	The session cookie is stored in a /tmp/folder for later use, if the script restarts. 
*	Once instantiated, object can be reused for sending a number of SMS messages. 
*	If a new object is instantiated, it will prefer to use a stored session.
*
* @version Date: 1. february 2012
* @author Žan Kafol
* @access public
*/

class sms {
	
	public $username = '';
	public $pass = '';
	
	public $cookie_jar = '/tmp/cookie.najdi.si';
	
	private $error = '';
	
	/*
	* Construct function
	* @param string $username
	* @param string $password
	*/
	function __construct($u = null, $p = null) {
		if(!is_null($u)) {
			$this->username = $u;
		}
		
		if(!is_null($p)) {
			$this->pass = $p;
		}
	}
	
	/*
	* Sends SMS
	* @param string $number
	* @param string $message
	*/
	public function send($number,$message) {
		$msg = $message;
		$message = urlencode(substr(iconv('UTF-8', 'ASCII//TRANSLIT', $message), 0, 160));
		$number = ltrim(preg_replace('/[^\d]/','',$number),'0');
		
		@list($area,$num1,$num2) = explode(' ',preg_replace('/(\d{2})(\d{3})(\d{3})/','\1 \2 \3',$number));
		
		$url = "http://www.najdi.si/sms/smsController.jsp?sms_action=4&sms_so_ac_={$area}&sms_so_l_={$num1}%20{$num2}&myContacts=&sms_message_=$message";
		
		$response = $this->response($this->req($url));
		
		if($response === false) {
			trigger_error("Failed to send SMS ($number,$msg)");
			$this->login();
			$response = $this->response($this->req($url));
		}
		
		return $response;
	}
	
	/*
	* Returns last error as stdclass
	*/
	public function get_error() {
		return $this->error;
	}
	
	private function login() {
		$this->req('http://www.najdi.si/auth/login.jsp?lg=0&target_url=http%3A%2F%2Fwww.najdi.si%2Findex.jsp');
		
		return $this->req("https://id.najdi.si/j_spring_security_check", array(
			'j_username'					=> $this->username,
			'j_password'					=> $this->pass,
			'_spring_security_remember_me'	=> 'on'
		));
	}
	
	private function response($r) {
		$r = json_decode($r);
		
		if($r->dialog == 3) {
			$this->error = false;
			return $r;
		}
		
		$this->error = $r;
		return false;
	}
	
	private function req($url, $post = false) {
		$ack = curl_init();
		
		curl_setopt($ack, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
		curl_setopt($ack, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ack, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ack, CURLOPT_URL, $url);
		curl_setopt($ack, CURLOPT_COOKIEFILE, $this->cookie_jar); 
		curl_setopt($ack, CURLOPT_COOKIEJAR, $this->cookie_jar); 
		
		if($post) {
			curl_setopt($ack, CURLOPT_POST, true);
			curl_setopt($ack, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		
		$dat = curl_exec($ack);
		curl_close($ack);
		
		return $dat;
	}
}
?>