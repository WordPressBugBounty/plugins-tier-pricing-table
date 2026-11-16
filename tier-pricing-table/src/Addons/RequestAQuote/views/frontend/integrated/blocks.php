<?php
/**
 * Request a Quote Blocks Integrated Template.
 *
 * The quote entry is one more block, and the whole block is the button: the label on top, the action
 * under it. It looks different from the price blocks (dashed border, no fill) so it reads as an action.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/blocks.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $blocksStyle
 * @var string $promptText
 * @var bool $addsToList
 * @var string $buttonHtml  The hidden trigger the block presses.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hasInner = 'style-3' === $blocksStyle;
?>
<div class="tiered-pricing-block tpt-request-quote-integrated-block<?php echo ! empty( $addsToList ) ? ' tpt-quote-entry--list' : ''; ?>"
	 role="button"
	 tabindex="0"
	 data-tpt-quote-row="tpt-raq-link-<?php echo esc_attr( $productId ); ?>">
	<?php echo wp_kses_post( $buttonHtml ); ?>

	<?php if ( $hasInner ) : ?>
	<div class="tiered-pricing-block-inner">
	<?php endif; ?>

		<div class="tiered-pricing-block__quantity tpt-quote-block__label">
			<span data-tpt-quote-label><?php echo esc_html( $form->getIntegratedLabelText() ); ?></span>
		</div>
		<div class="tiered-pricing-block__price tpt-quote-block__action">
			<span><?php echo esc_html( $promptText ); ?></span>
		</div>

	<?php if ( $hasInner ) : ?>
	</div>
	<?php endif; ?>
</div>
