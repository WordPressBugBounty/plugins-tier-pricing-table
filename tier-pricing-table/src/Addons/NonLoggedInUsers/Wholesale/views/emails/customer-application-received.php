<?php
/**
 * Customer e-mail: the wholesale application is waiting for approval.
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/customer-application-received.php.
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
<p><?php esc_html_e( 'Thank you for applying for a wholesale account. We review every application and will e-mail you as soon as yours is approved. Here is what you sent us:', 'tier-pricing-table' ); ?></p>

<h2><?php esc_html_e( 'Application details', 'tier-pricing-table' ); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%%; margin-bottom: 40px; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<tbody>
	<?php foreach ( $application->getDetails() as $label => $value ) : ?>
		<tr>
			<th class="td" scope="row" style="text-align:left; width: 35%%;"><?php echo esc_html( $label ); ?></th>
			<td class="td" style="text-align:left;"><?php echo nl2br( esc_html( $value ) ); ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>


<?php
if ( $additionalContent = $email->get_additional_content() ) {
	echo wp_kses_post( wpautop( wptexturize( $additionalContent ) ) );
}

do_action( 'woocommerce_email_footer', $email );
