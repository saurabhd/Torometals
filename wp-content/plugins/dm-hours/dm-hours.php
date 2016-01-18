<?php
/*
Plugin Name: Digital Metals Market Hours
Description: Manage hours that WooCommerce will sell various metals, and create exceptions
Author URI: http://www.imaginuity.com
Author: Imaginuity & Dillon Gage
Version: 2.0.0
*/

// check for required version
function dm_hours_check_requirements () {
	$min_dm_version = '2.0.1';
	$dm_version = get_option('digitalmetals_db_version');
	
	if ( !function_exists('dm_create_products_table') ) {		
		deactivate_plugins( basename( __FILE__ ) );
		wp_die('<p>The <strong>Digital Metals Hours</strong> plugin requires the <strong>Digital Metals</strong> to be active.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
	}
	if ( version_compare( $dm_version, $min_dm_version, '<' ) ) {
		deactivate_plugins( basename( __FILE__ ) );
		wp_die('<p>Please update the <strong>Digital Metals</strong> plugin to at least version '.$min_dm_version.' before activating this plugin.</p>','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
	}
}
register_activation_hook( __FILE__, 'dm_hours_check_requirements' );

// set default options
function dm_hours_default_options () {
	$tz = ini_get('date.timezone');
	if (!empty($tz))
		$tz = trim($tz);
	else
		$tz = 'America/Chicago';
	
	add_option( 'market_timezone', $tz );
}
register_activation_hook( __FILE__, 'dm_hours_default_options' );


// check if the metal market is open
function dm_hours_fiztrade_add_to_cart () {
	global $product;
	if ($product->product_type != 'fiztrade')
		return;
	
	$metal = dm_inv_get_metal($product->dg_id);
	
	$hours = dm_hours_get_hours_check_exceptions($metal .'_hours');  // apply exceptions to market periods
	
	$tz = new DateTimeZone(get_option('market_timezone'));
	$now = new DateTime(null, $tz);
	
	$day = $now->format('w');
	
	$dayStart = DateTime::createFromFormat('g:i a O', $hours['dm_hours_week_'. $day .'_start'] .' '. get_option('market_timezone'));
	$dayEnd = DateTime::createFromFormat('g:i a O', $hours['dm_hours_week_'. $day .'_end'] .' '. get_option('market_timezone'));
	
	if ($dayStart == null || $dayEnd == null) { // missing field in market hours
		$open = false;
	} else if ($dayStart == $dayEnd) { // 24 hour
		$open = true;
	} else {
		if ($dayEnd <= $dayStart) {
			$dayEnd->add(new DateInterval('P1D')); // correct for midnight and post-midnight closings
		}
		if ($now < $dayStart || $now > $dayEnd)
			$open = false;
		else
			$open = true;
	}
	
	if (!$open) {
		global $hours_string;
		$hours_string = apply_filters('dm_hours_string', $hours['hours_string']);
		// cancel all actions on add_to_cart
		remove_all_actions('woocommerce_fiztrade_add_to_cart');
		// add our message
		add_action('woocommerce_fiztrade_add_to_cart', 'dm_hours_out_of_hours_msg');
	}
}
add_action('woocommerce_before_single_product', 'dm_hours_fiztrade_add_to_cart');

function dm_hours_out_of_hours_msg () {
	global $hours_string;
	echo apply_filters('dm_out_of_hours_msg', 
		'<p id="out-of-hours">Item not available for trading at this time. Please try again during '. $metal .
		' market <a href="#" id="market-hours-link">hours</a>.</p>') .
		'<div id="market-hours" style="display:none">'. $hours_string .'</div>';
}

// returns hours defined by optionName modified by the exceptions
// also checks if fiztrade hours are selected within the optionName schedule
function dm_hours_get_hours_check_exceptions($optionName) {
	
	$prefix = strtolower(str_replace('_hours', '', $optionName));
	if (in_array($prefix, array('gold', 'silver', 'platinum', 'palladium'))) 
		$hours = check_fiztrade_schedule(get_option($optionName), $prefix);
	else
		$hours = get_option($optionName);
		
	$hours_exceptions = get_option('dm_hours_exceptions');

	$week = date('Y\WW');
	$week_start = strtotime($week."1");
	$week_end = strtotime($week."7");	
	
	// Exception 1 
	if ($hours_exceptions["dm_hours_a_week_start"] <= $week_start && $hours_exceptions["dm_hours_a_week_end"] >= $week_end) {

		for($i=0;$i<=6;$i++) {
			$counter = $i%7;	
			$hours["dm_hours_week_" . $counter ."_start"] = $hours_exceptions["dm_hours_a_week_" . $counter . "_start"];
			$hours["dm_hours_week_" . $counter ."_end"]   = $hours_exceptions["dm_hours_a_week_" . $counter . "_end"];
		}
		$hours["hours_string"] = $hours_exceptions["hours_string_a"]; 

	// Exception 2		
	} elseif ($hours_exceptions["dm_hours_b_week_start"] <= $week_start && $hours_exceptions["dm_hours_b_week_end"] >= $week_end) {	
	
		for($i=0;$i<=6;$i++) {
			$counter = $i%7;	
			$hours["dm_hours_week_" . $counter ."_start"] = $hours_exceptions["dm_hours_b_week_" . $counter . "_start"];
			$hours["dm_hours_week_" . $counter ."_end"] = $hours_exceptions["dm_hours_b_week_" . $counter . "_end"];			
		}
		$hours["hours_string"] = $hours_exceptions["hours_string_b"]; 
		
	// Exception 3		
	} elseif ($hours_exceptions["dm_hours_c_week_start"] <= $week_start && $hours_exceptions["dm_hours_c_week_end"] >= $week_end) {	
	
		for($i=0;$i<=6;$i++) {
			$counter = $i%7;	
			$hours["dm_hours_week_" . $counter ."_start"] = $hours_exceptions["dm_hours_c_week_" . $counter . "_start"];
			$hours["dm_hours_week_" . $counter ."_end"] = $hours_exceptions["dm_hours_c_week_" . $counter . "_end"];			
		}
		$hours["hours_string"] = $hours_exceptions["hours_string_c"]; 

	}	
		
	return $hours;
	
}

function dm_hours_draw_hours($prefix = 'dm_hours') {

	$output = "";

	$hours = dm_hours_get_hours_check_exceptions($prefix); // check exceptions
	
	$alternate_class[0] = '';
	$alternate_class[1] = ' class="alternate"';
	$alternate = 0;
	
			
	$output .= '<h2>';
					
	$week = date('Y\WW');
	$week_start = strtotime($week."1");
	$week_end = strtotime($week."7");

	// if the week starts and ends in the same month
	if (date('m', $week_start) == date('m', $week_end)) {
		$date_string = date('F j', $week_start) . '&ndash;' . date('j', $week_end);  	
	} else { // if the week spans the end and beginning of a month
		$date_string = date('F j', $week_start) . ' &ndash; ' . date('F j', $week_end);  	
	}

	$output .= "Week of " . $date_string;
	$output .= '</h2>';


	$hours_array = array();
	$index = -1;

	$output .= '<table class="mall-hours">';
	for($i=0;$i<=6;$i++) {
	
		$alternate = abs($alternate-1);
		$counter = $i%7;
		
		if ($hours["dm_hours_week_" . $counter . "_start"] == '' &&
			$hours["dm_hours_week_" . $counter . "_end"] == '') {
			$time = "Closed";
		} else if ($hours["dm_hours_week_" . $counter . "_start"] == $hours["dm_hours_week_" . $counter . "_end"]) {
			$time = "All day";
		} else {
			$time = str_replace(" ","",$hours["dm_hours_week_" . $counter . "_start"]) . "&ndash;" . str_replace(" ","", $hours["dm_hours_week_" . $counter . "_end"]);
		}
		
		/*
		if ($hours["dm_hours_week_" . $counter . "_start"] != $hours["dm_hours_week_" . $counter . "_end"]) {
			$time = str_replace(" ","",$hours["dm_hours_week_" . $counter . "_start"]) . "&ndash;" . str_replace(" ","", $hours["dm_hours_week_" . $counter . "_end"]);
		} else {
			$time = "Closed";
		}
		*/
		$output .= '<tr' . $alternate_class[$alternate] . '><th>' . date('F j',strtotime($week.$i)) . '</th><td>' . date('l',strtotime($week.$i)) . '</td><td class="final">' . $time . "</td></tr>"; 
	
	}
	$output .= "</table>";
	
	$output = str_replace("01:","1:", $output);
	$output = str_replace("02:","2:", $output);
	$output = str_replace("03:","3:", $output);
	$output = str_replace("04:","4:", $output);
	$output = str_replace("05:","5:", $output);
	$output = str_replace("06:","6:", $output);
	$output = str_replace("07:","7:", $output);
	$output = str_replace("08:","8:", $output);
	$output = str_replace("09:","9:", $output);	
	
	return $output;
}

function dm_hours_draw_hours_sc ($atts) {
	extract(shortcode_atts( array(
		'metal' => 'all',  // gold, silver, platinum, or palladium
	), $atts));
	
	if ($metal == 'all') {
		foreach (array('gold', 'silver', 'platinum', 'palladium') as $metal) {
			$output .= '<h3>'. ucfirst($metal) .'</h3>';
			$output .= dm_hours_draw_hours($metal);		
		}
	} else {	
		$output .= '<h3>'. ucfirst($metal) .'</h3>';
		$output .= dm_hours_draw_hours($metal);
	}
	
	return $output;
}
add_shortcode("dm_hours", "dm_hours_draw_hours_sc", -1);

// add styles
function dm_hours_scripts () {
	if ($_GET['page'] == 'dm_hours' ||
		$_GET['page'] == 'dm_hours_exceptions') {
		wp_register_style('dm_hours', plugins_url('/style.css', __FILE__));
		wp_enqueue_style('dm_hours');
		wp_register_style('jquery_timepicker', plugins_url('/js/jquery-timepicker/jquery.timepicker.css', __FILE__));
		wp_enqueue_style('jquery_timepicker');
		wp_register_script('jquery_timepicker', plugins_url('/js/jquery-timepicker/jquery.timepicker.min.js', __FILE__), 'jquery');
		wp_enqueue_script('jquery_timepicker');
		wp_register_script('dm_hours', plugins_url('/script.js', __FILE__), 'jquery jquery_timepicker');
		wp_enqueue_script('dm_hours');
	}
}
add_action('admin_enqueue_scripts', 'dm_hours_scripts');

function dm_hours_frontend_scripts () {
	wp_register_style('dm_hours', plugins_url('/style-frontend.css', __FILE__));
	wp_register_script('dm_hours', plugins_url('/script-frontend.js', __FILE__), array('jquery'));
}	
add_action('wp_enqueue_scripts', 'dm_hours_frontend_scripts');

function dm_hours_enqueue_frontend_scripts () {
	global $hours_string;
	if (!empty($hours_string)) {
		wp_enqueue_style('dm_hours');
		wp_enqueue_script('dm_hours');
	}
}
add_action('wp_footer', 'dm_hours_enqueue_frontend_scripts');

// ==== Options Page ====
add_action('admin_init', 'dm_hours_init' );
add_action('admin_menu', 'dm_hours_add_page');

// Init plugin options to white list our options
function dm_hours_init(){
	register_setting( 'imag_market_hours', 'gold_hours', 'dm_hours_validate' );
	register_setting( 'imag_market_hours', 'silver_hours', 'dm_hours_validate' );
	register_setting( 'imag_market_hours', 'platinum_hours', 'dm_hours_validate' );
	register_setting( 'imag_market_hours', 'palladium_hours', 'dm_hours_validate' );
	register_setting( 'imag_market_hours', 'market_timezone' );
	register_setting( 'dm_hours_exceptions', 'dm_hours_exceptions', 'dm_hours_exceptions_validate' );
}

// Add menu page
function dm_hours_add_page() {

	add_menu_page( 'Market Hours', 'Market Hours', 'manage_options', 'dm_hours', 'dm_hours_market_do_page', plugins_url('img/clock.png',__FILE__), 57 );
	//add_submenu_page('dm_hours', 'Market Periods', 'Market Periods', 'manage_options', 'dm_hours_market', 'dm_hours_market_do_page');
	add_submenu_page('dm_hours', 'Exceptions', 'Exceptions', 'manage_options', 'dm_hours_exceptions', 'dm_hours_exceptions_do_page');
}

// Draw the market hours page
function dm_hours_market_do_page() {
	?>
	
	<div class="wrap">
		<h2>Market Hours</h2>
		<p class="explanation">These are the hours this website will trade FizTrade products with your customers.
		If a user visits the page of a product linked to FizTrade, and if they are outside of the market hours
		set here, they will be asked to come back during these hours. </p>
		<p class="explanation"><strong>Note:</strong> If you set your market hours to be longer than those of Dillon Gage, 
		orders made outside of the DG hours will NOT auto-forward.  You will have to wait until the market opens to forward them manually.</p>
		<p class="explanation">Click the checkbox next to an open/close time to use the time the Dillon Gage markets open or close.</p>
		<form method="post" action="options.php" id="dm_hours">
			<?php settings_fields('imag_market_hours'); ?>
			<?php
				$utc = new DateTimeZone('UTC');
				$dt = new DateTime('now', $utc);
				
				//echo '<select name="market_timezone">';
				$tzList = array();
				foreach(DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, 'US') as $tz) {
					$current_tz = new DateTimeZone($tz);
					$offset =  $current_tz->getOffset($dt);
					$transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
					$abbr = $transition[0]['abbr'];
					
					$tzList[$tz] = $tz. ' [' .$abbr. ' '. formatOffset($offset). ']';
					//echo '<option value="' .$tz. '">' .$tz. ' [' .$abbr. ' '. formatOffset($offset). ']</option>';
				}
				//echo '</select>';
				imaginuity_wp_select(array(
					'id' => 'market_timezone',
					'label' => 'Select Timezone',
					'class' => 'select',
					'value' => get_option('market_timezone') ? get_option('market_timezone') : 'America/Chicago',
					'options' => $tzList
				));
			?>
			<div class="secondary">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</div>			
			<div class="primary">
				<h2>Gold Market Hours</h2> 
				<a class="select-all check">Use FizTrade hours for all</a>
				<a class="select-all uncheck" style="display:none">Use my hours for all</a>
				<noscript>.select-all  { display:none; }</noscript>
				
				<?php $options = get_option('gold_hours'); ?>
				<?php echo dm_hours_draw_time_entry_fields("gold_hours","dm_hours_week_",$options); ?>
				<?php if ($options["hours_string"] != "") {echo '<div class="display">' . $options["hours_string"] . '</div>';} ?>
			</div>			
			<div class="primary">
				<h2>Silver Market Hours</h2>
				<a class="select-all check">Use FizTrade hours for all</a>
				<a class="select-all uncheck" style="display:none">Use my hours for all</a>
				<?php $options = get_option('silver_hours'); ?>
				<?php echo dm_hours_draw_time_entry_fields("silver_hours","dm_hours_week_",$options); ?>
				<?php if ($options["hours_string"] != "") {echo '<div class="display">' . $options["hours_string"] . '</div>';} ?>
			</div>			
			<div class="primary">
				<h2>Platinum Market Hours</h2>
				<a class="select-all check">Use FizTrade hours for all</a>
				<a class="select-all uncheck" style="display:none">Use my hours for all</a>
				<?php $options = get_option('platinum_hours'); ?>
				<?php echo dm_hours_draw_time_entry_fields("platinum_hours","dm_hours_week_",$options); ?>
				<?php if ($options["hours_string"] != "") {echo '<div class="display">' . $options["hours_string"] . '</div>';} ?>
			</div>			
			<div class="primary">
				<h2>Palladium Market Hours</h2>
				<a class="select-all check">Use FizTrade hours for all</a>
				<a class="select-all uncheck" style="display:none">Use my hours for all</a>
				<?php $options = get_option('palladium_hours'); ?>
				<?php echo dm_hours_draw_time_entry_fields("palladium_hours","dm_hours_week_",$options); ?>
				<?php if ($options["hours_string"] != "") {echo '<div class="display">' . apply_filters('dm_hours_string', $options["hours_string"]) . '</div>';} ?>
			</div>
		</form>
	</div>
	
	<?php	
}

// draw the exceptions page
function dm_hours_exceptions_do_page() {
	?>
<style type="text/css">
form#dm_hours {position: relative; margin-top: 1.5em;}
.secondary {position: absolute; top: 0; right: 11px;}
#dm_hours .primary label span,
#dm_hours .primary span.timeEntry_control {display: none !important;}
#dm_hours .primary label {clear: both;}
#dm_hours .primary dl {float: left;}
#dm_hours .primary dl.start {clear: both;}
#dm_hours .primary p,
#dm_hours .primary h3 {clear: both;}
#dm_hours .primary h3 {margin-top: 2em; padding-top: 2em; border-top: 1px solid #CCC;}
#dm_hours .primary h3.first {margin-top: 0em; padding-top: 0em; border-top: 0;}
#dm_hours .primary label,
#dm_hours .primary dt,
#dm_hours .primary dd {width: 100px; float: left; }
#dm_hours .primary .select {margin-bottom: 1em;}
#dm_hours .primary .select label {width: auto; float: none; clear: both; display: block; padding-bottom: .5em;}
#dm_hours .primary .select label span {width: 150px; display: block !important; float: left;}
#dm_hours .primary input {width: 80px;}
#dm_hours .primary div.display {clear: both; margin: 2em; padding: 1em; border: 1px solid #CCC; background: #F8F8F8; float: left;}
</style>
	
	<div class="wrap">
		<h2>Exceptions</h2>
		<p class="explanation">This page allows you to define exceptions to your market periods.  
		Using the Exception Starts/Ends boxes, select one or more weeks for which you'd like to store an exception.
		Then fill in the hours your markets will be open for that time period.  You may store up to three exceptions.</p>
		<form method="post" action="options.php" id="dm_hours">

		
			<?php settings_fields('dm_hours_exceptions'); ?>
			<?php $options = get_option('dm_hours_exceptions'); ?>
			
			<div class="secondary">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</div>			
			<div class="primary">

<?php
	$week = date('Y\WW');
	$week_start = strtotime($week."1");
	$week_end = strtotime($week."7");
?>

				<h3 class="first">Exception 1</h3>
				
				<?php 
					$start_value_selected = $options['dm_hours_a_week_start'];
					$end_value_selected   = $options['dm_hours_a_week_end'];
					$start_select = draw_date_range_select("dm_hours_exceptions[dm_hours_a_week_start]",$week_start,$start_value_selected);							
					$end_select = draw_date_range_select("dm_hours_exceptions[dm_hours_a_week_end]",$week_end,$end_value_selected);
				?>				
				<div class="select">
					<label>
						<span>Exception Starts:</span> <?php echo $start_select; ?>
					</label>
					<label>
						<span>Exception Ends:</span> <?php echo $end_select; ?>
					</label>
				</div>				
				
				<?php echo dm_hours_draw_time_entry_fields("dm_hours_exceptions","dm_hours_a_week_",$options); ?>
				<?php if ($options["hours_string_a"] != "") { echo '<div class="display">' . $options["hours_string_a"] . '</div>';} ?>
				

				<h3>Exception 2</h3>

				<?php 
					$start_value_selected = $options['dm_hours_b_week_start'];
					$end_value_selected   = $options['dm_hours_b_week_end'];
					$start_select = draw_date_range_select("dm_hours_exceptions[dm_hours_b_week_start]",$week_start,$start_value_selected);							
					$end_select = draw_date_range_select("dm_hours_exceptions[dm_hours_b_week_end]",$week_end,$end_value_selected);
				?>
				<div class="select">
					<label>
						<span>Exception Starts:</span> <?php echo $start_select; ?>
					</label>
					<label>
						<span>Exception Ends:</span> <?php echo $end_select; ?>
					</label>				
				</div>
				
				<?php echo dm_hours_draw_time_entry_fields("dm_hours_exceptions","dm_hours_b_week_",$options); ?>
				<?php if ($options["hours_string_b"] != "") { echo '<div class="display">' . $options["hours_string_b"] . '</div>'; } ?>	
                
                <h3>Exception 3</h3>
				
				<?php 
					$start_value_selected = $options['dm_hours_c_week_start'];
					$end_value_selected   = $options['dm_hours_c_week_end'];
					$start_select = draw_date_range_select("dm_hours_exceptions[dm_hours_c_week_start]",$week_start,$start_value_selected);							
					$end_select = draw_date_range_select("dm_hours_exceptions[dm_hours_c_week_end]",$week_end,$end_value_selected);
				?>				
				<div class="select">
					<label>
						<span>Exception Starts:</span> <?php echo $start_select; ?>
					</label>
					<label>
						<span>Exception Ends:</span> <?php echo $end_select; ?>
					</label>
				</div>				
				
				<?php echo dm_hours_draw_time_entry_fields("dm_hours_exceptions","dm_hours_c_week_",$options); ?>
				<?php if ($options["hours_string_c"] != "") { echo '<div class="display">' . $options["hours_string_c"] . '</div>';} ?>			

			</div>
		</form>
	</div>
	
	<?php	
}

function dm_hours_draw_time_entry_fields($name_array,$name_index_prefix,$options) {
	$days[0]["full"] = "Sunday";
	$days[0]["abbr"] = "Sun";
	$days[1]["full"] = "Monday";
	$days[1]["abbr"] = "Mon";	
	$days[2]["full"] = "Tuesday";
	$days[2]["abbr"] = "Tues";
	$days[3]["full"] = "Wednesday";
	$days[3]["abbr"] = "Wed";	
	$days[4]["full"] = "Thursday";
	$days[4]["abbr"] = "Thurs";
	$days[5]["full"] = "Friday";
	$days[5]["abbr"] = "Fri";
	$days[6]["full"] = "Saturday";
	$days[6]["abbr"] = "Sat";

	if (in_array($name_array, array('gold_hours', 'silver_hours', 'platinum_hours', 'palladium_hours'))) {
		$metal = str_replace('_hours', '', $name_array);
		$output .= '<input type="hidden" name="' . $name_array . '[' . $name_index_prefix .'metal]" value="'. $metal .'" />';
		$ft_schedule = get_site_option('fiztrade_schedule');
		// dunno why this is necessary, but it is
		if (is_string($ft_schedule)) {
			$ft_schedule = unserialize(trim($ft_schedule));
		}
	}
	
	for ($i=1; $i<=7; $i++) {
		$day_start = get_first_open($ft_schedule[$metal][$i%7]);
		$day_end = get_last_close($ft_schedule[$metal][$i%7]);
		if (!empty($day_start)) {
			// set time zone
			$tz = new DateTimeZone(get_option('market_timezone'));
			$day_start->setTimezone($tz);
			$day_end->setTimezone($tz);
			
			$ft_start = $day_start->format('h:i A');
			$ft_end = $day_end->format('h:i A');
		} else {
			$ft_start = $ft_end = '';
		}
		
		$output  .= '<dl class="time start ' . $name_index_prefix . $i%7 .'_start"><dt><label>'. $days[$i%7]['full'] .' <span>Start</span></label></dt><dd><input type="text" name="' . $name_array . '[' . $name_index_prefix . $i%7 .'_start]" value="' . $options[$name_index_prefix . $i%7 .'_start']. '" size="25" />';
		if ($metal) {
			$output  .= '<input type="text" disabled="disabled" class="fiztrade-hours" style="display:none" value="'. $ft_start .'">';
			$output  .= '<input type="checkbox" name="' . $name_array . '[' . $name_index_prefix . $i%7 .'_start_default]" title="Use FizTrade market open" '. checked($options[$name_index_prefix . $i%7 .'_start_default'], 'on', false) .'" />';
		}
		$output  .= '</dd></dl>';

		$output  .= '<dl class="time end   ' . $name_index_prefix . $i%7 .'_end"><dt><label><span>'. $days[$i%7]['full'] .' End</span></label></dt><dd><input type="text" name="' . $name_array . '[' . $name_index_prefix . $i%7 .'_end]" value="' . $options[$name_index_prefix . $i%7 .'_end']. '" size="25" />';
		if ($metal) {
			$output  .= '<input type="text" disabled="disabled" class="fiztrade-hours" style="display:none" value="'. $ft_end .'">';
			$output  .= '<input type="checkbox" name="' . $name_array . '[' . $name_index_prefix . $i%7 .'_end_default]" title="Use FizTrade market close" '. checked($options[$name_index_prefix . $i%7 .'_end_default'], 'on', false) .'" />';
		}
		$output  .= '</dd></dl>';
	}
		
	return $output;

}

function draw_date_range_select($select_name,$date,$value_selected,$select_blank_label="&ndash; Select &ndash;") {
	
	$output = "";
	
	$output .= '<select name="' . $select_name . '">';
	$output .= '<option>' . $select_blank_label . '</option>';
							
	$date = $date-604800;

	// check if the currently selected dates come before the range 
	if ($value_selected!="" && $value_selected<=$date) {
		$output .= '<option selected="selected" value="' . ($value_selected) . '">' . date('l, F j, Y', $value_selected) . '</option>';
	}
							
	// loop through dates 
	for($i=0;$i<=55;$i++) {
								
		// add a week to get this iteration's date
		$date = $date+604800;

		// add week to the select box and check if the date for this iteration is the one that was previously selected								
		$output .= '<option';
		if ($value_selected==$date) $output.= ' selected="selected"';  
		$output .= ' value="' . ($date) . '">' . date('l, F j, Y', $date) . '</option>';
							
	}
	
	// check if the currently selected dates come after the range 
	if ($value_selected!="" && $value_selected>$date) {
		$output .= '<option selected="selected" value="' . ($value_selected) . '">' . date('l, F j, Y', $value_selected) . '</option>';
	}
	
	$output .= '</select>';

	return $output;
					
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function dm_hours_validate($input) {
	$days[0]["full"] = "Sunday";
	$days[0]["abbr"] = "Sun";
	$days[1]["full"] = "Monday";
	$days[1]["abbr"] = "Mon";	
	$days[2]["full"] = "Tuesday";
	$days[2]["abbr"] = "Tues";
	$days[3]["full"] = "Wednesday";
	$days[3]["abbr"] = "Wed";	
	$days[4]["full"] = "Thursday";
	$days[4]["abbr"] = "Thurs";
	$days[5]["full"] = "Friday";
	$days[5]["abbr"] = "Fri";
	$days[6]["full"] = "Saturday";
	$days[6]["abbr"] = "Sat";
	
	$hours_array = array();
	$index = -1;
	
	$metal = $input['dm_hours_week_metal'];
	$ft_schedule = get_site_option('fiztrade_schedule');
	if (is_string($ft_schedule))
		$ft_schedule = unserialize(trim($ft_schedule));
	$tz = new DateTimeZone(get_option('market_timezone'));
	
		
	for($i=1;$i<=7;$i++) {
	
		$counter = $i%7;
		
		// handle fiztrade checkboxes
		if (!empty($input["dm_hours_week_" . $counter . "_start_default"]))
			$input["dm_hours_week_" . $counter . "_start_default"] = 'on';
		else
			$input["dm_hours_week_" . $counter . "_start_default"] = 'off';
		if (!empty($input["dm_hours_week_" . $counter . "_end_default"]))
			$input["dm_hours_week_" . $counter . "_end_default"] = 'on';
		else
			$input["dm_hours_week_" . $counter . "_end_default"] = 'off';
		
		if ($input["dm_hours_week_" . $counter . "_start_default"] == 'on') {
			$day_start = get_first_open($ft_schedule[$metal][$counter]);
			if (!empty($day_start)) {
				$day_start->setTimezone($tz);
				$open = $day_start->format('h:i A');
			} else {
				$open = '';
			}
		} else {
			$open = $input["dm_hours_week_" . $counter . "_start"];
		}
		
		if ($input["dm_hours_week_" . $counter . "_end_default"] == 'on') {
			$day_end = get_last_close($ft_schedule[$metal][$counter]);
			if (!empty($day_end)) {
				$day_end->setTimezone($tz);
				$close = $day_end->format('h:i A');
			} else {
				$close = '';
			}
		} else {
			$close = $input["dm_hours_week_" . $counter . "_end"];
		}
		
		if ($open == '' &&
			$close == '') {
			$time = "Closed";
		} else if (!date_create($open) || !date_create($close)) {
			$time = "Error";
		} else if ($open == $close || minute_diff($open, $close)) {
			$time = "All day";
		} else {
			$time = str_replace(" ","", $open) . "&ndash;" . str_replace(" ","", $close);
		}
		/*
		if ($input["dm_hours_week_" . $counter . "_start"] != $input["dm_hours_week_" . $counter . "_end"]) {
			$time = str_replace(" ","",$input["dm_hours_week_" . $counter . "_start"]) . "&ndash;" . str_replace(" ","", $input["dm_hours_week_" . $counter . "_end"]);
				
		} else {
			$time = "Closed";
		}
		*/
		if ($time == $hours_array[$index]["time"]) {

			$hours_array[$index]["end"] = $i%7;
		
		} else {
		
			$index++;
			$hours_array[$index]["time"] = $time;
			$hours_array[$index]["start"] = $i%7;
			$hours_array[$index]["end"] = $i%7;	
		
		}
	
	}
	
	$hours_string = "";
	foreach($hours_array as $hours_row) {
		print_r($hours_row);
		$hours_string .= "<div>"; 
	
		if ($hours_row["start"]==$hours_row["end"]) {
			$hours_string .= $days[$hours_row["start"]]["full"];		
		} else {
			$hours_string .= $days[$hours_row["start"]]["abbr"] . "&ndash;" . $days[$hours_row["end"]]["abbr"];
		}
		
		$hours_string .= ": ";
		$hours_string .= $hours_row["time"];
		$hours_string .= "</div>"; 
		
	}
	$hours_string = str_replace("01:","1:", $hours_string);
	$hours_string = str_replace("02:","2:", $hours_string);
	$hours_string = str_replace("03:","3:", $hours_string);
	$hours_string = str_replace("04:","4:", $hours_string);
	$hours_string = str_replace("05:","5:", $hours_string);
	$hours_string = str_replace("06:","6:", $hours_string);
	$hours_string = str_replace("07:","7:", $hours_string);
	$hours_string = str_replace("08:","8:", $hours_string);
	$hours_string = str_replace("09:","9:", $hours_string);		
	
	$input["hours_string"] = $hours_string;	
	//wp_die(print_r($input,true));
	return $input;
}

function dm_hours_exceptions_validate($input) {
	
	// validate Exception 1
	$hours_to_vaidate = array();	
	for($i=1;$i<=7;$i++) {
		$counter = $i%7;	
		$hours_to_validate["dm_hours_week_" . $counter ."_start"] = $input["dm_hours_a_week_" . $counter . "_start"];					
		$hours_to_validate["dm_hours_week_" . $counter ."_end"]   = $input["dm_hours_a_week_" . $counter . "_end"];		
	}
	$output = dm_hours_validate($hours_to_validate);
	$input["hours_string_a"] = $output["hours_string"];  

	// validate Exception 2
	$hours_to_vaidate = array();	
	for($i=1;$i<=7;$i++) {
		$counter = $i%7;	
		$hours_to_validate["dm_hours_week_" . $counter ."_start"] = $input["dm_hours_b_week_" . $counter . "_start"];					
		$hours_to_validate["dm_hours_week_" . $counter ."_end"]   = $input["dm_hours_b_week_" . $counter . "_end"];		
	}
	$output = dm_hours_validate($hours_to_validate);
	$input["hours_string_b"] = $output["hours_string"];  
	
	// validate Exception 3
	$hours_to_vaidate = array();	
	for($i=1;$i<=7;$i++) {
		$counter = $i%7;	
		$hours_to_validate["dm_hours_week_" . $counter ."_start"] = $input["dm_hours_c_week_" . $counter . "_start"];					
		$hours_to_validate["dm_hours_week_" . $counter ."_end"]   = $input["dm_hours_c_week_" . $counter . "_end"];		
	}
	$output = dm_hours_validate($hours_to_validate);
	$input["hours_string_c"] = $output["hours_string"];

	return $input;
	
}	

function formatOffset($offset) {
	$hours = $offset / 3600;
	$remainder = $offset % 3600;
	$sign = $hours > 0 ? '+' : '-';
	$hour = (int) abs($hours);
	$minutes = (int) abs($remainder / 60);

	if ($hour == 0 AND $minutes == 0) {
		$sign = ' ';
	}
	return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');

}

// returns DateTime of opening of first session in day
function get_first_open($day_schedule) {	
	if (count($day_schedule) == 0)
		return null;

	$first_open = new DateTime('23:59:59');
	foreach ($day_schedule as $session) {
		if ($session['start'] < $first_open)
			$first_open = $session['start'];
	}
	
	return $first_open;
}

// returns DateTime of closing of last session in day
function get_last_close($day_schedule) {
	if (count($day_schedule) == 0)
		return null;

	$last_close = new DateTime('00:00:00');
	foreach ($day_schedule as $session) {
		if ($session['end'] > $last_close)
			$last_close = $session['end'];
	}
	
	return $last_close;
}

// if no fiztrade box has been selected this returns $schedule
// otherwise returns $schedule modified by the appropriate fiztrade hours
function check_fiztrade_schedule($schedule, $metal) {
	$ft_schedule = get_site_option('fiztrade_schedule');
	if (is_string($ft_schedule))
		$ft_schedule = unserialize(trim($ft_schedule));
	$tz = new DateTimeZone(get_option('market_timezone'));
	
	for ($i=1; $i<=7; $i++) {
		if ($schedule['dm_hours_week_'. $i%7 .'_start_default'] == 'on') {
			$day_start = get_first_open($ft_schedule[$metal][$i%7]);
			if (!empty($day_start)) {
				$day_start->setTimezone($tz);
				
				$schedule['dm_hours_week_'. $i%7 .'_start'] = $day_start->format('h:i A');
			} else {
				$schedule['dm_hours_week_'. $i%7 .'_start'] = '';
			}
		}
		if ($schedule['dm_hours_week_'. $i%7 .'_end_default'] == 'on') {
			$day_end = get_last_close($ft_schedule[$metal][$i%7]);
			if (!empty($day_end)) {
				$day_end->setTimezone($tz);
				
				$schedule['dm_hours_week_'. $i%7 .'_end'] = $day_end->format('h:i A');
			} else {
				$schedule['dm_hours_week_'. $i%7 .'_end'] = '';
			}
		}
	}
	
	return $schedule;
}

function minute_diff($start, $end) {
	$sTime = new DateTime($start);
	$eTime = new DateTime($end);
	
	if ($eTime == $sTime)
		return true;
		
	$tmp = $eTime->add(new DateInterval('PT1M'));
	
	if ($tmp == $sTime)
		return true;
		
	$tmp = $tmp->sub(new DateInterval('P1D'));
	
	if ($tmp == $sTime)
		return true;
		
	return false;
}

?>