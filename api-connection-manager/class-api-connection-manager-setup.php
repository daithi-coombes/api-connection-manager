<?php

/**
 * Class for handling the dashboard and network admin pages for the API
 * Connection Manager
 *
 * @todo add nonces to forms
 * @author daithi
 * @package api-connection-manager
 */
class API_Connection_Manager_Setup extends WP_List_Table{
	
	/** @var integer The total number of services. Defined in this::get_data() */
	public $total;
	/** @var integer Total active services. Defined in this::get_data() */
	public $total_active;
	/** @var integer Total inactive services. Defined in this::get_data() */
	public $total_inactive;
	
	/**
	 * Construct.
	 */
	function __construct(){
		
		//process actions on the services before anything else
		$this->process_bulk_actions();
		
		//vars
        global $status, $page;
		get_current_screen();
        
		//register admin pages
		if(is_multisite())	//if multisite then put settings in network admin
			add_action('network_admin_menu', array(&$this, 'dash_menu'));
		else	//if not then put settings in dashboard
			add_action('admin_menu', array(&$this, 'dash_menu'));
				
		//actions
		add_action('admin_head', array(&$this,'admin_head'));
		
	} //end construct()
	
	public function __construct_wp_list(){
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'service',     //singular name of the listed records
            'plural'    => 'services',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );		
	}
	
	/**
	 * Activate modules. 
	 */
	public function activate(){
		
		global $API_Connection_Manager;
		if( "API_Connection_Manager"!=get_class($API_Connection_Manager))
			$API_Connection_Manager = new API_Connection_Manager();
		if(@$_REQUEST['service'])
			$API_Connection_Manager->_module_activate( $_REQUEST['service'] );
	}
	
	/**
	 * Print inline styles and scripts the html head tag.  
	 */
	public function admin_head(){
		?>
		<style type="text/css">
			.api-con-list-services li{
				border : 1px solid;
				padding: 10px;
			}
			.api-con-list-services li ul li{
				border: none;
			}
			.api-con-list-services .widget .module-inside{
				margin: 15px 10px;
			}
		</style>
		<script type="text/javascript">
			var apiConMngr = {
				toggle_settings : function(id){
					jQuery('.api-con-mng-settings').hide();
					var show = '#api-settings-'+id;
					console.log(show);
					jQuery(show).show();
					return false;
				}
			};
		</script>
		<?php
	} // end admin_head()
	
    /**
	 * Defines the default columns
	 * 
     * @see WP_List_Table::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    public function column_default($item, $column_name){

        switch($column_name){
            case 'title':
            case 'description':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }
    
	/**
	 * Add actions to the title column.
	 * 
	 * @param type $item
	 * @return type 
	 */
	public function column_title( $item ){
		$id = preg_replace("/[\s\W]+/", "_", $item['ID']);
		$actions['inline hide-if-no-js'] = '<a href="#" onclick="apiConMngr.toggle_settings(\''.$id.'\')" class="editinline" title="' . esc_attr( __( 'Edit this item inline' ) ) . '">' . __( 'Settings' ) . '</a>';
		return sprintf('%1$s %2$s', $item['title'], $this->row_actions($actions) );
	}
	
	/**
	 * Adds the dashboard menu. 
	 */
	public function dash_menu(){
		add_menu_page("API Connection Manager", "API Connection Manager", "manage_options", "api-connection-manager-setup", 'api_connection_manager_dash');
		add_submenu_page("api-connection-manager-setup", "Serivce Options", "Service Options", "manage_options", "api-connection-manager-service", 'api_connection_manager_dash_options');
	} //end dash_menu
	
	/**
	 * Deactivate modules.
	 */
	public function deactivate(){
		
		$api = new API_Connection_Manager();
		if(@$_REQUEST['service'])
			$api->_module_deactivate( $_REQUEST['service'] );
	}
	
	/**
	 * Defines bulk actions for the WP_List_Table class.
	 * 
	 * Overrides WP_List_Table::get_bulk actions.
	 *
	 * @return string 
	 */
    public function get_bulk_actions() {
		
		$status = $_GET['module_status'];
		
		if('active'==@$status)
			$actions = array(
				'deactivate' => 'Deactivate'
			);
		elseif('inactive'==@$status)
			$actions = array(
				'activate' => 'Activate'
			);
		else
			$actions = array(
				'activate'    => 'Activate',
				'deactivate' => 'Deactivate'
			);
        return $actions;
    }
	
	/**
	 * Build html form for setting service options.
	 * 
	 * @see api_connection_manager_dash_options()
	 * @global API_Connection_Manager $API_Connection_Manager
	 * @return string Returns the html list of forms. 
	 */
	public function get_service_options(){
		
		//get api-connection-manager class
		global $API_Connection_Manager;
		if( "API_Connection_Manager"!=get_class($API_Connection_Manager))
			$API_Connection_Manager = new API_Connection_Manager();
		
		//get services
		$services = $API_Connection_Manager->services['active'];
		
		//build up html
		$html = "<ul class=\"api-con-list-services\">\n";
		foreach($services as $slug=>$service){
			
			//vars
			$grant_options = @$service['params']['grant-options'];
			$token_options = @$service['params']['token-options'];
			$params = $service['params'];
			$slug_safe = preg_replace("/[\s\W]+/", "_", $slug);
			$html .= "<li class=\"widget\">
				<div class=\"widget-top\">
					<div class=\"widget-title\"><h4>{$service['Name']}</h4></div>
				</div>";
			
			//if no options
			if(!$grant_options && !$token_options){
				$html .= "<div class=\"module-inside\">
						No options for this service
					</div></li>";
				continue;
			}
			
			//start form
			$html .= "
				<div class=\"module-inside\">
					<form method=\"post\">
					<input type=\"hidden\" name=\"action\" value=\"save_service\"/>
					<input type=\"hidden\" name=\"service\" value=\"$slug\"/>
					<ul>\n";
			
			//grant options
			foreach($grant_options as $name=>$label){
				(@$params['grant-vars'][$name]) ?
					$value=$params['grant-vars'][$name] :
					$value = "";
				if(preg_match("/<\!--\[--(.+)--\]-->/", $value, $matches))
					$value="";
				$html .= "<li>
					<input type=\"text\" name=\"{$name}\" id=\"{$slug_safe}-{$name}\" value=\"{$value}\"/>
					<label for=\"{$slug_safe}-{$name}\">{$label}</label>
					</li>\n";
			}
						
			//token options
			if(count($token_options))
				foreach($token_options as $name=>$label){
					(preg_match("/<\!--\[--(.+)--\]-->/", $params['token-vars'][$name], $matches)) ?
						$value="" :
						$value=$params['token-vars'][$name] ;
					$html .= "<li>
						<input type=\"text\" name=\"{$name}\" id=\"{$slug_safe}-{$name}\" value=\"{$value}\"/>
						<label for=\"{$slug_safe}-{$name}\">{$label}</label>
						</li>\n";
				}
			
			//close option
			$html .= "</li>
				<li><input type=\"submit\" value=\"Save {$service['Name']} Options\"/></li>
				</ul></form></form>\n";
		}
		
		return "{$html}</ul>\n";
	}
	
	/**
	 * Returns an html list of services and their options.
	 * 
	 * @subpackage WP_List_Table
	 * @return string 
	 */
	public function prepare_items(){
		
		//vars
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'     => 'Title',
            'description'    => 'Description'
        );
		$columns_hidden = array();
		$columns_sortable = array(
            'title'     => array('title',true),     //true means its already sorted
            'description'    => array('description',false)
        );
		$per_page = 20;
		
		//register headers with parent
        $this->_column_headers = array($columns, $columns_hidden, $columns_sortable);
		
		//do actions
		$data = $this->get_data();
		
       /**
         * This checks for sorting input and sorts the data in our array accordingly.
         */
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
		/**
		 * Pagination 
		 */
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );		
		//end Pagination
		
	} //end list_services()
	
	/**
	 * Saves a services options.
	 * 
	 * @uses API_Connection_Manager::_set_service_options()
	 * @global API_Connection_Manager $API_Connection_Manager 
	 */
	public function save_service(){
		
		//get api-connection-manager class
		global $API_Connection_Manager;
		
		//vars
		$slug = $_REQUEST['service'];
		$service = $API_Connection_Manager->get_service( $slug );
		print "<pre>";
		print_r($service);
		print "</pre>";
		$grant_vars = @$service['params']['grant-options'];
		$token_vars = @$service['params']['token-options'];
		$options = array();
		
		//get values from keys in $_REQUEST
		foreach($_REQUEST as $key=>$value)
			if(array_key_exists($key, $grant_vars))
				$options['grant-vars'][$key] = $value;
			elseif(array_key_exists($key, (array) $token_vars))
				$options['token-vars'][$key] = $value;
		
		if(
			is_array($options['grant-vars']) ||
			is_array(@$options['token-vars'])
		)
			$API_Connection_Manager->_set_service_options($slug, $options);
	}
	
	/**
	 * Callback function for saving service settings form.
	 *  
	 * @global API_Connection_Manager $API_Connection_Manager
	 * @deprecated
	 */
	public function set_service_settings(){
		
		//vars
		global $API_Connection_Manager;
		$slug = urldecode($_REQUEST['service']);
		$service = $API_Connection_Manager->get_service( $slug );
		$params = $service['params'];
		$options = $service['options'];
		
		//look for grant app vars
		foreach(@$params['app-grant-vars'] as $key => $name)
			if(isset($_REQUEST[$key]))
				$options['app-grant-vars'][$key] = $_REQUEST[$key];
		
		//look for token app vars
		foreach(@$params['app-token-vars'] as $key => $name)
			if(isset($_REQUEST[$key]))
				$options['app-token-vars'][$key] = $_REQUEST[$key];
		
		$API_Connection_Manager->_set_service_options($slug, $options);
	} //end set_service_settings()
	
	/**
	 * To show inline-edit as in posts table.
	 * 
	 * @staticvar string $row_class
	 * @param type $item 
	 */
	public function single_row($item){
		
		static $row_class = '';
		$row_class = ( $row_class == '' ? 'alternate' : '' );

		echo '<tr class="' . $row_class . '">';
		echo $this->single_row_columns( $item );
		echo '</tr>';
		$row_class .= " api-con-mng-settings";
		$id = preg_replace("/[\s\W]+/", "_", $item['ID']);
		echo '<tr class="' . $row_class . '" style="display: none" id="api-settings-'.$id.'"><td colspan="'.$this->get_column_count().'">';
		/**
		 * @deprecated Changed to own settings page instead.
		 *
		?>
<form method="post" action="">
	<input type="hidden" name="action" value="api-connection-service-options"/>
	<input type="hidden" name="service" value="<?php echo $item['ID']; ?>"/>
	
	<ul>
	<?php
	//build up options
	
	?>
	</ul>
</form>
		<?php
		 */
		echo '</td></tr>';
	}
	
    /**
     * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
     * is given special treatment when columns are processed. It ALWAYS needs to
     * have it's own method.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    public function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }
	
	/**
	 * Gets services from API_Connection_Manager and formats into data for the
	 * WP_List_Tables class
	 *
	 * @return array
	 */
	private function get_data(){
		
		//vars
		$api = new API_Connection_Manager();
		$data = array();
		(@$_GET['module_status']) ?
			$status = $_GET['module_status'] :
			$status = 'all';
		
		//get totals
		$this->total_active = count($api->services['active']);
		$this->total_inactive = count($api->services['inactive']);
		$this->total = (int)( (int)$this->total_active + (int)$this->total_inactive);
		
		//get inactive services
		if('inactive'==$status || 'all'==$status)
			foreach($api->services['inactive'] as $service)
				$data[] = array(
					'ID' => $service['slug'],
					'title' => $service['Name'],
					'description' => $service['Description']
				);
		
		//get active services
		if('active'==$status || 'all'==$status)
			foreach($api->services['active'] as $service)
				$data[] = array(
					'ID' => $service['slug'],
					'title' => $service['Name'],
					'description' => $service['Description']
				);
		
		return $data;
	}
	
	/**
	 * Do any form submit actions on the data.
	 */
	private function process_bulk_actions(){
		
		if("api-connection-manager-setup"!=@$_GET['page'])
			if("api-connection-manager-service"!=@$_GET['page'])
				return;
		$action = @$_REQUEST['action'];
		if(method_exists($this, $action))
			$this->$action();
	}
}

