<?php namespace TierPricingTable\Admin\Tips\Tips;

use TierPricingTable\Admin\Tips\Tip;

/**
 * Suggests a default variation so the pricing table shows before a variation is picked.
 *
 * @package TierPricingTable\Admin\Tips\Tips
 */
class DefaultVariationTip extends Tip {
	
	public function getSlug(): string {
		return 'default_variation_tip';
	}
	
	public function __construct() {
		parent::__construct();
		
		add_action( 'tiered_pricing_table/admin/variable_product_options', array( $this, 'render' ), 999, 1 );
	}
	
	public function render() {
		
		if ( $this->isSeen() ) {
			return;
		}
		
		?>
		<div class="hidden show_if_variable show_if_variable-subscription">
			<div class="tiered-pricing-tip"
				 style="margin: 12px; padding: 10px; background: #fafafa; border: 1px solid #eeeeee; display: flex; gap: 10px; justify-content: space-between">
				<div style="display:flex; gap: 10px; ">
					<div style="color: #2272b1; margin: 0 5px;">
						<span class="dashicons dashicons-admin-post"></span>
					</div>
					<div>
						<strong>
							<?php esc_html_e( 'Tip', 'tier-pricing-table' ); ?>:
						</strong>
						
						<?php esc_html_e( 'Show tiered pricing before a variation is selected.', 'tier-pricing-table' ); ?>
						<div style="margin-top: 10px;">
							<?php esc_html_e( 'Tiers belong to each variation, so the pricing table appears once the customer has picked one. To show it right away, choose a default variation in the field above. Its tiers are shown until the customer picks another variation.', 'tier-pricing-table' ); ?>
						</div>
						
					</div>
				</div>

				<div style="white-space: nowrap; ">
					<a role="button" href="<?php echo esc_attr( $this->getMarkAsSeenURL() ); ?>"
					   class="tiered-pricing-tip-close-button">
						&times; <?php esc_html_e( 'Hide this tip', 'tier-pricing-table' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
		
	}
}
