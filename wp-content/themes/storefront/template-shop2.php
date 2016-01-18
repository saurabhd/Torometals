<?php
/**
 * The template for displaying the homepage.
 *
 * This page template will display any functions hooked into the `homepage` action.
 * By default this includes a variety of product displays and the page content itself. To change the order or toggle these components
 * use the Homepage Control plugin.
 * https://wordpress.org/plugins/homepage-control/
 *
 * Template name: Shop2
 *
 * @package storefront
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main"> 
			    
			<div style="position:absolute;top:0px;right:20%;width:200px;z-index:9999">
				<iframe name="CustomerWidgetContainer" id="CustomerWidgetContainer"  width="100%" frameborder="0"></iframe>
			</div>
			<div style="position:absolute;top:0px;right:4%;width:200px;z-index:9999">
				<iframe name="CartWidgetContainer" id="CartWidgetContainer" width="100%" frameborder="0"></iframe>
			</div> 
			<div class="row" id="FizCommerceContainer">
				<div class="col-sm-12">
					<div>
						<iframe name="CatalogWidgetContainer" id="CatalogWidgetContainer" width="100%" frameborder="0"></iframe>
					</div>
				</div>
			</div>
			<link href="https://stage-connect.fiztrade.com/Scripts/FizCommerce/FizCommerce.css" rel="stylesheet">
			<link href="http://retail.digitalmetals.us/css/nav-menu.css" rel="stylesheet">
			<script type="text/javascript" src="http://retail.digitalmetals.us/Scripts/NavMenu.js"></script>
			 
			<script>
				function cartClickHandler() {
					$('.catalogPageContent').hide();
					$('.FizCommerceContainer').show();
					eCommerceWidgetAction('LoadCart', '');
				}

				function customerClickHandler() {
					$('.catalogPageContent').hide();
					$('.FizCommerceContainer').show();
					eCommerceWidgetAction('LoadCustomer', '');
				}

				//$(document).ready(function () {
				setTimeout(function(){
					eCommerceInit('1-aaa16cf97f74528a42cd222830434bbd', 'STAGE');
					eCommerceWidgetAction('LoadFamily', 'ROLEX');
					eCommerceLoadWidget('cart');
					eCommerceLoadWidget('customer');
					eCommerceWidgetEvent('cart', 'click', cartClickHandler);
					eCommerceWidgetEvent('customer', 'click', customerClickHandler);


					//$('.mainNavLink').removeClass('active');
					//$('#shopMenu').addClass('active');

				},1000);
			</script>
			<script type="text/javascript" src="https://stage-connect.fiztrade.com/Scripts/FizCommerce/eCommerce-1.0.6.js"></script>

			<div id="chartContainer">
				<iframe name="my-iframe" frameborder="0" width="500" height="350" scrolling="no"></iframe>
				<iframe name="my-iframe2" frameborder="0" width="500" height="350" scrolling="no"></iframe>
				<iframe name="my-iframe4" frameborder="0" width="850" scrolling="no"></iframe>
				<iframe name="my-iframe5" frameborder="0" width="100%" scrolling="no"></iframe>
			</div>
		    
		</main><!-- #main -->
	</div><!-- #primary -->
<?php get_footer(); ?>
