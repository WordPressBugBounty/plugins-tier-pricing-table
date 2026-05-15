<?php

namespace TierPricingTable\Integrations\Plugins\SEOPress;

use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;
use TierPricingTable\Managers\FormatPriceManager;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
class SEOPress extends PluginIntegrationAbstract {
    protected $product = null;

    public function getTitle() : string {
        return 'SEOPress';
    }

    public function getDescription() : string {
        return __( 'Adds <b>%%lowest_price%%</b> and <b>%%price_range%%</b> variables to display the lowest and price range of products with tiered pricing.', 'tier-pricing-table' );
    }

    public function getSlug() : string {
        return 'seopress';
    }

    public function run() {
        add_action( 'plugins_loaded', function () {
            if ( !function_exists( 'seopress_init' ) ) {
                return;
            }
            add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
                // Ensure you create a corresponding Settings class for SEOPress in this namespace
                $sections[] = new Settings();
                return $sections;
            } );
        } );
    }

    public function getIntegrationCategory() : string {
        return 'seo';
    }

    public function getPrice( $type ) : ?string {
        $product = $this->get_product();
        if ( !$product ) {
            return '';
        }
        return FormatPriceManager::getFormattedPrice( $product, array(
            'for_display'        => true,
            'with_suffix'        => false,
            'with_default_price' => true,
            'with_lowest_prefix' => false,
            'html'               => false,
            'display_type'       => $type,
        ) );
    }

    public function getIconURL() : ?string {
        // Make sure to add a seopress-icon.png/gif to your assets
        return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/seopress-icon.gif' );
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
        return $this->getContainer()->getSettings()->get( 'seopress_enable_variables', 'yes' ) === 'yes';
    }

    public function isEnhancedSchemaEnabled() : bool {
        return $this->getContainer()->getSettings()->get( 'seopress_enhance_schema', 'no' ) === 'yes';
    }

}
