<html>
<head>
	<?php
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
	require_once dirname(dirname(__FILE__)) . '/woocommerce/woocommerce.php';
	global $wpdb;
	?>
	<link rel='stylesheet' href="<?php echo plugins_url(); ?>/dm-central/shipping-label.css" />
</head>
<body>
	<?php
	require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
	require_once dirname(dirname(__FILE__)) . '/woocommerce/woocommerce.php';
	global $wpdb;
	
	// figure out from which site we came
	$sites = get_blog_list(0, 'all');
	foreach ($sites as $site) {
		if ( strpos($_SERVER['HTTP_REFERER'], $site['domain'] . $site['path']) !== false) {
			$current_site = $site;
			break;
		}
	}
	// get dealer shipping address
	$serialized = $wpdb->get_var( "SELECT option_value FROM wp_". $site['blog_id'] ."_options WHERE option_name='imag_mall_options'" );
	$options = unserialize($serialized);
	$fields = array(
		'shipping_alias',
		'shipping_address1',
		'shipping_address2',
		'shipping_city',
		'shipping_state',
		'shipping_zip'
	);
	
	// get query from referer
	$splits = explode('?', $_SERVER['HTTP_REFERER']);
	parse_str($splits[1]);
	
	
	echo '<div id="sender" class="address">';
	
	echo $options['shipping_alias'] . '<br/>';
	echo $options['shipping_address1'] . '<br/>';
	if (!empty($options['shipping_address2']))
		echo $options['shipping_address2'] . '<br/>';
	echo $options['shipping_city'] .', '. $options['shipping_state'] .' '. $options['shipping_zip'];
	
	echo '</div>';
	echo '<div id="print"><button id="btnPrint" onclick="window.print()">Print</button></div>';
	$order = new WC_Order($post);
	echo '<div id="addressee" class="address">'. $order->get_formatted_shipping_address() .'</div>';
	
	?>
	<noscript>
		<style>#print { display:none; }</style>
	</noscript>
</body>
</html>