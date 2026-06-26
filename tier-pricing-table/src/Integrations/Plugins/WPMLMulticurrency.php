<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PricingRule;
use woocommerce_wpml;

class WPMLMulticurrency extends PluginIntegrationAbstract {
	
	public function run() {
		
		add_filter( 'tiered_pricing_table/price/price_by_rules', function (
			$productPrice,
			$quantity,
			$productId,
			$context,
			$place,
			PricingRule $pricingRule
		) {
			
			/**
			 * Clarifying type
			 *
			 * @var woocommerce_wpml $woocommerce_wpml
			 */ global $woocommerce_wpml;
			
			if ( $pricingRule->isPercentage() || ! $productPrice || ! $woocommerce_wpml ) {
				return $productPrice;
			}
			
			if ( ! $woocommerce_wpml->multi_currency || ! method_exists( $woocommerce_wpml->multi_currency,
					'get_client_currency' ) ) {
				return $productPrice;
			}
			
			$currentCurrency = $woocommerce_wpml->multi_currency->get_client_currency();
			
			if ( wcml_get_woocommerce_currency_option() !== $currentCurrency ) {
				
				return $woocommerce_wpml->multi_currency->prices->convert_price_amount( $productPrice,
					$currentCurrency );
			}
			
			return $productPrice;
		}, 10, 10 );
		
		add_filter( 'tiered_pricing_table/services/regular_pricing/price', function ( $newPrice ) {
			
			if ( is_null( $newPrice ) ) {
				return $newPrice;
			}
			
			global $woocommerce_wpml;
			
			if ( ! $woocommerce_wpml || ! $woocommerce_wpml->multi_currency || ! method_exists( $woocommerce_wpml->multi_currency,
					'get_client_currency' ) ) {
				return $newPrice;
			}
			
			$currentCurrency = $woocommerce_wpml->multi_currency->get_client_currency();
			
			if ( wcml_get_woocommerce_currency_option() !== $currentCurrency ) {
				return $woocommerce_wpml->multi_currency->prices->convert_price_amount( $newPrice, $currentCurrency );
			}
			
			return $newPrice;
		}, 10, 4 );
	}
	
	public function getTitle(): string {
		return  'WPML Multicurrency';
	}
	
	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/wpml-multicurrency-icon.png' );
	}
	
	public function getAuthorURL(): string {
		return 'https://wpml.org/documentation/related-projects/woocommerce-multilingual/';
	}
	
	public function getDescription(): string {
		return __( 'Convert and display tiered pricing correctly when using WPML Multicurrency.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'wpml_multicurrency';
	}
	
	public function getIntegrationCategory(): string {
		return 'multicurrency';
	}
}
