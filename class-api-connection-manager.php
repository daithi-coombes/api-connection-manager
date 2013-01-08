<?php
/**
 * class-wp-services-api
 * 
 * This class uses params stored in a module file to connect to remote services.
 * It manages the storing of access_tokens and refresh_tokens in the oauth2
 * spec and also allows for custom service api's to be connected to.
 * 
 * Plugins can then easily connect to these services and make requests.
 * Developers can also easily create new modules with the params for different
 * services.
 * 
 * Currently only one service can be requested at a time. As each service more
 * than likely has its own params, even if two or more services had similar
 * params they would still be individual calls made by the wp_response_*
 * helper functions. Also if more than one service was requested and they
 * required login, it would be unfeasible to have multpile login screens.
 * 
 * Requests made to this api will then check that the user is logged into that
 * service. If they are not and the service is active then the developer should
 * be able to call a login screen for that service. Some services require that
 * a new tab be opened, some require only a popup with a form.
 * 
 * <b>N.B.</b>
 * Although wordpress mixes and jumps between slugs and index files when working
 * with plugins, for this class the index file and the slug are the same and the
 * one. So the slug for a module stored at:
 * api-con-mngr-modules/google-login/index.php
 * the slug would then be:
 * google-login/index.php
 * The reason for this is that the index file is the only definite unique item
 * between different modules. Two developers giving two modules the same slug
 * by accident could cause headaches. Using the index.php file makes this
 * impossible. Don't forget to urlencode() your slugs before using them in
 * forms etc. Also don't forget to urldecode() your slugs when parsing form
 * submits.
 *
 * @todo method for login link, so request, connect and others can die("login");
 * @todo localization
 * @todo refresh token
 * @todo research google's AuthSub or ClientLogin api's. Might have something
 * to help building for custom services.
 * @todo move query_append() from debug.func.php to method in this class
 * @todo look into a sevices datatype/object that plugins would request and work
 * of. Something like WP_Service. WP_Service->get_grant_uri or
 * WP_Service->get_btn
 * @todo create a DTO object to return from calls to the api. The DTO would
 * therefore always be based on a server response. This helps keep with the
 * wordpress norm of accepting strings and returning objects. ie where calls to 
 * get_user* functions return a WP_User object, calls to this api would return a
 * WP_DTO object for the plugin dev's
 * to work with.
 * @todo write up standard for module index.php files. Clean out old vars and
 * allow for more header options. Also allow the placement of standard vars in
 * the values for params. @see this::get_logout_button(). IE have:
 *  - <!--[--token--]-->
 *  - <!--[--token-refresh--]-->
 * or for a value set somewhere else have:
 *  - <!--[--app-grant-vars=>some_child_key--]-->
 * @todo activation/deactivation of services
 * @package api-connection-manager
 * @author daithi
 */
session_start();
class API_Connection_Manager{
	
	/** @var string The last error made */
	public $last_error = "";
	/** @var string The redirect uri */
	public $redirect_uri = "";
	/** @var array List of all installed services */
	public $services = array();
	/** @var string The location of the submodules dir */
	protected $dir_sub = "";
	/** @var string The name of the options var for this api */
	protected $option_name = "api-connection-manager";
	/** @var string The url to the submodules dir */
	protected $url_sub = "";
	/** @var WP_User The current user */
	private $user;
	
	/**
	 * Loads settings and set default params.
	 * 
	 * @see index.php for dependencies idea of code flow.
	 */
	public function __construct(){
		
		/**
		 * dependencies 
		 */
		require_once( "class-api-con-mngr-module.php" ); //module, header and param classes
		// end dependencies
		
		//get current user first
		$this->user = $this->_get_current_user();
		
		//default params
		$this->dir_sub = WP_PLUGIN_DIR . "/api-con-mngr-modules";
		$this->redirect_uri = admin_url('admin-ajax.php') . "?" . http_build_query(array(
			'action' => 'api_con_mngr'
		));
		$this->services = $this->_get_installed_services();
		$this->url_sub = WP_PLUGIN_URL . "/api-con-mngr-modules";
				
		//make sure options array is set
		$options = $this->_get_options();
		if(!@$options['services'])
			$options['services'] = array();
		if(!@$options['active'])
			$options['active'] = array();
		if(!@$options['inactive'])
			$options['inactive'] = array();
		$this->_set_option($options);
		
		/**
		 * actions
		 */
		add_action('plugins_loaded', array(&$this,'_response_listener'));
		
		/**
		 * Check if logout request 
		 */
		if(@$_REQUEST['api-con-mngr-logout'])
			$this->_service_logout( urldecode($_REQUEST['service']) );
		
	} //end construct()
	
	/**
	 * Connects to a service.
	 * 
	 * First checks if user is logged in, if not then returns a WP_Error object
	 * with the service login link as the error message. If service is logged in
	 * will return the service data.
	 * 
	 * @see API_Connection_Manager::get_service_states
	 * @subpackage helper-methods
	 * @param string $slug
	 * @param boolean $die Default true. Whether to die with login link or
	 * return a WP_Error object if service is not connected
	 * @return true|WP_Error
	 */
	public function connect($slug, $die=true){
		
		//vars
		$service = $this->get_service($slug);
		$token = $this->_get_token($slug); //"get token from user meta";
		$link = $this->_print_login($slug, false);
		
		
		/**
		 * check token for oauth2 spec error
		 */
		if($this->_service_get_error($token)){
			$msg = "<b>".$this->_service_get_error($token)."</b>\n";
			$msg .= $link;
			
			if($die)
				die($msg);
			else return new WP_Error ('API_Connection_Manager::connect', $msg);
		}// end oauth2 spec error check
		
		
		/**
		 * Check service for param errors 
		 */
		if(is_wp_error($service['params'])){
			$errs = $service['params']->get_error_messages();
			$msg = "<ul><li>" . implode("</li><li>",$errs[0]) . "</li></ul>";
			if($die)
				die($msg);
			else
				return new WP_Error ('API_Connection_Manager::connect', $msg);
		}
		
		/**
		 * if no token
		 */
		if(!$token){
			if($die)
				die($link);
			else
				return new WP_Error('API_Connection_Manager::connect', $link);
		}// end no token check
		
		
		/**
		 * if WP_Error stored as token 
		 */
		if(is_wp_error($token)){
			$msg = $token->get_error_message();
			$msg .= "<br/>{$link}";
			if($die)
				die($msg);
			else
				return new WP_Error ('API_Connection_Manager::connect', $msg);
		}// end WP_Error as token
		
		
		//return true
		return $service;
	} //end connect()
	
