<?php

/**
 * Request a Quote Modal Template.
 *
 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-modal.php.
 *
 * @var RequestQuoteForm $form
 * @var int $productId
 * @var WC_Product $product
 * @var QuoteFormDisplay $formDisplay
 */
use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteFormDisplay;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="tpt-quote-modal-<?php 
echo esc_attr( $productId );
?>" class="tpt-quote-modal"
     style="display:none;">
	<div class="tpt-quote-modal-content" role="dialog" aria-modal="true" tabindex="-1"
	     aria-labelledby="tpt-quote-modal-title-<?php 
echo esc_attr( $productId );
?>">
		<div class="tpt-quote-modal-header"
		     style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
			<h3 class="tpt-quote-modal-title" id="tpt-quote-modal-title-<?php 
echo esc_attr( $productId );
?>"
			    style="margin: 0; font-size: 22px; line-height: 1.2;">
				<?php 
$modalTitle = $form->toArray()['modal_title'] ?? '';
echo esc_html( ( $modalTitle ?: __( 'Request a Quote', 'tier-pricing-table' ) ) );
?>
			</h3>
			<button type="button" class="tpt-quote-modal-close"
			        aria-label="<?php 
esc_attr_e( 'Close', 'tier-pricing-table' );
?>">&times;</button>
		</div>
		<form class="tpt-quote-form-element" style="margin: 0">
			<?php 
?>
				<div style="background-color: #fdf2cd; border: 1px solid #f0b849; padding: 10px; margin-bottom: 15px; font-size: 13px; border-radius: 4px;">
					<strong><?php 
esc_html_e( 'Preview Mode', 'tier-pricing-table' );
?>:</strong> <?php 
esc_html_e( 'Request a Quote on the frontend is a premium feature. This popup is only visible to administrators.', 'tier-pricing-table' );
?>
					<br><a href="<?php 
echo esc_url( tpt_fs()->get_upgrade_url() );
?>" target="_blank" style="color: #000; font-weight: 500; text-decoration: underline;"><?php 
esc_html_e( 'Upgrade to Premium', 'tier-pricing-table' );
?></a>
				</div>
			<?php 
?>
			<input type="hidden" name="form_id" class="tpt-quote-form-id"
			       value="<?php 
echo esc_attr( $form->getId() );
?>">
			<input type="hidden" name="product_id" class="tpt-quote-product-id"
			       value="<?php 
echo esc_attr( $productId );
?>">

			<?php 
// honeypot: hidden from people, filled by bots
?>
			<div class="tpt-quote-hp" aria-hidden="true">
				<label for="tpt_quote_website_url_<?php 
echo esc_attr( $productId );
?>">Website URL</label>
				<input type="text" name="tpt_quote_website_url" id="tpt_quote_website_url_<?php 
echo esc_attr( $productId );
?>"
				       value="" tabindex="-1" autocomplete="off">
			</div>

			<div class="tpt-quote-product-info"
			     style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e5e5e5;">
				<?php 
if ( $image_id = $product->get_image_id() ) {
    ?>
					<img src="<?php 
    echo esc_url( wp_get_attachment_image_url( $image_id, 'thumbnail' ) );
    ?>" alt=""
					     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"
					     class="tpt-quote-product-image">
				<?php 
}
?>
				<div>
					<strong class="tpt-quote-product-name"
					        style="display: block; font-size: 16px;"><?php 
echo esc_html( $product->get_name() );
?></strong>
					<div class="price" style="font-size: 14px; margin-top: 4px;">
						<?php 
echo wp_kses_post( $product->get_price_html() );
?>
					</div>
					<div class="tpt-quote-variation-attributes"
					     style="font-size: 13px; margin-top: 4px; display: none;"></div>
				</div>
			</div>

			<div class="tpt-quote-fields-container">
				<?php 
$formDisplay->renderFormFields( $form, $productId );
?>
			</div>

			<div class="tpt-quote-form-message" role="alert"></div>
			<button type="submit" class="button alt wp-element-button"
			        style="width: 100%; margin-top: 10px;">
				<?php 
$btnText = $form->toArray()['submit_button_text'] ?? '';
echo esc_html( ( $btnText ?: __( 'Submit Request', 'tier-pricing-table' ) ) );
?>
			</button>
			<?php 
if ( '' !== $form->getSubmitNote() ) {
    ?>
				<p class="tpt-quote-submit-note"><?php 
    echo esc_html( $form->getSubmitNote() );
    ?></p>
			<?php 
}
?>
		</form>
	</div>
</div>
