<?php
/**
 * Customer e-mail: the wholesale application was not approved. (plain text)
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/plain/customer-application-rejected.php.
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
echo esc_html__( 'Thank you for your interest. We could not approve your wholesale application at this time.', 'tier-pricing-table' ) . "\n\n";
if ( $application->getRejectionReason() ) {
	echo esc_html__( 'Reason:', 'tier-pricing-table' ) . ' ' . esc_html( $application->getRejectionReason() ) . "\n\n";
}
echo esc_html__( 'If you think this is a mistake or want to add information, just reply to this e-mail.', 'tier-pricing-table' ) . "\n\n";

if ( $additionalContent = $email->get_additional_content() ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additionalContent ) ) ) . "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
