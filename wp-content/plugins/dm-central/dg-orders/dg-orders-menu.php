<?php

function dm_inv_dg_post_type () {
	$show_in_menu = current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true;

	register_post_type( "dg_order",	array(
			'labels' => array(
					'name' 					=> __( 'Dillon Gage Orders', 'woocommerce' ),
					'singular_name' 		=> __( 'Dillon Gage Order', 'woocommerce' ),
					'add_new' 				=> __( 'Add Order', 'woocommerce' ),
					'add_new_item' 			=> __( 'Add New DG Order', 'woocommerce' ),
					'edit' 					=> __( 'Edit', 'woocommerce' ),
					'edit_item' 			=> __( 'Edit Order', 'woocommerce' ),
					'new_item' 				=> __( 'New DG Order', 'woocommerce' ),
					'view' 					=> __( 'View Order', 'woocommerce' ),
					'view_item' 			=> __( 'View Order', 'woocommerce' ),
					'search_items' 			=> __( 'Search DG Orders', 'woocommerce' ),
					'not_found' 			=> __( 'No DG Orders found', 'woocommerce' ),
					'not_found_in_trash' 	=> __( 'No DG Orders found in trash', 'woocommerce' ),
					'parent' 				=> __( 'Parent Orders', 'woocommerce' ),
					'menu_name'				=> _x('DG Orders', 'Admin menu name', 'woocommerce')
				),
			'description' 			=> __( 'This is where orders to Dillon Gage are stored.', 'woocommerce' ),
			'public' 				=> false,
			'show_ui' 				=> true,
			'capability_type' 		=> 'shop_order',  //TODO: needs to change if we want to add a different capability for dg_orders
			'map_meta_cap'			=> true,
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'show_in_menu' 			=> $show_in_menu,
			'hierarchical' 			=> false,
			'show_in_nav_menus' 	=> false,
			'rewrite' 				=> false,
			'query_var' 			=> false,
			'supports' 				=> array( 'title', 'comments' ),
			'has_archive' 			=> false,
		)
	);
}
add_action('init', 'dm_inv_dg_post_type', 12);

/**
 * Disable the auto-save functionality for Orders.
 *
 * @access public
 * @return void
 */
function dm_orders_disable_autosave_for_orders(){
    global $post;

    if ( $post && get_post_type( $post->ID ) === 'dg_order' ) {
        wp_dequeue_script( 'autosave' );
    }
}

add_action( 'admin_print_scripts', 'dm_orders_disable_autosave_for_orders' );


/**
 * Define columns for the orders page.
 *
 * @access public
 * @param mixed $columns
 * @return array
 */
function dm_orders_edit_order_columns($columns){
	global $woocommerce;

	$columns = array();

	$columns["cb"] 					= "<input type=\"checkbox\" />";
	$columns["order_status"] 		= '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce' ) . '">' . esc_attr__( 'Status', 'woocommerce' ) . '</span>';
	$columns["order_title"] 		= __( 'Order', 'woocommerce' );
	$columns["shipping_address"] 	= __( 'Shipping', 'woocommerce' );
	//$columns["total_cost"] 			= __( 'Order Total', 'woocommerce' );
	$columns["order_date"] 			= __( 'Date', 'woocommerce' );
	//$columns["order_actions"] 		= __( 'Actions', 'woocommerce' );

	return $columns;
}

add_filter('manage_edit-dg_order_columns', 'dm_orders_edit_order_columns');


/**
 * Values for the custom columns on the orders page.
 *
 * @access public
 * @param mixed $column
 * @return void
 */
