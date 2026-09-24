<?php namespace TierPricingTable\Addons\LayoutConfigurator;

use TierPricingTable\Addons\AbstractAddon;

/**
 * Layout configurator add-on: the visual editor for the product-page pricing layout with a live
 * preview. Switched off, the settings page shows the classic layout rows instead.
 */
class LayoutConfiguratorAddon extends AbstractAddon {
	
	const SLUG = 'layout-configurator';
	
	public function getName(): string {
		return __( 'Layout Configurator', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Visual editor for the product page pricing layout with a live preview. Switch it off to get the classic layout settings rows back.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4v-2H11v2h10zm-6-4h2V7h4V5h-4V3h-2v6z"/></svg>';
	}
	
	public function getSlug(): string {
		return self::SLUG;
	}
	
	/**
	 * Whether the add-on is switched on. Same rule as isEnabled(), usable without an instance.
	 */
	public static function isActive(): bool {
		return self::isAddonEnabled( self::SLUG );
	}
	
	public function run() {
		$this->getContainer()->add( 'settings.layout_preview', new LayoutPreview() );
		$this->getContainer()->add( 'settings.layout_configurator', new LayoutConfigurator() );
	}
}
