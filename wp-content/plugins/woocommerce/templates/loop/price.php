<?php
/**
 * Loop Price
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

# Display Review Start Section Start	
$args = array ('post_type' => 'product','post_id' => $product->id, 'ID' => $product->id);
$comment = get_comments($args);  
$rating = intval(get_comment_meta( $comment[0]->comment_ID, 'rating', true ));
if ( $rating > 0 && get_option( 'woocommerce_enable_review_rating' ) == 'yes' ) : ?> 
	<div class="home-star-rating">
		<div itemprop="reviewRating" itemscope itemtype="http://schema.org/Rating" class="star-rating" 
		title="<?php echo sprintf( __( 'Rated %d out of 5', 'woocommerce' ), $rating ) ?>">
			<span style="width:<?php echo ( $rating / 5 ) * 100; ?>%"><strong itemprop="ratingValue"><?php 
			echo $rating; ?></strong> <?php _e( 'out of 5', 'woocommerce' ); ?></span>
		</div> 
		<div class="home-reviews"><?php echo $rating . " REVIEW(S)"?></div>
	</div>
<?php else : ?>
	<div class="home-star-rating">&nbsp;</div>
<?php endif; ?> 
	<?php if ( $comment->comment_approved == '0' ) : ?> 
		<p class="meta"><em><?php _e( 'Your comment is awaiting approval', 'woocommerce' ); ?></em></p>
	<?php else : ?> 
<?php endif; 
# Display Review Start Section End
?>

<?php if ( $price_html = $product->get_price_html() ) : ?>
	<span class="price"><?php echo $price_html; ?></span>
<?php endif; ?>
