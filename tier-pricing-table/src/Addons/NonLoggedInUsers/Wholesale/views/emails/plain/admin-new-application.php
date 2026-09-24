<?php
/**
 * Admin e-mail: a new wholesale application. (plain text)
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/plain/admin-new-application.php.
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

echo esc_html__( 'A customer applied for a wholesale account. Review the application and approve or reject it.', 'tier-pricing-table' ) . "\n\n";

echo esc_html( wc_strtoupper( __( 'Application details', 'tier-pricing-table' ) ) ) . "\n\n";

foreach ( $application->getDetails() as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
}

echo "\n";

echo esc_html__( 'Open the application:', 'tier-pricing-table' ) . ' ' . esc_url( $application->getAdminUrl() ) . "\n\n";

if ( $additionalContent = $email->get_additional_content() ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additionalContent ) ) ) . "\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
