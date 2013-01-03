<?php
/*
  Plugin Name: DropBox
  Plugin URI: https://github.com/cityindex/labs.cityindex.com/tree/master/httpdocs/wp-content/plugins/ci-login
  Description: Dropbox module
  Version: 0.1
  Author: Daithi Coombes
  Author URI: http://david-coombes.com
 */

/**
3.  Definitions

	Service Provider:
		A web application that allows access via OAuth.
	User:
		An individual who has an account with the Service Provider.
	Consumer:
		A website or application that uses OAuth to access the Service Provider on behalf of the User.
	Protected Resource(s):
		Data controlled by the Service Provider, which the Consumer can access through authentication.
	Consumer Developer:
		An individual or organization that implements a Consumer.
	Consumer Key:
		A value used by the Consumer to identify itself to the Service Provider.
	Consumer Secret:
		A secret used by the Consumer to establish ownership of the Consumer Key.
	Request Token:
		A value used by the Consumer to obtain authorization from the User, and exchanged for an Access Token.
	Access Token:
		A value used by the Consumer to gain access to the Protected Resources on behalf of the User, instead of using the User’s Service Provider credentials.
	Token Secret:
		A secret used by the Consumer to establish ownership of a given Token.
	OAuth Protocol Parameters:
		Parameters with names beginning with oauth_.
 */

$oauth1 = array(
	
	'button-text' => 'Login with DropBox',
	
	'request-token' => array(
		'uri' => 'https://api.dropbox.com/1/oauth/request_token',
		'method' => 'post'
	),
	
	'user-auth' => array(
		'uri' => 'https://www.dropbox.com/1/oauth/authorize',
		'method' => 'get',
		'body' => array(
			'oauth_token' => '<!--[--request-token_oauth_token--]-->',
			'oauth_callback' => '<!--[--callback-uri--]-->'
		)
	),
	
	'access-token' => array(
		'uri' => 'https://api.dropbox.com/1/oauth/access_token',
		'method' => 'post',
		'body' => array(
			'oauth_token' => '<!--[--request-token_oauth_token--]-->',
			'oauth_token_secret' => '<!--[--request-token_oauth_token_secret--]-->'
		)
	)
	
);