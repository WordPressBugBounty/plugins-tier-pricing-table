<?php namespace TierPricingTable\Addons\LayoutConfigurator;

use WC_Product_Simple;

/**
 * In-memory product for the layout preview: a fixed price and no database row. Registered with
 * WooCommerce's product factory for its id (see LayoutPreview::getProduct()), so wc_get_product() calls
 * inside the renderer resolve to it as well.
 */
class PreviewProduct extends WC_Product_Simple {
	
	/**
	 * Id no stored product uses.
	 */
	const ID = 2000000000;
	
	public function __construct( $product = 0 ) {
		parent::__construct( 0 ); // no data store read
		
		$this->set_id( self::ID );
		$this->set_props( array(
			'name'               => __( 'Preview product', 'tier-pricing-table' ),
			'status'             => 'publish',
			'catalog_visibility' => 'visible',
			'regular_price'      => (string) LayoutPreview::PRICE,
			'price'              => (string) LayoutPreview::PRICE,
			'stock_status'       => 'instock',
		) );
		$this->set_object_read( true );
	}
}
