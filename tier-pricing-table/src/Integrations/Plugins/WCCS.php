<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;

class WCCS extends PluginIntegrationAbstract {

	public function run() {
		// Amount-off tiers are stored in the base currency: convert the amounts, the product price is converted by WCCS.
		add_filter( 'tiered_pricing_table/price/pricing_rule', function ( PricingRule $pricingRule ) {
			global $WCCS;
			
			$product = $pricingRule->isAmount() && $WCCS ? wc_get_product( $pricingRule->getProductId() ) : null;
			
			if ( $product ) {
				$pricingRule->setRules( array_map( function ( $amount ) use ( $WCCS, $product ) {
					return $WCCS->wccs_custom_price( $amount, $product );
				}, $pricingRule->getRules() ) );
			}
			
			return $pricingRule;
		}, 9999 );
		

		add_filter( 'tiered_pricing_table/price/price_by_rules',
			function ( $product_price, $quantity, $product_id, $context, $place, PricingRule $pricingRule ) {

				global $WCCS;

				// Percentage tiers are derived from the product's 'view' price, which WCCS has already converted.
				// Converting again here would double-convert the price.
				if ( $pricingRule->isPercentage() || $pricingRule->isAmount() ) {
					return $product_price;
				}

				if ( $WCCS && $product_price ) {

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

		add_filter( 'tiered_pricing_table/cart/recalculate_cart_item_subtotal', function ( $state ) {
			global $WCCS;

			if ( $WCCS ) {
				return false;
			}

			return $state;
		} );
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
