<?php

/**
 * Modules should extend this class.
 * 
 * Allows the over-riding of headers and the parsing of parameters. 
 */
class API_Con_Mngr_Module{
	
	/**
	 * Set the header.
	 * 
	 * @param API_Con_Mngr_Header $header
	 * @return \API_Con_Mngr_Header 
	 */
	public function set_header( API_Con_Mngr_Header $header){
		return $header;
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