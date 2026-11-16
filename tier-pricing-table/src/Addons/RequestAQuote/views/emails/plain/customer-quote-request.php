<?php
/**
 * Customer Quote Request Email (Plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-quote-request.php.
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

echo "= " . esc_html( $emailHeading ) . " =\n\n";

echo esc_html__( 'Thank you for your quote request. We have received it and will process it shortly. Here are the details of your request:', 'tier-pricing-table' ) . "\n\n";

if ( $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && $quotePost->getId() ) {
	/* translators: %s: request number */
	echo esc_html( sprintf( __( 'Quote request #%s', 'tier-pricing-table' ), $quotePost->getId() ) ) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

// a quote list request: one line per product
$isQuoteList = $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && $quotePost->isList();

if ( $isQuoteList ) {
	foreach ( $quotePost->getItems() as $quoteItem ) {
		$line = $quoteItem['name'] . ( '' !== $quoteItem['sku'] ? ' (' . $quoteItem['sku'] . ')' : '' ) . ': ' . $quoteItem['quantity'];

		echo esc_html( $line ) . "\n";
	}
} else {
	echo esc_html__( 'Product', 'tier-pricing-table' ) . ': ' . ( $product ? wp_kses_post( $product->get_name() ) : esc_html__( 'Unknown', 'tier-pricing-table' ) ) . "\n";
}

$fields = $email->get_submitted_fields();
if ( ! empty( $fields ) ) {
	foreach ( $fields as $field ) {
		echo esc_html( $field['label'] ) . ': ' . esc_html( wp_strip_all_tags( $field['value'] ) ) . "\n";
	}
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'We will be in touch soon!', 'tier-pricing-table' ) . "\n\n";

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
