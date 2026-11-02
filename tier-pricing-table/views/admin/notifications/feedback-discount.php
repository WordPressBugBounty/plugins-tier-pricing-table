<?php defined( 'WPINC' ) || die;

	use TierPricingTable\Admin\Notifications\Notifications\TwoMonthsUsingDiscount;
	use TierPricingTable\TierPricingTablePlugin;

	/**
	 * Feedback for a coupon: shown on the plugin's own screens after two months (and a year) on
	 * the free plan, until dismissed; "Remind me later" keeps it away for a month.
	 *
	 * @var TwoMonthsUsingDiscount $notification
	 */

	$features    = TierPricingTablePlugin::getPremiumFeatures();
	$upgradeUrl  = tpt_fs_activation_url();
	$feedbackUrl = 'https://forms.gle/yWTt3aWuvZVQhbRZ7';
?>

<style>
	.tpt-feedback.notice { position: relative; margin: 20px 0 15px; padding: 0; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid var(--wp-admin-theme-color, #2271b1); border-radius: 4px; box-shadow: none; color: #1d2327; }
	.tpt-feedback__close { position: absolute; top: 10px; right: 12px; color: #787c82; text-decoration: none; font-size: 20px; line-height: 1; }
	.tpt-feedback__close:hover, .tpt-feedback__close:focus { color: #1d2327; }
	.tpt-feedback__inner { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 32px; padding: 22px 28px 24px; }
	.tpt-feedback__badge { display: inline-block; margin-bottom: 10px; padding: 2px 9px; border-radius: 999px; background: #f0f0f1; color: #3c434a; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
	.tpt-feedback__title { margin: 0 0 8px; font-size: 18px; line-height: 1.3; font-weight: 600; color: #1d2327; }
	.tpt-feedback__title strong { color: var(--wp-admin-theme-color, #2271b1); }
	.tpt-feedback__text { margin: 0 0 16px; color: #50575e; font-size: 14px; line-height: 1.55; max-width: 60ch; }
	.tpt-feedback__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; }
	.tpt-feedback__actions .button { margin: 0; }
	.tpt-feedback__actions a:not(.button) { color: #787c82; font-size: 13px; }
	.tpt-feedback__features { border-left: 1px solid #f0f0f1; padding-left: 32px; }
	.tpt-feedback__features-title { margin: 0 0 10px; font-size: 13px; font-weight: 600; color: #1d2327; }
	.tpt-feedback__list { list-style: none; margin: 0 0 12px; padding: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 20px; }
	.tpt-feedback__list li { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; line-height: 1.45; color: #3c434a; }
	.tpt-feedback__list svg { flex: 0 0 16px; width: 16px; height: 16px; margin-top: 1px; color: #00a32a; }
	.tpt-feedback__help { margin: 0; font-size: 12px; color: #787c82; }
	@media screen and (max-width: 1100px) { .tpt-feedback__inner { grid-template-columns: 1fr; gap: 20px; } .tpt-feedback__features { border-left: 0; padding-left: 0; border-top: 1px solid #f0f0f1; padding-top: 16px; } }
	@media screen and (max-width: 600px) { .tpt-feedback__list { grid-template-columns: 1fr; } }
</style>

<div class="tpt-feedback notice">
	<a href="<?php echo esc_url( $notification->getCloseURL() ); ?>" class="tpt-feedback__close" aria-label="<?php esc_attr_e( 'Dismiss', 'tier-pricing-table' ); ?>">&times;</a>

	<div class="tpt-feedback__inner">
		<div>
			<span class="tpt-feedback__badge"><?php esc_html_e( 'Tiered Pricing Table', 'tier-pricing-table' ); ?></span>

			<h3 class="tpt-feedback__title">
				<?php
				echo wp_kses( sprintf(
					/* translators: %s: "save 20%" */
					__( 'Tell us what you think and %s on premium', 'tier-pricing-table' ),
					'<strong>' . esc_html__( 'save 20%', 'tier-pricing-table' ) . '</strong>'
				), array( 'strong' => array() ) );
				?>
			</h3>

			<p class="tpt-feedback__text">
				<?php esc_html_e( 'You have been using the plugin for a while. Answer four short questions about it and get a 20% coupon for the premium version.', 'tier-pricing-table' ); ?>
			</p>

			<div class="tpt-feedback__actions">
				<a class="button button-primary" href="<?php echo esc_url( $feedbackUrl ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Give feedback and get the coupon', 'tier-pricing-table' ); ?></a>
				<a class="button" href="<?php echo esc_url( $upgradeUrl ); ?>"><?php esc_html_e( 'Upgrade now', 'tier-pricing-table' ); ?></a>
				<a href="<?php echo esc_url( $notification->getSnoozeURL() ); ?>"><?php esc_html_e( 'Remind me later', 'tier-pricing-table' ); ?></a>
				<a href="<?php echo esc_url( $notification->getCloseURL() ); ?>"><?php esc_html_e( 'No thanks', 'tier-pricing-table' ); ?></a>
			</div>
		</div>

		<div class="tpt-feedback__features">
			<p class="tpt-feedback__features-title"><?php esc_html_e( 'What premium adds', 'tier-pricing-table' ); ?></p>
			<ul class="tpt-feedback__list">
				<?php foreach ( $features as $feature ) : ?>
					<li>
						<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="10" fill="currentColor" opacity=".14"/><path d="M6 10.4l2.6 2.6L14 7.6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<?php echo esc_html( $feature ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="tpt-feedback__help">
				<?php
				printf(
					/* translators: %s: link to the contact page */
					esc_html__( 'Questions? %s', 'tier-pricing-table' ),
					'<a href="' . esc_url( TierPricingTablePlugin::getContactUsURL() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Contact us', 'tier-pricing-table' ) . '</a>'
				);
				?>
			</p>
		</div>
	</div>
</div>
