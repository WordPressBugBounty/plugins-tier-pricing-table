<?php

namespace TierPricingTable\Admin\ProductManagers;

use  TierPricingTable\PriceManager ;
use  WC_Product ;
/**
 * Class ProductManager
 *
 * @package TierPricingTable\Admin\Product
 */
class ProductManager extends ProductManagerAbstract
{
    /**
     * Register hooks
     */
    protected function hooks()
    {
        // Tiered Pricing Product Tab
        add_filter(
            'woocommerce_product_data_tabs',
            array( $this, 'registerTieredPricingProductTab' ),
            99,
            1
        );
        add_action( 'woocommerce_product_data_panels', array( $this, 'renderTieredPricingTab' ) );
        // Simple Product
        add_action( 'woocommerce_product_options_pricing', array( $this, 'renderPriceRules' ) );
        // Saving
        add_action( 'woocommerce_process_product_meta', array( $this, 'updatePriceRules' ) );
    }
    
    /**
     * Add tiered pricing tab to woocommerce product tabs
     *
     * @param  array  $productTabs
     *
     * @return array
     */
    public function registerTieredPricingProductTab( $productTabs )
    {
        $productTabs['tiered-pricing-tab'] = array(
            'label'  => __( 'Tiered Pricing', 'tier-pricing-table' ),
            'target' => 'tiered-pricing-data',
            'class'  => array( 'show_if_simple', 'show_if_variable' ),
        );
        return $productTabs;
    }
    
    /**
     * Render content for the tiered pricing tab
     */
    public function renderTieredPricingTab()
    {
        global  $post ;
        $min = PriceManager::getProductQtyMin( $post->ID, 'edit' );
        $desc = __( 'Set if you sell the product only from a specific quantity that is more than 1', 'tier-pricing-table' );
        $tip = true;
        $customAttrs = array(
            'min'  => 1,
            'step' => 1,
        );
        
        if ( !tpt_fs()->is_premium() ) {
            $desc = '<span style="color:red"> ' . __( 'Available only in the premium version', 'tier-pricing-table' ) . ' </span> <a target="_blank"
            href="' . esc_url( tpt_fs_activation_url() ) . '">' . __( 'Upgrade', 'tier-pricing-table' ) . '</a>';
            $tip = false;
            $customAttrs['disabled'] = true;
        }
        
        ?>
		<div id="tiered-pricing-data" class="panel woocommerce_options_panel">
			<?php 
        do_action( 'tiered_pricing_table/admin/pricing_tab_begin', $post->ID );
        woocommerce_wp_text_input( array(
            'id'                => '_tiered_pricing_minimum',
            'wrapper_class'     => 'show_if_simple show_if_variable',
            'type'              => 'number',
            'custom_attributes' => $customAttrs,
            'value'             => $min,
            'label'             => __( 'Minimum quantity', 'tier-pricing-table' ),
            'description'       => $desc,
            'default'           => 1,
            'desc_tip'          => $tip,
        ) );
        ?>

			<div class="hidden show_if_variable">
				<?php 
        $type = PriceManager::getPricingType( $post->ID, 'fixed', 'edit' );
        $this->getContainer()->getFileManager()->includeTemplate( 'admin/add-price-rules.php', array(
            'price_rules_fixed'      => PriceManager::getFixedPriceRules( $post->ID, 'edit' ),
            'price_rules_percentage' => PriceManager::getPercentagePriceRules( $post->ID, 'edit' ),
            'type'                   => $type,
            'prefix'                 => 'variable',
            'isFree'                 => !tpt_fs()->is_premium(),
        ) );
        ?>
			</div>

			<?php 
        woocommerce_wp_select( array(
            'id'          => '_tiered_pricing_template',
            'value'       => self::getProductTemplate( $post->ID ),
            'options'     => array(
            'default'  => __( 'Default', 'tier-pricing-table' ),
            'table'    => __( 'Table', 'tier-pricing-table' ),
            'blocks'   => __( 'Blocks', 'tier-pricing-table' ),
            'options'  => __( 'Options', 'tier-pricing-table' ),
            'dropdown' => __( 'Dropdown', 'tier-pricing-table' ),
            'tooltip'  => __( 'Tooltip', 'tier-pricing-table' ),
        ),
            'label'       => __( 'Layout', 'tier-pricing-table' ),
            'description' => __( 'Specify a tiered pricing layout for the product. Leave the default to use the default layout from the settings', 'tier-pricing-table' ),
            'default'     => 'default',
            'desc_tip'    => true,
        ) );
        ?>

			<?php 
        do_action( 'tiered_pricing_table/admin/before_advance_product_options', $post->ID );
        ?>

			<?php 
        
        if ( apply_filters( 'tiered_pricing_table/admin/has_advance_product_options', false, $post ) ) {
            ?>
				<div class="tiered_pricing_tab_product_advance_options">
					<div class="tiered_pricing_tab_product_advance_options__header">
						<h4>
							<?php 
            esc_html_e( 'Advanced settings', 'tier-pricing-table' );
            ?>
						</h4>
						<div>
							<span class="tiered_pricing_arrow_down">▼</span>
							<span class="tiered_pricing_arrow_up">▲</span>
						</div>
					</div>

					<div class="tiered_pricing_tab_product_advance_options__content">
						<?php 
            do_action( 'tiered_pricing_table/admin/advance_product_options', $post->ID );
            ?>
					</div>
				</div>
			<?php 
        }
        
        ?>

			<?php 
        do_action( 'tiered_pricing_table/admin/pricing_tab_end', $post->ID );
        ?>

		</div>
		<?php 
    }
    
