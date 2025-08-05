<?php namespace TierPricingTable\Frontend;

use TierPricingTable\Admin\ProductManagers\AdvanceOptionsForVariableProduct;
use TierPricingTable\Cache;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\ProductPagePriceSubsection;
use WC_Product;
use WC_Product_Variable;

/**
 * Class CatalogPriceManager
 *
 * @package TierPricingTable
 */
class CatalogPriceManager {

	use ServiceContainerTrait;

	/**
	 * Price hash
	 *
	 * @var string
	 */
	private $variablePriceHash;

	/**
	 * CatalogPriceManager constructor.
	 */
	public function __construct() {

		if ( $this->isEnabled() && ! is_admin() ) {
			add_filter( 'woocommerce_get_price_html', array( $this, 'formatPrice' ), 99, 2 );

			// Store variable products price hash
			add_filter( 'woocommerce_get_variation_prices_hash', function ( $hash, WC_Product_Variable $product ) {

				$hash[] = $product->get_date_modified();

				$this->variablePriceHash[ $product->get_id() ] = md5( wp_json_encode( $hash ) . $this->getDisplayType() );

				return $hash;
			}, 10, 2 );
		}
	}

	/**
	 * Change logic showing prince at catalog for product with tiered price rules
	 *
	 * @param string $priceHtml
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	public function formatPrice( $priceHtml, $product ) {

		// Some themes uses ->get_price_html() to show cart item price. Do not modify such prices
		if ( is_cart() ) {
			return $priceHtml;
		}

		$currentProductPageProductId = get_queried_object_id();
		$parentProductId             = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		if ( AdvanceOptionsForVariableProduct::productHasNoRules( $parentProductId ) ) {
			return $priceHtml;
		}

		$isAdjustPriceOnProductPage = 'same_as_catalog' === ProductPagePriceSubsection::getFormatPriceType();

		if ( $currentProductPageProductId === $parentProductId ) {

			// Do not modify prices for variations on product page
			if ( ! apply_filters( 'tiered_pricing_table/catalog_pricing/format_variation_price', false, $priceHtml, $product ) && $product->is_type( 'variation' ) ) {
				return $priceHtml;
			}

			if ( $isAdjustPriceOnProductPage ) {
				if ( $product instanceof WC_Product_Variable ) {
					$newPriceHTML = $this->getFormattedPriceForVariableProduct( $product );
				} else {
					$newPriceHTML = $this->getFormattedPriceForSimpleProducts( $product );
				}
			} else {
				$newPriceHTML = false;
			}

		} else {

			if ( $product instanceof WC_Product_Variable && 'yes' === $this->useForVariable() ) {
				$newPriceHTML = $this->getFormattedPriceForVariableProduct( $product );
			} else {
				$newPriceHTML = $this->getFormattedPriceForSimpleProducts( $product );
			}
		}

		$newPriceHtml = $newPriceHTML ? $newPriceHTML . ' ' . $product->get_price_suffix() : $priceHtml;

		return apply_filters( 'tiered_pricing_table/catalog_pricing/price_html', $newPriceHtml, $priceHtml, $product );
	}

	/**
	 * Format price for simple/variation products.
	 *
	 * @param WC_Product $product
	 *
	 * @return bool|string
	 */
	protected function getFormattedPriceForSimpleProducts( WC_Product $product ) {

		$priceHtml = false;

		if ( in_array( $product->get_type(), array( 'simple', 'variation' ) ) ) {

			$displayPriceType = $this->getDisplayType();
			$pricingRule      = PriceManager::getPricingRule( $product->get_id() );

			if ( ! empty( $pricingRule->getRules() ) ) {
				if ( 'range' === $displayPriceType ) {
					$priceHtml = $this->getRange( $pricingRule, $product );
				} else {
					$priceHtml = $this->getLowestPrice( $pricingRule, $product );
				}
			}
		}

		return $priceHtml;
	}

