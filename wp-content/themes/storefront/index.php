<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package storefront
 */ 
get_header(); ?>
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">
		
		<?php if (false) : ?>
			<?php if ( have_posts() ) : ?>
				<?php get_template_part( 'loop' ); ?>
			<?php else : ?>
				<?php get_template_part( 'content', 'none' ); ?>
			<?php endif; ?>		
		<?php endif; ?>
		
		
		<!-- #Slider Start --><?php 
                echo do_shortcode('[crellyslider alias="home_page_slider"]');
		?><!-- #Slider End -->
		
		
		<!-- #LIVE Spot Pricing Start -->
    		<div style="/*position:absolute;*/ top:350px;left:5px;" class="Precious-Metal">
    		<div class="Spot_Chart">
			<span class="Spot_Price">Precious Metal Spot Prices</span>
			<div class="Timestamp">Last Update : <span id="spotTimestamp"></span></div>
		</div>
    		<br />
    		<div class="dgspotcontainer">
                    <ul>
                        <li id="goldSpot" class="dgspotbox"></li>
                        <li id="silverSpot" class="dgspotbox"></li>
                        <li id="platinumSpot" class="dgspotbox"></li>
                        <li id="palladiumSpot" class="dgspotbox"></li>
                    </ul>
    		</div>
    		<div id="spotDataZone" style="display:none"></div>
    		</div>
    		<script>    
		    var spotChartCustomParms = {
			   'container': 'spotDataZone',
			   'goldContainer': 'goldSpot',
			   'silverContainer': 'silverSpot',
			   'platinumContainer': 'platinumSpot',
			   'palladiumContainer': 'palladiumSpot',
			   'timestampContainer': 'spotTimestamp',
			   'changePositiveColor': '#4cff00',
			   'changeNegativeColor': '#ff0000',
			   'changePositiveIcon': 'https://stage-connect.fiztrade.com/Content/price-arrow-up.gif',
			   'changeNegativeIcon': 'https://stage-connect.fiztrade.com/Content/price-arrow-down.gif',
		    }; 
		    LoadFizTradeChart('SpotPriceWithParms', '1148-fb8aecdea7e0101860bd76fa64f524a0', JSON.stringify(spotChartCustomParms)); 
		</script>
		<!-- #LIVE Spot Pricing End -->
		
          <!-- #Tabs Start -->
		<div id="tabs">
		  <ul>
		    <li><a href="#tabs-1"><?php echo __('Product Categories', 'woothemes'); ?></a></li>
		    <li><a href="#tabs-2"><?php echo __('Hot Selling Items', 'woothemes'); ?></a></li>
		    <li><a href="#tabs-3"><?php echo __('On sale Products', 'woothemes'); ?></a></li>
		    <li><a href="#tabs-4"><?php echo __('Featured Products', 'woothemes'); ?></a></li>
		    <li><a href="#tabs-5"><?php echo __('Top Rated Products', 'woothemes'); ?></a></li>
		    <li><a href="#tabs-6"><?php echo __('Recent Products', 'woothemes'); ?></a></li>
		  </ul>
		  <div id="tabs-1"><?php 
		   	echo '<h2 class="section-title">'.__('Product Categories', 'woothemes').'</h2>'; 
   		 	echo do_shortcode("[product_categories category=\"Popular Copper Products\" per_page=\"4\" columns=\"4\" ids=\"9,10,6,11\" orderby=\"date\" 
   		 	order=\"desc\"]");
	   	   ?></div>
		  <div id="tabs-2"><?php 
		   	echo '<h2 class="section-title">'.__('Hot Selling Items', 'woothemes').'</h2>';
			echo do_shortcode("[best_selling_products per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		   ?></div>
		  <div id="tabs-3"><?php 
		   	echo '<h2 class="section-title">'.__('On sale Products', 'woothemes').'</h2>';
			echo do_shortcode("[sale_products per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		   ?></div> 
		  <div id="tabs-4"><?php 
		   	echo '<h2 class="section-title">'.__('Featured Products', 'woothemes').'</h2>';
			echo do_shortcode("[featured_products per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		   ?></div>
		  <div id="tabs-5"><?php 
		   	echo '<h2 class="section-title">'.__('Top rated Products', 'woothemes').'</h2>';
			echo do_shortcode("[top_rated_products per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		   ?></div>
		  <div id="tabs-6"><?php 
		   	echo '<h2 class="section-title">'.__('Recent Products', 'woothemes').'</h2>';
			echo do_shortcode("[recent_products per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		   ?></div>
		</div>
          <!-- #Tabs End -->
          
          <!-- #Popular Silver Products Start -->
		<div id="popular-silver-products" class="popular-products"><?php 
		   	echo '<h2 class="section-title">'.__('Popular Silver Products', 'woothemes').'</h2>';
			echo do_shortcode("[product_category category=\"silver\" per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		?></div>
          <!-- #Popular Silver Products End -->
          
          <!-- #Popular Gold Products Start -->
		<div id="popular-gold-products" class="popular-products"><?php 
		   	echo '<h2 class="section-title">'.__('Popular Gold Products', 'woothemes').'</h2>';
			echo do_shortcode("[product_category category=\"gold\" per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		?></div>
          <!-- #Popular Gold Products End -->
          
          <!-- #Popular Platinum Products Start -->
		<div id="popular-platinum-products" class="popular-products"><?php 
		   	echo '<h2 class="section-title">'.__('Popular Platinum Products', 'woothemes').'</h2>';
			echo do_shortcode("[product_category category=\"platinum\" per_page=\"4\" columns=\"4\" orderby=\"date\" order=\"desc\"]");
		?></div>
          <!-- #Popular Platinum Products End -->

		</main>
		<!-- #main -->

	</div>
	<!-- #primary -->

<?php #do_action( 'storefront_sidebar' ); ?>
<?php get_footer(); ?>

