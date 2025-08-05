<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available variables
 *
 * @var string $upgradeUrl
 * @var string $contactUsUrl
 */
?>
<div class="tpt-alert">
	
	<div class="tpt-alert__text">
		<div class="tpt-alert__inner">
			<?php
				esc_html_e( 'Upgrade your plan to unlock the great premium features.', 'tier-pricing-table' );
			?>
		</div>
	</div>
	
	<div class="tpt-alert__buttons">
		<div class="tpt-alert__inner">
			
			<a class="tpt-button tpt-button--accent tpt-button--bounce" target="_blank"
			   href="<?php echo esc_attr( $upgradeUrl ); ?>">
				<?php esc_html_e( 'Upgrade to Premium!', 'tier-pricing-table' ); ?>
			</a>
			
			<span style="font-weight: bold; color: #646970"> - or -</span>
			
			<a target="_blank" class="tpt-button tpt-button--secondary"
			   href="<?php echo esc_attr( tpt_fs()->get_trial_url() ); ?>">
				<?php esc_html_e( 'Try trial', 'tier-pricing-table' ); ?>
			</a>
			
			<a target="_blank" class="tpt-button tpt-button--default" href="<?php echo esc_attr( $contactUsUrl ); ?>">
				<?php esc_html_e( 'Contact Us!', 'tier-pricing-table' ); ?>
			</a>
		</div>
	</div>
</div>