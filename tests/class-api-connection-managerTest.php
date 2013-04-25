<?php
require_once('bootstrap.php');

class API_Connection_ManagerTest extends PHPUnit_Framework_TestCase{
	
	protected $api;

	function setUp(){
		parent::setUp();

		define('DOING_AJAX', true);
		$_GET = $_REQUEST = array(
			'action' => 'api_con_mngr',
			'slug' => 'autoflow'
			);

		$this->api = new API_Connection_Manager();
	}

	function test_response_listener_bootstrap(){

		//get dto
		$this->assertInstanceOf( 'stdClass', $this->api->get_dto(), "Unable to get dto" );
		//get module API_Con_Mngr_Module
		$this->assertInstanceOf( 'API_Con_Mngr_Module', $this->api->get_service('autoflow'), "Unable to API_Con_Mngr_Module");
	}

	function test_response_listener_login_form(){
	}

	function test_response_listener_login_authorize(){

	}

	function test_response_listener_login_oauth1(){

	}

	function test_response_listener_login_oauth2(){
		
	}

}