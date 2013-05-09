<?php

class API_Connection_Manager_UserTest extends WP_UnitTestCase{
	
	protected $api;
	protected $user;
	protected $module;
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
		ar_print($module->get_connections());
		$GLOBALS['wp_tests_options']['API_Con_Mngr_Module-connections'] = array();
		$this->user->disconnect();
		$uid = "1234";
		$login = $module->login($uid);
		
		$this->assertEquals('1', $login);
		$GLOBALS['wp_tests_options']['API_Con_Mngr_Module-connections'] = array(
			$this->slug => array(1,123456)
		);
	}

	function test_disconnect_user(){

		$module = $this->api->get_service($this->slug);
		$GLOBALS['wp_tests_options']['API_Con_Mngr_Module-connections'] = array(
			$this->slug => array()
		);
		$this->user->disconnect();

		$this->assertEquals($module->get_connections()[$this->slug], array());
		$GLOBALS['wp_tests_options']['API_Con_Mngr_Module-connections'] = array(
			$this->slug => array(1,123456)
		);
	}
	
}