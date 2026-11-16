<?php
/**
 * Request a Quote Dropdown Integrated Template.
 *
 * The quote entry is the last line of the dropdown list and stays in view while the list scrolls.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/dropdown.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="tiered-pricing-dropdown-option tpt-request-quote-integrated-dropdown-option"
	data-tpt-quote-row="tpt-raq-link-<?php echo esc_attr( $productId ); ?>">
	<div class="tpt-quote-entry">
		<strong class="tpt-quote-entry__label" data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></strong>
		<?php echo wp_kses_post( $buttonHtml ); ?>
	</div>
</li>
