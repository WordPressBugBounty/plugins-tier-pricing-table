<?php namespace TierPricingTable\Addons\YouSave;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class YouSaveSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( '"You Save" Badge', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Show customers exactly how much they save when tiered discounts are applied.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'you_save';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Enable "You Save" badge', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'you_save_enabled',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the difference between the regular price and a discounted price. You can also show it via the [tiered_price_you_save] shortcode.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Include sale price in calculation', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'you_save_consider_sale_price',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'The difference between the regular and sale price will be included in the “you save” calculation.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'you_save_template',
				'default'      => __( 'You save {tp_ys_total_price} ({tp_ys_percentage_discount}%)', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_ys_price',
					'tp_ys_total_price',
					'tp_ys_percentage_discount',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'   => __( '"You save" price color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'you_save_text_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#FF0000',
			),
		);
	}
}
