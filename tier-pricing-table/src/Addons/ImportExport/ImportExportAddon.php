<?php namespace TierPricingTable\Addons\ImportExport;

use TierPricingTable\Addons\AbstractAddon;

class ImportExportAddon extends AbstractAddon {
	
	public function getName(): string {
		return __( 'CSV import and export', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Tiered pricing columns in WooCommerce\'s product CSV importer and exporter, with one set of columns per role.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-3H7v-3h4v-3l4.5 4.5L11 16.5z"/></svg>';
	}
	
	public function getSlug(): string {
		return 'import-export';
	}

	public function getGroup(): string {
		return self::GROUP_TOOLS;
	}
	
	public function run() {
		$this->getContainer()->initService( WoocommerceImportService::class );
		$this->getContainer()->initService( WoocommerceExportService::class );
	}
}
