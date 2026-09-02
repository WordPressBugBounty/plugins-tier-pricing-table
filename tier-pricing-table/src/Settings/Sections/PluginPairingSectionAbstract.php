<?php namespace TierPricingTable\Settings\Sections;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Settings;

/**
 * Base for "pairing" settings sections (Multicurrency, Product Addons, …): informational tabs that
 * show which third-party plugin of the kind is detected, the state of its Tiered Pricing
 * integration (status here — the toggle stays on the Integrations page), and recommend the
 * U2Code plugin of the same kind. Four states: none detected / competitor detected /
 * U2Code plugin active / both active (conflict).
 */
abstract class PluginPairingSectionAbstract extends SectionAbstract {

	use ServiceContainerTrait;

	/**
	 * @return array<string, array{name: string, active: bool}> Integration slug => plugin.
	 */
	abstract public function getSupportedPlugins(): array;

	abstract public function isOwnPluginActive(): bool;

	abstract public function getOwnPluginName(): string;

	/**
	 * Asset path of the plugin icon, relative to the assets directory.
	 */
	abstract public function getOwnPluginIconAsset(): string;

	/**
	 * Asset path of the plugin's wp.org banner (772×250), relative to the assets directory.
	 */
	abstract public function getOwnPluginBannerAsset(): string;

	/**
	 * @return array<int, string>
	 */
	abstract public function getOwnPluginPills(): array;

	abstract public function getOwnPluginWporgURL(): string;

	abstract public function getOwnPluginSiteURL(): string;

	/**
	 * utm_campaign prefix, e.g. 'multicurrency' → 'multicurrency-tab' / 'multicurrency-switch'.
	 */
	abstract public function getUtmCampaignBase(): string;

	abstract public function getIntroHeading(): string;

	abstract public function getIntroText(): string;

	abstract public function getPromoText(): string;

	abstract public function getPairedBannerText(): string;

	abstract public function getIntegrationEnabledText(): string;

	abstract public function getIntegrationDisabledText(): string;

	/**
	 * @param  string  $competitorNames  Human-readable list of the detected plugins.
	 */
	abstract public function getConflictText( string $competitorNames ): string;

	public function getConflictBannerClass(): string {
		return 'tpt-pairing__banner--danger';
	}

