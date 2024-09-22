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
		return __( 'Set up regular and tiered pricing.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$this->form->renderHint( __( 'This section controls the base product price, where tiered pricing is not applied. You can set new regular and sale prices or specify a percentage discount based on the original product price.',
			'tier-pricing-table' ), array( 'only_for_new_rules' => true ) );
		
		RegularPricingForm::render( null, null, $pricingRule->getRegularPrice(), $pricingRule->getSalePrice(),
			$pricingRule->getPricingType(), $pricingRule->getDiscount(), $pricingRule->getDiscountType() );
		
		if ( ! tpt_fs()->can_use_premium_code() ) {
			$this->form->renderUpgradingNotice();
		}
		?>

		<div class="tpt-global-pricing-title">
			<hr>
			<h4><?php esc_attr_e( 'Tiered Pricing', 'tier-pricing-table' ); ?></h4>
		</div>
		
		<?php
		$this->form->renderHint( __( '<b>Mix & Match:</b> Combines the quantities of different products to reach tiered pricing thresholds, allowing discounts when products are purchased together. <br /><br />
<b>Individually:</b> Treats each product’s quantity separately in pricing calculations, ensuring that each product follows its own pricing tier independently without being combined with others.',
			'tier-pricing-table' ), array( 'show_icon' => false, 'only_for_new_rules' => true ) );
		?>

		<p class="form-field">
			<label for="tpt_applying_type"><?php esc_html_e( 'Calculation type', 'tier-pricing-table' ); ?></label>

			<label for="tpt_applying_type_individual"
				   style="padding: 0; float: none; width: auto; margin: 0;">
				<input type="radio"
					   style="margin-right: 3px;"
					   value="individual"
					<?php checked( 'individual', $pricingRule->getApplyingType() ); ?>
					   name="tpt_applying_type"
					   id="tpt_applying_type_individual"
				>
				<?php esc_attr_e( 'Individually', 'tier-pricing-table' ); ?>
			</label>

			<label for="tpt_applying_type_mix_and_match"
				   style="padding: 0; float: none; width: auto; margin: 0 5px 0 20px;">
				<input type="radio"
					   value="cross"
					   style="margin-right: 3px;"
					<?php checked( 'cross', $pricingRule->getApplyingType() ); ?>
					   name="tpt_applying_type"
					   id="tpt_applying_type_mix_and_match"
				>
				<?php esc_attr_e( 'Mix and Match', 'tier-pricing-table' ); ?>
			</label>
		</p>

		<div>
			<?php
				global $post;
				
				TieredPricingRulesForm::render( $post->ID, null, null, $pricingRule->getTieredPricingType(),
					$pricingRule->getPercentageTieredPricingRules(), $pricingRule->getFixedTieredPricingRules() );
			?>
		</div>
		
		
		<?php
	}
}