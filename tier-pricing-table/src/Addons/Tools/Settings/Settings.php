<?php namespace TierPricingTable\Addons\Tools\Settings;

use TierPricingTable\Settings\Settings as MainSettings;

class Settings {

	public function __construct() {

		// the utilities panel on the Advanced tab, between the modules and the cache settings
		add_filter( 'tiered_pricing_table/settings/tools_settings', function ( $settings ) {
			return array_merge( array(
				array(
					// #roles: the "Manage roles" links land here, above the Utilities heading; the app also reads the hash as its tab
					'type' => 'tiered-pricing_tools-anchor',
				),
				array(
					'title' => __( 'Utilities', 'tier-pricing-table' ),
					'desc'  => __( 'Manage roles and clean up tiered pricing data.', 'tier-pricing-table' ),
					'id'    => MainSettings::SETTINGS_PREFIX . 'tools_utilities',
					'type'  => 'title',
				),
				array(
					'type' => 'tiered-pricing_tools-ui',
				),
				array(
					'type' => 'sectionend',
					'id'   => MainSettings::SETTINGS_PREFIX . 'tools_utilities',
				),
			), $settings );
		} );

		add_action( 'woocommerce_admin_field_tiered-pricing_tools-anchor', function () {
			// printed between two settings tables, so it is not a table row; the scroll margin keeps the heading below the sticky bars
			?>
			<div id="roles" style="scroll-margin-top: 90px;"></div>
			<?php
		} );

		add_action( 'woocommerce_admin_field_tiered-pricing_tools-ui', function () {
			?>
			<tr valign="top">
				<td colspan="2" class="forminp">
					<div id="tiered-pricing__feature__tools"></div>
				</td>
			</tr>
			<?php
		} );

		add_action( 'admin_enqueue_scripts', function () {

			$settingsTab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
			$section     = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

			if ( MainSettings::SETTINGS_PAGE !== $settingsTab ) {
				return;
			}

			if ( ! in_array( $section, array( 'advanced', 'tools' ), true ) ) { // 'tools' is the pre-7.2.1 link
				return;
			}

			$assetFile = include( plugin_dir_path( __FILE__ ) . '../build/index.asset.php' );

			wp_enqueue_script( 'tiered-pricing/feature/tools',
					plugins_url( 'build/index.js', dirname( __FILE__ . '../' ) ), $assetFile['dependencies'],
					$assetFile['version'], true );

			wp_set_script_translations( 'tiered-pricing/feature/tools', 'tier-pricing-table',
					dirname( __FILE__, 5 ) . '/languages' );

			wp_enqueue_style( 'wp-components' );
		} );
	}
}
