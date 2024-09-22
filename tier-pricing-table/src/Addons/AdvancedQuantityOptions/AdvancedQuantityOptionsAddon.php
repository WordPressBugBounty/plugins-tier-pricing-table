<?php

namespace TierPricingTable\Addons\AdvancedQuantityOptions;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\MaximumOrderQuantity;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\QuantityStep;
use TierPricingTable\Addons\AdvancedQuantityOptions\ProductEditor\ProductEditor;
class AdvancedQuantityOptionsAddon extends AbstractAddon {
    const MAXIMUM_QUANTITY_BASE_META_KEY = 'maximum_quantity';

    const GROUP_OF_QUANTITY_BASE_META_KEY = 'group_of_quantity';

    public function getName() : string {
        return __( 'Additional product quantity options', 'tier-pricing-table' );
    }

    public function getDescription() : string {
        return __( 'Manage maximum quantity and quantity step.', 'tier-pricing-table' );
    }

    public function getSlug() : string {
        return 'additional-product-quantity-options';
    }

    public function run() {
        $form = new AdvancedQuantityOptionsForm();
        new RoleBasedOptions($form);
        new ProductOptions($form);
        new GlobalPricingOptions($form);
        new ProductEditor();
    }

}