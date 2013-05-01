<?php
require_once('class-api-connection-managerTest.php');

class API_Con_Mngr_ModuleTest extends WP_UnitTestCase{

	protected $module;

	function setUp(){
		require('../api-con-mngr-modules/google/index.php');
		$this->module = $module;
	}

	function tearDown(){

	}

	function test___construct(){
		$this->assertEquals('google/index.php', $this->module->slug, "no module slug");
		$this->module->get_params();
		$this->assertInternalType('array', $this->module->options, "no options for module. This could be related to ::get_params()");

	}

	function test_do_callback(){
		
	}
}