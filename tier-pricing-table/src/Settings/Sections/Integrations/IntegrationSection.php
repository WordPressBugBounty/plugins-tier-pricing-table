<?php namespace TierPricingTable\Settings\Sections\Integrations;

use TierPricingTable\Admin\PluginInstaller\PluginInstaller;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTIntegrationOption;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

class IntegrationSection extends SectionAbstract {
	
	use ServiceContainerTrait;
	
	
	public function getSettings(): array {
		
		$settings = array();
		
		// a category says what it covers only where its name does not
		$categories = array(
			'u2code'               => array(
				'title'       => __( 'U2Code plugins', 'tier-pricing-table' ),
				'description' => __( 'More free plugins from the makers of Tiered Pricing Table.',
					'tier-pricing-table' ),
			),
			'other'                => array(
				'title'       => __( 'General integrations', 'tier-pricing-table' ),
				'description' => '',
			),
			'multicurrency'        => array(
				'title'       => __( 'Multicurrency integrations', 'tier-pricing-table' ),
				'description' => '',
			),
			'product_addons'       => array(
				'title'       => __( 'Product Addons', 'tier-pricing-table' ),
				'description' => '',
			),
			'seo'                  => array(
				'title'       => __( 'SEO', 'tier-pricing-table' ),
				'description' => '',
			),
			'feeds'                => array(
				'title'       => __( 'Product feeds', 'tier-pricing-table' ),
				'description' => __( 'Google, Meta and Pinterest get the price a logged-out visitor pays. While a feed plugin is active, its options are in the Product feeds section.', 'tier-pricing-table' ),
			),
			'custom_product_types' => array(
				'title'       => __( 'Custom Product Types', 'tier-pricing-table' ),
				'description' => '',
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
		
		// U2Code plugins with nothing to switch here: recommended next to the paired ones
		$listed = array_column( $integrations['u2code'] ?? array(), 'install_slug' );

		foreach ( PluginInstaller::plugins() as $slug => $plugin ) {
			if ( in_array( $slug, $listed, true ) ) {
				continue;
			}

			$integrations['u2code'][] = array(
				'title'        => $plugin->getName(),
				'desc'         => PluginInstaller::getText( $slug ),
				'type'         => TPTIntegrationOption::FIELD_TYPE,
				'icon_url'     => $this->getContainer()->getFileManager()->locateAsset( $plugin->getIconAsset() ),
				'author_url'   => $plugin->getSiteURL(),
				'official'     => true,
				'install_slug' => $slug,
			);
		}

		foreach ( $categories as $categoryID => $category ) {
			
			if ( empty( $integrations[ $categoryID ] ) ) {
				continue;
			}

			// the U2Code plugins get a panel of their own; the switches inside it are saved as usual
			if ( 'u2code' === $categoryID ) {
				$settings[] = array(
					'id'   => Settings::SETTINGS_PREFIX . $categoryID . '__integration_section',
					'type' => 'title',
				);

				$settings[] = array(
					'title'   => $category['title'],
					'desc'    => $category['description'],
					'type'    => TPTIntegrationOption::PANEL_TYPE,
					'plugins' => $integrations[ $categoryID ],
				);

				foreach ( $integrations[ $categoryID ] as $integration ) {
					if ( ! empty( $integration['id'] ) ) {
						$integration['type'] = TPTIntegrationOption::PANEL_SWITCH_TYPE;
						$settings[]          = $integration;
					}
				}

				$settings[] = array(
					'type' => 'sectionend',
					'id'   => Settings::SETTINGS_PREFIX . $categoryID . '__integration_section_end',
				);

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
		// the cards are styled in assets/admin/style.css (.tpt-integration-*); less space above the headings and
		// under the descriptions than WooCommerce's default, as on the Modules screen.
		// Printed through esc_html(): no ">" and no quotes in these selectors.
		return '#mainform h2 { margin-top: 22px; }
		#mainform div[id$=-description] p { margin: 0 0 12px; }
		#mainform .form-table { margin-top: 0; }
		.form-table:has(.tpt-integration-item) tbody { display: flex; flex-wrap: wrap; margin: 4px -6px 8px; }
		.form-table:has(.tpt-integration-item) tr { display: block; border-bottom: none; padding-bottom: 0; }
		.form-table:has(.tpt-integration-item) th { display: none; }
		.form-table:has(.tpt-integration-item) td { padding: 0 !important; width: 100%; }';
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
