<?php

class API_Con_Mngr_ViewTest extends WP_UnitTestCase{

	protected $view;

	function setUp(){
		parent::setUp();

		if(!defined("DOING_AJAX"))
			define('DOING_AJAX', true);

		//load view class
		$this->slug = get_option('test_slug');
		require("../api-con-mngr-modules/{$this->slug}");
		$this->view = new API_Con_Mngr_View();

		//sign in user
		$this->user = wp_signon(array(
			'user_login' => 'admin',
			'user_password' => 'password'));
		wp_set_current_user($this->user->ID);
	}

	function test_get_head(){
		$this->assertInternalType('string', $this->view->get_head());
	}

	function test_get_footer(){
		$this->assertInternalType('string', $this->view->get_footer());
	}

	function test_get_html(){
		$this->assertInternalType('string', $this->view->get_html(false));
	}
}

