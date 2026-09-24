<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;

/**
 * "Who can see this product" in the product's Tiered Pricing tab (Additional Options).
 */
class ProductVisibilityFields {

	const NONCE = 'tpt_visibility_product';

	public function __construct() {
		add_action( 'tiered_pricing_table/admin/advance_product_options', array( $this, 'render' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save' ) );
	}

	public function render( $productId ) {
		$rule = VisibilityMeta::getProductRule( (int) $productId );

		wp_nonce_field( self::NONCE, self::NONCE );

		woocommerce_wp_select( array(
			'id'          => VisibilityMeta::PRODUCT_MODE,
			'label'       => __( 'Who can see this product', 'tier-pricing-table' ),
			'options'     => VisibilityMeta::getModeOptions( true ),
			'value'       => $rule['mode'],
			'desc_tip'    => true,
			'description' => __( 'Hidden products leave the shop, search, related lists and menus, cannot be bought, and their page sends guests to the login page. Shop managers always see everything.', 'tier-pricing-table' ),
		) );

		RoleChecklist::render( VisibilityMeta::PRODUCT_ROLES, $rule['roles'], VisibilityMeta::PRODUCT_MODE );
	}

	public function save( $productId ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $productId ) ) {
			return;
		}

		$mode  = isset( $_POST[ VisibilityMeta::PRODUCT_MODE ] ) ? sanitize_key( wp_unslash( $_POST[ VisibilityMeta::PRODUCT_MODE ] ) ) : VisibilityRule::MODE_INHERIT;
		$roles = isset( $_POST[ VisibilityMeta::PRODUCT_ROLES ] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST[ VisibilityMeta::PRODUCT_ROLES ] ) ) : array();

		VisibilityMeta::saveProductRule( (int) $productId, $mode, $roles );
	}
}
