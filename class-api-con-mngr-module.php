<?php

/**
 * Modules should extend this class.
 * 
 * Allows the over-riding of headers and the parsing of parameters. 
 */
if (!class_exists("API_Con_Mngr_Module")):

	class API_Con_Mngr_Module {

		/** @var string Oauth1 autorize url */
		public $autorize_url = false;

		/** @var integer The connection timeout */
		public $connecttimeout = 30;

		/** @var OAuthConsumer The consumer object */
		public $consumer;

		/** @var string The consumer key */
		public $consumer_key;

		/** @var string The consumer secret */
		public $consumer_secret;

		/** @var string The uri for displaying a login link */
		public $login_uri = false;

		/** @var string The name of the module */
		public $Name = false;

		/** @var string The nonce for this instance of the module */
		public $oauth_nonce = false;

		/** @var string Oauth1 token */
		public $oauth_token = false;

		/** @var array An array of static params */
		public $params = array();

		/** @var string The current protocol used (oauth, custom, etc) */
		public $protocol = "";

		/** @var string The signature encoding method */
		public $sha1_method = "";

		/** @var string The slug of the current login */
		public $slug = "";

		/** @var boolean Verify SSL Cert. */
		public $ssl_verifypeer = FALSE;

		/** @var integer Set timeout default. */
		public $timeout = 30;

		/** @var string The token */
		public $token = NULL;

		/** @var string The request token url */
		public $url_request_token;

		/** @var string The user agent to send with requests */
		public $useragent = "TwitterOAuth v0.2.0-beta2";

		/** @var API_Connection_Manager The main api class */
		private $api;

		function __construct($params = array(), $options = array()) {

			global $API_Connection_Manager;
			$this->api = $API_Connection_Manager;
			$this->consumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);
			$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();

			$this->set_params($params);
			$this->set_options($options);
		}

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
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
			$request->sign_request($this->sha1_method, $this->consumer, $this->token);
			return $request;
		}
		
		public function do_callback() {
			;
		}

		public function get_authorize_url() {
			;
		}

		/**
		 * Returns a link to login to this service.
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
			$nonce = substr(wp_hash($i . $this->slug . $user, 'nonce'), -12, 10);
			$state = serialize(array(
				$nonce,
				urlencode($this->slug),
				$user
					));
			$this->oauth_nonce = $state;

			//set callback
			$API_Connection_Manager->_set_callback($file, $callback, $state);
			//$_SESSION['callbacks'][ $this->slug ];
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

		/**
		 * Get oauth1 request token 
		 */
		public function get_request_token() {
			;
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

			$http = new WP_Http();
			$method = strtoupper($method);
			switch ($method) {
				case 'POST':
					break;
				default:
					return wp_remote_get($url);
					break;
			}
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

			//if param is a field
			foreach ($params as $key => $val)
				if (isset($this->{$key}))
					$this->{$key} = $val;

			//set raw param array
			$this->params = $params;
		}

		/**
		 * Error handling
		 * @param string $msg
		 * @return \WP_Error 
		 */
		private function _error($msg) {
			return new WP_Error('API_Con_Mngr_Module', $msg);
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