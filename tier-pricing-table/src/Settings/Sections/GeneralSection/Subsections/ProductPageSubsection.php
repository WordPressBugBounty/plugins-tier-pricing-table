<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class ProductPageSubsection extends SubsectionAbstract {

	public function getTitle() {
		return __( 'Tiered pricing on product page', 'tier-pricing-table' );
	}

	public function getDescription() {
		return __( 'This section controls how the tiered pricing will look and behave on the product page.',
			'tier-pricing-table' );
	}

	public function getSlug() {
		return 'products';
	}

	public function getSettings() {
		return array(
			array(
				'title'    => __( 'Show tiered pricing', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Display a tiered pricing on the product page? Prices will be changing even if the tiered pricing is not displaying.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Display tiered pricing as', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'table'   => __( 'Table', 'tier-pricing-table' ),
					'blocks'  => __( 'Blocks', 'tier-pricing-table' ),
					'options' => __( 'Options', 'tier-pricing-table' ),
					'tooltip' => __( 'Tooltip', 'tier-pricing-table' ),
				),
				'desc'     => __( 'Type of displaying tiered pricing on the product page.', 'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'table',
			),
			array(
				'title'    => __( 'Quantity displaying type', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'quantity_type',
				'type'     => 'select',
				'options'  => array(
					'range'  => __( 'Range', 'tier-pricing-table' ),
					'static' => __( 'Static values', 'tier-pricing-table' ),
				),
				'desc'     => __( 'How to display quantities in the tiered pricing. Range for a range of quantities that a tiered price will apply for or static for a minimum quantity that a tiered price will apply for.', 'tier-pricing-table' ),
				'desc_tip' => false,
				'default'  => 'range',
			),
			array(
				'title'    => __( 'Tooltip icon color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_color',
				'type'     => 'color',
				'default'  => '#96598A',
				'css'      => 'width:6em;',
				'desc'     => __( 'Color of the icon', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Tooltip icon size (px)', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_size',
				'type'     => 'number',
				'default'  => '15',
				'desc'     => __( 'Size of the icon', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Tooltip border', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'tooltip_border',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'    => __( 'Pricing title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'table_title',
				'type'     => 'text',
				'default'  => '',
				'desc'     => __( 'The name is displaying above the tiered pricing.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Tiered pricing position', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'position_hook',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_add_to_cart_button'     => __( 'Above buy button', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_button'      => __( 'Below buy button', 'tier-pricing-table' ),
					'woocommerce_before_add_to_cart_form'       => __( 'Above add to cart form', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_form'        => __( 'Below add to cart form', 'tier-pricing-table' ),
					'woocommerce_single_product_summary'        => __( 'Above product title', 'tier-pricing-table' ),
					'woocommerce_before_single_product_summary' => __( 'Before product summary', 'tier-pricing-table' ),
					'woocommerce_after_single_product_summary'  => __( 'After product summary', 'tier-pricing-table' ),
				),
				'desc'     => __( 'Where to display the tiered pricing.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Active pricing tier color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'selected_quantity_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#96598A',
			),
			array(
				'title'    => __( 'Quantity column title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'head_quantity_text',
				'type'     => 'text',
				'default'  => __( 'Quantity', 'tier-pricing-table' ),
				'desc'     => __( 'Name of the quantity column. Set price column and quantity column name blank to do not show table heading.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Price column title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'head_price_text',
				'type'     => 'text',
				'default'  => __( 'Price', 'tier-pricing-table' ),
				'desc'     => __( 'Name of the price column. Let price column and quantity column names blank to do not show table heading.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Show percentage discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_column',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'desc'    => __( 'Show a percentage discount as column for table or for pricing blocks', 'tier-pricing-table' ),
				'default' => 'yes',

			),
			array(
				'title'    => __( 'Discount column title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'head_discount_text',
				'type'     => 'text',
				'default'  => __( 'Discount (%)', 'tier-pricing-table' ),
				'desc'     => __( 'Name of discount column.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),

			array(
				'title'   => __( 'Show original product price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_original_product_price',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show price with no discount in the option.', 'tier-pricing-table' ),
			),

			array(
				'title'             => __( 'Show total in each option', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'options_show_total',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Show total for each option when an option is active.', 'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ]
			),

			array(
				'title'        => __( 'Option text', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_rounded_discount',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show the default option', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_default_option',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the option without a discount (option with a regular product price)',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Default option text', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_default_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'             => __( 'Set tiered price on a click', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'clickable_table_rows',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Change product quantity on click at table pricing row or pricing block',
					'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ]
			),
			array(
				'title'             => __( 'Show total price', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'show_total_price',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'no',
				'desc'              => __( 'Calculate and show total price on the product page', 'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ]
			),
		);
	}
}