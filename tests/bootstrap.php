<?php

//change this to your path
$path = '/var/www/wordpress.loc/wordpress-tests/bootstrap.php';

if (file_exists($path)) {
        $GLOBALS['wp_tests_options'] = array(
                'active_plugins' => array('api-connection-manager/index.php')
        );
        require_once $path;
} else {
        exit("Couldn't find wordpress-tests/bootstrap.phpn");
}