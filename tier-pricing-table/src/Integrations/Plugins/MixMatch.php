<?php namespace TierPricingTable\Integrations\Plugins;

class MixMatch extends PluginIntegrationAbstract {

	public function run() {
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', function ( $bool, $cart_item ) {

			if ( isset( $cart_item['mnm_container'] ) ) {
				return false;
			}

			return $bool;

		}, 10, 2 );
	}

	public function getIconURL() {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/mix-match-icon.png' );
	}

	public function getAuthorURL() {
		return 'https://woocommerce.com/products/woocommerce-mix-and-match-products/';
	}

	public function getTitle() {
		return __( 'Mix&Match for WooCommerce', 'tier-pricing-table' );
	}

	public function getDescription() {
		return __( 'Make tiered pricing properly work with this type of product.', 'tier-pricing-table' );
	}

	public function getSlug() {
		return 'mix-match-for-woocommerce';
	}

	public function getIntegrationCategory() {
		return 'custom_product_types';
	}
}
