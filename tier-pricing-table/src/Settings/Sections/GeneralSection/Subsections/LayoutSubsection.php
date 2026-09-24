<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Addons\LayoutConfigurator\LayoutConfigurator;
use TierPricingTable\Addons\LayoutConfigurator\LayoutConfiguratorAddon;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\Sections\GeneralSection\GeneralSection;
use TierPricingTable\Settings\CustomOptions\TPTTableColumnsField;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

/**
 * Product-page layout settings. With the layout configurator add-on on (the default) the whole
 * subsection is one configurator field with a live preview; with it off the classic rows are shown.
 */
class LayoutSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		// the configurator prints its own heading, next to the page switch
		return LayoutConfiguratorAddon::isActive() ? '' : __( 'Pricing Layout Settings', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return LayoutConfiguratorAddon::isActive()
			? ''
			: __( 'Customize the appearance and behavior of your tiered pricing tables.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'layout';
	}
	
	public function getSettings(): array {
		return LayoutConfiguratorAddon::isActive() ? $this->getConfiguratorSettings() : $this->getClassicSettings();
	}
	
	protected function getConfiguratorSettings(): array {
		return array(
			array(
				'title' => __( 'Pricing Display', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . 'display_type',
				'type'  => LayoutConfigurator::FIELD_TYPE,
				'desc'  => '',
			),
			// Options saved from the configurator's hidden inputs; rendered by nothing.
			...LayoutConfigurator::getSavedOnlyFields(),
		);
	}
	
	/**
	 * The classic rows, one option each, shown while the layout configurator add-on is off.
	 */
	protected function getClassicSettings(): array {
		return array(
			array(
				'title'    => __( 'Show tiered pricing automatically', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Automatically insert the pricing layout on the product page. If disabled, pricing remains dynamic but you must insert the layout manually via shortcode, block, or widget.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Default visual layout', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'display_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => TierPricingTablePlugin::getAvailablePricingLayouts(),
				'desc'     => __( 'Choose the default visual layout. You can also customize this individually per product.',
					'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'table',
			),
			array(
				'title'    => __( 'Options design style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_options_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions()['options'],
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Table design style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_table_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions()['table'],
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Blocks design style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_blocks_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions()['blocks'],
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Dropdown design style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_dropdown_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions()['dropdown'],
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Plain text design style', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'pricing_plain_text_style',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions()['plain-text'],
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Enable compact layout', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'compact_layout',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc'     => __( 'Apply a space-saving compact design for the selected layout.', 'tier-pricing-table' ),
			),
			array(
				'title'    => __( 'Layout title', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'table_title',
				'type'     => 'text',
				'default'  => '',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Layout position', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'position_hook',
				'type'     => 'select',
				'options'  => array(
					'woocommerce_before_add_to_cart_button'     => __( 'Above add to cart button', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_button'      => __( 'Below add to cart button', 'tier-pricing-table' ),
					'woocommerce_before_add_to_cart_form'       => __( 'Above add to cart form', 'tier-pricing-table' ),
					'woocommerce_after_add_to_cart_form'        => __( 'Below add to cart form', 'tier-pricing-table' ),
					'woocommerce_single_product_summary'        => __( 'Above product title', 'tier-pricing-table' ),
					'woocommerce_before_single_product_summary' => __( 'Before product summary', 'tier-pricing-table' ),
					'woocommerce_after_single_product_summary'  => __( 'After product summary', 'tier-pricing-table' ),
					'____none____'                              => __( 'I display tiered pricing via shortcode/gutenberg/elementor',
						'tier-pricing-table' ),
				),
				'desc'     => __( 'Choose where you what tiered pricing be displayed on the product page.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Quantity format', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'quantity_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'range'  => __( 'Range', 'tier-pricing-table' ),
					'static' => __( 'Static values', 'tier-pricing-table' ),
				),
				'desc'     => __( '"Range" displays the quantity range a tier applies to. "Static" displays only the minimum quantity required.',
					'tier-pricing-table' ),
				'desc_tip' => false,
				'default'  => 'range',
			),
			array(
				'title'             => __( 'Spacing', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'layout_spacing',
				'type'              => 'number',
				'default'           => '',
				'css'               => 'width: 6em;',
				'custom_attributes' => array( 'min' => 0, 'max' => 40, 'step' => 1, 'placeholder' => __( 'Auto', 'tier-pricing-table' ) ),
				'desc'              => __( 'Gap between the pricing blocks, options or card rows, in pixels. Leave empty to keep each design style\'s own spacing.', 'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Cell padding', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'cell_padding',
				'type'              => 'number',
				'default'           => '',
				'css'               => 'width: 6em;',
				'custom_attributes' => array( 'min' => 0, 'max' => 30, 'step' => 1, 'placeholder' => __( 'Auto', 'tier-pricing-table' ) ),
				'desc'              => __( 'Padding inside the cells of the table and horizontal table layouts, in pixels. Leave empty to keep each design style\'s own padding; a value also overrides the theme\'s cell padding.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Tier order', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'tiers_order',
				'type'         => TPTDisplayType::FIELD_TYPE,
				'options'      => array(
					'asc'  => __( 'Ascending', 'tier-pricing-table' ),
					'desc' => __( 'Descending', 'tier-pricing-table' ),
				),
				'descriptions' => array(
					'asc'  => __( 'Smallest quantity first', 'tier-pricing-table' ),
					'desc' => __( 'Biggest discount first', 'tier-pricing-table' ),
				),
				'default'      => 'asc',
			),
			array(
				'title'   => __( 'Active pricing tier color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'selected_quantity_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#3858e9',
			),
			array(
				'title'   => __( 'Discount badge color', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'discount_badge_color',
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '',
				'desc'    => __( 'The discount badge of table styles #2 to #6. Styles #2 and #6 show a light tint of this color behind text in this color, styles #3 and #4 a solid badge with white text, style #5 colors its savings bar and percentage. Leave empty to keep the style\'s own colors (the active tier color on styles #3 to #5).', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Active tier left border', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_active_border',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Table styles #1, #5 and #6 mark the active tier with a left border in the active tier color. Switch it off to keep the row highlight only.', 'tier-pricing-table' ),
			),
			array(
				'title'    => __( 'Tooltip icon color', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tooltip_color',
				'type'     => 'color',
				'default'  => '#3858e9',
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
				'title'   => __( 'Quantity unit label', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'table_quantity_measurement',
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => '',
					'plural'   => '',
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Quantity unit label', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'blocks_quantity_measurement',
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
					'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Table column headers', 'tier-pricing-table' ),
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
				'desc'    => __( 'Leave a column title empty to hide that column entirely.', 'tier-pricing-table' ),
				'type'    => TPTTableColumnsField::FIELD_TYPE,
			),
			array(
				'title'   => __( 'Show discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_column',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the discount in pricing blocks that offer a discount.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Discount format', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'discount_format',
				'type'         => TPTDisplayType::FIELD_TYPE,
				'options'      => array(
					'percentage' => __( 'Percentage', 'tier-pricing-table' ),
					'amount'     => __( 'Amount saved', 'tier-pricing-table' ),
					'both'       => __( 'Both', 'tier-pricing-table' ),
				),
				'descriptions' => array(
					'percentage' => __( 'e.g. 10%', 'tier-pricing-table' ),
					'amount'     => __( 'e.g. $4.50', 'tier-pricing-table' ),
					'both'       => __( 'e.g. 10% ($4.50)', 'tier-pricing-table' ),
				),
				'desc'         => __( 'How the discount column and discount badges show a tier\'s discount.', 'tier-pricing-table' ),
				'desc_tip'     => false,
				'default'      => 'percentage',
			),
			array(
				'title'   => __( 'Show original price crossed out', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_original_product_price',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Pricing options will show a crossed-out regular price next to the discounted tier price.',
					'tier-pricing-table' ),
			),
			
			array(
				'title'             => __( 'Show total calculated price for selected option', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'options_show_total',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'The selected pricing option will dynamically display the total calculated cost.', 'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
			array(
				'title'        => __( 'Pricing option text template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_rounded_discount',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Use the variables above to build the template for the pricing option.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show base price (no discount) option', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'options_show_default_option',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Display an option for the regular product price (e.g. 1 item) where no tier discount is applied.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Base price option template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'options_default_option_text',
				'default'      => __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Customize the template for the base price option.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Pricing string template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_discount',
					'tp_price',
					'tp_rounded_discount',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Use the variables above to build the template for the pricing string.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Show first tier pricing string', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'plain_text_show_first_tier',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the tier with a regular product price. This is the first pricing tier where no discount is offered.',
					'tier-pricing-table' ),
			),
			
			array(
				'title'        => __( 'First tier pricing string template', 'tier-pricing-table' ),
				'id'           => Settings::SETTINGS_PREFIX . 'plain_text_first_tier_template',
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_price',
					'tp_base_unit_name',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'desc'         => __( 'Set up the first pricing tier template where a discount is not offered.',
					'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Make pricing tiers clickable', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'clickable_table_rows',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Allow customers to click on a pricing tier (table row, block, or option) to automatically select that quantity.',
					'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
		);
	}
}
