<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Core\PricingContext;
use TierPricingTable\Services\Feeds\FeedEligibility;
use TierPricingTable\Services\Feeds\MinimumQuantityPrice;
use WC_Product;

/**
 * A product feed plugin (Google Shopping, Merchant Center): its feed carries the price a logged-out visitor pays,
 * which is what Google compares it with, and it can leave out products a visitor cannot see or buy.
 *
 * @package TierPricingTable\Integrations\Plugins\Feeds
 */
abstract class FeedIntegrationAbstract extends PluginIntegrationAbstract {

	/**
	 * Whether the feed plugin is installed and active (checked on plugins_loaded). It decides whether the shared
	 * settings show and whether the plugin's background jobs are watched.
	 */
	abstract public function isPluginActive(): bool;

	/**
	 * The feed plugin's filters. Added right away: a filter that never fires costs nothing.
	 */
	abstract protected function registerHooks(): void;

	/**
	 * The feed plugin's background jobs to price as a visitor (FeedJobs::addJobHook()), when it is active.
	 */
	protected function registerJobs(): void {
	}

	public function run() {
		$this->registerHooks();

		add_action( 'plugins_loaded', function () {
			if ( ! apply_filters( 'tiered_pricing_table/feeds/' . $this->getSlug() . '/active', $this->isPluginActive() ) ) {
				return;
			}

			FeedsSettings::register( $this );

			$this->registerJobs();
		}, 20 );
	}

	public function getIntegrationCategory(): string {
		return 'feeds';
	}

	/**
	 * Whether this feed can advertise the total of the smallest quantity (the "Products with a minimum quantity"
	 * setting). A feed plugin without price filters keeps the price of one item.
	 */
	public function supportsMinimumQuantityTotal(): bool {
		return true;
	}

	/**
	 * The totals of the smallest quantity a visitor can buy, when the store advertises those (null otherwise).
	 *
	 * @return array|null See MinimumQuantityPrice::getTotals().
	 */
	protected function getMinimumQuantityTotals( $product ): ?array {
		if ( ! $product instanceof WC_Product || ! MinimumQuantityPrice::isTotalMode() ) {
			return null;
		}

		return PricingContext::runAsGuest( function () use ( $product ) {
			return MinimumQuantityPrice::getTotals( $product );
		} );
	}

	/**
	 * By how much to multiply a price the feed plugin read for one item ('regular', 'price' or 'sale'), when the
	 * store advertises the total of the smallest quantity (null: leave the price as it is).
	 */
	protected function getMinimumQuantityFactor( $product, string $kind ): ?float {
		if ( ! $product instanceof WC_Product || ! MinimumQuantityPrice::isTotalMode() ) {
			return null;
		}

		return PricingContext::runAsGuest( function () use ( $product, $kind ) {
			return MinimumQuantityPrice::getFactor( $product, $kind );
		} );
	}

	protected function shouldExclude( $product ): bool {
		return $product instanceof WC_Product && FeedEligibility::shouldExclude( $product );
	}
}
