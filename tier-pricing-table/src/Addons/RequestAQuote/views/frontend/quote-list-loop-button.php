<?php
	/**
	 * "Add to quote" on shop and category pages.
	 *
	 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-list-loop-button.php.
	 *
	 * @var WC_Product $product
	 * @var int $quantity The quantity added (the product's minimum).
	 * @var string $text
	 * @var string $classes The button's style classes.
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<div class="tpt-add-to-quote-wrap">
	<a href="<?php echo esc_url( $product->get_permalink() ); ?>"
	   class="<?php echo esc_attr( $classes ?? 'button wp-element-button tpt-quote-button tpt-quote-button--outline' ); ?> tpt-add-to-quote tpt-add-to-quote--loop"
	   data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
	   data-quantity="<?php echo esc_attr( $quantity ); ?>"
	   role="button"><?php echo esc_html( $text ); ?></a>
</div>
