<?php
/*
  Plugin Name: Facebook Login
  Plugin URI: https://github.com/cityindex/labs.cityindex.com/tree/master/httpdocs/wp-content/plugins/ci-login
  Description: Facebook login module for google
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

$oauth2 = array(
	'button-text' => 'Login with FaceBook',
	
	//grant options
	'grant-options' => array(
		'client_id' => 'Client ID',
		'scope' => 'Scope'
	),
	
	//grant access variables
	'grant-uri' => 'https://www.facebook.com/dialog/oauth',
	'grant-vars' => array(
		'client_id' => '<!--[--grant-client_id--]-->',
		'redirect_uri' => '<!--[--redirect-uri--]-->',
		'state' => '<!--[--[state]--]-->',
		'scope' => '<!--[--grant-scope--]-->'
	),
	
	//token options
	'token-options' => array(
		'client_secret' => 'Client Secret'
	),
	
	//access token variables
	'token-uri' => 'https://graph.facebook.com/oauth/access_token',
	'token-method' => 'get',
	'token-vars' => array(
		'client_secret' => '<!--[--token-client_secret--]-->',
		'client_id' => '<!--[--grant-client_id--]-->',
		'redirect_uri' => '<!--[--redirect-uri--]-->',
	)
);