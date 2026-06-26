<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;

class WCCS extends PluginIntegrationAbstract {
	
	public function run() {
		
		add_filter( 'tiered_pricing_table/price/price_by_rules',
			function ( $product_price, $quantity, $product_id, $context ) {
				
				global $WCCS;
				
				if ( $WCCS ) {
					
					$product = wc_get_product( $product_id );
					
					if ( 'view' === $context && $product ) {
						return $WCCS->wccs_custom_price( $product_price, $product );
					}
				}
				
				return $product_price;
				
			}, 10, 10 );
		
		add_filter( 'tiered_pricing_table/cart/product_cart_price',
			function ( $price, $cartItem, $cartItemKey, $totalQuantity ) {
				
				global $WCCS;
				
				if ( ! $WCCS ) {
					return $price;
				}
				
				if ( $price ) {
					return PriceManager::getPriceByRules( $totalQuantity, $cartItem['data']->get_id(), 'edit', 'cart',
						false );
				}
				
				return $price;
			}, 10, 4 );
	}
	
	public function getTitle(): string {
		return 'WooCommerce Currency Switcher by WP Experts (WCCS)';
	}
	
	public function getDescription(): string {
		return __( 'Convert and display tiered pricing correctly when using the WooCommerce Currency Switcher by WP Experts.', 'tier-pricing-table' );
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/wccs-icon.png' );
	}
	
	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/currency-switcher-for-woocommerce/';
	}
	
	public function getSlug(): string {
		return 'wccs';
	}
	
	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}
}
