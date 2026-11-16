<?php
	/**
	 * Shown in place of the quick order form to customers who may not use it.
	 *
	 * Override in a theme: tier-pricing-table/quick-order/denied.php
	 *
	 * @var string $message
	 * @var string $loginUrl empty for logged-in customers
	 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="tpt-quick-order-denied woocommerce-info">
	<?php echo wp_kses_post( $message ); ?>
	<?php if ( $loginUrl ) : ?>
		<a href="<?php echo esc_url( $loginUrl ); ?>"><?php esc_html_e( 'Log in', 'tier-pricing-table' ); ?></a>
	<?php endif; ?>
</div>
