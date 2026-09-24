<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;

class CartRulesSubsection extends SubsectionAbstract {

	public function getTitle(): string {
		return __( 'Cart rules by role', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'A minimum order amount or quantity for the whole cart per role, and payment or shipping methods limited to roles (the two groups below). Guests count as a role; a customer with several roles gets the strictest minimum. Off by default.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'cart-rules';
	}

	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Cart rules by role', 'tier-pricing-table' ),
				'id'      => CartRulesSettings::optionId( 'enabled' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'Applies the minimums below and the payment and shipping method limits of the next two groups in the cart and at checkout, for the classic pages and the blocks.', 'tier-pricing-table' ),
			),
			array(
				'title' => __( 'Minimum order', 'tier-pricing-table' ),
				'id'    => CartRulesSettings::optionId( 'minimums' ),
				'type'  => RoleMinimumsOption::FIELD_TYPE,
				'desc'  => __( 'Leave a cell empty for no minimum. The amount is the cart subtotal after tiered prices, before shipping.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Amount message', 'tier-pricing-table' ),
				'id'      => CartRulesSettings::optionId( 'amount_message' ),
				'type'    => 'text',
				'default' => CartRulesSettings::defaultAmountMessage(),
				'desc'    => __( 'Placeholders: {minimum}, {subtotal}.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Quantity message', 'tier-pricing-table' ),
				'id'      => CartRulesSettings::optionId( 'quantity_message' ),
				'type'    => 'text',
				'default' => CartRulesSettings::defaultQuantityMessage(),
				'desc'    => __( 'Placeholders: {minimum}, {count}.', 'tier-pricing-table' ),
			),
		);
	}
}
