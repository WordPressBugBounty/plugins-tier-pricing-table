<?php namespace TierPricingTable\Integrations\Plugins;

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
		return __( 'Sell in multiple currencies — automatic exchange rates, geolocation detection, per-currency price rules and a customizable switcher.',
			'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'u2code-multicurrency';
	}

	public function isOfficial(): bool {
		return true;
	}

	public function getMetaPills(): array {
		return array(
			__( 'Automatic exchange rates', 'tier-pricing-table' ),
			__( 'Geolocation', 'tier-pricing-table' ),
			__( 'Per-currency price rules', 'tier-pricing-table' ),
			__( 'Multi-currency checkout', 'tier-pricing-table' ),
		);
	}

	public function getAuthorURL(): string {
		return 'https://u2code.com/plugins/multicurrency-for-woocommerce/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=integrations-page';
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/u2code-multicurrency-icon.png' );
	}

	public function getActionLinks(): array {
		return array(
			array(
				'url'    => 'https://wordpress.org/plugins/u2code-product-multicurrency-for-woocommerce/',
				'label'  => __( 'Free on WordPress.org', 'tier-pricing-table' ),
				'target' => '_blank',
			),
			array(
				'url'    => 'https://u2code.com/plugins/multicurrency-for-woocommerce/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=integrations-page',
				'label'  => __( 'Learn more', 'tier-pricing-table' ),
				'target' => '_blank',
			),
		);
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
