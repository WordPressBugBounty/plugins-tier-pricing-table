<?php
/**
 * The free shipping bar of an order rule: the message and the progress towards the threshold.
 *
 * @var string $context Where it shows: cart, checkout, minicart, product, shortcode, block or standalone.
 * @var string $message Safe HTML.
 * @var int    $percent 0 to 100.
 * @var bool   $reached
 * @var string $color   The fill colour (hex).
 * @var int    $height  The track height in pixels.
 * @var \TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule $rule
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<div class="tpt-free-shipping-bar tpt-free-shipping-bar--<?php echo esc_attr( $context ); ?><?php echo $reached ? ' is-reached' : ''; ?>" data-rule="<?php echo esc_attr( $rule->getId() ); ?>">
	<p class="tpt-free-shipping-bar__message"><?php echo wp_kses_post( $message ); ?></p>
	<div class="tpt-free-shipping-bar__track" style="height: <?php echo (int) $height; ?>px;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo (int) $percent; ?>" aria-label="<?php esc_attr_e( 'Progress towards free shipping', 'tier-pricing-table' ); ?>">
		<span class="tpt-free-shipping-bar__fill" style="width: <?php echo (int) $percent; ?>%; background-color: <?php echo esc_attr( $color ); ?>;"></span>
	</div>
</div>
