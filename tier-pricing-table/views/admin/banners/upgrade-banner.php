<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
	/**
	 * Available variables
	 *
	 * @var string $upgradeUrl
	 * @var string $contactUsUrl
	 */
    
    $accountURL = tpt_fs()->get_account_url();
?>
<div class="tpt-alert">
	
	<div class="tpt-alert__text">
		<div class="tpt-alert__inner">
			<?php
				esc_html_e( 'Upgrade your plan to unlock the great premium features', 'tier-pricing-table' );
			?>
			🚀
			<?php if ( tpt_fs()->is_activation_mode() ) : ?>
				<br>
				<br>
				<?php
				$activationURL = tpt_fs()->get_activation_url();
				$linkText      = esc_html__( 'finish plugin activation', 'tier-pricing-table' );
				
				$upgradeLink = '<a target="_blank" href="' . $activationURL . '">' . $linkText . '</a>';
				?>
				<small style="font-size: .8em">
					<?php
						// translators: %s: activation link
						echo wp_kses_post( sprintf( esc_html__( 'Please %s to proceed with upgrading.', 'tier-pricing-table' ),
							$upgradeLink ) );
					?>
				</small>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="tpt-alert__buttons">
		<div class="tpt-alert__inner">
			
			<a class="tpt-button tpt-button--accent tpt-button--bounce" target="_blank"
			   href="<?php echo esc_attr( $upgradeUrl ); ?>">
				<?php esc_html_e( 'Upgrade to Premium!', 'tier-pricing-table' ); ?>
			</a>
   
			<a target="_blank" class="tpt-button tpt-button--default" href="<?php echo esc_attr( $contactUsUrl ); ?>">
				<?php esc_html_e( 'Contact us', 'tier-pricing-table' ); ?>
			</a>
			
			<?php if ( tpt_fs()->is_activation_mode() ) : ?>
				<a target="_blank" class="tpt-button tpt-button--default"
				   href="<?php echo esc_attr( tpt_fs()->get_activation_url() ); ?>">
					Opt-in
				</a>
			<?php elseif( tpt_fs()->get_user() ) : ?>
				<a target="_blank" class="tpt-button tpt-button--default"
				   href="<?php echo esc_attr( admin_url( 'admin.php?page=tiered-pricing-table-account' ) ); ?>">
					Your account
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>