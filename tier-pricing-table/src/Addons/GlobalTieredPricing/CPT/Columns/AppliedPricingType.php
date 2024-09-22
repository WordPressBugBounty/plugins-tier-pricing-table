<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class AppliedPricingType {

	public function getName() {
		return __( 'Pricing Applying As', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		$rule->getApplyingType() === 'individual' ? esc_html_e( 'Individually per product',
			'tier-pricing-table' ) : esc_html_e( 'Mix and Match', 'tier-pricing-table' );
	}
}
