<?php
/**
 * Description of class-api-connection-manager-user
 *
 * @author daithi
 */
class API_Connection_Manager_User{

	function __construct(){
		
		//check for actions
		$action = @$_REQUEST['api_con_user_action'];
		if($action)
			if(method_exists($this, $action))
				$this->$action();
		add_shortcode('API_Con_User_Connections', array(&$this, 'do_shortcode'));
		
		//add menu page
		add_action('admin_menu', array(&$this, 'dash_menu'));
	}

	/**
	 * Print the user connections
	 * 
	 * @global API_Connection_Manager $API_Connection_Manager
	 */
	public function do_shortcode(){
		
		//vars
		global $API_Connection_Manager;
		global $current_user;
		$current_user = wp_get_current_user();
		$count=1;
		$html = "<div>\n";
		$meta = get_option("API_Con_Mngr_Module-connections", array());
		$modules = $API_Connection_Manager->get_services();
		
		//check user is logged in
		if(!is_user_logged_in()){
			print "<h3>API Connection Manager Error</h3>\n";
			print "<p>You must be logged in to connect to services</p>\n";
			return;
		}
		
		//loop through modules
		foreach($modules as $slug=>$module){
			
			/**
			 * get status icon and params
			 */
			if(@$meta[$slug][$current_user->ID]){
				$valid = true;
				$status = "status_icon_green_12x12.png";
			}
			else{
				$valid = false;
				$status = "status_icon_red_12x12.png";
			}
			//end get status icon and params
			
			/**
			 * Start module html
			 */
			$html .= "<div id=\"postbox-container-{$count}\" class=\"postbox-container\">
					<div class=\"postbox\">
						<h3>
							<img src=\"".WP_PLUGIN_URL."/api-connection-manager/images/{$status}\" width=\"12\" height=\"12\"/>
							{$module->Name}</h3>
						<div class=\"inside\">";
			//print delete access tokens / show login link
			if($valid)
				$html .= "
					<form method=\"post\">
						<input type=\"hidden\" name=\"api_con_user_action\" value=\"disconnect\"/>
						<input type=\"hidden\" name=\"slug\" value=\"{$slug}\"/>
						<input type=\"submit\" value=\"Disconnect\"/>
					</form>";
			else
				$html .= "<p>You are not connected to {$module->Name}</p>
					<p><a href=\"" . $module->get_login_button(__FILE__, array(&$this, 'connect_user', false)) . "\" target=\"_new\">
						Connect your wordpress account with {$module->Name}</a>";
					
			//close container
			$html .= "	</div>
					</div>
				</div>";
			$count++;
		}
		//end loop through modules
		
		print $html;
	}
	
	/**
	 * Connects the logged in user with the uid for the required module. Is
	 * called in API_Connection_Manager::_response_listener()
	 * 
	 * @global API_Connection_Manager $API_Connection_Manager
	 * @param stdClass $dto The dto from the provider response
	 */
	public function connect_user( $dto ){
		
		global $API_Connection_Manager;
		$module = $API_Connection_Manager->get_service($dto->slug);
		$uid = $module->get_uid();
		$module->login($uid);
	}
	
	public function dash_menu(){
		add_menu_page("User Connections", "API Connection Manager - User Connections", "manage_options", "api-connection-manager-user", array(&$this, 'do_shortcode'));
	}
	
	/**
	 * Disconnect a user from a service
	 * @global API_Connection_Manager $API_Connection_Manager
	 */
	public function disconnect(){
		
		global $API_Connection_Manager;
		global $current_user;
		
		$current_user = wp_get_current_user();
		$user_id = $API_Connection_Manager->get_current_user()->ID;
		$meta = get_option("API_Con_Mngr_Module-connections", array());
		unset($meta[$_REQUEST['slug']][$user_id]);
		if(empty($meta[$_REQUEST['slug']]))
			unset($meta[$_REQUEST['slug']]);
		update_option("API_Con_Mngr_Module-connections", $meta);
	}
	
}

$API_Con_User = new API_Connection_Manager_User();