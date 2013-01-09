<?php

/**
 * Modules should extend this class.
 * 
 * Allows the over-riding of headers and the parsing of parameters. 
 */
require_once('includes/OAuth.php');
if (!class_exists("API_Con_Mngr_Module")):

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
		
		/** @var string The signature encoding method */
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
		
		/** @var string The prefix for the user meta keys */
		private $option_name = "API_Con_Mngr_Module";

		function __construct() {

			global $API_Connection_Manager;
			$this->api = $API_Connection_Manager;
			
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
		 * If you find an error then return $this->error("error message") and if
		 * not then return true.
		 * 
		 * @uses $this->error()
		 * @param array $response The response in the same format as returned by
		 * the WP_HTTP class.
		 * @return mixed Returns WP_Error if error or true if none.
		 */
		abstract public function check_error( array $response );
		
		/**
		 * Builds and signs a request object.
		 * 
		 * Uses the field $this->sha1_method to sign the request.
		 *
		 * @param string $url Def
		 * @param type $method
		 * @param type $params
		 * @return OAuthRequest Returns an oauth request object 
		 */
		public function build_request( $url, $method='GET', $params=array()){
			
			//token must be stdClass
			$token = (object) array(
				'key' => $this->oauth_token,
				'secret' => $this->oauth_token_secret,
				'uid' => $this->user_id
			);
			
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $token, $method, $url, $params);
			$request->sign_request($this->sha1_method, $this->consumer, $token);
			return $request;
		}
		
		/**
		 * Do callback. Will check $_SESSION for callback details.
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
		 * Handy for grabbing other information like user id's etc
		 * @param stdClass $dto The data transport object created by
		 * API_Connection_Manager::_service_parse_dto()
		 */
		public function do_login( stdClass $dto ){
			;
		}
		
		/**
		 * Error handling
		 * @param string $msg
		 * @return \WP_Error 
		 */
		public function error($msg) {
			return new WP_Error('API_Con_Mngr_Module', $msg);
		}

		public function get_access_token( $oauth_verifier ){
			$request = $this->request( $this->url_access_token, 'GET', array(
				'oauth_verifier' => $oauth_verifier
			));
			$token = OAuthUtil::parse_parameters($request['body']);
			$this->token = new OAuthConsumer( $this->oauth_token, $this->oauth_token_secret);
		}
		
		/**
		 * Returns the authorize url for oauth1
		 * @param string $token The request token
		 * @return string 
		 */
		public function get_authorize_url( array $tokens ) {
			return $this->url_authorize . "?" . http_build_query($tokens); //oauth_token={$token['oauth_token']}&oauth_token_secret={$token['oauth_token_secret']}";
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
		 * @see API_Connection_Manager::_response_listener() for more.
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
		 * Returns array of params for this module
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
		 * You may need to override this to suit. It must return an array
		 * @param string $method Default GET. The http method to use.
		 * @return array
		 */
		public function get_request_token( $method='GET' ) {
			
			$res = $this->request($this->url_request_token, $method);
			
			$ret = array();
			
			switch($res['headers']['content-type']){
				
				case 'application/x-www-form-urlencoded':
					parse_str($res['body'], $ret);
					break;
				
				default:
					$ret = (array) json_decode($res['body']);
					break;
			}
			
			return $ret;
		}

		/**
		 * Sets any class fields that in the dto->response array
		 * @param stdClass $dto 
		 */
		public function parse_dto($dto) {

			//looks for fields that are in dto response
			foreach ($dto->response as $key => $val)
				if (isset($this->$key))
					$this->$key = $val;
		}

		/**
		 * Format and sign an OAuth / API request
		 */
		public function request($url, $method, $parameters = array()) {

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
				$msg = $errs->get_error_message();
				print $msg;
				$msg = addslashes( "Error: {$original_url}\\n".$msg);
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
		 * Set the module details fields such as Name and Description.
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
		 * Set the header.
		 * 
		 * @param API_Con_Mngr_Header $header
		 * @return \API_Con_Mngr_Header 
		 */
		public function set_header(API_Con_Mngr_Header $header) {
			return $header;
		}

		/**
		 * Set the module options.
		 * @param array $options Associative array of options
		 */
		public function set_options(array $options) {
			$this->options = $options;
		}

		/**
		 * Set params.
		 * Will set fields that have the same param name.
		 * @param array $params An associative array of parameters for this module
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

	/**
	 * The header datatype for the API Connection Manager. 
	 */
	class API_Con_Mngr_Header {

		//the current header params
		public $headers;

		/**
		 * Return the current $headers as an array
		 * @return array 
		 */
		public function header_to_array() {
			
		}

		/**
		 * Return the current $headers as string
		 * @return string 
		 */
		public function array_to_header() {
			
		}

	}

	/**
	 * The param datatype, will include methods for formating/encoding and parsing
	 * parameters for the service. This datatype forms the body of the DTO sent to
	 * the service. 
	 */
	class API_Con_Mngr_Param {

		public $params = array();

		function __construct(array $params) {
			$this->params = $params;
		}

	}

	

	

	

	
endif;