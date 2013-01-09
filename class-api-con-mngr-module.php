<?php
require_once('includes/OAuth.php');
if (!class_exists("API_Con_Mngr_Module")):
	/**
	* Modules should extend this class.
	* e.g.:
	* <code>
	* class My_Class extends API_Con_Mngr_Module{
	*		function __construct(){
	*			parent::__construct();
	*		}
	*		function check_error( $response ){
	*			return false;
	*		}
	* }
	* $oauth1 = new My_Class();
	* </code>
	* 
	 * You module class should be saved in a file called <code>index.php</code>
	 * in a sub-folder in <code>wp-content/plugins/api-con-mngr-modules</code>
	 * If this folder is not created yet make sure you have installed and
	 * activated the API Manager Core
	 * {@link https://github.com/david-coombes/api-connection-manager}
	 * 
	* Class definition file
	* =====================
	* Your class should extend this and also construct it at the bottom of the
	* class file as one of the following:
	*	- $oauth1 = new My_Class()
	*	- $oauth2 = new My_Class()
	*	- $service = new My_Class() //custom services
	* 
	* Oauth1
	* ======
	* As well as providing methods declared abstract the following is required:
	* Methods:
	*	- ::__construct() //this must call parent::__construct()
	*	- ::do_login() //use this to process request tokens
	* Fields:
	*	- ::consumer_key //required by the oauth1 spec
	*	- ::consumer_secret //required by the oauth1 spec
	* 
	* Oauth2
	* ======
	* No oauth2 documentation yet
	* 
	* Service (provider's custom api)
	* ===============================
	* No service documentation yet
	* 
	* @package api-connection-manager
	* @author daithi
	*/
	abstract class API_Con_Mngr_Module {

		/** @var string The callback url */
		public $callback_url = "";
		
		/** @var integer The connection timeout */
		public $connecttimeout = 30;

		/** @var OAuthConsumer The consumer object */
		public $consumer;

		/** @var string The consumer key */
		public $consumer_key;

		/** @var string The consumer secret */
		public $consumer_secret;
		
		/** @var string The module description */
		public $Description;

		/** @var string The uri for displaying a login link */
		public $login_uri = "";

		/** @var string The name of the module */
		public $Name = "";

		/** @var string The nonce for this instance of the module */
		public $oauth_nonce = "";

		/** @var string Oauth1 token */
		public $oauth_token = "";

		/** @var string Oauth1 token secret */
		public $oauth_token_secret = "";
		
		/** @var array An array of static params */
		public $params = array();

		/** @var string The current protocol used (oauth, custom, etc) */
		public $protocol = "";

		/** @var boolean Flag whether server allows sessions or not. Some
		 * modules need sessions and will be disabled on servers without 
		 * sessions enabled */
		public $sessions = true;
		
		/** @var OAuthSignatureMethod_HMAC_SHA1 The signature encoding method */
		public $sha1_method = "";

		/** @var string The slug of the current login */
		public $slug = "";

		/** @var boolean Verify SSL Cert. */
		public $ssl_verifypeer = FALSE;

		/** @var integer Set timeout default. */
		public $timeout = 30;

		/** @var string The token */
		public $token = "";

		/** @var string The authorize url */
		public $url_authorize;
		
		/** @var string The access token url */
		public $url_access_token;
		
		/** @var string The request token url */
		public $url_request_token = "";

		/** @var string The url to verify an access token */
		public $url_verify_token;
		
		/** @var boolean Flag to set whether provider will return nonce or not */
		public $use_nonce = true;
		
		/** @var string The user agent to send with requests */
		public $useragent = "TwitterOAuth v0.2.0-beta2";

		/** @var API_Connection_Manager The main api class */
		private $api;
		
		/** @var API_Con_Mngr_Log The log class */
		private $log_api;
		
		/** @var string The prefix for the user meta keys */
		private $option_name = "API_Con_Mngr_Module";

		/**
		 * Make sure you call this from your child class.
		 * 
		 * @global API_Connection_Manager $API_Connection_Manager 
		 */
		function __construct() {

			global $API_Connection_Manager;
			$this->api = $API_Connection_Manager;
			$this->log_api = new API_Con_Mngr_Log();
			
			//if oauth1
			if($this->protocol=='oauth1'){
				$this->consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret, $this->callback_url);
				$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
			}

			//if sessions enabled (default is true)
			$id = session_id();
			if(!$id || $id=="")
				$this->sessions = false;
		}

		/**
		 * This method checks a response from the service for an error and must
		 * be declared by your class.
		 * 
		 * If you find an error in the response return the error string else
		 * return false for no error.
		 * 
		 * @param array $response The response in the same format as returned by
		 * the WP_HTTP class.
		 * @return mixed Returns false if no error or string if error found.
		 */
		abstract public function check_error( array $response );
		
		/**
		 * Builds and signs a request object.
		 * 
		 * Uses the field $this->sha1_method to sign the request which must be
		 * type OAuthSignatureMethod_HMAC_SHA1
		 *
		 * @uses API_Con_Mngr_Module::sha1_method OAuthSignatureMethod_HMAC_SHA1
		 * @param string $url The end point url.
		 * @param string $method Default GET. The http method
		 * @param array $params Array of params in key value pairs
		 * @return OAuthRequest Returns an oauth request object 
		 */
		public function oauth_sign_request( $url, $method='GET', $params=array()){
			
			$token = new OAuthConsumer($this->oauth_token, $this->oauth_token_secret);
			
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $token, $method, $url, $params);
			$request->sign_request($this->sha1_method, $this->consumer, $token);
			return $request;
		}
		
		/**
		 * Do callback.
		 * 
		 * This is called from API_Connection_Manager::_response_listener() The
		 * callback is set when the login button is printed
		 * 
		 * @see API_Con_Mngr_Module::get_login_button()
		 * @see API_Connection_Manager::_response_listener()
		 * @param stdClass $dto The response dto.
		 */
		public function do_callback( stdClass $dto ) {
			
			//get callback from sessions
			if(!$this->use_nonce){
				$callback = $_SESSION['callback'];
				
				require_once( $callback['file'] );
				
				//call a method
				if(is_array($callback['func'])){
					$class = $callback['func'][0];
					$method = $callback['func'][1];
					$obj = new $class();
					$obj->$method($dto);
				}

				//call a function
				else{
					$func = $callback['func'];
					$func($dto);
				}
			}
		}

		/**
		 * This method gets called after a successfull login.
		 * It is called after:
		 *  - oauth1 successfull app authorization and request tokens
		 *  - oauth2
		 * 
		 * Override this method to parse results.
		 * 
		 * @param stdClass $dto The data transport object created by
		 * API_Connection_Manager::_service_parse_dto()
		 */
		public function do_login( stdClass $dto ){
			;
		}
		
		/**
		 * Error handling.
		 * 
		 * Will return a WP_Error object with 'API_Con_Mngr_Module' as the code.
		 * 
		 * @param string $msg The error message.
		 * @return \WP_Error 
		 */
		public function error($msg) {
			return new WP_Error('API_Con_Mngr_Module', $msg);
		}

		/**
		 * Returns the authorize url for oauth1.
		 * 
		 * @param array $tokens The request tokens
		 * @return string 
		 */
		public function get_authorize_url( array $tokens ) {
			return $this->url_authorize . "?" . http_build_query($tokens);
		}

		/**
		 * Returns a link to login to this service.
		 * 
		 * Covers two scenario's:
		 * 1) If there is no wordpress user logged in, then it will print a
		 * login with $service link.
		 * 2) If wp user is logged in then it will print the login link and die.
		 * The accept app will open in a new tab and will then refresh the
		 * window.opener object.
		 * 
		 * @param string The full url to the file with the callback, if one is
		 * required.
		 * @param mixed Either an array or string of the function/method if a
		 * callback is required.
		 * @return string Html anchor
		 */
		public function get_login_button($file = '', $callback = '') {

			//nonce
			global $API_Connection_Manager;
			$i = wp_nonce_tick();
			$user = $API_Connection_Manager->get_current_user()->ID;
			
			
			/**
			 * If no user id
			 * Then this will be a sign in with $service link 
			 */
			if(!$user || $user==='0'){
				$nonce = substr(wp_hash($i . $this->slug . $user, 'nonce'), -12, 10);
				$state = serialize(array(
					$nonce,
					urlencode($this->slug),
					$user
						));
				$this->oauth_nonce = $state;

				//set callback
				$API_Connection_Manager->_set_callback($file, $callback, $state, $this->use_nonce);

				//if not using nonces in request, append nonce to login uri
				if(!$this->use_nonce)
					$this->login_uri .= "&nonce=".  urlencode($state);

				//switch through protocols. These login uri's are set in API_Connection_Manager::_get_module();
				switch ($this->protocol) {
					case 'oauth1':
						return "<a href=\"{$this->login_uri}\">{$this->Name}</a>";
						break;

					default:
						return $this->_error("Please override API_Con_Mngr_Module::get_login_button in you plugin");
						break;
				}
			}
			//end no user id
			
			/**
			 * If user id
			 * then login new tab and refresh window.parent 
			 */
			else{
				switch($this->protocol){
					
					case 'oauth1':
						print "<br/><em>You are not signed into {$this->Name}</em><br/>
							<a href=\"{$this->login_uri}&login=true\" target=\"_new\">Sign into {$this->Name}</a>
							";
						break;
				}
				exit;
			}
			//end if user id
		}

		/**
		 * Returns array of params for this module.
		 * 
		 * @global type $API_Connection_Manager To get the current user_id
		 * @return array $array[key=>val] 
		 */
		public function get_params(){
			
			global $API_Connection_Manager;
			$user_id = API_Connection_Manager::_get_current_user()->ID;
			$meta = get_user_meta($user_id, $this->option_name."-{$this->slug}", true);
			
			if(is_array($meta))
				foreach($meta as $key=>$val)
					if(isset($this->{$key}))
						$this->{$key} = $val;
			
			return $meta;
		}
		
		/**
		 * Get oauth1 request token.
		 * You may need to override this to suit.
		 * 
		 * @param string $method Default GET. The http method to use.
		 * @return array
		 */
		public function get_request_token( $method='GET' ) {
			
			//clear any redundant params before getting authorize url
			$this->set_params(array(
				'oauth_token' => null,
				'oauth_token_secret' => null,
				'token' => null
			));
			
			//make request
			$res = $this->request($this->url_request_token, $method);
			$ret = $this->parse_response($res);
			
			//store tokens and return
			$this->set_params(array(
				'oauth_token' => $ret['oauth_token'],
				'oauth_token_secret' => $ret['oauth_token_secret']
			));
			return $ret;
		}

		/**
		 * Log a message to the log file.
		 * 
		 * @see API_Con_Mngr_Log::write()
		 * @param string The message to log
		 * @return mixed Returns num of bytes if success or FALSE on fail.
		 */
		public function log( $msg ){
			
			return $this->log_api->write($msg, "API Module {$this->slug}");
		}
		
		/**
		 * Sets any class fields in this instance that in the dto->response 
		 * array.
		 * 
		 * @param stdClass $dto 
		 */
		public function parse_dto($dto) {

			//looks for fields that are in dto response
			foreach ($dto->response as $key => $val)
				if (isset($this->$key))
					$this->$key = $val;
		}

		/**
		 * Parse a response body.
		 * 
		 * @param array $response The response in the format returned from
		 * WP_HTTP
		 * @return mixed Will return either an array or object based on the
		 * response header content-type
		 */
		public function parse_response( array $response ){
			
			//vars
			$content_type = strtolower($response['headers']['content-type']);
			$ret = array();
			
			//parse string
			if(
				(strpos($content_type, "text/html") !== false) ||
				(strpos($content_type, "application/x-www-form-urlencoded") !== false)
			) parse_str($response['body'], $ret);
			
			//default to json
			else
				$ret = json_decode($response['body']);
			
			//return result
			return $ret;
		}
		
		/**
		 * Send requests to the provider.
		 * 
		 * @param string $uri The full endpoint url.
		 * @param string $method Default GET. The http method to user.
		 * @param array $parameters Optional. An array of parameters in key
		 * value pairs
		 * @return array Returns the response array in the WP_HTTP format. 
		 */
		public function request($url, $method='GET', $parameters = array()) {

			//vars
			$method = strtoupper($method);
			$original_url = $url;	//used for error reporting
			$errs=false;
			
			//make request
			switch ($method) {
				case 'POST':
					$response = wp_remote_post($url, array('body'=>$parameters));
					break;
				default:
					
					if(count($parameters))
						$url .= "?" . http_build_query($parameters);
					$response = wp_remote_get($url);
					break;
			}//end request
			
			//if http body
			if(is_wp_error($response))
				$errs = $response;
			elseif(is_wp_error($response['body']))
				$errs = $response['body'];
			
			//check for errors
			if(!$errs)
				$errs = $this->check_error($response);
			if(is_wp_error($errs)){
				$msg = addslashes( $errs->get_error_message() );
				//print addslashes( "Error: <small>{$original_url}</small><br/>");
				print "
					<script>
						if(window.opener){
							alert('{$msg}');
							//window.opener.location.reload();
							//window.close();
						}
					</script>
					";
				print "<em>\n" . str_replace("\n", "<br/>", $msg) . "</em>\n";
				$this->get_login_button();
			}
			
			return $response;
		}

		/**
		 * Set this instance details fields such as Name and Description.
		 * 
		 * Called in API_Connection_Manager::_get_installed_services()
		 * @param array $data 
		 */
		public function set_details( array $data ){
			
			//if param is a field
			foreach ($data as $key => $val)
				if (isset($this->{$key}))
					$this->{$key} = $val;

		}
		
		/**
		 * Set params.
		 * Will set fields that have the same param name and update the db.
		 * 
		 * @param array $params An associative array of parameters for this 
		 * module
		 * @return array Returns the new params db values.
		 */
		public function set_params(array $params) {
			
			global $API_Connection_Manager;
			$user_id = $API_Connection_Manager->get_current_user()->ID;
			
			$meta = $this->get_params(); //get_user_meta($user_id, $this->option_name."-{$this->slug}", true);
			foreach($params as $key=>$val)
				$meta[$key] = $val;

			update_user_meta($user_id, $this->option_name."-{$this->slug}", $meta);
			return $this->get_params();
		}		
	}
	
endif;