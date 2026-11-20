<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Core\PricingContext;
use WC_Product;

/**
 * Product Feed Manager for WooCommerce (RexTheme, "RexFeed") builds feeds from an administrator's browser by
 * default, so it would get the administrator's prices. Its price filter gets the visitor's price instead.
 *
 * Grouped products and bundles are read from the database by RexFeed itself and are left as they are.
 */
class RexFeed extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'Product Feed Manager for WooCommerce (RexTheme)';
	}

	public function getDescription(): string {
		return __( 'Feeds get the price a logged-out visitor pays, also when an administrator builds them.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'rexfeed';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/best-woocommerce-feed/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/rexfeed-icon.png' );
	}

	public function isPluginActive(): bool {
		return function_exists( 'rexfeed_get_variable_parent_product_price' ) || ( defined( 'SINGLE_SCHEDULE_HOOK' ) && 'rex_feed_regenerate_feed_batch' === constant( 'SINGLE_SCHEDULE_HOOK' ) );
	}

	protected function registerHooks(): void {
		add_filter( 'rex_feed_product_price_before_formatting', array( $this, 'getVisitorPrice' ), 20, 4 );
	}

	/**
	 * The price RexFeed asked for, as a logged-out visitor pays it.
	 *
	 * @param  mixed  $price  As RexFeed read it.
	 * @param  WC_Product|mixed  $product
	 * @param  string  $type  _regular_price, _price or _sale_price.
	 *
	 * @return mixed
	 */
	public function getVisitorPrice( $price, $product = null, $type = '' ) {
		if ( ! $product instanceof WC_Product || ! in_array( $type, array( '_regular_price', '_price', '_sale_price' ), true ) ) {
			return $price;
		}

		// RexFeed reads these from the database itself
		if ( $product->is_type( array( 'grouped', 'bundle' ) ) ) {
			return $price;
		}

		return PricingContext::runAsGuest( function () use ( $product, $type, $price ) {
			if ( $product->is_type( 'variable' ) ) {
				// RexFeed's own choice of variation (the default one, else the cheapest), now priced for a visitor
				return function_exists( 'rexfeed_get_variable_parent_product_price' ) ? rexfeed_get_variable_parent_product_price( $product, $type ) : $price;
			}

			$method = 'get' . $type;
			$value  = is_callable( array( $product, $method ) ) ? $product->$method() : $price;
			$totals = $this->getMinimumQuantityTotals( $product );

			if ( $totals ) {
				$value = array( '_regular_price' => $totals['regular'], '_price' => $totals['price'], '_sale_price' => $totals['sale'] ?? '' )[ $type ];
			}

			return $value;
		} );
	}
}
