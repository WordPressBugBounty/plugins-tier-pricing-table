<?php

namespace TierPricingTable\Addons\TaxSettings;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\TaxSettings\API\TaxSettingsEndpoints;
use TierPricingTable\Addons\TaxSettings\Overrides\ProductsTaxService;
use TierPricingTable\Addons\TaxSettings\RoleBasedRulesIntegration\RoleBasedRulesIntegration;
use TierPricingTable\Addons\TaxSettings\Settings\Settings;
use TierPricingTable\Addons\TaxSettings\Overrides\RoleBasedTaxOptions;
class TaxSettingsAddon extends AbstractAddon {
    public function getName() : string {
        return __( 'Tax by role', 'tier-pricing-table' );
    }

    public function getDescription() : string {
        return __( 'Tax-exempt roles, prices shown with or without tax per role, and a tax status and class in role and global rules.', 'tier-pricing-table' );
    }

    public function getIcon() : string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 17H6v-2h12v2zm0-4H6v-2h12v2zm0-4H6V7h12v2zM3 22l1.5-1.5L6 22l1.5-1.5L9 22l1.5-1.5L12 22l1.5-1.5L15 22l1.5-1.5L18 22l1.5-1.5L21 22V2l-1.5 1.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2 7.5 3.5 6 2 4.5 3.5 3 2v20z"/></svg>';
    }

    public function getSlug() : string {
        return 'tax-settings';
    }

    public function getGroup() : string {
        return self::GROUP_PRICING;
    }

    public function isEnabled() : bool {
        if ( get_option( 'woocommerce_calc_taxes' ) !== 'yes' ) {
            return false;
        }
        return parent::isEnabled();
    }

    public function run() {
        new GlobalRulesIntegration\GlobalRules();
        new RoleBasedRulesIntegration();
        new TaxSettingsEndpoints();
        new Settings();
        // the tax settings of global rules are free; the role-based tax options are premium
        new ProductsTaxService();
    }

}
