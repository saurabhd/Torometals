<?php
/**
 * The template for displaying 404 pages (not found).
 *
 * @package storefront
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<section class="error-404 not-found">
				<div class="page-content">
					<div class="errorcontainer">
						<div class="erroricon">
							<span class="error-icon">
								<img alt="" src="http://newco2.youniversal.mobi/wp-content/uploads/2015/12/1.png">
								<span class="error-txt">Looks like the page you’re looking for was moved.</span>
							</span>
						</div>
						<div class="errorcontent"><div class="hd-typ2">Please try one of the following options:</div> 								<ul>
								<li>Make sure you typed the correct URL or followed a valid link.</li> 									
								<li>Try to access the page directly from the <a title="Home" class="Home" 
								href="http://newco2.youniversal.mobi/">home</a> page.</li>
								<li>Please contact <a title="Contact us" href="http://newco2.youniversal.mobi/contact-us/">webmaster</a> 
								if you think there are some errors in processing the web page.</li>
							</ul>
						</div>
					</div>
				</div><!-- .page-content -->
			</section><!-- .error-404 -->

		</main><!-- #main -->
	</div><!-- #primary -->

<?php get_footer(); ?>
