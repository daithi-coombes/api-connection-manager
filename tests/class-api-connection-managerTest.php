<?php
require_once('../class-api-connection-manager.php');

class API_Connection_ManagerTest extends PHPUnit_Framework_TestCase{
	
	function test_response_listener(){

		define('DOING_AJAX', true);
		$obj = new API_Connection_Manager();
		$_GET = array(
			'action' => 'api_con_mngr',
			'slug' => 'autoflow'
			);

		$this->assertTrue( $obj->_response_listener() !== false );
	}
	
}