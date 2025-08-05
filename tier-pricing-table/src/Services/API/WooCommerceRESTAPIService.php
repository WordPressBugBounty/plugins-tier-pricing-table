<?php

namespace TierPricingTable\Services\API;

use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingRule;
use TierPricingTable\PriceManager;
use WC_Product;
class WooCommerceRESTAPIService {
    public function __construct() {
        add_action( 'rest_api_init', function () {
            $supportedAPIProductTypes = apply_filters( 'tiered_pricing_table/api/supported_product_types', array('product', 'product_variation') );
            foreach ( $supportedAPIProductTypes as $productType ) {
                register_rest_field( $productType, 'tiered_pricing_type', array(
                    'get_callback'    => function ( $product ) {
                        return PriceManager::getPricingType( $product['id'] );
                    },
                    'update_callback' => function ( $value, WC_Product $object ) {
                        if ( in_array( $value, array('fixed', 'percentage') ) ) {
                            PriceManager::updatePriceRulesType( $object->get_id(), $value );
                        }
                    },
                    'schema'          => array(
                        'description' => __( 'Tiered Pricing type. can be either "percentage" or "fixed"', 'tier-pricing-table' ),
                        'type'        => 'string',
                        'context'     => array('view', 'edit'),
                    ),
                ) );
                register_rest_field( $productType, 'tiered_pricing_fixed_rules', array(
                    'get_callback'    => function ( $product ) {
                        return PriceManager::getFixedPriceRules( $product['id'], 'edit' );
                    },
                    'update_callback' => function ( $value, WC_Product $object ) {
                        update_post_meta( $object->get_id(), '_fixed_price_rules', $value );
                    },
                    'schema'          => array(
                        'description' => __( 'Tiered Pricing fixed rules. The format is the following: "quantity:price". For example, "10:20,5:40" means 10$ per piece if users buy 20pcs and 5$ per piece if users buy 40pcs', 'tier-pricing-table' ),
                        'type'        => 'object',
                        'context'     => array('view', 'edit'),
                    ),
                ) );
            }
        } );
    }

    protected function decodeRules( $data ) : array {
        $rules = ( is_array( $data ) ? $data : explode( ',', $data ) );
        $data = array();
        if ( $rules ) {
            foreach ( $rules as $rule ) {
                $rule = explode( ':', $rule );
                if ( isset( $rule[0] ) && isset( $rule[1] ) ) {
                    $data[intval( $rule[0] )] = $rule[1];
                }
            }
        }
        $data = array_filter( $data );
        return ( !empty( $data ) ? $data : array() );
    }

}
