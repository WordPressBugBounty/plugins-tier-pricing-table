<?php

namespace TierPricingTable\Frontend;

use  Exception ;
use  TierPricingTable\Admin\ProductManagers\ProductManager ;
use  TierPricingTable\Core\ServiceContainerTrait ;
use  TierPricingTable\PriceManager ;
use  TierPricingTable\PricingTable ;
use  TierPricingTable\Settings\Sections\GeneralSection\Subsections\ProductPagePriceSubsection ;
use  TierPricingTable\TierPricingTablePlugin ;
use  WC_Product ;
use  WC_Product_Data_Store_CPT ;
/**
 * Class ProductPageHandler
 *
 * @package TierPricingTable\Frontend
 */
class ProductPageHandler
{
    use  ServiceContainerTrait ;
    public function __construct()
    {
        // Wrap price
        add_action(
            'woocommerce_get_price_html',
            array( $this, 'wrapPriceOnProductPage' ),
            10,
            2
        );
        // Render price table
        add_action( $this->getContainer()->getSettings()->get( 'position_hook', 'woocommerce_before_add_to_cart_button' ), [ $this, 'renderPricingTableOnProductPage' ], -999 );
        // Get table for variation
        add_action(
            'wc_ajax_get_pricing_table',
            array( $this, 'getVariationPricingTable' ),
            10,
            1
        );
        // Render tooltip if enabled
        add_filter(
            'woocommerce_get_price_html',
            array( $this, 'renderTooltip' ),
            999,
            2
        );
        add_filter(
            'woocommerce_get_price_suffix',
            function (
            $suffix,
            \WC_product $product,
            $price,
            $qty
        ) {
            if ( empty($suffix) ) {
                return $suffix;
            }
            $html = '';
            $suffix = get_option( 'woocommerce_price_display_suffix' );
            
            if ( $suffix && wc_tax_enabled() && 'taxable' === $product->get_tax_status() ) {
                if ( '' === $price ) {
                    $price = $product->get_price();
                }
                $replacements = array(
                    '{price_including_tax}' => '<span class="tiered-pricing-dynamic-price__including_tax">' . wc_price( wc_get_price_including_tax( $product, array(
                    'qty'   => $qty,
                    'price' => $price,
                ) ) ) . '</span>',
                    '{price_excluding_tax}' => '<span class="tiered-pricing-dynamic-price__excluding_tax">' . wc_price( wc_get_price_excluding_tax( $product, array(
                    'qty'   => $qty,
                    'price' => $price,
                ) ) ) . '</span>',
                );
                $html = str_replace( array_keys( $replacements ), array_values( $replacements ), ' <small class="woocommerce-price-suffix">' . wp_kses_post( $suffix ) . '</small>' );
            }
            
            return $html;
        },
            10,
            4
        );
    }
    
    /**
     * Wrap product price for managing it by JS
     *
     * @param  string  $priceHTML
     * @param  WC_Product  $product
     *
     * @return string
     */
    public function wrapPriceOnProductPage( $priceHTML, WC_Product $product )
    {
        // Wrapping shouldn't work elsewhere except the product page
        if ( !is_product() ) {
            return $priceHTML;
        }
        $parentProductID = ( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() );
        // Do not wrap any prices except the current product page product
        if ( $parentProductID !== get_queried_object_id() ) {
            return $priceHTML;
        }
        // Do not wrap prices if tiered pricing price formatting enabled on the product page.
        if ( 'same_as_catalog' === ProductPagePriceSubsection::getFormatPriceType() && $product->get_type() !== 'variation' ) {
            return $priceHTML;
        }
        if ( !apply_filters(
            'tiered_pricing_table/frontend/wrap_price',
            true,
            $product,
            $priceHTML
        ) ) {
            return $priceHTML;
        }
        $isVariable = in_array( $product->get_type(), TierPricingTablePlugin::getSupportedVariableProductTypes() );
        $pricingRules = PriceManager::getPricingRule( $product->get_id() );
        // Do not wrap if there is no pricing rules
        if ( !$isVariable && empty($pricingRules->getRules()) ) {
            return $priceHTML;
        }
        $supportedTypes = array_merge( TierPricingTablePlugin::getSupportedSimpleProductTypes(), array( 'variation' ) );
        $wrapVariableProductPrice = apply_filters( 'tiered_pricing_table/frontend/wrap_variable_price', true, $product );
        // Is "show total price" is enabled, we can wrap the variable product price or it's forced by the hook
        if ( $wrapVariableProductPrice || $this->getContainer()->getSettings()->get( 'show_total_price', 'no' ) === 'yes' ) {
            $supportedTypes = array_merge( $supportedTypes, TierPricingTablePlugin::getSupportedVariableProductTypes() );
        }
        if ( in_array( $product->get_type(), $supportedTypes ) ) {
            return '<span class="tiered-pricing-dynamic-price-wrapper' . (( $isVariable ? ' tiered-pricing-dynamic-price-wrapper--variable' : '' )) . '" data-product-id="' . $product->get_id() . '">' . $priceHTML . '</span>';
        }
        return $priceHTML;
    }
    
