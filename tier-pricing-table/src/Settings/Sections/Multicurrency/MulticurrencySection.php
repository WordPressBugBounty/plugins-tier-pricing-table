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

	public function getOwnPluginBannerAsset(): string {
		return 'admin/integrations/u2code-multicurrency-banner.png';
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

	public function getExamples(): array {
		$store   = get_woocommerce_currency();
		$foreign = 'EUR' === $store ? array( 'symbol' => '$', 'rate' => 1.09, 'name' => __( 'dollars', 'tier-pricing-table' ) ) : array( 'symbol' => '€', 'rate' => 0.92, 'name' => __( 'euros', 'tier-pricing-table' ) );
		$home    = function ( $amount ) {
			return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
		};
		$away    = function ( $amount ) use ( $foreign ) {
			return $foreign['symbol'] . number_format( $amount * $foreign['rate'], 2 );
		};

		return array(
			array(
				'title' => __( 'Tier prices follow the currency', 'tier-pricing-table' ),
				/* translators: 1: tier price, 2: next tier price, 3: converted price, 4: converted next price, 5: currency name */
				'text'  => sprintf( __( 'Tiers of %1$s and %2$s show as %3$s and %4$s for a shopper paying in %5$s, at the store\'s rate.', 'tier-pricing-table' ), $home( 20 ), $home( 18 ), $away( 20 ), $away( 18 ), $foreign['name'] ),
			),
			array(
				'title' => __( 'Fixed prices per currency', 'tier-pricing-table' ),
				/* translators: 1: price, 2: currency name */
				'text'  => sprintf( __( 'Set the 10+ tier to a round %1$s for shoppers paying in %2$s instead of the converted amount, the same way for any currency.', 'tier-pricing-table' ), $foreign['symbol'] . '15.00', $foreign['name'] ),
			),
			array(
				'title' => __( 'Role and catalog prices convert too', 'tier-pricing-table' ),
				/* translators: 1: wholesale price, 2: converted price */
				'text'  => sprintf( __( 'A wholesale price of %1$s becomes %2$s, and the "From" price in the shop grid follows.', 'tier-pricing-table' ), $home( 12 ), $away( 12 ) ),
			),
			array(
				'title' => __( 'Checkout in the shopper\'s currency', 'tier-pricing-table' ),
				/* translators: %s: currency name */
				'text'  => sprintf( __( 'Cart totals, discounts and the "You save" badge are shown in %s, and the order is placed in that currency.', 'tier-pricing-table' ), $foreign['name'] ),
			),
		);
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
		return __( 'When a currency switcher is active, tiered prices follow it: tier rules, role-based prices, catalog prices and cart discounts are converted to the visitor\'s currency.', 'tier-pricing-table' );
	}

	public function getPromoText(): string {
		return __( 'U2Code Multicurrency is built by the makers of Tiered Pricing Table — a native pairing instead of an adapter: tier and role prices convert natively, per-currency price rules, and one support team for both plugins.', 'tier-pricing-table' );
	}

	public function getPairedBannerText(): string {
		return '<strong>' . esc_html__( 'U2Code Multicurrency is active — the native pairing.', 'tier-pricing-table' ) . '</strong> '
			. esc_html__( 'Tiered, role-based and catalog prices convert to the visitor\'s currency automatically.', 'tier-pricing-table' );
	}

	public function getIntegrationEnabledText(): string {
		return __( 'The Tiered Pricing integration for it is enabled — tiered prices are converted at the current exchange rate.', 'tier-pricing-table' );
	}

	public function getIntegrationDisabledText(): string {
		return __( 'The Tiered Pricing integration for it is disabled — tiered prices may not convert correctly.', 'tier-pricing-table' );
	}

	public function getConflictText( string $competitorNames ): string {
		return '<strong>' . esc_html__( 'Two currency systems are active.', 'tier-pricing-table' ) . '</strong> '
			. sprintf(
				/* translators: %s: competitor plugin name(s) */
				esc_html__( 'U2Code Multicurrency and %s will both try to convert prices. Deactivate one of them — we recommend keeping U2Code Multicurrency for the native pairing with tiered pricing.', 'tier-pricing-table' ),
				esc_html( $competitorNames )
			);
	}
}
