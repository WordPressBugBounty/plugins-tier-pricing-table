<?php namespace TierPricingTable\Addons\CartUpsells;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class UpsellsSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Cart Upsell Messages', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Display targeted messages in the cart to encourage customers to reach the next discount tier.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'cart-upsells';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Enable cart upsells', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_enabled',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc_tip' => false,
			),
			
			array(
				'title'        => __( 'Template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'cart_upsell_template',
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'placeholders' => array(
					'tp_required_quantity',
					'tp_next_price',
					'tp_next_discount',
					'tp_actual_discount',
				),
				'default'      => __( 'Add <b>{tp_required_quantity}</b> more to get <b>{tp_next_price}</b> each',
					'tier-pricing-table' ),
				'desc_tip'     => false,
			),
			
			array(
				'title'    => __( 'Quantity link', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_link',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc_tip' => false,
				'desc'     => __( 'The required quantity in the message is a link: one click sets the cart line to the next tier and updates the cart.', 'tier-pricing-table' ),
			),
			
			array(
				'title'    => __( 'Progress bar', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_progress',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc_tip' => false,
				'desc'     => __( 'Show a bar under the message that fills up as the quantity approaches the next tier.', 'tier-pricing-table' ),
			),
			
			array(
				'title'    => __( 'Upsell text color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'cart_upsell_color',
				'type'     => 'color',
				'default'  => '#059669',
				'css'      => 'width:6em;',
				'desc_tip' => false,
				'desc'         => __( 'Note: This feature is not compatible with the Gutenberg Cart/Checkout blocks.',
					'tier-pricing-table' ),
			),
		);
	}
}
