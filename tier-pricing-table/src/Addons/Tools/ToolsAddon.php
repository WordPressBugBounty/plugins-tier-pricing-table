<?php namespace TierPricingTable\Addons\Tools;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\Tools\API\ToolsEndpoints;
use TierPricingTable\Addons\Tools\Settings\Settings;

class ToolsAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Tools', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'A collection of useful tools to manage your tiered pricing data. Includes cleanup utilities and more.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'tools';
	}
	
	public function run() {
		new ToolsEndpoints();
		new Settings();
	}
}
