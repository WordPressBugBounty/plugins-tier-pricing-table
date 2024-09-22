<?php

namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\PricingRule;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\CartOptionsSubsection;
class PricingService {
    public function __construct() {
        /**
         * Main function to filter the tiered pricing rules
         *
         * @priority 30
         */
        add_filter(
            'tiered_pricing_table/price/pricing_rule',
            array($this, 'addPricing'),
            30,
            2
        );
    }

    /**
     * Main function to filter pricing rules with global pricing rule data
     *
     * @param  PricingRule  $pricingRule
     * @param $productId
     *
     * @return PricingRule
     */
    public function addPricing( PricingRule $pricingRule, $productId ) : PricingRule {
        if ( !CartOptionsSubsection::globalRulesOverrideProductLevelRules() ) {
            // Do not modify if there is pricing rules set (in product or role-based or category-based)
            if ( !empty( $pricingRule->getRules() ) || 'product' !== $pricingRule->provider ) {
                return $pricingRule;
            }
        }
        $product = wc_get_product( $productId );
        if ( !$product ) {
            return $pricingRule;
        }
        $globalPricingRule = GlobalPricingRulesRepository::getInstance()->getMatchedPricingRule( $product );
        if ( !$globalPricingRule ) {
            return $pricingRule;
        }
        $pricingRule->setRules( $globalPricingRule->getTieredPricingRules() );
        $pricingRule->setType( $globalPricingRule->getTieredPricingType() );
        $pricingRule->provider = 'global-rules';
        $pricingRule->providerData['rule_id'] = $globalPricingRule->getId();
        $pricingRule->providerData['applying_type'] = $globalPricingRule->getApplyingType();
        return apply_filters(
            'tiered_pricing_table/global_pricing/after_adjusting_pricing_rule',
            $pricingRule,
            $globalPricingRule,
            $productId
        );
    }

}
