<?php
/**
 * class-api-connection-manager
 * 
 * This class uses params stored in a module file to connect to remote services.
 * It manages the storing of access_tokens and refresh_tokens in the oauth2
 * spec and also allows for custom service api's to be connected to.
 * 
 * Plugins can then easily connect to these services and make requests.
 * Developers can also easily create new modules with the params for different
 * services.
 * 
 * Requests made to this api will then check that the user is logged into that
 * service. If they are not and the service is active then they will be shown
 * a login screen.
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
 * @todo localization
 * @todo refresh token
 * @global array $_SESSION['API_Con_Mngr_Module']
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
	/** @var Logger The log class */
	private $log_api;
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
		
		/**
		 * Logging. Uncomment the below line to log 
		 */
		if(file_exists(dirname(__FILE__)."/log4net-config.xml"))
			@$this->log_api = Logger::getLogger(__CLASS__);
		else $this->log_api = new WP_Error('API_Connection_Manager: log4php','No log4net-config.xml file found');
		//end logging
		
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
		
		//check for errors
		add_action('admin_notices', array(&$this, 'admin_notices'));
		
		/**
		 * actions
		 */
		add_action('delete_user', array(&$this, 'delete_user'));
		add_action('wpmu_delete_user', array(&$this, 'delete_user'));
		
		/**
		 * Check if logout request 
		 */
		if(@$_REQUEST['api-con-mngr-logout'])
			$this->_service_logout( urldecode($_REQUEST['service']) );
		
	} //end construct()
	
	/**
	 * Admin notices callback. Will print any errors in the session 
	 * Api-Con-Errors and then unset the session
	 */
	public function admin_notices(){
		
		$errors = @$_SESSION['Api-Con-Errors'];
		if(!$errors)
				return;
		
		print "<div id=\"message\" class=\"error\">
			<h2>API Connection Manager</h2>
			<ul>";
		foreach($errors as $err)
			echo "<li>{$err}</li>\n";
		echo "</ul></div>";
		unset($_SESSION['Api-Con-Errors']);
	}
	
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
	 * Action callback
	 * Delete option values relating to deleted user
	 */
	public function delete_user( $user_id ){
		
		$option_name = "API_Con_Mngr_Module-connections";
		if(is_multisite())
			$connections = get_site_option($option_name, array());
		else
			$connections = get_option($option_name, array());
		
		//look through all slugs
		foreach($connections as $slug => $connection)
			foreach($connection as $_user => $uid)
				if($_user==$user_id){
					unset($connections[$slug][$_user]);
					if(!count($connections[$slug]))
						unset($connections[$slug]);
				}
				
		if(is_multisite())
			update_site_option($option_name, $connections);
		else
			update_option($option_name, $connections);
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
	 * Returns the details for a service.
	 * 
	 * Returns module details, service params and service options.
	 * 
	 * @param string $slug The service index_file
	 * @return array
	 * @subpackage helper-methods
	 */
	public function get_service( $slug ){
		
		$ret = null;
		
		//look in active
		foreach($this->services['active'] as $index_file => $service)
			if($index_file==$slug)
				$ret = $service;
		
		//look in inactive
		foreach($this->services['inactive'] as $index_file => $service){
			if($index_file==$slug)
				$ret = $service;
		}
		
		if(!$ret)
			$ret = new WP_Error ('API Connection Manager', "Module not found");
		
		return $ret;
			
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
	 * Log an INFO message
	 * @param string $msg The message to log
	 * @return none
	 */
	public function log($msg, $level='info'){
			if(!is_wp_error($this->log_api)){
				
				// Manually construct a logging event
				$level = LoggerLevel::toLevel($level);
				$logger = Logger::getLogger(__CLASS__);
				$event = new LoggerLoggingEvent(__CLASS__, $logger, $level, $msg);

				// Override the location info
				$bt = debug_backtrace();
				$caller = array_shift($bt);
				$location = new LoggerLocationInfo($caller);
				$event->setLocationInformation($location);

				// Log it
				$logger->logEvent($event);
				/**
				//trace
				$bt = debug_backtrace();
				$caller = array_shift($bt);
				$trace = $caller['file'] . ":" . $caller['line'];
				$this->log_api->trace($trace);
				
				//log
				$this->log_api->$level( $msg );
				 */
			}
	}

	static public function error($msg){
		if(!@$_SESSION['Api-Con-Errors'])
			$_SESSION['Api-Con-Errors'] = array();
		$_SESSION['Api-Con-Errors'][] = $msg;
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
	 * @deprecated
	 * @todo remove this method. Been moved to API_Con_Mngr_Module
	 * @param string $slug The service slug
	 * @param boolean $die Default true. Return the login link or die()
	 */
	private function _print_login($slug, $die=true){
		return;
		
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
	 * Get current user.
	 * 
	 * Get this as early as possible for some calls when using admin ajax.
	 * @return WP_User if no user then returns WP_User(0) else will return
	 * current users WP_User object.
	 */
	static public function _get_current_user(){
		
		global $current_user;
		$current_user = wp_get_current_user();
		return $current_user;
	}
	
	/**
	 * In crease the WP_HTTP request timeout from 5 (default) to 25. Callback
	 * declared in index.php add_filter('http_request_timeout');
	 * @param int $time
	 * @return int 
	 */
	public function _get_http_request_timeout($time){
		$time=50;
		return $time;
	}
	
	/**
	 * Builds an array of all installed modules and sets their params.
	 * 
	 * The return array is split into active and inactive modules. There are a
	 * lot of system calls and working with files. Overuse of this method would
	 * not be advised.
	 * 
	 * Currently this method is used in:
	 *  - API_Connection_Manager::__construct()
	 *  - API_Connection_Manager::get_services //only if no services loaded yet
	 * 
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
		$slugs = array();
		if($plugins_dir){
			
			while( ($file=readdir($plugins_dir)) !==false ){
				if($file=="." || $file=="..") continue;
				if(is_readable("{$plugin_root}/{$file}/index.php"))
					$slugs[] = "{$file}/index.php";
			}
			closedir($plugins_dir);
		} //end list of plugin files
		
		if ( empty($slugs) )
			return array(
				'active' => array(),
				'inactive' => array()
			);
		
		/**
		 * Build array of API_Con_Mngr_Module classes
		 */
		foreach ( $slugs as $slug ) {
			
			/**
			 * Use API_Con_Mngr_Module 
			 */
			//load index file
			unset($module);
			include("{$plugin_root}/{$slug}");
			if(!isset($module))
				continue;
			
			//set params
			$plugin_data = get_plugin_data("{$plugin_root}/{$slug}", false, false); //Do not apply markup/translate as it'll be cached.
			$module->set_params( $plugin_data );
			$wp_plugins[$module->slug] = $module;
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
			!@$user_options[$slug][$type]
		) return $this->_error($err_msg);
		
		//if empty slug
		
		
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
	
	public function _reset_options(){
		$option_name = "API_Con_Mngr_Module-connections";
		//multisite install
		if(is_multisite())
			$connections = update_site_option($option_name, array());
		else
			$connections = update_option($option_name, array());
		$this->log("New options");
		$this->log(get_site_option($option_name));
	}
	
	/**
	 * Ajax callback
	 * 
	 * Bootstraps all response's to the api connection manager. Both secure and
	 * non-secure response are parsed here (wp_ajax && wp_ajax_nopriv).
	 *
	 * Processes login requests for custom services, oauth1 and oauth2.
	 * 
	 * @subpackage api-core
	 * @return mixed
	 */
	public function _response_listener( ){
		
		/**
		 * BOOTSTRAP
		 */
		//make sure admin ajax call
		if(
			!defined('DOING_AJAX') || 
			true!==@DOING_AJAX ||
			@$_GET['action']!='api_con_mngr'
		) return;
		
		/**
		 * Process flag.
		 * In situations where the ajax page needs to be reloaded, ie autoflow
		 * when the email forms are submited, this flag if set to false will
		 * stop api-con from reprocessing request tokens for access tokens.
		 */
		if(@$_REQUEST['api-con-mngr']=='false')
			return false;
		//end Process flag
		
		//if reseting options
		if(@$_GET['api-action']=='reset'){
			$this->_reset_options();
			die("Options reset");
		}
		//end if reseting options
		
		@$this->log("response listener: nonce {$_SESSION['API_Con_Mngr_Module'][$this->slug]['nonce']}");
		
		//get dto (will also set the current user)
		$dto = $this->get_dto();
		if(is_api_con_error($dto))
			$dto->get_error_message('die');
		
		//if slug setup module
		if(!is_wp_error($dto->slug)){
			$module = $this->get_service($dto->slug);
			if(is_wp_error($module))
				die( $module->get_error_message() );
			$module->parse_dto($dto);
			$err = $module->check_error($dto->response);
			
			//check for error
			if(is_wp_error($err))
				die($err->get_error_message());
		}
		else
			die("Error: " . $dto->slug->get_error_message());		
		//END BOOTSTRAP
		$this->log($dto);
		
		/**
		 * Connecting... screen
		 * set sessions
		 * register callbacks
		 * redirect to authorize url
		 */
		if(@$dto->response['login']){
			
			//has a custom login form been submited?
			if($dto->response['login']=='do_login'){
				$dto->response['session'] = $module->login_form_callback( $dto );
				$module->do_callback( $dto );
			}
			//do we need to print a login form?
			elseif($module->login_form)
				$module->get_login_form();	//this call will print form and die()
			
			//login authorize
			$this->_service_login_authorize( $module, $dto );
		}
		//end Connecting... screen
		
		
		/**
		 * oauth1 service response
		 */
		elseif($module->protocol == 'oauth1'){
			
			/**
			 * Get the access_token 
			 */
			if(@$dto->response['oauth_token']){
				$module->oauth_token = $dto->response['oauth_token'];
				$module->oauth_token_secret = @$dto->response['oauth_token_secret'];
				$module->oauth_token_verifier = @$dto->response['oauth_token_verifier'];
				$access = $module->get_access_token( $dto->response );
				$dto->response = $access;
				$module->oauth_token = $access['oauth_token'];
				$module->oauth_token_secret = $access['oauth_token_secret'];
				
				//helper method module can override to add actions to login
				//such as get request token for oauth1
				$module->do_login( $dto );
				
				$module->do_callback( $dto );
				/**
				//if callback
				if(!$this->user->ID || ($this->user->ID==0))
					$module->do_callback( $dto );
				
				//login user
				else{
					$this->log("Connecting user {$this->user->ID} to {$module->slug}");
					$uid = $module->get_uid();
					$module->login($uid);
				}
				 * 
				 */
			}
			// end saving the access token

		}
		//end oauth1 service response
		
		/**
		 * oauth2 service response.
		 * Normally should only happen when $_REQUEST['code'] is recieved
		 */
		elseif(@$module->protocol=="oauth2"){
			
			//get tokens (this call will set tokens in db for module)
			$tokens = $module->get_access_token( $dto->response );			
			if(is_wp_error($tokens))
				die("Error: " . $tokens->get_error_message());
			
			//reset dto response to tokens recieved
			$dto->response = $tokens;
			
			//helper method module can override to add actions to login
			//such as get request token for oauth1
			$module->do_login( $dto );

			//do callback
			$module->do_callback( $dto );
		}
		//end oauth2 service response
		
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
	 * Authorize login method.
	 * 
	 * Grabs request tokens from remote server and redirects to authorize url
	 * 
	 * @param API_Con_Mngr_Module $module
	 * @param stdClass $dto 
	 */
	private function _service_login_authorize( API_Con_Mngr_Module $module, stdClass $dto ){
		
		//set referer, in case error reported mid authentication
		$_SESSION['api-con-mngr-referer'] = $_SERVER['HTTP_REFERER'];
		
		switch($module->protocol){
			
			//oauth1
			case 'oauth1':
				
				//get request tokens and authorize url
				$tokens = $module->get_request_token();
				$url = $module->get_authorize_url( $tokens );
				
				//if nonce in request then set it as session var
				$_SESSION['API_Con_Mngr_Module']['slug'] = $dto->response['slug'];

				//redirect to url and exit
				header("Location: {$url}");
				exit;
			//end oauth1
			
			//oauth2
			case 'oauth2':
				
				$url = $module->get_authorize_url();
				header("Location: {$url}");
				
				break;
			//end oauth2
			
			//custom service
			default:
				$this->log("Custom service:");
				$this->log($dto);
				break;
			//end custom service
		}
		
		die("Connecting to {$dto->slug} ...");
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
	 * Parses a http request and returns a stdClass dto.
	 * 
	 * The format of the returned DTO is:
	 * 
	 * $res = new stdClass();
	 * $res->response = array();	//the $_REQUEST array
	 * $res->callback = stdClass;	//the plugin callback if any
	 * $res->slug = "";
	 * $res->user = "";
	 * 
	 * @param array $response An array to parse. Generally a $_REQUEST
	 * @return stdClass|WP_error Returns an error if no service slug found.
	 * @subpackage service-method
	 */
	public function get_dto(){

		//if $_REQUEST remove vars
		$response = $_REQUEST;
		unset($response['action']);

		/**
		 * get module slug
		 */
		if(@$_REQUEST['slug'])
			$slug = urldecode($_REQUEST['slug']);
		elseif(@$_SESSION['API_Con_Mngr_Module']['slug'])
			$slug = $_SESSION['API_Con_Mngr_Module']['slug'];
		else
			return new API_Con_Mngr_Error("Error: No slug found");
		
		$_SESSION['API_Con_Mngr_Module']['slug'] = $slug;
		//end get module slug
		
		/**
		 * set callback
		 */
		if(@$_REQUEST['file'] && @$_REQUEST['callback']){
			$callback = array(
				'file' => @$_REQUEST['file'],
				'callback' => @$_REQUEST['callback']
			);
			$_SESSION['API_Con_Mngr_Module'][$slug]['callback'] = $callback;
		}
		//end set callback
		
		/**
		 * get callback 
		 */
		else{
			if(@$_REQUEST['callback']){
				$callback = stripslashes(urldecode($_REQUEST['callback']));
				$_SESSION['API_Con_Mngr_Module'][$slug]['callback'] = $callback;
			}
			elseif(@$_SESSION['API_Con_Mngr_Module'][$slug]['callback']){
				$callback = $_SESSION['API_Con_Mngr_Module'][$slug]['callback'];
			}
			else
				$callback = array();
		}
		//end get callback
		
		//vars
		$res = new stdClass();
		$res->callback = $callback;
		$res->response = array();
		$res->slug = $slug;
		$res->user = $this->_get_current_user();
		
		//what ever vars are left is the services response struct
		foreach($response as $key=>$val)
			$res->response[ urldecode($key) ] = urldecode(stripslashes($val));
		
		//make sure state is always exact same.
		if(@$response['state'])
			$res->response['state'] = $response['state'];
		
		//look for params in sessions
		if(@is_array($_SESSION['API_Con_Mngr_Module'][$res->slug]['params'])){
			foreach($_SESSION['API_Con_Mngr_Module'][$res->slug]['params'] as $key=>$val)
				$res->response[$key] = $val;
			unset($_SESSION['API_Con_Mngr_Module'][$res->slug]['params']);
		}
		
		//unset sessions
		//unset($_SESSION['API_Con_Mngr_Module']['slug']);
		unset($_SESSION['callback']); //dev code - clear deprecated sessions
		unset($_SESSION['API_Con_Mngr_Module']['callback']); //dev code - clear deprecated sessions
		unset($_SESSION['headers']);
		
		//load serivice module's params
		$params = $this->get_service($res->slug);
		//if(is_wp_error($params)) die( $params->get_error_message() );
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
	 * Sets the slug for module as a callback var
	 * 
	 * For oauth2 the key in the callbacks array is the client_id. 
	 *
	 * @see API_Connection_Manager::do_callback()
	 * @param API_Con_Mngr_Module $module
	 * @return type 
	 * @subpackage api-core
	 */
	public function _set_callback( API_Con_Mngr_Module $module ){
		
		$this->_set_option_transient("-callbacks", array(
			$module->key = $module->slug
		));
		return;
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
	}
	
	/**
	 * Save the options for a service.
	 * 
	 * @deprecated module options get/set in option class
	 * @see API_Con_Mngr_Module::set_options()
	 * @param API_Con_Mngr_Module $module
	 * @return array Returns the new api options array
	 * @subpackage api-core
	 */
	public function _set_service_options(API_Con_Mngr_Module $module, $options ){
		
		//update the db
		$current = $this->_get_options();
		$current['services'][$module->slug] = $options;
		$this->_set_option($current);
		
		//update the services array
		foreach($this->services['active'] as $slug=>$data)
			if($slug==$module->slug){
				$state = 'active';
				break;
			}else $state = "inactive";
		$this->services[$state][$slug] = $module;
		
		return $module;
		
		/**
		 * set service option
		 * @deprecated
		 *
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
		 * 
		 */
	}
	
}