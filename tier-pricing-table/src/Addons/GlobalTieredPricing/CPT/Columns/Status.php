<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

class Status {
	
	public function getName(): string {
		return __( 'Status', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $rule ) {
		if ( $rule->isSuspended() ) {
			?>
			<mark class="tpt-rule-suspend-status tpt-rule-suspend-status--suspended">
				<span>
					<?php esc_html_e( 'Suspended', 'tier-pricing-table' ); ?>
				</span>
			</mark>
			<?php
		} else {
			?>
			<mark class="tpt-rule-suspend-status tpt-rule-suspend-status--active">
				<span><?php esc_html_e( 'Active', 'tier-pricing-table' ); ?></span>
			</mark>
			<?php
		}
	}
}
