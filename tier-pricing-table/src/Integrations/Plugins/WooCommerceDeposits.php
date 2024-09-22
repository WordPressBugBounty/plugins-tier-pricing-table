<?php namespace TierPricingTable\Integrations\Plugins;

class WooCommerceDeposits extends PluginIntegrationAbstract {

	public function getTitle() {
		return __( 'WooCommerce Deposits (by WooCommerce)', 'tier-pricing-table' );
	}

	public function getDescription() {
		return __( 'Make tiered pricing properly work with deposit purchases.', 'tier-pricing-table' );
	}

	public function getSlug() {
		return 'woocommerce-deposits';
	}

	

	public function getAuthorURL() {
		return 'https://woocommerce.com/products/woocommerce-deposits/';
	}

	public function getIconURL() {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/woocommerce-develop.jpeg' );
	}

	public function run() {
		add_filter( 'tiered_pricing_table/cart/product_cart_price', function ( $new_price, $cart_item, $key ) {

			if ( $new_price ) {
				// WooCommerce Deposit
				$cart = wc()->cart;

				if ( isset( $cart->cart_contents[ $key ]['full_amount'] ) ) {

					$depositPercentage = 1 / ( $cart->cart_contents[ $key ]['full_amount'] / $cart->cart_contents[ $key ]['deposit_amount'] );

					$cart->cart_contents[ $key ]['full_amount']    = $new_price;
					$cart->cart_contents[ $key ]['deposit_amount'] = $cart->cart_contents[ $key ]['full_amount'] * $depositPercentage;
				}
			}

			return $new_price;

		}, 10, 3 );
	}

	public function getIntegrationCategory() {
		return 'custom_product_types';
	}
}
