<?php

namespace TierPricingTable\Managers;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WC_Product_Variable;
/**
 * Class FormatPriceManager
 *
 * @package TierPricingTable\Managers
 */
class FormatPriceManager {
    public static function getFormattedPrice( WC_Product $product, array $args = array() ) : ?string {
        $args = wp_parse_args( $args, array(
            'html'               => true,
            'display_type'       => null,
            'use_cache'          => true,
            'for_display'        => true,
            'with_suffix'        => true,
            'with_lowest_prefix' => true,
            'with_default_price' => true,
        ) );
        $priceHTML = 'default';
        if ( 'default' === $priceHTML ) {
            if ( $args['with_default_price'] ) {
                $priceHTML = ( $args['html'] ? $product->get_price_html() : $product->get_price() );
            } else {
                return null;
            }
        }
        return $priceHTML;
    }

    public static function getLowestPrice( WC_Product $product, $args = array() ) : ?string {
        $args = wp_parse_args( $args, array(
            'html'               => true,
            'for_display'        => true,
            'with_suffix'        => true,
            'with_lowest_prefix' => true,
            'with_default_price' => true,
        ) );
        $lowestPrice = null;
        $regularPrice = null;
        // Handle a case when there are no pricing rules
        if ( !is_numeric( $lowestPrice ) || $lowestPrice < 0 || $lowestPrice >= $regularPrice ) {
            if ( !$args['with_default_price'] ) {
                return null;
            }
            $lowest = $product->get_price();
            $lowestPrice = ( $args['for_display'] ? wc_get_price_to_display( $product, array(
                'price' => $lowest,
            ) ) : $lowest );
            return ( $args['html'] ? $product->get_price_html() : $lowestPrice );
        }
        if ( $args['for_display'] ) {
            $lowestPrice = wc_get_price_to_display( $product, array(
                'price' => $lowestPrice,
            ) );
        }
        if ( $args['html'] ) {
            $lowestPrice = wc_price( $lowestPrice );
        }
        if ( $args['with_suffix'] ) {
            $lowestPrice .= $product->get_price_suffix();
        }
        if ( $args['with_lowest_prefix'] ) {
            $lowestPrice = self::getLowestPrefix() . ' ' . $lowestPrice;
        }
        return $lowestPrice;
    }

    public static function getPriceRange( WC_Product $product, array $args = array() ) : ?string {
        $args = wp_parse_args( $args, array(
            'for_display'        => true,
            'with_suffix'        => true,
            'with_default_price' => true,
        ) );
        /**
         * Variable type declaration for PHPStorm
         *
         * @var WC_Product_Variable $product
         */
        $lowestPrice = self::getLowestPrice( $product, array(
            'html'               => false,
            'for_display'        => false,
            'with_lowest_prefix' => false,
            'with_default_price' => false,
            'with_suffix'        => false,
        ) );
        if ( is_null( $lowestPrice ) ) {
            if ( $args['with_default_price'] ) {
                return ( $args['html'] ? $product->get_price_html() : $product->get_price() );
            } else {
                return null;
            }
        }
        if ( TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
            $highestPrice = $product->get_variation_price( 'max' );
        } elseif ( TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
            $highestPrice = $product->get_price();
        } else {
            return ( $args['with_default_price'] ? $product->get_price() : null );
        }
        if ( is_null( $highestPrice ) || $highestPrice === $lowestPrice ) {
            return ( $args['with_default_price'] ? $product->get_price() : null );
        }
        if ( $args['for_display'] ) {
            $lowestPrice = wc_get_price_to_display( $product, array(
                'price' => $lowestPrice,
            ) );
            $highestPrice = wc_get_price_to_display( $product, array(
                'price' => $highestPrice,
            ) );
        }
        $lowestPrice = wc_price( $lowestPrice );
        $highestPrice = wc_price( $highestPrice );
        if ( $lowestPrice === $highestPrice ) {
            return ( $args['with_default_price'] ? $product->get_price() : null );
        }
        $range = $lowestPrice . ' - ' . $highestPrice;
        if ( $args['with_suffix'] ) {
            $range .= $product->get_price_suffix();
        }
        return $range;
    }

    public static function getLowestPrefix() : string {
        $settings = ServiceContainer::getInstance()->getSettings();
        return (string) $settings->get( 'lowest_prefix', __( 'From', 'tier-pricing-table' ) );
    }

    public static function getDisplayType() : string {
        $settings = ServiceContainer::getInstance()->getSettings();
        return ( $settings->get( 'tiered_price_at_catalog_type', 'range' ) === 'range' ? 'range' : 'lowest' );
    }

}
