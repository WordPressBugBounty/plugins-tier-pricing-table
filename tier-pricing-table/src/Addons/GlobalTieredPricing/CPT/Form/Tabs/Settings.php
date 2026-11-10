<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\TieredPricingCart\CartOptionsSubsection;

/**
 * What happens when a product has its own pricing settings next to this rule: the rule wins, the product's
 * settings stay, or a choice per setting (base prices, tiers, quantity limits). Product rules only.
 */
class Settings extends FormTab {
	
	public function getId(): string {
		return 'settings';
	}
	
	public function getTitle(): string {
		return __( 'Product overrides', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Which settings win when a product has its own', 'tier-pricing-table' );
	}
	
	public function getKinds(): array {
		return array( GlobalPricingRule::KIND_PRODUCT );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->renderSectionTitle( __( 'Product overrides', 'tier-pricing-table' ) );
		
		// the store default is the "Prioritize global pricing rules" switch: say what it does right now
		$storeDefaultOverrides = CartOptionsSubsection::globalRulesOverrideProductLevelRules();
		$storeDefaultEffect    = $storeDefaultOverrides
			? __( 'global rules override the product\'s settings', 'tier-pricing-table' )
			: __( 'the product\'s settings win', 'tier-pricing-table' );
		
		$this->renderHint( sprintf(
			/* translators: 1: "on" or "off", 2: what the store default does right now */
			__( 'A product can carry its own tiered prices, role prices and quantity limits. Decide what happens when this rule and the product both have a setting. "Follow the store default" uses the "Prioritize global pricing rules" switch under Settings → General → Price Calculation, which is %1$s right now: %2$s.', 'tier-pricing-table' ),
			$storeDefaultOverrides ? __( 'on', 'tier-pricing-table' ) : __( 'off', 'tier-pricing-table' ),
			$storeDefaultEffect
		) );
		
		$this->renderRadioOptions( array(
			'id'      => '_tpt_settings_priority_type',
			'title'   => __( 'When a product has its own pricing', 'tier-pricing-table' ),
			'options' => array(
				/* translators: %s: what the store default does right now */
				'default'        => sprintf( __( 'Follow the store default (right now: %s)', 'tier-pricing-table' ), $storeDefaultEffect ),
				'prefer-product' => __( 'Keep the product\'s settings',
					'tier-pricing-table' ),
				'override'       => __( 'This rule overrides them',
					'tier-pricing-table' ),
				'flexible'       => __( 'Decide per setting',
					'tier-pricing-table' ),
			),
			'value'   => $pricingRule->getSettings()->getPriorityType(),
		) );
		
		?>

		<div class="tpt_settings_advanced_priority_settings hidden">
			<?php
				$this->renderSectionTitle( __( 'Base prices', 'tier-pricing-table' ) );
			?>
			<div class="tpt_settings_regular_pricing">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_regular_pricing_priority_type',
						'title'   => __( 'Base prices', 'tier-pricing-table' ),
						'options' => array(
							'prefer-role-based-product' => __( 'Keep the product\'s role prices',
								'tier-pricing-table' ),
							'override'                  => __( 'This rule overrides every product price',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getRegularPricingPriority(),
					) );
					
					$this->renderHint( __( 'Whether a product\'s own role prices stay in place or this rule\'s base prices replace them.',
						'tier-pricing-table' ) );
				?>
			</div>
			
			<?php
				$this->renderSectionTitle( __( 'Tiered prices', 'tier-pricing-table' ) );
			?>

			<div class="tpt_settings_tiered_pricing">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_tiered_pricing_priority_type',
						'title'   => __( 'Tiered prices', 'tier-pricing-table' ),
						'options' => array(
							'prefer-product'            => __( 'Keep the product\'s tiers',
								'tier-pricing-table' ),
							'prefer-role-based-product' => __( 'Keep the product\'s role tiers only',
								'tier-pricing-table' ),
							'override'                  => __( 'This rule overrides every product tier',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getTieredPricingPriority(),
					) );
					
					$this->renderCheckbox( array(
						'title' => __( 'Mix & Match', 'tier-pricing-table' ),
						'id'    => '_tpt_settings_tiered_pricing_allow_mix_and_match',
						'value' => $pricingRule->getSettings()->isAllowTieredPricingMixAndMatch(),
						'label' => __( 'Enable Mix & Match for product-level tiers',
							'tier-pricing-table' ),
					) );
					
					$this->renderHint( __( 'Whether a product\'s own tiers stay in place or this rule\'s tiers replace them; Mix & Match can apply to the tiers kept from the product.',
						'tier-pricing-table' ) );
				?>
			</div>
			
			<?php
				$this->renderSectionTitle( __( 'Quantity limits', 'tier-pricing-table' ) );
			?>

			<div class="tpt_settings_quantity_limits">
				<?php
					$this->renderRadioOptions( array(
						'id'      => '_tpt_settings_quantity_limits_priority_type',
						'title'   => __( 'Quantity limits', 'tier-pricing-table' ),
						'options' => array(
							'prefer-product'            => __( 'Keep the product\'s limits',
								'tier-pricing-table' ),
							'prefer-role-based-product' => __( 'Keep the product\'s role limits only',
								'tier-pricing-table' ),
							'override'                  => __( 'This rule overrides every product limit',
								'tier-pricing-table' ),
						),
						'value'   => $pricingRule->getSettings()->getQuantityLimitsPriority(),
					) );
					
					$this->renderHint( __( 'Whether a product\'s own quantity limits stay in place or this rule\'s limits replace them.',
						'tier-pricing-table' ) );
				?>
			</div>
		</div>

		<script>
			jQuery(document).ready(function (jQuery) {
				jQuery('[name=_tpt_settings_priority_type]').on('change', function () {

					if (jQuery(this).val() === 'flexible') {
						jQuery('.tpt_settings_advanced_priority_settings').removeClass('hidden');
					} else {
						jQuery('.tpt_settings_advanced_priority_settings').addClass('hidden');
					}
				}).filter(':checked').trigger('change');
			});
		</script>
		
		<?php
	}
	
	public function getIcon(): string {
		return 'dashicons-admin-settings';
	}
}