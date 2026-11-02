<?php defined( 'WPINC' ) || die;

	use TierPricingTable\Admin\Notifications\Notifications\BlackFriday;
	use TierPricingTable\TierPricingTablePlugin;

	/**
	 * The Black Friday offer, see BlackFriday::passedRequirements() for when it shows.
	 *
	 * @var BlackFriday $notification
	 */

	$coupon     = $notification->getCoupon();
	$features   = TierPricingTablePlugin::getPremiumFeatures();
	$upgradeUrl = add_query_arg( array( 'coupon' => $coupon['code'] ), tpt_fs_activation_url() );
	$lastDay    = wp_date( get_option( 'date_format' ), $notification->getLastDay() );
?>

<style>
	.tpt-bf.notice { position: relative; margin: 20px 0 15px; padding: 0; background: #fff; border: 1px solid #dcdcde; border-left: 4px solid var(--wp-admin-theme-color, #2271b1); border-radius: 4px; box-shadow: none; color: #1d2327; }
	.tpt-bf__close { position: absolute; top: 10px; right: 12px; color: #787c82; text-decoration: none; font-size: 20px; line-height: 1; }
	.tpt-bf__close:hover, .tpt-bf__close:focus { color: #1d2327; }
	.tpt-bf__inner { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr); gap: 32px; padding: 22px 28px 24px; }
	.tpt-bf__badge { display: inline-block; margin-bottom: 10px; padding: 2px 9px; border-radius: 999px; background: #1d2327; color: #fff; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
	.tpt-bf__title { margin: 0 0 8px; font-size: 18px; line-height: 1.3; font-weight: 600; color: #1d2327; }
	.tpt-bf__title strong { color: var(--wp-admin-theme-color, #2271b1); }
	.tpt-bf__text { margin: 0 0 14px; color: #50575e; font-size: 14px; line-height: 1.55; max-width: 60ch; }
	.tpt-bf__coupon { display: inline-flex; align-items: center; gap: 10px; margin: 0 0 16px; padding: 8px 14px; border: 1px dashed #8c8f94; border-radius: 6px; font-size: 13px; color: #50575e; }
	.tpt-bf__code { font-family: Menlo, Consolas, monospace; font-size: 15px; font-weight: 700; letter-spacing: .08em; color: #1d2327; user-select: all; }
	.tpt-bf__actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; }
	.tpt-bf__actions .button { margin: 0; }
	.tpt-bf__actions a:not(.button) { color: #787c82; font-size: 13px; }
	.tpt-bf__features { border-left: 1px solid #f0f0f1; padding-left: 32px; }
	.tpt-bf__features-title { margin: 0 0 10px; font-size: 13px; font-weight: 600; color: #1d2327; }
	.tpt-bf__list { list-style: none; margin: 0 0 12px; padding: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 20px; }
	.tpt-bf__list li { display: flex; align-items: flex-start; gap: 8px; font-size: 13px; line-height: 1.45; color: #3c434a; }
	.tpt-bf__list svg { flex: 0 0 16px; width: 16px; height: 16px; margin-top: 1px; color: #00a32a; }
	.tpt-bf__help { margin: 0; font-size: 12px; color: #787c82; }
	@media screen and (max-width: 1100px) { .tpt-bf__inner { grid-template-columns: 1fr; gap: 20px; } .tpt-bf__features { border-left: 0; padding-left: 0; border-top: 1px solid #f0f0f1; padding-top: 16px; } }
	@media screen and (max-width: 600px) { .tpt-bf__list { grid-template-columns: 1fr; } }
</style>

<div class="tpt-bf notice">
	<a href="<?php echo esc_url( $notification->getCloseURL() ); ?>" class="tpt-bf__close" aria-label="<?php esc_attr_e( 'Dismiss', 'tier-pricing-table' ); ?>">&times;</a>

	<div class="tpt-bf__inner">
		<div>
			<span class="tpt-bf__badge"><?php esc_html_e( 'Black Friday', 'tier-pricing-table' ); ?></span>

			<h3 class="tpt-bf__title">
				<?php
				echo wp_kses( sprintf(
					/* translators: %s: "save 25%" */
					__( 'Tiered Pricing Table: %s on premium', 'tier-pricing-table' ),
					/* translators: %d: discount in percent */
					'<strong>' . esc_html( sprintf( __( 'save %d%%', 'tier-pricing-table' ), $coupon['percent'] ) ) . '</strong>'
				), array( 'strong' => array() ) );
				?>
			</h3>

			<p class="tpt-bf__text">
				<?php
				echo esc_html( sprintf(
					/* translators: %s: the last day of the offer */
					__( 'Role and customer prices, cart rules, upsells and the rest of the premium version, with the code below until %s.', 'tier-pricing-table' ),
					$lastDay
				) );
				?>
			</p>

			<p class="tpt-bf__coupon">
				<span><?php esc_html_e( 'Coupon code', 'tier-pricing-table' ); ?></span>
				<span class="tpt-bf__code"><?php echo esc_html( $coupon['code'] ); ?></span>
			</p>

			<div class="tpt-bf__actions">
				<a class="button button-primary" href="<?php echo esc_url( $upgradeUrl ); ?>"><?php esc_html_e( 'Get the offer', 'tier-pricing-table' ); ?></a>
				<a href="<?php echo esc_url( $notification->getCloseURL() ); ?>"><?php esc_html_e( 'No thanks', 'tier-pricing-table' ); ?></a>
			</div>
		</div>

		<div class="tpt-bf__features">
			<p class="tpt-bf__features-title"><?php esc_html_e( 'What premium adds', 'tier-pricing-table' ); ?></p>
			<ul class="tpt-bf__list">
				<?php foreach ( $features as $feature ) : ?>
					<li>
						<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="10" fill="currentColor" opacity=".14"/><path d="M6 10.4l2.6 2.6L14 7.6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<?php echo esc_html( $feature ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="tpt-bf__help">
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
