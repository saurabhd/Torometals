<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.3
 * @package           Custom widget area
 *
 * @wordpress-plugin
 * Plugin Name:       WP Custom Widget area
 * Plugin URI:        http://kishorkhambu.com.np/plugins/
 * Description:       A wordpress plugin to create custom dynamic widget area.
 * Version:           1.0.3
 * Author:            Kishor Khambu
 * Author URI:        http://kishorkhambu.com.np
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-custom-widget-area
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
$purl = plugin_dir_url( __FILE__ );
require_once plugin_dir_path( __FILE__ ) . 'includes/config.php';
/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-custom-widget-area-activator.php';

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-custom-widget-area-deactivator.php';

/** This action is documented in includes/class-wp-custom-widget-area-activator.php */
register_activation_hook( __FILE__, array( 'Custom_Widget_Area_Activator', 'activate' ) );

/** This action is documented in includes/class-wp-custom-widget-area-deactivator.php */
register_deactivation_hook( __FILE__, array( 'Custom_Widget_Area_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-custom-widget-area.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.3
 */
function run_plugin_name() {
	$plugin = new Custom_Widget_Area();
	$plugin->run();

}
run_plugin_name();

function cb(){
	echo "welcome to first metabox showcase";
}