	/**
	 * Delete tokens for a service. Unless specified will delete both refresh
	 * and access token.
	 *
	 * @param string $slug The service slug
	 * @param enum $type all|access|refresh The type of token to delete.
	 * Defaults to 'all'
	 */
	public function delete_token($slug, $type='all'){
		
		$options = $this->_get_user_options();
		$service = $this->get_service($slug);
		
		//delete from user meta
		switch ($type) {
			
			//delete access token
			case 'access':
				if(@$options[$slug]['access'])
					unset($options[$slug]['access']);
				break;
			
			//delete refresh token
			case 'refresh':
				if(@$options[$slug]['refresh'])
					unset($options[$slug]['refresh']);
				break;

			//default delete both
			default:
				if(@$options[$slug]['access'])
					unset($options[$slug]['access']);
				if(@$options[$slug]['refresh'])
					unset($options[$slug]['refresh']);
				break;
		}
		
		//revoke using service
		if(@$service['params']['revoke-uri']){
			$vars = $service['params']['revoke-vars'];
			
			//build vars
			foreach($service['params']['revoke-vars'] as $key=>$val){
				//access token
				if(preg_match("/<\!--\[--token-access--\]-->/", $val, $matches))
					$vars[$key] = $this->_get_token($slug);
			}
			
			//make request
			if("get"==$service['params']['revoke-method'])
				$uri = $this->_url_query_append ($service['params']['revoke-uri'], $vars);
				$res = wp_remote_get($uri);
		}
		
		//save user meta
		update_user_meta($this->user->ID, $this->option_name, $options);
	}
	
	/**
	 * Return a list of connected services.
	 * 
	 * Returns an array of service connect states. If a service is connected
	 * with the currently logged in user then it will return $slug=>array if
	 * the service is not connected then it will return $slug=>WP_Error. You can
	 * access the service login link by then calling:
	 * $slug=>WP_Error::get_error_message()
	 * 
	 * @uses API_Connection_Manager::connect()
	 * @param string $type Default 'active'. Whether to check active or inactive
	 * services.
	 * @return array
	 */
	public function get_service_states($type='active'){
		
		$res = array();
		
		foreach($this->services[$type] as $slug=>$data)
			$res[$slug] = $this->connect($slug, false);
		
		return $res;
	}
	
	/**
	 * Get the current user the api class is working off.
	 * @return WP_User
	 */
	public function get_current_user(){
		return $this->user;
	}
	
	/**
	 * Returns the html for a module's login link.
	 * 
	 * This is a possible helper function that will return the button or html
	 * for an oauth2 token url.
	 * 
	 * @deprecated use API_Con_Mngr_Module::get_login_button()
	 * @todo remove this method when modules are all oop
	 * @param string $slug The index filename of the sub-module.
	 * @param string $file The location of the file to run the callback from
	 * @param mixed $callback Callback function in same string|array format as
	 * used by add_action.
	 * @return string Returns the html.
	 * @subpackage helper-methods
	 */
	public function get_login_button( $slug, $file, $callback ) {
				
		//vars
		$html = "<a href='";
		$path = explode( "/", $slug );
		$module_folder = $path[ 0 ];
		//( @ $oauth2 ) ? $module = $oauth2 : $module = $service;
		$options = $this->_get_options();
		$options = $options['services'];
		$slug = trim($slug);	//clean slug just in case ;)
		$service = $this->get_service($slug);
		
		/**
		 * If module is object
		 */
		if(get_parent_class($service)=='API_Con_Mngr_Module'){
			
			$html .= "{$service->login_uri}'>";
			$html .= "{$service->Name}\n";
		} // end module is object
		
		/**
		 * If module is array
		 */
		else{
			if(is_wp_error($service['params']))
				return "ERROR: " . $service['params']->get_error_message();

			//add uri
			if(@$service['params']['grant-uri'])
				$html .= "{$service['params']['grant-uri']}'>\n";
			else $html .= "#'>\n";

			/**
			* Set callback 
			*/
			if(@$service['params']['grant-vars']['state'])
				$callbacks = $this->_set_callback($file, $callback, $service['params']['grant-vars']['state']);

			/**
			* oauth2 link button image or text?
			*/
			if ( @$service['params'][ 'button-image' ] )
				$html .= "<img src='{$this->url_sub}/{$module_folder}/{$service['params']['button-image']}'
					border='0' alt='{$service['params']['button-text']}'/>";
			else
				$html .= $service['params'][ 'button-text' ];

			//close link and return
			$html .= "</a>\n";
		} // end module is array
		
		return $html;
	} //end get_login_button()
	
	/**
	 * Get the login out link for a servie.
	 * 
	 * @todo finish the logout params in the module index.php file
	 * @param string $slug The service slug.
	 * @return string 
	 */
	public function get_logout_button( $slug ){
		
		$service = $this->get_service($slug);
		return "<em>please logout using your {$service['Name']} account";
		
		//vars
		$codes = array(
			'token' => $this->_get_token($slug)
		);
		$logout_uri = $this->_url_query_append($this->_get_current_url(), array(
				'api-con-mngr-logout' => true,
				'service' => urlencode($slug)
			));
		$logout_link = "<a href=\"{$logout_uri}\">LogOut</a>\n";
		$service = $this->get_service($slug);
		$params = $service['params'];
		
		/**
		 * Print logout link 
		 */
		if(!@$_REQUEST['api-con-mngr-logout']){
			if(!@$params['app-token-revoke'])
				return "<em>please logout using your {$service['Name']} account";
			return $logout_link;
		}
		
	}
	
	/**
	 * Returns the details for a service.
	 * 
	 * Returns module details, service params and service options.
	 * 
	 * @param string $slug The service index_file
	 * @return array
	 * @subpackage helper-methods
	 */
	public function get_service( $slug ){
		
		//look in active
		foreach($this->services['active'] as $index_file => $service)
			if($index_file==$slug)
				return $service;
		
		//look in inactive
		foreach($this->services['inactive'] as $index_file => $service){
			if($index_file==$slug)
				return $service;
		}
	} //end get_service()
	
	/**
	 * Returns a list of services.
	 * 
	 * This is the main method for plugins to get services from.
	 * 
	 * @todo change default to active.
	 * @param string $type The type of services active|inactive Default active.
	 * @return array
	 * @subpackage helper-methods
	 */
	public function get_services( $type='active' ){		
		return $this->services[$type];
	}
	
