<?php
/**
 * Class for displaying the user options in the dashboard of blogs.
 * 
 * This must be available to anybody that can edit their profile in the
 * dashboard.
 *
 * @todo make sure subscribers can access this, ie anybody that has basic
 * enough rights to edit their profiles should have access.
 * @author daithi
 * @package api-connection-manager
 */
class API_Connection_Manager_User {

	/**
	 * Construct
	 */
	function __construct() {
		
		//actions
		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('admin_menu', array(&$this, 'dash_menu'));
		
		//look for actions in form submits
		if(@$_REQUEST['action'])
			if(method_exists($this, $_REQUEST['action']))
				add_action('admin_init', array(&$this, $_REQUEST['action']));
	}
	
	public function admin_head(){
		?>
		<style type="text/css">
			#api-connection-manager-user-list .status{
				float: left;
			}
			#api-connection-manager-user-list .widget-title{
				float: left;
				margin-left: 20px;
			}
		</style>
		<?php
	}
	
	/**
	 * Admin menu hook callback.
	 */
	public function dash_menu(){
		add_menu_page("API Connection Manager User", "API Connection Manager User", "manage_options", "api-connection-manager-user", 'api_connection_manager_user');
	}
	
	/**
	 * Action callback. Deletes tokens for a service.
	 * 
	 * @global API_Connection_Manager $API_Connection_Manager 
	 */
	public function delete_tokens(){
		
		//get the main class
		global $API_Connection_Manager;
		if( "API_Connection_Manager"!=get_class($API_Connection_Manager))
			$API_Connection_Manager = new API_Connection_Manager();
		
		$API_Connection_Manager->delete_token($_REQUEST['slug']);
	}
	
	/**
	 * List the services.
	 * 
	 * @global API_Connection_Manager $API_Connection_Manager
	 * @return string returns a html list of services user is connected to. 
	 */
	public function list_services(){
		
		//get the main class
		global $API_Connection_Manager;
		if( "API_Connection_Manager"!=get_class($API_Connection_Manager))
			$API_Connection_Manager = new API_Connection_Manager();
		
		//vars
		$count=1;
		$html = "<div id=\"dashboard-widgets\" class=\"metabox-holder columns-1\">\n";
		$options = $API_Connection_Manager->_get_user_options();
		$states = $API_Connection_Manager->get_service_states();

		/**
		 * loop through services and build html form
		 */
		foreach($states as $slug => $service){
			
			/**
			 * get status icon and params
			 */
			if(!is_wp_error($service)){
				$status = "status_icon_green_12x12.png";
				$data = $service;
			}
			else{
				$data = $API_Connection_Manager->get_service($slug);
				$status = "status_icon_red_12x12.png";
			}//end get status icona  and params
			
			//vars
			$slug_safe = preg_replace("/[\s\W]+/", "_", $slug);
			(@$options[$slug]['refresh_on']) ?
				$offline=true :
				$offline=false;
			$logout = $API_Connection_Manager->get_logout_button($slug);
			
			/**
			 * Html container for this service 
			 */
			$html .= "
				<div id=\"postbox-container-{$count}\" class=\"postbox-container\">
					<div class=\"postbox\">
						<h3>
							<img src=\"".WP_PLUGIN_URL."/api-connection-manager/images/{$status}\" width=\"12\" height=\"12\"/>
							{$data['Name']}</h3>
						<div class=\"inside\">";
			
			//if offline access is allowed
			if(@$data['params']['offline'])
				if($offline)
					$html .= "Offline is enabled <a href=\"{$_SERVER['REQUEST_URI']}&action=api_con_mngr_user_save&slug={$slug}&offline=false\">|disable|</a><br/>";
				else
					$html .= "Offline is disabled <a href=\"{$_SERVER['REQUEST_URI']}&action=api_con_mngr_user_save&slug={$slug}&offline=true\">|enable|</a><br/>";
			//end if offline access is allowed
									
			//print delete access tokens / show login link
			if(is_wp_error($service))
				$html .= $service->get_error_message();
			else $html .= "
				<form method=\"post\">
					<input type=\"hidden\" name=\"action\" value=\"delete_tokens\"/>
					<input type=\"hidden\" name=\"slug\" value=\"{$slug}\"/>
					<input type=\"submit\" value=\"Delete Tokens\"/>
				</form>
				$logout";
			//end delete tokens / show login link
					
			//close container
			$html .= "	</div>
					</div>
				</div>";
			$count++;
		}// end loop build html form
		
		return "{$html}</div>\n";
	}
	
	/**
	 * Form submit action to store the user settings.
	 * 
	 * @global API_Connection_Manager $API_Connection_Manager 
	 */
	public function api_con_mngr_user_save(){
		
		//get the main class
		global $API_Connection_Manager;
		if( "API_Connection_Manager"!=get_class($API_Connection_Manager))
			$API_Connection_Manager = new API_Connection_Manager();
		
		//work new offline state
		if($_REQUEST['offline']=='true')
			$state = true;
		else
			$state = false;
			
		//update user options
		$API_Connection_Manager->_set_refresh_state( array(
			$_REQUEST['slug'] => $state
		) );
	}
}

$dashboard = new API_Connection_Manager_User();
if( !function_exists( 'api_connection_manager_user' ) ):
	/**
	 * Function for displaying service activation/deactivation table.
	 * 
	 * @global API_Connection_Manager_Setup $dashboard 
	 * @package api-connection-manager
	 */
	function api_connection_manager_user(){
		
		global $dashboard;
		if("API_Connection_Manager_User"!=get_class($dashboard))
			$dashboard = new API_Connection_Manager_User();
		
		?>
		<h2><div class="icon32" id="icon-users"></div>API Connection Manager User</h2>
		<div class="clear"></div>
		
		<?php 
		echo $dashboard->list_services();
	}
endif;