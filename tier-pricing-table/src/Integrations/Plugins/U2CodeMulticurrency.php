<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\Admin\PluginInstaller\PluginInstaller;

/**
 * U2Code Product Multicurrency for WooCommerce — automatic exchange rates, geolocation,
 * per-currency price rules and a storefront switcher.
 *
 * Like the U2Code Product Addons pairing, price-conversion glue belongs on the multicurrency
 * plugin's side; this class registers the pairing on the Tiered Pricing side and gives future
 * TPT-side glue a place to live.
 */
class U2CodeMulticurrency extends PluginIntegrationAbstract {


	public function getTitle(): string {
		return 'U2Code Multicurrency';
	}

	public function getDescription(): string {
		return PluginInstaller::getText( $this->getPromotedPluginSlug() );
	}

	public function getSlug(): string {
		return 'u2code-multicurrency';
	}

	public function isOfficial(): bool {
		return true;
	}

	public function getPromotedPluginSlug(): string {
		return 'u2code-product-multicurrency-for-woocommerce';
	}

	public function getAuthorURL(): string {
		return 'https://u2code.com/plugins/multicurrency-for-woocommerce/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=integrations-page';
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/u2code-multicurrency-icon.png' );
	}

	public function run() {
		/**
		 * The pairing is registered. Conversion glue ships with the multicurrency plugin —
		 * future Tiered Pricing–side glue for this integration belongs here.
		 *
		 * @param U2CodeMulticurrency $this
		 */
		do_action( 'tiered_pricing_table/integration/u2code_multicurrency/run', $this );
	}

	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}


}
