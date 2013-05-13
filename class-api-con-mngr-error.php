<?php

/**
 * class-api-con-mngr-error
 *
 * @package api-connection-manager
 * @global array $_SESSION['Api-Con-Errors']
 * @author daithi
 */
class API_Con_Mngr_Error extends WP_Error{

	public $code;
	public $global_key;

	function __construct($msg='', $code='API Connetion Manager', $global_key='Api-Con-Errors'){
		parent::__construct();

		//create global
		if(!@is_array($_SESSION[ $this->global_key ]))
			$_SESSION[ $this->global_key ] = array();

		if(strlen($msg))
			$this->add($msg);
	}

	public function add($msg, $data=array()){
		parent::add( $this->code, $msg, $data);

		$_SESSION[ $this->global_key ][] = $msg;
	}

	public function get_all_errors(){
		return $_SESSION[ $this->global_key ];
	}
}