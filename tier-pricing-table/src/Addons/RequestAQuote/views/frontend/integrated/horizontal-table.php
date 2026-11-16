<?php
/**
 * Request a Quote Horizontal Table Integrated Template.
 *
 * The quote entry is a strip under the horizontal table, as wide as the table: a column of its own would
 * be pushed out of sight on narrow screens.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/horizontal-table.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tpt-quote-entry tpt-quote-entry--under-table"
	 data-tpt-quote-row="tpt-raq-table-<?php echo esc_attr( $productId ); ?>">
	<span class="tpt-quote-entry__label" data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></span>
	<?php echo wp_kses_post( $buttonHtml ); ?>
</div>
