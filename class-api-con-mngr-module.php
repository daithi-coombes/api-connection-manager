<?php
require_once('includes/OAuth.php');

if (!class_exists("API_Con_Mngr_Module")):
	/**
	* Modules should extend this class.
	* e.g.:
	* <code>
	* class My_Class extends API_Con_Mngr_Module{
	*		function __construct(){
	*			$this->options = array(
	*				'access_token' => '%s'
	*			);
	*			$this->protocol = "custom";
	*			parent::__construct();
	*		}
	*		function check_error( $response ){
	*			return false;
	*		}
	* }
	* $service = new My_Class();
	* </code>
	* 
	* Options that are per wordpress installation, such as client_id or scopes
	* should be defined in the class construct. See
	* {@see API_Con_Mngr_Module::construct_options()} for details on the format
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
	 *  - ::check_error //check the service responses for error
	*	- ::do_login() //use this to process request tokens
	* Fields:
	*	- ::consumer_key //required by the oauth1 spec
	*	- ::consumer_secret //required by the oauth1 spec
	* 
	* Oauth2
	* ======
	* The following must be declared by your child class
	* Methods:
	*  - ::__constrcut() //must construct parent class
	*  - ::check_error() //check the server responses for errors
	*  - ::get_authorize_url //only the client_id and redirect_url are
	* required by the spec, you may need to add additional params here
	* 
	* Service (provider's custom api)
	* ===============================
	* No service documentation yet
	* 
	* @package api-connection-manager
	* @author daithi
	*/
	abstract class API_Con_Mngr_Module {

		/** @var string Oauth2 access token */
		public $access_token = "";
		
		/** @var string The oauth2 access type parameter */
		public $access_type = "";
	
		/** @var string The callback url */
		public $callback_url = "";
		
		/** @var string The client id. Mainly used for oauth2 */
		public $client_id = "";
		
		/** @var string The client secret. Mainly used for oauth2  */
		public $client_secret = "";
		
		/** @var integer The connection timeout */
		public $connecttimeout = 30;

		/** @var OAuthConsumer The consumer object */
		public $consumer;

		/** @var string The module description */
		public $Description;

		/** @var string The uri for displaying a login link */
		public $login_uri = "";

		/** @var string The name of the module */
		public $Name = "";

		/** @var string Oauth1. The consumer key */
		public $oauth_consumer_key;

		/** @var string Oauth1. The consumer secret */
		public $oauth_consumer_secret;
		
		/** @var string Oauth1. The nonce for this instance of the module */
		public $oauth_nonce = "";
		
		/** @var string Oauth1 request token */
		public $oauth_request_token = "";
		
		/** @var string Oauth1 request token secret */
		public $oauth_request_token_secret = "";

		/** @var string Oauth1 token */
		public $oauth_token = "";

		/** @var string Oauth1 token secret */
		public $oauth_token_secret = "";
		
		/**An array of options for the service.
		 * @see API_Con_Mngr_Module::construct_options() for details
		 * @var array 
		 */
		public $options = array();
		
		/** @var array An array of static params */
		public $params = array();

		/** @var string The current protocol used (oauth, custom, etc) */
		public $protocol = "";

		/** @var string The redirect uri. Mainly used for oauth2 */
		public $redirect_uri = "";
		
		/** @var string The scope parameter, usually for oauth2 */
		public $scope = "";
		
		/** @var boolean Flag whether server allows sessions or not. Some
		 * modules need sessions and will be disabled on servers without 
		 * sessions enabled. Default false */
		public $sessions = false;
		
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

		/** @var WP_User The current user. Makes static call to the api core
		 *	method to build user from cookies & wp authentication
		 * @uses API_Connection_Manager::_get_current_user()
		 */
		public $user='';
		
		/** @var array An array of additional headers */
		protected $headers = array();
		
		/** @var API_Connection_Manager The main api class */
		private $api;
		
		/** @var Logger The log class */
		private $log_api;
		
		/** @var string The prefix for the user meta keys */
		private $option_name = "API_Con_Mngr_Module";
		
		/**
		 * Make sure you call this from your child class.
		 * 
		 * @global API_Connection_Manager $API_Connection_Manager 
		 */
		function __construct() {
			
			//make sure we have user id
			$this->user = API_Connection_Manager::_get_current_user();		
			/**
			 * bootstrap fields, params and options
			 */
			$this->slug = $this->get_slug();
			$this->log_api = Logger::getLogger(__CLASS__."::API Module {$this->slug}");
			//load user specific db params (access_tokens etc)
			$this->get_params();
			//load stored options
			$this->get_options();
			//if sessions enabled (default is true)
			$id = session_id();
			if(!$id || $id=="")
				$this->sessions = false;			
			//setup options variables for the API Services dashboard page
			$this->construct_options( $this->options );
			//end bootstrap
			
			//if oauth1
			if($this->protocol=='oauth1'){
				$this->consumer = new OAuthConsumer($this->oauth_consumer_key, $this->oauth_consumer_secret, $this->callback_url);
				$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
			}

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
		 * Make a request to verify a token. If no token then return false, if
		 * service provides no call to test token then make request for profile
		 * etc as a test.
		 * @return boolean 
		 */
		abstract public function verify_token();
		
		/**
		 * Do callback.
		 * 
		 * This is called from API_Connection_Manager::_response_listener() The
		 * callback is set when the connecting tab is opened. The dto must have
		 * the module slug set.
		 * 
		 * @see API_Con_Mngr_Module::get_login_button()
		 * @see API_Connection_Manager::_response_listener()
		 * @param stdClass $dto The response dto.
		 */
		public function do_callback( array $callback, stdClass $dto ) {
			
			if(!count($callback))
				return;
			
			//load file parse callback
			require_once( $callback['file'] );
			$callback['func'] = unserialize($callback['callback']);
			
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
			return new WP_Error('API_Con_Mngr_Module', "Error: " . $msg);
		}

		/**
		 * Oauth2. Get the access tokens from a token request and sets the 
		 * requests results using $this->set_params()
		 * @param array $response The response code.
		 * @return stdClass Returns the tokens 
		 */
		public function get_access_token( array $response ){
			
			switch($this->protocol){
				
				case 'oauth1': 
					$res = $this->request( $this->url_access_token, "POST",array(
						'oauth_verifier' => $response['oauth_verifier']
					));
					break;
				
				case 'oauth2':
					//make request for access tokens
					$res = $this->request( $this->url_access_token, "POST", array(
						'grant_type' => 'authorization_code',
						'client_id' => $this->client_id,
						'client_secret' => $this->client_secret,
						'code' => $response['code'],
						'redirect_uri' => $this->redirect_uri
					) );
					break;
			}
			
			//parse response and set tokens in db
			$tokens = (array) $this->parse_response($res);
			$this->set_params($tokens);
			return $tokens;
		}
		
		/**
		 * Returns the authorize url for oauth1 and oauth2
		 * 
		 * Override this method and provide any optional params your service
		 * requires. Only client_id and response_type are required by oauth2
		 * spec.
		 * 
		 * @param array $tokens The request tokens
		 * @return string 
		 */
		public function get_authorize_url( $params=array() ) {
			
			switch($this->protocol){
				
				//oauth1
				case 'oauth1':
					$params = array_merge(array(
						'oauth_consumer_key' => $this->oauth_consumer_key,
						'oauth_token' => $params['oauth_request_token']
					), $params);
					//$url = $this->
					
					return $this->url_authorize . "?" . http_build_query($params);
					break;
				//end oauth1
				
				//oauth2
				case 'oauth2':
					
					//fields
					$fields = array_merge($params, array(
						'client_id' => $this->client_id,	//oauth2 required
						'response_type' => 'code'			//oauth2 required
					));
					
					return $this->url_authorize . "?" . http_build_query($fields);
					break;
				//end oauth2
			}
		}

		/**
		 * Returns a link to login to this service.
		 * 
		 * Builds the link and dies. If the request is for a signin button then 
		 * provide the callback params and the link will be returned.
		 * 
		 * @param string $file Optional. Full path to callback file.
		 * @param mixed $callback Optional. Callback func name or array of 
		 * class, method names.
		 * @param boolean $die Default true. If true and sign on button
		 * required will die(button_html);
		 * @return string Html anchor
		 */
		public function get_login_button( $file='', $callback='', $die=true ){
			
			//nonce
			global $API_Connection_Manager;

			//using sessions
			$url = $API_Connection_Manager->redirect_uri . "&login=true&slug=" . urlencode($this->slug);
			
			//if not a sign on button
			if(empty($file) && empty($callback)){
				$msg = "<br/><em>You are not signed into {$this->Name}</em><br/>
						<a href=\"{$url}\" target=\"_new\">Sign into {$this->Name}</a>
						";
				if($die)
					die($msg);
				else 
					return $msg;
			}
			//end not a sign on button
					
			//if a sign on button
			else{
				if(is_array($callback))
					$clbk = serialize(array(
						get_class($callback[0]),
						$callback[1]
					));
				$url .= "&file=" .urlencode($file) . "&callback=" . urlencode($clbk);
				return $url;
			}
		}

		/**
		 * Returns module options such as client_id, scope etc.
		 * Sets the fields with their relevant option values.
		 * @return array 
		 */
		public function get_options(){
			
			//multisite install
			if(is_multisite())
				$options_db = get_site_option($this->option_name, array());
			else
				$options_db = get_option($this->option_name, array());
			
			//get module options or return default array
			if(!@$options_db[$this->slug]) return array();
			$options = $options_db[$this->slug];
			
			//set redirect_uri
			$redirect = admin_url('admin-ajax.php') . "?" . http_build_query(array(
				'action' => 'api_con_mngr'
			));
			if(@isset($options['redirect_uri']) && @empty($options['redirect_uri']))
				$options['redirect_uri'] = $redirect;
			if(@isset($options['callback_url']) && @empty($options['callback_url']))
				$options['callback_url'] = $redirect;
			//end redirect uri
				
			//set fields
			foreach($options as $key=>$val)
				$this->$key = $val;
			
			return $options;
		}
		
		/**
		 * Returns array of params for this module.
		 * 
		 * @global type $API_Connection_Manager To get the current user_id
		 * @return array $array[key=>val] 
		 */
		public function get_params(){
			
			$user_id = $this->user->ID;
			$key = $this->option_name."-{$this->slug}";
			$meta = get_user_meta($user_id, $key, true);
			
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
				'oauth_request_token' => null,
				'oauth_request_token_secret' => null,
				'token' => null
			));
			
			//make request
			$res = $this->request($this->url_request_token, $method);
			$ret = $this->parse_response($res);
			$params = array(
				'oauth_request_token' => $ret['oauth_token'],
				'oauth_request_token_secret' => $ret['oauth_token_secret']
			);
			
			//store tokens and return
			if(!$this->set_params($params))
				$_SESSION[$this->option_name][$this->slug]['params'] = $params;
			return $params;
		}

		/**
		 * Log an INFO message to the log file.
		 * 
		 * @param string The message to log
		 * @return None
		 */
		public function log( $msg ){
			$this->log_api->info($msg);
		}
		
		/**
		 * Sets any fields in this instance that in the dto->response 
		 * array.
		 * 
		 * @param stdClass $dto
		 * @deprecated
		 */
		public function parse_dto(stdClass $dto) {

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
		public function parse_response(  $response=array() ){
			
			//vars
			$content_type = strtolower($response['headers']['content-type']);
			$ret = array();
			
			//parse string
			if(
				(strpos($content_type, "text/html") !== false) ||
				(strpos($content_type, "text/plain") !== false) ||
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
		 * @param boolean $die Default true. Whether to die with a login button
		 * if an error occurs
		 * @return array Returns the response array in the WP_HTTP format. 
		 */
		public function request($url, $method='GET', $parameters = array(), $die=true) {

			//vars
			$method = strtoupper($method);
			$errs=false;
			
			//make request
			switch ($method) {
				case 'POST':
					$response = wp_remote_post($url, array('body'=>$parameters,'headers'=>$this->headers));
					break;
				default:
					
					if(count($parameters))
						$url .= "?" . http_build_query($parameters);
					$response = wp_remote_get($url, array('headers' => $this->headers));
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
				if($die){
					$msg = addslashes( $errs->get_error_message() );
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
				}
				$this->get_login_button(null, null, $die);
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
		 * @param array $options 
		 */
		public function set_options( array $options ){
			
			//set fields
			foreach($options as $key=>$val)
				$this->$key = $val;
			
			//multisite install
			if(is_multisite())
				$options_db = get_site_option($this->option_name, array());
			else
				$options_db = get_option($this->option_name, array());
			$options_db[$this->slug] = $options;
			
			//write to db
			if(is_multisite())
				update_site_option($this->option_name, $options_db);
			else
				update_option($this->option_name, $options_db);
			
			return $options;
		}
		
		/**
		 * Set params.
		 * Will set fields that have the same param name and update the db.
		 * 
		 * @global wpdb $wpdb
		 * @param array $params An associative array of parameters for this 
		 * module
		 * @return array Returns the new params db values.
		 */
		public function set_params(array $params) {
			
			global $wpdb;
			
			//set fields
			foreach($params as $key=>$val)
				$this->$key = $val;
			
			//if no user logged as in sign in buttons then return
			if(empty($this->user))
				$this->user = API_Connection_Manager::_get_current_user();
			$user_id = $this->user->ID;
			if($user_id==0 || empty($user_id))
				return false;
			
			//vars
			$option_name = $this->option_name."-{$this->slug}";
			$meta = $this->get_params(); //get_user_meta($user_id, $this->option_name."-{$this->slug}", true);
			foreach($params as $key=>$val)
				$meta[$key] = $val;
			
			/**
			 * manually update user_meta 
			 */
			if($wpdb->get_row("SELECT * FROM {$wpdb->usermeta} WHERE user_id={$user_id} AND meta_key='{$option_name}'"))
				$wpdb->update($wpdb->usermeta, array(
					'meta_value' => serialize($meta)
				), array(
					'user_id' => $user_id,
					'meta_key' => $option_name
				), array('%s'), array('%d','%s'));
			else
				$wpdb->insert($wpdb->usermeta, array(
					'user_id' => $user_id,
					'meta_key' => $option_name,
					'meta_value' => serialize($meta)
				), array('%d','%s','%s'));
			//end manual update
			
			//return params
			return $this->get_params();
		}	
		
		/**
		 * Helper method to login user using an email provided by a service. On
		 * success will redirect to $redirect on fail will die() with error
		 * message.
		 * @param string $email The email to use
		 * @param string $redirect Default will redirect to admin_url()
		 */
		public function wp_login( $email, $redirect='' ){
			
			$user_id = email_exists($email);
			if(empty($redirect)) $redirect = admin_url();
			
			//if no email match
			if(!$user_id){
				die("Sorry the email <em>{$email}</em> is not on our system");
			}
			
			//
			$user = get_userdata($user_id);
			wp_set_current_user( $user->data->ID );
			wp_set_auth_cookie( $user->data->ID );
			wp_redirect("{$redirect}");
			die();
		}
		
		/**
		 * Builds the options array.
		 * 
		 * Options are added in the format:
		 * array( 'option_name' => 'datatype')
		 * where datatype is:
		 *  - '%s' for string
		 *  - '%d' for integer
		 *  - array() key value pairs for drop down
		 * 
		 * Oauth1:
		 * Defaults are consumer key and consumer secret.
		 * 
		 * Oauth2:
		 * Defaults are client_id, client_secret, client_uri
		 * 
		 * @param array $options An array of options
		 * @return array Returns the full options including requirements 
		 */
		protected function construct_options( $options=array() ){
			
			switch($this->protocol){
				
				//oauth1 defaults
				case 'oauth1':
					$defaults = array(
						'oauth_consumer_key' => '%s',
						'oauth_consumer_secret' => '%s'
					);
					break;
				
				case 'oauth2':
					$defaults = array(
						'client_id' => '%s',
						'client_secret' => '%s',
						'redirect_uri' => '%s'
					);
					break;
				
				default:
					$defaults = array();
					break;
			}
			
			$this->options = array_merge($defaults, $options);
			return $this->options;
		}
		
		/**
		 * Get the slug.
		 * 
		 * Currently uses debug_backtrace to the get child class's file path.
		 * There is the php function ReflectionClass but this is only available
		 * in php > 5. The code for stripping the file path is taken directly
		 * from plugin_basename()
		 * 
		 * @return string The current slug 
		 */
		protected function get_slug(){
			
			//get child class filename
			$stacktrace = @debug_backtrace(false);
			$file = $stacktrace[1]['file'];
			
			//taken directly from plugin_basename()
			$file = str_replace('\\','/',$file); // sanitize for Win32 installs
			$file = preg_replace('|/+|','/', $file); // remove any duplicate slash
			$plugin_dir = str_replace('\\','/',WP_PLUGIN_DIR); // sanitize for Win32 installs
			$plugin_dir = preg_replace('|/+|','/', $plugin_dir); // remove any duplicate slash
			$mu_plugin_dir = str_replace('\\','/',WPMU_PLUGIN_DIR); // sanitize for Win32 installs
			$mu_plugin_dir = preg_replace('|/+|','/', $mu_plugin_dir); // remove any duplicate slash
			$file = preg_replace('#^' . preg_quote($plugin_dir, '#') . '/|^' . preg_quote($mu_plugin_dir, '#') . '/#','',$file); // get relative path from plugins dir
			$file = trim($file, '/');
			
			//return slug
			$parts = explode("/", $file);
			return "{$parts[1]}/{$parts[2]}";
		}
	}
	
endif;