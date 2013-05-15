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


	public function clear(){
		unset($_SESSION[ $this->global_key ]);
		$this->errors = array();
	}

	public function get_all_errors(){

		//get errors param
		(@$this->errors[ $this->code ]) ?
			$errors = $this->errors[ $this->code ] :
			$errors = array();
		(@$_SESSION[ $this->global_key ]) ?
			$globals = $_SESSION[ $this->global_key ] :
			$globals = array();
		$res = array_unique(array_merge($errors, $globals));

		return $res;
	}

	public function get_error_message($action=false){

		//if action called
		if($action){
			$action = "_".$action;
			if(method_exists($this, $action))
				return $this->$action();
		}

		//else return last error
		return parent::get_error_message();
	}

	private function _die(){

		$msg = parent::get_error_message();
		$this->clear();

		throw new API_Con_Mngr_Exception($msg);
	}

	private function _notify_parent(){

		//default print js
		$res = "<script type=\"text/javascript\">
			if(window.opener){
				window.opener.location.reload();
				window.close();
			}
		</script>";

		return $res;
	}
}