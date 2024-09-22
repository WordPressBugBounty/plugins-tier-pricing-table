<?php

namespace TierPricingTable;

use WC_Product;
class PriceManager {
    protected static $types = array();

    protected static $rules = array();

    protected static $pricingRules = array();

    public static function getFixedPriceRules( $productId, string $context = 'view' ) : array {
        return self::getPriceRules( $productId, 'fixed', $context );
    }

    public static function getPercentagePriceRules( $productId, string $context = 'view' ) : array {
        return self::getPriceRules( $productId, 'percentage', $context );
    }

    public static function getPriceRules( $productId, ?string $type = null, string $context = 'view' ) : array {
        if ( 'edit' !== $context && array_key_exists( $productId, self::$rules ) ) {
            return self::$rules[$productId];
        }
        $type = ( $type ? $type : self::getPricingType( $productId, 'fixed', $context ) );
        if ( 'fixed' === $type ) {
            $rules = (array) get_post_meta( $productId, '_fixed_price_rules', true );
        } else {
            $rules = (array) get_post_meta( $productId, '_percentage_price_rules', true );
        }
        $parent_id = $productId;
        // If no rules for variation check for product level rules.
        if ( 'edit' !== $context && self::variationHasNoOwnRules( $productId, $rules ) ) {
            $product = wc_get_product( $productId );
            $parent_id = $product->get_parent_id();
            $type = self::getPricingType( $parent_id );
            if ( 'fixed' === $type ) {
                $rules = get_post_meta( $parent_id, '_fixed_price_rules', true );
            } else {
                $rules = get_post_meta( $parent_id, '_percentage_price_rules', true );
            }
        }
        $rules = ( !empty( $rules ) ? $rules : array() );
        $rules = ( is_array( $rules ) ? array_filter( $rules ) : array() );
        ksort( $rules );
        if ( 'edit' !== $context ) {
            $rules = apply_filters(
                'tiered_pricing_table/price/product_price_rules',
                $rules,
                $productId,
                $type,
                $parent_id
            );
            // Cache
            self::$rules[$productId] = $rules;
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
     *
     * @return bool|float|int
     */
    public static function getPriceByRules(
        $quantity,
        $productId,
        $context = 'view',
        $place = 'shop',
        bool $withTaxes = true,
        PricingRule $pricingRule = null,
        bool $roundPrice = false
    ) {
        $pricingRule = ( $pricingRule ? $pricingRule : self::getPricingRule( $productId ) );
        $roundPrice = ( $roundPrice ? $roundPrice : CalculationLogic::roundPrice() );
        foreach ( array_reverse( $pricingRule->getRules(), true ) as $_amount => $price ) {
            if ( $_amount <= $quantity ) {
                if ( $pricingRule->isPercentage() ) {
                    $product = wc_get_product( $productId );
                    if ( $product ) {
                        $productPrice = self::getProductPriceWithPercentageDiscount( $product, $price );
                    }
                } else {
                    $productPrice = $price;
                }
                if ( 'view' === $context && $withTaxes ) {
                    $product = wc_get_product( $productId );
                    $productPrice = self::getPriceWithTaxes( $productPrice, $product, $place );
                }
                break;
            }
        }
        $productPrice = ( isset( $productPrice ) ? $productPrice : false );
        if ( $productPrice && apply_filters( 'tiered_pricing_table/price/round_price', $roundPrice ) ) {
            $productPrice = round( $productPrice, max( 2, wc_get_price_decimals() ) );
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
     * @param  ?string  $place
     *
     * @return ?float
     */
    public static function getPriceWithTaxes( $price, WC_Product $product, ?string $place = 'shop' ) : ?float {
        if ( wc_tax_enabled() ) {
            if ( 'cart' === $place ) {
                $price = ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ? wc_get_price_including_tax( $product, array(
                    'qty'   => 1,
                    'price' => $price,
                ) ) : wc_get_price_excluding_tax( $product, array(
                    'qty'   => 1,
                    'price' => $price,
                ) ) );
            } else {
                $price = wc_get_price_to_display( $product, array(
                    'price' => $price,
                    'qty'   => 1,
                ) );
            }
        }
        return floatval( $price );
    }

    /**
     * Calculate price using percentage discount
     *
     * @param  float|int  $price
     * @param  float|int  $discount
     *
     * @return bool|float|int
     */
    public static function getPriceByPercentDiscount( $price, $discount ) {
        if ( $price > 0 && $discount <= 100 ) {
            $discount_amount = $price / 100 * $discount;
            return $price - $discount_amount;
        }
        return false;
    }

    public static function getProductPriceWithPercentageDiscount( WC_Product $product, float $discount ) {
        $productPrice = ( CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $product->get_regular_price() : $product->get_price() );
        return self::getPriceByPercentDiscount( $productPrice, $discount );
    }

    public static function getPricingType( $productId, string $default = 'fixed', string $context = 'view' ) : string {
        if ( 'edit' !== $context && array_key_exists( $productId, self::$types ) ) {
            return self::$types[$productId];
        }
        $type = 'fixed';
        $type = ( in_array( $type, array('fixed', 'percentage') ) ? $type : $default );
        if ( 'edit' !== $context ) {
            $type = apply_filters( 'tiered_pricing_table/price/type', $type, $productId );
            // Cache
            self::$types[$productId] = $type;
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
        if ( in_array( $type, array('percentage', 'fixed') ) ) {
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

    /**
     * Check if variation has no own rules
     *
     * @param  int  $productId
     * @param  ?array  $rules
     *
     * @return bool
     */
    protected static function variationHasNoOwnRules( $productId, ?array $rules = array() ) : bool {
        $rules = ( !empty( $rules ) ? $rules : self::getPriceRules( $productId, false, 'edit' ) );
        if ( empty( $rules ) ) {
            $product = wc_get_product( $productId );
            if ( $product ) {
                return $product->is_type( 'variation' );
            }
        }
        return false;
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
    public static function getPricingRule( $productId, ?string $tieredPricingType = null ) : PricingRule {
        // Object cache
        if ( array_key_exists( $productId, self::$pricingRules ) && !$tieredPricingType ) {
            return self::$pricingRules[$productId];
        }
        $pricingRule = new PricingRule($productId);
        if ( $tieredPricingType ) {
            if ( 'percentage' === $tieredPricingType ) {
                $pricingRule->setRules( self::getPercentagePriceRules( $productId ) );
                $pricingRule->setType( 'percentage' );
            } else {
                $pricingRule->setRules( self::getFixedPriceRules( $productId ) );
                $pricingRule->setType( 'fixed' );
            }
        } else {
            $pricingRule->setRules( self::getPriceRules( $productId ) );
            $pricingRule->setType( self::getPricingType( $productId ) );
        }
        $pricingRule->setMinimum( self::getProductQtyMin( $productId ) );
        /**
         * Services that modify pricing rule
         *
         * @hooked QuantityManager - 1:  Added maximum and quantity step information.
         * @hooked CategoryTierAddon - 10:  Filter with category-based rules
         * @hooked RoleBasedPricingAddon - 20:  Filter with role-based rules
         * @hooked GlobalPricingService - 30:  Filter with global rules
         */
        $pricingRule = apply_filters( 'tiered_pricing_table/price/pricing_rule', $pricingRule, $productId );
        self::$pricingRules[$productId] = $pricingRule;
        return $pricingRule;
    }

}
