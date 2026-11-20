<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

/**
 * CTX Feed (WebAppick) already builds every batch as a logged-out visitor (version 8.0.36 and later), so its
 * prices are right. This leaves out products a visitor cannot buy, when that setting is on.
 */
class CtxFeed extends FeedIntegrationAbstract {

	public function getTitle(): string {
		return 'CTX Feed (WebAppick)';
	}

	public function getDescription(): string {
		return __( 'Products visitors cannot buy can be left out, and a minimum quantity can show its total.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'ctx-feed';
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/webappick-product-feed-for-woocommerce/';
	}

	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/ctx-feed-icon.png' );
	}

	public function isPluginActive(): bool {
		return class_exists( 'CTXFeed\V8\Utility\GuestContext' );
	}

	protected function registerHooks(): void {
		// the price rows (raw number, before formatting): the total of the smallest quantity, when the store
		// advertises that; priority 50 is CTX Feed's own discount stage, so currency plugins (100) convert it after
		foreach ( array( 'price', 'regular_price', 'sale_price', 'price_with_tax', 'regular_price_with_tax', 'sale_price_with_tax' ) as $attribute ) {
			add_filter( 'woo_feed_filter_product_' . $attribute, function ( $price, $product = null, $config = null, $withTax = false ) use ( $attribute ) {
				$totals = $this->getMinimumQuantityTotals( $product );

				if ( ! $totals ) {
					return $price;
				}

				$kind  = 0 === strpos( $attribute, 'sale_price' ) ? 'sale' : ( 0 === strpos( $attribute, 'regular_price' ) ? 'regular' : 'price' );
				$total = 'sale' === $kind ? $totals['sale'] : $totals[ $kind ];

				if ( null === $total ) {
					return '';
				}

				return $withTax ? wc_get_price_including_tax( $product, array( 'price' => $total ) ) : $total;
			}, 50, 4 );
		}


		add_filter( 'ctxfeed_product_should_include', function ( $keep, $product = null, $config = null ) {
			return $keep && $this->shouldExclude( $product ) ? false : $keep;
		}, 20, 3 );
	}
}
