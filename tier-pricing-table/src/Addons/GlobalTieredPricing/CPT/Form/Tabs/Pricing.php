<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Forms\RegularPricingForm;
use TierPricingTable\Forms\TieredPricingRulesForm;

class Pricing extends FormTab {

	public function getId(): string {
		return 'pricing';
	}

	public function getTitle(): string {
		return __( 'Pricing', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Configure regular and tiered pricing rules', 'tier-pricing-table' );
	}

	public function getKinds(): array {
		return array( GlobalPricingRule::KIND_PRODUCT );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		?>
		<div>
			<?php
				// Flat prices are in the free version; the form marks the percentage discount as premium itself.
				$this->renderSectionTitle( __( 'Base Pricing', 'tier-pricing-table' ), array(
						'description' => __( 'Override the default regular product prices.', 'tier-pricing-table' ),
				) );

				RegularPricingForm::render( null, null, $pricingRule->getRegularPrice(), $pricingRule->getSalePrice(),
						$pricingRule->getPricingType(), $pricingRule->getDiscount(), $pricingRule->getDiscountType() );
			?>
		</div>

		<div>
			<?php
				$this->renderSectionTitle( __( 'Tiered Pricing', 'tier-pricing-table' ) );


				$this->renderRadioOptions( array(
						'id'      => 'tpt_applying_type',
						'title'       => __( 'Count quantities', 'tier-pricing-table' ),
						'options'     => array(
								'individual' => __( 'Per product', 'tier-pricing-table' ),
								'cross'      => __( 'Across all products in this rule (mix and match)', 'tier-pricing-table' ),
						),
						'value'       => $pricingRule->getApplyingType(),
						'description' => __( 'Mix and match adds up the quantities of every product this rule covers to reach a tier. Per product counts each product on its own.', 'tier-pricing-table' ),
						'desc_tip'    => true,
				), true );

				global $post;

				TieredPricingRulesForm::render( $post->ID, null, null, $pricingRule->getTieredPricingType(),
						$pricingRule->getPercentageTieredPricingRules(), $pricingRule->getFixedTieredPricingRules(), null, $pricingRule->getAmountTieredPricingRules() );
			?>
		</div>
		<?php
	}

	public function getIcon(): string {
		return '$';
	}
}