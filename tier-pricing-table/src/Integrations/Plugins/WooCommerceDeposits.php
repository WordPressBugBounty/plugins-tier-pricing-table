<?php namespace TierPricingTable\Integrations\Plugins;

class WooCommerceDeposits extends PluginIntegrationAbstract {
	
	public function getTitle(): string {
		return 'WooCommerce Deposits (by WooCommerce)';
	}
	
	public function getDescription(): string {
		return __( 'Calculate deposit amounts and remaining balances correctly based on tiered pricing rules.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'woocommerce-deposits';
	}
	
	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/woocommerce-deposits/';
	}
	
	public function getIconURL(): string {
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
	
	public function getIntegrationCategory(): string {
		return 'custom_product_types';
	}
}
