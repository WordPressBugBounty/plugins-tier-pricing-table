<?php

namespace TierPricingTable\Addons\AdvancedQuantityOptions;

use TierPricingTable\Admin\ProductPage\Product;
class ProductOptions {
    /**
     * Form
     *
     * @var AdvancedQuantityOptionsForm
     */
    protected $form;

    public function __construct( AdvancedQuantityOptionsForm $form ) {
        $this->form = $form;
        // Rendering
        add_action(
            'tiered_pricing_table/admin/after_minimum_order_quantity_field',
            function ( $productId, $loop, $customPrefix = '' ) {
                $this->form->render(
                    $productId,
                    $loop,
                    null,
                    false,
                    true,
                    (string) $customPrefix
                );
            },
            10,
            3
        );
        add_action(
            'tiered_pricing_table/admin/after_variation_minimum_order_quantity_field',
            array($this->form, 'render'),
            10,
            2
        );
    }

    public function save( $productId, $loop = null ) {
        $prefix = Product::getSubmittedPrefix( $loop );
        DataProvider::updateFromRequest(
            'maximum',
            $productId,
            null,
            $loop,
            $prefix
        );
        DataProvider::updateFromRequest(
            'group_of',
            $productId,
            null,
            $loop,
            $prefix
        );
    }

}
