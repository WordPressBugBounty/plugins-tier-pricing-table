<?php namespace TierPricingTable\Settings\Sections\Advanced;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Settings\CustomOptions\TPTFeatureFlagOption;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * The "Advanced" tab: the Tools add-on's utilities (roles, pricing test, cache, data clean-up), then the module
 * switches. The former "Tools" tab lives here since 7.2.1; its old links still work.
 */
class AdvancedSection extends SectionAbstract {

	public function getSettings() {
		// Utilities first, then the modules; the cache switch lives in the Utilities panel ("Cache" tab)
		return array_merge(
			(array) apply_filters( 'tiered_pricing_table/settings/tools_settings', array() ),
			$this->getModulesSettings()
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
				'desc'  => __( 'Switch off the features your store does not use. A module that is off does not load at all.', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'advanced',
				'type'  => 'title',
			),
		);

		$modules = (array) apply_filters( 'tiered_pricing_table/settings/advanced_settings', array() );
		$groups  = self::getModuleGroups();
		$byGroup = array_fill_keys( array_keys( $groups ), array() );

		foreach ( $modules as $module ) {
			$group = isset( $module['group'], $groups[ $module['group'] ] ) ? $module['group'] : AbstractAddon::GROUP_TOOLS;

			$byGroup[ $group ][] = $module;
		}

		foreach ( $byGroup as $group => $groupModules ) {
			if ( empty( $groupModules ) ) {
				continue;
			}

			$settings[] = array(
				'type'  => TPTFeatureFlagOption::GROUP_TYPE,
				'title' => $groups[ $group ],
				'group' => $group,
			);

			foreach ( $groupModules as $module ) {
				$module['group'] = $group;
				$settings[]      = $module;
			}
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => Settings::SETTINGS_PREFIX . 'advanced',
		);

		return $settings;
	}

	/**
	 * The groups of the Modules screen, in display order. Modules without a known group are listed under Tools.
	 *
	 * @return array<string, string>
	 */
	public static function getModuleGroups(): array {
		return array(
			AbstractAddon::GROUP_PRICING      => __( 'Pricing rules', 'tier-pricing-table' ),
			AbstractAddon::GROUP_PRODUCT_PAGE => __( 'Product page', 'tier-pricing-table' ),
			AbstractAddon::GROUP_SHOP_CART    => __( 'Shop and cart', 'tier-pricing-table' ),
			AbstractAddon::GROUP_WHOLESALE    => __( 'Wholesale and orders', 'tier-pricing-table' ),
			AbstractAddon::GROUP_TOOLS        => __( 'Tools', 'tier-pricing-table' ),
		);
	}

	public function getSectionCSS(): string {
		// only the modules table (the one with the module cards) is a grid; the utilities table keeps its rows.
		// The cards themselves are styled in assets/admin/style.css (.tpt-feature-flag-*).
		// less space above the headings and under the descriptions than WooCommerce's default (about 38px above,
		// 13px under); the gap between a heading and its description stays WooCommerce's.
		// Printed through esc_html(): no ">" and no quotes in these selectors.
		return '#mainform h2 { margin-top: 28px; }
		#mainform div[id$=-description] p { margin: 0 0 12px; }
		#mainform .form-table { margin-top: 0; }
		.form-table:has(.tpt-feature-flag-item) tbody { display: flex; flex-wrap: wrap; margin: 4px -6px 24px; }
		.form-table:has(.tpt-feature-flag-item) tr { display: block; border-bottom: none; padding-bottom: 0; }
		.form-table:has(.tpt-feature-flag-item) th { display: none; }
		.form-table:has(.tpt-feature-flag-item) td { padding: 0 !important; width: 100%; }';
	}

	public static function deleteOptions() {
		delete_option( Settings::SETTINGS_PREFIX . 'advanced' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_category-tiered-pricing' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_manual-orders' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_role-based-rules' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_user-based-rules' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_role-customer-prices' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_global-tier-pricing' );
		delete_option( Settings::SETTINGS_PREFIX . '_addon_minimum-quantity' );
		delete_option( Settings::SETTINGS_PREFIX . 'advanced' );
	}
}
