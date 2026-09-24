<?php
/**
 * Admin e-mail: a new wholesale application.
 *
 * Override it by copying this file to yourtheme/woocommerce/emails/admin-new-application.php.
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

<p><?php esc_html_e( 'A customer applied for a wholesale account. Review the application and approve or reject it.', 'tier-pricing-table' ); ?></p>

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

<p><a href="<?php echo esc_url( $application->getAdminUrl() ); ?>"><?php esc_html_e( 'Open the application', 'tier-pricing-table' ); ?></a></p>

<?php
if ( $additionalContent = $email->get_additional_content() ) {
	echo wp_kses_post( wpautop( wptexturize( $additionalContent ) ) );
}

do_action( 'woocommerce_email_footer', $email );
