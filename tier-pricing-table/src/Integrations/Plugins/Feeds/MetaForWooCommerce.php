<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Services\Feeds\FeedJobs;

/**
 * Meta for WooCommerce (Facebook for WooCommerce) sends products to the Facebook and Instagram catalog in three
 * ways, all priced as a logged-out visitor here:
 *
 * - The background product sync: an admin-ajax request the plugin sends to the site itself with the cookies of
 *   the request that queued the products. A stock change queues them, so a logged-in customer's checkout can
 *   start it, as can an administrator's edits. The request ends with the sync, so it is priced as a visitor
 *   from its start.
 * - The daily feed file, in Action Scheduler: "wc_facebook_regenerate_feed" (the default generator) and the jobs
 *   of the newer generator.
 *
 * It reads WooCommerce's price getters, so nothing else is needed for the price. Not covered: a simple product
 * sent right away when it is restored from the trash in wp-admin (the next sync or feed file corrects it).
 *
 * Products with a minimum quantity keep the price of one item: Meta has no filter for the sale price, so a total
 * could not be sent consistently.
 */
class MetaForWooCommerce extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'Meta for WooCommerce (Facebook and Instagram)';
	}

	public function getDescription(): string {
		return __( 'The Facebook and Instagram catalog gets the price a logged-out visitor pays, whoever starts the sync.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'meta-for-woocommerce';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/facebook-for-woocommerce/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/meta-for-woocommerce-icon.svg' );
	}

	public function isPluginActive(): bool {
		return function_exists( 'facebook_for_woocommerce' );
	}

	public function supportsMinimumQuantityTotal(): bool {
		return false;
	}

	protected function registerHooks(): void {
		// the background product sync request (it ends when the sync is done)
		add_action( 'wp_ajax_wc_facebook_background_product_sync', array( FeedJobs::class, 'beginForRequest' ), 0 );
		add_action( 'wp_ajax_nopriv_wc_facebook_background_product_sync', array( FeedJobs::class, 'beginForRequest' ), 0 );

		// every way of sending a product checks this filter first
		add_filter( 'wc_facebook_should_sync_product', function ( $sync, $product = null ) {
			return $sync && $this->shouldExclude( $product ) ? false : $sync;
		}, 20, 2 );
	}

	protected function registerJobs(): void {
		// the exact name: other "wc_facebook_regenerate_feed_*" jobs build feeds without products
		FeedJobs::addJobHook( 'wc_facebook_regenerate_feed' );
		FeedJobs::addJobHook( 'facebook_for_woocommerce/jobs/generate_feed/*' );
	}
}
