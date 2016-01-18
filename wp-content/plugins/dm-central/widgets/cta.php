<?php
// defines the CTA widget used in sidebars in our Digital Metals themes
class DM_CTA_Widget extends WP_Widget {
	function __construct() {
		parent::__construct(
			'dm-cta', // Base ID
			'Digital Metals CTA', // Name
			array( 'description' => __( 'Creates an image with a link to a product category or URL.', 'twentytwelve' ), ) // Args
		);
	}
	
	// sets the display of the widget on the page
	public function widget($args, $instance) {
		if ($instance['link-to'] == 'category') {
			$cat = get_term($instance['category'], 'product_cat');  // get product category object
			$target = get_bloginfo('url') . '/product-category/' . $cat->slug;
		} else {
			// link to whatever's in the the URL box - may be empty string
			$target = $instance['url'];
		}
		
		echo $args['before_widget'];
		if ($instance['button'] == 'none') {
			?>
			<div class="sidebar-cta">
				<a href="<?php echo $target; ?>">
					<img src="<?php echo $instance['image']; ?>"/>
					<?php if ($instance['label'] != '') : ?>
						<div class="cta-label">
							<?php echo $instance['label']; ?>
						</div>
					<?php endif; ?>						
				</a>
			</div>
			<?php
		} else {
			?>
			<div class="sidebar-cta">
				<img src="<?php echo $instance['image']; ?>"/>
				<div class="<?php echo $instance['button']; ?>-button">
					<a href="<?php echo $target; ?>"><?php echo $instance['button']; ?></a>
				</div>
				<?php if ($instance['label'] != '') : ?>
					<div class="cta-label">
						<?php echo $instance['label']; ?>
					</div>
				<?php endif; ?>	
			</div>
			<?php
		}
		echo $args['after_widget'];
	}
	
	// the form for the widget in the Appearance menu
	public function form($instance) {
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'image' ); ?>"><?php _e( 'Image:' ); ?></label> 
			<div data-preview_size="thumbnail" class="clearfix active">
				<input type="hidden" class="image_url" value="<?php echo esc_attr($instance['image']); ?>" name="<?php echo $this->get_field_name( 'image' ); ?>">
				<div class="has-image" style="<?php echo $instance['image'] == '' ? 'display:none' : '' ?>">
					<img alt="" src="<?php echo esc_attr($instance['image']); ?>" style="max-width:225px">
					<a href="#" class="remove-image">Remove</a>
				</div>
				<div class="no-image" style="<?php echo $instance['image'] != '' ? 'display:none' : '' ?>">
					No image selected <input type="button" value="Add Image" class="button add-image">
				</div>
			</div>
			<br/>
			<label for="<?php echo $this->get_field_name('link-to'); ?>"><?php _e( 'Link to:' ); ?></label>
			<span class="link-to">
				<input type="radio" <?php checked($instance['link-to'], 'category'); ?> name="<?php echo $this->get_field_name('link-to'); ?>" value="category">Category
				<input type="radio" <?php checked($instance['link-to'], 'url'); ?> name="<?php echo $this->get_field_name('link-to'); ?>" value="url">URL
			</span>
			<br/><br/>
			<div class="sel-cat">
				<label for="<?php echo $this->get_field_name('category'); ?>"><?php _e( 'Category:' ); ?></label>
				<?php wp_dropdown_categories(array(
					'name' => $this->get_field_name('category'),
					'taxonomy' => 'product_cat',
					'selected' => esc_attr($instance['category'])
				));
				?>
			</div>
			
			<div class="sel-url">
				<label for="<?php echo $this->get_field_name('url'); ?>"><?php _e( 'URL:' ); ?></label>
				<input class="widefat" name="<?php echo $this->get_field_name( 'url' ); ?>" type="text" value="<?php echo esc_attr($instance['url']); ?>" />
			</div>
			<br/><br/>
			
			<label for="<?php echo $this->get_field_name('label'); ?>"><?php _e( 'Label:' ); ?></label>
			<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>leave empty to remove label area</em>
			<input class="widefat" name="<?php echo $this->get_field_name( 'label' ); ?>" type="text" value="<?php echo esc_attr($instance['label']); ?>" />
			<br/><br/>
			
			<?php $button = !empty($instance['button']) ? $instance['button'] : 'none'; ?>
			<label for="<?php echo $this->get_field_name('button'); ?>"><?php _e( 'Button:' ); ?></label>
			<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>Choose a button, or None to make the whole CTA clickable</em><br/>
			<span class="sel-button">
				<input type="radio" <?php checked($button, 'shop'); ?> name="<?php echo $this->get_field_name('button'); ?>" value="shop">Shop
				<input type="radio" <?php checked($button, 'buy'); ?> name="<?php echo $this->get_field_name('button'); ?>" value="buy">Buy
				<input type="radio" <?php checked($button, 'sell'); ?> name="<?php echo $this->get_field_name('button'); ?>" value="sell">Sell
				<input type="radio" <?php checked($button, 'learn'); ?> name="<?php echo $this->get_field_name('button'); ?>" value="learn">Learn
				<input type="radio" <?php checked($button, 'none'); ?> name="<?php echo $this->get_field_name('button'); ?>" value="none">None
			</span>
		</p>
		<?php
	}
	
	// Sanitize/validate form values
	public function update($new_instance, $old_instance) {
		return $new_instance; // not doing anything
	}

}
add_action('widgets_init', function () {
	register_widget('DM_CTA_Widget');
});

// add the script to get the media uploader working for the widget above
function dm_cta_enqueue_scripts() {
	global $pagenow;
	
	if (in_array($pagenow, array('widgets.php'))) {
		wp_register_script( 'dm-widgets', plugins_url('widgets.js', __FILE__), array('jquery','media-upload','thickbox') );
		wp_enqueue_script('jquery');

		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');

		wp_enqueue_script('media-upload');
		wp_enqueue_script('dm-widgets');

	}

}
add_action('admin_enqueue_scripts', 'dm_cta_enqueue_scripts');
?>