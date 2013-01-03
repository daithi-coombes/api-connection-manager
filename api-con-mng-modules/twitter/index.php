<?php

$oauth1 = array(
	'options' => array(
		'oauth_consumer_key' => 'Consumer Key',
		'oauth_nonce' => '<!--[--scope--]-->',
		''
	),
);

error_reporting(E_ALL);
ini_set('display_errors','on');

$ch = curl_init();
$params = array(
	'oauth_callback' => 'http://david-coombes.com/wp-admin/admin-ajax.php?action=api-con-mngr'
);
curl_setopt($ch, CURLOPT_URL, "http://api.twitter.com/oauth/request_token");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
$res = curl_exec($ch);
$info = curl_getinfo($ch);

print_r($res);
print_r($info);
print "\n";