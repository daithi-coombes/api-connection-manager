<?php

class API_Connection_Manager_SetupTest extends WP_UnitTestCase{
	protected $api;
	protected $setup;
	protected $slug;

	function setUp(){
		parent::setUp();

		if(!defined("DOING_AJAX"))
			define('DOING_AJAX', true);

		//set up main class
		global $API_Connection_Manager;
		$this->api = $API_Connection_Manager;
		$this->slug = get_option('test_slug');

		//load setup class
		require("../api-con-mngr-modules/{$this->slug}");
		$this->setup = new API_Connection_Manager_Setup();
		$this->setup->prepare_items();

		//sign in user
		$this->user = wp_signon(array(
			'user_login' => 'admin',
			'user_password' => 'password'));
		wp_set_current_user($this->user->ID);
		error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
	}

	function test___construct_wp_list(){
		$this->setup->construct_wp_list();
		$this->assertEquals(array(
			'plural' => 'services',
			'singular' => 'service',
			'ajax' => null,
			'screen' => null
		), $this->setup->_args);
	}

	function test_admin_head(){
		$this->assertInternalType('string', $this->setup->admin_head(false));
	}

	function test_column_default(){
		$item = array('description' => 'foo');

		$this->assertEquals('foo', $this->setup->column_default($item, 'description'));
	}

	function test_column_title(){
		$this->assertInternalType('string', $this->setup->column_title($this->items[0]));
	}

	function test_get_bulk_actions(){
		$this->assertEquals(array(
			'activate' => 'Activate',
			'deactivate' => 'Deactivate'
			), $this->setup->get_bulk_actions()
		);
	}

	function test_get_service_options(){
		$this->assertInternalType('string', $this->setup->get_service_options());
	}

	function test_prepare_items(){}

	function test_save_service(){
		$_REQUEST['service'] = $this->slug;
		$_REQUEST['client_id'] = 'test_save_service';
		$this->setup->save_service();
		$this->assertEquals('test_save_service', $this->api->get_service($this->slug)->client_id);
	}

	function test_single_row(){}

	function test_column_cb(){
		$this->assertInternalType('string', $this->setup->column_cb($this->setup->items[0]));
	}

}