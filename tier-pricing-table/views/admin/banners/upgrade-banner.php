<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
	/**
	 * Available variables
	 *
	 * @var string $upgradeUrl
	 * @var string $contactUsUrl
	 * @var array  $setup url, done, total, left; empty once every getting-started step is done
	 */
	
	$accountURL = tpt_fs()->get_account_url();
?>
<div class="tpt-alert">
	
	<div class="tpt-alert__text">
		<div class="tpt-alert__inner">
			<span style="margin-left: 6px;">
				<?php esc_html_e( 'Tiered Pricing Table', 'tier-pricing-table' ); ?>
			</span>
			<span class="tpt-badge tpt-badge--free"><?php esc_html_e( 'Free', 'tier-pricing-table' ); ?></span>
			<?php if ( ! empty( $setup ) ) : ?>
				<a class="tpt-setup" href="<?php echo esc_url( $setup['url'] ); ?>">
					<span class="tpt-setup__ring" aria-hidden="true">
						<svg viewBox="0 0 36 36" focusable="false">
							<circle class="tpt-setup__track" cx="18" cy="18" r="15.915"></circle>
							<?php if ( $setup['done'] > 0 ) : ?>
								<circle class="tpt-setup__bar" cx="18" cy="18" r="15.915" stroke-dasharray="<?php echo esc_attr( round( $setup['done'] / max( 1, $setup['total'] ) * 100 ) ); ?> 100"></circle>
							<?php endif; ?>
						</svg>
					</span>
					<?php // translators: %1$d: getting-started steps done, %2$d: steps in total ?>
					<span><?php echo esc_html( sprintf( __( 'Setup %1$d of %2$d', 'tier-pricing-table' ), $setup['done'], $setup['total'] ) ); ?></span>
					<span class="tpt-setup__sep" aria-hidden="true">&middot;</span>
					<span class="tpt-setup__link"><?php esc_html_e( 'Continue', 'tier-pricing-table' ); ?></span>
				</a>
			<?php endif; ?>
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
						echo wp_kses_post( sprintf( esc_html__( 'Please %s to upgrade.', 'tier-pricing-table' ),
							$upgradeLink ) );
					?>
				</small>
			<?php endif; ?>
		</div>
	</div>
	
	<div class="tpt-alert__buttons">
		<div class="tpt-alert__inner">
			
			<a class="tpt-button tpt-button--accent" target="_blank" style="display: inline-flex; align-items: center;"
			   href="<?php echo esc_attr( $upgradeUrl ); ?>">
			   <svg style="width: 16px; height: 16px; fill: currentColor; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
				<?php esc_html_e( 'Upgrade to premium', 'tier-pricing-table' ); ?>
			</a>

			<a target="_blank" class="tpt-button tpt-button--default" style="display: inline-flex; align-items: center;" href="<?php echo esc_attr( $contactUsUrl ); ?>">
				<svg style="width: 16px; height: 16px; stroke: currentColor; fill: none; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
				<?php esc_html_e( 'Contact us', 'tier-pricing-table' ); ?>
			</a>

			<?php if ( tpt_fs()->is_activation_mode() ) : ?>
				<a target="_blank" class="tpt-button tpt-button--default"
				   href="<?php echo esc_attr( tpt_fs()->get_activation_url() ); ?>">
					<?php esc_html_e( 'Opt-in', 'tier-pricing-table' ); ?>
				</a>
			<?php elseif ( tpt_fs()->get_user() ) : ?>
				<a target="_blank" class="tpt-button tpt-button--default" style="display: inline-flex; align-items: center;"
				   href="<?php echo esc_attr( admin_url( 'admin.php?page=tiered-pricing-table-account' ) ); ?>">
				    <svg style="width: 16px; height: 16px; stroke: currentColor; fill: none; margin-right: 6px;" xmlns="http://www.w3.org/2000/svg" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					<?php esc_html_e( 'Account', 'tier-pricing-table' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>