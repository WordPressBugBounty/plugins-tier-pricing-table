<?php namespace TierPricingTable\Addons\TierLabels;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\TierLabels\Admin\TierLabelsAdmin;
use TierPricingTable\Addons\TierLabels\API\TierLabelsEndpoints;
use TierPricingTable\Addons\TierLabels\Frontend\DisplayManager;
use TierPricingTable\Addons\TierLabels\Settings\Settings;

class TierLabelsAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Tier labels', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Add customizable labels to pricing tiers to highlight offers like “Best value” or “Most popular”. Control label text, color, and style, and assign them to specific tiers.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'tier-labels';
	}
	
	public function run() {
		
		$manager = TierLabelsManager::getInstance();
		
		new TierLabelsEndpoints();
		new TierLabelsAdmin( $manager );
		new Settings();
		new DisplayManager();
	}
}