<?php

ob_start();

//enter in your google app details in config.php
require_once('config.php');
require_once('/var/www/cityindex.loc/labs.cityindex.com/httpdocs/wp-content/plugins/api-connection-manager/class-api-connection-manager.php');

//change this to your path
$path = '/var/www/wordpress.loc/foo/wordpress-tests/includes/bootstrap.php';

if (file_exists($path)) {
    $GLOBALS['wp_tests_options'] = $options;
    require_once $path;
} else 
    exit("Couldn't find wordpress-tests/bootstrap.phpn");