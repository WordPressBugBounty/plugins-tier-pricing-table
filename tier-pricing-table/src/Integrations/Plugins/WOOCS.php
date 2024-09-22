<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;

class WOOCS extends PluginIntegrationAbstract {
	
	public function run() {
		add_filter( 'tiered_pricing_table/price/price_by_rules',
			function ( $product_price, $quantity, $product_id, $context ) {
				global $WOOCS_STARTER;
				
				if ( $WOOCS_STARTER  && $product_price ) {
					if ( 'view' === $context ) {
						return (float) $WOOCS_STARTER->get_actual_obj()->raw_woocommerce_price( $product_price,
							wc_get_product( $product_id ) );
					}
				}
				
				return $product_price;
				
			}, 10, 10 );
		
		add_filter( 'tiered_pricing_table/cart/product_cart_price',
			function ( $price, $cartItem, $cartItemKey, $totalQuantity ) {
				global $WOOCS_STARTER;
				
				if ( $WOOCS_STARTER && $price ) {
					return PriceManager::getPriceByRules( $totalQuantity, $cartItem['data']->get_id(), 'edit', 'cart',
						false );
				}
				
				return $price;
			}, 10, 4 );
		
		add_filter( 'tiered_pricing_table/cart/recalculate_cart_item_subtotal', function ( $state ) {
			global $WOOCS_STARTER;
			
			if ( $WOOCS_STARTER ) {
				return false;
			}
			
			return $state;
		} );
	}
	
	public function getTitle(): string {
		return __( 'WooCommerce Currency Switcher (FOX)', 'tier-pricing-table' );
	}
	
	public function getIconURL() {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/fox-icon.png' );
	}
	
	public function getAuthorURL() {
		return 'https://wordpress.org/plugins/woocommerce-currency-switcher/';
	}
	
	public function getDescription() {
		return __( 'Make the tiered pricing properly work with multiple currencies.', 'tier-pricing-table' );
	}
	
	public function getSlug() {
		return 'woocs';
	}
	
	public function getIntegrationCategory() {
		return 'multicurrency';
	}
	
	protected function isActiveByDefault() {
		return false;
	}
}
