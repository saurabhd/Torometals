<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.1.5
 *
 * @package    Custom_Widget_Area
 * @subpackage Custom_Widget_Area/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Custom_Widget_Area
 * @subpackage Custom_Widget_Area/admin
 * @author     Your Name <email@example.com>
 */

require_once('partials/cwa-admin-display.php');
require_once('partials/cwa-menu-admin-display.php');
require_once('partials/cwa-help.php');
class Custom_Widget_Area_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.1.5
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.1.5
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.1.5
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		global $table_name, $wpdb;
		$this->view = new CWA_View();
		$this->menuView = new Menu_View();
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->table_name = $table_name; 
		$this->setup_ajax_request();
		add_action( 'widgets_init', array($this, 'registerSidebar'));
		$this->registerMenuLocations();
	}
	public function menu_setup(){
		
		add_menu_page('CWA Settings', 'CWA Settings', 'administrator', 'custom_widget_area', 	array($this->view, 'displayView'),''/*plugins_url('/images/icon.png', __FILE__)*/);
		add_submenu_page( 'custom_widget_area', 'Custom Widget Area', 'Custom Widget Area', 'administrator', 'custom_widget_area', array($this->view, 'displayView') );
		add_submenu_page( 'custom_widget_area', 'Menu Locations', 'Menu Locations', 'administrator', 'custom_menu_location', array($this->menuView, 'displayView') );
		add_submenu_page( 'custom_widget_area', 'Help', 'Help', 'manage_options', 'cwa_help', 'help_page');
		//self::setuo_ajax_request();  		
	}
	public function setup_ajax_request(){
		add_action( 'wp_ajax_add_cwa', array($this, 'add_cwa'));
		add_action( 'wp_ajax_get_cwa', array($this, 'get_cwa'));
		
		add_action( 'wp_ajax_delete_cwa', array($this, 'delete_cwa'));
		add_action( 'wp_ajax_check_cwa_id', array($this, 'check_cwa_id'));
		//add_action( 'wp_ajax_get_cwa', array($this, 'get_cwa'));
		add_action( 'wp_ajax_reloadTable', array($this->view , 'widgetTable'));

		// Menu Location settings
		add_action( 'wp_ajax_add_menu', array($this, 'add_menu'));
		
		add_action( 'wp_ajax_delete_menu', array($this, 'delete_menu'));
		add_action( 'wp_ajax_check_menu_id', array($this, 'check_menu_id'));
		//add_action( 'wp_ajax_get_cwa', array($this, 'get_cwa'));
		add_action( 'wp_ajax_reloadMenuTable', array($this->menuView , 'MenuTable'));

		
	}

	/* Widget functions start */
	public function add_cwa(){

		global $table_name, $wpdb;
		$wpdb->show_errors();
		//get parameter $x = $_POST['x'];
		$data = $_POST['data'];
		//echo "hello : your test is successfull!!!";
		//var_dump($_POST['data']);
		if(isset($data['task']) && $data['task'] == 'update'){
			$data['cwa_id'] = $data['updateid'];
		}
		//var_export($table_name);
		if($data['cwa_name'] !== '' && $data['cwa_id'] !== '' ){			
			$new_data = $this->validatePost();
			//echo "id: " .$this->check_cwa_id($new_data['cwa_id']);
			$task =$data['task'];
			unset($new_data['task']);
			unset($new_data['updateid']);
			//var_dump($new_data);
			if($this->check_cwa_id($new_data['cwa_id']) || (isset($data['task']) && $data['task'] == 'update') ){
				$new_data['last_updated'] = date('Y-m-d');
				$new_data['cwa_type'] = "widget";

				//var_dump($new_data);
				$row = $wpdb->replace( $table_name, $new_data );
				
				if($row && !$task ){
					wp_send_json(array('code'=>1, 'message' => $new_data['cwa_id'].' created successfully.'));
				}
				elseif($row && $task ){
					wp_send_json(array('code'=>1, 'message' => $new_data['cwa_id'].' updated successfully.'));
				}
			}
			else{
				wp_send_json(array('code' => 0, 'message' => 'Widget id already registered'));
				
			}
		}
		else{
			wp_send_json(array('code' => 0, 'message' => 'Widget area name or id not defined'));
		}
		die(); // this is required to terminate immediately and return a proper response
	}

	
	
	public function delete_cwa(){
		global $table_name, $wpdb;
		$wpdb->show_errors();
		$cwa_id = esc_html($_POST['data']['cwa_id']);
		

		$row = $wpdb->delete( $table_name, array( 'cwa_id' => $cwa_id, 'cwa_type' => 'widget' ), $where_format = null );
		//wp_send_json_success(array('code' => 1, 'message' => $cwa_id.' deleted successfully.'));
		
		if($row>0)
			wp_send_json(array('code' => 1, 'message' => $cwa_id.' deleted successfully.'));
		else
			wp_send_json(array('code' => 0, 'message' => 'Error accured!.'));

		die();
	}
	public function check_cwa_id($id=null){
		global $wpdb;
			
		if(empty($id))	
			$cwa_id = $_POST['data']['cwa_id'];
		else
			$cwa_id = $id;

		$valid = self::checSpecialChar($cwa_id);
		
		if($valid && !empty($cwa_id)){

			$sql = "SELECT * FROM $this->table_name WHERE cwa_id='$cwa_id' AND cwa_type='widget'";

			
			$row = $wpdb->get_row( $sql, 'OBJECT');
			
			if(empty($id)){
				if($row)
					wp_send_json(array('code' => 0, 'message' => 'Widget id already registered'));
				else
					wp_send_json(array('code' => 1, 'message' => 'Widget id available'));
				die();
			}
			else{
				if($row)
					return false;
				else
					return true;	
			}	
		}
		elseif (empty($cwa_id)) {
			# code...
			if(empty($id)){
				wp_send_json(array('code' => 0, 'message' => 'Please enter a Widget id '));
			}
			else{
				return false;
			}
		}
		else{
			if(empty($id)){
				wp_send_json(array('code' => 0, 'message' => 'Invalid id, use [a-z]-[0-9]'));
			}
			else{
				return false;
			}
		}
	}
	public function get_cwa(){
		global $wpdb;
		$cwa_id = $_POST['data']['cwa_id'];
		//var_dump($cwa_id);
		array('code' => 0, 'message' => 'Invalid widget id!');
		if(!!$cwa_id){
			$sql = "SELECT * FROM $this->table_name WHERE cwa_id='$cwa_id' AND cwa_type='widget'";
			$row = $wpdb->get_row( $sql, 'OBJECT');
			
		}
		wp_send_json($row);
		die();
	}
	public function getall_cwa(){
		global $wpdb;
		$sql = "SELECT * FROM $this->table_name  WHERE cwa_type='widget'";
		$row = $wpdb->get_results( $sql, 'OBJECT');
		return $row;
	}
	public function registerSidebar(){
		$data = $this->getall_cwa();
		//var_dump($data);
		foreach ($data as $row) {
			# code...
			$this->createSidebar($row);
		}
		
	}
	public function createSidebar($row){
		//register_widget( 'wp_custom_widget_area' );
		register_sidebar( array(
			'name'          => __($row->cwa_name, 'wp_custom_widget_area' ),
			'id'            => $row->cwa_id,
			'description'   => __( $row->cwa_description, 'wp_custom_widget_area' ),
			'before_widget' => '<'.$row->cwa_widget_wrapper.' id="%1$s" class="widget %2$s '.$row->cwa_widget_class.'">',
			'after_widget'  => '</'.$row->cwa_widget_wrapper.'>',
			'before_title'  => '<'.$row->cwa_widget_header_wrapper.' class="widgettitle '.$row->cwa_widget_header_class.'">',
			'after_title'   => '</'.$row->cwa_widget_header_wrapper.'>',
		) );
	}

	/* Widget functions end */


	/* Menu functions start */
	public function add_menu(){

		global $table_name, $wpdb;
		$wpdb->show_errors();
		//get parameter $x = $_POST['x'];
		$data = $_POST['data'];
		//echo "hello : your test is successfull!!!";
		//var_dump($_POST);
		//var_export($table_name);
		if($data['cwa_name'] !== '' && $data['cwa_id'] !== '' ){			
			$new_data = $this->validatePost();
			//echo "id: " .$this->check_cwa_id($new_data['cwa_id']);
			if($this->check_menu_id($new_data['cwa_id'])){
				$new_data['last_updated'] = date('Y-m-d');
				$new_data['cwa_type'] = "menu";
				//var_dump($new_data);
				$row = $wpdb->replace( $table_name, $new_data );
				
				if($row){
					wp_send_json(array('code'=>1, 'message' => $new_data['cwa_id'].' created successfully.'));
				}
			}
			else{
				wp_send_json(array('code' => 0, 'message' => 'Menu Location id already registered'));
				
			}
		}
		else{
			wp_send_json(array('code' => 0, 'message' => 'Menu Location name or id not defined'));
		}
		die(); // this is required to terminate immediately and return a proper response
	}
	
	public function delete_menu(){
		global $table_name, $wpdb;
		$wpdb->show_errors();
		$cwa_id = esc_html($_POST['data']['cwa_id']);
		

		$row = $wpdb->delete( $table_name, array( 'cwa_id' => $cwa_id, 'cwa_type' => 'menu' ), $where_format = null );
		//wp_send_json_success(array('code' => 1, 'message' => $cwa_id.' deleted successfully.'));
		
		if($row>0)
			wp_send_json(array('code' => 1, 'message' => $cwa_id.' deleted successfully.'));
		else
			wp_send_json(array('code' => 0, 'message' => $row));

		die();
	}
	public function check_menu_id($id=null){
		global $wpdb;
			
		if(empty($id))	
			$cwa_id = $_POST['data']['cwa_id'];
		else
			$cwa_id = $id;

		$valid = self::checSpecialChar($cwa_id);
		
		if($valid && !empty($cwa_id)){

			$sql = "SELECT * FROM $this->table_name WHERE cwa_id='$cwa_id' AND cwa_type='menu'";

			
			$row = $wpdb->get_row( $sql, 'OBJECT');
			//wp_send_json(!!$cwa_id);die();
			if(empty($id)){
				if($row)
					wp_send_json(array('code' => 0, 'message' => 'Menu Location id already registered'));
				else
					wp_send_json(array('code' => 1, 'message' => 'Menu Location id available'));
				die();
			}
			else{
				if($row)
					return false;
				else
					return true;	
			}	
		}
		elseif(empty($cwa_id)){
			if(empty($id)){
				wp_send_json(array('code' => 0, 'message' => 'Please enter a Menu Location id'));
			}
			else{
				return false;
			}
		}
		else{
			if(empty($id)){
				wp_send_json(array('code' => 0, 'message' => 'Invalid id, use [a-z]-[0-9]'));
			}
			else{
				return false;
			}
		}
		die;
	}
	public function get_menu(){
		global $wpdb;
		$cwa_id = $_POST['data']['cwa_id'];
		//var_dump($cwa_id);
		$sql = "SELECT * FROM $this->table_name WHERE cwa_id='$cwa_id' AND cwa_type='menu'";
		$row = $wpdb->get_row( $sql, 'OBJECT');
		wp_send_json($row);
	}
	public function getall_menu(){
		global $wpdb;
		$sql = "SELECT * FROM $this->table_name  WHERE cwa_type='menu'";
		$row = $wpdb->get_results( $sql, 'OBJECT');
		return $row;
	}

	public function registerMenuLocations(){
		$data = $this->getall_menu();
		//var_dump($data);
		foreach ($data as $row) {
			# code...
			$this->createMenuLocaiton($row);
		}
		
	}
	public function createMenuLocaiton($row){
		
		register_nav_menu( $row->cwa_id, __($row->cwa_name, 'wp_custom_widget_area' ) );
	}
	/* Menu functions end */

	public function validatePost(){
		$data =$_POST['data'];
		if(isset($data['task']) && $data['task'] == 'update'){
			$data['cwa_id'] = $data['updateid'];
		}
		$new_data = array();
		foreach ($data as $key => $value) {
			# code...
			$new_data[$key] = esc_html($value);
		}
		return $new_data;
	}
	public function checSpecialChar($string){
		if(!preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬ ]/', $string)){
			return true;
		}
		else{
			return false;
		}
	}
	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.1.5
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Custom_Widget_Area_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Widget_Area_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-custom-widget-area-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.1.5
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Custom_Widget_Area_Admin_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Custom_Widget_Area_Admin_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( 'tooltip', plugin_dir_url( __FILE__ ) . 'js/jquery.tooltipster.min.js', array( ), $this->version, false );
		wp_enqueue_script( 'hashchange', plugin_dir_url( __FILE__ ) . 'js/jquery.hashchange.min.js', array( ), $this->version, false );
		wp_enqueue_script( 'easytabs', plugin_dir_url( __FILE__ ) . 'js/jquery.easytabs.min.js', array( 'hashchange'), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-custom-widget-area-admin.js', array( 'jquery', 'tooltip', 'easytabs'), $this->version, false );

	}


}
