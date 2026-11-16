<?php
	/**
	 * The quote list page: the products the shopper collected and the form that sends them.
	 *
	 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-list.php.
	 *
	 * @var RequestQuoteForm|null $form
	 * @var string $shopUrl
	 * @var string $honeypotField
	 * @var string $buttonText What the store's quote buttons say.
	 */

	use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteFormDisplay;
	use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<div class="tpt-quote-list" data-tpt-quote-list>
	<p class="tpt-quote-list__status" data-tpt-quote-list-status role="status" hidden></p>

	<div class="tpt-quote-list__lines" data-tpt-quote-list-lines aria-live="polite" aria-busy="true">
		<p class="tpt-quote-list__loading"><?php esc_html_e( 'Loading your quote list…', 'tier-pricing-table' ); ?></p>
	</div>

	<div class="tpt-quote-list__empty" data-tpt-quote-list-empty hidden>
		<p class="tpt-quote-list__empty-title"><?php esc_html_e( 'Your quote list is empty', 'tier-pricing-table' ); ?></p>
		<p>
			<?php
				/* translators: %s: the text of the store's quote button, like "Add to quote" */
				echo esc_html( sprintf( __( 'Add products with the "%s" button, then come back here to send one request for all of them.', 'tier-pricing-table' ), $buttonText ?? __( 'Add to quote', 'tier-pricing-table' ) ) );
			?>
		</p>
		<?php if ( $shopUrl ) : ?>
			<a class="button wp-element-button" href="<?php echo esc_url( $shopUrl ); ?>"><?php esc_html_e( 'Browse products', 'tier-pricing-table' ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( $form ) : ?>
		<form class="tpt-quote-list__form" data-tpt-quote-list-form hidden>
			<h2 class="tpt-quote-list__form-title"><?php esc_html_e( 'Your details', 'tier-pricing-table' ); ?></h2>

			<input type="hidden" name="form_id" value="<?php echo esc_attr( $form->getId() ); ?>">

			<?php // honeypot: hidden from people, filled by bots ?>
			<div class="tpt-quote-hp" aria-hidden="true">
				<label for="tpt_quote_list_website_url">Website URL</label>
				<input type="text" name="<?php echo esc_attr( $honeypotField ); ?>" id="tpt_quote_list_website_url" value="" tabindex="-1" autocomplete="off">
			</div>

			<div class="tpt-quote-fields-container">
				<?php QuoteFormDisplay::renderFormFields( $form, 0, true ); ?>
			</div>

			<div class="tpt-quote-list__message" data-tpt-quote-list-message role="alert"></div>

			<button type="submit" class="button alt wp-element-button tpt-quote-list__submit">
				<?php echo esc_html( $form->getSubmitButtonText() ?: __( 'Send quote request', 'tier-pricing-table' ) ); ?>
			</button>
			<?php if ( '' !== $form->getSubmitNote() ) : ?>
				<p class="tpt-quote-submit-note tpt-quote-list__submit-note"><?php echo esc_html( $form->getSubmitNote() ); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>

	<div class="tpt-quote-list__success" data-tpt-quote-list-success tabindex="-1" hidden>
		<p class="tpt-quote-list__success-title"><?php esc_html_e( 'Your request is sent', 'tier-pricing-table' ); ?></p>
		<p data-tpt-quote-list-success-message></p>
		<p data-tpt-quote-list-reference></p>
		<p data-tpt-quote-list-copy hidden></p>
		<div class="tpt-quote-list__sent" data-tpt-quote-list-sent></div>
		<?php if ( $shopUrl ) : ?>
			<a class="button wp-element-button" href="<?php echo esc_url( $shopUrl ); ?>"><?php esc_html_e( 'Continue shopping', 'tier-pricing-table' ); ?></a>
		<?php endif; ?>
	</div>
</div>
