<?php
/**
 * Customer e-mail: the wholesale application was not approved.
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/customer-application-rejected.php.
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
<p><?php esc_html_e( 'Thank you for your interest. We could not approve your wholesale application at this time.', 'tier-pricing-table' ); ?></p>
<?php if ( $application->getRejectionReason() ) : ?>
	<p><strong><?php esc_html_e( 'Reason:', 'tier-pricing-table' ); ?></strong> <?php echo nl2br( esc_html( $application->getRejectionReason() ) ); ?></p>
<?php endif; ?>
<p><?php esc_html_e( 'If you think this is a mistake or want to add information, just reply to this e-mail.', 'tier-pricing-table' ); ?></p>

<?php
if ( $additionalContent = $email->get_additional_content() ) {
	echo wp_kses_post( wpautop( wptexturize( $additionalContent ) ) );
}

do_action( 'woocommerce_email_footer', $email );
