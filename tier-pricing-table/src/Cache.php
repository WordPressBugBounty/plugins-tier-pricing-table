<?php namespace TierPricingTable;

/*
 * Class Cache
 *
 * @package TierPricingTable
 */

use TierPricingTable\Core\ServiceContainer;
use WC_Product_Variable;

class Cache {

	public static $variableProductsHash = array();

	protected static $inited = false;

	protected static $isEnabled = false;

	const PURGE_CACHE_ACTION = 'tpt_purge_cache';

	public static function init() {

		if ( self::$inited ) {
			return;
		}

		self::$isEnabled = ServiceContainer::getInstance()->getSettings()->get( 'cache_enabled', 'yes' ) === 'yes';

		if ( self::isEnabled() ) {

			$pricesDisplayType = ServiceContainer::getInstance()->getSettings()->get( 'tiered_price_at_catalog_type', 'range' );

			// Store variable products price hash
			add_filter( 'woocommerce_get_variation_prices_hash', function ( $hash, WC_Product_Variable $product ) use ( $pricesDisplayType ) {

				$hash[] = $product->get_date_modified();

				if ( ! array_key_exists( $product->get_id(), self::$variableProductsHash ) ) {
					self::$variableProductsHash[ $product->get_id() ] = md5( wp_json_encode( $hash ) . $pricesDisplayType );
				}

				return $hash;
			}, 999999, 2 );

			add_action( 'admin_post_' . self::PURGE_CACHE_ACTION, function () {

				$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : false;

				if ( current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, self::PURGE_CACHE_ACTION ) ) {

					ServiceContainer::getInstance()->getAdminNotifier()->flash( __( 'Cache has been purged successfully', 'tier-pricing-table' ) );

					self::clearCache();
				}

				return wp_safe_redirect( wp_get_referer() );
			} );
		}

		self::$inited = true;
	}

	public static function getCachedDataForVariableProduct( $productId, $key = null ) {

		if ( ! self::$isEnabled ) {
			return false;
		}

		if ( ! empty( self::$variableProductsHash[ $productId ] ) ) {

			$productKey = $productId . self::$variableProductsHash[ $productId ];
			$cacheKey   = 'tpt_variable_product_data';

			$value = (array) get_transient( $cacheKey );

			if ( empty( $value[ $productKey ] ) ) {
				return false;
			}

			if ( $key ) {

				return isset( $value[ $productKey ][ $key ] ) ? (string) $value[ $productKey ][ $key ] : false;
			}

			return $value;
		}

		return false;
	}

	public static function setCachedDataForVariableProduct( $productId, $key, $value ) {

		if ( ! self::$isEnabled ) {
			return;
		}

		if ( ! empty( self::$variableProductsHash[ $productId ] ) ) {

			$productKey = $productId . self::$variableProductsHash[ $productId ];
			$cacheKey   = 'tpt_variable_product_data';

			$data = (array) get_transient( $cacheKey );

			$data[ $productKey ][ $key ] = $value;

			$data = array_filter( $data );

			set_transient( $cacheKey, $data, DAY_IN_SECONDS * 10 + rand( 10, DAY_IN_SECONDS ) );
		}
	}

	public static function clearCache() {
		delete_transient( 'tpt_variable_product_data' );
	}

	public static function getPurgeURL() {
		return add_query_arg( array(
			'action' => self::PURGE_CACHE_ACTION,
			'nonce'  => wp_create_nonce( self::PURGE_CACHE_ACTION )
		), admin_url( 'admin-post.php' ) );
	}

	public static function isEnabled() {
		return self::$isEnabled;
	}
}