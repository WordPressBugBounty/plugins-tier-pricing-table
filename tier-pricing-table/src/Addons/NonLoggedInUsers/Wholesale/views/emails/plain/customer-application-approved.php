<?php
/**
 * Customer e-mail: the wholesale account is approved. (plain text)
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/plain/customer-application-approved.php.
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
echo esc_html__( 'Good news: your wholesale account is approved. Log in to see your wholesale prices.', 'tier-pricing-table' ) . "\n\n";
echo esc_url( wc_get_page_permalink( 'myaccount' ) ) . "\n\n";

if ( $additionalContent = $email->get_additional_content() ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additionalContent ) ) ) . "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
