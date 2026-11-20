<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Services\Feeds\FeedJobs;

/**
 * Google for WooCommerce (Google Listings & Ads) uploads products to Merchant Center in Action Scheduler jobs
 * ("gla/jobs/..."). Those jobs run as whoever triggered them, so they are priced as a logged-out visitor here.
 * It reads WooCommerce's price getters, so nothing else is needed for the price.
 */
class GoogleForWooCommerce extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'Google for WooCommerce';
	}

	public function getDescription(): string {
		return __( 'Merchant Center gets the price a logged-out visitor pays, whoever runs the product sync.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'google-for-woocommerce';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/google-listings-and-ads/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/google-for-woocommerce-icon.svg' );
	}

	public function isPluginActive(): bool {
		return function_exists( 'woogle_get_container' );
	}

	protected function registerHooks(): void {
		// the products of a sync batch, before they are sent (WC_Product[])
		add_filter( 'woocommerce_gla_get_sync_ready_products_filter', function ( $products ) {
			if ( ! is_array( $products ) ) {
				return $products;
			}

			return array_filter( $products, function ( $product ) {
				return ! $this->shouldExclude( $product );
			} );
		}, 20 );

		// float $price, WC_Product $product, bool $tax_excluded
		add_filter( 'woocommerce_gla_product_attribute_value_price', function ( $price, $product = null ) {
			return $this->scaleForMinimumQuantity( $price, $product, 'regular' );
		}, 20, 2 );
		add_filter( 'woocommerce_gla_product_attribute_value_sale_price', function ( $price, $product = null ) {
			return $this->scaleForMinimumQuantity( $price, $product, 'sale' );
		}, 20, 2 );
	}

	/**
	 * A product that cannot be bought one at a time is advertised at the total of its smallest quantity, when the
	 * store chose that. Google for WooCommerce passes its prices with its own tax and currency applied, so they are
	 * scaled rather than replaced.
	 *
	 * @param  mixed  $price
	 * @param  mixed  $product
	 * @param  string  $kind  regular or sale
	 *
	 * @return mixed
	 */
	protected function scaleForMinimumQuantity( $price, $product, string $kind ) {
		$factor = is_numeric( $price ) ? $this->getMinimumQuantityFactor( $product, $kind ) : null;

		return null === $factor ? $price : round( (float) $price * $factor, wc_get_price_decimals() );
	}

	protected function registerJobs(): void {
		FeedJobs::addJobHook( 'gla/jobs/*' );
	}
}
