<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Services\Feeds\FeedJobs;

/**
 * Product Feed PRO for WooCommerce (AdTribes) builds feeds in batches: in Action Scheduler jobs, and inside
 * admin-ajax requests while an administrator watches the feed list. Both are priced as a logged-out visitor.
 * It reads WooCommerce's price getters, so nothing else is needed for the price.
 */
class ProductFeedPro extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'Product Feed PRO for WooCommerce (AdTribes)';
	}

	public function getDescription(): string {
		return __( 'Feeds get the price a logged-out visitor pays, whoever builds them.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'product-feed-pro';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/woo-product-feed-pro/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/product-feed-pro-icon.png' );
	}

	public function isPluginActive(): bool {
		return defined( 'ADT_PFP_AS_GENERATE_PRODUCT_FEED_BATCH' );
	}

	protected function registerHooks(): void {
		// a batch that runs inside an admin's request (feed list status poll, the "refresh" button): the rest of
		// that request builds the feed
		add_action( 'adt_before_product_feed_batch_processing', array( FeedJobs::class, 'beginForRequest' ), 0 );
		add_action( 'woosea_before_get_products', array( FeedJobs::class, 'beginForRequest' ), 0 );

		// the data of one product, all keys at once: an empty array leaves the product out
		add_filter( 'adt_get_product_data', function ( $productData, $feed = null, $product = null ) {
			if ( $this->shouldExclude( $product ) ) {
				return array();
			}

			return is_array( $productData ) ? $this->scaleForMinimumQuantity( $productData, $product ) : $productData;
		}, 20, 3 );
	}

	/**
	 * The plain price keys of a product's feed data, multiplied up to the total of the smallest quantity when the
	 * store advertises that. Formatted variants (with separators or currency) are left as they are.
	 */
	protected function scaleForMinimumQuantity( array $productData, $product ): array {
		$keys = array(
			'regular' => array( 'regular_price', 'regular_price_forced' ),
			'price'   => array( 'price', 'price_forced' ),
			'sale'    => array( 'sale_price', 'sale_price_forced' ),
		);

		foreach ( $keys as $kind => $names ) {
			$factor = $this->getMinimumQuantityFactor( $product, $kind );

			if ( null === $factor ) {
				continue;
			}

			foreach ( $names as $name ) {
				if ( isset( $productData[ $name ] ) && is_numeric( $productData[ $name ] ) ) {
					$productData[ $name ] = round( (float) $productData[ $name ] * $factor, wc_get_price_decimals() );
				}
			}
		}

		return $productData;
	}

	protected function registerJobs(): void {
		FeedJobs::addJobHook( (string) constant( 'ADT_PFP_AS_GENERATE_PRODUCT_FEED_BATCH' ) );
	}
}
