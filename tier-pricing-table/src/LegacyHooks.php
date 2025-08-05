<?php namespace TierPricingTable;

class LegacyHooks {

	public function __construct() {

		add_filter( 'tiered_pricing_table/price/pricing_rule', function ( PricingRule $pricingRule, $productId ) {
			$hasDeprecated = false;

			if ( has_action( 'tier_pricing_table/price/minimum' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/price/minimum', '3.0.0', 'tiered_pricing_table/price/pricing_rule' );
				$hasDeprecated = true;
			}

			if ( has_action( 'tier_pricing_table/price/type' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/price/type', '3.0.0', 'tiered_pricing_table/price/pricing_rule' );
				$hasDeprecated = true;
			}

			if ( has_action( 'tier_pricing_table/price/product_price_rules' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/price/product_price_rules', '3.0.0', 'tiered_pricing_table/price/pricing_rule' );
				$hasDeprecated = true;
			}

			if ( ! $hasDeprecated ) {
				return $pricingRule;
			}

			$rules       = $pricingRule->getRules();
			$pricingType = $pricingRule->getType();
			$minimum     = $pricingRule->getMinimum();

			$minimum     = apply_filters( 'tier_pricing_table/price/minimum', $minimum, $productId, $productId );
			$pricingType = apply_filters( 'tier_pricing_table/price/type', $pricingType, $productId, 'view' );
			$rules       = apply_filters( 'tier_pricing_table/price/product_price_rules', $rules, $productId, $pricingType, $productId );

			$pricingRule->setRules( $rules );
			$pricingRule->setType( $pricingType );
			$pricingRule->setMinimum( $minimum );

			return $pricingRule;
		}, 99, 2 );

		add_filter( 'tiered_pricing_table/supported_simple_product_types', function ( $types ) {
			if ( has_action( 'tier_pricing_table/supported_simple_product_types' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/supported_simple_product_types', '3.0.0', 'tiered_pricing_table/supported_simple_product_types' );
			} else {
				return $types;
			}

			$types = apply_filters( 'tier_pricing_table/supported_simple_product_types', $types );

			return $types;
		} );

		add_filter( 'tiered_pricing_table/supported_variable_product_types', function ( $types ) {
			if ( has_action( 'tier_pricing_table/supported_variable_product_types' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/supported_variable_product_types', '3.0.0', 'tiered_pricing_table/supported_variable_product_types' );
			} else {
				return $types;
			}

			$types = apply_filters( 'tier_pricing_table/supported_variable_product_types', $types );

			return $types;
		} );

		add_filter( 'tiered_pricing_table/price/price_by_rules', function ( $productPrice, $quantity, $product_id, $context ) {
			if ( has_action( 'tier_pricing_table/price/price_by_rules' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/price/price_by_rules', '3.0.0', 'tiered_pricing_table/price/price_by_rules' );
			} else {
				return $productPrice;
			}

			return apply_filters( 'tier_pricing_table/price/price_by_rules', $productPrice, $quantity, $product_id, $context );
		}, 999, 5 );


		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', function ( $needRecalculation, $cartItem ) {
			if ( has_action( 'tier_pricing_table/cart/need_price_recalculation' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/cart/need_price_recalculation', '3.0.0', 'tiered_pricing_table/cart/need_price_recalculation' );
			} else {
				return $needRecalculation;
			}

			/**
			 * Backwards compatibility
			 *
			 * @since 5.0.2
			 */
			$needRecalculation = apply_filters( 'tier_pricing_table/cart/need_price_recalculation', $needRecalculation, $cartItem );

			return $needRecalculation;
		}, 999, 2 );

		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item', function ( $needRecalculation, $cartItem ) {
			if ( has_action( 'tier_pricing_table/cart/need_price_recalculation/item' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/cart/need_price_recalculation/item', '3.0.0', 'tiered_pricing_table/cart/need_price_recalculation/item' );
			} else {
				return $needRecalculation;
			}

			$needRecalculation = apply_filters( 'tier_pricing_table/cart/need_price_recalculation/item', $needRecalculation, $cartItem );

			return $needRecalculation;
		}, 999, 2 );

		add_filter( 'tiered_pricing_table/cart/total_product_count', function ( $count, $cartItem ) {
			if ( has_action( 'tier_pricing_table/cart/total_product_count' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/cart/total_product_count', '3.0.0', 'tiered_pricing_table/cart/total_product_count' );
			} else {
				return $count;
			}

			return apply_filters( 'tier_pricing_table/cart/total_product_count', $count, $cartItem );
		}, 999, 2 );

		add_filter( 'tiered_pricing_table/cart/product_cart_price', function ( $new_price, $cartItem, $key ) {
			if ( has_action( 'tier_pricing_table/cart/product_cart_price' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/cart/product_cart_price', '3.0.0', 'tiered_pricing_table/cart/product_cart_price' );
			} else {
				return $new_price;
			}

			return apply_filters( 'tier_pricing_table/cart/product_cart_price', $new_price, $cartItem, $key );
		}, 999, 3 );

		add_filter( 'tiered_pricing_table/cart/product_cart_price/item', function ( $new_price, $cartItem ) {
			if ( has_action( 'tier_pricing_table/cart/product_cart_price/item' ) ) {
				wc_deprecated_hook( 'tier_pricing_table/cart/product_cart_price/item', '3.0.0', 'tiered_pricing_table/cart/product_cart_price/item' );
			} else {
				return $new_price;
			}

			return apply_filters( 'tier_pricing_table/cart/product_cart_price/item', $new_price, $cartItem );
		}, 999, 2 );
	}
}