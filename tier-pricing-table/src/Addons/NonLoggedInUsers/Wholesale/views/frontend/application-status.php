<?php
/**
 * What a customer sees on the registration page once they applied or were approved.
 *
 * Override it by copying this file to yourtheme/tiered-pricing-table/application-status.php.
 *
 * @var string       $status   pending | approved | rejected
 * @var string       $message
 * @var WP_User|null $user
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$icons = array(
	'pending'  => '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
	'approved' => '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
	'rejected' => '<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
);

$titles = array(
	'pending'  => __( 'Application received', 'tier-pricing-table' ),
	'approved' => __( 'Wholesale account active', 'tier-pricing-table' ),
	'rejected' => __( 'Application not approved', 'tier-pricing-table' ),
);
?>
<div class="tpt-wholesale tpt-wholesale-status tpt-wholesale-status--<?php echo esc_attr( $status ); ?>">
	<div class="tpt-wholesale-status__icon"><?php echo $icons[ $status ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG ?></div>
	<div class="tpt-wholesale-status__body">
		<h2 class="tpt-wholesale-status__title"><?php echo esc_html( $titles[ $status ] ?? '' ); ?></h2>
		<p><?php echo wp_kses_post( $message ); ?></p>
		<?php if ( 'approved' === $status ) : ?>
			<p><a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Start shopping', 'tier-pricing-table' ); ?></a></p>
		<?php elseif ( $user ) : ?>
			<p><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Go to my account', 'tier-pricing-table' ); ?></a></p>
		<?php endif; ?>
	</div>
</div>
