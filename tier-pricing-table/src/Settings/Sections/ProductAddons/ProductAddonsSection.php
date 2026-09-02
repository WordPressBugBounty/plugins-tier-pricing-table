<?php namespace TierPricingTable\Settings\Sections\ProductAddons;

use TierPricingTable\Settings\Sections\PluginPairingSectionAbstract;

/**
 * "Product Addons" pairing section — see PluginPairingSectionAbstract for the shared behaviour.
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
		// Free build (SimpleProductAddons) or premium build (U2Code\ProductAddons)
		return class_exists( '\\SimpleProductAddons\\Plugin' ) || class_exists( '\\U2Code\\ProductAddons\\Plugin' );
	}

	public function getOwnPluginName(): string {
		return 'U2Code Product Addons';
	}

	public function getOwnPluginIconAsset(): string {
		return 'admin/integrations/u2code-product-addons-icon.svg';
	}

	public function getOwnPluginBannerAsset(): string {
		return 'admin/integrations/u2code-product-addons-banner.png';
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
		return __( 'Product addons — options, extra fields, services like engraving or gift wrap — work together with tiered pricing: addon prices are added on top of the tiered price in the cart and totals.', 'tier-pricing-table' );
	}

	public function getPromoText(): string {
		return __( 'U2Code Product Addons is built by the makers of Tiered Pricing Table — a native pairing: addon prices follow the tier price, percentage options are based on the actual tier price, and one support team for both plugins.', 'tier-pricing-table' );
	}

	public function getPairedBannerText(): string {
		return '<strong>' . esc_html__( 'U2Code Product Addons is active — the native pairing.', 'tier-pricing-table' ) . '</strong> '
			. esc_html__( 'Addon prices follow the tier price on the product page, in the live totals and in the cart.', 'tier-pricing-table' );
	}

	public function getIntegrationEnabledText(): string {
		return __( 'The Tiered Pricing integration for it is enabled — addon prices are included in tiered price calculations in the cart.', 'tier-pricing-table' );
	}

	public function getIntegrationDisabledText(): string {
		return __( 'The Tiered Pricing integration for it is disabled — addon prices may be lost when tiered pricing applies.', 'tier-pricing-table' );
	}

	public function getConflictText( string $competitorNames ): string {
		return '<strong>' . esc_html__( 'Multiple product addons plugins are active.', 'tier-pricing-table' ) . '</strong> '
			. sprintf(
				/* translators: %s: competitor plugin name(s) */
				esc_html__( 'U2Code Product Addons and %s are both adding fields to your products, which usually duplicates options on the product page. Consider consolidating on one — we recommend U2Code Product Addons for the native pairing with tiered pricing.', 'tier-pricing-table' ),
				esc_html( $competitorNames )
			);
	}

	public function getConflictBannerClass(): string {
		return 'tpt-pairing__banner--warning';
	}
}