	/**
	 * Makes a request to a service.
	 * 
	 * Uses the native wordpress wp_response_* wrappers for the WP_HTTP class to
	 * make requests. Any variables that are need across different service
	 * either come from the params in the module file or are set using the API
	 * settings page. Defaults to using the 'post' method unless $req['method']
	 * is set to 'get'.
	 * 
	 * The $req array takes the same format as the WP_HTTP methods:
	 * $req = array(
	 *	'uri'		//required
	 *  'method'	//defaults to post
	 *  'body'	//params to send for both GET and POST requests. If the access
	 * token is needed here you must define it.
	 *  'headers'
	 * );
	 * 
	 * @param string $slug The service slug to connect to.
	 * @param array $req The request array
	 * @return response|$this->_print_link() Returns the response in the same 
	 * format as WP_HTTP class or dies(login_link) if error
	 * @subpackage helper-methods
	 */
	public function request( $slug, $req, $headers=null ){	
		
		//vars
		if(!@$req['method']) $req['method'] = 'post';
		if(!@$req['body']) $req['body'] = array();
		if(!@$req['headers']) $req['headers'] = array();
		$body = $req['body'];
		$service = $this->get_service($slug);
		
		//add token to body if needed
		if(@$req['body']['access_token']===true){
			$body['access_token'] = $this->_get_token($slug);
		}
		
		//add token to uri if needed
		if(preg_match("/<\!--\[--access-token--\]-->/", $req['uri'], $matches))
			$req['uri'] = str_replace("<!--[--access-token--]-->", $this->_get_token($slug), $req['uri']);
		
		//if token passed, then store it
		if(is_string(@$req['access_token'])){
			$this->_set_token($slug, $req['access_token']);
		}
		
		//add token to header if required
		if(is_array($req['headers']))
			foreach($req['headers'] as $key=>$val)
				if(preg_match("/<\!--\[--access-token--\]-->/", $val, $matches))
					$req['headers'][$key] = str_replace("<!--[--access-token--]-->", $this->_get_token($slug), $val);
		
		//GET request
		if("get"==strtolower($req['method'])){
			if(is_array($body))
				$req['uri'] = trim($req['uri'], "/");
				$url = $this->_url_query_append($req['uri'], $body);
			$res = wp_remote_get($url, array(
				'headers'=> $req['headers']
			));
		}
		
		//POST request
		else{
			$url = $this->_url_query_append($req['uri'], $body);
			$res = wp_remote_post($url, array(
				'headers'=>$req['headers'],
				'body' => $body
			));
		}
		$ret = json_decode($res['body']);
		
		//check response http code
		if($res['response']['code']=='401')
			$this->_print_login($service['slug']);
		
		//if error returned
		if($this->_service_get_error($ret)){
			print "<b>".$this->_service_get_error($ret)."</b><br/>\n";
			$this->_print_login($service['slug']);
		}
		
		return $res;
	}
	
	public function set_user_token( $slug, $token, $type='access', $user=null){
		$this->_set_token($slug, $token, $type, $user);
	}
	
	/**
	 * Check if sub-modules directory exists.
	 * 
	 * If the directory doesn't exist tries to create it using the wordpress
	 * Filesystem_API class. As the ftp login will be displayed this method
	 * should be called from the dashboard/network admin settings page.
	 *
	 * @global WP_Filesystem $wp_filesystem The wordpress filesystem class
	 * @return boolean
	 * @throws Exception If the sub-modules directory can't be created.
	 * @subpackage api-core
	 */
	public function _check_modules_dir() {

		//vars
		$http = 'http';
		if ( @$_SERVER["HTTPS"] == "on" )
			$http .= "s";
		$redirect_url = $http . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

		//check if modules dir exists
		if ( file_exists( $this->dir_sub ) )
			return true;

		//check if we have permissions
		if ( false === ( $creds = request_filesystem_credentials( $redirect_url, '', false, false ) ) )
			return false;
		
		//check credentials
		if ( !WP_Filesystem( $creds ) ) {
			request_filesystem_credentials($url, $method, true, false, $form_fields);
			return false;
		}

		//create sub-modules dir, check and error report
		global $wp_filesystem;
		$wp_filesystem->mkdir($this->dir_sub);
		if ( !file_exists( $this->dir_sub ) )
			throw new Exception("Unable to create sub modules directory: {$this->dir_sub}");
		return true;
	}
	
	/**
	 * Prints a login link for a service to the screen and dies
	 * 
	 * @todo remove this method. Been moved to API_Con_Mngr_Module
	 * @param string $slug The service slug
	 * @param boolean $die Default true. Return the login link or die()
	 */
	private function _print_login($slug, $die=true){
		
		//vars
		$service = $this->get_service($slug);
		$login_link = "
						You are not signed into {$service['Name']}<br/>
						<a href=\"{$service['params']['grant-uri']}\" target=\"new\">Sign In</a>
						";
		
		if($die)
			die($login_link);
		else return $login_link;
	}
	
	/**
	 * Returns a WP_Error object.
	 * 
	 * Sets the error code to 'API Connection Manager' and returns a constructed 
	 * WP_Error object. Sets the last_error param to error message.
	 * 
	 * @uses API_Connection_Manager::last_error
	 * @param string $msg The error message
	 * @return WP_Error 
	 * @subpackage api-core
	 */
	private function _error($msg){
		
		//if array of errors format to &gt;li> list
		if(is_array($msg))
			$msg = "<ul><li>\n" . implode("</li><li>", $msg) . "</li>\n</ul>\n";
		
		$this->last_error = $msg;
		return new WP_Error('API Connection Manager', $msg);
	}
	
