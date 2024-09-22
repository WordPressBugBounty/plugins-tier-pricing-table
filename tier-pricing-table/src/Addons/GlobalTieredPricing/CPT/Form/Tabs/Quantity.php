<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Forms\MinimumOrderQuantityForm;

class Quantity extends FormTab {

	public function getId(): string {
		return 'quantity';
	}

	public function getTitle(): string {
		return __( 'Quantity rules', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Specify minimum, maximum and quantity step for products.', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $pricingRule ) {

		$this->form->renderHint( __( 'Quantity rules are applied to products individually.', 'tier-pricing-table' ) );

		MinimumOrderQuantityForm::render( null, null, $pricingRule->getMinimum() );

		do_action( 'tiered_pricing_table/global_pricing/after_minimum_order_quantity_field', $pricingRule->getId(),
			$pricingRule );


		if ( ! tpt_fs()->can_use_premium_code() ) {
			$this->form->renderUpgradingNotice();
		}

	}
}
