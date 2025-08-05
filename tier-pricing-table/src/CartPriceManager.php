<?php

namespace TierPricingTable;

use  TierPricingTable\Core\ServiceContainerTrait ;
use  WC_Cart ;
use  WC_Product ;
/**
 * Class CartPriceManager
 *
 * @package TierPricingTable
 */
class CartPriceManager
{
    use  ServiceContainerTrait ;
    /**
     * CatalogPriceManager constructor.
     */
    public function __construct()
    {
        // Calculate product price in cart by pricing rules
        add_action(
            'woocommerce_before_calculate_totals',
            array( $this, 'calculateTotals' ),
            10,
            3
        );
        add_action(
            'woocommerce_before_mini_cart_contents',
            array( $this, 'miniCartSubTotal' ),
            9999,
            3
        );
        add_filter(
            'woocommerce_cart_item_price',
            array( $this, 'calculateItemPrice' ),
            999,
            2
        );
    }
    
    /**
     * Calculate price by quantity rules
     *
     * @param WC_Cart $cart
     */
    public function calculateTotals( WC_Cart $cart )
    {
        if ( !empty($cart->cart_contents) ) {
            foreach ( $cart->cart_contents as $key => $cartItem ) {
                $needPriceRecalculation = apply_filters( 'tiered_pricing_table/cart/need_price_recalculation', true, $cartItem );
                
                if ( $cartItem['data'] instanceof WC_Product && $needPriceRecalculation ) {
                    $product_id = ( !empty($cartItem['variation_id']) ? $cartItem['variation_id'] : $cartItem['product_id'] );
                    $new_price = PriceManager::getPriceByRules(
                        $this->getTotalProductCount( $cartItem ),
                        $product_id,
                        'calculation',
                        'cart'
                    );
                    $new_price = apply_filters(
                        'tiered_pricing_table/cart/product_cart_price',
                        $new_price,
                        $cartItem,
                        $key
                    );
                    
                    if ( false !== $new_price ) {
                        $cartItem['data']->set_price( $new_price );
                        $cartItem['data']->add_meta_data( 'tiered_pricing_cart_price_calculated', 'yes' );
                    }
                
                }
            
            }
        }
    }
    
    public function miniCartSubTotal()
    {
        $cart = wc()->cart;
        $cart->calculate_totals();
    }
    
    /**
     * Get total product count depend on user's settings
     *
     * @param array $cartItem
     *
     * @return int
     */
    public function getTotalProductCount( $cartItem )
    {
        
        if ( $this->getContainer()->getSettings()->get( 'summarize_variations', 'no' ) !== 'yes' ) {
            $count = $cartItem['quantity'];
        } else {
            $count = 0;
            foreach ( wc()->cart->cart_contents as $cart_content ) {
                if ( $cart_content['product_id'] == $cartItem['product_id'] ) {
                    $count += $cart_content['quantity'];
                }
            }
        }
        
        return (int) apply_filters( 'tiered_pricing_table/cart/total_product_count', $count, $cartItem );
    }
    
    /**
     * Calculate price in mini cart
     *
     * @param string $price
     * @param array $cartItem
     *
     * @return string
     */
    public function calculateItemPrice( $price, $cartItem )
    {
        $needPriceRecalculation = apply_filters( 'tiered_pricing_table/cart/need_price_recalculation/item', true, $cartItem );
        
        if ( $cartItem['data'] instanceof WC_Product && $needPriceRecalculation ) {
            $new_price = PriceManager::getPriceByRules(
                $this->getTotalProductCount( $cartItem ),
                $cartItem['data']->get_id(),
                'view',
                'cart'
            );
            // To get real product price
            $product = wc_get_product( $cartItem['data']->get_id() );
            $new_price = apply_filters( 'tiered_pricing_table/cart/product_cart_price/item', $new_price, $cartItem );
            if ( false !== $new_price ) {
                return wc_price( $new_price );
            }
        }
        
        return $price;
    }

}