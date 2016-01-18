<?php

//load_plugin_textdomain( 'woocommerce', false, basename( dirname( __FILE__ ) ) . '/languages' );

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class PP_EU_Export_Orders {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		add_action( 'init', array( $this, 'generate_csv' ) );
		//add_filter( 'pp_eu_exclude_data', array( $this, 'exclude_data' ) );
	}

	/**
	 * Add administration menus
	 *
	 * @since 0.1
	 **/
	public function add_admin_pages() {
		//add_users_page( __( 'Export to CSV', 'woocommerce' ), __( 'Export to CSV', 'woocommerce' ), 'list_users', 'woocommerce', array( $this, 'users_page' ) );
		add_submenu_page('woocommerce', 'Export Orders to CSV', 'Export', 'manage_woocommerce', 'order_export', array( $this, 'orders_page' ));
	}

	/**
	 * Process content of CSV file
	 *
	 * @since 0.1
	 **/
	public function generate_csv() {
		
		if ( isset( $_POST['_wpnonce-pp-eu-export-orders-orders-page_export'] ) ) {
			check_admin_referer( 'pp-eu-export-orders-orders-page_export', '_wpnonce-pp-eu-export-orders-orders-page_export' );
			
			$order_type = $_POST['order_type'];
			
			// file name
			$sitename = sanitize_key( get_bloginfo( 'name' ) );
			if ( ! empty( $sitename ) )
				$sitename .= '.';			
			$filename = $sitename . $order_type .'.' . date( 'Y-m-d-H-i-s' ) . '.csv';

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );


			global $wpdb;

			
			// get orders from database
			$args = array(
				'post_type' => $_POST['order_type'],
				'posts_per_page' => -1,
				'post_status' => 'any'
			);
			
			if (!empty($_POST['start_date']) || !empty($_POST['end_date'])) {
				add_filter('posts_where', array($this, 'filter_date'));
			}
			
			
			$query = new WP_Query($args);
			
			remove_filter('posts_where', 'filter_date');
			
			if ( $query->found_posts == 0 ) {
				$referer = add_query_arg( 'error', 'empty', wp_get_referer() );
				wp_redirect( $referer );
				exit;
			}
			
			
			$fields = array(
				'order_id' => 'Order ID',
				'status' => 'Status',
				'customer_name' => 'Customer',
				'date' => 'Date/Time',
				'order_total' => $order_type == 'shop_order' ? 'Order Total' : 'Order Total/Quote',
				'billing_name' => 'Billing Name',
				'billing_company' => 'Billing Company',
				'billing_address_1' => 'Billing Address 1',
				'billing_address_2' => 'Billing Address 2',
				'billing_city' => 'Billing City',
				'billing_state' => 'Billing State',
				'billing_postcode' => 'Billing Postcode',
				'shipping_method_title' => 'Shipping Method',
				'shipping_name' => 'Shipping Name',
				'shipping_company' => 'Shipping Company',
				'shipping_address_1' => 'Shipping Address 1',
				'shipping_address_2' => 'Shipping Address 2',
				'shipping_city' => 'Shipping City',
				'shipping_state' => 'Shipping State',
				'shipping_postcode' => 'Shipping Postcode',
			);
			
			$exclude_data = $this->exclude_data($fields);
			
			// filter out fields inappropriate for the order type
			$headers = array();
			foreach ( $fields as $key => $field ) {
				if ( in_array( $field, $exclude_data ) )
					unset( $fields[$key] );
				else
					$headers[] = '"' . $field . '"';
			}
			// column headers
			echo implode( ',', $headers ) . "\r\n";
			
			// data columns
			while ($query->have_posts()) {
				$query->next_post();
			
				$order = $order_type == 'shop_order' ? new WC_Order($query->post->ID) : new DG_Order($query->post->ID);
				
			
				$csv_row = array();
				foreach (array_keys($fields) as $column) {  // the fields array was filtered above
					switch ($column) {
						case 'order_id':
							$csv_row[] = sprintf( __( 'Order %s', 'woocommerce' ), esc_attr( $order->get_order_number() ) );
							break;
						
						case 'customer_name':
							$user_info = get_userdata( $order->user_id );
							if ( $user_info->first_name || $user_info->last_name )
								$user = esc_html( $user_info->first_name . ' ' . $user_info->last_name );
							else
								$user = esc_html( $user_info->display_name );
							$csv_row[] = $user;
							break;
						
						case 'date':
							$h_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $query->post );
							$csv_row[] = apply_filters( 'post_date_column_time', $h_time, $query->post );
							break;
						
						case 'order_total':
							$csv_row[] = html_entity_decode(strip_tags( $order->get_order_total() ));
							break;
						
						case 'billing_name':
							$csv_row[] = $order->billing_first_name .' '. $order->billing_last_name;
							break;
						
						case 'shippingg_name':
							$csv_row[] = $order->shippingg_first_name .' '. $order->shippingg_last_name;
							break;
						
						default:
							$csv_row[] = $order->$column;
							
					}
				}
				
				echo implode( ',', $csv_row ) . "\r\n";
			}

			exit;
		}
	}

	/**
	 * Content of the settings page
	 *
	 * @since 0.1
	 **/
	public function orders_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) )
			wp_die('You do not have sufficient permissions to access this page.');
