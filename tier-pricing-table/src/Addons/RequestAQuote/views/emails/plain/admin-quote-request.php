<?php
/**
 * Admin Quote Request Email (Plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-quote-request.php.
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

echo esc_html__( 'A new quote request has been submitted on your store.', 'tier-pricing-table' ) . "\n\n";

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
		if ( is_numeric( $quoteItem['price'] ) ) {
			$line .= ' x ' . html_entity_decode( wp_strip_all_tags( wc_price( (float) $quoteItem['price'] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) )
			         . ' = ' . html_entity_decode( wp_strip_all_tags( wc_price( (float) $quoteItem['price'] * $quoteItem['quantity'] ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		}

		echo esc_html( $line ) . "\n";
	}
} else {
	echo esc_html__( 'Product', 'tier-pricing-table' ) . ': ' . ( $product ? wp_kses_post( $product->get_name() ) : esc_html__( 'Unknown', 'tier-pricing-table' ) ) . "\n";
}

$fields = $email->get_submitted_fields();
if ( ! empty( $fields ) ) {
	foreach ( $fields as $field ) {
		echo esc_html( $field['label'] ) . ': ' . esc_html( wp_strip_all_tags( $field['value'] ) ) . ( ! empty( $field['url'] ) ? ' (' . esc_url_raw( $field['url'] ) . ')' : '' ) . "\n";
	}
}

// the price the request was made at: the tier price for the requested quantity (none for products without a price)
$unitPrice = $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest && ! $isQuoteList ? $quotePost->getPrice() : '';
$listTotal = $isQuoteList ? $quotePost->getItemsTotal() : null;

if ( null !== $listTotal ) {
	echo esc_html__( 'Total', 'tier-pricing-table' ) . ': ' . html_entity_decode( wp_strip_all_tags( wc_price( $listTotal ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ) . "\n";
}

if ( is_numeric( $unitPrice ) ) {
	$plainPrice = function ( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	};

	echo esc_html__( 'Unit Price', 'tier-pricing-table' ) . ': ' . $plainPrice( (float) $unitPrice ) . "\n";
	echo esc_html__( 'Total', 'tier-pricing-table' ) . ': ' . $plainPrice( (float) $unitPrice * max( 1, $quotePost->getQuantity() ) ) . "\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $quotePost instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ) {
	echo esc_html__( 'View Request in Dashboard', 'tier-pricing-table' ) . ': ' . esc_url( admin_url( 'post.php?post=' . $quotePost->getId() . '&action=edit' ) ) . "\n\n";
} else {
	echo esc_html__( 'View Request in Dashboard', 'tier-pricing-table' ) . ": #\n\n";
}

echo "\n----------------------------------------\n\n";

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