function dm_orders_custom_order_columns( $column ) {
	global $post, $woocommerce, $the_order;

	if ( empty( $the_order ) || $the_order->id != $post->ID )
		$the_order = new DG_Order( $post->ID );

	switch ( $column ) {
		case "order_status" :

			printf( '<mark class="%s tips" data-tip="%s">%s</mark>', sanitize_title( $the_order->get_status() ), esc_html__( $the_order->get_status(), 'woocommerce' ), esc_html__( $the_order->get_status(), 'woocommerce' ) );

		break;
		case "order_title" :
           	echo '<a href="' . admin_url( 'post.php?post=' . absint( $post->ID ) . '&action=edit' ) . '"><strong>' . sprintf( __( 'Order %s', 'woocommerce' ), esc_attr( $the_order->get_order_number() ) );
		break;
		case "shipping_address" :
			if ( $the_order->get_formatted_shipping_address() ) {
            	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q=' . urlencode( $the_order->get_shipping_address() ) . '&z=16' ) . '">'. esc_html( preg_replace('#<br\s*/?>#i', ', ', $the_order->get_formatted_shipping_address() ) ) .'</a>';
        	} else if ($the_order->shipping_method) {
				switch ($the_order->shipping_method) {
					case 'drop_ship':
						echo __( 'Drop ship', 'woocommerce' );
						break;
					case 'hold':
						echo __( 'Hold for drop ship', 'woocommerce' );
						break;
					case 'ship_to_me':
						echo __( 'Ship to me', 'woocommerce' );
						break;
					default:
						echo ucfirst($the_order->shipping_method);
				}
			}
			else {
        		echo '&ndash;';
			}
		break;
		case "total_cost" :
			echo esc_html( strip_tags( $the_order->get_formatted_order_total() ) );
		break;
		case "order_date" :

			if ( '0000-00-00 00:00:00' == $post->post_date ) {
				$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
			} else {
				$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );

				$gmt_time = strtotime( $post->post_date_gmt . ' UTC' );
				$time_diff = current_time('timestamp', 1) - $gmt_time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $gmt_time, current_time('timestamp', 1) ) );
				else
					$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
			}

			echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post ) ) . '</abbr>';

		break;
		case "order_actions" :

			?><p>
				<?php
					do_action( 'dm_orders_admin_order_actions_start', $the_order );

					$actions = array();

					if ( in_array( $the_order->get_status(), array( 'pending', 'on-hold' ) ) )
						$actions['processing'] = array(
							'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce-mark-order-processing&order_id=' . $post->ID ), 'woocommerce-mark-order-processing' ),
							'name' 		=> __( 'Processing', 'woocommerce' ),
							'action' 	=> "processing"
						);

					if ( in_array( $the_order->get_status(), array( 'pending', 'on-hold', 'processing' ) ) )
						$actions['complete'] = array(
							'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce-mark-order-complete&order_id=' . $post->ID ), 'woocommerce-mark-order-complete' ),
							'name' 		=> __( 'Complete', 'woocommerce' ),
							'action' 	=> "complete"
						);

					$actions['view'] = array(
						'url' 		=> admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
						'name' 		=> __( 'View', 'woocommerce' ),
						'action' 	=> "view"
					);

					$actions = apply_filters( 'dm_orders_admin_order_actions', $actions, $the_order );

					foreach ( $actions as $action ) {
						$image = ( isset( $action['image_url'] ) ) ? $action['image_url'] : $woocommerce->plugin_url() . '/assets/images/icons/' . $action['action'] . '.png';
						printf( '<a class="button tips" href="%s" data-tip="%s"><img src="%s" alt="%s" width="14" /></a>', esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $image ), esc_attr( $action['name'] ) );
					}

					do_action( 'dm_orders_admin_order_actions_end', $the_order );
				?>
			</p><?php

		break;
	}
}

add_action( 'manage_dg_order_posts_custom_column', 'dm_orders_custom_order_columns', 2 );


/**
 * Filters for the order page.
 *
 * @access public
 * @param mixed $views
 * @return array
 */
function dm_orders_custom_order_views( $views ) {

	unset( $views['publish'] );

	if ( isset( $views['trash'] ) ) {
		$trash = $views['trash'];
		unset( $views['draft'] );
		unset( $views['trash'] );
		$views['trash'] = $trash;
	}

	return $views;
}

add_filter( 'views_edit-dg_order', 'dm_orders_custom_order_views' );


/**
 * Actions for the orders page.
 *
 * @access public
 * @param mixed $actions
 * @return array
 */
function dm_orders_remove_row_actions( $actions ) {
    if( get_post_type() === 'dg_order' ) {
        unset( $actions['view'] );
        unset( $actions['inline hide-if-no-js'] );
    }
    return $actions;
}

add_filter( 'post_row_actions', 'dm_orders_remove_row_actions', 10, 1 );


/**
 * Remove edit from the bulk actions.
 *
 * @access public
 * @param mixed $actions
 * @return array
 */
function dm_orders_bulk_actions( $actions ) {

	if ( isset( $actions['edit'] ) )
		unset( $actions['edit'] );

	return $actions;
}

add_filter( 'bulk_actions-edit-dg_order', 'dm_orders_bulk_actions' );


/**
 * Show custom filters to filter orders by status/customer.
 *
 * @access public
 * @return void
 */
function dm_orders_restrict_manage_orders() {
	global $woocommerce, $typenow, $wp_query;

	if ( $typenow != 'dg_order' )
		return;

	// Status
	?>
	<select name='dg_order_status' id='dropdown_dg_order_status'>
		<option value=""><?php _e( 'Show all statuses', 'woocommerce' ); ?></option>
		<?php
			$terms = get_terms('order_status');

			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->slug ) . '"';

				if ( isset( $wp_query->query['order_status'] ) )
					selected( $term->slug, $wp_query->query['order_status'] );

				echo '>' . esc_html__( $term->name, 'woocommerce' ) . ' (' . absint( $term->count ) . ')</option>';
			}
		?>
		</select>
	<?php

	$woocommerce->add_inline_js( "

		jQuery('select#dropdown_dg_order_status, select[name=m]').css('width', '150px').chosen();

	" );
}

add_action( 'restrict_manage_posts', 'dm_orders_restrict_manage_orders' );


/**
 * Make order columns sortable.
 *
 *
 * https://gist.github.com/906872
 *
 * @access public
 * @param mixed $columns
 * @return array
 */
function dm_orders_custom_dg_order_sort( $columns ) {
	$custom = array(
		'order_title'	=> 'ID',
		'order_total'	=> 'order_total',
		'order_date'	=> 'date'
	);
	unset( $columns['comments'] );
	return wp_parse_args( $custom, $columns );
}

