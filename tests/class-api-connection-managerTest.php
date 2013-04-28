<?php
require_once('bootstrap.php');

class API_Connection_ManagerTest extends WP_UnitTestCase{
	
	protected $api;

	function setUp(){
		parent::setUp();

		if(!defined("DOING_AJAX"))
			define('DOING_AJAX', true);
		$_GET = $_REQUEST = array(
			'action' => 'api_con_mngr',
			'slug' => 'autoflow'
			);

		$this->api = new API_Connection_Manager();
		$this->api->services['active'] = get_option('api-connection-manager')['services'];
	}

	function tearDown(){
		delete_option('api-connection-manager');
	}

	function test_response_listener_bootstrap(){

		//get dto
		$this->assertInstanceOf( 'stdClass', $this->api->get_dto(), "Unable to get dto" );
		//get service
		$this->assertInstanceOf( 'API_Con_Mngr_Module', $this->api->get_service('google/index.php'), "Unable to get service");

	}

	function test_response_listener_login_form(){
	}

	function test_response_listener_login_authorize(){

	}

	function test_response_listener_login_oauth1(){

	}

	function test_response_listener_login_oauth2(){
		$module = $this->api->get_service('google/index.php');

		//test protocol
		$this->assertEquals("oauth2", $module->protocol, "_response_listener test oauth2: module not oauth2");
		//get code
		$code = 
		ar_print($module->get_access_token(array('code'=>'')));
	}
}
