<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderRulesSettingsSection;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscount as OrderDiscountValue;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscountForm;

/**
 * The first tab of an order rule: the discount tiers on the cart and the label of the discount line. Free
 * shipping, the minimum order and the scope have tabs of their own.
 */
class OrderDiscount extends FormTab {

	public function getId(): string {
		return 'order';
	}

	public function getTitle(): string {
		return __( 'Discount', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Tiers and the discount line label', 'tier-pricing-table' );
	}

	public function getKinds(): array {
		return array( GlobalPricingRule::KIND_ORDER );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		$premium = tpt_fs()->can_use_premium_code();

		$this->renderSectionTitle( __( 'Discount tiers', 'tier-pricing-table' ), array(
			'description' => __( 'The lines are counted after tiered prices, before shipping. The highest tier reached is used. Whether the subtotal includes tax is set on the Cart rules settings page.', 'tier-pricing-table' ),
		) );

		OrderDiscountForm::render( $pricingRule, $premium );

		$this->renderSectionTitle( __( 'Label in the cart', 'tier-pricing-table' ) );

		woocommerce_wp_text_input( array(
			'id'          => 'tpt_order_label',
			'label'       => __( 'Discount line label', 'tier-pricing-table' ),
			'value'       => $pricingRule->getOrderLabel(),
			'placeholder' => OrderDiscountValue::defaultLabel(),
			'description' => __( 'Shown in the cart, at checkout and in e-mails. Placeholders: {discount}, {threshold}.', 'tier-pricing-table' ),
			'desc_tip'    => true,
		) );

		$this->renderHint( __( 'The discount is applied as an automatic coupon, so WooCommerce computes it, shows it as one line and counts it in Analytics. The checkout block shows the rule title as the coupon code.', 'tier-pricing-table' ) );

		$this->renderHint( sprintf(
			/* translators: %s: link "Customize the cart messages" */
			esc_html__( '%s: the message that leads to the next tier, the subtotal basis and how the discount behaves next to customer coupons, with a live preview of the cart, the checkout and the mini cart.', 'tier-pricing-table' ),
			'<a href="' . esc_url( OrderRulesSettingsSection::url( 'order-discount' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Customize the cart messages', 'tier-pricing-table' ) . '</a>'
		), array( 'show_icon' => false ) );
	}

	public function getIcon(): string {
		return '%';
	}
}
