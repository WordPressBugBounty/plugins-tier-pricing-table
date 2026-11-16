<?php namespace TierPricingTable\Services;

use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current visitor may see a product's tiered pricing where the plugin renders it for a product ID
 * that does not come from the page itself: the variation pricing table request, the shortcode and the
 * Elementor widget.
 */
class ProductAccess {

	/**
	 * Published products are for everyone; private ones for users who may read them; drafts, pending and
	 * scheduled products only for users who may edit them (previews). A variation also follows its parent,
	 * and a disabled variation is for editors only. Catalog visibility by role can take a product away.
	 *
	 * @param  WC_Product  $product  A product or a variation.
	 *
	 * @return bool
	 */
	public static function canView( WC_Product $product ): bool {
		$root = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;

		if ( ! $root instanceof WC_Product ) {
			return false;
		}

		$rootId = $root->get_id();
		$status = $root->get_status();

		if ( 'publish' === $status ) {
			$canView = true;
		} elseif ( 'private' === $status ) {
			$canView = current_user_can( 'read_post', $rootId );
		} else {
			$canView = current_user_can( 'edit_post', $rootId );
		}

		// a variation switched off in the product editor
		if ( $canView && $product->get_id() !== $rootId && 'publish' !== $product->get_status() ) {
			$canView = current_user_can( 'edit_post', $rootId );
		}

		/**
		 * Whether the current visitor may see this product's tiered pricing.
		 *
		 * @param bool       $canView
		 * @param WC_Product $product The product or variation asked for.
		 * @param WC_Product $root    The product itself (the parent of a variation).
		 */
		return (bool) apply_filters( 'tiered_pricing_table/product/can_view', $canView, $product, $root );
	}
}
