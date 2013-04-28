<?php

/**
 * class-api-con-mngr-error
 *
 * @package api-connection-manager
 * @author daithi
 */
class API_Con_Mngr_Error extends WP_Error{

	/**
	 * The WP_Error::code to use. Default 'API Connection Manager'
	 * @var string
	 */
	protected $code;

	/**
	 * extends WP_Error.
	 * Note: takes message as first param, all other params are optional
	 * @param string $msg  The error message
	 * @param string $code Default 'API Connection Manager'
	 * @param mixed $data Default array()
	 */
	function __construct($msg, $action=null, $code="API Connection Manager", $data=array()){
		$this->code = $code;
		parent::__construct($code, $msg);

		if($action){
			$method = "_" . $action;
			if(method_exists($this, $action))
				$this->action();
		}
	}

	/**
	 * Get an error message.
	 * The action param will specify whether to die(), or use js to report 
	 * message to calling window etc. To add actions, create a method
	 * ::_$action (append an underscore to action name).
	 * @param  string $action Default 'die'. The action to call
	 * @return string       The error message, but only if $die=false (default)
	 */
	function get_error_message($action='die'){

		if($action){
			$method = "_" . $action;
			if(method_exists($this, $method))
				$this->$method();
		}
	}

	/**
	 * get_error_message action.
	 * Will die() error message to screen
	 * @return void
	 */
	function _die(){
		die( parent::get_error_message() );
	}
}