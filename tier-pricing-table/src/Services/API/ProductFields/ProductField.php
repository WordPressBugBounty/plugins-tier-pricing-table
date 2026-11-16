<?php namespace TierPricingTable\Services\API\ProductFields;

use TierPricingTable\Core\ServiceContainerTrait;
use WC_Product;

abstract class ProductField {
	
	use ServiceContainerTrait;
	
	abstract public function getFieldSlug(): string;
	
	abstract public function getValue( array $product );
	
	abstract public function updateValue( $value, WC_Product $product );
	
	abstract public function getType();
	
	abstract public function getDescription();
	
	public function register() {
		register_rest_field( $this->getSupportedProductTypes(), $this->getFieldSlug(), array(
			'get_callback'    => array( $this, 'getValueForRequest' ),
			'update_callback' => array( $this, 'updateValueForRequest' ),
			'schema'          => array(
				'description' => $this->getDescription(),
				'type'        => $this->getType(),
				'context'     => $this->getContext(),
			),
		) );
	}
	
	/**
	 * The value, for those who may edit the product only. The fields hold the pricing of every role, which the
	 * storefront shows to nobody but that role; WordPress's public product route serves the same object type.
	 *
	 * @param  array  $object
	 *
	 * @return mixed|null
	 */
	public function getValueForRequest( $object ) {
		$productId = is_array( $object ) && isset( $object['id'] ) ? (int) $object['id'] : 0;

		if ( ! $productId || ! current_user_can( 'edit_post', $productId ) ) {
			return null;
		}

		return $this->getValue( $object );
	}

	/**
	 * Writes come through WooCommerce's API, with a product object. WordPress's own product route hands over a
	 * post: those writes are ignored instead of failing.
	 *
	 * @param  mixed  $value
	 * @param  mixed  $object
	 *
	 * @return mixed|null
	 */
	public function updateValueForRequest( $value, $object ) {
		if ( ! $object instanceof WC_Product || ! current_user_can( 'edit_post', $object->get_id() ) ) {
			return null;
		}

		return $this->updateValue( $value, $object );
	}

	public function getContext(): array {
		return array( 'view', 'edit' );
	}
	
	public function getSupportedProductTypes() {
		
		return apply_filters( 'tiered_pricing_table/api/supported_product_types', array(
			'product',
			'product_variation',
		), $this );
	}
}
