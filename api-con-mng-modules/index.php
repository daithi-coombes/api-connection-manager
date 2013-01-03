<?php
/**
 * Work in progress.
 * 
 * Using headers.
 * Headers are in the format:
 * $key => $value
 * 
 * When setting the response type for grant or token it can be one of the following:
 * - query
 * - json
 * - xml
 * 
 * variable shortcodes:
 * <!--[--[code]--]-->				//the authorization code recieved after grant access
 * <!--[--[access]--]-->			//access_token
 * <!--[--[refresh]--]-->			//refresh_token
 * <!--[--[state]--]-->				//state (NB: this value is set by the api connection manager)
 * <!--[--grant-$var--]-->			//grant option
 * <!--[--token-$var--]-->			//token option
 */

$oauth2 = array(
	
	/**
	 * service params 
	 */
	'offline' => false,				//set this to true if service provides refresh tokens
	
	
	/**
	 * Grant vars 
	 */
	'grant-uri' => 'https://github.com/login/oauth/authorize',
	//options to be set by the blog admin
	'grant-options' => array(
		'client_id' => 'Client ID',
		'redirect_uri' => 'Redirect URI',
		'scope' => 'Scope'
	),
	//parameters that will make up the final grant uri
	'grant-vars' => array(
		'client_id' => '<!--[--grant-client_id--]-->',
		'redirect_uri' => '<!--[--grant-redirect_uri--]-->',
		'scope' => '<!--[--grant-scope--]-->',
		'state' => '<!--[--[state]--]-->'
	), //end Grant vars
	
	
	/**
	 * Token vars 
	 */
	'token-uri' => 'https://github.com/login/oauth/access_token',
	'token-method' => 'post',
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
		'redirect_uri' => '<!--[--grant-redirect_uri--]-->',
		'client_secret' => '<!--[--token-client_secret--]-->',
		'code' => '<!--[--[code]--]-->',
		'state' => '<!--[--[state]--]-->'
	) //end Token vars
);