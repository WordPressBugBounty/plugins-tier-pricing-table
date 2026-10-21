<?php namespace TierPricingTable\Core;

/*
 * Class Cache
 *
 * Stores computed product data (catalog price HTML, "has rules" flags) per product: one transient per
 * variable product, keyed by a cache version so a full purge is a version bump. A product's entry is
 * dropped when the product or one of its variations is saved.
 *
 * @package TierPricingTable\Core
 */

use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WC_Product_Variable;

class Cache {

	use ServiceContainerTrait;

	protected $variableProductsHashes = array();

	/**
	 * Entries already read in this request, by product ID.
	 *
	 * @var array<int, array>
	 */
	protected $loaded = array();

	/**
	 * @var int|null
	 */
	protected $version = null;

	const PURGE_CACHE_ACTION = 'tpt_purge_cache';

	/**
	 * Pre 8.2 storage: one transient holding every product. Removed on purge so upgraded stores shed it.
	 */
	const LEGACY_TRANSIENT_KEY = 'tpt_product_data';

	const VERSION_OPTION = 'tpt_cache_version';

	const TRANSIENT_PREFIX = 'tpt_pd_';

	/**
	 * Entries kept per product (one per role combination and salt).
	 */
	const MAX_ENTRIES_PER_PRODUCT = 50;

	protected $isEnabled = true;

	public function __construct() {
		$this->isEnabled = $this->getContainer()->getSettings()->get( 'cache_enabled', 'yes' ) === 'yes';

		// The purge action must be available even when caching is disabled: the "Purge cache" button is always
		// rendered, and stale data left over from when caching was enabled must be removable.
		add_action( 'admin_post_' . self::PURGE_CACHE_ACTION, array( $this, 'handlePurgeRequest' ) );

		if ( ! $this->isEnabled ) {
			return;
		}

		// A saved product (or variation) drops its own entry; the cache key also carries the modified date.
		add_action( 'woocommerce_after_product_object_save', array( $this, 'forgetProduct' ), 10, 1 );
		add_action( 'woocommerce_delete_product', array( $this, 'forgetProduct' ), 10, 1 );
		add_action( 'woocommerce_trash_product', array( $this, 'forgetProduct' ), 10, 1 );

		// Store variable products price hashes
		add_filter( 'woocommerce_get_variation_prices_hash', function ( $hash, WC_Product_Variable $product ) {

			$pricesDisplayType = ServiceContainer::getInstance()->getSettings()->get( 'tiered_price_at_catalog_type',
				'range' );

			$hash[] = $product->get_date_modified();
			$hash[] = $product->get_id();

			if ( ! array_key_exists( $product->get_id(), $this->variableProductsHashes ) ) {
				$this->variableProductsHashes[ $product->get_id() ] = md5( wp_json_encode( $hash ) . $pricesDisplayType );
			}

			return $hash;
		}, 999999, 2 );
	}

