<?php
require_once('class-api-connection-managerTest.php');

class API_Con_Mngr_ModuleTest extends WP_UnitTestCase{

	protected $module;
	public $user;

	function setUp(){
		require('../api-con-mngr-modules/google/index.php');
		$this->module = $module;
		$this->user = wp_signon(array(
			'user_login' => 'admin',
			'user_password' => 'password'));
		$this->module->user = $this->user;
	}

	function tearDown(){

	}

	function test___construct(){
		$this->assertEquals('google/index.php', $this->module->slug, "no module slug");
		$this->module->get_params();
		$this->assertInternalType('array', $this->module->options, "no options for module. This could be related to ::get_params()");

	}

	function test_do_callback(){
		$dto = new stdClass();
		$dto->callback = array(
			'file' => __FILE__,
			'callback' => serialize('API_Con_Mngr_Module_Callback')
			);
		$this->assertTrue($this->module->do_callback($dto), "Failed testing callback function");
		$foo = new API_Con_Mngr_Module_Callback();
		$dto->callback['callback'] = serialize(array('API_Con_Mngr_Module_Callback','foo'));
		$this->assertTrue($this->module->do_callback($dto), "Failed testing callback method");
	}

	function test_oauth_sign_request(){
		$this->module->consumer = new OAuthConsumer('foo', 'bar', 'http://www.example.com');
		$this->module->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->assertInstanceOf('OauthRequest', $this->module->oauth_sign_request('http://www.example.com'));
	}

	function test_get_access_token(){}

	function test_get_authorize_url(){}

	function test_get_connections(){}

	function test_get_login_button(){
		$this->assertInternalType('string', $this->module->get_login_button());
	}

	function test_get_params(){

		$user = wp_signon(array(
			'user_login' => 'admin',
			'user_password' => 'password'));
		$this->module->user = $user;
		$params = $this->module->set_params(array(
			'access_token' => 'foo',
			'refresh_token' => 'bar'));
	}

	function test_get_request_token(){}

	function test_login(){}

	function test_login_form_callback(){}

	function test_login_connect(){}

	function test_parse_dto(){
		$dto = new stdClass();
		$dto->response = array(
			'access_token' => 'foo'
			);
		$this->module->parse_dto($dto);

		$this->assertEquals('foo', $this->module->access_token);
	}

	function test_parse_response(){

		//test json response
		$response = array(
			'headers' => array(
				'content-type' => 'json'
				),
			'body' => json_encode(array('status' => 'ok'))
			);
		$res = $this->module->parse_response($response);
		$this->assertInstanceOf('stdClass', $res);

		//test normal response
		$response['headers']['content-type'] = 'text/plain';
		$response['body'] = "status=ok&foo=bar";
		$res = $this->module->parse_response($response);
		$this->assertInternalType('array', $res);

		//test http error
		$response = new WP_Error('API Con Mngr Module Test', 'testing parse_response()');
		$res = $this->module->parse_response($response);
		$this->assertInstanceOf('WP_Error', $res);
	}

	function test_response(){}

	function test_set_connections(){}

	function test_set_details(){
		$this->module->set_details(array(
			'access_token' => 'foo'));
		$this->assertEquals('foo', $this->module->access_token);
	}

	function test_set_options(){}

	function test_set_params(){}

	function test_wp_login(){}

	function test_construct_options(){}

	function test_options(){}

	function test_get_slug(){}

}

function foo(){
	print "foo";
}

class API_Con_Mngr_Module_Callback{
	function foo(){
		return true;
	}
}
function API_Con_Mngr_Module_Callback(){
	return true;
}