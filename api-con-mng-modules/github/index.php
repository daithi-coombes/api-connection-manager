<?php
/*
  Plugin Name: Github Oauth2
  Plugin URI: https://github.com/cityindex/labs.cityindex.com/tree/master/httpdocs/wp-content/plugins/ci-login
  Description: AutoFLow login module for google
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

$oauth2 = array(
	
	
	/**
	 * service params 
	 */
	'offline' => false,				//set this to true if service provides refresh tokens
	'button-text' => 'Login with GitHub',
	
	
	/**
	 * Grant vars 
	 */
	'grant-uri' => 'https://github.com/login/oauth/authorize',
	'grant-response-type' => 'query',
	//options to be set by the blog admin
	'grant-options' => array(
		'client_id' => 'Client ID',
		'scope' => 'Scope'
	),
	//parameters that will make up the final grant uri
	'grant-vars' => array(
		'client_id' => '<!--[--grant-client_id--]-->',
		'redirect_uri' => '<!--[--redirect-uri--]-->',
		'state' => '<!--[--[state]--]-->'
	), //end Grant vars
	
	
	/**
	 * Token vars 
	 */
	'token-uri' => 'https://github.com/login/oauth/access_token',
	'token-method' => 'post',
	'token-datatype' => 'json',
	//options to be set by the blog admin
	'token-options' => array(
		'client_secret' => 'Client Secret'
	),
	//set token headers
	'token-headers' => array(
		'Accept' => 'application/json'
	),
	//parameters that will be sent to request token
	'token-vars' => array(
		'client_id' => '<!--[--grant-client_id--]-->',
		'redirect_uri' => '<!--[--redirect-uri--]-->',
		'client_secret' => '<!--[--token-client_secret--]-->',
		'state' => '<!--[--[state]--]-->'
	) //end Token vars
);