<?php namespace TierPricingTable\Services\API\ProductFields;

use TierPricingTable\PriceManager;
use WC_Product;

class AmountTieredPricing extends ProductField {
	
	public function getFieldSlug(): string {
		return 'tiered_pricing_amount_rules';
	}
	
	public function sanitizeValue( $value ): array {
		$rules = array();
		$value = is_array( $value ) ? $value : array();
		
		foreach ( $value as $key => $val ) {
			$rules[ (int) $key ] = (float) $val;
		}
		
		return $rules;
	}
	
	public function getValue( array $product ): array {
		return PriceManager::getAmountPriceRules( (int) $product['id'], 'edit' );
	}
	
	public function updateValue( $value, WC_Product $product ) {
		update_post_meta( $product->get_id(), '_amount_price_rules', $this->sanitizeValue( $value ) );
	}
	
	public function getType(): string {
		return 'object';
	}
	
	public function getDescription(): string {
		return 'Tiered pricing amount-off rules. Key is a quantity and value is the amount taken off the price of each unit. Minimum quantity is 2';
	}
}
