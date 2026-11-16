<?php namespace TierPricingTable\Addons\QuickOrder;

use TierPricingTable\Addons\AbstractAddon;

/**
 * Quick Order: a one-page order form. Customers search by name or SKU, paste or upload a list, or fill in an
 * order sheet, and see the tier price of every line before they add it all to the cart.
 *
 * The module is on by default, so the Quick Order tab is there to find, but the form is off: until "Enable the
 * quick order form" is switched on, the block and shortcode show nothing to shoppers, there is no My Account tab
 * or page, and the search and add to cart requests are refused.
 */
class QuickOrderAddon extends AbstractAddon {

	const SLUG = 'quick-order';

	public function getName(): string {
		return __( 'Quick Order Form', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'A one-page order form for bulk and repeat buyers: search by name or SKU, paste a list or upload a CSV, see the tier price of every line, and add it all to the cart at once.',
			'tier-pricing-table' );
	}

	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 5h16v2H4V5zm0 6h10v2H4v-2zm0 6h10v2H4v-2zm13-5h2v3h3v2h-3v3h-2v-3h-3v-2h3v-3z"/></svg>';
	}

	public function getSlug(): string {
		return self::SLUG;
	}

	public function getGroup(): string {
		return self::GROUP_WHOLESALE;
	}

	/**
	 * On by default so its tab is there to find; the form itself stays off until its "Enable the quick order
	 * form" switch is on (QuickOrderSettingsSection::isEnabled()).
	 */
	protected function isActiveByDefault(): bool {
		return true;
	}

	public static function isActive(): bool {
		return self::isAddonEnabled( self::SLUG, true );
	}

	public function run() {
		new QuickOrderSettings();
		new QuickOrderEndpoints( new QuickOrderService() );
		new QuickOrderDisplay();
	}
}
