<?php namespace TierPricingTable\Addons\PricingSummary;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\LayoutConfigurator\LayoutConfiguratorAddon;

class PricingSummaryAddon extends AbstractAddon {
	
	public function getName() {
		return __( 'Pricing summary', 'tier-pricing-table' );
	}
	
	public function getDescription() {
		return __( 'A quantity × price block with the total near the pricing table, updated as the quantity changes.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>';
	}
	
	public function getSlug() {
		return 'pricing-summary';
	}

	public function getGroup(): string {
		return self::GROUP_PRODUCT_PAGE;
	}
	
	public function run() {
		$this->getContainer()->initService( PricingSummaryService::class );
		add_filter( 'tiered_pricing_table/settings/general_subsections', array( $this, 'addSettingsSubsection' ) );
	}
	
	public function addSettingsSubsection( $subsections ) {
		// the layout configurator carries these options while it is on
		if ( LayoutConfiguratorAddon::isActive() ) {
			return $subsections;
		}
		
		$subsections[] = SummarySubsection::class;
		return $subsections;
	}
}
