<?php
/**
 * Request a Quote Plain Text Integrated Template.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/plain-text.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $plainTextStyle
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// the "one line" style keeps its tiers inline
$display = isset( $plainTextStyle ) && 'style-2' === $plainTextStyle ? 'inline-flex' : 'flex';
?>
<li class="tiered-pricing-plain-text tpt-request-quote-integrated-plain-text-line"
	style="display: <?php echo esc_attr( $display ); ?>; gap: 10px; align-items: center;"
	onclick="document.getElementById('tpt-raq-link-<?php echo esc_attr( $productId ); ?>').click();">
	<strong><?php echo esc_html( $form->getIntegratedLabelText() ); ?></strong>
	<?php echo wp_kses_post( $buttonHtml ); ?>
</li>
