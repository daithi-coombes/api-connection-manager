<?php
/**
 * Function to login to custom service CityIndex.
 * 
 * The function gets passed the $_REQUEST global after the iframe login form is
 * submitted.
 * 
 * Also this function must return either a valid email address or a WP_Error
 * object.
 * 
 * @param string $data The $_Request[] array from the form submit
 * @return string|WP_Error 
 */
function cityindex_login( $data ){
	
	//vars
	$api = new CIAPIPHP();
	$UserName = $data['UserName'];
	$Password = $data['Password'];
	
	//login
	$api->logIn( $UserName, $Password );
	$err = $api->get_errors();
	if($err)
		return new WP_Error("Login Error", $err[0]);
	
	//get account information
	$res = $api->getAccountInformation();
	$err = $api->get_errors();
	if($err)
		return new WP_Error ('Getting Account', $err[0]);
	
	//if no email address, return WP_Error
	if(!@$res->PersonalEmailAddress)
		return new WP_Error('No email address','getAccountInformation returned no email address');
	
	//return email address
	return $res->PersonalEmailAddress;
}

