<?php
// defines the spots widget
class DM_Spot_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'dm-spots', // Base ID
			'Digital Metals Spot Prices', // Name
			array( 'description' => __( 'Tracks Dillon Gage spot prices.  Updates once a second.', 'twentytwelve' ), ) // Args
		);
	}
	
	// sets the display of the widget on the page
	public function widget($args, $instance) {	
		echo $args['before_widget'];
		
		if (!empty($instance['title'])) 
			echo '<h2>'. $instance['title'] .'</h2>';
		echo do_shortcode('[spot_prices]');
		
		echo $args['after_widget'];
	}
	
	// the form for the widget in the Appearance menu
	public function form($instance) {
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<?php	
	}
	
	// Sanitize/validate form values
	public function update($new_instance, $old_instance) {
		return $new_instance; // not doing anything
	}

}
add_action('widgets_init', function () {
	register_widget('DM_Spot_Widget');
});
?>