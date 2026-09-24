<?php
/**
 * Customer e-mail: the wholesale account is approved.
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/customer-application-approved.php.
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

do_action( 'woocommerce_email_header', $emailHeading, $email ); ?>

<p><?php
/* translators: %s: first name */
echo esc_html( sprintf( __( 'Hi %s,', 'tier-pricing-table' ), $application->getField( 'first_name' ) ?: $application->getName() ) ); ?></p>
<p><?php esc_html_e( 'Good news: your wholesale account is approved. Log in to see your wholesale prices.', 'tier-pricing-table' ); ?></p>
<p><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Log in to your account', 'tier-pricing-table' ); ?></a></p>

<?php
if ( $additionalContent = $email->get_additional_content() ) {
	echo wp_kses_post( wpautop( wptexturize( $additionalContent ) ) );
}

do_action( 'woocommerce_email_footer', $email );