?>

<div class="wrap">
	<h2><?php _e( 'Export orders to a CSV file', 'woocommerce' ); ?></h2>
	<?php
	if ( isset( $_GET['error'] ) ) {
		echo '<div class="updated"><p><strong>' . __( 'No orders found.', 'woocommerce' ) . '</strong></p></div>';
	}
	?>
	<form method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'pp-eu-export-orders-orders-page_export', '_wpnonce-pp-eu-export-orders-orders-page_export' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><label><?php _e( 'Order Type', 'woocommerce' ); ?></label></th>
				<td>
					<select name="order_type">
						<option value="shop_order">Shop Orders</option>
						<option value="dg_order">Dillon Gage Orders</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label><?php _e( 'Date range', 'woocommerce' ); ?></label></th>
				<td>
					<select name="start_date" id="pp_eu_users_start_date">
						<option value=""><?php _e( 'Start Date', 'woocommerce' ); ?></option>
						<?php $this->export_date_options(); ?>
					</select>
					<select name="end_date" id="pp_eu_users_end_date">
						<option value=""><?php _e( 'End Date', 'woocommerce' ); ?></option>
						<?php $this->export_date_options(); ?>
					</select>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="hidden" name="_wp_http_referer" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />
			<input type="submit" class="button-primary" value="<?php _e( 'Export', 'woocommerce' ); ?>" />
		</p>
	</form>
<?php
	}

	public function exclude_data($fields) {
		$exclude = array();
		
		if ($_POST['order_type'] == 'dg_order') {
			$exclude[] = 'Customer';
		
			foreach ($fields as $key => $value) {
				if ( strpos($key, 'billing_') !== false)
					$exclude[] = $value;
			}			
		}
		
		return $exclude;
	}
	
	public function filter_date( $where = '') {
		$start = $_POST['start_date'];
		$end = $_POST['end_date'];
		
		if (!empty($start)) {
			$dt = strtotime($start);
			$where .= " AND post_date >= '". date("Y-m-d", $dt) ."'";
		}
		
		if (!empty($end)) {
			$dt = strtotime($end);
			$where .= " AND post_date <= '". date("Y-m-t", $dt) ."'";
		}
		//echo "query: ". $wher .PHP_EOL;
		return $where;
	}

	public function pre_user_query( $user_search ) {
		global $wpdb;

		$where = '';

		if ( ! empty( $_POST['start_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered >= %s", date( 'Y-m-d', strtotime( $_POST['start_date'] ) ) );

		if ( ! empty( $_POST['end_date'] ) )
			$where .= $wpdb->prepare( " AND $wpdb->users.user_registered < %s", date( 'Y-m-d', strtotime( '+1 month', strtotime( $_POST['end_date'] ) ) ) );

		if ( ! empty( $where ) )
			$user_search->query_where = str_replace( 'WHERE 1=1', "WHERE 1=1$where", $user_search->query_where );

		return $user_search;
	}

	private function export_date_options() {
		global $wpdb, $wp_locale, $blog_id;
		
		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM wp_". $blog_id ."_posts
			WHERE post_type IN ('shop_order', 'dg_order')
			ORDER BY post_date DESC
		" );

		$month_count = count( $months );
		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;

		foreach ( $months as $date ) {
			if ( 0 == $date->year )
				continue;

			$month = zeroise( $date->month, 2 );
			echo '<option value="' . $date->year . '-' . $month . '">' . $wp_locale->get_month( $month ) . ' ' . $date->year . '</option>';
		}
	}
}

new PP_EU_Export_Orders;
