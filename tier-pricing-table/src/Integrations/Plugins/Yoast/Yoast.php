<?php

namespace TierPricingTable\Integrations\Plugins\Yoast;

use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Managers\FormatPriceManager;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
class Yoast extends PluginIntegrationAbstract {
    protected $product = null;

    public function getTitle() : string {
        return 'Yoast SEO';
    }

    public function getDescription() : string {
        return __( 'Adds <b>%%lowest_price%%</b> and <b>%%price_range%%</b> variables to display the lowest and price range of products with tiered pricing.', 'tier-pricing-table' );
    }

    public function getSlug() : string {
        return 'yoast-seo';
    }

    public function run() {
        add_action( 'plugins_loaded', function () {
            if ( !class_exists( 'WPSEO_Options' ) ) {
                return;
            }
            add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
                $sections[] = new Settings();
                return $sections;
            } );
        } );
    }

    public function getIntegrationCategory() : string {
        return 'seo';
    }

    public function getIconURL() : ?string {
        return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/yoast-icon.gif' );
    }

    public function get_product() {
        if ( !is_null( $this->product ) ) {
            return $this->product;
        }
        $product_id = get_queried_object_id();
        $this->product = ( !function_exists( 'wc_get_product' ) || !$product_id || !is_admin() && !is_singular( 'product' ) ? null : wc_get_product( $product_id ) );
        return $this->product;
    }

    public function isVariablesEnabled() : bool {
        return $this->getContainer()->getSettings()->get( 'yoast_enable_variables', 'yes' ) === 'yes';
    }

    public function isEnhancedSchemaEnabled() : bool {
        return $this->getContainer()->getSettings()->get( 'yoast_enhance_schema', 'no' ) === 'yes';
    }

}
