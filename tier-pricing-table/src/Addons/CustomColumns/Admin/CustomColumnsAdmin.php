<?php namespace TierPricingTable\Addons\CustomColumns\Admin;

use TierPricingTable\Settings\Settings as MainSettings;

class CustomColumnsAdmin {

	public function __construct() {
		add_action( 'tiered_pricing_table/settings/table_columns/after_fields', function () {
			$columnsManager = \TierPricingTable\Addons\CustomColumns\CustomColumnsManager::getInstance();
			$hasColumns = ! empty( $columnsManager->getRawColumns() );

			if ( $hasColumns ) {
				return;
			}
			?>
				<div style="height:0; width:100%"></div>
			<div style="margin-top: 15px; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1; border-radius: 4px; max-width: 575px;">
				<p style="margin-top: 0; margin-bottom: 5px;"><strong><?php esc_html_e( 'Need more data?', 'tier-pricing-table' ); ?></strong></p>
				<p style="margin-bottom: 15px; font-size: 13px; color: #646970;"><?php esc_html_e( 'Create custom columns to display extra information like discounts or shipping times alongside your pricing tiers.', 'tier-pricing-table' ); ?></p>
				<button type="button" id="tpt-toggle-custom-columns" class="button button-primary" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 4px 14px 4px 10px; font-weight: 600;">
					<span class="dashicons dashicons-plus" style=" margin-top: 4px; display: flex; align-items: center;"></span>
					<?php esc_html_e( 'Add Custom Columns', 'tier-pricing-table' ); ?>
				</button>
			</div>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					var toggleBtn = document.getElementById('tpt-toggle-custom-columns');
					var customColumnsDiv = document.getElementById('tiered-pricing__feature__custom-columns');
					if (toggleBtn && customColumnsDiv) {
						customColumnsDiv.style.display = 'none';
						toggleBtn.addEventListener('click', function(e) {
							e.preventDefault();
							if (customColumnsDiv.style.display === 'none') {
								customColumnsDiv.style.display = 'block';
							} else {
								customColumnsDiv.style.display = 'none';
							}
						});
					}
				});
			</script>
			<?php
		} );

		add_action( 'tiered_pricing_table/settings/table_columns/end', function () {
			?>
			<div id="tiered-pricing__feature__custom-columns"></div>
			<?php
		} );

		add_action( 'admin_enqueue_scripts', function () {

			$settingsTab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';

			if ( MainSettings::SETTINGS_PAGE !== $settingsTab ) {
				return;
			}

			$buildDir = dirname( __FILE__, 2 ) . '/build';
			
			if ( ! file_exists( $buildDir . '/index.asset.php' ) ) {
				return;
			}

			$assetFile = include( $buildDir . '/index.asset.php' );

			wp_enqueue_script( 'tiered-pricing/feature/custom-columns',
					plugins_url( 'build/index.js', dirname( __FILE__ ) ), $assetFile['dependencies'],
					$assetFile['version'], true );

			wp_localize_script( 'tiered-pricing/feature/custom-columns', 'tptCustomColumns', array(
					'isPremium'  => function_exists( 'tpt_fs' ) && tpt_fs()->can_use_premium_code(),
					'upgradeUrl' => function_exists( 'tpt_fs' ) ? tpt_fs()->get_upgrade_url() : '#',
			) );

			wp_set_script_translations( 'tiered-pricing/feature/custom-columns', 'tier-pricing-table',
					dirname( __FILE__, 5 ) . '/languages' );
		} );
	}
}
