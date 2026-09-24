<?php
/**
 * Customer e-mail: the wholesale application is waiting for approval. (plain text)
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/plain/customer-application-received.php.
 *
 * @var \TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication $application
 * @var string   $emailHeading
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . esc_html( wp_strip_all_tags( $emailHeading ) ) . " =\n\n";

/* translators: %s: first name */
echo esc_html( sprintf( __( 'Hi %s,', 'tier-pricing-table' ), $application->getField( 'first_name' ) ?: $application->getName() ) ) . "\n\n";
echo esc_html__( 'Thank you for applying for a wholesale account. We review every application and will e-mail you as soon as yours is approved. Here is what you sent us:', 'tier-pricing-table' ) . "\n\n";

echo esc_html( wc_strtoupper( __( 'Application details', 'tier-pricing-table' ) ) ) . "\n\n";

foreach ( $application->getDetails() as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
}

echo "\n";


if ( $additionalContent = $email->get_additional_content() ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additionalContent ) ) ) . "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
