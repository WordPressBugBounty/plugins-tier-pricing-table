<?php

namespace TierPricingTable\Admin\ProductManagers;

use  TierPricingTable\PriceManager ;
use  TierPricingTable\TierPricingTablePlugin ;
use  WP_Post ;
/**
 * Class VariationProduct
 *
 * @package TierPricingTable\Admin\Product
 */
class VariationProductManager extends ProductManagerAbstract
{
    /**
     * Register hooks
     */
    protected function hooks()
    {
        new AdvanceOptionsForVariableProduct();
        add_action(
            'woocommerce_variation_options_pricing',
            [ $this, 'renderPriceRules' ],
            10,
            3
        );
        add_action(
            'woocommerce_save_product_variation',
            [ $this, 'updatePriceRules' ],
            10,
            3
        );
    }
    
    /**
     * Update price quantity rules for variation product
     *
     * @param int $variation_id
     * @param int $loop
     */
    public function updatePriceRules( $variation_id, $loop )
    {
        
        if ( isset( $_POST['tiered_price_fixed_quantity'][$loop] ) ) {
            $amounts = $_POST['tiered_price_fixed_quantity'][$loop];
            $prices = ( !empty($_POST['tiered_price_fixed_price'][$loop]) ? $_POST['tiered_price_fixed_price'][$loop] : [] );
            PriceManager::updateFixedPriceRules( $amounts, $prices, $variation_id );
        }
    
    }
    
    /**
     * Render inputs for price rules on variation
     *
     * @param int $loop
     * @param array $variation_data
     * @param WP_Post $variation
     */
    public function renderPriceRules( $loop, $variation_data, $variation )
    {
        $this->getContainer()->getFileManager()->includeTemplate( 'admin/add-price-rules-variation.php', [
            'price_rules_fixed'      => PriceManager::getFixedPriceRules( $variation->ID, 'edit' ),
            'price_rules_percentage' => PriceManager::getPercentagePriceRules( $variation->ID, 'edit' ),
            'i'                      => $loop,
            'minimum'                => PriceManager::getProductQtyMin( $variation->ID, 'edit' ),
            'variation_data'         => $variation_data,
            'type'                   => PriceManager::getPricingType( $variation->ID ),
            'isFree'                 => !tpt_fs()->is_premium(),
        ] );
    }

}