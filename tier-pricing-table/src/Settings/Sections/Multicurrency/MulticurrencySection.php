<?php namespace TierPricingTable\Settings\Sections\Multicurrency;

use TierPricingTable\Settings\Sections\PluginPairingSectionAbstract;

/**
 * "Multicurrency" pairing section — see PluginPairingSectionAbstract for the shared behaviour.
 */
class MulticurrencySection extends PluginPairingSectionAbstract {

	public function getName(): string {
		return __( 'Multicurrency', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'multicurrency';
	}

	public function getSupportedPlugins(): array {
		return array(
			'woocs'                    => array(
				'name'   => 'FOX — Currency Switcher (WOOCS)',
				'active' => isset( $GLOBALS['WOOCS_STARTER'] ) || class_exists( '\\WOOCS' ),
			),
			'curcy'                    => array(
				'name'   => 'CURCY — Multi Currency for WooCommerce',
				'active' => function_exists( 'wmc_get_price' ),
			),
			'aelia-multicurrency'      => array(
				'name'   => 'Aelia Currency Switcher',
				'active' => has_action( 'wc_aelia_cs_convert' ) || class_exists( '\\Aelia\\WC\\CurrencySwitcher\\WC_Aelia_CurrencySwitcher' ),
			),
			'wpml_multicurrency'       => array(
				'name'   => 'WPML Multicurrency (WCML)',
				'active' => isset( $GLOBALS['woocommerce_wpml'] ) && class_exists( '\\WCML_Multi_Currency' ),
			),
			'woopayments-multicurrency' => array(
				'name'   => 'WooPayments Multi-Currency',
				'active' => class_exists( '\\WCPay\\MultiCurrency\\MultiCurrency' ),
			),
			'yith-multicurrency'       => array(
				'name'   => 'YITH Multi Currency Switcher',
				'active' => function_exists( 'yith_wcmcs_convert_price' ),
			),
			'wccs'                     => array(
				'name'   => 'WooCommerce Currency Switcher (WP Experts)',
				'active' => isset( $GLOBALS['WCCS'] ),
			),
		);
	}

	public function isOwnPluginActive(): bool {
		return class_exists( '\\U2Code\\Multicurrency\\Plugin' );
	}

	public function getOwnPluginName(): string {
		return 'U2Code Multicurrency';
	}

	public function getOwnPluginIconAsset(): string {
		return 'admin/integrations/u2code-multicurrency-icon.png';
	}

	public function getOwnPluginPills(): array {
		return array(
			__( 'Automatic exchange rates', 'tier-pricing-table' ),
			__( 'Geolocation', 'tier-pricing-table' ),
			__( 'Per-currency price rules', 'tier-pricing-table' ),
			__( 'Multi-currency checkout', 'tier-pricing-table' ),
		);
	}

	public function getOwnPluginWporgURL(): string {
		return 'https://wordpress.org/plugins/u2code-product-multicurrency-for-woocommerce/';
	}

	public function getOwnPluginWporgSlug(): string {
		return 'u2code-product-multicurrency-for-woocommerce';
	}

	public function getOwnPluginHeaderNames(): array {
		return array( 'U2Code Product Multicurrency for WooCommerce', 'U2Code Multicurrency for WooCommerce', 'U2Code Multicurrency' );
	}

	public function getOwnPluginSiteURL(): string {
		return 'https://u2code.com/plugins/multicurrency-for-woocommerce/';
	}

	public function getUtmCampaignBase(): string {
		return 'multicurrency';
	}

	public function getIntroHeading(): string {
		return __( 'Tiered pricing works with multi-currency stores', 'tier-pricing-table' );
	}

	public function getIntroText(): string {
		return __( 'Tier, role and catalog prices and cart discounts follow the shopper\'s currency.', 'tier-pricing-table' );
	}

	public function getPromoText(): string {
		return __( 'Made by the Tiered Pricing Table team: tier and role prices convert natively, and you can set prices per currency.', 'tier-pricing-table' );
	}

	public function getPairedBannerText(): string {
		return '<strong>' . esc_html__( 'U2Code Multicurrency is active.', 'tier-pricing-table' ) . '</strong> '
			. esc_html__( 'Tier, role and catalog prices convert to the shopper\'s currency automatically.', 'tier-pricing-table' );
	}

	public function getIntegrationEnabledText(): string {
		return __( 'The tiered pricing integration is on, so tiered prices convert at the current exchange rate.', 'tier-pricing-table' );
	}

	public function getIntegrationDisabledText(): string {
		return __( 'The tiered pricing integration is off, so tiered prices may not convert.', 'tier-pricing-table' );
	}

	public function getConflictText( string $competitorNames ): string {
		return '<strong>' . esc_html__( 'Two currency systems are active.', 'tier-pricing-table' ) . '</strong> '
			. sprintf(
				/* translators: %s: competitor plugin name(s) */
				esc_html__( 'U2Code Multicurrency and %s both convert prices. Deactivate one; U2Code Multicurrency pairs natively with tiered pricing.', 'tier-pricing-table' ),
				esc_html( $competitorNames )
			);
	}

	public function getSwitchNote( string $competitorSlug ): string {
		$plugins = $this->getSupportedPlugins();

		/* translators: %s: the plugin in use now */
		return sprintf( __( 'Once it is active, deactivate %s so prices are converted only once.', 'tier-pricing-table' ), $plugins[ $competitorSlug ]['name'] ?? $competitorSlug );
	}
}