	public function handlePurgeRequest() {

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( $_REQUEST['nonce'] ) : false;

		if ( current_user_can( 'manage_options' ) && wp_verify_nonce( $nonce, self::PURGE_CACHE_ACTION ) ) {
			ServiceContainer::getInstance()->getAdminNotifier()->flash( __( 'Cache has been purged successfully',
				'tier-pricing-table' ) );

			$this->purge();
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	public function isEnabled(): bool {
		return $this->getContainer()->getSettings()->get( 'cache_enabled', 'yes' ) === 'yes';
	}

	public function getProductCacheKey( WC_Product $product ) {

		if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
			if ( ! empty( $this->variableProductsHashes[ $product->get_id() ] ) ) {
				return $this->variableProductsHashes[ $product->get_id() ];
			}
		}

		// add-ons whose pricing changes without the product changing (scheduled global rules, for instance) salt the key
		$salt = (string) apply_filters( 'tiered_pricing_table/cache/product_cache_key_salt', '', $product );

		return md5( $product->get_date_modified() . implode( ',', TierPricingTablePlugin::getCurrentUserRoles() ) . $salt );
	}

	/**
	 * @param WC_Product $product
	 * @param string|null $key A stored key, or null for every value stored under the product's current cache key.
	 *
	 * @return mixed False when nothing is stored.
	 */
	public function getProductData( WC_Product $product, $key = null ) {

		if ( ! $this->isEnabled ) {
			return false;
		}

		$productCacheKey = $this->getProductCacheKey( $product );
		$entries         = $this->load( $product->get_id() );

		if ( empty( $entries[ $productCacheKey ] ) ) {
			return false;
		}

		if ( $key ) {
			return $entries[ $productCacheKey ][ $key ] ?? false;
		}

		return $entries[ $productCacheKey ];
	}

	public function setProductData( WC_Product $product, $key, $value ) {

		if ( ! $this->isEnabled ) {
			return;
		}

		$productId       = $product->get_id();
		$productCacheKey = $this->getProductCacheKey( $product );
		$entries         = $this->load( $productId );

		// The entry being written becomes the newest one.
		$current = $entries[ $productCacheKey ] ?? array();
		unset( $entries[ $productCacheKey ] );

		if ( $key ) {
			$current         = is_array( $current ) ? $current : array();
			$current[ $key ] = $value;
		} else {
			$current = $value;
		}

		$entries[ $productCacheKey ] = $current;

		// Other role combinations keep their entries, up to a limit; the oldest go first.
		while ( count( $entries ) > self::MAX_ENTRIES_PER_PRODUCT ) {
			array_shift( $entries );
		}

		$this->loaded[ $productId ] = $entries;

		set_transient( $this->getTransientKey( $productId ), $entries,
			DAY_IN_SECONDS * 10 + wp_rand( 10, DAY_IN_SECONDS ) );
	}

	/**
	 * Drop the stored entries of a product. A variation drops its parent's entries, because that is where
	 * the variable product's data lives.
	 *
	 * @param WC_Product|int $product
	 */
	public function forgetProduct( $product ) {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$productId = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

		unset( $this->loaded[ $productId ] );
		delete_transient( $this->getTransientKey( $productId ) );
	}

	/**
	 * @param WC_Product|null $product A product to forget, or null to purge everything.
	 */
	public function purge( $product = null ) {

		if ( $product ) {
			$this->forgetProduct( $product );

			return;
		}

		// A new version makes every stored entry unreachable; the old transients expire on their own.
		update_option( self::VERSION_OPTION, $this->getVersion() + 1, true );

		$this->version = null;
		$this->loaded  = array();

		delete_transient( self::LEGACY_TRANSIENT_KEY );

		// Without a persistent object cache the old entries sit in the options table: remove them now.
		if ( ! wp_using_ext_object_cache() ) {
			global $wpdb;

			$like = $wpdb->esc_like( '_transient_' . self::TRANSIENT_PREFIX ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );

			$like = $wpdb->esc_like( '_transient_timeout_' . self::TRANSIENT_PREFIX ) . '%';
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		}
	}

	public function getPurgeURL(): string {
		return add_query_arg( array(
			'action' => self::PURGE_CACHE_ACTION,
			'nonce'  => wp_create_nonce( self::PURGE_CACHE_ACTION ),
		), admin_url( 'admin-post.php' ) );
	}

	protected function load( int $productId ): array {

		if ( ! array_key_exists( $productId, $this->loaded ) ) {
			$data = get_transient( $this->getTransientKey( $productId ) );

			$this->loaded[ $productId ] = is_array( $data ) ? $data : array();
		}

		return $this->loaded[ $productId ];
	}

	protected function getTransientKey( int $productId ): string {
		return self::TRANSIENT_PREFIX . $this->getVersion() . '_' . $productId;
	}

	protected function getVersion(): int {

		if ( null === $this->version ) {
			$version = (int) get_option( self::VERSION_OPTION, 1 );

			$this->version = $version > 0 ? $version : 1;
		}

		return $this->version;
	}
}
