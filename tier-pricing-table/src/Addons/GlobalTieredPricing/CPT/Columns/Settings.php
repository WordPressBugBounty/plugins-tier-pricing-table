<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

/**
 * Settings that differ from the defaults: how a product rule meets product-level pricing; for an Order
 * rule, the discount type, what the tiers count, the free shipping bar and a custom label. Add-ons append
 * theirs (tax settings, for instance) through the after_tab_render action.
 */
class Settings {

	public function getName(): string {
		return __( 'Settings', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		if ( $rule->isOrderRule() ) {
			$this->renderOrderRule( $rule );

			return;
		}

		$settings = $rule->getSettings();
		$labels   = array(
			'prefer-product'            => __( 'product kept', 'tier-pricing-table' ),
			'prefer-role-based-product' => __( 'product role prices kept', 'tier-pricing-table' ),
			'override'                  => __( 'rule overrides', 'tier-pricing-table' ),
		);

		switch ( $settings->getPriorityType() ) {
			case 'prefer-product':
				$this->renderTag( __( 'Keeps product settings', 'tier-pricing-table' ) );
				break;
			case 'override':
				$this->renderTag( __( 'Overrides product settings', 'tier-pricing-table' ) );
				break;
			case 'flexible':
				$this->renderTag( __( 'Per setting', 'tier-pricing-table' ), sprintf(
					/* translators: 1, 2, 3: the three choices */
					__( 'Base prices: %1$s. Tiers: %2$s. Quantity limits: %3$s.', 'tier-pricing-table' ),
					$labels[ $settings->getRegularPricingPriority() ] ?? $settings->getRegularPricingPriority(),
					$labels[ $settings->getTieredPricingPriority() ] ?? $settings->getTieredPricingPriority(),
					$labels[ $settings->getQuantityLimitsPriority() ] ?? $settings->getQuantityLimitsPriority()
				) );
				break;
			default:
				echo '<span class="tpt-rules-list__muted">' . esc_html__( 'Store default', 'tier-pricing-table' ) . '</span>';
		}
	}

	/**
	 * An Order rule's settings that differ from the defaults (a fixed amount off, the subtotal counted, no
	 * bar, the default label).
	 */
	protected function renderOrderRule( GlobalPricingRule $rule ) {
		$tags = array(); // the discount type and what it counts are in the Pricing column's caption

		if ( $rule->hasOrderShippingBar() ) {
			$tags[] = array( __( 'Free shipping bar', 'tier-pricing-table' ), __( 'Customers see a progress bar towards the free shipping threshold.', 'tier-pricing-table' ) );
		}

		if ( '' !== $rule->getOrderLabel() ) {
			/* translators: %s: the discount line label */
			$tags[] = array( sprintf( __( 'Label: %s', 'tier-pricing-table' ), $rule->getOrderLabel() ), __( 'The discount line in the cart, at checkout and in e-mails.', 'tier-pricing-table' ) );
		}

		if ( ! $tags ) {
			echo '<span class="tpt-rules-list__muted">' . esc_html__( 'Default', 'tier-pricing-table' ) . '</span>';

			return;
		}

		foreach ( $tags as $tag ) {
			self::renderTag( $tag[0], $tag[1] );
		}
	}

	public static function renderTag( string $text, string $title = '' ) {
		?>
		<span class="tpt-rules-list__tag" <?php echo $title ? 'title="' . esc_attr( $title ) . '"' : ''; ?>><?php echo esc_html( $text ); ?></span>
		<?php
	}
}
