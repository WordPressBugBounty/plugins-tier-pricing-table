<?php namespace TierPricingTable\Addons;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTFeatureFlagOption;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Settings;

abstract class AbstractAddon {
	
	use ServiceContainerTrait;
	
	// Groups of the Modules screen (Settings → Advanced), in display order there
	const GROUP_PRICING      = 'pricing';
	const GROUP_PRODUCT_PAGE = 'product-page';
	const GROUP_SHOP_CART    = 'shop-and-cart';
	const GROUP_WHOLESALE    = 'wholesale';
	const GROUP_TOOLS        = 'tools';
	
	public function __construct() {
		add_filter( 'tiered_pricing_table/settings/advanced_settings', array(
			$this,
			'addToAddonsSettings',
		) );
	}
	
	public function addToAddonsSettings( $addons ) {
		$addons[] = array(
			'title'   => $this->getName(),
			'id'      => Settings::SETTINGS_PREFIX . '_addon_' . $this->getSlug(),
			'default' => $this->isActiveByDefault() ? 'yes' : 'no',
			'desc'    => $this->getDescription(),
			'icon'    => $this->getIcon(),
			'group'   => $this->getGroup(),
			'type'    => TPTFeatureFlagOption::FIELD_TYPE,
		);
		
		return $addons;
	}
	
	public function isEnabled(): bool {
		
		$settings = $this->getContainer()->getSettings();
		$addonKey = '_addon_' . $this->getSlug();
		
		return $settings->get( $addonKey, $this->isActiveByDefault() ? 'yes' : 'no' ) === 'yes';
	}
	
	protected function isActiveByDefault(): bool {
		return true;
	}
	
	/**
	 * Whether the add-on with this slug is switched on, without needing its instance.
	 */
	public static function isAddonEnabled( string $slug, bool $activeByDefault = true ): bool {
		$settings = \TierPricingTable\Core\ServiceContainer::getInstance()->getSettings();
		
		return 'yes' === $settings->get( '_addon_' . $slug, $activeByDefault ? 'yes' : 'no' );
	}
	
	/**
	 * The group the module is listed under on the Modules screen (one of the GROUP_ constants).
	 */
	public function getGroup(): string {
		return self::GROUP_TOOLS;
	}
	
	abstract public function getName();
	
	abstract public function getDescription();
	
	abstract public function getIcon(): string;
	
	abstract public function getSlug();
	
	abstract public function run();
}
