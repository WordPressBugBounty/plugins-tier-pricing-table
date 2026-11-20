<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Services\Feeds\FeedJobs;

/**
 * Pinterest for WooCommerce builds one XML feed file in Action Scheduler jobs ("pinterest/jobs/generate_feed/...")
 * and Pinterest fetches it. Those jobs run as whoever triggered them, so they are priced as a logged-out visitor
 * here. It reads WooCommerce's price getters, so nothing else is needed for the price.
 *
 * Products with a minimum quantity keep the price of one item: Pinterest has no price filter.
 */
class PinterestForWooCommerce extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'Pinterest for WooCommerce';
	}

	public function getDescription(): string {
		return __( 'The Pinterest catalog gets the price a logged-out visitor pays, whoever runs the feed job.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'pinterest-for-woocommerce';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/pinterest-for-woocommerce/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/pinterest-for-woocommerce-icon.svg' );
	}

	public function isPluginActive(): bool {
		return defined( 'PINTEREST_FOR_WOOCOMMERCE_VERSION' );
	}

	public function supportsMinimumQuantityTotal(): bool {
		return false;
	}

	protected function registerHooks(): void {
		// the XML of one product: an empty string leaves it out of the feed
		add_filter( 'pinterest_for_woocommerce_feed_item_xml', function ( $xml, $product = null ) {
			return $this->shouldExclude( $product ) ? '' : $xml;
		}, 20, 2 );
	}

	protected function registerJobs(): void {
		FeedJobs::addJobHook( 'pinterest/jobs/generate_feed/*' );
	}
}