	public function getFieldType(): string {
		return 'tiered-pricing_' . $this->getSlug() . '-ui';
	}

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . $this->getFieldType(), array( $this, 'renderUI' ) );
	}

	public function getSettings(): array {

		// Pairing sections are informational — there is nothing to save.
		$GLOBALS['hide_save_button'] = true;

		return array(
			array(
				'type' => $this->getFieldType(),
			),
		);
	}

	/**
	 * @return array<string, array{name: string, active: bool}> Active competitor plugins only.
	 */
	public function getDetectedCompetitors(): array {
		return array_filter( $this->getSupportedPlugins(), function ( $plugin ) {
			return $plugin['active'];
		} );
	}

	protected function isIntegrationEnabled( string $slug ): bool {
		return get_option( Settings::SETTINGS_PREFIX . '_integration_' . $slug, 'yes' ) === 'yes';
	}

	protected function getIntegrationsPageURL(): string {
		return admin_url( 'admin.php?page=wc-settings&tab=' . Settings::SETTINGS_PAGE . '&section=integrations' );
	}

	public function renderUI() {

		$competitors = $this->getDetectedCompetitors();
		$ownActive   = $this->isOwnPluginActive();
		?>
		<div class="tpt-pairing">

			<?php if ( $ownActive && $competitors ) : ?>

				<div class="tpt-pairing__banner <?php echo esc_attr( $this->getConflictBannerClass() ); ?>">
					<?php echo wp_kses_post( $this->getConflictText( implode( ', ', wp_list_pluck( $competitors, 'name' ) ) ) ); ?>
				</div>

			<?php elseif ( $ownActive ) : ?>

				<div class="tpt-pairing__banner tpt-pairing__banner--ok">
					<?php echo wp_kses_post( $this->getPairedBannerText() ); ?>
				</div>

				<p>
					<a href="<?php echo esc_url( $this->getIntegrationsPageURL() ); ?>">
						<?php esc_html_e( 'Manage integrations', 'tier-pricing-table' ); ?> &rarr;
					</a>
				</p>

			<?php elseif ( $competitors ) : ?>

				<?php foreach ( $competitors as $slug => $competitor ) : ?>
					<?php $enabled = $this->isIntegrationEnabled( $slug ); ?>
					<div class="tpt-pairing__banner <?php echo $enabled ? 'tpt-pairing__banner--ok' : 'tpt-pairing__banner--warning'; ?>">
						<strong>
							<?php
							printf(
								/* translators: %s: detected plugin name */
								esc_html__( '%s detected.', 'tier-pricing-table' ),
								esc_html( $competitor['name'] )
							);
							?>
						</strong>

						<?php echo esc_html( $enabled ? $this->getIntegrationEnabledText() : $this->getIntegrationDisabledText() ); ?>

						<a href="<?php echo esc_url( $this->getIntegrationsPageURL() ); ?>">
							<?php esc_html_e( 'Manage integration', 'tier-pricing-table' ); ?> &rarr;
						</a>
					</div>
				<?php endforeach; ?>

				<?php $this->renderSupportedPlugins(); ?>

				<?php $this->renderPromo( array_key_first( $competitors ) ); ?>

			<?php else : ?>

				<div class="tpt-pairing__intro">
					<h3><?php echo esc_html( $this->getIntroHeading() ); ?></h3>
					<p><?php echo esc_html( $this->getIntroText() ); ?></p>
				</div>

				<?php $this->renderSupportedPlugins(); ?>

				<?php $this->renderPromo(); ?>

			<?php endif; ?>

		</div>
		<?php
	}

	protected function renderSupportedPlugins() {
		?>
		<div class="tpt-pairing__plugins">
			<span class="tpt-pairing__plugins-label"><?php esc_html_e( 'Works with', 'tier-pricing-table' ); ?></span>
			<?php foreach ( $this->getSupportedPlugins() as $plugin ) : ?>
				<span class="tpt-pairing__plugin <?php echo $plugin['active'] ? 'tpt-pairing__plugin--active' : ''; ?>">
					<i aria-hidden="true"></i>
					<?php echo esc_html( $plugin['name'] ); ?>
					<?php if ( $plugin['active'] ) : ?>
						<em><?php esc_html_e( 'active', 'tier-pricing-table' ); ?></em>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	protected function renderPromo( string $detectedCompetitor = '' ) {

		$utm = '?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=' . $this->getUtmCampaignBase()
			. ( $detectedCompetitor ? '-switch&utm_term=' . rawurlencode( $detectedCompetitor ) : '-tab' );

		$iconURL = $this->getContainer()->getFileManager()->locateAsset( $this->getOwnPluginIconAsset() );

		$title = $detectedCompetitor
			/* translators: %s: U2Code plugin name */
			? sprintf( __( 'Switch to %s', 'tier-pricing-table' ), $this->getOwnPluginName() )
			: $this->getOwnPluginName();
		?>
		<div class="tpt-pairing__promo tpt-pairing__promo--<?php echo esc_attr( $this->getSlug() ); ?>">
			<div class="tpt-pairing__promo-glow" aria-hidden="true"></div>

			<div class="tpt-pairing__promo-content">
				<div class="tpt-pairing__promo-head">
					<span class="tpt-pairing__promo-icon">
						<img src="<?php echo esc_url( $iconURL ); ?>" width="52" height="52" alt="">
					</span>
					<div class="tpt-pairing__promo-title">
						<h4><?php echo esc_html( $title ); ?></h4>
						<div class="tpt-pairing__promo-badges">
							<span class="tpt-integration-item__official-badge"><?php esc_html_e( 'By U2Code', 'tier-pricing-table' ); ?></span>
							<span class="tpt-pairing__promo-free"><?php esc_html_e( 'Free', 'tier-pricing-table' ); ?></span>
						</div>
					</div>
				</div>

				<p class="tpt-pairing__promo-text"><?php echo esc_html( $this->getPromoText() ); ?></p>

				<ul class="tpt-pairing__promo-features">
					<?php foreach ( $this->getOwnPluginPills() as $pill ) : ?>
						<li>
							<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="10" fill="currentColor" opacity=".14"/><path d="M6 10.4l2.6 2.6L14 7.6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
							<?php echo esc_html( $pill ); ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<div class="tpt-pairing__promo-actions">
					<a class="tpt-pairing__promo-cta" target="_blank" rel="noopener" href="<?php echo esc_url( $this->getOwnPluginWporgURL() ); ?>">
						<?php esc_html_e( 'Get it free on WordPress.org', 'tier-pricing-table' ); ?>
						<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><path d="M4 10h11M11 5l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
					<a class="tpt-pairing__promo-link" target="_blank" rel="noopener" href="<?php echo esc_url( $this->getOwnPluginSiteURL() . $utm ); ?>">
						<?php esc_html_e( 'Learn more', 'tier-pricing-table' ); ?>
					</a>
					<span class="tpt-pairing__promo-trust"><?php esc_html_e( 'Same team, one support channel for both plugins.', 'tier-pricing-table' ); ?></span>
				</div>
			</div>

			<div class="tpt-pairing__promo-banner" aria-hidden="true">
				<img src="<?php echo esc_url( $this->getContainer()->getFileManager()->locateAsset( $this->getOwnPluginBannerAsset() ) ); ?>" width="772" height="250" alt="">
			</div>
		</div>
		<?php
	}
}