	/**
	 * Get the current url including scheme, host and query args
	 * 
	 * @return string
	 */
	private function _get_current_url(){
		$http = 'http';
		if($_SERVER["HTTPS"] == "on")
			$http .= "s";
		return $http."://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	
	/**
	 * Get current user.
	 * 
	 * Get this as early as possible for some calls.
	 * @return WP_User if no user then returns WP_User(0) else will return
	 * current users WP_User object.
	 */
	public function _get_current_user(){
		require_once( ABSPATH . "/wp-includes/pluggable.php" );
		wp_cookie_constants();
		$user_id = wp_validate_auth_cookie();
		if($user_id)
			return get_user_by("id", $user_id);
		else
			return new WP_User(0);
		//end Get current user		
	}
	
	/**
	 * Builds an array of all installed services and their params.
	 * 
	 * The return array is split into active and inactive services. There are a
	 * lot of system calls and working with files. Overuse of this method would
	 * not be advised.
	 * 
	 * N.B. if a module requires session and it is not enabled then that module
	 * will always be set as inactive.
	 * 
	 * Currently this method is used in:
	 *  - API_Connection_Manager::__construct()
	 *  - API_Connection_Manager::get_services //only if no services loaded yet
	 * 
	 * @todo add error message is sessions disabled and module needs it
	 * @todo use API_Con_Mngr_Module for all services
	 * @param boolean Default false. Whether to include downloadable modules.
	 * @return array 
	 * @subpackage api-core
	 */
	private function _get_installed_services(){
		
		require_once( ABSPATH . "/wp-admin/includes/plugin.php" );
		$wp_plugins = array();
		$plugin_root = $this->dir_sub;
		
		/**
		 * Get list of plugin index files 
		 */
		$plugins_dir = @ opendir( $plugin_root );
		$plugin_files = array();
		if ( $plugins_dir ) {
			while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
				if ( substr( $file, 0, 1 ) == '.' )
					continue;
				if ( is_dir( $plugin_root . '/' . $file ) ) {
					$plugins_subdir = @ opendir( $plugin_root . '/' . $file );
					if ( $plugins_subdir ) {
						while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
							if ( substr( $subfile, 0, 1 ) == '.' )
								continue;
							if ( substr($subfile, -4) == '.php' )
								$plugin_files[] = "$file/$subfile";
						}
						closedir($plugins_subdir);
					}
				} else {
					if ( substr($file, -4) == '.php' )
						$plugin_files[] = $file;
				}
			}
			closedir($plugins_dir);
		} //end list of plugin files
		
		if ( empty($plugin_files) )
			return array(
				'active' => array(),
				'inactive' => array()
			);
		
		/**
		 * Build up array of plugin[data]
		 */
		foreach ( (array) $plugin_files as $plugin_file ) {
			
			if ( !is_readable("$plugin_root/$plugin_file") )
				continue;

			$plugin_data = get_plugin_data("$plugin_root/$plugin_file", false, false); //Do not apply markup/translate as it'll be cached.
			
			//get slug
			if( @empty( $plugin_data['slug'] ) )
				$plugin_data['slug'] = $plugin_file;
			
			if ( empty($plugin_data['Name']) )
				continue;

			$wp_plugins[plugin_basename($plugin_file)] = $plugin_data;
			
			/**
			 * Use array (will be deprecated)
			 */
			//get params and options
			$params = $this->_get_module( plugin_basename($plugin_file) );
			$options = $this->_get_service_options( plugin_basename($plugin_file) );
			
			$wp_plugins[plugin_basename($plugin_file)]['params'] = $params;
			$wp_plugins[plugin_basename($plugin_file)]['options'] = $options;
			//end array
			
			/**
			 * Use API_Con_Mngr_Module 
			 */
			if(isset($oauth1)) unset($oauth1);
			include("{$plugin_root}/{$plugin_file}");
			if(isset($oauth1)){
				$module = $this->_get_module( plugin_basename($plugin_file) );
				$module->protocol = 'oauth1';
				$module->set_details($plugin_data);
				$wp_plugins[plugin_basename($plugin_file)] = $module; //new API_Con_Mngr_Module($params, $options);
			}
			//end use API_Con_Mngr_Module
			
		}//end build of plugin[data]
		
		//build return array
		$res = array(
			'active' => array(),
			'inactive' => $wp_plugins
		);
		
		//get active/inactive service slugs
		$api_options = $this->_get_options();
		(is_array(@$api_options['active'])) ?
			$active = $api_options['active'] :
			$active = array();
		(is_array(@$api_options['active'])) ?
			$inactive = $api_options['inactive'] :
			$inactive = array();
		
		//remove active plugins from list
		foreach($active as $slug){
			if(array_key_exists($slug, $wp_plugins)){
				$res['active'][$slug] = $wp_plugins[$slug];
				unset($res['inactive'][$slug]);
			}
			
			//disable if sessions required
		}
		