    /**
     *  Render a pricing table on the product page
     */
    public function renderPricingTableOnProductPage()
    {
        global  $post ;
        if ( !$post ) {
            return;
        }
        $variationId = $this->getVariationIdFromURL( $post->ID );
        PricingTable::getInstance()->renderPricingTable( $post->ID, $variationId );
    }
    
    public function getVariationIdFromURL( $productId )
    {
        $attributes = [];
        $attributeWordLen = strlen( 'attribute_' );
        foreach ( $_REQUEST as $key => $value ) {
            if ( strlen( $key ) < $attributeWordLen ) {
                continue;
            }
            $string = substr( $key, 0, $attributeWordLen );
            if ( strcasecmp( $string, 'attribute_' ) === 0 ) {
                $attributes[$key] = $value;
            }
        }
        if ( empty($attributes) ) {
            return 0;
        }
        $product = wc_get_product( $productId );
        if ( !$product || !in_array( $product->get_type(), TierPricingTablePlugin::getSupportedVariableProductTypes() ) ) {
            return 0;
        }
        $productDataStore = new WC_Product_Data_Store_CPT();
        return $productDataStore->find_matching_product_variation( $product, $attributes );
    }
    
    /**
     * Render tooltip near product price if selected display type is "tooltip"
     *
     * @param  string  $price
     * @param  WC_Product  $_product
     *
     * @return string
     */
    public function renderTooltip( $price, $_product )
    {
        // Do not render if not display
        if ( 'yes' !== $this->getContainer()->getSettings()->get( 'display', 'yes' ) ) {
            return $price;
        }
        $displayType = ProductManager::getProductTemplate( ( $_product->get_parent_id() ? $_product->get_parent_id() : $_product->get_id() ) );
        $displayType = ( $displayType === 'default' ? $this->getContainer()->getSettings()->get( 'display_type', 'table' ) : $displayType );
        // Do not display if display type is not the tooltip
        if ( 'tooltip' !== $displayType ) {
            return $price;
        }
        
        if ( is_product() ) {
            $addTooltip = false;
            $page_product_id = get_queried_object_id();
            
            if ( $_product->is_type( 'variation' ) && $_product->get_parent_id() === $page_product_id ) {
                $addTooltip = true;
            } elseif ( $_product->get_id() === $page_product_id && TierPricingTablePlugin::isSimpleProductSupported( $_product ) ) {
                $addTooltip = true;
            }
            
            if ( !$addTooltip ) {
                return $price;
            }
            $pricingRule = PriceManager::getPricingRule( $_product->get_id() );
            if ( !empty($pricingRule->getRules()) ) {
                return $price . $this->getContainer()->getFileManager()->renderTemplate( 'frontend/tooltip.php', array(
                    'color' => $this->getContainer()->getSettings()->get( 'tooltip_color', '#96598A' ),
                    'size'  => $this->getContainer()->getSettings()->get( 'tooltip_size', 15 ) . 'px',
                ) );
            }
        }
        
        return $price;
    }
    
    /**
     * Fired when user choose some variation. Render price rules table for it if it exists
     *
     * @throws Exception
     * @global WP_Post $post .
     */
    public function getVariationPricingTable()
    {
        $product_id = ( isset( $_POST['variation_id'] ) ? sanitize_text_field( $_POST['variation_id'] ) : false );
        $nonce = ( isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : false );
        
        if ( wp_verify_nonce( $nonce, 'get_pricing_table' ) ) {
            $product = wc_get_product( $product_id );
            
            if ( $product ) {
                $parentProduct = wc_get_product( $product->get_parent_id() );
                PricingTable::getInstance()->renderPricingTableHTML( $parentProduct, $product );
            }
        
        }
    
    }

}