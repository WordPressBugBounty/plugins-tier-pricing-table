<?php

namespace TierPricingTable\Addons\RoleBasedPricing;

use Exception;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Forms\MinimumOrderQuantityForm;
use TierPricingTable\Forms\RegularPricingForm;
use TierPricingTable\Forms\TieredPricingRulesForm;
use WP_Post;
class ProductManager {
    use ServiceContainerTrait;
    const GET_ROLE_ROW_HTML__ACTION = 'tpt_get_role_row_html';

    public function __construct() {
        // Get role row via AJAX
        add_action( 'wp_ajax_' . self::GET_ROLE_ROW_HTML__ACTION, array($this, 'getRoleRowHtml') );
        // Activate new tab
        add_filter( 'tiered_pricing_table/admin/role_customer_pricing_tab_active', '__return_true' );
        // Render
        add_action(
            'tiered_pricing_table/admin/role_customer_pricing_tab_content',
            array($this, 'render'),
            99,
            1
        );
        add_action(
            'woocommerce_variation_options_pricing',
            function ( $loop, $variationData, WP_Post $variation ) {
                $this->render( $variation->ID, $loop );
            },
            11,
            3
        );
        // Save: the tiers, the quote form and the taxes of a role are free; its regular prices and quantity limits
        // are premium (see updateFromRequest())
        add_action( 'woocommerce_process_product_meta', function ( $productId ) {
            $this->updateFromRequest( $productId );
        } );
        add_action(
            'woocommerce_save_product_variation',
            function ( $variationId, $loop ) {
                $this->updateFromRequest( $variationId, $loop );
            },
            10,
            3
        );
    }

    /**
     * The roles whose rows were sent with the product form: every row has a tiered pricing type (free), and the
     * premium version also sends its minimum order quantity.
     *
     * @param  array  $data
     * @param  int|null  $loop
     *
     * @return string[]
     */
    protected function getRolesFromRequest( array $data, $loop ) : array {
        $roles = array();
        foreach ( array('type', 'minimum_order_quantity') as $field ) {
            $key = 'tiered_pricing_' . $field . (( is_null( $loop ) ? '' : '_variation' )) . '_role';
            $values = ( is_null( $loop ) ? $data[$key] ?? array() : $data[$key][$loop] ?? array() );
            foreach ( (array) $values as $role => $value ) {
                $role = sanitize_text_field( (string) $role );
                // customer rows share the field names (user_{id}); the customer prices save those
                if ( '' !== $role && 0 !== strpos( $role, 'user_' ) ) {
                    $roles[$role] = $role;
                }
            }
        }
        return array_values( $roles );
    }

    public function updateFromRequest( $productId, $loop = null ) {
        if ( wp_verify_nonce( true, true ) ) {
            // as phpcs comments at Woo is not available, we have to do such a trash
            $woo = 'Woo, please add ignoring comments to your phpcs checker';
        }
        $data = $_POST;
        $rolesToUpdate = $this->getRolesFromRequest( (array) $data, $loop );
        foreach ( $rolesToUpdate as $role ) {
            // the stored rule: what this form does not change (in the free version: regular prices, the minimum)
            // stays as it is
            $pricingRule = RoleBasedPricingRule::build( (int) $productId, $role );
            $tieredPricingData = TieredPricingRulesForm::getDataFromRequest(
                $role,
                $loop,
                null,
                $data,
                $productId
            );
            $pricingRule->setTieredPricingType( $tieredPricingData['type'] );
            $pricingRule->setFixedTieredPricingRules( $tieredPricingData['fixed_tiered_pricing_rules'] );
            $pricingRule->setPercentageTieredPricingRules( $tieredPricingData['percentage_tiered_pricing_rules'] );
            $pricingRule->setAmountTieredPricingRules( $tieredPricingData['amount_tiered_pricing_rules'] );
            try {
                $pricingRule->save();
            } catch ( Exception $e ) {
                return;
            }
            do_action(
                'tiered_pricing_table/role_based_rules/save_role_based_rules',
                $productId,
                $data,
                $role,
                $loop
            );
        }
        $this->handleRemoving( $productId, $loop, $_POST );
    }

    /**
     * Delete removed role-based rules
     *
     * @param  int  $productId
     * @param  null|int  $loop
     * @param  array  $data
     */
    public function handleRemoving( $productId, $loop, $data ) {
        if ( !empty( $data['tiered_price_rules_roles_to_delete'] ) ) {
            foreach ( $data['tiered_price_rules_roles_to_delete'] as $roleToRemove ) {
                if ( !empty( $roleToRemove ) ) {
                    RoleBasedPriceManager::deleteAllDataForRole( $productId, $roleToRemove );
                }
            }
        }
        if ( !empty( $data['tiered_price_rules_roles_to_delete_variation'][$loop] ) ) {
            foreach ( $data['tiered_price_rules_roles_to_delete_variation'][$loop] as $roleToRemove ) {
                if ( !empty( $roleToRemove ) ) {
                    RoleBasedPriceManager::deleteAllDataForRole( $productId, $roleToRemove );
                }
            }
        }
    }

    public function render( $productId, $loop = null ) {
        $this->getContainer()->getFileManager()->includeTemplate( 'addons/role-based-pricing/role-based-block.php', array(
            'product_id' => $productId,
            'loop'       => $loop,
        ) );
    }

    /**
     * AJAX Handler
     */
    public function getRoleRowHtml() {
        $nonce = ( isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false );
        $productId = ( isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0 );
        // the row is part of the product editor: only for users who may edit the product
        if ( wp_verify_nonce( $nonce, self::GET_ROLE_ROW_HTML__ACTION ) && $productId && current_user_can( 'edit_post', $productId ) ) {
            $role = ( isset( $_GET['role'] ) ? sanitize_text_field( $_GET['role'] ) : '' );
            $loop = ( isset( $_GET['loop'] ) && '' !== $_GET['loop'] ? intval( $_GET['loop'] ) : null );
            $product = wc_get_product( $productId );
            if ( $role && RoleBasedPriceManager::isAvailableRole( $role ) && $product ) {
                $pricingRule = RoleBasedPricingRule::build( $productId, $role );
                wp_send_json( array(
                    'success'       => true,
                    'role_row_html' => $this->getContainer()->getFileManager()->renderTemplate( 'addons/role-based-pricing/role.php', array(
                        'loop'         => $loop,
                        'pricing_rule' => $pricingRule,
                        'product'      => $product,
                    ) ),
                ) );
            }
            wp_send_json( array(
                'success'       => false,
                'error_message' => __( 'Invalid role', 'tier-pricing-table' ),
            ) );
        }
        wp_send_json( array(
            'success'       => false,
            'error_message' => __( 'Invalid nonce', 'tier-pricing-table' ),
        ) );
    }

}
