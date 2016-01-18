<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */
?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="col-full">

			<?php
			/**
			 * @hooked storefront_footer_widgets - 10
			 * @hooked storefront_credit - 20
			 */
			dynamic_sidebar( 'Footer' ); 

			do_action( 'storefront_footer' ); ?>

		</div><!-- .col-full -->
	</footer><!-- #colophon -->

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>
<script type="text/javascript" src="https://stage-connect.fiztrade.com/Scripts/FizCommerce/eCommerce.js"></script>
<script type="text/javascript" src="https://stage-connect.fiztrade.com/Scripts/FizCharts.js"></script>
<!-- <script type="text/javascript" src="https://stage-connect.fiztrade.com/Scripts/FizCommerce/eCommerce-1.0.6.js"> --></script>
</body>
</html>
