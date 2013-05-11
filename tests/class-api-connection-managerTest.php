<?php
require_once('bootstrap.php');

class API_Connection_ManagerTest extends WP_UnitTestCase{
	
	protected $api;
	protected $slug;

	function setUp(){
		parent::setUp();

		if(!defined("DOING_AJAX"))
			define('DOING_AJAX', true);
		$this->slug = get_option('test_slug');
		$_GET = $_REQUEST = array(
			'action' => 'api_con_mngr',
			'slug' => $this->slug
			);

		$this->api = new API_Connection_Manager();
		$_SESSION['Api-Con-Errors'] = array("test error");
	}

	function tearDown(){
		delete_option('api-connection-manager');
	}

	function test_response_listener_bootstrap(){

		//get dto
		$this->assertInstanceOf( 'stdClass', $this->api->get_dto(), "Unable to get dto" );
	}

	function test_admin_notices(){
		ob_start();
		//test error - session declared in this::setUp()
		$this->assertTrue( $this->api->admin_notices(), "Not finding any errors" );
		//make sure notices were reset in last call
		$this->assertFalse( $this->api->admin_notices(), "Errors not being reset" );
		ob_end_clean();
	}

	function test_delete_user(){

	}

	function test_get_current_user(){
		$this->assertInstanceOf('WP_User', $this->api->get_current_user());
	}

	function test_get_service(){
		$this->assertInstanceOf( 'API_Con_Mngr_Module', $this->api->get_service('google/index.php'), "Unable to get service");
	}

	function test_get_services(){
		$this->assertInternalType('array', $this->api->get_services());
	}

	function test_log(){}

	function test_error(){}

	function test__error(){}

	function test__get_installed_services(){}

	function test__get_options(){
		$this->greaterThan(1, count($this->api->_get_options()));
	}

	function test__module_activate(){
		$this->api->_module_deactivate( $this->slug );
		$this->api->_module_activate( $this->slug );
		$active = $this->api->get_services('active');
		$inactive = $this->api->get_services('inactive');

		$this->assertArrayHasKey($this->slug, $active);
		$this->assertArrayNotHasKey($this->slug, $inactive);
	}

	function test__module_deactivate(){
		$this->api->_module_deactivate( $this->slug );
		$inactive = $this->api->get_services('inactive');
		$active = $this->api->get_services('active');

		$this->assertArrayHasKey($this->slug, $inactive);
		$this->assertArrayNotHasKey($this->slug, $active);
	}

	function test__set_refresh_state(){}

	function test__url_query_append(){}

	function test__service_login_authorize(){}

	function test__get_dto(){
		$this->assertInstanceOf('stdClass', $this->api->get_dto());
	}

}