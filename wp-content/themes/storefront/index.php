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
	<div id="primary" class="home-content-area">
		<main id="main" class="site-main" role="main">

			<?php if (false) : ?>
				<?php if ( have_posts() ) : ?>
					<?php get_template_part( 'loop' ); ?>
				<?php else : ?>
					<?php get_template_part( 'content', 'none' ); ?>
				<?php endif; ?>		
			<?php endif; ?>

			<div class="middle-bar" id="middlebar">
				<!-- #Slider Start -->
				<div class="slider"><?php 
					echo do_shortcode('[crellyslider alias="home_page_slider"]');
				?></div>
				<!-- #Slider End -->

				<!-- #Spot Pricing / Gold Graph Start -->
				<div class="main-spot-price">
					<div class="spot-price">
						<iframe id="spotChart" name="spotChart" height="210" width="420" frameborder="0" scrolling="no"></iframe>
					</div>
					<div class="gold-graph">
						<iframe id="spotIntraday" name="spotIntraday" height="210" width="420" frameborder="0" scrolling="no"></iframe>
					</div>
				</div>
				<!-- #Spot Pricing / Gold Graph End -->
			</div>

			<!-- #LIVE Spot Pricing Start -->
					<!-- <div style="/*position:absolute;*/ top:350px;left:5px;" class="Precious-Metal">
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
	    		    // For Display Spot Price
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
			    //LoadFizTradeChart('SpotPriceWithParms', '1148-fb8aecdea7e0101860bd76fa64f524a0', JSON.stringify(spotChartCustomParms));
			</script> -->
			<!-- #LIVE Spot Pricing End -->

			<a href="javascript" id="viewProducts">&nbsp;</a>

			<!-- #Product Detail View on Home Page Start -->
			<div class="row" id="FizCommerceContainer" style="display:none;">
				<div class="col-sm-12">
					<div>
						<iframe name="CatalogWidgetContainer" id="CatalogWidgetContainer" width="100%" frameborder="0"></iframe>
					</div>
				</div>
			</div>
			<!-- #Product Detail View on Home Page End -->

			<!-- #Featured Products Widget Start -->
			<div id="home-featuredProducts">
				<div  class="popular-products">
					<?php echo '<h2 class="section-title">'.__('Featured Products', 'woothemes').'</h2>'; ?>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<div>
							<iframe name="FeaturedProductsWidgetContainer" id="FeaturedProductsWidgetContainer" height="1" width="100%" frameborder="0" scrolling="no"></iframe>
						</div>
					</div>
				</div>
			</div>
			<!-- #Featured Products Widget End -->

			<!-- #Spot Combo Chart Start -->
			<div id="home-spotcombochart">
				<div  class="popular-products">
					<?php echo '<h2 class="section-title">'.__('Spot Combo Chart', 'woothemes').'</h2>'; ?>
				</div>
				<div class="row">
					<div class="col-sm-4">
						<iframe id="spotCombo" name="spotCombo" height="580" width="100%" frameborder="0" scrolling="no"></iframe>
					</div>
				</div> 
			</div>   
			<!-- #Spot Combo Chart End  -->

			<script language="Javascript">
				function featuredProductClickHandler(event, item) {
					  if (event == 'addToCart') {
						 $('#popular-products').show();
						 $("#home-featuredProducts").hide();
						 $('#FizCommerceContainer').show();
						 eCommerceWidgetAction('LoadCart', '');
					  }
					  else
						 if (event = 'view') {
							$('#popular-products').show();
							$("#home-featuredProducts").hide();
						 	$('#FizCommerceContainer').show();
							eCommerceWidgetAction('LoadDetails', item);
						 }

					$('.mainNavLink').removeClass('active');
					$('.homePageContent').hide();
					$('.cartContent').show();
					if($('#middlebar')) {
						$('#middlebar').hide();
					}					  
					if($('#home-spotcombochart')) {
						$('#home-spotcombochart').hide();
					}
				}

				setTimeout(function(){
					eCommerceLoadWidget('featuredProducts');
					eCommerceWidgetEvent('featuredProducts', '*', featuredProductClickHandler);

					$('#onsaleBtn').click(function () {
					   $('#popular-products').show();
					   $("#home-featuredProducts").hide();
					   $("#crellyslider-1").hide();
					   $('#FizCommerceContainer').show();
					   eCommerceWidgetAction('LoadCategory', 'ON');
					   //$("html, body").animate({ scrollTop: $(window).height()}, 150);
					   return true;
					});
					$('#goldBtn').click(function (event) {
					   $('#popular-products').show();
					   $("#home-featuredProducts").hide();
					   $("#crellyslider-1").hide();
					   $('#FizCommerceContainer').show();
					   eCommerceWidgetAction('LoadCatalog', 'GOLD');
					   //$("html, body").animate({ scrollTop: $(window).height()}, 150);
					   return true;
					});
					$('#silverBtn').click(function () {
					   $('#popular-products').show();
					   $("#home-featuredProducts").hide();
					   $("#crellyslider-1").hide();
					   $('#FizCommerceContainer').show();
					   eCommerceWidgetAction('LoadCatalog', 'SILVER');
					   //$("html, body").animate({ scrollTop: $(window).height()}, 150);
					   return true;
				    });
				    $('#platinumBtn').click(function () {
					   $('#popular-products').show();
					   $("#home-featuredProducts").hide();
					   $("#crellyslider-1").hide();
					   $('#FizCommerceContainer').show();
					   eCommerceWidgetAction('LoadCatalog', 'PLATINUM');
					   //$("html, body").animate({ scrollTop: $(window).height()}, 150);
					   return true;
				    });
				    $('#palladiumBtn').click(function () {
					   $('#popular-products').show();
					   $("#home-featuredProducts").hide();
					   $("#crellyslider-1").hide();
					   $('#FizCommerceContainer').show();
					   eCommerceWidgetAction('LoadCatalog', 'PALLADIUM');
					   //$("html, body").animate({ scrollTop: $(window).height()}, 150);
					   return true;
				    });

				    // For Display News Widget.....
				    //eCommerceLoadWidget('news');
				    //eCommerceWidgetEvent('news', '*', newsClickHandler);

				    // Initialization of Chart Widget.....
				    FizChartInit('1148-fb8aecdea7e0101860bd76fa64f524a0', 'STAGE');

				    // Display Spot Price Start...
				    var options = {};
				    FizChartLoad('SpotPriceChart', options, 'spotChart');

				    // Display Intraday Spot Graph Start...
				    var options = {metalSelectorFontSize: '12px' ,titleFontSize: '18px', chartHeight: '160px' };
				    FizChartLoad('IntradaySpot', options, 'spotIntraday');

				    // Display Spot Price Combo Chart Start...
				    var options = {};
				    FizChartLoad('Combo', options, 'spotCombo');

				}, 2500);
			</script>
		</main>
		<!-- #main -->
	</div>
	<!-- #primary -->
<?php #do_action( 'storefront_sidebar' ); ?>
<?php get_footer(); ?>

