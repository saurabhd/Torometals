<?php

if (class_exists('Woocommerce')) {

	// defines the Filter widget used in sidebars in our Digital Metals themes
	class DM_Filter_Widget extends WP_Widget {
		function __construct() {
			parent::__construct(
				'dm-filter', // Base ID
				'Digital Metals Product Filter', // Name
				array( 'description' => __( 'Creates a product filter that can select FizTrade products based on several criteria.', 'twentytwelve' ), ) // Args
			);
		}
		
		// sets the display of the widget on the page
		public function widget($args, $instance) {
			
			echo $args['before_widget'];
			if ($instance['title']) {
				echo '<div id="filter-header">'. $instance['title'] .'</div>';
			}
			
			echo '<div id="product-filter"><ul>';
			$a = array(
				'taxonomy' => 'product_cat',
				'orderby' => 'id',
				'parent' => 0
			);
			
			// iterates through top level categories
			foreach(get_categories($a) as $cat) {
				if (!$this->contains_visible_products($cat))
					continue;
			
				?>
				<li id="<?php echo 'filter-'. $cat->slug; ?>">
					<a href="<?php echo get_bloginfo('home') .'/product-category/'. $cat->slug; ?>">
						<?php echo $cat->name; ?>
					</a>
					
					<?php 
					$a = array(
						'taxonomy' => 'product_cat',
						'orderby' => 'id',
						'parent' => $cat->cat_ID
					);
					// show first level sub-categories
					if (count(get_categories($a)) > 0) {
						echo '<ul class="filter">';
						foreach(get_categories($a) as $subcat) {
							?>
							<li id="<?php echo 'filter-'. $subcat->slug; ?>">
								<a href="<?php echo get_bloginfo('home') .'/product-category/'. $cat->slug .'/'. $subcat->slug; ?>">
									<?php echo $subcat->name; ?>
								</a>
							<?php
						
							$parts = explode('/', $_SERVER['REQUEST_URI']);
							//echo $_SERVER['REQUEST_URI'] . '->' . $parts[count($parts) - 2];
							if ($parts[count($parts) - 2] == $subcat->slug) {
								// filter below category name if we're in that category
								parse_str($_SERVER['QUERY_STRING'], $queryParts);  // parse query string into assoc. array
								$cat_tax = array(
									'taxonomy' => 'product_cat',
									'field' => 'slug',
									'terms' => array($subcat->slug)
								);
								
								echo $this->generate_filter($queryParts, array($cat_tax));
							}
						}
						echo '</ul>';
					}
			}
			echo '</ul></div>';
			
			?>
			<div id="product-filter-bottom">
				<!-- background image set in theme css -->
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
				<!-- TODO: maybe add options to set what can be filtered on? -->
			</p>
			<?php
		}
		
		// Sanitize/validate form values
		public function update($new_instance, $old_instance) {
			return $new_instance; // not doing anything
		}
		
		private function generate_filter($queryParts, $filter=array()) {
		
			$tax_name = $this->decide_tax($filter);
			$terms = get_terms($tax_name);
		
			$args = array(
				'post_type'	=> 'product',
				'post_status' => 'publish',
				'ignore_sticky_posts'	=> 1,
				'meta_query' => array(
					array( 
						'key' => '_visibility',
						'value' => array('catalog', 'visible'),
						'compare' => 'IN'
					)
				)				
			);


			// get data about each term in this taxonomy from DB
			$productsByTerm = array();
			$sum = 0;
			$html = '';
			foreach ($terms as $term) {
				$id = $term->term_id;
				
				$t_query = array(
					'taxonomy' => $tax_name,
					'field' => 'id',
					'terms' => array($id)
				);
				
				// returned items must conform to previously set criteria in addition to our current iterating set			
				$args['tax_query'] = array_merge(
					array('relation' => 'AND'),
					$filter,
					array($t_query)
				);
				// print_r($args['tax_query']);
				// echo'<br/><br/>';
				$products = new WP_Query( $args );
				$sumCounts += $products->found_posts;
				
				if ($products->post_count > 0) { // skip terms with no products
					
					// add to filter array
					$temp = array_merge($filter, array($this->next_tax_filter($tax_name, $id)));
					
					$html .= sprintf('<li><a href="?%s">%s (%d)</a>',
						$this->get_qstring($temp),
						apply_filters('dm_inv_filter_product_taxonomy', $term->name), // shorten certain words - check the functions.php for the theme
						$products->post_count
					);
					
					// see if we need another level of filter
					if (!empty($queryParts)) {					
						$key = key($queryParts);
						$val = $queryParts[$key];
						if ($val == $id) {
							// remove option from query string array
							array_shift($queryParts);
							
							$new_filter = array_merge($filter, array($this->next_tax_filter($key, $val)));
							// keep going
							$html .= $this->generate_filter($queryParts, $new_filter);
						}
					}
					$html .= '</li>';
				}
			}
			
			if ($sumCounts > 0) {
				return '<ul class="filter">'. $html .'</ul>';
			} else {
				return '';
			}
		}
		
		// figure out which taxonomy we're displaying
		private function decide_tax($filter) {
			$possible_tax = array('pa_origin', 'pa_strike', 'pa_mint');
			
			// remove possibles we've already filtered on
			foreach ($filter as $term) {
				$index = array_search($term['taxonomy'], $possible_tax);
				if ($index !== false) {
					unset($possible_tax[$index]);
				}
			}
			
			// pick the first remaining one
			return array_shift($possible_tax);
		}
		
		// see what selected filter we need to add
		private function next_tax_filter($qKey, $qVal) {
			return array(
				'taxonomy' => str_replace('filter_', 'pa_', $qKey),
				'field' => 'id',
				'terms' => array($qVal)
			);
		}
		
		// return a query string that represents the attribute filter
		private function get_qstring($filter) {
			//print_r($filter);
			$queryParts = array();
			foreach ($filter as $t) {
				if ($t['taxonomy'] == 'product_cat') 
					continue;			// only care about attributes
					
				$filter_terms = $t['terms'];
				//echo 'test:'. $filter_terms[0];
				//echo $t['taxonomy'] .' ';
				$terms = get_terms($t['taxonomy'], array('include' => array($filter_terms[0])));
				//print_r($terms); echo '<br/><br/>';
				if (is_wp_error($terms)) echo $terms->get_error_message();
				$queryParts[] = str_replace('pa_', 'filter_', $t['taxonomy']) .'='. $terms[0]->term_id; 
			}
			
			return implode ('&', $queryParts);
		}
		
		private function contains_visible_products ($cat) {
			$cat_tax = array(
				'taxonomy' => 'product_cat',
				'field' => 'slug',
				'terms' => array($cat->slug)
			);
			$args = array(
				'tax_query' => array( $cat_tax ),
				'post_type' => 'product',
				'meta_query' => array(
					array( 
						'key' => '_visibility',
						'value' => array('catalog', 'visible'),
						'compare' => 'IN'
					)
				)
			);
			$query = new WP_Query($args);
					
			if ($query->post_count > 0)
				return true;
			else
				return false;
		}

	}
	add_action('widgets_init', function () {
		register_widget('DM_Filter_Widget');
	});

	// enables filtering by query string
	function dm_central_product_filter_init( ) {

		if ( ! is_admin() ) {

			global $_chosen_attributes, $woocommerce, $_attributes_array;

			$_chosen_attributes = $_attributes_array = array();

			$attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
			if ( $attribute_taxonomies ) {
				foreach ( $attribute_taxonomies as $tax ) {

					$attribute = sanitize_title( $tax->attribute_name );
					$taxonomy = $woocommerce->attribute_taxonomy_name( $attribute );

					// create an array of product attribute taxonomies
					$_attributes_array[] = $taxonomy;

					$name = 'filter_' . $attribute;
					$query_type_name = 'query_type_' . $attribute;

					if ( ! empty( $_GET[ $name ] ) && taxonomy_exists( $taxonomy ) ) {

						$_chosen_attributes[ $taxonomy ]['terms'] = explode( ',', $_GET[ $name ] );

						if ( ! empty( $_GET[ $query_type_name ] ) && $_GET[ $query_type_name ] == 'or' )
							$_chosen_attributes[ $taxonomy ]['query_type'] = 'or';
						else
							$_chosen_attributes[ $taxonomy ]['query_type'] = 'and';

					}
				}
			}

			add_filter('loop_shop_post_in', 'woocommerce_layered_nav_query');
		}
	}

	add_action( 'init', 'dm_central_product_filter_init', 1 );
}
?>