<?php
/**
 * Request a Quote Options Integrated Template.
 *
 * The quote entry is the last option of the list. It is an action, not a choice: it has an icon where the
 * price options have their radio circle.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/options.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $optionsStyle
 * @var bool $addsToList
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// styles that wrap the option content in an inner box
$hasInner = in_array( $optionsStyle, array( 'style-3', 'style-4' ), true );
?>
<div class="tiered-pricing-option tpt-request-quote-integrated-option<?php echo ! empty( $addsToList ) ? ' tpt-quote-entry--list' : ''; ?>"
	 data-tpt-quote-row="tpt-raq-link-<?php echo esc_attr( $productId ); ?>">

	<?php if ( $hasInner ) : ?>
	<div class="tiered-pricing-option-inner">
	<?php endif; ?>

		<div class="tiered-pricing-option__checkbox">
			<span class="tpt-quote-entry__icon" aria-hidden="true"></span>
		</div>

		<div class="tpt-quote-entry">
			<div class="tiered-pricing-option__quantity tpt-quote-entry__label">
				<strong data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></strong>
			</div>
			<?php echo wp_kses_post( $buttonHtml ); ?>
		</div>

	<?php if ( $hasInner ) : ?>
	</div>
	<?php endif; ?>
</div>
