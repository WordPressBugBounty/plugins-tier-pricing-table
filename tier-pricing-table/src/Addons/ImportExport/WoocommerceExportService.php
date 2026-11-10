<?php

namespace TierPricingTable\Addons\ImportExport;

use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
/**
 * Class WooCommerce Export
 */
class WoocommerceExportService {
    /**
     * Export constructor.
     */
    public function __construct() {
        add_filter(
            'woocommerce_product_export_column_names',
            array($this, 'addExportColumn'),
            1,
            10
        );
        add_filter(
            'woocommerce_product_export_product_default_columns',
            array($this, 'addExportColumn'),
            1,
            10
        );
        add_filter(
            'woocommerce_product_export_product_column_tiered_price_fixed',
            array($this, 'addExportFixedData'),
            10,
            2
        );
        add_filter(
            'woocommerce_product_export_product_column_tiered_price_percentage',
            array($this, 'addExportPercentageData'),
            10,
            2
        );
        add_filter(
            'woocommerce_product_export_product_column_tiered_price_amount',
            array($this, 'addExportAmountData'),
            10,
            2
        );
        add_filter(
            'woocommerce_product_export_product_column_tiered_price_type',
            array($this, 'addExportPricingTypeData'),
            10,
            2
        );
        add_filter(
            'woocommerce_product_export_product_column_tiered_price_minimum',
            array($this, 'addExportPricingMinimumData'),
            10,
            2
        );
    }

    /**
     * Register the 'Fixed tiered price' column in the exporter.
     *
     * @param  array  $columns
     *
     * @return array $options
     */
    public function addExportColumn( $columns ) {
        // Fixed prices, amount-off tiers and the type are free; percentage tiers and the minimum are premium.
        $columns['tiered_price_fixed'] = 'Tiered Pricing — ' . __( 'Fixed pricing rules', 'tier-pricing-table' );
        $columns['tiered_price_amount'] = 'Tiered Pricing — ' . __( 'Amount pricing rules', 'tier-pricing-table' );
        $columns['tiered_price_type'] = 'Tiered Pricing — ' . __( 'Tiered pricing type', 'tier-pricing-table' );
        return $columns;
    }

    /**
     * Provide the data to be exported for one item in the column.
     *
     * @param  WC_Product  $product
     * @param  string  $type
     *
     * @return mixed $value
     */
    public function addExportData( $product, $type = 'fixed' ) {
        if ( 'percentage' == $type ) {
            $tiered_pricing = PriceManager::getPercentagePriceRules( $product->get_id(), 'edit' );
        } elseif ( 'amount' === $type ) {
            $tiered_pricing = PriceManager::getAmountPriceRules( $product->get_id(), 'edit' );
        } else {
            $tiered_pricing = PriceManager::getFixedPriceRules( $product->get_id(), 'edit' );
        }
        $str = '';
        foreach ( $tiered_pricing as $quantity => $price ) {
            $str .= $quantity . TierPricingTablePlugin::getRuleSeparator() . $price . ',';
        }
        return ( mb_strlen( $str ) > 0 ? trim( $str, ',' ) : null );
    }

    /**
     * Export fixed pricing rules
     *
     * @param  mixed  $value
     * @param  WC_product  $product
     *
     * @return mixed
     */
    public function addExportFixedData( $value, $product ) {
        return $this->addExportData( $product, 'fixed' );
    }

    /**
     * Export percentage pricing rules
     *
     * @param  mixed  $value
     * @param  WC_product  $product
     *
     * @return mixed
     */
    public function addExportPercentageData( $value, $product ) {
        return $this->addExportData( $product, 'percentage' );
    }

    /**
     * Export amount-off pricing rules
     *
     * @param  mixed  $value
     * @param  WC_product  $product
     *
     * @return mixed
     */
    public function addExportAmountData( $value, $product ) {
        return $this->addExportData( $product, 'amount' );
    }

    /**
     * Export tiered pricing type
     *
     * @param  mixed  $value
     * @param  WC_product  $product
     *
     * @return mixed
     */
    public function addExportPricingTypeData( $value, $product ) {
        $type = PriceManager::getPricingType( $product->get_id(), false, 'edit' );
        return ( $type ? $type : '' );
    }

    /**
     * Export tiered pricing minimum product quantity
     *
     * @param  mixed  $value
     * @param  WC_product  $product
     *
     * @return mixed
     */
    public function addExportPricingMinimumData( $value, $product ) {
        return PriceManager::getProductQtyMin( $product->get_id(), 'edit' );
    }

}
