<?php

class API_Connection_Manager_UserTest extends WP_UnitTestCase{
	
	protected $api;
	protected $user;
	protected $slug;

	function setUp(){
		parent::setUp();

		$this->api = new API_Connection_Manager();
		$this->slug = get_option('test_slug');
		$this->user = new API_Connection_Manager_User();
		$_SESSION['Api-Con-Errors'] = array("test error");
	}

	function test_connect_user(){
		$module = $this->api->get_service($this->slug);
		ar_print($module);
	}
	
}