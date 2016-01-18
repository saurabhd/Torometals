<?php
// defines the CTA widget used in sidebars in our Digital Metals themes
class DM_Graph_Control_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'dm-graph-control', // Base ID
			'Digital Metals Price History Graph Control', // Name
			array( 'description' => __( 'Creates a control panel for a Price History graph on the same page.', 'twentytwelve' ), ) // Args
		);
	}
	
	// sets the display of the widget on the page
	public function widget($args, $instance) {

		echo $args['before_widget'];
		?>
		<div class="graph-control">
			<?php if (!empty($instance['title'])) : ?>
				<h2><?php echo $instance['title']; ?></h2>
			<?php endif; ?>
			<div class="btn-group" id="chart-select">
				<a id="chart-intraday" class="dmpilli active">Intraday</a>
				<a id="chart-close" class="dmpilli">Historical</a>
				<a id="chart-ratio" class="dmpilli">Ratio</a>
			 </div>
			<div class="btn-group" id="metal-select">
				<a id="chart-gold" class="dmpilli active ">Gold</a>
				<span id="gold-to" style="display:none">Gold to:</span>
				<a id="chart-silver" class="dmpilli">Silver</a>
				<a id="chart-platinum" class="dmpilli">Platinum</a>
				<a id="chart-palladium" class="dmpilli">Palladium</a>
			 </div>
			<div class="btn-group" id="zoom">
				<a id="chart-hour" class="dmpilli">1 h</a>
				<a id="chart-day" class="dmpilli active">1 d</a>
				<a id="chart-month" class="dmpilli" style="display:none">1 m</a>
				<a id="chart-3month" class="dmpilli" style="display:none">3 m</a>
				<a id="chart-6month" class="dmpilli" style="display:none">6 m</a>
				<a id="chart-ytd" class="dmpilli" style="display:none">YTD</a>
				<a id="chart-year" class="dmpilli" style="display:none">1 y</a>
				<!--<a id="chart-all" class="dmpilli">All</a>-->
			 </div>
		</div>
		<?php
		echo $args['after_widget'];
	}
	
	// the form for the widget in the Appearance menu
	public function form($instance) {
		$title = $instance['title'] != '' ? $instance['title'] : 'Filter';
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
	register_widget('DM_Graph_Control_Widget');
});
?>