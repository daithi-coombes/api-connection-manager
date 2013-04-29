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
		$_SESSION['Api-Con-Errors'] = array("test error");
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

	function test_admin_notices(){
		//test error - session declared in this::setUp()
		$this->assertTrue( $this->api->admin_notices() );
		//make sure notices were reset in last call
		$this->assertFalse( $this->api->admin_notices() );
	}
}
