<?php

//include Requests class
require_once(dirname(__FILE__) . "/Requests/Requests.php");

/**
 * The CityIndex API PHP Client.
 * 
 * Usage:
 *  - include the CIAPIPHP.class.php file
 *  - check documentation for methods.
 * 
 * @package CIAPI-PHP
 * @todo Is there need for a $password field?
 */
class CIAPIPHP{
	
	/** @var constant The max length for the username/password */
	const ID_MAX = 10;
	/** @var constant The min lenght for the username/password */
	const ID_MIN = 2;
	
	/** @var string If user logged in stores the current session token. Defaults
	 * false */
	public $session=false;
	
	/** @var string The end point url */
	private $endpoint = "https://ciapi.cityindex.com/tradingapi/";
	/** @var array An array of error messages */
	private $errors = array();
	/** @var string The current password */
	private $password = false;
	/** @var string The current username */
	private $username = false;
	
	/**
	 *  Construct.
	 */
	function __construct(){
		
		Requests::register_autoloader();
	}
	
	/**
	 * Get account information.
	 * 
	 * Client must be logged in and have a valid session token. Error reports.
	 * 
	 * @return mixed On success will return a stdClass or false on error.
	 */
	public function getAccountInformation() {
		
		if(!$this->session){
			$this->error("Invalid session token");
			return false;
		}
		
		$response = $this->get($this->endpoint . '/UserAccount/ClientAndTradingAccount');
		$result = json_decode($response->body);
		
		if(@$result->ErrorMessage){
			$this->error( $result->ErrorMessage );
			return false;
		}
		
		return $result;
	}
	
	/**
	 * Get an array of errors.
	 * 
	 * Will empty the error stack and reset $this->errors to false.
	 * 
	 * @return mixed Will return array of errors or false if none found. 
	 */
	public function get_errors(){
		if(count($this->errors)){
			$ret = $this->errors;
			$this->errors = false;
			return $ret;
		}
		else return false;
	}
	
	/**
	 * Get the last error.
	 * 
	 * Will leave the error stack intact. Use CIAPIPHP::get_errors() to clear
	 * the error stack.
	 * 
	 * @return mixed Will return last error, or false if none found.
	 */
	public function get_last_error(){
		if(count($this->errors)) 
			return $this->errors[count($this->errors)-1];
		return false;
	}
	
	/**
	 * Logs in a user.
	 * 
	 * A successful login will return true, the session token will be stored in
	 * $this->session. 
	 * 
	 * A false login attempt will return false and log an error.
	 * 
	 * @uses CIAPIPHP::session
	 * @see CIAPIPHP::get_error()
	 * @param string $username Required. The CityIndex username.
	 * @param string $password Required. The CityIndex password.
	 * @return boolean.
	 */
	public function logIn( $username, $password ){
		
		//set user/pswd fields
		if(
			!$this->set_username( $username ) ||
			!$this->set_password( $password )
		) return false;
		
		//post data
		$data = json_encode(array(
			'userName' => $this->username,
			'password' => $this->password
		));
		
		//get response
		$res = $this->post(
			$data,
			$this->endpoint . "/session"
		);
		$json = json_decode($res->body);
		
		//error report
		if(@$json->ErrorMessage){
			$this->error($json->ErrorMessage);
			return false;
		}
		if(!$json->Session){
			$this->error("Unkown error logging in");
			return false;
		}
		
		//success!
		$this->session = $json->Session;
		return true;
	}
	
	/**
	 * Sets the username.
	 * 
	 * Checks the length, if wrong length registers error and returns false.
	 * 
	 * @param string $username The CityIndex username.
	 * @return boolean
	 */
	public function set_username( $username ){
		
		//check username length
		if(
			strlen($username) > self::ID_MAX ||
			strlen($username) < self::ID_MIN
		){
			$this->error("Invalid username length");
			return false;
		}
		
		$this->username = $username;
		return true;
	}
	
	/**
	 * Set the password.
	 * 
	 * Checks the length, if wrong length registers error and returns false.
	 * 
	 * @param string $password The CityIndex password.
	 * @return boolean 
	 */
	public function set_password( $password ){
		
		//check password length
		if(
			strlen($password) > self::ID_MAX || 
			strlen($password) < self::ID_MIN 
		){
			$this->error("Invalid password length");
			return false;
		}
		
		$this->password = $password;
		return true;
	}
	
	/**
	 * Log an error.
	 * 
	 * @uses CIAPIPHP::errors
	 * @param type $msg
	 */
	private function error( $msg ){
		$this->errors[] = $msg;
	}
	
	/**
	 * Make a get request.
	 * 
	 * @param string $url The end point url.
	 * @param array $headers Additional headers.
	 * @param array $options Additional options
	 * @return Requests_Response 
	 */
	private function get($url, $headers = array(), $options = array()) {
		$defaultHeaders = array(
			'Content-Type' => 'application/json', 'UserName' => $this->username, 'Session' => $this->session,
		);
		$headers = array_merge($defaultHeaders, $headers);
		$defaultOptions = array();
		$options = array_merge($defaultOptions, $options);

		return Requests::get($url, $headers, $options);
	}

	/**
	 * Make a post request.
	 * 
	 * @uses Requests
	 * @param string $url
	 * @param type $headers
	 * @param type $data
	 * @param type $options
	 * @return Requests_Response
	 */
	private function post( $data, $url="", $headers=array(), $options=array()){
		
		//default values
		if(!$url) 
			$url = $this->endpoint;
		$headers = array_merge(array(
			'Content-Type' => 'application/json', 'UserName' => $this->username, 'Session' => $this->session,
		), $headers);
		$options = array_merge(array(
			
		), $options);
		
		//post request
		return Requests::post(
			$url, 
			$headers,
			$data, 
			$options
		);
	}
}
?>