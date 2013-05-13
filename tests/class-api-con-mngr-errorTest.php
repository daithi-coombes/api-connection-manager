<?php

class API_Con_Mngr_ErrorTest extends WP_UnitTestCase{

	protected $code;
	protected $error;
	protected $global_key;
	protected $msg;

	function setUp(){
		parent::setUp();

		//set params
		$this->code = 'API Connection Manager Test';
		$this->global_key = 'Api-Con-Errors-Test';
		$this->msgs = array("Unit test error message", "2nd unit test error message");

		//build object
		$this->error = new API_Con_Mngr_Error(
			$this->msgs[0], 
			$this->code, 
			$this->global_key
		);
	}

	function tearDown(){
		unset($_SESSION[ $this->global_key ]);
	}

	function test_add(){

		//add 2nd test message
		$this->error->add($this->msgs[1]);

		//test global
		$this->assertSame($this->msgs, $_SESSION[$this->global_key], "session global not updated");

		//test field
		$this->assertEquals($this->msgs, $this->error->errors[ $this->code ], "WP_Error::errors not updated");
	}

	function test_get_all_errors(){
		
		$this->assertSame(array($this->msgs[0]), $this->error->get_all_errors());
	}
}