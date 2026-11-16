<?php
/**
 * Request a Quote Table Integrated Template.
 *
 * The quote entry is the last row of the pricing table: one cell across every column, with the label and
 * the button inside. The button drops under the label when the two do not fit side by side.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/table.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var bool $isDivTable
 * @var bool $hasQty
 * @var bool $hasPrice
 * @var bool $hasDiscount
 * @var int $columns      every column of the table, custom columns included
 * @var int $span         columns after the quantity cell (kept for templates copied from older versions)
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// templates rendered by older code pass no column count
$columns = isset( $columns ) ? max( 1, (int) $columns ) : ( 1 + ( isset( $span ) ? (int) $span : 1 ) );

if ( $isDivTable ) : ?>
	<div class="tiered-pricing-table-row tpt-request-quote-integrated-row"
		 style="grid-template-columns: minmax(0, 1fr);"
		 data-tpt-quote-row="tpt-raq-table-<?php echo esc_attr( $productId ); ?>">
		<div class="tpt-quote-entry">
			<span class="tpt-quote-entry__label" data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></span>
			<?php echo wp_kses_post( $buttonHtml ); ?>
		</div>
	</div>
<?php else : ?>
	<tr class="tiered-pricing-table-row tpt-request-quote-integrated-row"
		data-tpt-quote-row="tpt-raq-table-<?php echo esc_attr( $productId ); ?>">
		<td class="tpt-quote-entry-cell" colspan="<?php echo esc_attr( $columns ); ?>">
			<div class="tpt-quote-entry">
				<span class="tpt-quote-entry__label" data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></span>
				<?php echo wp_kses_post( $buttonHtml ); ?>
			</div>
		</td>
	</tr>
<?php endif; ?>
