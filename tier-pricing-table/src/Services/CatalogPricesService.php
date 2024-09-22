<?php

namespace TierPricingTable\Services;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\ProductPagePriceSubsection;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WC_Product_Variable;
/**
 * Class CatalogPriceManager
 *
 * @package TierPricingTable
 */
class CatalogPricesService {
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
        if ( !$this->isEnabled() ) {
            return;
        }
        if ( is_admin() && !defined( 'DOING_AJAX' ) ) {
            return;
        }
    }

    public function getFormattedProductPrice( WC_Product $product ) : ?string {
        $priceHTML = false;
        $isVariable = TierPricingTablePlugin::isVariableProductSupported( $product );
        if ( $isVariable ) {
            $priceHTML = $this->getContainer()->getCache()->getProductData( $product, 'price_html' );
        }
        // there is no cache - build price and update the cache
        if ( false === $priceHTML ) {
            if ( $product instanceof WC_Product_Variable ) {
                $priceHTML = $this->getFormattedPriceForVariableProduct( $product );
            } else {
                $priceHTML = $this->getFormattedPriceForSimpleProduct( $product );
            }
            // product has no tiered pricing rules - store as "default" to do not check this again
            if ( is_null( $priceHTML ) ) {
                $priceHTML = 'default';
            }
            // Update cache only for variable products
            if ( $isVariable ) {
                $this->getContainer()->getCache()->setProductData( $product, 'price_html', $priceHTML );
            }
        }
        if ( 'default' === $priceHTML ) {
            return null;
        }
        return $priceHTML . $product->get_price_suffix();
    }

    public function formatPrice( ?string $defaultPriceHTML, ?WC_Product $product ) : ?string {
        if ( !$product ) {
            return $defaultPriceHTML;
        }
        // Some themes use ->get_price_html() to show cart item price. Do not modify product price if we're in the cart
        if ( is_cart() ) {
            return $defaultPriceHTML;
        }
        $currentProductPageProductId = get_queried_object_id();
        $parentProductId = ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
        if ( $currentProductPageProductId === $parentProductId ) {
            // Do not modify prices for variations on product page
            if ( TierPricingTablePlugin::isVariationProductSupported( $product ) && !apply_filters(
                'tiered_pricing_table/catalog_pricing/format_variation_price',
                false,
                $defaultPriceHTML,
                $product
            ) ) {
                return $defaultPriceHTML;
            }
            $newPriceHTML = null;
            if ( 'same_as_catalog' === ProductPagePriceSubsection::getFormatPriceType() ) {
                $newPriceHTML = $this->getFormattedProductPrice( $product );
            }
        } else {
            // Formation can be disabled for variable products
            if ( TierPricingTablePlugin::isVariableProductSupported( $product ) && !$this->useForVariable() ) {
                $newPriceHTML = null;
            } else {
                $newPriceHTML = $this->getFormattedProductPrice( $product );
            }
        }
        $newPriceHtml = ( is_null( $newPriceHTML ) ? $defaultPriceHTML : $newPriceHTML );
        return apply_filters(
            'tiered_pricing_table/catalog_pricing/price_html',
            $newPriceHtml,
            $defaultPriceHTML,
            $product
        );
    }

    /**
     * Format price for simple/variation products.
     *
     * @param  WC_Product  $product
     *
     * @return null|string
     */
    protected function getFormattedPriceForSimpleProduct( WC_Product $product ) {
        if ( TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
            $displayPriceType = $this->getDisplayType();
            $pricingRule = PriceManager::getPricingRule( $product->get_id() );
            if ( !empty( $pricingRule->getRules() ) ) {
                if ( 'range' === $displayPriceType ) {
                    return $this->getRange( $pricingRule, $product );
                } else {
                    return $this->getLowestPrice( $pricingRule, $product );
                }
            }
        }
        return null;
    }

    /**
     * Format price for variable product. Range uses lowest and high prices from all variations
     *
     * @param  WC_Product_Variable  $product
     *
     * @return null|string
     */
    protected function getFormattedPriceForVariableProduct( WC_Product_Variable $product ) : ?string {
        // With taxes
        $maxPrice = (float) $product->get_variation_price( 'max', true );
        $minPrices = array((float) $product->get_variation_price( 'min', true ));
        foreach ( $product->get_available_variations() as $variation ) {
            $pricingRule = PriceManager::getPricingRule( (int) $variation['variation_id'] );
            if ( !empty( $pricingRule->getRules() ) ) {
                $minPrices[] = $this->getLowestPrice( $pricingRule, wc_get_product( $variation['variation_id'] ), false );
            }
        }
        // If product has more than 1 min price - that means that some variation has a tiered pricing rule.
        if ( !empty( $minPrices ) && count( $minPrices ) > 1 ) {
            if ( 'range' === $this->getDisplayType() ) {
                if ( min( $minPrices ) === $maxPrice ) {
                    return null;
                }
                return wc_price( min( $minPrices ) ) . ' - ' . wc_price( $maxPrice );
            } else {
                return $this->getLowestPrefix() . ' ' . wc_price( min( $minPrices ) );
            }
        }
        return null;
    }

    /**
     * Get range from lowest to highest price from price rules
     *
     * @param  PricingRule  $pricingRule
     * @param  WC_Product  $product
     *
     * @return string
     */
    protected function getRange( PricingRule $pricingRule, WC_Product $product ) : string {
        $pricingRules = $pricingRule->getRules();
        $lowest = (float) array_pop( $pricingRules );
        $highestHtml = wc_price( wc_get_price_to_display( $product, array(
            'price' => $product->get_price(),
        ) ) );
        if ( $pricingRule->isPercentage() ) {
            $lowest = PriceManager::getProductPriceWithPercentageDiscount( $product, $lowest );
        }
        $lowestHtml = wc_price( wc_get_price_to_display( $product, array(
            'price' => $lowest,
        ) ) );
        $range = $lowestHtml . ' - ' . $highestHtml;
        if ( $lowestHtml !== $highestHtml ) {
            return $range;
        }
        return $lowestHtml;
    }

    /**
     * Get the lowest price from price rules
     *
     * @param  PricingRule  $pricingRule
     * @param  WC_Product  $product
     *
     * @param  bool  $html
     *
     * @return string|float
     */
    protected function getLowestPrice( PricingRule $pricingRule, WC_Product $product, bool $html = true ) {
        $pricingRules = $pricingRule->getRules();
        if ( $pricingRule->isPercentage() ) {
            $lowest = PriceManager::getProductPriceWithPercentageDiscount( $product, (float) array_pop( $pricingRules ) );
        } else {
            $lowest = array_pop( $pricingRules );
        }
        if ( $html ) {
            return $this->getLowestPrefix() . ' ' . wc_price( wc_get_price_to_display( $product, array(
                'price' => $lowest,
            ) ) );
        }
        return wc_get_price_to_display( $product, array(
            'price' => $lowest,
        ) );
    }

    public function getLowestPrefix() : string {
        return (string) $this->getContainer()->getSettings()->get( 'lowest_prefix', __( 'From', 'tier-pricing-table' ) );
    }

    public function isEnabled() : bool {
        return 'yes' === $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog', 'yes' );
    }

    public function getDisplayType() : string {
        return $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog_type', 'range' );
    }

    public function useForVariable() : bool {
        return $this->getContainer()->getSettings()->get( 'tiered_price_at_catalog_for_variable', 'yes' ) === 'yes';
    }

}
