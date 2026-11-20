<?php namespace TierPricingTable\Addons\UserBasedPricing;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\RoleCustomerPricing\RoleCustomerPricingAddon;

class UserBasedPricingAddon extends AbstractAddon {
	
	const SETTING_ENABLE_KEY = 'enable_user_based_pricing_addon';
	
	public function getName(): string {
		return __( 'Customer prices on products', 'tier-pricing-table' );
	}
	
	public function isActive(): bool {
		return $this->getContainer()->getSettings()->get( self::SETTING_ENABLE_KEY, 'yes' ) === 'yes';
	}
	
	public function getDescription(): string {
		return __( 'Give one customer their own price, tiers and quantity limits on a product, for negotiated accounts.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
	}
	
	public function getSlug(): string {
		return 'user-based-rules';
	}

	public function getGroup(): string {
		return self::GROUP_PRICING;
	}

	/**
	 * No card of its own: the "Role and customer prices" module switches it.
	 */
	public function addToAddonsSettings( $addons ) {
		return $addons;
	}

	public function isEnabled(): bool {
		return RoleCustomerPricingAddon::isPartEnabled( RoleCustomerPricingAddon::CUSTOMER_PART );
	}
	
	public function run() {
		// Enable pricing service
		add_filter( 'tiered_pricing_table/services/pricing_service_enabled', '__return_true' );
		
		// customer tiers are free; regular prices and quantity limits are premium (inside the service)
		new PricingService();
		
		new ProductManager();
	}
}
