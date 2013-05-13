<?php

class API_Con_Mngr_ErrorTest extends WP_UnitTestCase{

	protected $code;
	protected $error;
	protected $global_key;
	protected $msg;

	function setUp(){
		parent::setUp();
		$this->code = 'API Connection Manager Test';
		$this->global_key = 'Api-Con-Errors-Test';
		$this->msg = "Unit test error message";
		$this->error = new API_Con_Mngr_Error($this->msg, $this->code, $this->error);
	}

	function tearDown(){
		unset($_SESSION[ $this->global_key ]);
	}

	function test_add(){
		$msg = "2nd unit test error message";
		$this->error->add($msg);

	}

	function test_get_all_errors(){
		$this->assertEquals(array($this->msg), $this->error->get_all_errors());
	}

}