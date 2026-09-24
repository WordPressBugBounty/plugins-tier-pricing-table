<?php namespace TierPricingTable\Settings\Sections\Advanced;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\CustomOptions\TPTLinkButton;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * The "Advanced" tab: the module switches, the Tools add-on's utilities (roles, data clean-up),
 * the cache and the debug mode. The former "Tools" tab lives here since 7.2.1; its old links still work.
 */
class AdvancedSection extends SectionAbstract {

	public function getSettings() {
		return array_merge(
			$this->getModulesSettings(),
			(array) apply_filters( 'tiered_pricing_table/settings/tools_settings', array() ),
			$this->getCacheSettings(),
			$this->getDebugSettings()
		);
	}

	public function getSlug(): string {
		return 'advanced';
	}

	public function getName(): string {
		return __( 'Advanced', 'tier-pricing-table' );
	}

	protected function getModulesSettings(): array {
		$settings = array(
			array(
				'title' => __( 'Modules', 'tier-pricing-table' ),
				'desc'  => __( 'You can disable or enable specific plugin features.', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'advanced',
				'type'  => 'title',
			),
		);

		$settings = array_merge( $settings, (array) apply_filters( 'tiered_pricing_table/settings/advanced_settings', array() ) );

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => Settings::SETTINGS_PREFIX . 'advanced',
		);

		return $settings;
	}

	protected function getCacheSettings(): array {
		return array(
			array(
				'title' => __( 'Cache', 'tier-pricing-table' ),
				'desc'  => __( 'Cache improves performance making the plugin not calculate data on each request. Disable to debug issues.', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'cache_section',
				'type'  => 'title',
			),
			array(
				'title'   => __( 'Enabled', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'cache_enabled',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'        => __( 'Purge', 'tier-pricing-table' ),
				'button_text'  => __( 'Purge cache', 'tier-pricing-table' ),
				'button_class' => 'button button-large',
				'button_link'  => ServiceContainer::getInstance()->getCache()->getPurgeURL(),
				'type'         => TPTLinkButton::FIELD_TYPE,
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::SETTINGS_PREFIX . 'cache_section',
			),
		);
	}

	protected function getDebugSettings(): array {
		return array(
			array(
				'title' => __( 'Debug', 'tier-pricing-table' ),
				'desc'  => __( 'Debug mode is useful when you need to track what pricing rule is applying for a cart item.', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'debug_section',
				'type'  => 'title',
			),
			array(
				'title'   => __( 'Enabled', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'debug_enabled',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::SETTINGS_PREFIX . 'debug_section',
			),
		);
	}

	public function getSectionCSS(): string {
		// only the modules table (the first one) is a grid; the utilities, cache and debug tables keep the default rows
		return '.form-table:first-of-type tbody { display: flex; flex-wrap: wrap; margin: 10px 0 20px 0; }
		.form-table:first-of-type tr { display: block; border-bottom: none; padding-bottom: 0; }
		.form-table:first-of-type th { display: none; }
		.form-table:first-of-type td { padding: 0 !important; width: 100%; }';
	}

	public static function deleteOptions() {
		delete_option( Settings::SETTINGS_PREFIX . 'advanced' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_category-tiered-pricing' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_manual-orders' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_role-based-rules' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_global-tier-pricing' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_minimum-quantity' );
		delete_option( Settings::SETTINGS_PREFIX . 'advanced' );
	}
}