add_filter( "manage_edit-dg_order_sortable_columns", 'dm_orders_custom_dg_order_sort' );


/**
 * Order column orderby/request.
 *
 * @access public
 * @param mixed $vars
 * @return array
 */
function dm_orders_custom_dg_order_orderby( $vars ) {
	global $typenow, $wp_query;
    if ( $typenow != 'dg_order' )
    	return $vars;

    // Sorting
	if ( isset( $vars['orderby'] ) ) {
		if ( 'order_total' == $vars['orderby'] ) {
			$vars = array_merge( $vars, array(
				'meta_key' 	=> '_order_total',
				'orderby' 	=> 'meta_value_num'
			) );
		}
	}

	return $vars;
}

add_filter( 'request', 'dm_orders_custom_dg_order_orderby' );

/**************************************************
			Single DG order page
****************************************************/


// borrow some of the Woocommerce metaboxes
function dm_inv_dg_order_meta_boxes () {
	add_meta_box( 'woocommerce-order-data', __( 'Order Data', 'woocommerce' ), 'dm_inv_dg_order_data_meta_box', 'dg_order', 'normal', 'high' );
	add_meta_box( 'woocommerce-order-items', __( 'Order Items', 'woocommerce' ) . ' <span class="tips" data-tip="' . __( 'Note: if you edit quantities or remove items from the order you will need to manually update stock levels.', 'woocommerce' ) . '">[?]</span>', 'WC_Meta_Box_Order_Items::output', 'dg_order', 'normal', 'high');
	//add_meta_box( 'woocommerce-order-totals', __( 'Order Totals', 'woocommerce' ), 'woocommerce_order_totals_meta_box', 'dg_order', 'side', 'default');
	add_meta_box( 'woocommerce-order-notes', __( 'Order Notes', 'woocommerce' ), 'WC_Meta_Box_Order_Notes::output', 'dg_order', 'side', 'default');
	//add_meta_box( 'woocommerce-order-actions', __( 'Order Actions', 'woocommerce' ), 'woocommerce_order_actions_meta_box', 'dg_order', 'side', 'high');

	remove_meta_box( 'commentsdiv', 'dg_order' , 'normal' );
	remove_meta_box( 'woothemes-settings', 'dg_order' , 'normal' );
	remove_meta_box( 'commentstatusdiv', 'dg_order' , 'normal' );
	remove_meta_box( 'slugdiv', 'dg_order' , 'normal' );
}
add_action( 'add_meta_boxes', 'dm_inv_dg_order_meta_boxes' );

