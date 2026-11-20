<?php namespace TierPricingTable\Services\Feeds;

use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

/**
 * When feeds advertise the total of the smallest quantity, the product page has to show that total too, and its
 * structured data has to carry it: Google compares the feed price with both.
 *
 * - A "Minimum order: 6 for $54.00" line under the product price (classic and block themes).
 * - WooCommerce's structured data offer priced at that total.
 *
 * Simple products and variations shown as products; a variable product's page lists variations with their own
 * minimums, so it is left as it is.
 *
 * @package TierPricingTable\Services\Feeds
 */
class MinimumQuantityDisplay {

	/**
	 * Whether the hooks are added.
	 *
	 * @var bool
	 */
	protected static $started = false;

	public static function init(): void {
		if ( self::$started ) {
			return;
		}

		self::$started = true;

		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'renderClassic' ), 11 );
		add_filter( 'render_block_woocommerce/product-price', array( __CLASS__, 'appendToPriceBlock' ), 10, 3 );
		add_filter( 'woocommerce_structured_data_product_offer', array( __CLASS__, 'filterOffer' ), 20, 2 );
	}

	/**
	 * The totals for the product page's own product, when the store advertises them (null otherwise).
	 */
	protected static function getTotalsForPage( $product ): ?array {
		if ( ! $product instanceof WC_Product || ! MinimumQuantityPrice::isTotalMode() ) {
			return null;
		}

		if ( ! TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
			return null;
		}

		return MinimumQuantityPrice::getTotals( $product );
	}

	public static function getLine( WC_Product $product ): string {
		$totals = self::getTotalsForPage( $product );

		if ( ! $totals ) {
			return '';
		}

		$total = wc_get_price_to_display( $product, array( 'price' => null !== $totals['sale'] ? $totals['sale'] : $totals['price'] ) );

		return '<p class="tpt-minimum-order">' . wp_kses_post( sprintf(
			/* translators: 1: the smallest quantity a shopper can buy, 2: the price of that quantity */
			__( 'Minimum order: %1$s for %2$s', 'tier-pricing-table' ),
			'<strong>' . esc_html( number_format_i18n( $totals['quantity'] ) ) . '</strong>',
			'<strong>' . wc_price( $total ) . '</strong>'
		) ) . '</p>';
	}

	public static function renderClassic() {
		global $product;

		if ( ! is_product() || ! $product instanceof WC_Product || get_queried_object_id() !== $product->get_id() ) {
			return;
		}

		echo self::getLine( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in getLine()
	}

	/**
	 * Block themes: the line goes under the price block of the page's own product (not related products).
	 *
	 * @param  string  $content
	 * @param  array  $block
	 * @param  mixed  $instance
	 *
	 * @return string
	 */
	public static function appendToPriceBlock( $content, $block = array(), $instance = null ) {
		if ( ! is_product() ) {
			return $content;
		}

		$postId = is_object( $instance ) && isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : get_the_ID();

		if ( get_queried_object_id() !== $postId ) {
			return $content;
		}

		$product = wc_get_product( $postId );

		return $product ? $content . self::getLine( $product ) : $content;
	}

	/**
	 * WooCommerce's structured data offer, priced at the total of the smallest quantity.
	 *
	 * @param  array  $offer
	 * @param  mixed  $product
	 *
	 * @return array
	 */
	public static function filterOffer( $offer, $product = null ) {
		if ( ! is_array( $offer ) || 'Offer' !== ( $offer['@type'] ?? '' ) ) {
			return $offer;
		}

		$totals = self::getTotalsForPage( $product );

		if ( ! $totals ) {
			return $offer;
		}

		$template  = is_array( $offer['priceSpecification'] ?? null ) && $offer['priceSpecification'] ? end( $offer['priceSpecification'] ) : array();
		$inclusive = 'incl' === get_option( 'woocommerce_tax_display_shop' );

		$display = function ( float $raw ) use ( $product, $inclusive ): string {
			$price = $inclusive ? wc_get_price_including_tax( $product, array( 'price' => $raw ) ) : wc_get_price_excluding_tax( $product, array( 'price' => $raw ) );

			return wc_format_decimal( $price, wc_get_price_decimals() );
		};

		$spec = function ( float $raw, bool $listPrice = false ) use ( $display, $template ): array {
			$specification = array(
				'@type'         => 'UnitPriceSpecification',
				'price'         => $display( $raw ),
				'priceCurrency' => $template['priceCurrency'] ?? get_woocommerce_currency(),
			);

			foreach ( array( 'validThrough', 'valueAddedTaxIncluded' ) as $key ) {
				if ( isset( $template[ $key ] ) ) {
					$specification[ $key ] = $template[ $key ];
				}
			}

			if ( $listPrice ) {
				$specification['priceType'] = 'https://schema.org/ListPrice';
			}

			return $specification;
		};

		if ( null !== $totals['sale'] ) {
			$offer['priceSpecification'] = array( $spec( $totals['sale'] ), $spec( $totals['regular'], true ) );
			$offer['price']              = $display( $totals['sale'] );
		} else {
			$offer['priceSpecification'] = array( $spec( $totals['price'] ) );
			$offer['price']              = $display( $totals['price'] );
		}

		return $offer;
	}
}
