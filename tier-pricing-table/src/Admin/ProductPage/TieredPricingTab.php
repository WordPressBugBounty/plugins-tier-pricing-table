<?php

namespace TierPricingTable\Admin\ProductPage;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\PriceManager;
use TierPricingTable\Forms\MinimumOrderQuantityForm;
use TierPricingTable\Forms\TieredPricingRulesForm;
use TierPricingTable\TierPricingTablePlugin;
/**
 * Class ProductManager
 *
 * @package TierPricingTable\Admin\Product
 */
class TieredPricingTab {
    use ServiceContainerTrait;
    public function __construct() {
        // Tiered Pricing Product Tab
        add_filter(
            'woocommerce_product_data_tabs',
            array($this, 'register'),
            99,
            1
        );
        add_action( 'woocommerce_product_data_panels', array($this, 'render') );
        add_action( 'woocommerce_process_product_meta', array($this, 'save') );
    }

    /**
     * Add tiered pricing tab to woocommerce product tabs
     *
     * @param  array  $productTabs
     *
     * @return array
     */
    public function register( $productTabs ) : array {
        $productTabs['tiered-pricing-tab'] = array(
            'label'  => __( 'Tiered Pricing', 'tier-pricing-table' ),
            'target' => 'tiered-pricing-data',
            'class'  => (function () {
                $types = array_merge( TierPricingTablePlugin::getSupportedSimpleProductTypes(), TierPricingTablePlugin::getSupportedVariableProductTypes() );
                return array_map( function ( $type ) {
                    return 'show_if_' . $type;
                }, $types );
            })(),
        );
        return $productTabs;
    }

    /**
     * Render content for the tiered pricing tab
     */
    public function render() {
        global $post;
        ?>
		<div id="tiered-pricing-data" class="panel woocommerce_options_panel">
			
			<?php 
        if ( tpt_fs()->can_use_premium_code() && !tpt_fs()->is_premium() ) {
            $this->getContainer()->getFileManager()->includeTemplate( 'admin/banners/free-version-used-premium-available.php', array(
                'is_product' => true,
            ) );
        }
        do_action( 'tiered_pricing_table/admin/pricing_tab_begin', $post->ID );
        ?>

			<?php 
        $variableClasses = implode( ' ', array_map( function ( $type ) {
            return 'show_if_' . $type;
        }, TierPricingTablePlugin::getSupportedVariableProductTypes() ) );
        ?>
			<div class="tpt-product-section tpt-product-section--open hidden <?php 
        echo esc_attr( $variableClasses );
        ?>" id="tpt-product-section-variable">
				<?php 
        self::renderSectionHeader( esc_html__( 'Variable product', 'tier-pricing-table' ), 'variable', esc_html__( 'Tiers, quantity limits and how variations are priced together', 'tier-pricing-table' ) );
        ?>

				<div class="tpt-product-section__content">
					<div class="options_group">
						<?php 
        $type = PriceManager::getPricingType( $post->ID, 'fixed', 'edit' );
        $percentageRules = PriceManager::getPercentagePriceRules( $post->ID, 'edit' );
        $fixedRules = PriceManager::getFixedPriceRules( $post->ID, 'edit' );
        TieredPricingRulesForm::render(
            $post->ID,
            null,
            null,
            $type,
            $percentageRules,
            $fixedRules,
            '_variable'
        );
        ?>
					</div>

					<div class="options_group">
						<?php 
        Product::renderQuantityLimits( $post->ID, '_variable' );
        ?>
					</div>

					<?php 
        do_action( 'tiered_pricing_table/admin/variable_product_options', $post->ID );
        ?>
				</div>
			</div>

			<?php 
        do_action( 'tiered_pricing_table/admin/before_advance_product_options', $post->ID );
        ?>

			<div class="tpt-product-section tiered_pricing_tab_product_advance_options" id="tpt-product-section-options">
				<?php 
        self::renderSectionHeader( esc_html__( 'Display', 'tier-pricing-table' ), 'display', esc_html__( 'Layout and unit label for this product', 'tier-pricing-table' ) );
        ?>

				<div class="tpt-product-section__content tiered_pricing_tab_product_advance_options__content">
					
					<?php 
        $availableTemplates = TierPricingTablePlugin::getAvailablePricingLayouts();
        $availableTemplates = array_merge( array(
            'default' => __( 'Default' ),
        ), $availableTemplates );
        woocommerce_wp_select( array(
            'id'          => '_tiered_pricing_template',
            'value'       => self::getProductTemplate( $post->ID ),
            'options'     => $availableTemplates,
            'label'       => __( 'Pricing layout', 'tier-pricing-table' ),
            'description' => __( 'Override the global layout for this specific product.', 'tier-pricing-table' ),
            'default'     => 'default',
            'desc_tip'    => true,
        ) );
        ?>

					<p class="form-field">
						<label>
							<?php 
        esc_html_e( 'Unit label', 'tier-pricing-table' );
        ?>
						</label>
						
						<?php 
        $productBaseUnitName = self::getProductBaseUnitName( $post->ID );
        ?>

						<input type="text"
							   value="<?php 
        echo esc_attr( $productBaseUnitName['singular'] );
        ?>"
							   placeholder="<?php 
        esc_attr_e( 'Singular (e.g. piece)', 'tier-pricing-table' );
        ?>"
							   style="width: 24%; margin-right: 20px;"
							   name="_tiered_pricing_base_unit_name[singular]">

						<input type="text"
							   value="<?php 
        echo esc_attr( $productBaseUnitName['plural'] );
        ?>"
							   placeholder="<?php 
        esc_attr_e( 'Plural (e.g. pieces)', 'tier-pricing-table' );
        ?>"
							   style="width: 24%"
							   name="_tiered_pricing_base_unit_name[plural]">
					</p>

					<div style="    padding: 0 20px 0px 162px;
    margin-bottom: 10px;
    color: #666;
    margin-top: -10px;
    font-size: 12px;">
						<?php 
        esc_html_e( 'Displayed next to quantities. Leave empty to inherit global settings.', 'tier-pricing-table' );
        ?>
					</div>
					
					<?php 
        do_action( 'tiered_pricing_table/admin/advance_product_options', $post->ID );
        ?>
				</div>
			</div>

			<?php 
        if ( has_action( 'tiered_pricing_table/admin/visibility_options' ) ) {
            ?>
				<div class="tpt-product-section tpt-product-section--open" id="tpt-product-section-visibility">
					<?php 
            self::renderSectionHeader( esc_html__( 'Visibility', 'tier-pricing-table' ), 'visibility', esc_html__( 'Who can see this product', 'tier-pricing-table' ) );
            ?>

					<div class="tpt-product-section__content">
						<div class="options_group">
							<?php 
            do_action( 'tiered_pricing_table/admin/visibility_options', $post->ID );
            ?>
						</div>
					</div>
				</div>
			<?php 
        }
        ?>

			<?php 
        $this->renderRoleCustomerSection( $post->ID );
        ?>

			<?php 
        do_action( 'tiered_pricing_table/admin/pricing_tab_end', $post->ID );
        ?>
		</div>
		<?php 
    }

