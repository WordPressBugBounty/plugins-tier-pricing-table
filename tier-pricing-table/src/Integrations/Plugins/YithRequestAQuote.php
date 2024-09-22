<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;

class YithRequestAQuote extends PluginIntegrationAbstract {

	public function run() {
		add_action( 'ywraq_quote_adjust_price', function ( $raq, \WC_Product $product ) {
			$quantity = $raq['quantity'] ? $raq['quantity'] : 1;

			$pricingRule = PriceManager::getPricingRule( $product->get_id() );

			$newPrice = $pricingRule->getTierPrice( $quantity, false, 'cart', false );

			if ( $newPrice ) {
				$product->set_price( $newPrice );
			}
		}, 10, 2 );
		
		add_filter('woocommerce_cart_product_subtotal', function ( $product_subtotal, $product, $quantity ){
			if (class_exists('YITH_Request_Quote')) {
				
				$pricingRule = PriceManager::getPricingRule( $product->get_id() );
				
				$newPrice = $pricingRule->getTierPrice( $quantity, false, 'cart', false );

				if ( $newPrice ) {
					$product_subtotal = wc_price( $newPrice * $quantity );
				}
			}
			
			return $product_subtotal;
		}, -99999, 3);
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/yith-raq-icon.jpeg' );
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/yith-woocommerce-request-a-quote/';
	}

	public function getTitle(): string {
		return __( 'YITH Request a Quote', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Make tiered pricing properly work with request a quote form.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'yith-request-a-quote';
	}

	public function getIntegrationCategory(): string {
		return 'other';
	}
}
