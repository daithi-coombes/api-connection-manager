<?php

/**
 * class-api-con-mngr-error
 *
 * @package api-connection-manager
 * @author daithi
 */
class API_Con_Mngr_Error extends WP_Error{

	/**
	 * extends WP_Error::__construct.
	 * Note: takes message as first param, all other params are optional
	 * @param string $msg  The error message
	 * @param string $code Default 'API Connection Manager'
	 * @param mixed $data Default array()
	 */
	function __construct($msg, $code="API Connection Manager", $data=array()){
		$this->code = $code;
		parent::__construct($this->code, $msg, $data);
	}

	/**
	 * Log an error message
	 * @param  boolean $die Default false. If true will die() the error message
	 * else will return the error message as string
	 * @return string       The error message, but only if $die=false (default)
	 */
	function get_error_message($die=false){
		if($die)
			die( parent::get_error_message($this->code) );
		else
			return parent::get_error_message($this->code);
	}

}