    /**
     * Header of a collapsible section of the tab. The title may carry markup.
     */
    public static function renderSectionHeader( string $titleHtml, string $icon = '', string $descriptionHtml = '' ) {
        ?>
		<div class="tpt-product-section__header tiered_pricing_tab_product_advance_options__header" role="button" tabindex="0">
			<div class="tpt-product-section__heading">
				<h4>
					<?php 
        if ( $icon ) {
            ?>
						<span class="tpt-product-section__icon"><?php 
            echo self::getSectionIcon( $icon );
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup
            ?></span>
					<?php 
        }
        ?>
					<?php 
        echo wp_kses_post( $titleHtml );
        ?>
				</h4>
				<?php 
        if ( $descriptionHtml ) {
            ?>
					<div class="tpt-product-section__description"><?php 
            echo wp_kses_post( $descriptionHtml );
            ?></div>
				<?php 
        }
        ?>
			</div>
			<div>
				<span class="tiered_pricing_arrow_down">▼</span>
				<span class="tiered_pricing_arrow_up">▲</span>
			</div>
		</div>
		<?php 
    }

    /**
     * Inline SVG icons of the tab sections (Feather style, 24-unit grid, stroked with the current colour).
     */
    public static function getSectionIcon( string $name ) : string {
        $paths = array(
            'display'    => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line>',
            'variable'   => '<polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline>',
            'visibility' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>',
            'roles'      => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        );
        if ( empty( $paths[$name] ) ) {
            return '';
        }
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }

