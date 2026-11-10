<?php

namespace TierPricingTable;

use WC_Product;
class PriceManager {
    protected static $pricingRules = array();

    public static function getFixedPriceRules( $productId, string $context = 'view' ) : array {
        return self::getPriceRules( $productId, 'fixed', $context );
    }

    public static function getPercentagePriceRules( $productId, string $context = 'view' ) : array {
        return self::getPriceRules( $productId, 'percentage', $context );
    }

    public static function getAmountPriceRules( $productId, string $context = 'view' ) : array {
        return self::getPriceRules( $productId, 'amount', $context );
    }

    /**
     * The tiered pricing types: "fixed" (a price per unit), "percentage" (a discount off the product
     * price) and "amount" (an amount off the product price per unit).
     *
     * @return string[]
     */
    public static function getPricingTypes() : array {
        return array('fixed', 'percentage', 'amount');
    }

    /**
     * Whether the store's plan may use a tiered pricing type: fixed prices and amount-off tiers are free,
     * percentage discounts are premium.
     */
    public static function isPricingTypeAvailable( string $type ) : bool {
        return 'percentage' !== $type || tpt_fs()->can_use_premium_code();
    }

    /**
     * The pricing type to use for a stored type: an unavailable type falls back to fixed prices.
     */
    public static function getAvailablePricingType( string $type ) : string {
        return ( self::isPricingTypeAvailable( $type ) ? $type : 'fixed' );
    }

    /**
     * The product meta key holding the tiers of a type (prefixed with the role or customer for their rules).
     */
    public static function getRulesMetaKey( string $type ) : string {
        return ( in_array( $type, self::getPricingTypes(), true ) ? "_{$type}_price_rules" : '_fixed_price_rules' );
    }

    public static function getPriceRules( $productId, ?string $type = null, string $context = 'view' ) : array {
        $type = ( $type ? $type : self::getPricingType( $productId, 'fixed', $context ) );
        $rules = (array) get_post_meta( $productId, self::getRulesMetaKey( $type ), true );
        $rules = array_map( function ( $price ) {
            return floatval( $price );
        }, $rules );
        $rules = ( !empty( $rules ) ? array_filter( $rules ) : array() );
        ksort( $rules );
        if ( 'edit' !== $context ) {
            $rules = apply_filters(
                'tiered_pricing_table/price/product_price_rules',
                $rules,
                $productId,
                $type
            );
        }
        return array_filter( $rules, function ( $quantity ) {
            return intval( $quantity ) > 1;
        }, ARRAY_FILTER_USE_KEY );
    }

    /**
     * Get price by product quantity
     *
     * @param  int  $quantity
     * @param  int  $productId
     * @param  ?string  $context
     * @param  ?string  $place
     * @param  bool  $withTaxes
     * @param  ?PricingRule  $pricingRule
     * @param  bool  $roundPrice
     *
     * @return bool|float|int
     */
    public static function getPriceByRules(
        $quantity,
        $productId,
        $context = 'view',
        $place = 'shop',
        bool $withTaxes = true,
        ?PricingRule $pricingRule = null,
        ?bool $roundPrice = null
    ) {
        $pricingRule = ( $pricingRule ? $pricingRule : self::getPricingRule( $productId ) );
        $roundPrice = ( !is_null( $roundPrice ) ? $roundPrice : CalculationLogic::roundPrice() );
        foreach ( array_reverse( $pricingRule->getRules(), true ) as $_amount => $price ) {
            if ( $_amount <= $quantity ) {
                if ( $pricingRule->isPercentage() ) {
                    $product = wc_get_product( $productId );
                    if ( $product ) {
                        $productPrice = self::getProductPriceWithPercentageDiscount( $product, $price, $context );
                    }
                } elseif ( $pricingRule->isAmount() ) {
                    $product = wc_get_product( $productId );
                    if ( $product ) {
                        $productPrice = self::getProductPriceWithAmountDiscount( $product, $price, $context );
                    }
                } else {
                    $productPrice = $price;
                }
                if ( 'view' === $context && $withTaxes ) {
                    $product = wc_get_product( $productId );
                    $productPrice = self::getPriceToDisplay( $productPrice, $product, $place );
                }
                break;
            }
        }
        $productPrice = $productPrice ?? false;
        if ( $productPrice && apply_filters( 'tiered_pricing_table/price/round_price', $roundPrice ) ) {
            $roundPrecision = (int) apply_filters( 'tiered_pricing_table/price/round_precision', max( 2, wc_get_price_decimals() ) );
            $productPrice = round( $productPrice, $roundPrecision );
        }
        if ( 'edit' !== $context ) {
            return apply_filters(
                'tiered_pricing_table/price/price_by_rules',
                $productPrice,
                $quantity,
                $productId,
                $context,
                $place,
                $pricingRule
            );
        }
        return $productPrice;
    }

