<?php
/*
  Plugin Name: City Index Login
  Plugin URI: https://github.com/cityindex/labs.cityindex.com/tree/master/httpdocs/wp-content/plugins/ci-login
  Description: AutoFlow login for City Index
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

require_once('CIAPI-PHP/CIAPIPHP.class.php');

$service = array(
	'button-text' => 'Login with CityIndex',
	'data' => array(
		'UserName' => array(
			'type' => 'text',
			'label' => 'Username',
			'value' => ''
		),
		'Password' => array(
			'type' => 'password',
			'label' => 'Password',
			'value' => ''
		)/**,
		'AppKey' => array(
			'type' => 'hidden',
			'value' => ''
		),
		'AppVersion' => array(
			'type' => 'hidden',
			'value' => ''
		),
		'AppComments' => array(
			'type' => 'hidden',
			'value' => ''
		)**/
	),
	'data-type' => 'json',
	'function_name' => 'cityindex_login',
	'headers' => array(
		'Content-Type' => 'application/json',
		'UserName' => 'xx189949',
		'Session' => ''
	),
	'iframe' => 'true',
	'method' => 'POST',
	'uri' => 'https://ciapi.cityindex.com/tradingapi/session'
);