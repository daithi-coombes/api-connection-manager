<?php
$services = array(
	'google/index.php' => array(
		'app-grant-vars' => array(
			'client_id' => 'foo',
			'redirect_uri' => 'bar',
			'scope' => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email'
		),
		'app-token-vars' => array(
			'client_secret' => 'baz'
		)
	)
);