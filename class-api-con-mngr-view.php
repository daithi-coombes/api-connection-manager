<?php
/**
 * The view class for handling all of the api-con-mngr's html data. Used when
 * the api manager has to show pages in wp-admin/admin-ajax.php.
 * 
 * Usage:
 * ======
 * //construct and set
 * $obj = new API_Con_Mngr_View();
 * $obj->body[] = "<h1>My lovely header</h1>";
 * $obj->body[] = "<p>no more hello world</p>";
 * $obj->body[] = "<p>hellow universe ;)</p>";
 *
 * //to print html to client
 * $obj->get_html();
 * 
 * //to return html as string
 * $html = $obj->get_html( false );
 * 
 * @author daithi
 */
class API_Con_Mngr_View{

	/** @var array An array of body elements */
	public $body = array();
	/** @var array An array of footer elements */
	public $footer = array();
	/** @var array An array of header string to be printed */
	public $headers = array();
	/** @var string The full html to be printed */
	private $html = "";
	
	/**
	 * Construct
	 */
	function __construct(){
		;
	}
	
	/**
	 * Returns the document <head> 
	 * @return string
	 */
	public function get_head(){
		;
	}
	
	/**
	 * Builds the body array and returns the html
	 * @return string
	 */
	public function get_body(){
		return implode("\n", $this->body);
	}
	
	/**
	 * Builds the footer array and returns the html
	 * @return string 
	 */
	public function get_footer(){
		
	}
	
	/**
	 * Will return or print html and die. The default is to die( $html );
	 * @param boolean $die Default true. Whether to die or return html
	 * @return string 
	 */
	public function get_html( $die=true ){
		
		//print any headers
		foreach($this->headers as $header)
			header($header);
		
		//build html
		$this->html .= $this->get_head();
		$this->html .= $this->get_body();
		$this->html .= $this->get_footer();
		
		//print or return html
		if($die)
			die($this->html);
		return $this->html;
	}
	
}