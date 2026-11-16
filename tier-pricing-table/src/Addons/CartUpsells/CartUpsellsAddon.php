<?php namespace TierPricingTable\Addons\CartUpsells;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\LayoutConfigurator\LayoutConfiguratorAddon;

class CartUpsellsAddon extends AbstractAddon {
	
	public function getName() {
		return __( 'Next tier message in the cart', 'tier-pricing-table' );
	}
	
	public function getDescription() {
		return __( '"Add 13 more to get $14.50 each" under cart items, with a one-click quantity link and a progress bar.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>';
	}
	
	public function getSlug() {
		return 'cart-upsells';
	}

	public function getGroup(): string {
		return self::GROUP_SHOP_CART;
	}
	
	public function run() {
		$this->getContainer()->initService( CartUpsellsService::class );
		add_filter( 'tiered_pricing_table/settings/general_subsections', array( $this, 'addSettingsSubsection' ) );
	}
	
	public function addSettingsSubsection( $subsections ) {
		// the layout configurator carries these options while it is on
		if ( LayoutConfiguratorAddon::isActive() ) {
			return $subsections;
		}
		
		$subsections[] = UpsellsSubsection::class;
		return $subsections;
	}
}
