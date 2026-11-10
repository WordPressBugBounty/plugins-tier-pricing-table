<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;

class WOOCS extends PluginIntegrationAbstract {
	
	public function run() {
		// Amount-off tiers are stored in the base currency: convert the amounts, the product price is converted by FOX.
		add_filter( 'tiered_pricing_table/price/pricing_rule', function ( PricingRule $pricingRule ) {
			global $WOOCS_STARTER;
			
			$product = $pricingRule->isAmount() && $WOOCS_STARTER ? wc_get_product( $pricingRule->getProductId() ) : null;
			
			if ( $product ) {
				$pricingRule->setRules( array_map( function ( $amount ) use ( $WOOCS_STARTER, $product ) {
					return (float) $WOOCS_STARTER->get_actual_obj()->raw_woocommerce_price( $amount, $product );
				}, $pricingRule->getRules() ) );
			}
			
			return $pricingRule;
		}, 9999 );
		
		add_filter( 'tiered_pricing_table/price/price_by_rules',
			function ( $productPrice, $quantity, $productId, $context, $place, PricingRule $pricingRule ) {
			
				global $WOOCS_STARTER;
				
				if ( $pricingRule->isPercentage() || $pricingRule->isAmount() ) {
					return $productPrice;
				}
				
				if ( $WOOCS_STARTER && $productPrice ) {
					if ( 'view' === $context ) {
						return (float) $WOOCS_STARTER->get_actual_obj()->raw_woocommerce_price( $productPrice,
							wc_get_product( $productId ) );
					}
				}
				
				return $productPrice;
				
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
		return  'WooCommerce Currency Switcher (FOX)';
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/fox-icon.png' );
	}
	
	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/woocommerce-currency-switcher/';
	}
	
	public function getDescription(): string {
		return __( 'Convert and display tiered pricing correctly when using the FOX WooCommerce Currency Switcher.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'woocs';
	}
	
	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}
	
}
