<?php
/**
 * Customer Quote Request Email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-quote-request.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ) {
	$productId = $quotePost->getProductId();
	$product    = wc_get_product( $productId );
} else {
	$product = false;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $emailHeading, $email ); ?>

<p><?php esc_html_e( 'Thank you for your quote request. We have received it and will process it shortly. Here are the details of your request:', 'tier-pricing-table' ); ?></p>

<h2>
	<?php
		if ( $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && $quotePost->getId() ) {
			/* translators: %s: request number */
			echo esc_html( sprintf( __( 'Quote request #%s', 'tier-pricing-table' ), $quotePost->getId() ) );
		} else {
			esc_html_e( 'Quote Request Details', 'tier-pricing-table' );
		}
	?>
</h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<tbody>
			<?php
			// a quote list request: one row per product
			$isQuoteList = $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && $quotePost->isList();
			?>
			<?php if ( $isQuoteList ) : ?>
				<?php foreach ( $quotePost->getItems() as $quoteItem ) : ?>
					<tr>
						<th class="td" scope="row" style="text-align:left;">
							<?php echo esc_html( $quoteItem['name'] ); ?>
							<?php if ( '' !== $quoteItem['sku'] ) : ?>
								<br><small><?php esc_html_e( 'SKU:', 'tier-pricing-table' ); ?> <?php echo esc_html( $quoteItem['sku'] ); ?></small>
							<?php endif; ?>
						</th>
						<td class="td" style="text-align:left;">
							<?php echo esc_html( $quoteItem['quantity'] ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
				<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Product', 'tier-pricing-table' ); ?></th>
				<td class="td" style="text-align:left;"><?php echo $product ? wp_kses_post( $product->get_name() ) : esc_html__( 'Unknown', 'tier-pricing-table' ); ?></td>
				</tr>
			<?php endif; ?>
			<?php
			$fields = $email->get_submitted_fields();
			if ( ! empty( $fields ) ) :
				foreach ( $fields as $field ) : ?>
					<tr>
						<th class="td" scope="row" style="text-align:left;"><?php echo esc_html( $field['label'] ); ?></th>
						<td class="td" style="text-align:left;"><?php echo wp_kses_post( nl2br( $field['value'] ) ); ?></td>
					</tr>
				<?php endforeach;
			endif;
			?>
		</tbody>
	</table>
</div>

<p><?php esc_html_e( 'We will be in touch soon!', 'tier-pricing-table' ); ?></p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
