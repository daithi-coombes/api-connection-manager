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
		$this->code = $code;
		$this->global_key = $global_key;

		//create global
		if(!@is_array($_SESSION[ $this->global_key ]))
			$_SESSION[ $this->global_key ] = array();

		if(strlen($msg)){
			$this->add($msg);
		}
	}

	/**
	 * Adds a message to the errors array and the global $_SESSION array
	 * @uses array WP_Error::errors
	 * @uses array $_SESSION[ API_Con_Mngr_Error::global_key ]
	 */
	public function add($msg, $data=array()){
		parent::add( $this->code, $msg );
		$_SESSION[ $this->global_key ][] = $msg;
	}


	public function get_all_errors(){

		//get errors param
		$errors = $this->errors[ $this->code ];
		$globals = $_SESSION[ $this->global_key ];
		$res = array_unique(array_merge($errors, $globals));

		return $res;
	}
}