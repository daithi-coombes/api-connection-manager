<?php
/**
 * Description of class-api-connection-mngr-log
 *
 * @author daithi
 */
class API_Con_Mngr_Log {

	/** @var string The full location of the log file */
	public $location=null;
	/** @var string The mode to open the file */
	public $mode;
	/** @var resource The file handle */
	private $fp=null;
	
	/**
	 * Constructor
	 * @param string $location Full path including filename. Default is:
	 * wp-content/uploads/api-con-mngr.txt
	 * in the same folder as this class.
	 * @param string $mode Default a+ The mode to open the log file
	 */
	function __construct(  $location=null, $mode=null  ){
		
		($location) ?
			$this->location = $location :
			$this->location = dirname(dirname(dirname(__FILE__))) . "/uploads/api-con-mngr.txt" ;
		($mode) ?
			$this->mode = $mode :
			$this->mode = "a+";
		
		$this->set_fp();
	}
	
	/**
	 * Clears the log file 
	 */
	public function clear(){
		
		//close handle
		@fclose($this->fp);
		$this->set_fp($this->location, "w");
		fclose($this->fp);
		$this->set_fp($this->location);
	}
	
	/**
	 * Returns the current file handle.
	 * @uses $this->fp
	 * @return resource 
	 */
	public function get_fp(){
		return $this->fp;
	}
	
	/**
	 * Writes to the log file.
	 * Lines are in the format:
	 * [$time] \t $code: $message \n
	 * @param string $msg The message to write
	 * @param string $code Default 0. The error code to log
	 * @return mixed Returns the num of bytes written on success or FALSE on
	 * failure. 
	 */
	public function write( $msg, $code="0" ){
		
		$time = time();
		$line = "[{$time}]\t{$code}: {$msg}\n";
		return fwrite($this->fp, $line);
	}
	
	/**
	 * Set the file handle to the location provided. Returns the file handle.
	 * @param string $location Full path to file (inc filename)
	 * @param string $mode Default a+ The mode to open the file with.
	 * @return resource 
	 */
	public function set_fp( $location=null, $mode=null){
		if($location)
			$this->location = $location;
		if($mode)
			$this->mode = $mode;
		
		@fclose($this->fp);
		$this->fp = fopen($this->location, $this->mode);
		return $this->fp;
	}
}