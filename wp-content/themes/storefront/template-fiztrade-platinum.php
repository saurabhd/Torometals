<?php
/**
 * The template for displaying the homepage.
 *
 * This page template will display any functions hooked into the `homepage` action.
 * By default this includes a variety of product displays and the page content itself. To change the order or toggle these components
 * use the Homepage Control plugin.
 * https://wordpress.org/plugins/homepage-control/
 *
 * Template name: Platinum
 *
 * @package storefront
 */
get_header(); ?>
	<div id="primary" class="content-area iframe-content">
		<main id="main" class="site-main" role="main">		
			<div class="col-sm-12 spotTicker">
				<iframe id="spotTicker" name="spotTicker" height="50" width="100%" frameborder="0" scrolling="no"></iframe>
			</div>
			<div class="row" id="FizCommerceContainer">
				<div class="col-sm-12">
					<div>
						<iframe name="CatalogWidgetContainer" id="CatalogWidgetContainer" width="100%" frameborder="0"></iframe>
					</div>
				</div>
			</div>
			<script>			
				setTimeout(function(){
				     eCommerceWidgetAction('LoadCatalog', 'PLATINUM');
        				
        				// Initialization of Chart Widget.....
					FizChartInit('1148-fb8aecdea7e0101860bd76fa64f524a0', 'STAGE');
				     var options = {};
        				FizChartLoad('SpotPriceTicker', options, 'spotTicker');
				},1000);
			</script>		    
		</main><!-- #main -->
	</div><!-- #primary -->
<?php get_footer(); ?>
