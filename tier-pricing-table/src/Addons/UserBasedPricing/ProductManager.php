<?php

namespace TierPricingTable\Addons\UserBasedPricing;

use Exception;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Forms\MinimumOrderQuantityForm;
use TierPricingTable\Forms\RegularPricingForm;
use TierPricingTable\Forms\TieredPricingRulesForm;
use WP_Post;
class ProductManager {
    use ServiceContainerTrait;
    const GET_USER_ROW_HTML__ACTION = 'tpt_get_user_row_html';

    public function __construct() {
        // Get user row via AJAX
        add_action( 'wp_ajax_' . self::GET_USER_ROW_HTML__ACTION, array($this, 'getUserRowHtml') );
        // Activate new tab
        add_filter( 'tiered_pricing_table/admin/role_customer_pricing_tab_active', '__return_true' );
        // Render (priority 100 to show after role-based pricing)
        add_action(
            'tiered_pricing_table/admin/role_customer_pricing_tab_content',
            array($this, 'render'),
            100,
            1
        );
        add_action(
            'woocommerce_variation_options_pricing',
            function ( $loop, $variationData, WP_Post $variation ) {
                $this->render( $variation->ID, $loop );
            },
            12,
            3
        );
        // Save: the tiers, the quote form and the taxes of a customer are free; their regular prices and quantity
        // limits are premium (see updateFromRequest())
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
     * The customers whose rows were sent with the product form (field keys user_{id}): every row has a tiered
     * pricing type (free), and the premium version also sends its minimum order quantity.
     *
     * @param  array  $data
     * @param  int|null  $loop
     *
     * @return int[]
     */
    protected function getUsersFromRequest( array $data, $loop ) : array {
        $users = array();
        foreach ( array('type', 'minimum_order_quantity') as $field ) {
            $key = 'tiered_pricing_' . $field . (( is_null( $loop ) ? '' : '_variation' )) . '_role';
            $values = ( is_null( $loop ) ? $data[$key] ?? array() : $data[$key][$loop] ?? array() );
            foreach ( (array) $values as $userKey => $value ) {
                if ( 0 === strpos( (string) $userKey, 'user_' ) ) {
                    $userId = (int) substr( (string) $userKey, 5 );
                    if ( $userId > 0 ) {
                        $users[$userId] = $userId;
                    }
                }
            }
        }
        return array_values( $users );
    }

    public function updateFromRequest( $productId, $loop = null ) {
        if ( wp_verify_nonce( true, true ) ) {
            // as phpcs comments at Woo is not available, we have to do such a trash
            $woo = 'Woo, please add ignoring comments to your phpcs checker';
        }
        $data = $_POST;
        $usersToUpdate = $this->getUsersFromRequest( (array) $data, $loop );
        foreach ( $usersToUpdate as $userId ) {
            $pricingRule = UserBasedPricingRule::build( (int) $productId, $userId );
            $roleKey = 'user_' . $userId;
            // the stored rule: what this form does not change (in the free version: regular prices, the minimum)
            // stays as it is
            $tieredPricingData = TieredPricingRulesForm::getDataFromRequest(
                $roleKey,
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
                'tiered_pricing_table/user_based_rules/save_user_based_rules',
                $productId,
                $data,
                $userId,
                $loop
            );
        }
        $this->handleRemoving( $productId, $loop, $_POST );
    }

    /**
     * Delete removed user-based rules
     *
     * @param  int  $productId
     * @param  null|int  $loop
     * @param  array  $data
     */
    public function handleRemoving( $productId, $loop, $data ) {
        if ( !empty( $data['tiered_price_rules_users_to_delete'] ) ) {
            foreach ( $data['tiered_price_rules_users_to_delete'] as $userToRemove ) {
                if ( !empty( $userToRemove ) ) {
                    UserBasedPriceManager::deleteAllDataForUser( $productId, (int) $userToRemove );
                }
            }
        }
        if ( !empty( $data['tiered_price_rules_users_to_delete_variation'][$loop] ) ) {
            foreach ( $data['tiered_price_rules_users_to_delete_variation'][$loop] as $userToRemove ) {
                if ( !empty( $userToRemove ) ) {
                    UserBasedPriceManager::deleteAllDataForUser( $productId, (int) $userToRemove );
                }
            }
        }
    }

    public function render( $productId, $loop = null ) {
        $this->getContainer()->getFileManager()->includeTemplate( 'addons/user-based-pricing/user-based-block.php', array(
            'product_id' => $productId,
            'loop'       => $loop,
        ) );
    }

    /**
     * AJAX Handler
     */
    public function getUserRowHtml() {
        $nonce = ( isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false );
        $productId = ( isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0 );
        // the row is part of the product editor: only for users who may edit the product
        if ( wp_verify_nonce( $nonce, self::GET_USER_ROW_HTML__ACTION ) && $productId && current_user_can( 'edit_post', $productId ) ) {
            $userId = ( isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0 );
            $loop = ( isset( $_GET['loop'] ) && '' !== $_GET['loop'] ? intval( $_GET['loop'] ) : null );
            $user = get_userdata( $userId );
            $product = wc_get_product( $productId );
            if ( $user && $product ) {
                $pricingRule = UserBasedPricingRule::build( $productId, $userId );
                wp_send_json( array(
                    'success'       => true,
                    'user_row_html' => $this->getContainer()->getFileManager()->renderTemplate( 'addons/user-based-pricing/user.php', array(
                        'loop'         => $loop,
                        'pricing_rule' => $pricingRule,
                        'product'      => $product,
                        'user'         => $user,
                    ) ),
                ) );
            }
            wp_send_json( array(
                'success'       => false,
                'error_message' => __( 'Invalid user', 'tier-pricing-table' ),
            ) );
        }
        wp_send_json( array(
            'success'       => false,
            'error_message' => __( 'Invalid nonce', 'tier-pricing-table' ),
        ) );
    }

}
