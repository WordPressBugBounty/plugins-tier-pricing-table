<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderRulesSettingsSection;

/**
 * The minimum order of an order rule: an amount and/or a number of items the counted lines must reach
 * before the order can be placed (premium).
 */
class OrderMinimum extends FormTab {

	public function getId(): string {
		return 'order-minimum';
	}

	public function getTitle(): string {
		return __( 'Minimum order', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'An amount or a number of items before checkout', 'tier-pricing-table' );
	}

	public function getKinds(): array {
		return array( GlobalPricingRule::KIND_ORDER );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		$premium = tpt_fs()->can_use_premium_code();

		$this->renderSectionTitle( __( 'Minimum order', 'tier-pricing-table' ), array(
			'only_for_premium' => true,
			'description'      => __( 'The cart and the checkout, classic pages and blocks, block the order until the counted lines reach the minimum. Every matching cart rule\'s minimum must be met. The messages are set on the Cart rules settings page.', 'tier-pricing-table' ),
		) );

		woocommerce_wp_text_input( array(
			'id'                => 'tpt_order_minimum_amount',
			'label'             => __( 'Minimum amount', 'tier-pricing-table' ),
			'value'             => is_null( $pricingRule->getOrderMinimumAmount() ) ? '' : wc_format_localized_price( $pricingRule->getOrderMinimumAmount() ),
			'data_type'         => 'price',
			'placeholder'       => __( 'No minimum', 'tier-pricing-table' ),
			'custom_attributes' => $premium ? array() : array( 'disabled' => 'disabled' ),
		) );

		woocommerce_wp_text_input( array(
			'id'                => 'tpt_order_minimum_quantity',
			'label'             => __( 'Minimum items', 'tier-pricing-table' ),
			'value'             => is_null( $pricingRule->getOrderMinimumQuantity() ) ? '' : (string) $pricingRule->getOrderMinimumQuantity(),
			'type'              => 'number',
			'placeholder'       => __( 'No minimum', 'tier-pricing-table' ),
			'custom_attributes' => $premium ? array( 'min' => '1', 'step' => '1' ) : array( 'min' => '1', 'step' => '1', 'disabled' => 'disabled' ),
		) );

		$this->renderHint( __( 'The counted lines are the ones matching the Products tab, or the whole cart when it is empty. The amount follows the subtotal basis of the Cart rules settings.', 'tier-pricing-table' ) );

		$this->renderHint( sprintf(
			/* translators: %s: link "Edit the minimum order messages" */
			esc_html__( '%s, with a live preview of the notice in the cart and at checkout.', 'tier-pricing-table' ),
			'<a href="' . esc_url( OrderRulesSettingsSection::url( 'minimum-order' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Edit the minimum order messages', 'tier-pricing-table' ) . '</a>'
		), array( 'show_icon' => false ) );
	}

	public function getIcon(): string {
		return 'dashicons-cart';
	}
}
