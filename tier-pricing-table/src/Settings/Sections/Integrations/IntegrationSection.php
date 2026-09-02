<?php namespace TierPricingTable\Settings\Sections\Integrations;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

class IntegrationSection extends SectionAbstract {
	
	use ServiceContainerTrait;
	
	
	public function getSettings(): array {
		
		$settings = array();
		
		$categories = array(
			'u2code'               => array(
				'title'       => __( 'U2Code plugins', 'tier-pricing-table' ),
				'description' => __( 'Plugins by the makers of Tiered Pricing Table — built to work together with deep, first-party integrations.',
					'tier-pricing-table' ),
			),
			'other'                => array(
				'title'       => __( 'General integrations', 'tier-pricing-table' ),
				'description' => __( 'Integrations with most popular plugins that are not related to a specific category.' ),
				'tier-pricing-table',
			),
			'multicurrency'        => array(
				'title'       => __( 'Multicurrency integrations', 'tier-pricing-table' ),
				'description' => __( 'Integrations with multicurrency plugins.', 'tier-pricing-table' ),
			),
			'product_addons'       => array(
				'title'       => __( 'Product Addons', 'tier-pricing-table' ),
				'description' => __( 'Integrations with product add-ons (custom fields) plugins.',
					'tier-pricing-table' ),
			),
			'seo'                  => array(
				'title'       => __( 'SEO', 'tier-pricing-table' ),
				'description' => __( 'Integrations with SEO plugins.', 'tier-pricing-table' ),
			),
			'custom_product_types' => array(
				'title'       => __( 'Custom Product Types', 'tier-pricing-table' ),
				'description' => __( 'Integrations plugins that provides custom product types.', 'tier-pricing-table' ),
			),
		);
		
		/**
		 * Integration categories
		 *
		 * @since 5.5.0
		 */
		$categories = apply_filters( 'tiered_pricing_table/settings/integrations_categories', $categories );
		
		/**
		 * Integration section settings
		 */
		$_integrations = apply_filters( 'tiered_pricing_table/settings/integrations_settings', array() );
		$integrations  = [];
		
		foreach ( $_integrations as $integration ) {
			// Official (U2Code) integrations are shown in their own section at the top of the page.
			$categoryID = ! empty( $integration['official'] ) ? 'u2code' : $integration['integration_category'];
			
			$integrations[ $categoryID ][] = $integration;
		}
		
		foreach ( $categories as $categoryID => $category ) {
			
			if ( empty( $integrations[ $categoryID ] ) ) {
				continue;
			}
			
			$settings[] = array(
				'title' => $category['title'],
				'desc'  => $category['description'],
				'id'    => Settings::SETTINGS_PREFIX . $categoryID . '__integration_section',
				'type'  => 'title',
			);
			
			foreach ( $integrations[ $categoryID ] as $integration ) {
				$settings[] = $integration;
			}
			
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => Settings::SETTINGS_PREFIX . $categoryID . '__integration_section_end',
			);
		}
		
		return $settings;
	}
	
	public function getSectionCSS(): string {
		return '.form-table tbody { display: flex; flex-wrap: wrap; margin: 10px 0 20px 0; }';
	}
	
	public function getSlug(): string {
		return 'integrations';
	}
	
	public function getName(): string {
		return __( 'Integrations', 'tier-pricing-table' );
	}
	
	public static function deleteOptions() {
		delete_option( Settings::SETTINGS_PREFIX . 'table_integrations' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_elementor' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_wpallimport' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_mix-match-for-woocommerce' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_product-add-ons' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_woocommerce-deposits' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_product-bundles-for-woocommerce' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_aelia-multicurrency' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_wcpa' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_u2code-product-addons' );
		delete_option( Settings::SETTINGS_PREFIX . '_integration_u2code-multicurrency' );
		delete_option( 'tpt_integrations_badge_seen' );
		delete_option( 'tpt_section_badge_seen_multicurrency' );
		delete_option( 'tpt_section_badge_seen_product-addons' );
		delete_option( Settings::SETTINGS_PREFIX . 'integrations' );
	}
}
