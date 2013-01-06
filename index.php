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
error_reporting(E_ALL);
ini_set('display_errors',1);
$PLUGIN_DIR =  WP_PLUGIN_DIR . "/" . basename(dirname( __FILE__ ));
$PLUGIN_URL =  WP_PLUGIN_URL . "/" . basename(dirname( __FILE__ ));


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
 * Dev dependencies 
 */
require_once( "debug.func.php" );
/**
 * end Dev dependencies 
 */


/**
 * Api Connection Manager.
 * 
 * Make sure the $API_Connection_Manager is constructed before the dash
 * settings pages are loaded.
 */
require_once( $PLUGIN_DIR . "/class-api-connection-manager.php");
global $API_Connection_Manager;
$API_Connection_Manager = new API_Connection_Manager();
require_once( $PLUGIN_DIR . "/class-api-con-mngr-module.php" ); //module, header and param classes
require_once( $PLUGIN_DIR . "/class-api-connection-manager-setup.php");
require_once( $PLUGIN_DIR . "/class-api-connection-manager-user.php");

/**
 * end Api Connection Manager 
 */