<?php

/**
 * Modules should extend this class.
 * 
 * Allows the over-riding of headers and the parsing of parameters. 
 */
class API_Con_Mngr_Module{
	
	/** @var string Oauth1 autorize url */
	public $autorize_url=false;
	/** @var string The uri for displaying a login link */
	public $login_uri=false;
	/** @var string The name of the module */
	public $Name=false;
	/** @var string Oauth1 token */
	public $oauth_token=false;
	/** @var array An array of static params */
	public $params=array();
	/** @var string The current protocol used (oauth, custom, etc) */
	public $protocol="";
	/** @var string The slug of the current login */
	public $slug="";
	
	function construct($params=array(), $options=array()){
		
		$this->set_params($params);
		$this->set_options($options);
	}
	
	public function get_authorize_url(){
		;
	}
	
	/**
	 * Returns a link to login to this service
	 * @return string Html anchor
	 */
	public function get_login_button(){
		
		
		//switch through protocols. These login uri's are set in API_Connection_Manager::_get_module();
		switch ($this->protocol) {
			case 'oauth1':
				return "<a href=\"{$this->login_uri}\">{$this->Name}</a>";
				break;

			default:
				return $this->_error("Please override API_Con_Mngr_Module::get_login_button in you plugin");
				break;
		}
	}
	
	/**
	 * Get oauth1 request token 
	 */
	public function get_request_token(){
		;
	}
	
	/**
	 * Set the header.
	 * 
	 * @param API_Con_Mngr_Header $header
	 * @return \API_Con_Mngr_Header 
	 */
	public function set_header( API_Con_Mngr_Header $header){
		return $header;
	}
	
	/**
	 * Set the module options.
	 * @param array $options Associative array of options
	 */
	public function set_options( array $options ){
		$this->options = $options;
	}
	
	/**
	 * Set params.
	 * Will set fields that have the same param name.
	 * @param array $params An associative array of parameters for this module
	 */
	public function set_params( array $params ){
		
		//if param is a field
		foreach($params as $key=>$val)
			if(isset($this->{$key}))
				$this->{$key} = $val;
				
		//set raw param array
		$this->params = $params;
	}
	
	/**
	 * Error handling
	 * @param string $msg
	 * @return \WP_Error 
	 */
	private function _error( $msg ){
		return new WP_Error('API_Con_Mngr_Module', $msg);
	}
}

/**
 * The header datatype for the API Connection Manager. 
 */
class API_Con_Mngr_Header{
	
	//the current header params
	public $headers;
	
	/**
	 * Return the current $headers as an array
	 * @return array 
	 */
	public function header_to_array(){
		
	}
	
	/**
	 * Return the current $headers as string
	 * @return string 
	 */
	public function array_to_header(){
		
	}
}

/**
 * The param datatype, will include methods for formating/encoding and parsing
 * parameters for the service. This datatype forms the body of the DTO sent to
 * the service. 
 */
class API_Con_Mngr_Param{
	
	public $params = array();
	
	function __construct( array $params ){
		$this->params = $params;
	}
}