<?php namespace TierPricingTable\Settings\Sections\ProductAddons;

use TierPricingTable\Settings\Sections\PluginPairingSectionAbstract;

/**
 * U2Code Product Addons as a pairing: its name, icon, WordPress.org slug and detection, used by the plugin
 * installer (welcome page). It is not a settings tab; only the Multicurrency pairing has one.
 */
class ProductAddonsSection extends PluginPairingSectionAbstract {

	public function getName(): string {
		return __( 'Product Addons', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'product-addons';
	}

	public function getSupportedPlugins(): array {
		return array(
			'product-add-ons'       => array(
				'name'   => 'Product Add-ons (by WooCommerce)',
				'active' => class_exists( '\\WC_Product_Addons' )
					|| ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce-product-addons/woocommerce-product-addons.php' ) ),
			),
			'wcpa'                  => array(
				'name'   => 'WooCommerce Custom Product Addons (WCPA) by Acowebs',
				'active' => class_exists( '\\WCPA' ) || defined( 'WCPA_VERSION' ) || class_exists( '\\Acowebs\\WCPA\\WCPA' ),
			),
			'wombat_product_addons' => array(
				'name'   => 'Advanced Product Fields (StudioWombat)',
				'active' => class_exists( '\\SW_WAPF\\Includes\\Classes\\Fields' ),
			),
		);
	}

	public function isOwnPluginActive(): bool {
		// Free build (SimpleProductAddons), premium build (U2Code\ProductAddons) or the WooCommerce.com build (Bespoke Product Options)
		return class_exists( '\\SimpleProductAddons\\Plugin' ) || class_exists( '\\U2Code\\ProductAddons\\Plugin' ) || class_exists( '\\BespokeProductOptions\\Plugin' );
	}

	public function getOwnPluginName(): string {
		return 'U2Code Product Addons';
	}

	public function getOwnPluginIconAsset(): string {
		return 'admin/integrations/u2code-product-addons-icon.svg';
	}

	public function getOwnPluginPills(): array {
		return array(
			__( '11+ field types', 'tier-pricing-table' ),
			__( 'Per-option pricing', 'tier-pricing-table' ),
			__( 'Conditional logic', 'tier-pricing-table' ),
			__( 'Live totals', 'tier-pricing-table' ),
		);
	}

	public function getOwnPluginWporgURL(): string {
		return 'https://wordpress.org/plugins/u2code-product-addons-for-woocommerce/';
	}

	public function getOwnPluginWporgSlug(): string {
		return 'u2code-product-addons-for-woocommerce';
	}

	public function getOwnPluginHeaderNames(): array {
		return array( 'U2Code Product Addons for WooCommerce', 'U2Code Product Addons', 'Bespoke Product Options' );
	}

	public function getOwnPluginSiteURL(): string {
		return 'https://product-addons.com/';
	}

	public function getUtmCampaignBase(): string {
		return 'product-addons';
	}

	public function getIntroHeading(): string {
		return __( 'Charge for product options alongside tiered pricing', 'tier-pricing-table' );
	}

	public function getIntroText(): string {
		return __( 'Option prices, such as engraving or gift wrap, are added on top of the tier price in the cart and totals.', 'tier-pricing-table' );
	}

	public function getPromoText(): string {
		return __( 'Made by the Tiered Pricing Table team: option prices follow the tier price, and percentage options use the actual tier price.', 'tier-pricing-table' );
	}

	public function getPairedBannerText(): string {
		return '<strong>' . esc_html__( 'U2Code Product Addons is active.', 'tier-pricing-table' ) . '</strong> '
			. esc_html__( 'Option prices follow the tier price on the product page, in live totals and in the cart.', 'tier-pricing-table' );
	}

	public function getIntegrationEnabledText(): string {
		return __( 'The tiered pricing integration is on, so option prices are added to tier prices in the cart.', 'tier-pricing-table' );
	}

	public function getIntegrationDisabledText(): string {
		return __( 'The tiered pricing integration is off, so option prices may be lost when tiered pricing applies.', 'tier-pricing-table' );
	}

	public function getConflictText( string $competitorNames ): string {
		return '<strong>' . esc_html__( 'Several product options plugins are active.', 'tier-pricing-table' ) . '</strong> '
			. sprintf(
				/* translators: %s: competitor plugin name(s) */
				esc_html__( 'U2Code Product Addons and %s both add fields to your products, which usually duplicates options on the product page. Keep one; U2Code Product Addons pairs natively with tiered pricing.', 'tier-pricing-table' ),
				esc_html( $competitorNames )
			);
	}

	public function getConflictBannerClass(): string {
		return 'tpt-pairing__banner--warning';
	}

	public function getSwitchNote( string $competitorSlug ): string {
		$plugins = $this->getSupportedPlugins();

		/* translators: %s: the plugin in use now */
		return sprintf( __( 'Once it is active, deactivate %s so options are not shown twice.', 'tier-pricing-table' ), $plugins[ $competitorSlug ]['name'] ?? $competitorSlug );
	}
}