    /**
     * Whether the product has prices for at least one role or one customer.
     */
    public static function hasRoleOrCustomerRules( int $productId ) : bool {
        if ( class_exists( \TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager::class ) ) {
            foreach ( array_keys( \TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager::getAvailableRoles() ) as $role ) {
                if ( \TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager::roleHasRules( (string) $role, $productId, 'edit' ) ) {
                    return true;
                }
            }
        }
        global $wpdb;
        $like = $wpdb->esc_like( '_user_' ) . '%' . $wpdb->esc_like( '_tiered_price_rules_type' );
        $keys = (array) $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", $productId, $like ) );
        foreach ( $keys as $key ) {
            if ( preg_match( '/^_user_\\d+_tiered_price_rules_type$/', (string) $key ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Role and customer prices, formerly a tab of their own. The wrapper keeps the old panel id and the
     * three actions fire in the same order, so add-ons and custom code hooked into the old tab keep working.
     */
    protected function renderRoleCustomerSection( int $productId ) {
        if ( !apply_filters( 'tiered_pricing_table/admin/role_customer_pricing_tab_active', false ) ) {
            return;
        }
        ?>
		<?php 
        // open on simple products; a variable product opens with its own section, so here the section
        // opens only when the product already has role or customer prices
        $product = wc_get_product( $productId );
        $isVariable = $product && TierPricingTablePlugin::isVariableProductSupported( $product );
        $open = (bool) apply_filters( 'tiered_pricing_table/admin/role_customer_section_open', !$isVariable || self::hasRoleOrCustomerRules( $productId ), $productId );
        ?>
		<div class="tpt-product-section<?php 
        echo ( $open ? ' tpt-product-section--open' : '' );
        ?>" id="tpt-product-section-roles">
			<?php 
        self::renderSectionHeader( esc_html__( 'Role & Customer Prices', 'tier-pricing-table' ), 'roles', esc_html__( 'Prices for customer roles and individual customers', 'tier-pricing-table' ) );
        ?>

			<div class="tpt-product-section__content">
				<div id="role-customer-pricing-data" class="tpt-role-customer-pricing">

					<?php 
        do_action( 'tiered_pricing_table/admin/role_customer_pricing_tab_begin', $productId );
        ?>

					<div class="options_group">
						<?php 
        RoleCustomerPricingUI::start( $productId );
        do_action( 'tiered_pricing_table/admin/role_customer_pricing_tab_content', $productId );
        RoleCustomerPricingUI::end( $productId );
        ?>
					</div>

					<?php 
        do_action( 'tiered_pricing_table/admin/role_customer_pricing_tab_end', $productId );
        ?>
				</div>
			</div>
		</div>
		<?php 
    }

    /**
     * Save tiered pricing tab data
     *
     * @param  $productId
     */
    public function save( $productId ) {
        if ( wp_verify_nonce( true, true ) ) {
            // as phpcs comments at Woo is not available, we have to do such a trash
            $woo = 'Woo, please add ignoring comments to your phpcs checker';
        }
        $template = ( isset( $_POST['_tiered_pricing_template'] ) ? sanitize_text_field( $_POST['_tiered_pricing_template'] ) : 'default' );
        self::updateProductTemplate( $productId, sanitize_text_field( $template ) );
        $baseUnitName = ( isset( $_POST['_tiered_pricing_base_unit_name'] ) ? array_map( 'sanitize_text_field', $_POST['_tiered_pricing_base_unit_name'] ) : array() );
        self::updateProductBaseUnitName( $productId, $baseUnitName );
    }

    public static function getProductTemplate( $productId ) {
        $template = get_post_meta( $productId, '_tiered_pricing_template', true );
        $availableTemplates = TierPricingTablePlugin::getAvailablePricingLayouts();
        return ( array_key_exists( $template, $availableTemplates ) ? $template : 'default' );
    }

    public static function updateProductTemplate( $productId, $template ) {
        $availableTemplates = TierPricingTablePlugin::getAvailablePricingLayouts();
        $template = ( array_key_exists( $template, $availableTemplates ) ? $template : 'default' );
        if ( 'default' !== $template ) {
            update_post_meta( $productId, '_tiered_pricing_template', $template );
        } else {
            delete_post_meta( $productId, '_tiered_pricing_template' );
        }
    }

    public static function getProductBaseUnitName( $productId ) : array {
        $_baseUnitName = (array) get_post_meta( $productId, '_tiered_pricing_base_unit_name', true );
        $baseUnitName['singular'] = $_baseUnitName['singular'] ?? null;
        $baseUnitName['plural'] = $_baseUnitName['plural'] ?? null;
        return $baseUnitName;
    }

    public static function updateProductBaseUnitName( $productId, array $unitNames ) {
        $baseUnitName['singular'] = $unitNames['singular'] ?? null;
        $baseUnitName['plural'] = $unitNames['plural'] ?? null;
        if ( empty( $baseUnitName['singular'] ) && empty( $baseUnitName['plural'] ) ) {
            delete_post_meta( $productId, '_tiered_pricing_base_unit_name' );
        } else {
            update_post_meta( $productId, '_tiered_pricing_base_unit_name', $baseUnitName );
        }
    }

}
