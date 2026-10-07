<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

/**
 * Settings that differ from the defaults: how the rule meets product-level pricing, and whatever
 * add-ons append (tax settings, for instance) through the after_tab_render action.
 */
class Settings {

	public function getName(): string {
		return __( 'Settings', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		$settings = $rule->getSettings();
		$labels   = array(
			'prefer-product'            => __( 'prefer product', 'tier-pricing-table' ),
			'prefer-role-based-product' => __( 'prefer product role prices', 'tier-pricing-table' ),
			'override'                  => __( 'override product', 'tier-pricing-table' ),
		);

		switch ( $settings->getPriorityType() ) {
			case 'prefer-product':
				$this->renderTag( __( 'Prefers product pricing', 'tier-pricing-table' ) );
				break;
			case 'override':
				$this->renderTag( __( 'Overrides product pricing', 'tier-pricing-table' ) );
				break;
			case 'flexible':
				$this->renderTag( __( 'Custom priorities', 'tier-pricing-table' ), sprintf(
					/* translators: 1, 2, 3: the three priority choices */
					__( 'Base prices: %1$s. Tiers: %2$s. Quantity limits: %3$s.', 'tier-pricing-table' ),
					$labels[ $settings->getRegularPricingPriority() ] ?? $settings->getRegularPricingPriority(),
					$labels[ $settings->getTieredPricingPriority() ] ?? $settings->getTieredPricingPriority(),
					$labels[ $settings->getQuantityLimitsPriority() ] ?? $settings->getQuantityLimitsPriority()
				) );
				break;
			default:
				echo '<span class="tpt-rules-list__muted">' . esc_html__( 'Default', 'tier-pricing-table' ) . '</span>';
		}
	}

	public static function renderTag( string $text, string $title = '' ) {
		?>
		<span class="tpt-rules-list__tag" <?php echo $title ? 'title="' . esc_attr( $title ) . '"' : ''; ?>><?php echo esc_html( $text ); ?></span>
		<?php
	}
}