    /**
     * Calculate displayed price depend on taxes
     *
     * @param  float  $price
     * @param  WC_Product  $product
     * @param  ?string  $displayContext
     *
     * @return ?float
     */
    public static function getPriceToDisplay( $price, WC_Product $product, ?string $displayContext = 'shop' ) : ?float {
        if ( wc_tax_enabled() ) {
            $price = wc_get_price_to_display( $product, array(
                'price'           => $price,
                'qty'             => 1,
                'display_context' => $displayContext,
            ) );
        }
        return floatval( $price );
    }

    /**
     * Calculate price using percentage discount
     *
     * @param  float  $price
     * @param  float  $discount
     *
     * @return bool|float
     */
    public static function getPriceByPercentDiscount( $price, $discount ) {
        if ( $price > 0 && $discount <= 100 ) {
            $discount_amount = $price / 100 * $discount;
            return $price - $discount_amount;
        }
        return false;
    }

    public static function getProductPriceWithPercentageDiscount( WC_Product $product, $discount, $context = 'view' ) {
        $discount = floatval( $discount );
        $productPrice = ( CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $product->get_regular_price( $context ) : $product->get_price( $context ) );
        return self::getPriceByPercentDiscount( $productPrice, $discount );
    }

    /**
     * The price of a product with an amount taken off per unit, never below zero. Starts from the same
     * price as a percentage tier: the sale price, or the regular price when the setting says so.
     *
     * @param  WC_Product  $product
     * @param  float|string  $amount
     * @param  string  $context
     *
     * @return float
     */
    public static function getProductPriceWithAmountDiscount( WC_Product $product, $amount, string $context = 'view' ) : float {
        $productPrice = ( CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $product->get_regular_price( $context ) : $product->get_price( $context ) );
        return self::getPriceByAmountDiscount( $productPrice, $amount );
    }

    /**
     * Take an amount off a price, never below zero. A negative amount is ignored.
     *
     * @param  float|string  $price
     * @param  float|string  $amount
     *
     * @return float
     */
    public static function getPriceByAmountDiscount( $price, $amount ) : float {
        return max( 0.0, (float) $price - max( 0.0, (float) $amount ) );
    }

    public static function getPricingType( $productId, string $default = 'fixed', string $context = 'view' ) : string {
        $type = get_post_meta( $productId, '_tiered_price_rules_type', true );
        $type = ( in_array( $type, self::getPricingTypes(), true ) ? $type : $default );
        $type = self::getAvailablePricingType( $type );
        if ( 'edit' !== $context ) {
            return apply_filters( 'tiered_pricing_table/price/type', $type, $productId );
        }
        return $type;
    }

    /**
     * Update product pricing type
     *
     * @param  int  $productId
     * @param  string  $type
     */
    public static function updatePriceRulesType( $productId, string $type ) {
        if ( in_array( $type, self::getPricingTypes(), true ) ) {
            update_post_meta( $productId, '_tiered_price_rules_type', $type );
        }
    }

    /**
     * Get product minimum quantity
     *
     * @param  int  $productId
     * @param  ?string  $context
     *
     * @return int
     */
    public static function getProductQtyMin( $productId, ?string $context = 'view' ) : ?int {
        return null;
    }

    public static function isProductMixAndMatch( $productId ) {
        $mixAndMatch = get_post_meta( $productId, '_tiered_price_mix_and_match_minimum', true );
        if ( '' !== $mixAndMatch ) {
            return 'yes' === $mixAndMatch;
        }
        $product = wc_get_product( $productId );
        if ( $product && $product->is_type( 'variation' ) ) {
            $parentMixAndMatch = get_post_meta( $product->get_parent_id(), '_tiered_price_mix_and_match_minimum', true );
            if ( '' !== $parentMixAndMatch ) {
                return 'yes' === $parentMixAndMatch;
            }
        }
        return CalculationLogic::isMixAndMatchMinimumEnabled();
    }

