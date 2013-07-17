<?php

/**
 * 
 * This class extends WP_Error and is used for error reporting by the
 * API Connection Manager package.
 *
 * @package api-connection-manager
 * @global array $_SESSION['Api-Con-Errors']
 * @author daithi
 */
class API_Con_Mngr_Error extends WP_Error{
	
	/** @var string The error code. Default 'API Connection Manager' */
	public $code;
	/** @var string The key used for $_SESSION[ $global_key ] */
	public $global_key;

	/**
	 * Constructs parent and sets params. Sets the global session
	 * @uses  array $_SESSION[ API_Con_Mngr_Error::global_key ] Global array
	 * for sessions
	 * @param string $msg        The error message
	 * @param string $code       Default 'API Connection Manager'
	 * @param string $global_key Default 'Api-Con-Errors'
	 */
	function __construct( $msg = '', $code = 'API Connetion Manager', $global_key = 'Api-Con-Errors' ){

		parent::__construct();
		$this->code = $code;
		$this->global_key = $global_key;

		//create global
		if ( !@is_array( $_SESSION[ $this->global_key ] ) )
			$_SESSION[ $this->global_key ] = array();

		if ( strlen( $msg ) ){
			$this->add( $msg );
		}

		return $this;
	}

	/**
	 * Adds a message to the errors array and the global $_SESSION array
	 * @uses array WP_Error::errors
	 * @uses array $_SESSION[ API_Con_Mngr_Error::global_key ]
	 */
	public function add_error( $msg ){
		parent::add( $this->code, $msg );
		$_SESSION[ $this->global_key ][] = $msg;
	}

	/**
	 * Clear the errors stack. Removes errors from parent and $_SESSION
	 * @return void
	 */
	public function clear(){
		unset( $_SESSION[ $this->global_key ] );
		$this->errors = array();
	}

	/**
	 * Returns all errors. Doesn't clear the errors stack.
	 * @return array Returns an array of errors.
	 */
	public function get_all_errors(){

		//get errors param
		( @$this->errors[ $this->code ]) ?
			$errors = $this->errors[ $this->code ] :
			$errors = array();
		( @$_SESSION[ $this->global_key ] ) ?
			$globals = $_SESSION[ $this->global_key ] :
			$globals = array();
		$res = array_unique( array_merge( $errors, $globals ) );

		return $res;
	}

	/**
	 * Gets the first error message
	 * @param  boolean $action Default false. The private method to call.
	 * @return string          The first error message
	 */
	public function get_error_message( $action = false ){

		//if action called
		if ( $action ){
			$action = '_' . $action;
			if ( method_exists( $this, $action ) )
				return $this->$action();
		}

		//else return last error
		return parent::get_error_message();
	}

	/**
	 * Throws exception with first error as message.
	 */
	private function _die(){

		$msg = parent::get_error_message();
		$this->clear();

		throw new Exception( $msg );
	}

	/**
	 * Prints js to reload page, or call parent window.
	 */
	private function _notify_parent(){

		//default print js
		$res = '<script type="text/javascript">
			if (window.opener){
				window.opener.location.reload();
				window.close();
			}
			else
				window.location.reload();
		</script>';

		return $res;
	}
}