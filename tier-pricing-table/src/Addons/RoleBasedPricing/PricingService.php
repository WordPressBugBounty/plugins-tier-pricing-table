<?php

namespace TierPricingTable\Addons\RoleBasedPricing;

use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
class PricingService {
    public function __construct() {
        /**
         * Main function to filter the tiered pricing rules
         *
         * @priority 20
         */
        add_filter(
            'tiered_pricing_table/price/pricing_rule',
            array($this, 'addPricing'),
            20,
            2
        );
    }

    /**
     * Main function to filter pricing rules with role-based pricing rule data
     *
     * @param  PricingRule  $pricingRule
     * @param $productId
     *
     * @return PricingRule
     */
    public function addPricing( PricingRule $pricingRule, $productId ) : PricingRule {
        $product = wc_get_product( $productId );
        if ( !$product ) {
            return $pricingRule;
        }
        $roleBasedRule = RoleBasedPricingRulesRepository::getInstance()->getCurrentUserRule( $product );
        if ( !$roleBasedRule ) {
            return $pricingRule;
        }
        // the tiers (free tiered pricing types only in the free version): they replace the product's tiers. In the
        // free version a rule without usable tiers leaves the product's tiers as they are.
        $premium = false;
        $tieredPricingType = $roleBasedRule->getTieredPricingType();
        $tieredRules = ( PriceManager::isPricingTypeAvailable( $tieredPricingType ) ? $roleBasedRule->getTieredPricingRules() : array() );
        if ( $premium || !empty( $tieredRules ) ) {
            $pricingRule->setType( $tieredPricingType );
            $pricingRule->setRules( $tieredRules );
        }
        // taxes are free
        $pricingRule->pricingData['tax_status'] = $roleBasedRule->getTaxStatus();
        $pricingRule->pricingData['tax_class'] = $roleBasedRule->getTaxClass();
        $pricingRule->provider = 'role-based';
        $pricingRule->providerData['role'] = $roleBasedRule->getRole();
        $pricingRule->logPricingModification( '[role-based]: Pricing rule is overridden by the rule for: ' . $roleBasedRule->getRole() );
        do_action(
            'tiered_pricing_table/role_based_pricing/after_adjusting_pricing_rule',
            $pricingRule,
            $roleBasedRule,
            $productId
        );
        return $pricingRule;
    }

}
