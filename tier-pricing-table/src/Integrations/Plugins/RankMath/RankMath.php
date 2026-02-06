<?php

namespace TierPricingTable\Integrations\Plugins\RankMath;

use RankMath\Helpers\Param;
use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Managers\FormatPriceManager;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
class RankMath extends PluginIntegrationAbstract {
    protected $product = null;

    public function getTitle() : string {
        return 'Rank Math SEO';
    }

    public function getDescription() : string {
        return __( 'Enhance the product schema with tiered pricing offers and adds <b>%lowest_price%</b> and <b>%price_range%</b> variables to display the lowest price and price range of products with tiered pricing.', 'tier-pricing-table' );
    }

    public function getSlug() : string {
        return 'rank-math';
    }

    public function run() {
        add_action( 'plugins_loaded', function () {
            if ( !class_exists( 'RankMath\\Helpers\\Param' ) || !function_exists( 'rank_math_register_var_replacement' ) ) {
                return;
            }
            add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
                $sections[] = new Settings();
                return $sections;
            } );
        } );
    }

    protected function getProductJSONldOffers() : array {
    }

    public function getIntegrationCategory() : string {
        return 'seo';
    }

    public function getIconURL() : ?string {
        return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/rank-math-icon.svg' );
    }

    public function getPriceRange() : ?string {
        $product = $this->get_product();
        if ( !$product ) {
            return '';
        }
        $price = FormatPriceManager::getFormattedPrice( $product, array(
            'for_display'        => true,
            'with_suffix'        => false,
            'with_default_price' => true,
            'with_lowest_prefix' => false,
            'html'               => true,
            'display_type'       => 'range',
        ) );
        return ( $price ? $price : '' );
    }

    public function getLowestPrice() : ?string {
        $product = $this->get_product();
        if ( !$product ) {
            return '';
        }
        $price = FormatPriceManager::getFormattedPrice( $product, array(
            'for_display'        => true,
            'with_suffix'        => false,
            'with_default_price' => true,
            'with_lowest_prefix' => false,
            'html'               => true,
            'display_type'       => 'lowest_price',
        ) );
        return ( $price ? $price : '' );
    }

    public function get_product() {
        if ( !is_null( $this->product ) ) {
            return $this->product;
        }
        if ( !class_exists( 'RankMath\\Helpers\\Param' ) ) {
            return null;
        }
        $product_id = Param::get( 'post', get_queried_object_id(), FILTER_VALIDATE_INT );
        $this->product = ( !function_exists( 'wc_get_product' ) || !$product_id || !is_admin() && !is_singular( 'product' ) ? null : wc_get_product( $product_id ) );
        return $this->product;
    }

    public function isVariablesEnabled() : bool {
        return $this->getContainer()->getSettings()->get( 'rank_math_enable_variables', 'yes' ) === 'yes';
    }

    public function isEnhancedSchemaEnabled() : bool {
        return $this->getContainer()->getSettings()->get( 'rank_math_enhance_schema', 'no' ) === 'yes';
    }

}