// load woocommerce script and styles
function dm_inv_dg_order_styles () {
	global $woocommerce, $typenow, $post, $wp_scripts;

	if ( $typenow == 'post' && ! empty( $_GET['post'] ) ) {
		$typenow = $post->post_type;
	} elseif ( empty( $typenow ) && ! empty( $_GET['post'] ) ) {
        $post = get_post( $_GET['post'] );
        $typenow = $post->post_type;
    }
	
	if ($typenow == "shop_order" || $typenow == "dg_order") {
		wp_register_style('dm-inv-dg-orders', plugins_url( 'style.css' , __FILE__ ));
		wp_enqueue_style('dm-inv-dg-orders');
		
		
		wp_register_script('fiztrade-order-admin', plugins_url('fiztrade-order-admin.js', dirname(__FILE__)), array('jquery'));
		wp_enqueue_script('fiztrade-order-admin',null, null, null, true);		
		//wp_enqueue_script( 'wc-admin-order-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-order' . $suffix . '.js', array( 'wc-admin-meta-boxes' ), WC_VERSION );
	}

	if ($typenow == "dg_order") {
		wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css' );

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
				
		wp_register_style('dm-inv-dg-order-single', plugins_url( 'style-dg-order-single.css' , __FILE__ ));
		wp_enqueue_style('dm-inv-dg-order-single');

		
    	wp_enqueue_script( 'woocommerce_admin' );
    	wp_enqueue_script( 'farbtastic' );
    	wp_enqueue_script( 'ajax-chosen' );
    	wp_enqueue_script( 'chosen' );
    	wp_enqueue_script( 'jquery-ui-sortable' );
    	wp_enqueue_script( 'jquery-ui-autocomplete' );
		
		wp_enqueue_script( 'woocommerce_writepanel' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_media();
		wp_enqueue_script( 'ajax-chosen' );
		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'plupload-all' );

		$woocommerce_writepanel_params = array(
			'remove_item_notice' 			=> __( 'Are you sure you want to remove the selected items? If you have previously reduced this item\'s stock, or this order was submitted by a customer, you will need to manually restore the item\'s stock.', 'woocommerce' ),
			'i18n_select_items'				=> __( 'Please select some items.', 'woocommerce' ),
			'remove_item_meta'				=> __( 'Remove this item meta?', 'woocommerce' ),
			'remove_attribute'				=> __( 'Remove this attribute?', 'woocommerce' ),
			'name_label'					=> __( 'Name', 'woocommerce' ),
			'remove_label'					=> __( 'Remove', 'woocommerce' ),
			'click_to_toggle'				=> __( 'Click to toggle', 'woocommerce' ),
			'values_label'					=> __( 'Value(s)', 'woocommerce' ),
			'text_attribute_tip'			=> __( 'Enter some text, or some attributes by pipe (|) separating values.', 'woocommerce' ),
			'visible_label'					=> __( 'Visible on the product page', 'woocommerce' ),
			'used_for_variations_label'		=> __( 'Used for variations', 'woocommerce' ),
			'new_attribute_prompt'			=> __( 'Enter a name for the new attribute term:', 'woocommerce' ),
			'calc_totals' 					=> __( 'Calculate totals based on order items, discounts, and shipping?', 'woocommerce' ),
			'calc_line_taxes' 				=> __( 'Calculate line taxes? This will calculate taxes based on the customers country. If no billing/shipping is set it will use the store base country.', 'woocommerce' ),
			'copy_billing' 					=> __( 'Copy billing information to shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
			'load_billing' 					=> __( 'Load the customer\'s billing information? This will remove any currently entered billing information.', 'woocommerce' ),
			'load_shipping' 				=> __( 'Load the customer\'s shipping information? This will remove any currently entered shipping information.', 'woocommerce' ),
			'featured_label'				=> __( 'Featured', 'woocommerce' ),
			'prices_include_tax' 			=> esc_attr( get_option('woocommerce_prices_include_tax') ),
			'round_at_subtotal'				=> esc_attr( get_option( 'woocommerce_tax_round_at_subtotal' ) ),
			'no_customer_selected'			=> __( 'No customer selected', 'woocommerce' ),
			'plugin_url' 					=> $woocommerce->plugin_url(),
			'ajax_url' 						=> admin_url('admin-ajax.php'),
			'order_item_nonce' 				=> wp_create_nonce("order-item"),
			'add_attribute_nonce' 			=> wp_create_nonce("add-attribute"),
			'save_attributes_nonce' 		=> wp_create_nonce("save-attributes"),
			'calc_totals_nonce' 			=> wp_create_nonce("calc-totals"),
			'get_customer_details_nonce' 	=> wp_create_nonce("get-customer-details"),
			'search_products_nonce' 		=> wp_create_nonce("search-products"),
			'calendar_image'				=> $woocommerce->plugin_url().'/assets/images/calendar.png',
			'post_id'						=> $post->ID,
			'base_country'					=> $woocommerce->countries->get_base_country(),
			'currency_format_num_decimals'	=> absint( get_option( 'woocommerce_price_num_decimals' ) ),
			'currency_format_symbol'		=> get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'	=> esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
			'currency_format_thousand_sep'	=> esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
			'currency_format'				=> esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS
			'product_types'					=> array_map( 'sanitize_title', get_terms( 'product_type', array( 'hide_empty' => false, 'fields' => 'names' ) ) ),
			'default_attribute_visibility'  => apply_filters( 'default_attribute_visibility', false ),
			'default_attribute_variation'   => apply_filters( 'default_attribute_variation', false )
		 );

		wp_localize_script( 'woocommerce_writepanel', 'woocommerce_writepanel_params', $woocommerce_writepanel_params );
	}
}
add_action('admin_enqueue_scripts', 'dm_inv_dg_order_styles', 30); // make sure this runs after woocommerce registers the scripts

/**
 * Displays the order data meta box.
 *
 * @access public
 * @param mixed $post
 * @return void
 */
function dm_inv_dg_order_data_meta_box($post) {
	global $post, $wpdb, $thepostid, $theorder, $order_status, $woocommerce;

	$thepostid = absint( $post->ID );

	if ( ! is_object( $theorder ) )
		$theorder = new DG_Order( $thepostid );

	$order = $theorder;

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

	// Customer user
	$customer_user = absint( get_post_meta( $post->ID, '_customer_user', true ) );

	// Order status
	$order_status = $order->get_status();
	if ( $order_status ) {
		$order_status = sanitize_title( $order_status );
	} else {
		$order_status = sanitize_title( apply_filters( 'woocommerce_default_order_status', 'pending' ) );
	}
	
	// did the user ask for a price lock?
	$lock = get_transient('lock-'. get_current_user_id());
	if ($lock !== false) {
		$lock_prices = $lock['prices'];
		
		$lock_total = 0;
		foreach ($lock_prices as $item) {
			$lock_total += $item['amount'];
		}
	}

	if ( empty( $post->post_title ) )
		$order_title = 'Order';
	else
		$order_title = $post->post_title;
	?>
	<style type="text/css">
		#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
	</style>
	<div class="panel-wrap woocommerce">
		<input name="post_title" type="hidden" value="<?php echo esc_attr( $order_title ); ?>" />
		<input name="post_status" type="hidden" value="publish" />
		<div id="order_data" class="panel">

			<h2><?php _e( 'Dillon Gage Order Details', 'woocommerce' ); ?></h2>
			<p class="order_number"><?php

				echo __( 'Order number', 'woocommerce' ) . ' ' . esc_html( $order->get_order_number() ) . '. ';

				$ip_address = get_post_meta( $post->ID, '_customer_ip_address', true );

				if ( $ip_address )
					echo __( 'Customer IP:', 'woocommerce' ) . ' ' . esc_html( $ip_address );

			?></p>

			<div class="order_data_column_container">
				<div class="order_data_column">

					<h4><?php _e( 'General Details', 'woocommerce' ); ?></h4>

					<p class="form-field"><label for="order_status"><?php _e( 'Order status:', 'woocommerce' ) ?></label>
					<select id="order_status" name="order_status" class="chosen_select">
						<?php
							$statuses = wc_get_order_statuses();
							foreach ( $statuses as $status => $status_name ) {
								echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'wc-' . $order->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
							}
						?>
					</select></p>

					<p class="form-field last"><label for="order_date"><?php _e( 'Order Date:', 'woocommerce' ) ?></label>
						<input type="text" class="date-picker-field" name="order_date" id="order_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $post->post_date ) ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" /> @ <input type="text" class="hour" placeholder="<?php _e( 'h', 'woocommerce' ) ?>" name="order_date_hour" id="order_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />:<input type="text" class="minute" placeholder="<?php _e( 'm', 'woocommerce' ) ?>" name="order_date_minute" id="order_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />
					</p>

				</div> 
				<div class="order_data_column">
				
					<h4>
					<?php 
						_e( 'Shipping Details', 'woocommerce' ); 
						if ($order->get_status() != 'completed') : ?> 
							<a class="edit_address" href="#">(<?php _e( 'Edit', 'woocommerce' ) ;?>)</a>
						<?php endif; ?>
					</h4>
					<?php
						$shipping_data = apply_filters('woocommerce_admin_shipping_fields', array(
							'first_name' => array(
								'label' => __( 'First Name', 'woocommerce' ),
								'show'	=> false
								),
							'last_name' => array(
								'label' => __( 'Last Name', 'woocommerce' ),
								'show'	=> false
								),
							'company' => array(
								'label' => __( 'Company', 'woocommerce' ),
								'show'	=> false
								),
							'address_1' => array(
								'label' => __( 'Address 1', 'woocommerce' ),
								'show'	=> false
								),
							'address_2' => array(
								'label' => __( 'Address 2', 'woocommerce' ),
								'show'	=> false
								),
							'city' => array(
								'label' => __( 'City', 'woocommerce' ),
								'show'	=> false
								),
							'postcode' => array(
								'label' => __( 'Postcode', 'woocommerce' ),
								'show'	=> false
								),
							'country' => array(
								'label' => __( 'Country', 'woocommerce' ),
								'show'	=> false,
								'type'	=> 'select',
								'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + $woocommerce->countries->get_allowed_countries()
								),
							'state' => array(
								'label' => __( 'State/County', 'woocommerce' ),
								'show'	=> false
								),
							) );

						// Display values
						echo '<div class="address">';
							
							if ($order->shipping_method == 'ship_to_me') {
								echo '<p><strong>' . __( 'Shipping Method', 'woocommerce' ) . ':</strong><br/>Ship to Dealer</p>';
							} else if ($order->shipping_method == 'hold') {
								echo '<p><strong>' . __( 'Shipping Method', 'woocommerce' ) . ':</strong><br/>Dropship Hold</p>';
								echo '<p><strong>' . __( 'Address', 'woocommerce' ) . ':</strong><br/> ' . $order->get_formatted_shipping_address() . '</p>';
							} else if ($order->shipping_method == 'drop_ship') {
								echo '<p><strong>' . __( 'Shipping Method', 'woocommerce' ) . ':</strong><br/>Dropship</p>';
								echo '<p><strong>' . __( 'Address', 'woocommerce' ) . ':</strong><br/> ' . $order->get_formatted_shipping_address() . '</p>';
							} else {
								echo '<p class="none_set">' . __( 'Delivery method not set.', 'woocommerce' ) . '</p>';
							}
							// TODO: store method
							
							if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) {
								if ( isset( $field['show'] ) && $field['show'] === false )
									continue;
								$field_name = 'shipping_' . $key;
								if ( $order->$field_name )
									echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . esc_html( $order->$field_name ) . '</p>';
							}

						echo '</div>';

						// Display form
						echo '<div class="edit_address">';
						// <p><button class="button load_customer_shipping">' . __( 'Load shipping address', 'woocommerce' ) . '</button> <button class="button billing-same-as-shipping">'. __( 'Copy from billing', 'woocommerce' ) . '</button></p>';
						// TODO: add store option
						woocommerce_wp_radio( array(
							'id' => '_shipping_method',
							'options' => array( 
								'drop_ship' => 'Dropship',
								'hold' => 'Dropship Hold', 
								'ship_to_me' => 'Ship to Dealer'
							),
							'class' => 'select'
						));
						echo '<div class="cf"></div>';
						
						
						if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) {
							if ( ! isset( $field['type'] ) )
								$field['type'] = 'text';
							switch ( $field['type'] ) {
								case "select" :
									woocommerce_wp_select( array( 'id' => '_shipping_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
								break;
								default :
									woocommerce_wp_text_input( array( 'id' => '_shipping_' . $key, 'label' => $field['label'] ) );
								break;
							}
						}

						echo '</div>';

						do_action( 'woocommerce_admin_order_data_after_shipping_address', $order );
					?>	
			</div>
			<div class="order_data_column">
				<?php if ($order_status == 'completed') : 
					// leave it blank
				elseif (isset($lock_total)) : ?>
					<div id="execute-area">
						<p class="price"><?php echo woocommerce_price($lock_total); ?></p>
						<button type="submit" name="save" value="execute" class="button">Execute Trade</button>
						<p id="countdown-area">This price good for <span id="countdown">20</span> seconds.</p>
					</div>
				<?php else : ?>
					<div id="lock-area">
						<button type="submit" name="save" value="lock" class="button">Lock Order Price</button>
						<p>Click the button to show the price you will pay for this order.</p>
					</div>
				<?php endif; ?>
			</div>
			<div class="clear"></div>
		</div>
	</div>
	<?php
}

function dm_inv_dg_order_item_header () {
	if (get_post_type() != 'dg_order')
		return;
		
	echo '<th>Customer Order</th>';
}
add_action('woocommerce_admin_order_item_headers', 'dm_inv_dg_order_item_header');

// adds a column to the shop_order table
function dm_inv_dg_order_item_column ($product, $item, $item_id) {
	if (get_post_type() != 'dg_order')
		return;
	
	$order_id = $item['item_meta']['_source_order'][0];
	
	echo '<td>';
	
	echo '<a href="'. admin_url('post.php?post='. $order_id) .'&action=edit">'. $order_id .'</a>';
	
	echo '</td>';
}
add_action('woocommerce_admin_order_item_values', 'dm_inv_dg_order_item_column', 10, 3);

// hide custom DG Order meta
function dm_inv_dg_order_hide_meta ($to_hide) {
	$to_hide[] = '_source_order';
	$to_hide[] = '_source_item';
	return $to_hide;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'dm_inv_dg_order_hide_meta');

// hide totals panel on DG Order
function dm_inv_dg_order_hide_panel ($order) {
	if (get_class($order) == 'DG_Order')
		echo '<style> .wc-order-totals-items { display: none; } </style>';
}
add_action( 'woocommerce_order_item_add_line_buttons', 'dm_inv_dg_order_hide_panel' );

// flag prevents multiple saves
function dm_inv_restrict_save () {
	global $dg_meta_done;
	$dg_meta_done = 0;
}
add_action('init', 'dm_inv_restrict_save');

/**
 * Save the order data meta box.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function dm_inv_process_dg_order_meta( $post_id, $post ) {
	global $wpdb, $woocommerce, $dg_meta_done;
	
	if ($dg_meta_done == 1)
		return;
		
	if ($_POST['save'] == 'forget')
		return;
		
	if ($_POST['save'] == 'execute') {
		$result = dm_orders_execute($post_id);
		$dg_meta_done = 1;
		//$woocommerce_errors[] = 'Execute result: '. print_r($result, true);
		
		if (isset($result['error']))
			WC_Admin_Meta_Boxes::add_error('Couldn\'t execute trade: '. $result['error'], 'error');
		else
			$confirmation = $result['confirmationNumber'][0];			
	}

	dm_inv_save_dg_order_meta($post_id);


	// Order items + fees
	if ( isset( $_POST['order_item_id'] ) ) {

		$get_values = array( 'order_item_id', 'order_item_name', 'order_item_qty', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'order_item_tax_class' );

		foreach( $get_values as $value )
			$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();

		foreach ( $order_item_id as $item_id ) {

			$item_id = absint( $item_id );

			if ( isset( $order_item_name[ $item_id ] ) )
				$wpdb->update(
					$wpdb->prefix . "woocommerce_order_items",
					array( 'order_item_name' => woocommerce_clean( $order_item_name[ $item_id ] ) ),
					array( 'order_item_id' => $item_id ),
					array( '%s' ),
					array( '%d' )
				);

			if ( isset( $order_item_qty[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $order_item_qty[ $item_id ] ) );

		 	if ( isset( $item_tax_class[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_tax_class', woocommerce_clean( $item_tax_class[ $item_id ] ) );

		 	if ( isset( $line_subtotal[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_line_subtotal', woocommerce_clean( $line_subtotal[ $item_id ] ) );

		 	if ( isset(  $line_subtotal_tax[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_clean( $line_subtotal_tax[ $item_id ] ) );

		 	if ( isset( $line_total[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_line_total', woocommerce_clean( $line_total[ $item_id ] ) );

		 	if ( isset( $line_tax[ $item_id ] ) )
		 		wc_update_order_item_meta( $item_id, '_line_tax', woocommerce_clean( $line_tax[ $item_id ] ) );

		 	// Clear meta cache
		 	wp_cache_delete( $item_id, 'order_item_meta' );
		}
	}

	// Save meta
	$meta_keys 		= isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : array();
	$meta_values 	= isset( $_POST['meta_value'] ) ? $_POST['meta_value'] : array();

	foreach ( $meta_keys as $id => $meta_key ) {
		$meta_value = ( empty( $meta_values[ $id ] ) && ! is_numeric( $meta_values[ $id ] ) ) ? '' : $meta_values[ $id ];
		$wpdb->update(
			$wpdb->prefix . "woocommerce_order_itemmeta",
			array(
				'meta_key' => $meta_key,
				'meta_value' => $meta_value
			),
			array( 'meta_id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	// Order data saved, now get it so we can manipulate status
	$order = new DG_Order( $post_id );	
	
	// create a lock if the user asked for one
	if ($_POST['save'] == 'lock') {
		$trade = get_post_type($post_id) == 'dg_order' ? 'buy' : 'sell';
		if ($trade == 'buy' && !isset( $_POST['_shipping_method'] )) {
			WC_Admin_Meta_Boxes::add_error('Please set a shipping method before locking price.', 'error');
		} else {
			$response = dm_orders_lock_prices($order, $trade);
			if (isset($response['error']))
				WC_Admin_Meta_Boxes::add_error('FizTrade lock failed: '. $response['error'], 'error');
		}
	}

	// Order status
	$type_str = str_replace('dg_', '', get_post_type($post_id));
	if ($_POST['order_status'] == 'wc-completed') {
		WC_Admin_Meta_Boxes::add_error('Can\'t manually set '. $type_str .' complete. Please use the Lock Price and Execute Trade buttons instead.', 'error');
	} elseif (isset($confirmation)) {
		// after trade execution, store confirmation number, set status to completed
		$order->update_status('completed', 'FizTrade accepted '. $type_str .'.  Confirmation number: '. $confirmation);
	} else {
		$order->update_status( $_POST['order_status'] );
	}

	// Handle button actions
	if ( ! empty( $_POST['wc_order_action'] ) ) {

		$action = woocommerce_clean( $_POST['wc_order_action'] );

		if ( strstr( $action, 'send_email_' ) ) {

			do_action( 'woocommerce_before_resend_order_emails', $order );

			$mailer = $woocommerce->mailer();

			$email_to_send = str_replace( 'send_email_', '', $action );

			$mails = $mailer->get_emails();

			if ( ! empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == $email_to_send ) {
						$mail->trigger( $order->id );
					}
				}
			}

			do_action( 'woocommerce_after_resend_order_email', $order, $email_to_send );

		} else {

			do_action( 'woocommerce_order_action_' . sanitize_title( $action ), $order );

		}
	}

	delete_transient( 'woocommerce_processing_order_count' );
}
add_action( 'woocommerce_process_dg_order_meta', 'dm_inv_process_dg_order_meta', 10, 2 );

/**
 * Save meta boxes
 *
 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */
function dm_inv_dg_order_meta_boxes_save( $post_id, $post ) {
	if ( empty( $post_id ) || empty( $post ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( is_int( wp_is_post_revision( $post ) ) ) return;
	if ( is_int( wp_is_post_autosave( $post ) ) ) return;
	if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) return;
	if ( !current_user_can( 'edit_post', $post_id )) return;
	if ($post->post_type != 'dg_order' && $post->post_type != 'dg_offer') return;

	do_action( 'woocommerce_process_dg_order_meta', $post_id, $post );

	//woocommerce_meta_boxes_save_errors();
	WC_Admin_Meta_Boxes::save_errors();
}
add_action( 'save_post', 'dm_inv_dg_order_meta_boxes_save', 11, 2 );

// saves non-line item info
function dm_inv_save_dg_order_meta($post_id, $source = null) {
	global $wpdb, $woocommerce;
	
	if ($source == null) {
		$source = $_POST;
	}
	$debug = 'Source array: '. print_r($source, true);
	// Add key
	add_post_meta( $post_id, '_order_key', uniqid('order_'), true );

	// Update post data
	//update_post_meta( $post_id, '_shipping_method', woocommerce_clean( $source['_shipping_method'] ) );
	update_post_meta( $post_id, '_shipping_first_name', woocommerce_clean( $source['_shipping_first_name'] ) );
	update_post_meta( $post_id, '_shipping_last_name', woocommerce_clean( $source['_shipping_last_name'] ) );
	update_post_meta( $post_id, '_shipping_company', woocommerce_clean( $source['_shipping_company'] ) );
	update_post_meta( $post_id, '_shipping_address_1', woocommerce_clean( $source['_shipping_address_1'] ) );
	update_post_meta( $post_id, '_shipping_address_2', woocommerce_clean( $source['_shipping_address_2'] ) );
	update_post_meta( $post_id, '_shipping_city', woocommerce_clean( $source['_shipping_city'] ) );
	update_post_meta( $post_id, '_shipping_postcode', woocommerce_clean( $source['_shipping_postcode'] ) );
	update_post_meta( $post_id, '_shipping_country', woocommerce_clean( $source['_shipping_country'] ) );
	update_post_meta( $post_id, '_shipping_state', woocommerce_clean( $source['_shipping_state'] ) );
	update_post_meta( $post_id, '_order_shipping', woocommerce_clean( $source['_order_shipping'] ) );
	update_post_meta( $post_id, '_cart_discount', woocommerce_clean( $source['_cart_discount'] ) );
	update_post_meta( $post_id, '_order_discount', woocommerce_clean( $source['_order_discount'] ) );
	update_post_meta( $post_id, '_order_total', woocommerce_clean( $source['_order_total'] ) );
	//update_post_meta( $post_id, '_customer_user', absint( $source['customer_user'] ) );

	if ( isset( $source['_order_tax'] ) )
		update_post_meta( $post_id, '_order_tax', woocommerce_clean( $source['_order_tax'] ) );

	if ( isset( $source['_order_shipping_tax'] ) )
		update_post_meta( $post_id, '_order_shipping_tax', woocommerce_clean( $source['_order_shipping_tax'] ) );

	// Shipping method handling
	if ( get_post_meta( $post_id, '_shipping_method', true ) !== stripslashes( $source['_shipping_method'] ) ) {

		$shipping_method = woocommerce_clean( $source['_shipping_method'] );

		update_post_meta( $post_id, '_shipping_method', $shipping_method );
	}

	if ( get_post_meta( $post_id, '_shipping_method_title', true ) !== stripslashes( $source['_shipping_method_title'] ) ) {

		$shipping_method_title = woocommerce_clean( $source['_shipping_method_title'] );

		if ( ! $shipping_method_title ) {

			$shipping_method = esc_attr( $source['_shipping_method'] );
			$methods = $woocommerce->shipping->load_shipping_methods();

			if ( isset( $methods ) && isset( $methods[ $shipping_method ] ) )
				$shipping_method_title = $methods[ $shipping_method ]->get_title();
		}

		update_post_meta( $post_id, '_shipping_method_title', $shipping_method_title );
	}

	// Payment method handling
	// if ( get_post_meta( $post_id, '_payment_method', true ) !== stripslashes( $source['_payment_method'] ) ) {

		// $methods 				= $woocommerce->payment_gateways->payment_gateways();
		// $payment_method 		= woocommerce_clean( $source['_payment_method'] );
		// $payment_method_title 	= $payment_method;

		// if ( isset( $methods) && isset( $methods[ $payment_method ] ) )
			// $payment_method_title = $methods[ $payment_method ]->get_title();

		// update_post_meta( $post_id, '_payment_method', $payment_method );
		// update_post_meta( $post_id, '_payment_method_title', $payment_method_title );
	// }

	// Update date
	if ( empty( $source['order_date'] ) ) {
		$date = current_time('timestamp');
	} else {
		$date = strtotime( $source['order_date'] . ' ' . (int) $source['order_date_hour'] . ':' . (int) $source['order_date_minute'] . ':00' );
	}

	$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s WHERE ID = %s", date_i18n( 'Y-m-d H:i:s', $date ), $post_id ) );


	// Tax rows
	if ( isset( $source['order_taxes_id'] ) ) {

		$get_values = array( 'order_taxes_id', 'order_taxes_rate_id', 'order_taxes_amount', 'order_taxes_shipping_amount' );

		foreach( $get_values as $value )
			$$value = isset( $source[ $value ] ) ? $source[ $value ] : array();

		foreach( $order_taxes_id as $item_id ) {

			$item_id  = absint( $item_id );
			$rate_id  = absint( $order_taxes_rate_id[ $item_id ] );

			if ( $rate_id ) {
				$rate     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $rate_id ) );
				$label    = $rate->tax_rate_name ? $rate->tax_rate_name : $woocommerce->countries->tax_or_vat();
				$compound = $rate->tax_rate_compound ? 1 : 0;

				$code = array();

				$code[] = $rate->tax_rate_country;
				$code[] = $rate->tax_rate_state;
				$code[] = $rate->tax_rate_name ? $rate->tax_rate_name : 'TAX';
				$code[] = absint( $rate->tax_rate_priority );
				$code   = strtoupper( implode( '-', array_filter( $code ) ) );
			} else {
				$code  = '';
				$label = $woocommerce->countries->tax_or_vat();
			}

			$wpdb->update(
				$wpdb->prefix . "woocommerce_order_items",
				array( 'order_item_name' => woocommerce_clean( $code ) ),
				array( 'order_item_id' => $item_id ),
				array( '%s' ),
				array( '%d' )
			);

			woocommerce_update_order_item_meta( $item_id, 'rate_id', $rate_id );
			woocommerce_update_order_item_meta( $item_id, 'label', $label );
			woocommerce_update_order_item_meta( $item_id, 'compound', $compound );

			if ( isset( $order_taxes_amount[ $item_id ] ) )
		 		woocommerce_update_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( $order_taxes_amount[ $item_id ] ) );

		 	if ( isset( $order_taxes_shipping_amount[ $item_id ] ) )
		 		woocommerce_update_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( $order_taxes_shipping_amount[ $item_id ] ) );
		}
	}
	return $debug;
}

?>