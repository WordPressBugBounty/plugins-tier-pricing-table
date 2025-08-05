<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CartSubsection extends SubsectionAbstract {

	public function getTitle() {
		return __( 'Tiered pricing in the cart and checkout pages', 'tier-pricing-table' );
	}

	public function getDescription() {
		return __( 'This section controls how the tiered pricing will look and behave on the cart and checkout pages',
			'tier-pricing-table' );
	}

	public function getSlug() {
		return 'cart';
	}

	public function getSettings() {
		return array(
			array(
				'title'    => __( 'Consider different product variations as the same product during calculation tiered pricing',
					'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'summarize_variations',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc'     => __( 'For the same variable product, the plugin will consider all its variations as the same product when calculating a discount.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'             => __( 'Show tiered price in the cart as a discount', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'show_discount_in_cart',
				'desc'              => __( 'Show the crossed-out original price with a discounted price beside it. Example: ', 'tier-pricing-table' ) . ' <b><del>$10.00</del> $8.00<b>',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc_tip'          => true,
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => 'yes' ]
			),

			array(
				'title'             => __( 'Cart upsells', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'cart_upsell_enabled',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'no',
				'desc_tip'          => false,
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => 'yes' ]
			),

			array(
				'title'             => __( 'Cart upsell template', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'cart_upsell_template',
				'type'              => TPTTextTemplate::FIELD_TYPE,
				'placeholders'      => array(
					'tp_required_quantity',
					'tp_next_price',
					'tp_next_discount',
					'tp_actual_discount',
				),
				'default'           => __( 'Buy <b>{tp_required_quantity}</b> more to get <b>{tp_next_price}</b> each', 'tier-pricing-table' ),
				'desc_tip'          => false,
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => 'yes' ]
			),

			array(
				'title'             => __( 'Cart upsell color', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'cart_upsell_color',
				'type'              => 'color',
				'default'           => '#96598A',
				'css'               => 'width:6em;',
				'desc_tip'          => false,
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => 'yes' ]
			),
		);
	}
}