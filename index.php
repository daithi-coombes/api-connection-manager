<?php
/**
 * @package api-connection-manager
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
error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
ini_set('display_errors',1);
define('API_CON_MNGR_LOG_ENABLE', false);
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
require_once( "debug.func.php" );
require_once( $API_CON_PLUGIN_DIR . "/vendor/OAuth.php");
include_once(dirname(__FILE__).'/vendor/log4php/Logger.php');
require_once( $API_CON_PLUGIN_DIR . "/class-api-con-logger-filter.php");
require_once('class-api-connection-manager.php'); //hack: jenkins ant is not finding this through autoloader!
require_once('class-api-con-mngr-view.php'); //hack: jenkins ant is not finding this through autoloader!
require_once('class-api-con-mngr-error.php'); //hack: jenkins ant is not finding this through autoloader!
@Logger::configure(dirname(__FILE__).'/log4net-config.xml');
/**
 * end Vendor dependencies 
 */



/**
 * Autoloader 
 */
function API_Con_Mngr_Autoload($class){
  return;
	global $API_CON_PLUGIN_DIR;
  $class_name = preg_replace('-', '_', $class);
  $class_name = strtolower( $class_name );
	$filename = "class-{$class_name}.php";
	@include "{$API_CON_PLUGIN_DIR}/{$filename}";
}
spl_autoload_register("API_Con_Mngr_Autoload");
global $API_Connection_Manager;
$API_Connection_Manager = new API_Connection_Manager();
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

/**
 * Log messages using Logger
 * @param  string $msg   The message to log
 * @param  string $level Default info. The log level to use
 */
function api_con_log($msg, $level='info'){
  
  //check able to log
	if(!defined('API_CON_MNGR_LOG_ENABLE') || !API_CON_MNGR_LOG_ENABLE)
		return false;

  $bt = debug_backtrace();
  $class = @$bt[1]['class'];

  // Manually construct a logging event
  $logger = Logger::getLogger("{$class}::{$bt[1]['function']}");

  $level = LoggerLevel::toLevel($level);
  $event = new LoggerLoggingEvent($class, $logger, $level, $msg);

  // Override the location info
  $location = new LoggerLocationInfo(array(
  	'line' => $bt[0]['line'],
  	'class' => $class,
  	'function' => $bt[1]['function'],
  	'file' => $bt[0]['file']
  	));
  $event->setLocationInformation($location);
  
  // Log it
  @$logger->logEvent($event);
}

//test logger is valid
$test_log = Logger::getRootLogger();
foreach(array('request','response') as $appender ){
  if(!defined('API_CON_MNGR_LOG_ENABLE') || !API_CON_MNGR_LOG_ENABLE)
    continue;
  
  $file = $test_log->getAppender( $appender )->getFile();
  if(!is_writable(dirname($file))){
    $api_con_mngr_log_error = new API_Con_Mngr_Error();
    $api_con_mngr_log_error->add_error("Unable to write to:<br/> {$file}");    
  }
}
//end Helper functions

/**
 * Api Connection Manager.
 * 
 * Make sure the $API_Connection_Manager is constructed before the dash
 * settings pages are loaded.
 */
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
 * end Api Connection Manager 
 */