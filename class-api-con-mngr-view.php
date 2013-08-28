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
 * @package api-connection-manager
 */
class API_Con_Mngr_View{

	/** @var array An array of body elements */
	public $body = array();
	/** @var array An array of footer elements */
	public $footer = array();
	/** @var array An array of header string to be printed */
	public $headers = array();
	/** @var string The full html to be printed */
	private $html = '';
	/** @var string The location of the bootstrap root */
	private $bootstrap_root;
	
	/**
	 * Construct
	 */
	function __construct(){
		
		//set default params
		$this->bootstrap_root = WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/vendor/bootstrap';
	}
	
	/**
	 * Returns the document <head> 
	 * @return string
	 */
	public function get_head(){
		return '<!DOCTYPE HTML>
		<html>
		<head>
			<title>' . get_bloginfo( 'name' ) . ' :: API Connection Manager</title>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<link rel="stylesheet" href="' . $this->bootstrap_root . '/css/bootstrap.min.css" media="screen"/>
			<style type="text/css">
				body{
					margin: 40px auto;
				}
				.container-fluid{
					max-width: 500px;
					padding: 19px 29px 29px;
					margin: 0 auto 20px;
					background-color: #fff;
					border: 1px solid #e5e5e5;
					-webkit-border-radius: 5px;
					-moz-border-radius: 5px;
					border-radius: 5px;
					-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
					-moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
					box-shadow: 0 1px 2px rgba(0,0,0,.05);
				}
			</style>
		</head>
		<body>';
	}
	
	/**
	 * Builds the body array and returns the html
	 * @return string
	 */
	public function get_body(){
		return '<div class="container-fluid">
				<h1>' . get_bloginfo( 'name' ) . '</h1>
				<h3>API Connection Manager</h3>
				' . implode( ' ', $this->body ) . '
			</div>';
	}
	
	/**
	 * Builds the footer array and returns the html
	 * @return string 
	 */
	public function get_footer(){
		return '
			<script src="http://code.jquery.com/jquery.js" type="text/javascript"></script>
			<script src="' . $this->bootstrap_root . '/js/bootstrap.min.js" type="text/javascript"></script>
			<script src="' . $this->bootstrap_root . '/js/ReactiveRaven-jqBootstrapValidation-d66d033/jqBootstrapValidation.js" type="text/javascript"></script>
				<script type="text/javascript">
					$(function () { $(\'input,select,textarea\').not(\'[type=submit]\').jqBootstrapValidation(); } );
				</script>
			</body>
		</html>';
	}
	
	/**
	 * Will return or print html and die. The default is to die( $html );
	 * @param boolean $die Default true. Whether to die or return html
	 * @return string 
	 */
	public function get_html( $die = true ){
		
		//print any headers
		foreach ( $this->headers as $header )
			header( $header );
		
		//build html
		$this->html .= $this->get_head();
		$this->html .= $this->get_body();
		$this->html .= $this->get_footer();
		
		//print or return html
		if ( $die )
			die( $this->html );
		return $this->html;
	}
	
}