<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> <?php storefront_html_tag_schema(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script> 
<script type="text/javascript" src="https://stage-connect.fiztrade.com/Scripts/FizTradeWidgets-stage.js"></script> 

<?php wp_head(); ?>

<script src="<?php bloginfo('template_directory'); ?>/js/custom.js"></script>
<link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/css/torometals.css" type="text/css" media="screen" />
</head>
<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<?php
	do_action( 'storefront_before_header' ); ?>

	<header id="masthead" class="site-header" role="banner" <?php if ( get_header_image() != '' ) { echo 'style="background-image: url(' . esc_url( get_header_image() ) . ');"'; } ?>>
		
		
		<div class="upper-top">
			<?php dynamic_sidebar( 'Upper-Top' ); ?>
			<div class="align-R">
				<div class="welcome-msg">Welcome Msg!</div>
				<div class="menu-items"><?php
					wp_nav_menu(
						array(
							'menu'			=> 'upper_top',
							'theme_location'	=> 'primary',
							'container_class'	=> 'primary-navigation',
							)
					); 

					wp_nav_menu(
						array(
							'menu' 			=> 'upper_top',
							'theme_location'	=> 'handheld',
							'container_class'	=> 'handheld-navigation',
							)
					);
				?></div>
			</div>			
		</div>
		<div class="col-full">

			<?php
			/**
			 * @hooked storefront_skip_links - 0
			 * @hooked storefront_social_icons - 10
			 * @hooked storefront_site_branding - 20
			 * @hooked storefront_secondary_navigation - 30
			 * @hooked storefront_product_search - 40
			 * @hooked storefront_primary_navigation - 50
			 * @hooked storefront_header_cart - 60
			 */
			do_action( 'storefront_header' ); ?>

		</div>
	</header><!-- #masthead -->

	<?php
	/**
	 * @hooked storefront_header_widget_region - 10
	 */
	do_action( 'storefront_before_content' ); ?>

	<div id="content" class="site-content" tabindex="-1">
		<div class="col-full">

		<?php
		/**
		 * @hooked woocommerce_breadcrumb - 10
		 */
		do_action( 'storefront_content_top' ); ?>
