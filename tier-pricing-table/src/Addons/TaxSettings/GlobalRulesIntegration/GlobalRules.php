<?php namespace TierPricingTable\Addons\TaxSettings\GlobalRulesIntegration;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns\Settings;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class GlobalRules {
	
	public function __construct() {
		add_filter( 'tiered_pricing_table/global_pricing/form_tabs', function ( $tabs, $form ) {
			
			$settingsTab = array_pop( $tabs );
			
			$tabs[] = new TaxSettingsTab( $form );
			$tabs[] = $settingsTab;
			
			return $tabs;
			
		}, 10, 2 );
		
		// free: the tax settings of a global rule
		// a rule that only sets a tax status or class still changes what the customer pays, so it is not "skipped"
		add_filter( 'tiered_pricing_table/global_pricing/validation', function ( $valid, GlobalPricingRule $rule ) {
			return $valid || '' !== $rule->getTaxStatus() || '' !== $rule->getTaxClass();
		}, 10, 2 );
	
		
		// a tag in the rules list's Settings column, only when the rule sets a tax status or class
		add_action( 'tiered_pricing_table/global_pricing/table/after_tab_render', function ( $column, GlobalPricingRule $rule ) {
			if ( 'settings' !== $column || ( ! $rule->getTaxStatus() && ! $rule->getTaxClass() ) ) {
				return;
			}
		
			$statuses = array(
				'taxable'  => __( 'Taxable', 'tier-pricing-table' ),
				'shipping' => __( 'Shipping only', 'tier-pricing-table' ),
				'none'     => __( 'None', 'tier-pricing-table' ),
			);
			$classes  = function_exists( 'wc_get_product_tax_class_options' ) ? wc_get_product_tax_class_options() : array();
			$parts    = array();
		
			if ( $rule->getTaxStatus() ) {
				/* translators: %s: tax status */
				$parts[] = sprintf( __( 'Tax status: %s', 'tier-pricing-table' ), $statuses[ $rule->getTaxStatus() ] ?? $rule->getTaxStatus() );
			}
			if ( $rule->getTaxClass() ) {
				/* translators: %s: tax class */
				$parts[] = sprintf( __( 'Tax class: %s', 'tier-pricing-table' ), $classes[ $rule->getTaxClass() ] ?? $rule->getTaxClass() );
			}
		
			foreach ( $parts as $part ) {
				Settings::renderTag( $part );
			}
		}, 10, 2 );
	
	}
}