	/**
	 * Format price for variable product. Range uses lowest and high prices from all variations
	 *
	 * @param WC_Product_Variable $product
	 * @param bool $force
	 *
	 * @return bool|string
	 */
	protected function getFormattedPriceForVariableProduct( WC_Product_Variable $product, $force = false ) {

		$price = Cache::getCachedDataForVariableProduct( $product->get_id(), 'price_html' );

		// For product that has no tiered pricing
		if ( $price === 'default' ) {
			return false;
		}

		if ( ! $price ) {
			$price = $this->formatPriceForVariableProduct( $product );

			Cache::setCachedDataForVariableProduct( $product->get_id(), 'price_html', $price );

			return $price;
		}

		return $price;
	}

	/**
	 * Format price for variable product. Range uses lowest and high prices from all variations
	 *
	 * @param WC_Product_Variable $product
	 *
	 * @return bool|string
	 */
	protected function formatPriceForVariableProduct( WC_Product_Variable $product ) {

		// With taxes
		$maxPrice  = (float) $product->get_variation_price( 'max', true );
		$minPrices = array( (float) $product->get_variation_price( 'min', true ) );

		foreach ( $product->get_available_variations() as $variation ) {
			$pricingRule = PriceManager::getPricingRule( $variation['variation_id'] );

			if ( ! empty( $pricingRule->getRules() ) ) {
				$minPrices[] = $this->getLowestPrice( $pricingRule, wc_get_product( $variation['variation_id'] ), false );
			}
		}

		if ( ! empty( $minPrices ) && count( $minPrices ) > 1 ) {

			if ( 'range' === $this->getDisplayType() ) {

				if ( min( $minPrices ) === $maxPrice ) {
					return false;
				}

				return wc_price( min( $minPrices ) ) . ' - ' . wc_price( $maxPrice );
			} else {
				return $this->getLowestPrefix() . ' ' . wc_price( min( $minPrices ) );
			}
		}

		return false;
	}

	/**
	 * Get range from lowest to highest price from price rules
	 *
	 * @param PricingRule $pricingRule
	 * @param WC_Product $product
	 *
	 * @return string
	 */
	protected function getRange( PricingRule $pricingRule, $product ) {
		$pricingRules = $pricingRule->getRules();
		$lowest       = array_pop( $pricingRules );

		$highest_html = wc_price( wc_get_price_to_display( $product, array(
			'price' => $product->get_price(),
		) ) );

		if ( $pricingRule->isPercentage() ) {

			$lowest_html = wc_price(
				wc_get_price_to_display( $product, array(
					'price' => PriceManager::getPriceByPercentDiscount( $product->get_price(), $lowest ),
				) )
			);

			$range = $lowest_html . ' - ' . $highest_html;
		} else {
			$lowest_html = wc_price(
				wc_get_price_to_display( $product, array(
					'price' => $lowest,
				) )
			);

			$range = $lowest_html . ' - ' . $highest_html;
		}

		if ( $lowest_html !== $highest_html ) {
			return $range;
		}

		return $lowest_html;
	}

	/**
	 * Get lowest price from price rules
	 *
	 * @param PricingRule $pricingRule
	 * @param WC_Product $product
	 *
	 * @param bool $html
	 *
	 * @return string|float
	 */
	protected function getLowestPrice( $pricingRule, $product, $html = true ) {
		$pricingRules = $pricingRule->getRules();

		if ( $pricingRule->isPercentage() ) {
			$lowest = PriceManager::getPriceByPercentDiscount( $product->get_price(),
				array_pop( $pricingRules ) );
		} else {
			$lowest = array_pop( $pricingRules );
		}

		if ( ! $html ) {
			return wc_get_price_to_display( $product, array(
				'price' => $lowest
			) );
		}

		return $this->getLowestPrefix() . ' ' . wc_price( wc_get_price_to_display( $product, array(
				'price' => $lowest
			) ) );
	}

	public function getLowestPrefix() {
		return $this->getContainer()->getSettings()->get( 'lowest_prefix', __( 'From', 'tier-pricing-table' ) );
	}

	public function isEnabled() {
		return 'yes' === $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog', 'yes' );
	}

	public function getDisplayType() {
		return $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog_type', 'range' );
	}

	public function useForVariable() {
		return $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog_for_variable', 'yes' );
	}
}
