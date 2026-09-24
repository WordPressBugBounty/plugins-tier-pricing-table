<?php
/**
 * Request a Quote Options Integrated Template.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/integrated/options.php.
 *
 * @var \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm $form
 * @var int $productId
 * @var string $optionsStyle
 * @var string $buttonHtml
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// styles that wrap the option content in an inner box; style 5 also has a head row, style 6 a main column
$hasInner = in_array( $optionsStyle, array( 'style-3', 'style-4' ), true );
$hasHead  = 'style-4' === $optionsStyle;
$hasMain  = 'style-5' === $optionsStyle;
?>
<div class="tiered-pricing-option tpt-request-quote-integrated-option"
	 onclick="document.getElementById('tpt-raq-link-<?php echo esc_attr( $productId ); ?>').click();">

	<?php if ( $hasInner ) : ?>
	<div class="tiered-pricing-option-inner">
	<?php endif; ?>

		<?php if ( $hasHead ) : ?>
		<div class="tiered-pricing-option__head">
		<?php endif; ?>
			<div class="tiered-pricing-option__checkbox">
				<div class="tiered-pricing-option-checkbox"></div>
			</div>
		<?php if ( $hasHead ) : ?>
		</div>
		<?php endif; ?>

		<?php if ( $hasMain ) : ?>
		<div class="tiered-pricing-option__main">
		<?php endif; ?>
			<div class="tiered-pricing-option__quantity">
				<strong><?php echo esc_html( $form->getIntegratedLabelText() ); ?></strong>
			</div>
		<?php if ( $hasMain ) : ?>
		</div>
		<?php endif; ?>

		<div class="tiered-pricing-option__pricing">
			<div class="tiered-pricing-option-price">
				<?php echo wp_kses_post( $buttonHtml ); ?>
			</div>
		</div>

	<?php if ( $hasInner ) : ?>
	</div>
	<?php endif; ?>
</div>
