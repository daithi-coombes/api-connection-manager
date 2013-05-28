<?php
/**
 * API_Con_Mngr_Module class file
 * @package api-connection-manager
 * @author daithi
 */
require_once('vendor/OAuth.php');

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
	* $module = new My_Class();
	* </code>
	* 
	* Options that are per wordpress installation, such as client_id or scopes
	* should be defined using the API Connection Manager dashboard settings 
	 * page.
	* 
	* Your module class should be saved in a file called <code>index.php</code>
	* in a sub-folder in <code>wp-content/plugins/api-con-mngr-modules</code>
	* If this folder is not created yet make sure you have installed and
	* activated the API Manager Core
	* {@link https://github.com/david-coombes/api-connection-manager}
	* 
	* Class definition file
	* =====================
	* Your class should extend this and also be constructed as $module at the
	* bottom of the index.php file
	* 
	* Oauth1
	* ======
	* As well as providing methods declared abstract the following is required:
	* Methods:
	*	- ::__construct() //this must call parent::__construct()
	*	- ::check_error //check the service responses for error
	*	- ::do_login() //use this to process request tokens
	*   - ::get_uid() //make request to service and return uid
	* Fields:
	*	- ::consumer_key //required by the oauth1 spec
	*	- ::consumer_secret //required by the oauth1 spec
	* 
	* Oauth2
	* ======
	* The following must be declared by your child class
	* Methods:
	*	- ::__constrcut() //must construct parent class
	*	- ::check_error() //check the server responses for errors
	*	- ::get_authorize_url //only the client_id and redirect_url are
	* required by the spec, you may need to add additional params here
	* 
	* Service (provider's custom api)
	* ===============================
	* No service documentation yet
	* 
	* Custom
	* ======
	 Params:
	*  - $this->endpoint string
	*  - $this->login_form array
	*  - $this->login_form_callback()
	* 
	* Callbacks
	* ======
	* Internally the api module class will use an stdClass param as a dto. This
	* will be passed to your callback function. You can define a callback
	* function or method when printing a login for a service:
	* <code>
	* $module->get_login_button( __FILE__, array(&$this, 'parse_dto') );
	* </code>
	* for a method, or if your callback is a function then:
	* <code>
	* $module->get_login_button( __FILE__, array('parse_dto') );
	* </code>
	* The dto will also contain other information such as access tokens and
	* response's from teh provider. The dto is in the format:
	* <code>
	*	res	stdClass Object
	*	(
	*		[callback] => Array
	*			(
	*				[0] => ClassName
	*				[1] => MethodName
	*			)
	*		[response] => Array
	*			(
	*				[login] => true
	*				[slug] => module/index.php
	*				[file] => /path/to/callback/file.php
	*				[callback] => a:2:{i:0;s:12:"ClassName";i:1;s:9:"MethodName";}
	*				[oauth_request_token] => **************
	*				[oauth_request_token_secret] => *************
	*			)
	*		[slug] => module/index.php
	*		[user] => WP_User Object
	*			(
	*				[data] => 
	*				[ID] => 0
	*				[caps] => Array
	*					(
	*					)
	*				[cap_key] => 
	*				[roles] => Array
	*					(
	*					)
	*				[allcaps] => Array
	*					(
	*					)
	*				[filter] => 
	*			)
	*	)
	* </code>
	* 
	* Globals
	* =======
	* There is one global used by the api, this is also used to build the dto
	* above {@see API_Connection_Manager::_service_parse_dto()}
	* <code>
	* $_SESSION	Array
	* (
	*	[API_Con_Mngr_Module] => Array
	*		(
	*			[dropbox/index.php] => Array
	*				(
	*					[callback] => Array
	*						(
	*							[0] => AutoFlow_API
	*							[1] => parse_dto
	*						)
	*				)
	*			[slug] => dropbox/index.php
	*		)
	* )
	* </code>
	* @package api-connection-manager
	* @author daithi
	*/
	abstract class API_Con_Mngr_Module {

		/** @var string Oauth2 access token */
		public $access_token = "";
		
		/** @var string The oauth2 access type parameter */
		public $access_type = "";
	
		/** @var string The img tag to the module button, or module name as
		 * type default*/
		public $button = "";
		
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

		/** @var mxied Flag Default false. If login form required then set as an
		 * array with the necessary param=>vals as key=>pairs.
		 */
		public $login_form = false;
		
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
		
		/**
		 * An array of options for the service.
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
		
		/** @var string The prefix for the user meta keys */
		private $option_name = "API_Con_Mngr_Module";
		
		/**
		 * Make sure you call this from your child class.
		 * 
		 * @global API_Connection_Manager $API_Connection_Manager 
		 */
		function __construct() {
			
			/**
			 * bootstrap fields, params and options
			 */
			//set slug
			$this->slug = $this->get_slug();			
			//load user specific db params (access_tokens etc)
			$this->get_params();
			//setup options variables for the API Services dashboard page
			$this->construct_options( $this->options );
			$this->get_options();
			//load stored options
			//if sessions enabled (default is true)
			$id = session_id();
			if(!$id || $id=="")
				$this->sessions = false;			
			//get button
			$parts = explode("/", $this->slug);
			$button = "/api-con-mngr-modules/{$parts[0]}/button.png";
			if(file_exists(WP_PLUGIN_DIR . $button))
				$this->button = "<img src=\"" . WP_PLUGIN_URL . "{$button}\" alt=\"{$this->Name}\" width=\"80\"/>";
			//end bootstrap
			
			//if oauth1 build oauthConsumer
			if($this->protocol=='oauth1'){
				$this->consumer = new OAuthConsumer($this->oauth_consumer_key, $this->oauth_consumer_secret, $this->callback_url);
				if($this->sha1_method)
					$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
				else $this->sha1_method = new OAuthSignatureMethod_PLAINTEXT();
			}

		}

		/**
		 * This method checks a response from the service for an error and must
		 * be declared by your class.
		 * 
		 * If you find an error in the response return a API_Con_Mngr_Error with the
		 * error string
		 * 
		 * @uses API_Con_Mngr_Error
		 * @param array $response The response in the same format as returned by
		 * the WP_HTTP class.
		 * @return mixed Returns false if no error or API_Con_Mngr_Error if error found
		 */
		abstract public function check_error( array $response );
		
		/**
		 * Makes a request to get an accounts uid. Must return the uid of the
		 * remote service or API_Con_Mngr_Error if none.
		 * @return string 
		 */
		abstract public function get_uid();
		
		/**
		 * Returns the profile for this connected user.
		 * @return mixed Return false if no profile available
		 */
		abstract public function get_profile();
		
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
		 * @return mixed Returns the result of the callback method/function
		 */
		public function do_callback( stdClass $dto ) {
			
			if(!$dto->callback)
				return;
			$callback = $dto->callback;
			
			//load file parse callback
			require_once( $callback['file'] );
			$callback['func'] = unserialize( stripslashes($callback['callback']));
			
			//call a method
			if(is_array($callback['func'])){
				$class = $callback['func'][0];
				$method = $callback['func'][1];
				$obj = new $class();
				$ret = $obj->$method($dto);
			}

			//call a function
			else{
				$func = $callback['func'];
				$ret = $func($dto);
			}
			unset($_SESSION['API_Con_Mngr_Module']['callback']);
			return $ret;
		}

		/**
		 * Builds and signs a request object.
		 * 
		 * Uses the field $this->sha1_method to sign the request which must be
		 * type OAuthSignatureMethod_HMAC_SHA1
		 *
		 * Sets the session nonce.
		 * 
		 * @uses API_Con_Mngr_Module::sha1_method OAuthSignatureMethod_HMAC_SHA1
		 * @param string $url The end point url.
		 * @param string $method Default GET. The http method
		 * @param array $params Array of params in key value pairs
		 * @return OAuthRequest Returns an oauth request object 
		 */
		public function oauth_sign_request( $url, $method='GET', $params=array()){
			
			$token = new OAuthConsumer($this->oauth_token, $this->oauth_token_secret, $this->callback_url);
			if(@$_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce']){
				$params['oauth_nonce'] = $_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce'];
			}
			
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $token, $method, $url, $params);
			$request->sign_request($this->sha1_method, $this->consumer, $token);
			if(!@$_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce']){
				$_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce'] = $request->get_parameter('oauth_nonce');
			}
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

		}
		
		/**
		 * Error handling.
		 * 
		 * Will return a API_Con_Mngr_Error
		 * 
		 * @param string $msg The error message.
		 * @return API_Con_Mngr_Error
		 */
		public function error($msg) {
			return new API_Con_Mngr_Error($msg);
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
					$res = $this->request( $this->url_access_token, "POST", $response, FALSE);
					break;
				
				case 'oauth2':
					//make request for access tokens
					$res = $this->request( $this->url_access_token, "POST", array(
						'grant_type' => 'authorization_code',
						'client_id' => $this->client_id,
						'client_secret' => $this->client_secret,
						'code' => $response['code'],
						'redirect_uri' => $this->redirect_uri
					), false );
					break;
			}
			
			//parse response and set tokens in db
			$res = $this->parse_response($res);
			if(is_wp_error($res)) return $res;
			$tokens = (array) $res;
			$params = $this->set_params($tokens);
			return $tokens;
		}
		
		/**
		 * Returns the authorize url for oauth1 and oauth2
		 * 
		 * Override this method and provide any optional params your service
		 * requires. Only client_id and response_type are required by oauth2
		 * spec.
		 * 
		 * Clears the session nonce
		 * 
		 * @param array $params The request parameters
		 * @return string 
		 */
		public function get_authorize_url( $params=array() ) {
			
			switch($this->protocol){
				
				//oauth1
				case 'oauth1':
					$params = array_merge(array(
						'oauth_consumer_key' => $this->oauth_consumer_key,
						'oauth_token' => $params['oauth_token']		//dropbox uses oauth_token (make sure nobody needs oauth_request_token)
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
		 * Returns the connections array.
		 * The connections array is in the format:
		 * <code>
		 * array[$slug][$user_id] = $profile_id
		 * </code>
		 * @return array Returns an empty array if no connections.
		 */
		public function get_connections(){
			$option_name = "{$this->option_name}-connections";
			//multisite install
			if(is_multisite())
				$connections = get_site_option($option_name, array());
			else{
				$connections = get_option($option_name, array());
			}
			return $connections;
		}
		
		/**
		 * Returns a link to login to this service.
		 * 
		 * @param string $file Optional. Full path to callback file.
		 * @param mixed $callback Optional. Callback func name or array of 
		 * class, method names.
		 * @return string Html anchor
		 */
		public function get_login_button( $file='', $callback=''){
			
			//nonce
			global $API_Connection_Manager;

			//using sessions
			$url = $API_Connection_Manager->redirect_uri . "&login=true&slug=" . urlencode($this->slug);
			
			if(is_array($callback))
				$clbk = serialize(array(
					get_class($callback[0]),
					$callback[1]
				));
			else $clbk = serialize($callback);
			$url .= "&file=" .urlencode($file) . "&callback=" . urlencode($clbk);
			return $url;
		}

		/**
		 * Gets the login form for custom services
		 * 
		 * @todo Create the login form for custom services that require the
		 * username/password to be collected on the client side.
		 * @return boolean 
		 */
		public function get_login_form(){
			
			//if no form params return
			if(!is_array($this->login_form) && !count($this->login_form))
				return false;
			
			//view class
			$view = new API_Con_Mngr_View();
			
			//work out return url
			(!@$this->login_form['endpoint']) ? 
				$url = $this->redirect_uri:
				$url = $this->login_form['endpoint'];
			
			//work out method
			(!@$this->login_form['method']) ?
				$method = "post":
				$method = $this->login_form['method'];
			
			//build and return form
			$view->body[] = "
				<p class=\"lead\">
					Please enter your login details for {$this->Name}
				</p>
				<form method=\"{$method}\" action=\"{$url}\" class=\"form-horizontal\">
					<fieldset>
						<input type=\"hidden\" name=\"slug\" value=\"{$this->slug}\"/>
						<input type=\"hidden\" name=\"login\" value=\"do_login\"/>\n";
			
			//add fields
			foreach($this->login_form['fields'] as $type=>$name)
				$view->body[] .= "
					<div class=\"control-group\">
						<label class=\"control-label\" for=\"api-con-login-{$name}\">
							{$name}</label>
						<div class=\"controls\">
							<input type=\"{$type}\" name=\"{$name}\" id=\"api-con-login-{$name}\" placeholder=\"{$name}\"/>
						</div>
					</div>
					";
			
			//submit btn
			$view->body[] = "
				<div class=\"control-group\">
					<div class=\"controls\">
						<button type=\"submit\" class=\"btn\">Sign In</button>
					</div>
				</div>
			</form>\n";
			
			//return html
			return $view->get_html();
		}
		
		/**
		 * Returns array of params for this module and sets the class fields.
		 * 
		 * @global type $API_Connection_Manager To get the current user_id
		 * @return array $array[key=>val] 
		 */
		public function get_params(){
			
			//$user_id = $this->user->ID;
			global $API_Connection_Manager;
			$user_id = $API_Connection_Manager::get_current_user()->ID;
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
				'token' => null
			));
			
			//unset nonce
			unset($_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce']);
			$_SESSION['API_Con_Mngr_Module']['slug'] = $this->slug;
			
			//make request
			$res = $this->request($this->url_request_token, $method);
			$ret = $this->parse_response($res);
			
			//store tokens and return
			$_SESSION[$this->option_name][$this->slug]['params'] = $ret;
			
			return $ret;
		}

		/**
		 * Log/ a user with this service. Uses $this->slug to see if this
		 * uid for this service has already been connected with the current
		 * logged in wp user.
		 * 
		 * @param string $uid The profile user id to match
		 * @return boolean If wp user logged in, will set connect service uid
		 * with user. If not will look for connection and login if found. If
		 * neither then will return false
		 */
		public function login( $uid ){
			
			$connections = $this->get_connections();
			
			//if logged in user then connect account
			if($this->user->ID)
				return $this->login_connect($this->user->ID, $uid);
			
			//else look for user in connections array and log them in
			else{
				//get list of users for this slug
				$data = @$connections[$this->slug];

				if(count($data))
					foreach($data as $user_id => $service_id)

						if(@$uid==@$service_id){

							//get user
							$user = get_userdata( $user_id );
							if(!$user || (!get_class($user)=="WP_User"))
								continue;

							//login
							wp_set_current_user( $user->data->ID );
							wp_set_auth_cookie( $user->data->ID );
							do_action('wp_login', $user->data->user_login, $user);

							//redirect to admin page
							wp_redirect(admin_url() . "admin.php?page=api-connection-manager-user");
							exit();
						}
			}
			return false;
		}
		
		/**
		 * Override this method if you are using a custom service login form
		 * @see API_Con_Mngr_Module::get_login_form()
		 * @param stdClass $dto The DTO passed from the login form
		 * @return string Must return a valid token/session. This will be then
		 * stored in API_Con_Mngr_Module::token for later use
		 */
		public function login_form_callback( stdClass $dto ){
			;
		}
		
		/**
		 * Set a wp user with this module providers account id.
		 * @param string $user_id The wordpress user id
		 * @param string $uid This module providers account id
		 */
		public function login_connect($user_id, $uid){
			
			$connections = $this->get_connections();

			/**
			 * check if provider account is already in use
			 */
			if(count(@$connections[$this->slug]) && is_array($connections[$this->slug]))
				foreach(@$connections[$this->slug] as $_user_id => $_uid)
					if($_uid==$uid){
						if(get_userdata($_user_id))
							return new API_Con_Mngr_Error("Sorry that profile is already associated with another account");
						else
							unset($connections[$this->slug][$_user_id]);
					}
			
			$connections[$this->slug][$user_id] = (string) $uid;
			$this->set_connections($connections);
			return true;
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
			
			if(is_wp_error($response))
				return $response;
			
			//check error code
			if($response['response']['code']=='500')
				return new API_Con_Mngr_Error($response['response']['message']);

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
		 * @param string $url The full endpoint url.
		 * @param string $method Default GET. The http method to user.
		 * @param array $parameters Optional. An array of parameters in key
		 * value pairs
		 * @param boolean $die Default true. Whether to die with a login button
		 * if an error occurs
		 * @return array Returns the response array in the WP_HTTP format. 
		 */
		public function request($url, $method='GET', $parameters = array(), $die=true) {
			
			global $current_user;
			
			//vars
			$current_user = wp_get_current_user();
			$connections = $this->get_connections();
			$method = strtoupper($method);
			$errs=false;
			
			//make request
			switch ($method) {
				case 'POST':
					$params = array('body'=>$parameters,'headers'=>$this->headers);
					$response = wp_remote_post($url, $params);
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
				
				/**
				 * @deprecated https://github.com/cityindex/labs.cityindex.com/issues/116
				$this->set_params(array(
					'access_token' => false
				));
				if(@$current_user->ID){
					unset($connections[$this->slug][$current_user->ID]);
					$this->set_connections($connections);
				}
				 * 
				 */
				
				$msg = $errs->get_error_message();
				
				//print js to reload calling page
				if($die)
					print "
						<script>
							alert('{$msg}');
							if(window.opener){
								window.opener.location.reload();
								window.close();
							}else
								window.location.href = document.referrer;
						</script>
						";
				else
					print "
						<script>
							alert('{$msg}');
							if(window.opener){
								window.opener.location.href = '{$_SESSION['api-con-mngr-referer']}';
								window.close();
							}else
								window.location.href = '{$_SESSION['api-con-mngr-referer']}';
						</script>";
			}
			
			//return response
			return $response;
		}

		/**
		 * Sets the connections array.
		 * The connections array is in the format:
		 * <code>
		 * array[$slug][$user_id] = $profile_id
		 * </code>
		 * @param array $connections
		 * @return array $connections
		 */
		public function set_connections( $connections ){
			$option_name = "{$this->option_name}-connections";
			if(is_multisite())
				update_site_option($option_name, $connections);
			else{
				update_option($option_name, $connections);
			}
			return $connections;
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
		 * Store site wide options, such as client_id and secret etc.
		 * @param array $options Options to set in key value pairs
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
		 * Set user specific params such as access token etc.
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
			update_user_meta($user_id, $option_name, $meta);
			/**
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
			 * 
			 */
			
			$params = $this->get_params();
			//return params
			return $params;
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
		 * Defaults are consumer key, consumer secret, redirect_uri
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
			(!@$options_db[$this->slug]) ? 
				$options = array():
				$options = $options_db[$this->slug];
			
			//set redirect_uri
			$redirect = admin_url('admin-ajax.php') . "?" . http_build_query(array(
				'action' => 'api_con_mngr'
			));
			
			if(@empty($options['redirect_uri']))
				$options['redirect_uri'] = $redirect;
			if(@empty($options['callback_url']))
				$options['callback_url'] = $redirect;
			//end redirect uri
			
			//set fields and return options
			foreach($options as $key=>$val)
				$this->$key = $val;
			return $options;
		}
		
		/**
		 * Returns the slug for the current module.
		 * @uses ReflectionClass To get the child class filename
		 * @return string
		 */
		private function get_slug(){
			$reflector = new ReflectionClass( $this );
			$path =  dirname($reflector->getFileName());
			$parts = explode(DIRECTORY_SEPARATOR, $path);
			return array_pop($parts) . "/" . "index.php";
		}
	}
	
endif;