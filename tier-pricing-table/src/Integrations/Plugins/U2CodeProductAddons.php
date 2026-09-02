<?php namespace TierPricingTable\Integrations\Plugins;

/**
 * U2Code Product Addons — product options / extra fields with per-option pricing.
 *
 * The price handling lives on the Simple Product Addons side (its own Tiered Pricing Table
 * integration folds addon prices into the tier price via `tiered_pricing_table/cart/product_cart_price`
 * and bases percentage options on the tier price actually charged). This class registers the pairing
 * on the Tiered Pricing side: the integrations screen entry, and a bridge so that switching the
 * integration off here also stops the addons-side integration — one toggle governs the pairing.
 */
class U2CodeProductAddons extends PluginIntegrationAbstract {


	public function __construct() {
		parent::__construct();

		// One toggle governs the whole pairing: when the integration is switched off on the
		// Tiered Pricing side, unregister the addons-side integration as well. The plugin ships
		// in two distributions with different namespaces (SimpleProductAddons — free build,
		// U2Code\ProductAddons — premium); instanceof against a class from an inactive plugin
		// is safe, it simply never matches.
		if ( ! $this->isEnabled() ) {
			add_filter( 'simple_product_addons/integrations', function ( $integrations ) {
				return array_filter( (array) $integrations, function ( $integration ) {
					return ! ( $integration instanceof \SimpleProductAddons\Integrations\Plugins\TieredPricingTable )
						&& ! ( $integration instanceof \U2Code\ProductAddons\Integrations\Plugins\TieredPricingTable );
				} );
			} );
		}
	}

	public function getTitle(): string {
		return 'U2Code Product Addons';
	}

	public function getDescription(): string {
		return __( 'Custom product options with per-option pricing, live totals and conditional logic. Deep integration: addon prices follow the tier price.',
			'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'u2code-product-addons';
	}

	public function isOfficial(): bool {
		return true;
	}

	public function getAuthorURL(): string {
		return 'https://product-addons.com/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=integrations-page';
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/u2code-product-addons-icon.svg' );
	}

	public function getMetaPills(): array {
		return array(
			__( '11+ field types', 'tier-pricing-table' ),
			__( 'Per-option pricing', 'tier-pricing-table' ),
			__( 'Conditional logic', 'tier-pricing-table' ),
			__( 'Live totals', 'tier-pricing-table' ),
		);
	}

	public function getActionLinks(): array {
		return array(
			array(
				'url'    => 'https://wordpress.org/plugins/u2code-product-addons-for-woocommerce/',
				'label'  => __( 'Free on WordPress.org', 'tier-pricing-table' ),
				'target' => '_blank',
			),
			array(
				'url'    => 'https://product-addons.com/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=integrations-page',
				'label'  => __( 'Learn more', 'tier-pricing-table' ),
				'target' => '_blank',
			),
		);
	}

	public function run() {
		/**
		 * The pairing is registered. Price handling lives on the Simple Product Addons side —
		 * future Tiered Pricing–side glue for this integration belongs here.
		 *
		 * @param U2CodeProductAddons $this
		 */
		do_action( 'tiered_pricing_table/integration/simple_product_addons/run', $this );
	}

	public function getIntegrationCategory(): string {
		return 'product_addons';
	}


}
