<?php
/**
 * Admin Quote Request Email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-quote-request.php.
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

<p><?php esc_html_e( 'A new quote request has been submitted on your store.', 'tier-pricing-table' ); ?></p>

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
						<?php if ( is_numeric( $quoteItem['price'] ) ) : ?>
							&times; <?php echo wp_kses_post( wc_price( (float) $quoteItem['price'] ) ); ?>
							= <strong><?php echo wp_kses_post( wc_price( (float) $quoteItem['price'] * $quoteItem['quantity'] ) ); ?></strong>
						<?php endif; ?>
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
						<td class="td" style="text-align:left;">
							<?php if ( ! empty( $field['url'] ) ) : ?>
								<a href="<?php echo esc_url( $field['url'] ); ?>"><?php echo esc_html( $field['value'] ); ?></a>
							<?php else : ?>
								<?php echo wp_kses_post( nl2br( $field['value'] ) ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach;
			endif;

			// the price the request was made at: the tier price for the requested quantity (none for products without a price)
			$unitPrice = $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && ! $isQuoteList ? $quotePost->getPrice() : '';
			$listTotal = $isQuoteList ? $quotePost->getItemsTotal() : null;

			if ( null !== $listTotal ) :
				?>
				<tr>
					<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
					<td class="td" style="text-align:left;"><strong><?php echo wp_kses_post( wc_price( $listTotal ) ); ?></strong></td>
				</tr>
				<?php
			endif;

			if ( is_numeric( $unitPrice ) ) :
				$quoteQuantity = max( 1, $quotePost->getQuantity() );
				?>
				<tr>
					<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Unit Price', 'tier-pricing-table' ); ?></th>
					<td class="td" style="text-align:left;"><?php echo wp_kses_post( wc_price( (float) $unitPrice ) ); ?></td>
				</tr>
				<tr>
					<th class="td" scope="row" style="text-align:left;"><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
					<td class="td" style="text-align:left;"><?php echo wp_kses_post( wc_price( (float) $unitPrice * $quoteQuantity ) ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

<p>
	<?php if ( $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ) : ?>
		<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $quotePost->getId() . '&action=edit' ) ); ?>">
			<?php esc_html_e( 'View Request in Dashboard', 'tier-pricing-table' ); ?>
		</a>
	<?php else : ?>
		<a href="#">
			<?php esc_html_e( 'View Request in Dashboard', 'tier-pricing-table' ); ?>
		</a>
	<?php endif; ?>
</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
