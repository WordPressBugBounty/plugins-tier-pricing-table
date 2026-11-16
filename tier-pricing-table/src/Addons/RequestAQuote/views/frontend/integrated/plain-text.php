<?php
/**
 * Request a Quote Plain Text Integrated Template.
 *
 * The quote entry is one more line of text: the label, then the action as a text link.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/plain-text.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $plainTextStyle
 * @var bool $addsToList
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<li class="tiered-pricing-plain-text tpt-request-quote-integrated-plain-text-line<?php echo ! empty( $addsToList ) ? ' tpt-quote-entry--list' : ''; ?>"
	data-tpt-quote-row="tpt-raq-link-<?php echo esc_attr( $productId ); ?>">
	<strong class="tpt-quote-entry__label" data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></strong>
	<?php echo wp_kses_post( $buttonHtml ); ?>
</li>