    /**
     * Update product minimum quantity
     *
     * @param  int  $productId
     * @param  ?int  $minimum
     */
    public static function updateProductMinimumQuantity( $productId, ?int $minimum ) {
        if ( !$minimum ) {
            delete_post_meta( $productId, '_tiered_price_minimum_qty' );
        } else {
            update_post_meta( $productId, '_tiered_price_minimum_qty', $minimum );
        }
    }

    public static function calculateDiscount( $originalPrice, $currentPrice ) {
        if ( $currentPrice >= $originalPrice ) {
            return 0;
        }
        return 100 * ($originalPrice - $currentPrice) / $originalPrice;
    }

    /**
     * Main function to get pricing information for a product.
     *
     * @param  int  $productId
     * @param  ?string  $tieredPricingType  - 'percentage' or 'fixed'. Leave null to use default.
     *
     * @return PricingRule
     */
    /**
     * Prime the post and meta caches for a list of product IDs in two queries, so the pricing lookups that
     * follow (meta reads, wc_get_product()) hit the cache instead of querying per product.
     *
     * @param array $productIds
     *
     * @return int[] The IDs, as integers, without empty values.
     */
    public static function primeProducts( array $productIds ) : array {
        $productIds = array_values( array_filter( array_map( 'intval', $productIds ) ) );
        if ( !empty( $productIds ) && function_exists( '_prime_post_caches' ) ) {
            _prime_post_caches( $productIds, false, true );
        }
        return $productIds;
    }

    public static function getPricingRule( $productId, ?string $tieredPricingType = null ) : PricingRule {
        /****************************************
         *
         * Object cache
         *
         ****************************************/
        if ( array_key_exists( $productId, self::$pricingRules ) && !$tieredPricingType ) {
            return self::$pricingRules[$productId];
        }
        /****************************************
         *
         * Initialize variables
         *
         ****************************************/
        $product = null;
        $pricingRule = new PricingRule($productId);
        $tieredPricingType = ( $tieredPricingType ? $tieredPricingType : self::getPricingType( $productId ) );
        /****************************************
         *
         * Set tiered pricing for the product
         *
         ****************************************/
        $tieredPricingRules = self::getPriceRules( $productId, $tieredPricingType );
        // If product does not have rules, check parent product
        if ( empty( $tieredPricingRules ) ) {
            $product = wc_get_product( $productId );
            if ( $product && TierPricingTablePlugin::isVariationProductSupported( $product ) ) {
                $tieredPricingType = self::getPricingType( $product->get_parent_id() );
                $tieredPricingRules = self::getPriceRules( $product->get_parent_id(), $tieredPricingType );
                if ( !empty( $tieredPricingRules ) ) {
                    $pricingRule->logPricingModification( 'Using parent product pricing rules' );
                }
            }
        }
        $pricingRule->setRules( $tieredPricingRules );
        $pricingRule->setType( $tieredPricingType );
        /****************************************
         *
         * Set minimum quantity for the product
         *
         ****************************************/
        $minimum = self::getProductQtyMin( $productId );
        $isMixAndMatch = self::isProductMixAndMatch( $productId );
        // If product does not have minimum quantity, check parent product
        if ( !$minimum ) {
            if ( $product && TierPricingTablePlugin::isVariationProductSupported( $product ) ) {
                $parentIsMixAndMatch = $isMixAndMatch;
                if ( !$parentIsMixAndMatch ) {
                    $minimum = self::getProductQtyMin( $product->get_parent_id() );
                    if ( $minimum ) {
                        $pricingRule->logPricingModification( 'Using parent product minimum quantity' );
                    }
                }
            }
        }
        $pricingRule->setMinimum( $minimum );
        $pricingRule->setMixAndMatchMinQuantity( $isMixAndMatch );
        /*****************************************
         *
         * Services that modify pricing rule
         *
         * @hooked QuantityManager - 1:  Added maximum and quantity step information.
         * @hooked CategoryTierAddon - 10:  Filter with category-based rules
         * @hooked RoleBasedPricingAddon - 20:  Filter with role-based rules
         * @hooked RoleBasedPricingAddon - 25:  Filter with customer-based rules
         * @hooked GlobalPricingService - 30:  Filter with global rules
         *
         *****************************************/
        $pricingRule = apply_filters( 'tiered_pricing_table/price/pricing_rule', $pricingRule, $productId );
        self::$pricingRules[$productId] = $pricingRule;
        return $pricingRule;
    }

}