		//return result
		return $res;
	}
	
	/**
	 * Get options.
	 * 
	 * If multisite install will take options from get_site_option() if not then
	 * defaults to get_option()
	 * 
	 * @todo this method is being called too much
	 * @return array 
	 * @subpackage api-core
	 */
	private function _get_options(){
		
		//multisite install
		if(is_multisite())
			$options = get_site_option($this->option_name, array());
		else
			$options = get_option($this->option_name, array());
		
		return $options;
	}
	
	/**
	 * Returns transient options.
	 * 
	 * If mutlisite then will use get_site_transient() if not will default to
	 * get_transient()
	 * 
	 * @param string $key The option key.
	 * @return array 
	 * @subpackage api-core
	 */
	private function _get_options_transient( $key='' ){
		
		//multisite install
		if(is_multisite())
			return get_site_transient($this->option_name."$key", array());
		else
			return get_transient($this->option_name."$key", array());
	}
	
	/**
	 * Loads a services module file (index.php) into the current namespace and
	 * returns the params for a service. 
	 * 
	 * Sets the grant parameters here in order to build the grant uri but does 
	 * not set the token parameters. For this see:
	 * @see API_Connection_Manager::_service_oauth2_login()
	 * 
	 * If there is an error including the file a WP_Error is returned. Returns
	 * params on success.
	 *
	 * @param string $slug The sevice slug
	 * @return array|WP_Error Returns error if file can't be loaded or options
	 * for service aren't set.
	 * @subpackage api-core
	 */
	private function _get_module($slug){
		
		//vars
		$errs = array();
		$user = $this->user->ID; //wp_validate_auth_cookie();
		
		//reset module vars
		if(isset($oauth1))
			unset($oauth1);
		if(isset($oauth2))
			unset($oauth2);
		if(isset($service))
			unset($service);
		
		//try loading file to get current module var
		require_once('class-api-con-mngr-module.php');
		if(!include($this->dir_sub . "/" . $slug))
			return $this->_error("No module file for serivce {$slug}");
			
		//check var is available (& add slug)
		if(isset($oauth1)) $oauth1->slug = $slug;
		elseif(isset($oauth2)) $oauth2['slug'] = $slug;
		elseif(isset($service)) $service['slug'] = $slug;
		else return $this->_error("No params set for service {$slug}");
		
		
		/**
		 * Parse Oauth1 data 
		 */
		if(@$oauth1){
			
			//build login uri
			if(!$oauth1->login_uri)
				$oauth1->login_uri = admin_url('admin-ajax.php') ."?". http_build_query(array(
					'action' => 'api_con_mngr',
					'slug' => urlencode($slug)
				));
			
			//set the access token
			$tokens = $this->_get_user_options();
			if(@$tokens[$slug]['access'])
				$oauth1->oauth_token = $tokens[$slug]['access'];
			
			//return oauth object
			return $oauth1;
		} // end parse Oauth1 data
		
		
		/**
		 * Parse Oauth2 data 
		 */
		if(@$oauth2){
			
			//get options
			$options = $this->_get_service_options($slug);
			$user_options = $this->_get_user_options();
			
			if(is_wp_error($options))
				return $this->_error("Unable to load options for {$slug}");
				
			$uri = $oauth2['grant-uri'];
			
			/**
			 * Create our own nonces here so service params can be constructed
			 * as early as possible.
			 * @see wp_create_nonce()
			 */
			$i = wp_nonce_tick();
			$nonce = substr(wp_hash($i . $slug . $user, 'nonce'), -12, 10);
			/**
			 * End nonce 
			 */
			
			/**
			 * @todo remove when modules all object. Now in module::get_login_button() 
			 */
			//set the state {$nonce}-{$slug}-{$userID}
			$oauth2[ 'grant-vars' ][ 'state' ] = serialize(array( 
				$nonce,
				urlencode( $slug ),
				$user
			));
			
			//check options for grant-var values
			if(@$options['grant-vars'])
				$oauth2['grant-vars'] = array_merge($oauth2['grant-vars'], $options['grant-vars']);
			
			//look for offline grant (if user has allowed offline)
			//if no user logged in, then default to look for offline params
			if(
				@$user_options[$slug]['refresh_on'] ||
				!$this->user->ID
			){
				if(@$oauth2['offline-token'])
					$oauth2['grant-vars'] = array_merge($oauth2['grant-vars'], $oauth2['offline-token']);
			}
					
			//add params from code values
			foreach($oauth2['grant-vars'] as $key=>$code){
				//if(preg_match("/<\!--\[--grant-(.+)--\]-->/", $code, $matches))
					//$oauth2['grant-vars'][$key] = $oauth2['grant-vars'][$matches[1]];
				//add redirect uri to grant vars
				if(preg_match("/<\!--\[--redirect-uri--\]-->/", $code, $matches))
					$oauth2['grant-vars'][$key] = $this->redirect_uri;
			}
				
			//check options for token-var values
			if(@$options['token-vars'])
				$oauth2['token-vars'] = array_merge($oauth2['token-vars'], $options['token-vars']);
			
			//add params from grant vars to token (if necessary)
			if(count($oauth2['token-vars']))
				foreach($oauth2['token-vars'] as $key=>$code){
					if(preg_match("/<\!--\[--grant-(.+)--\]-->/", $code, $matches))
						$oauth2['token-vars'][$key] = $oauth2['grant-vars'][$matches[1]];
					//add redirect uri to token vars
					if(preg_match("/<\!--\[--redirect-uri--\]-->/", $code, $matches))
						$oauth2['token-vars'][$key] = $this->redirect_uri;
				}
			
			//build the uri
			$query = http_build_query($oauth2['grant-vars']);
			$uri = $uri."?".$query;
			$oauth2['grant-uri'] = $uri;
			
			return $oauth2;
		} // end parse Oauth2 data
		
		//default return WP_Error
		return $this->_error("No service data found for {$slug}");
	}
	
	/**
	 * Gets the refresh states.
	 * 
	 * @todo maybe method shoudl be renamed _get_refresh_states() ?
	 * @see _set_refresh_state()
	 * @uses get_user_user_meta()
	 * @return array An array of states.
	 */
	public function _get_user_options(){
		
		//vars
		$user_id = $this->user->ID;
		$user_options = get_user_meta($user_id, $this->option_name, true);
		if(empty($user_options)) $user_options=array();
		
		return $user_options;
	}
	
	/**
	 * Returns the options for a service.
	 * 
	 * @param string $slug The services module's index file.
	 * @return array 
	 * @subpackage api-core
	 */
	public function _get_service_options( $slug ){
		
		$options = $this->_get_options();
		
		if(!@$options['services'][$slug])
			return array();
		else return $options['services'][$slug];
	}
	
	/**
	 * Returns the access_token for the current user for a service.
	 * 
	 * @param string $slug The service slug
	 * @param string $type Default access. Whether to return refresh or access
	 * tokens.
	 * @return string|WP_Error Returns the token or WP_Error if none found.
	 * @subpackage api-core
	 */
	public function _get_token( $slug, $type='access' ){
		
		//vars
		$user_id = $this->user->ID;
		$user_options = get_user_meta($user_id, $this->option_name, true);
		$err_msg = "No access|refresh token found for user in service {$slug}";
		if(!$user_options)	//needed to stop wordpress wsod
			return $this->_error($err_msg);
		
		//look for service
		if(
			!@$user_options[$slug]['access'] &&
			!@$user_options[$slug]['refresh']
		) return $this->_error($err_msg);
		
		//return refresh token?
		return $user_options[$slug][$type];
	}
	
	/**
	 * Activate modules.
	 * 
	 * @param string|array $slugs A string or array of slugs.
	 * @subpackage modules-method
	 */
	public function _module_activate( $slugs ){
		
		//make sure its an array we're dealing with
		if(!is_array($slugs))
			$slugs = array($slugs);
		
		//vars
		$options = $this->_get_options();
		$services = $this->services;
		$all = array_merge($services['active'], $services['inactive']);
		$active = $options['active'];
		$inactive = $options['inactive'];
		
		//remove slugs from inactive array
		foreach($inactive as $key => $slug)
			if(in_array($slug, $slugs))
				unset($inactive[$key]);
		
		//add slugs to active array
		foreach($slugs as $slug)
			if(!in_array($slug, $active))
				$active[] = $slug;
		
		//re-order services
		$this->services['active'] = array();
		$this->services['inactive'] = array();
		foreach($all as $slug => $data)
			if(in_array($slug, $active, false))
				$this->services['active'][$slug] = $data;
			elseif(in_array($slug, $inactive))
				$this->services['inactive'][$slug] = $data;
			
		$options['active'] = $active;
		$options['inactive'] = $inactive;
		
		$options = $this->_set_option($options);
	}
	
	/**
	 * Deactivate modules.
	 * 
	 * @param string|array $slugs A string or array of slugs.
	 * @subpackage modules-method
	 */
	public function _module_deactivate( $slugs ){
		
		//make sure its an array we're dealing with
		if(!is_array($slugs))
			$slugs = array($slugs);
		
		//vars
		$options = $this->_get_options();
		$services = $this->services;
		$all = array_merge($services['active'], $services['inactive']);
		$active = $options['active'];
		$inactive = $options['inactive'];
		
		//remove slugs from active array
		foreach($active as $key => $slug)
			if(in_array($slug, $slugs))
				unset($active[$key]);
		
		//add slugs to inactive array
		foreach($slugs as $slug)
			if(!in_array($slug, $inactive))
				$inactive[] = $slug;
		
		//re-order services
		$this->services['active'] = array();
		$this->services['inactive'] = array();
		foreach($all as $slug => $data)
			if(in_array($slug, $active, false))
				$this->services['active'][$slug] = $data;
			elseif(in_array($slug, $inactive))
				$this->services['inactive'][$slug] = $data;
			
		$options['active'] = $active;
		$options['inactive'] = $inactive;
		
		$options = $this->_set_option($options);
	}
	
	/**
	 * Ajax callback
	 * 
	 * Bootstraps all response's to the api connection manager. Both secure and
	 * non-secure response are parsed here (wp_ajax && wp_ajax_nopriv).
	 * 
	 * Called by hook 'plugins_loaded' to ensure all plugins dependant on the
	 * api are loaded. This method needs to do its own checking for ajax calls.
	 * 
	 * @todo test security.
	 * @todo There maybe need to tell if a response was for a logged in user or 
	 * not. ie if it was wp_ajax or wp_ajax_nopriv. One idea would be to 
	 * set a flag in the construct if wp_ajax_nopriv_(this_func) was requested?
	 * @todo should be private?
	 * @subpackage api-core
	 */
	public function _response_listener( ){
		
		/**
		 * Make sure its an ajax call for the api connection manager
		 */
		if(
			!defined('DOING_AJAX') || 
			true!==@DOING_AJAX ||
			@$_GET['action']!='api_con_mngr'
		) return;
		
		//get dto
		$dto = $this->_service_parse_dto( $_GET );
		if(is_wp_error($dto))
			die( $dto->get_error_message() );
		
		//get module from slug
		(@$dto->response['slug']) ?
			$slug = $dto->response['slug'] :
			$slug = $_SESSION['api-con-module'];
		$module = $this->get_service($slug);
		$module->parse_dto($dto);
		
		
		//if oauth1
		if($module->protocol == 'oauth1'){
			
			/**
			 * Get the access_token 
			 */
			if(@$dto->response['oauth_token']){
				
				/**
				 * use request token to get access token 
				 *
				$parameters = array();
				$parameters['oauth_verifier'] = $dto->response['oauth_verifier'];
				$request = $module->request( $module->url_access_token, 'GET', $parameters);
				$token = OAuthUtil::parse_parameters($request['body']);
				$module->set_params( $token );
				*/
				//end get access token

				//if callback
				if(!$this->user->ID || ($this->user->ID==0))
					$module->do_callback( $dto );
				
				//helper method module can override to add actions to login
				//success
				$module->do_login( $dto );
			}
			// end saving the access token
			
			/**
			 * Get authorize url 
			 */
			else{
				
				//get and set tokens
				$tokens = $module->get_request_token();
				$url = $module->get_authorize_url( $tokens );
				$_SESSION['api-con-module'] = $dto->response['slug'];
				$module->set_params(array(
					'oauth_token' => $tokens['oauth_token'],
					'oauth_token_secret' => $tokens['oauth_token_secret']
				));
				
				//if nonce in request then set it as session var
				if(@$_REQUEST['nonce']){
					$_SESSION['callback'] = $_SESSION['callbacks'][stripslashes($_REQUEST['nonce']) ];
					unset($_SESSION['callbacks'][ stripslashes($_REQUEST['nonce'])]);
				}
				
				//redirect to url and exit
				header("Location: {$url}");
				exit;
			}
			// end get autorize url
		}
		
		/**
		 * if oauth2 get token
		 * @todo remove array module code
		 */
		elseif(@$dto->response['code']){
			
			$tokens = $this->_service_get_token($dto);
			if(is_wp_error($tokens))
				die("Error: " . $tokens->get_error_message());
			$dto->access_token = $tokens->access_token;
			if(@$tokens->refresh_token)
				$dto->refresh_token = $tokens->refresh_token;
			
			$this->_service_do_callback($dto->response['state'], $dto);
		}
		
		//default print js
		?>
		<script type="text/javascript">
			if(window.opener){
				window.opener.location.reload();
				window.close();
			}
		</script>
		<?php
		
		//end ajax call
		die();
	}
	
	/**
	 * Enable|Disable the use of a refresh token.
	 * 
	 * Wordpress users can enable or disable the ability for oauth2 services to
	 * store a refresh token.
	 * 
	 * @param string|array $slugs The service slug or an array of slugs. These
	 * should be in the format:
	 * $slugs[$slug] = boolean
	 * @return void
	 */
	public function _set_refresh_state( array $slugs ){
		
		//vars
		$user_id = $this->user->ID;
		$user_options = get_user_meta($user_id, $this->option_name, true);
		
		foreach($slugs as $slug => $state)
			$user_options[$slug]['refresh_on'] = $state;
		
		update_user_meta($user_id, $this->option_name, $user_options);
		
	}
	
	/**
	 * Append params to a url.
	 * 
	 * @param string $url The full url including current params.
	 * @param array $vars Associative array of param name=>value pairs.
	 * @return string 
	 */
	private function _url_query_append( $url, $vars=array()){
		
		//vars
		$url = parse_url($url);
		$query_vars = array();
		parse_str(@$url['query'], $query_vars);
		$query_vars = array_merge($query_vars, $vars);
		//build and return new string
		return "{$url['scheme']}://{$url['host']}{$url['path']}"
			. "?" . http_build_query($query_vars);
	}

	
	/**
	 * Calls a callback, if one is set.
	 * 
	 * Checks the state variable in a dto against callbacks stored to get the
	 * file and function|class to call. The dto is passed to the callback.
	 *
	 * @see API_Connection_Manager::_service_parse_dto()
	 * @param string $unique The state variable to test agains
	 * @param stdClass $dto The dto response
	 * @return void
	 * @subpackage service-method
	 */
	private function _service_do_callback( $unique, $dto ){
		
		//get callbacks
		$callbacks = $this->_get_options_transient("-callbacks");
		
		//loop through them
		foreach($callbacks as $data){
			$unique = stripslashes( urldecode($unique));
			$nonce = stripslashes( urldecode($data['nonce']));

			//if this matches state
			if($nonce==$unique){

				//load file
				if(!file_exists($data['file'])) continue;
				require_once( $data['file'] );

				//call a method
				if(is_array($data['func'])){
					$class = $data['func'][0];
					$method = $data['func'][1];
					$obj = new $class();
					$obj->$method($dto);
					break;
				}

				//call a function
				$callback = $data['func'];
				$callback($dto);
				break;
			}
		}
	}
	
	/**
	 * Makes request to cancel a token.
	 * 
	 * @todo finish this.
	 * @param string $slug The service slug
	 */
	private function _service_logout($slug){
			
		//vars
		$service = $this->get_service($slug);
		$params = $service['params'];
		$uri = $params['app-token-revoke']['uri'];
		$headers = $params['token-revoke-headers'];
		$codes = array(
			'token' => $this->_get_token($slug)
		);
		
		//build up header values
		foreach($headers as $key=>$value)
			foreach($codes as $code=>$val)
				if(!is_wp_error($val)){
					$headers[$key] = str_replace("<!--[--{$code}--]-->", $val, $headers[$key]);
				}
		
		$res = wp_remote_get($uri, array(
			'headers' => $headers
		));
	}
	
	/**
	 * Test if response is oauth2 error.
	 * 
	 * @param mixed $response The server response body.
	 * @return string|false Returns the error message if found, or false if no
	 * error.
	 */
	private function _service_get_error( $response ){
		
		if(@$response->error->message)
			return $response->error->message;
		else
			return false;
	}
	
	/**
	 * Takes a dto object returned from an oauth2 grant request and
	 * requests a token.
	 * 
	 * Uses the params set in the services modules index file to request a
	 * token.
	 * 
	 * Will return WP_Error on failure.
	 *
	 * @todo allow refresh tokens
	 * @param stdClass $dto a dto object returned by $this->parse_dto()
	 * @return string|WP_Error returns the access token or WP_Error
	 * @subpackage service-method
	 */
	private function _service_get_token( $dto ){

		//get service
		$service = $this->get_service($dto->slug);
		$options = $service['options']; //$this->_get_service_options($dto->slug);
		$user_options = $this->_get_user_options();
		$params = $service['params'];
		
		/**
		 * Offline refresh tokens 
		 */
		if(
			@$user_options[$dto->slug]['refresh_on'] &&
			@$user_options[$dto->slug]['refresh'] &&
			@$options[$dto->slug]['refresh']
		){
			
			$uri = $params['offline-uri'];
			$req = $params['offline-vars'];
			
			//build request
			foreach($params['offline-vars'] as $key=>$code){
				//grant defined vars
				if(preg_match("/<\!--\[--grant-(.+)--\]-->/", $code, $matches))
					$req[$key] = $options['grant-vars'][$matches[1]];
				if(preg_match("/<\!--\[--refresh-token--\]-->/", $code, $matches))
					$req[$key] = $user_options[$dto->slug]['refresh'];
				if(preg_match("/<\!--\[--token-(.+)--\]-->/", $code, $matches))
					$req[$key] = $options['token-vars'][$matches[1]];
			}// end build request
			
			
			//snd request
			if(strtolower($params['offline-method'])=='post')
				$res = wp_remote_post($uri, $req);
			else
				$res = wp_remote_get($uri, $req);
			
			//parse response
			if("json"==strtolower(@$params['offline-datatype']))
				$res = json_decode($res['body']);
			else{
				parse_str($res['body']);
				if(isset($error))
					return $this->_error($error);
				$res = new stdClass();
				$res->access_token = $access_token;
				if(@$refresh_token)
					$res->refresh_token = $refresh_token;
			}
			
		} //end Offline refresh tokens
		
		
		/**
		 * Grant access tokens
		 */
		else{
			//error check we have params to send
			if(!count(@$params['token-vars']))
				return $this->_error("No token parameters set in module file for {$dto->slug}");

			//build up requst for token
			(@$params['token-vars']) ? 
				$req = $params['token-vars'] :
				$req = array();
			$req['code'] = $dto->response['code'];

			//get vars from options
			if(@$params['token-vars']){
				foreach($params['token-vars'] as $key => $name)
					if(@$options['token-vars'][$key])
						$req[$key] = $options['token-vars'][$key];

				//add params from grant vars to token (if necessary)
				foreach($params['token-vars'] as $key=>$code)
					//grant defined vars
					if(preg_match("/<\!--\[--grant-(.+)--\]-->/", $code, $matches)){
						$req[$key] = $options['grant-vars'][$matches[1]];
					}
			}// end get vars from options

			//build request array
			$request = array('body' => $req);
			if(count(@$params['token-headers']))
				$request['headers'] = $params['token-headers'];
			//make request
			unset($request['body']['state']);
			if("post" == strtolower(@$params['token-method'])){
				$res = wp_remote_post($params['token-uri'], $request);
			}
			elseif("get" == strtolower(@$params['token-method'])){
				$uri = $params['token-uri'] . "?" . http_build_query($request['body']);
				$res = wp_remote_get($uri);
			}
			if(is_wp_error($res))
				return $this->_error($res->get_error_message ());
			
			//parse response
			if("json"==strtolower(@$params['token-datatype']))
				$res = json_decode($res['body']);
			else{
				parse_str($res['body']);
				if(isset($error))
					return $this->_error($error);
				$res = new stdClass();
				$res->access_token = $access_token;
				if(@$refresh_token)
					$res->refresh_token = $refresh_token;
			}
		} //end Grant access tokens
		
		//error report
		if(@$res->error){
			if(@$res->error->message)
				return $this->_error( "Token Request: ". $res->error->message );
			return $this->_error( "Token Request: " . $res->error );
		}
		elseif(!@$res->access_token)
			return $this->_error("No access token returned from the service");

		//if user id, store token
		if($this->user->ID){
			$this->_set_token($dto->slug, $res->access_token );
			if(@$res->refresh_token)
				$this->_set_token($dto->slug, $res->refresh_token, 'refresh');
		}
		
		return $res;
		
	}
	
	/**
	 * Parses an array to a DTO for use with this class.
	 * 
	 * The format of the returned DTO is:
	 * 
	 * $res = new stdClass();
	 * $res->response = array();	//the module defined dto
	 * $res->slug = "";
	 * $res->user = "";
	 * 
	 * @param array $response An array to parse. Generally a $_REQUEST
	 * @return stdClass|WP_error Returns an error if no service slug found.
	 * @subpackage service-method
	 */
	private function _service_parse_dto( array $response ){
		
		//vars
		$res = new stdClass();
		$res->response = array();
		$res->slug = false;
		$res->user = "";
		
		//if $_REQUEST remove vars
		unset($response['action']);
		
		//parse slug and userID from oauth2 'state' variable.
		if(@$response['state']){
			$vars = unserialize(stripslashes($response['state']));
			$res->slug = @urldecode($vars[1]);
			$res->user = @urldecode($vars[2]);
		}
		//parse slug from get var
		elseif(@$response['slug'])
			$res->slug = $response['slug'];
		//parse slug from sessions
		elseif(@$_SESSION['api-con-module'])
			$res->slug = $_SESSION['api-con-module'];
		//return error
		else
			return $this->_error ("::_service_parse_dto() No module slug found");
		
		//what ever vars are left is the services response struct
		foreach($response as $key=>$val)
			$res->response[$key] = urldecode(stripslashes($val));
		
		//make sure state is always exact same.
		if(@$response['state'])
			$res->response['state'] = $response['state'];
		
		//load serivice module's params
		$params = $this->get_service($res->slug);
		if(is_wp_error($params)) die( $this->last_error );
		return $res;
	}
	
	/**
	 * Set the access token for a user.
	 * 
	 * @todo method moved to module::set_param( array );
	 * @deprecated
	 * @param string $slug The service slug
	 * @param string $token The token
	 * @param string $type 'access'|'refresh'. Whether storing access or refresh
	 * tokens. Default 'access'. 
	 * @return mixed Returns the meta data.
	 * @subpackage service-method
	 */
	private function _set_token($slug, $token, $type='access', $user_id=null){
		
		//try get current user
		if(!$user_id){
			if($this->user->ID==0)
				$this->user = $this->_get_current_user();
			if($this->user->ID==0)
				return false;
			$user_id = $this->user->ID;
		}
		
		$meta = get_user_meta($user_id, $this->option_name, true);
		if(!@$meta[$slug])
			$meta[$slug] = array('access'=>'','refresh'=>'');
		$meta[$slug][$type] = $token;
		
		update_user_meta($user_id, $this->option_name, $meta);
		return $meta;
	}
	
	/**
	 * Validates a token.
	 * 
	 * Will check for validation parameters in module index.php file.
	 * 
	 * @todo this method
	 * @param string $slug The service slug
	 * @param string $token The token to check
	 * @param boolean $refresh Default false. Whether its an access or refresh
	 * token
	 * @deprecated
	 * @return true|WP_Error
	 */
	private function _service_validate_token($slug, $token, $refresh=false){
		
		return true;
	}
	
	/**
	 * Allows a function hook into the login process.
	 * 
	 * In order for situations where the plugin may want to hook into the login
	 * process after a login is successfull. Using normal wordpress's actions
	 * won't work here because a successfull login from one request will
	 * trigger all callbacks.
	 * 
	 * The callbacks therefore need to be unique and also stored in the database
	 * This also means that there has to be a unique variable that can 
	 * sent/recieved to identify which callback should handle what request.
	 * 
	 * @param string $file The full location to the file with the function.
	 * @param mixed $func Either array of object & method string pair or a 
	 * function name as string.
	 * @param string $unique Unique string that will be returned by the response
	 * to match up with the right callback.
	 * @return string Returns the unique parameter
	 * @subpackage api-core
	 */
	public function _set_callback( $file, $func, $unique, $db=true ){
		
		//if object passed get class name
		if(is_object(@$func[0]))
			$func[0] = get_class($func[0]);
		
		$data = array(
			'file' => $file,
			'nonce' => $unique,
			'func' => $func
		);
		
		//build new callbacks array
		$callbacks = array();
		$callbacks = $this->_get_options_transient("-callbacks");
		$callbacks[ $unique ] = $data;
		
		//if using nonces in requests update db
		if($db)
			$this->_set_option_transient( "-callbacks", $callbacks);
		//if not update session array
		else
			$_SESSION['callbacks'][$unique] = $data;
		
		//return unique key
		return $unique;
	}
	
	/**
	 * Sets temporary options.
	 * 
	 * Sets options that will expire in x amount of seconds such as callbacks
	 * for login hooks.
	 * 
	 * @param string $key Options identifiying string
	 * @param array $options The options array
	 * @param integer $expiration Time to expire. Default 10 seconds.
	 * @subpackage api-core
	 */
	private function _set_option_transient($key='', array $options, $expiration=10){
	
		//multisite
		if(is_multisite())
			set_site_transient($this->option_name.$key, $options, $expiration);
		
		//normal blog
		else
			set_transient($this->option_name.$key, $options, $expiration);
	}
	
	/**
	 * Sets the API's options.
	 * 
	 * If multisite install will set site options, if not then normal wp 
	 * options. Will update the services member with the new options.
	 * 
	 * @param array $options 
	 * @subpackage api-core
	 */
	private function _set_option( array $options ){
		
		//multisite install
		if(is_multisite())
			update_site_option($this->option_name, $options);
		else
			update_option($this->option_name, $options);
		
		//update services array with new options
		foreach($options['services'] as $slug=>$option){
			
			//update active
			foreach($this->services['active'] as $active_slug=>$old_option)
				if($active_slug==$slug){
					$this->services['active'][$slug]['options'] = $option;
					continue;
				}
			
			//update inactive
			foreach($this->services['inactive'] as $inactive_slug=>$old_option)
				if($inactive_slug==$slug){
					$this->services['inactive'][$slug]['options'] = $option;
					continue;
				}
		}
		return $options;
	}
	
	/**
	 * Set the options for a service.
	 * 
	 * @todo try not use ::_get_params. this method re-reads all the module
	 * files using filesystem calls and is already called by 
	 * ::_get_installed_services. 
	 * @uses API_Connection_Manager::set_option()
	 * @param string $slug Index file to service.
	 * @param array $options An array of options.
	 * @return array Returns the new api options array
	 * @subpackage api-core
	 */
	public function _set_service_options( $slug, $options ){
		
		//set service option
		$current = $this->_get_options();
		$current['services'][$slug] = $options;
		$this->_set_option($current);
		
		//update the services array
		$params = $this->_get_module( $slug );
		$options = $this->_get_service_options( $slug );
		foreach($this->services['active'] as $key=>$data){
			$state = 'active';
			break;
		}
		
		$this->services[$state][$slug]['params'] = $params;
		$this->services[$state][$slug]['options'] = $options;
		return $options;
	}
	
}