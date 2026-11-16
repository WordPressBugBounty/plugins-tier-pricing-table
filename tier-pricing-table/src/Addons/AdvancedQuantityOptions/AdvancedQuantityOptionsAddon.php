<?php

namespace TierPricingTable\Addons\AdvancedQuantityOptions;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\MaximumOrderQuantity;
use TierPricingTable\Addons\AdvancedQuantityOptions\API\QuantityStep;
class AdvancedQuantityOptionsAddon extends AbstractAddon {
    const MAXIMUM_QUANTITY_BASE_META_KEY = 'maximum_quantity';

    const GROUP_OF_QUANTITY_BASE_META_KEY = 'group_of_quantity';

    public function getName() : string {
        return __( 'Maximum and quantity step', 'tier-pricing-table' );
    }

    public function getDescription() : string {
        return __( 'Adds a maximum order quantity and a quantity step, such as packs of 10, to products and rules.', 'tier-pricing-table' );
    }

    public function getIcon() : string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>';
    }

    public function getSlug() : string {
        return 'additional-product-quantity-options';
    }

    public function getGroup() : string {
        return self::GROUP_PRICING;
    }

    public function run() {
        $form = new AdvancedQuantityOptionsForm();
        new RoleBasedOptions($form);
        new ProductOptions($form);
        new GlobalPricingOptions($form);
    }

}
