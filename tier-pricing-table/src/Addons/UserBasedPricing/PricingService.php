<?php

namespace TierPricingTable\Addons\UserBasedPricing;

use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
class PricingService {
    public function __construct() {
        /**
         * Main function to filter the tiered pricing rules
         *
         * @priority 25 (Runs after RoleBasedPricing which is 20)
         */
        add_filter(
            'tiered_pricing_table/price/pricing_rule',
            array($this, 'addPricing'),
            25,
            2
        );
    }

    /**
     * Main function to filter pricing rules with user-based pricing rule data
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
        $userBasedRule = UserBasedPricingRulesRepository::getInstance()->getCurrentUserRule( $product );
        if ( !$userBasedRule ) {
            return $pricingRule;
        }
        // the tiers (free tiered pricing types only in the free version): they replace the product's tiers. In the
        // free version a rule without usable tiers leaves the product's tiers as they are.
        $premium = false;
        $tieredPricingType = $userBasedRule->getTieredPricingType();
        $tieredRules = ( PriceManager::isPricingTypeAvailable( $tieredPricingType ) ? $userBasedRule->getTieredPricingRules() : array() );
        if ( $premium || !empty( $tieredRules ) ) {
            $pricingRule->setType( $tieredPricingType );
            $pricingRule->setRules( $tieredRules );
        }
        // taxes are free
        $pricingRule->pricingData['tax_status'] = $userBasedRule->getTaxStatus();
        $pricingRule->pricingData['tax_class'] = $userBasedRule->getTaxClass();
        $pricingRule->provider = 'user-based';
        $pricingRule->providerData['user'] = $userBasedRule->getUserId();
        $pricingRule->logPricingModification( '[user-based]: Pricing rule is overridden by the rule for user ID: ' . $userBasedRule->getUserId() );
        do_action(
            'tiered_pricing_table/user_based_pricing/after_adjusting_pricing_rule',
            $pricingRule,
            $userBasedRule,
            $productId
        );
        return $pricingRule;
    }

}
