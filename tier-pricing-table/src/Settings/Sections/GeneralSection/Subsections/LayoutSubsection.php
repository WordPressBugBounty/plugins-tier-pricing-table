<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTTableColumnsField;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\CustomOptions\TPTTwoFields;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

class LayoutSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Tiered pricing layout', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'How the tiered pricing will look and behave in your store.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'layout';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Show tiered pricing on product page', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Display tiered pricing on the product page? Prices remain dynamic even if the tiered pricing is not displayed. You can also display tiered pricing via shortcode, Gutenberg block or Elementor widget.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Default layout', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => TierPricingTablePlugin::getAvailablePricingLayouts(),
				'desc'     => __( 'Default layout for tiered pricing on the product page. The layout can be customized individually per product.',
					'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'table',
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
				'title'    => __( 'Position on product page', 'tier-pricing-table' ),
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
					'____none____'                              => __( 'I display table via shortcode/gutenberg/elementor',
						'tier-pricing-table' ),
				),
				'desc'     => __( 'Where tiered pricing should be displayed on the product page.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			
			array(
				'title'    => __( 'Quantity displaying type', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'quantity_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'range'  => __( 'Range', 'tier-pricing-table' ),
					'static' => __( 'Static values', 'tier-pricing-table' ),
				),
				'desc'     => __( 'Range: Displays a range of quantities that a tiered price applies to. Static: Displays a minimum quantity that a tiered price applies to.', 'tier-pricing-table' ),
				'desc_tip' => false,
				'default'  => 'range',
			),
			array(
				'title'   => __( 'Active pricing tier color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'selected_quantity_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#96598A',
			),
			array(
				'title'    => __( 'Tooltip icon color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_color',
				'type'     => 'color',
				'default'  => '#96598A',
				'css'      => 'width:6em;',
				'desc'     => __( 'Color of the icon.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Tooltip icon size (px)', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_size',
				'type'     => 'number',
				'default'  => '15',
				'css'      => 'width:120px;',
				'desc'     => __( 'Size of the icon.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Tooltip border', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'tooltip_border',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Base unit name', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_quantity_measurement',
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => '',
					'plural'   => '',
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. It will be shown next to quantities. Leave empty to not add any.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Base unit name', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'blocks_quantity_measurement',
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
					'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. It will be shown next to quantities. Leave empty to not add any.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Columns titles', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_columns_titles',
				'options' => array(
					array(
						'label'   => __( 'Quantity', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_quantity_text',
						'default' => __( 'Quantity', 'tier-pricing-table' ),
					),
					array(
						'label'   => __( 'Discount', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_discount_text',
						'default' => __( 'Discount (%)', 'tier-pricing-table' ),
					),
					array(
						'label'   => __( 'Price', 'tier-pricing-table' ),
						'id'      => Settings::SETTINGS_PREFIX . 'head_price_text',
						'default' => __( 'Price', 'tier-pricing-table' ),
					),
				),
				'desc'    => __( 'Leave a column title empty to not show that column.' ),
				'type'    => TPTTableColumnsField::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show percentage discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_column',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show regular product price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_original_product_price',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the crossed out regular price in options.', 'tier-pricing-table' ),
			),
			
			array(
				'title'             => __( 'Show total pricing in option', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'options_show_total',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Show the total price in an active option.', 'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
			
			array(
				'title'        => __( 'Option template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_rounded_discount',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show the "no discount" option', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_default_option',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the option with a regular product price.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( '"No discount" option template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_default_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'        => __( 'Template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_price',
					'tp_rounded_discount',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show the "no discount" tier', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'plain_text_show_first_tier',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the tier with a regular product price.', 'tier-pricing-table' ),
			),
			
			array(
				'title'        => __( '"No discount" template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_first_tier_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_price'
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Clickable tiered pricing', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'clickable_table_rows',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Makes tiered pricing (table rows, blocks, options, etc) clickable.',
					'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
		);
	}
}
