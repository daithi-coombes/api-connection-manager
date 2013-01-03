<?php
/*
  Plugin Name: Google API
  Plugin URI: https://github.com/cityindex/labs.cityindex.com/tree/master/httpdocs/wp-content/plugins/ci-login
  Description: AutoFLow login module for google
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

/**
 * The callback uri in the service should always be: 
 * www.domain.com/wp-admin/admin-ajax.php?action=autoflow&module={$slug}
 * where {$slug} is the slug name of this module set in the below array.
 * 
 * If a security var is allowed with the oauth service, in your grant-vars array
 * set the var name value as _wpnonce
 * ie:
 * $oauth2['grant-vars']['state'] = '_wpnonce'
 * 
 * @link https://developers.google.com/accounts/docs/OAuth2WebServer
 * 
 * Standard vars
 * <!--[--token--]--> will display the refresh token, or the access token if
 * none
 * <!--[--token-access--]--> use the access token
 * <!--[--token-refresh--]--> use the refresh token
 * <!--[--redirect-uri--]--> use the redirect uri (you can find this in the API-Con service options in the dashboard
 */


$oauth2 = array(
	
	/**
	 * Service Params 
	 */
	'offline' => true,
	'button-text' => 'Login with Google',
	
	/**
	 * Grant Params
	 */
	'grant-uri' => 'https://accounts.google.com/o/oauth2/auth',
	'grant-response-type' => 'json',
	//per blog options
	'grant-options' => array(
		'client_id' => 'ID',
		'scope' => 'Scope'
	),
	//params that will make up grant query
	'grant-vars' => array(
		'client_id' => '<!--[--grant-client_id--]-->',	//use client_id value from grant options
		'redirect_uri' => '<!--[--redirect-uri--]-->',
		'state' => '<!--[--[state]--]-->',
		'response_type' => 'code',
		'approval_prompt' => 'auto'
	),// end Grant Params
	
	/**
	 * Token Params 
	 */
	'token-uri' => 'https://accounts.google.com/o/oauth2/token',
	'token-method' => 'POST',
	'token-datatype' => 'json',
	'token-options' => array(
		'client_secret' => 'Client Secret',
	),
	'token-vars' => array(
		'grant_type' => 'authorization_code',
		'client_id' => '<!--[--grant-client_id--]-->',
		'client_secret' => '<!--[--token-client_secret--]-->',
		'redirect_uri' => '<!--[--redirect-uri--]-->'
	),
	
	/**
	 * Revoke Params 
	 */
	'revoke-uri' => 'https://accounts.google.com/o/oauth2/revoke',
	'revoke-method' => 'get',
	'revoke-vars' => array(
		'token' => '<!--[--token-access--]-->'
	),
	
	/**
	 * Offline Params 
	 */
	'offline-token' => array(
		'access_type' => 'offline'	//this will get sent in token request
	),
	'offline-uri' => 'https://accounts.google.com/o/oauth2/token',
	'offline-method' => 'post',
	'offline-datatype' => 'json',
	'offline-vars' => array(
		'refresh_token' => '<!--[--refresh-token--]-->',
		'client_id' => '<!--[--grant-client_id--]-->',
		'client_secret' => '<!--[--token-client_secret--]-->',
		'grant_type' => 'refresh_token'
	)
);