$api_con_mngr_dash_setup = new API_Connection_Manager_Setup();
if( !function_exists( 'api_connection_manager_dash' ) ):
	/**
	 * Function for displaying service activation/deactivation table.
	 * 
	 * @global API_Connection_Manager_Setup $dashboard 
	 * @package api-connection-manager
	 */
	function api_connection_manager_dash(){
	
		//default show all
		if(!@$_GET['module_status'])
			$_GET['module_status'] = 'all';
		
		//construct WP_List_Table child class
		global $api_con_mngr_dash_setup;
		$api_con_mngr_dash_setup->__construct_wp_list();
		/*
		if("API_Connection_Manager_Setup"!=get_class($dashboard))
			$dashboard = new API_Connection_Manager_Setup();
		 * 
		 */
		$api_con_mngr_dash_setup->prepare_items();
		
		//get url
		if(is_multisite())
			$url = admin_url( "network/admin.php?page=" . $_GET['page'] );
		else
			$url = admin_url( "admin.php?page=" . $_GET['page'] );
		
		//print html
		?>
		<!-- Header //-->
		<h2><div id="icon-users" class="icon32"></div><?php _e( 'AutoFlow Wordpress Login Framework', 'autoflow' ); ?></h2>
		<ul class="subsubsub">
			<li class="all"><a <?php if( 'all'==@$_GET['module_status'] || !@$_GET['module_status']) echo ' class="current"' ?> 
					href="<?php echo $url; ?>&module_status=all">All <span class="count">(<?php echo $api_con_mngr_dash_setup->total; ?>)</span></a> |</li>

			<li class="active"><a <?php if( 'active'==$_GET['module_status'] ) echo ' class="current"' ?> 
					href="<?php echo $url; ?>&module_status=active">Active <span class="count">(<?php echo $api_con_mngr_dash_setup->total_active; ?>)</span></a> |</li>

			<li class="inactive"><a <?php if( 'inactive'==$_GET['module_status'] ) echo ' class="current"' ?> 
					href="<?php echo $url; ?>&module_status=inactive">Inactive <span class="count">(<?php echo $api_con_mngr_dash_setup->total_inactive; ?>)</span></a> |</li>
		</ul>
		<div class="clear"></div>
		<!-- END HEADER //-->
		
		<!-- Form List //-->
		<div class="wrap">

			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
			</div>

			<form id="movies-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $api_con_mngr_dash_setup->display() ?>
			</form>

		</div>
		<!-- END FORM LIST //-->
		<?php
		
	}
endif;	//api_connection_manager_dash()

if( !function_exists( 'api_connection_manager_dash_options' ) ):
	/**
	 * Function for displaying the service options
	 * 
	 * @global API_Connection_Manager_Setup $dashboard
	 * @package api-connection-manager
	 */
	function api_connection_manager_dash_options(){
	
		//get setup class
		global $api_con_mngr_dash_setup;
		$api_con_mngr_dash_setup->__construct_wp_list();
		
		//redirect uri
		$redirect_uri = admin_url('admin-ajax.php') . "?" . http_build_query(array(
			'action' => 'api_con_mngr'
		));
		
		//print service options
		?>
			<h2>Api Connection Manager - Service Options</h2>
			<h3>The redirect uri for this installation is: <em><?php echo $redirect_uri; ?></em></h3>
			<ul>
				<?php echo $api_con_mngr_dash_setup->get_service_options(); ?>
			</ul>
		<?php
	}
endif;