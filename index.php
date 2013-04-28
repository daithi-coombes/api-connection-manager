<?php
/**
 * @package wp-services-api
 */
/*
  Plugin Name: API Connection Manager
  Plugin URI: https://github.com/labs.cityindex.com
  Description: API for connecting to 3rd party services
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

//boostrap
error_reporting(E_ALL ^ E_STRICT);
ini_set('display_errors',1);
$API_CON_PLUGIN_DIR =  WP_PLUGIN_DIR . "/" . basename(dirname( __FILE__ ));
$API_CON_PLUGIN_URL =  WP_PLUGIN_URL . "/" . basename(dirname( __FILE__ ));


/**
 * WP Core dependencies
 */
require_once( ABSPATH . "/wp-admin/includes/class-wp-list-table.php");		//for dashboard/net admin tables in API_Connection_Manager_Setup()
require_once( ABSPATH . "/wp-admin/includes/plugin.php" );  //read module files in API_Connection_Manager::_get_installed_services()
require_once( ABSPATH . "/wp-admin/includes/screen.php" );  //for WP_List_tables in API_Connection_Manager_Setup
require_once( ABSPATH . WPINC ."/pluggable.php");			//wp_validate_cookie in API_Connection_Manager::_get_params()
/**
 * end WP Core dependencies 
 */


/**
 * Vendor dependencies 
 */
//logger
require_once( "debug.func.php" );
require_once( $API_CON_PLUGIN_DIR . "/includes/OAuth.php");
include_once(dirname(__FILE__).'/vendor/log4php/Logger.php');
@Logger::configure(dirname(__FILE__).'/log4net-config.xml');

/* Log the details of every wordpress hook at the TRACE level */
//add_action( 'all', 'log_action' );
function log_action() {
	$logger = Logger::getLogger(current_filter());
	if (@$logger->getName() == 'query') {
		@$logger->debug(func_get_args());
	} else {
		@$logger->trace(func_get_args());
	}
	
}
/**
 * end Vendor dependencies 
 */


/**
 * Autoloader 
 */
function API_Con_Mngr_Autoload($class){
	global $API_CON_PLUGIN_DIR;
	$class_name = "class-" . strtolower( str_replace("_", "-", $class));
	$filename = "{$class_name}.php";
	@include "{$API_CON_PLUGIN_DIR}/{$filename}";
}
spl_autoload_register("API_Con_Mngr_Autoload");
/**
 * end Autoloader 
 */

/**
 * Helper functions
 */
/**
 * Check if a variable is instance API_Con_Mngr_Error
 * @param  mixed  $thing The variable to test
 * @return boolean        Returns true if is API_Con_Mngr_Error
 */
function is_api_con_error($thing){
	if(get_class($thing)=='API_Con_Mngr_Error')
		return true;
	else
		return false;
}
//end Helper functions

/**
 * Api Connection Manager.
 * 
 * Make sure the $API_Connection_Manager is constructed before the dash
 * settings pages are loaded.
 */
require_once( $API_CON_PLUGIN_DIR . "/class-api-connection-manager.php");
add_action('plugins_loaded', function(){
	global $API_Connection_Manager;
	$API_Connection_Manager = new API_Connection_Manager();
	$API_Connection_Manager->_response_listener();
	
	/**
	 * Class depencencies 
	 */
	require_once('class-api-connection-manager-setup.php');
	require_once('class-api-connection-manager-user.php'); //Merge the autoflow settings here
	/**
	 * end Class  
	 */

	/**
	 * actions and hooks 
	 */
	add_filter( 'http_request_timeout', array(&$API_Connection_Manager,'_get_http_request_timeout'));

});
/**
 * end Api Connection Manager 
 */