<?php
	/**
	 * "Request a quote for this cart" under the checkout button.
	 *
	 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-list-cart-button.php.
	 *
	 * @var string $pageUrl
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<p class="tpt-cart-to-quote-wrap">
	<a href="<?php echo esc_url( $pageUrl ); ?>" class="button wp-element-button tpt-quote-button tpt-quote-button--outline tpt-cart-to-quote" role="button">
		<?php esc_html_e( 'Request a quote for this cart', 'tier-pricing-table' ); ?>
	</a>
</p>