    /**
     * Update price quantity rules for simple product
     *
     * @param  int  $productId
     */
    public function updatePriceRules( $productId )
    {
        $data = $_POST;
        $prefix = ( isset( $data['product-type'] ) && in_array( $data['product-type'], array( 'simple', 'variable' ) ) ? sanitize_text_field( $data['product-type'] ) : 'simple' );
        $fixedAmounts = ( isset( $data['tiered_price_fixed_quantity_' . $prefix] ) ? (array) $data['tiered_price_fixed_quantity_' . $prefix] : array() );
        $fixedPrices = ( !empty($data['tiered_price_fixed_price_' . $prefix]) ? (array) $data['tiered_price_fixed_price_' . $prefix] : array() );
        PriceManager::updateFixedPriceRules( $fixedAmounts, $fixedPrices, $productId );
        self::updateProductTemplate( $productId, ( isset( $data['_tiered_pricing_template'] ) ? $data['_tiered_pricing_template'] : 'default' ) );
    }
    
    /**
     * Render inputs for price rules on a simple product
     *
     * @global WC_Product $product_object
     */
    public function renderPriceRules()
    {
        global  $product_object ;
        
        if ( $product_object instanceof WC_Product ) {
            $type = PriceManager::getPricingType( $product_object->get_id(), 'fixed', 'edit' );
            $this->getContainer()->getFileManager()->includeTemplate( 'admin/add-price-rules.php', array(
                'price_rules_fixed'      => PriceManager::getFixedPriceRules( $product_object->get_id(), 'edit' ),
                'price_rules_percentage' => PriceManager::getPercentagePriceRules( $product_object->get_id(), 'edit' ),
                'type'                   => $type,
                'prefix'                 => 'simple',
                'isFree'                 => !tpt_fs()->is_premium(),
            ) );
        }
    
    }
    
    public static function getProductTemplate( $productId )
    {
        $template = get_post_meta( $productId, '_tiered_pricing_template', true );
        return ( in_array( $template, array(
            'default',
            'table',
            'blocks',
            'dropdown',
            'tooltip',
            'options'
        ) ) ? $template : 'default' );
    }
    
    public static function updateProductTemplate( $productId, $template )
    {
        $template = ( in_array( $template, array(
            'default',
            'table',
            'blocks',
            'dropdown',
            'tooltip',
            'options'
        ) ) ? $template : 'default' );
        update_post_meta( $productId, '_tiered_pricing_template', $template );
    }

}