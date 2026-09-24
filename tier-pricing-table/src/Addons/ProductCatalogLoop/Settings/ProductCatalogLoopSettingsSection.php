<?php namespace TierPricingTable\Addons\ProductCatalogLoop\Settings;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTQuantityMeasurementField;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\CustomOptions\TPTTextTemplate;
use TierPricingTable\Settings\Sections\GeneralSection\GeneralSection;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

class ProductCatalogLoopSettingsSection extends SectionAbstract {
	
	public static function getOptionID( $option ): string {
		return self::getSettingsPrefix() . $option;
	}
	
	public static function getSettingsPrefix(): string {
		return Settings::SETTINGS_PREFIX . 'shop_loop_display_';
	}
	
	public function getName(): string {
		return __( 'Shop & Categories', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'shop-loop-display';
	}
	
	public function getSettings(): array {
		
		$availableLayouts = TierPricingTablePlugin::getAvailablePricingLayouts();
		unset( $availableLayouts['tooltip'] );
		
		return array(
			array(
				'title' => __( 'Tiered Pricing on Shop & Category Pages', 'tier-pricing-table' ),
				'desc'  => __( 'Control how tiered pricing appears on shop and category pages.',
					'tier-pricing-table' ),
				'type'  => 'title',
			),
			array(
				'title'                => __( 'Enable on Shop & Categories', 'tier-pricing-table' ),
				'id'                   => self::getOptionID( 'enabled' ),
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
				'extended_description' => ( function () {
					ob_start();
					?>
					<p>
						<?php
							esc_html_e( 'Turn this on to display tiered pricing directly within your shop and category pages.',
								'tier-pricing-table' );
						?>
					</p>
					<p>
						<b><?php esc_html_e( 'Note:', 'tier-pricing-table' ); ?></b>
						<?php esc_html_e( 'Depending on your theme, you may need minor CSS adjustments for optimal display.', 'tier-pricing-table' ); ?>
					</p>
					<?php
					return ob_get_clean();
				} )(),
				'desc'                 => __( 'Display tiered pricing tables on shop and category pages.',
					'tier-pricing-table' ),
				'desc_tip'             => true,
			),
			array(
				'title'    => __( 'Position on product grid item', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'position' ),
				'type'     => 'select',
				'default'  => 'woocommerce_after_shop_loop_item__6',
				'options'  => array(
					'woocommerce_after_shop_loop_item__6'  => __( 'Above add-to-cart button', 'tier-pricing-table' ),
					'woocommerce_after_shop_loop_item__15' => __( 'Below add-to-cart button', 'tier-pricing-table' ),
					'woocommerce_shop_loop_item_title__5'  => __( 'Above product title', 'tier-pricing-table' ),
					'woocommerce_shop_loop_item_title__15' => __( 'Below product title', 'tier-pricing-table' ),
				),
				'desc'     => __( 'Choose where the tiered pricing table appears relative to the product image and details.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'        => __( 'Where it shows', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'scope' ),
				'type'         => TPTDisplayType::FIELD_TYPE,
				'options'      => self::getScopeOptions(),
				'descriptions' => array(
					'everywhere' => __( 'Every product list', 'tier-pricing-table' ),
					'selected'   => __( 'Chosen lists and categories', 'tier-pricing-table' ),
				),
				'default'      => 'everywhere',
			),
			array(
				'title'   => __( 'Product lists', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'contexts' ),
				'type'    => TPTCheckboxListOption::FIELD_TYPE,
				'options' => self::getContextOptions(),
				'default' => '',
				'desc'    => __( 'The product lists that show tiered pricing. Leave every box unticked for all lists.', 'tier-pricing-table' ),
			),
			array(
				'title'       => __( 'Only for products in categories', 'tier-pricing-table' ),
				'id'          => self::getOptionID( 'categories' ),
				'type'        => TPTCheckboxListOption::FIELD_TYPE,
				'display'     => 'category-search',
				'placeholder' => __( 'All categories', 'tier-pricing-table' ),
				'default'     => '',
				'desc'        => __( 'Leave empty to show tiered pricing for every product. A category covers its child categories.', 'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Tiers per grid item', 'tier-pricing-table' ),
				'id'                => self::getOptionID( 'tiers_limit' ),
				'type'              => 'number',
				'default'           => '',
				'css'               => 'width: 6em;',
				'custom_attributes' => array( 'min' => 1, 'max' => 20, 'step' => 1, 'placeholder' => __( 'All', 'tier-pricing-table' ) ),
				'desc'              => __( 'How many tiers a grid item lists; the rest sit behind a "+N more" link to the product page. Leave empty to list them all.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Bulk savings badge', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'badge_enabled' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'A badge on the product image with the biggest discount the product\'s tiers give.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Badge text', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'badge_template' ),
				'type'         => TPTTextTemplate::FIELD_TYPE,
				'placeholders' => self::getBadgePlaceholders(),
				'default'      => self::getDefaultBadgeTemplate(),
			),
			array(
				'title'   => __( 'Badge position', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'badge_position' ),
				'type'    => 'select',
				'default' => 'top-left',
				'options' => self::getBadgePositionOptions(),
			),
			array(
				'title'   => __( 'Badge color', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'badge_color' ),
				'type'    => 'color',
				'default' => '',
				'css'     => 'width:6em;',
				'desc'    => __( 'Leave empty to use the active tier color.', 'tier-pricing-table' ),
			),
			array(
				'title'                => __( 'Add quantity selector to grids', 'tier-pricing-table' ),
				'id'                   => self::getOptionID( 'show_quantity_field' ),
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
				'extended_description' => ( function () {
					ob_start();
					?>
					<p>
						<?php
							esc_html_e( 'Add a quantity input field directly to items on the shop and category pages.', 'tier-pricing-table' );
						?>
					</p>
					<p>
						<b>
							<?php
								esc_html_e( 'Only enable this if your theme doesn\'t already provide quantity selectors on the shop page.',
									'tier-pricing-table' );
							?>
						</b>
					</p>
					<?php
					return ob_get_clean();
				} )(),
				'custom_attributes'    => [ 'data-tiered-pricing-premium-option' => true ],
			),
			array(
				'title'                => __( 'Dynamic price', 'tier-pricing-table' ),
				'id'                   => self::getOptionID( 'dynamic_price' ),
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'yes',
				'extended_description' => ( function () {
					ob_start();
					?>
					<p>
						<?php
							esc_html_e( 'Update the displayed price to the tier price when the customer changes the quantity in the grid. Needs a quantity field on the grid item, from this plugin, the theme or another plugin.',
								'tier-pricing-table' );
						?>
					</p>
					<?php
					return ob_get_clean();
				} )(),
				'desc_tip'             => true,
			),
			array(
				'title'    => __( 'Compact layout', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'use_reduced_styles' ),
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Apply minimal styling to pricing tables to better fit the constrained space of catalog grids.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'type' => 'sectionend',
			),
			
			array(
				'title' => __( 'Pricing Layout Settings', 'tier-pricing-table' ),
				'desc'  => __( 'Customize the appearance and behavior of tiered pricing on catalog pages.',
					'tier-pricing-table' ),
				'id'    => self::getOptionId( 'layout_settings' ),
				'type'  => 'title',
			),
			
			array(
				'title'    => __( 'Layout source', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'layout_settings' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => array(
					'default' => __( 'Same as product page', 'tier-pricing-table' ),
					'custom'  => __( 'Custom', 'tier-pricing-table' ),
				),
				'desc'     => __( 'Choose whether to inherit the layout from the product page or use a custom layout for catalogs.',
					'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'default',
			),
			array(
				'title'    => __( 'Catalog layout', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'layout' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => $availableLayouts,

				'desc'     => __( 'Select the specific visual template for the catalog page.', 'tier-pricing-table' ),
				'desc_tip' => true,
				'default'  => 'table',
			),
			array(
				'title'    => __( 'Table design style', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'pricing_table_style' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions( true )['table'],
				'default'  => 'default',
				'value'    => self::getTableStyle(),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Blocks design style', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'pricing_blocks_style' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions( true )['blocks'],
				'default'  => 'default',
				'value'    => self::getBlocksStyle(),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Options design style', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'pricing_options_style' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions( true )['options'],
				'default'  => 'default',
				'value'    => self::getOptionsStyle(),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Dropdown design style', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'pricing_dropdown_style' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions( true )['dropdown'],
				'default'  => 'default',
				'value'    => self::getDropdownStyle(),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Plain text design style', 'tier-pricing-table' ),
				'id'       => self::getOptionID( 'pricing_plain_text_style' ),
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => GeneralSection::getStyleOptions( true )['plain-text'],
				'default'  => 'default',
				'value'    => self::getPlainTextStyle(),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Pricing title', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'title' ),
				'type'    => 'text',
				'default' => '',
				'desc'    => __( 'Text displayed above the tiered pricing block.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Quantity display type', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'quantity_type' ),
				'type'    => TPTDisplayType::FIELD_TYPE,
				'options' => array(
					'range'  => __( 'Range', 'tier-pricing-table' ),
					'static' => __( 'Static values', 'tier-pricing-table' ),
				),
				'descriptions' => array(
					'range'  => __( 'e.g. 10 - 24 pieces', 'tier-pricing-table' ),
					'static' => __( 'e.g. 10+ pieces', 'tier-pricing-table' ),
				),
				'default' => 'range',
			),
			array(
				'title'             => __( 'Spacing', 'tier-pricing-table' ),
				'id'                => self::getOptionID( 'layout_spacing' ),
				'type'              => 'number',
				'default'           => '',
				'css'               => 'width: 6em;',
				'custom_attributes' => array( 'min' => 0, 'max' => 40, 'step' => 1, 'placeholder' => __( 'Auto', 'tier-pricing-table' ) ),
				'desc'              => __( 'Gap between the pricing blocks, options or card rows, in pixels. Leave empty to keep each design style\'s own spacing.', 'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Cell padding', 'tier-pricing-table' ),
				'id'                => self::getOptionID( 'cell_padding' ),
				'type'              => 'number',
				'default'           => '',
				'css'               => 'width: 6em;',
				'custom_attributes' => array( 'min' => 0, 'max' => 30, 'step' => 1, 'placeholder' => __( 'Auto', 'tier-pricing-table' ) ),
				'desc'              => __( 'Padding inside the cells of the table and horizontal table layouts, in pixels. Leave empty to keep each design style\'s own padding; a value also overrides the theme\'s cell padding.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Tier order', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'tiers_order' ),
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
				'id'      => self::getOptionID( 'selected_quantity_color' ),
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '#3858e9',
			),
			array(
				'title'   => __( 'Discount badge color', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'discount_badge_color' ),
				'type'    => 'color',
				'css'     => 'width:6em;',
				'default' => '',
				'desc'    => __( 'The discount badge of table styles #2 to #6. Styles #2 and #6 show a light tint of this color behind text in this color, styles #3 and #4 a solid badge with white text, style #5 colors its savings bar and percentage. Leave empty to keep the style\'s own colors (the active tier color on styles #3 to #5).', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Active tier left border', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'table_active_border' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Table styles #1, #5 and #6 mark the active tier with a left border in the active tier color. Switch it off to keep the row highlight only.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Unit label', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'table_quantity_measurement' ),
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => '',
					'plural'   => '',
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Unit label', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'blocks_quantity_measurement' ),
				'type'    => TPTQuantityMeasurementField::FIELD_TYPE,
				'default' => array(
					'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
					'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
				),
				'desc'    => __( 'For example: pieces, boxes, bottles, packs, etc. This will be shown next to quantity values. Leave blank to skip adding a unit label.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Quantity column title', 'tier-pricing-table' ),
				'default' => __( 'Quantity', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'quantity_column_title' ),
				'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Discount column title', 'tier-pricing-table' ),
				'default' => __( 'Discount', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'discount_column_title' ),
				'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Price column title', 'tier-pricing-table' ),
				'default' => __( 'Price', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'price_column_title' ),
				'desc'    => __( 'Leave empty to not show this column.', 'tier-pricing-table' ),
				'type'    => 'text',
			),
			array(
				'title'   => __( 'Show discount', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'blocks_show_discount' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'        => __( 'Discount format', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'discount_format' ),
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
				'default'      => 'percentage',
			),
			array(
				'title'   => __( 'Show regular product price', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'options_show_original_product_price' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the crossed out regular price in options.', 'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Show total pricing in option', 'tier-pricing-table' ),
				'id'                => self::getOptionID( 'options_show_total' ),
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Show the total price in an active option.', 'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
			array(
				'title'        => __( 'Option template', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'options_option_text' ),
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
				'id'      => self::getOptionID( 'options_show_default_option' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the option with a regular product price.', 'tier-pricing-table' ),
			),
			array(
				'title'        => __( '"No discount" option template', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'options_default_option_text' ),
				'default'      => __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'        => __( 'Template', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'plain_text_template' ),
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
				'id'      => self::getOptionID( 'plain_text_show_first_tier' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show the tier with a regular product price.', 'tier-pricing-table' ),
			),
			
			array(
				'title'        => __( '"No discount" template', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'plain_text_first_tier_template' ),
				'default'      => __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>',
					'tier-pricing-table' ),
				'placeholders' => array(
					'tp_quantity',
					'tp_price',
				),
				'type'         => TPTTextTemplate::FIELD_TYPE,
			),
			array(
				'title'             => __( 'Clickable tiered pricing', 'tier-pricing-table' ),
				'id'                => self::getOptionID( 'clickable_table_rows' ),
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'yes',
				'desc'              => __( 'Makes tiered pricing (table rows, blocks, options, etc) clickable.',
					'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
			array(
				'type' => 'sectionend',
			),
		);
	}
	
	/**
	 * The product lists tiered pricing can show in, keyed by the value stored in the "contexts" option.
	 */
	public static function getContextOptions(): array {
		return array(
			'shop'        => __( 'Shop page', 'tier-pricing-table' ),
			'category'    => __( 'Category pages', 'tier-pricing-table' ),
			'tag'         => __( 'Tag and attribute pages', 'tier-pricing-table' ),
			'search'      => __( 'Search results', 'tier-pricing-table' ),
			'related'     => __( 'Related products', 'tier-pricing-table' ),
			'upsells'     => __( 'Upsells', 'tier-pricing-table' ),
			'cross_sells' => __( 'Cross-sells in the cart', 'tier-pricing-table' ),
			'shortcode'   => __( 'Product shortcodes and other lists', 'tier-pricing-table' ),
			'blocks'      => __( 'Product Collection block', 'tier-pricing-table' ),
		);
	}
	
	public static function getScopeOptions(): array {
		return array(
			'everywhere' => __( 'Everywhere', 'tier-pricing-table' ),
			'selected'   => __( 'Selected places', 'tier-pricing-table' ),
		);
	}
	
	/**
	 * Whether the display is limited to chosen lists and categories.
	 */
	public static function isScopeSelected(): bool {
		return 'selected' === get_option( self::getOptionID( 'scope' ), 'everywhere' );
	}
	
	/**
	 * The enabled contexts; nothing stored, or the "everywhere" scope, means all of them.
	 */
	public static function getContexts(): array {
		$known = array_keys( self::getContextOptions() );
		
		if ( ! self::isScopeSelected() ) {
			return $known;
		}
		
		$chosen = array_values( array_intersect( TPTCheckboxListOption::toArray( get_option( self::getOptionID( 'contexts' ), '' ) ), $known ) );
		
		return $chosen ? $chosen : $known;
	}
	
	/**
	 * Product category ids the display is limited to; empty means every product.
	 */
	public static function getCategories(): array {
		if ( ! self::isScopeSelected() ) {
			return array();
		}
		
		return array_values( array_filter( array_map( 'intval', TPTCheckboxListOption::toArray( get_option( self::getOptionID( 'categories' ), '' ) ) ) ) );
	}
	
	/**
	 * Names of the chosen categories, keyed by id (for the pickers; the full list is never loaded).
	 */
	public static function getCategoryNames( array $ids ): array {
		$names = array();
		
		foreach ( $ids as $id ) {
			$term = get_term( (int) $id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$path = array();
				foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat' ) ) as $ancestorId ) {
					$ancestor = get_term( $ancestorId, 'product_cat' );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$path[] = $ancestor->name;
					}
				}
				$path[]             = $term->name;
				$names[ (int) $id ] = implode( ' > ', $path );
			}
		}
		
		return $names;
	}
	
	public static function getTiersLimit(): int {
		return max( 0, (int) get_option( self::getOptionID( 'tiers_limit' ), '' ) );
	}
	
	public static function isBadgeEnabled(): bool {
		return 'yes' === get_option( self::getOptionID( 'badge_enabled' ), 'no' );
	}
	
	public static function getDefaultBadgeTemplate(): string {
		return __( 'Save up to {tp_max_discount}', 'tier-pricing-table' );
	}
	
	public static function getBadgeTemplate(): string {
		$template = (string) get_option( self::getOptionID( 'badge_template' ), '' );
		
		return '' !== trim( $template ) ? $template : self::getDefaultBadgeTemplate();
	}
	
	public static function getBadgePlaceholders(): array {
		return array( 'tp_max_discount', 'tp_lowest_price', 'tp_lowest_quantity', 'tp_max_saving' );
	}
	
	public static function getBadgePositionOptions(): array {
		return array(
			'top-left'  => __( 'Top left', 'tier-pricing-table' ),
			'top-right' => __( 'Top right', 'tier-pricing-table' ),
		);
	}
	
	public static function getBadgePosition(): string {
		return 'top-right' === get_option( self::getOptionID( 'badge_position' ), 'top-left' ) ? 'top-right' : 'top-left';
	}
	
	public static function getBadgeColor(): string {
		return (string) get_option( self::getOptionID( 'badge_color' ), '' );
	}
	
	public static function isEnabled(): bool {
		return 'yes' === get_option( self::getOptionID( 'enabled' ), 'no' );
	}
	
	public static function getPosition(): array {
		$hook = get_option( self::getOptionID( 'position' ), 'woocommerce_after_shop_loop_item__6' );
		
		$hook = explode( '__', $hook );
		
		return array(
			'hook'     => ! empty( $hook[0] ) ? $hook[0] : '__none__',
			'priority' => ! empty( $hook[1] ) ? $hook[1] : 15,
		);
	}
	
	public static function isCustomLayoutSettings(): bool {
		return 'custom' === get_option( self::getOptionID( 'layout_settings' ), 'default' );
	}
	
	public static function getLayoutType(): string {
		return get_option( self::getOptionID( 'layout' ), 'table' );
	}
	
	/**
	 * Catalog design styles. Until a catalog style is saved, the product page style applies (that is how
	 * catalogs always rendered before they had their own style options).
	 */
	public static function getTableStyle(): string {
		return self::catalogStyle( 'table', (string) get_option( self::getOptionID( 'pricing_table_style' ), '' ) ?: GeneralSection::getPricingTableStyle() );
	}
	
	public static function getBlocksStyle(): string {
		return self::catalogStyle( 'blocks', (string) get_option( self::getOptionID( 'pricing_blocks_style' ), '' ) ?: GeneralSection::getPricingBlocksStyle() );
	}
	
	public static function getOptionsStyle(): string {
		return self::catalogStyle( 'options', (string) get_option( self::getOptionID( 'pricing_options_style' ), '' ) ?: GeneralSection::getPricingOptionsStyle() );
	}
	
	public static function getDropdownStyle(): string {
		return self::catalogStyle( 'dropdown', (string) get_option( self::getOptionID( 'pricing_dropdown_style' ), '' ) ?: GeneralSection::getPricingDropdownStyle() );
	}
	
	public static function getPlainTextStyle(): string {
		return self::catalogStyle( 'plain-text', (string) get_option( self::getOptionID( 'pricing_plain_text_style' ), '' ) ?: GeneralSection::getPricingPlainTextStyle() );
	}
	
	/**
	 * A style withheld from shop and category pages falls back to the default one there.
	 */
	protected static function catalogStyle( string $layout, string $style ): string {
		$excluded = GeneralSection::getCatalogExcludedStyles();
		
		return in_array( $style, (array) ( $excluded[ $layout ] ?? array() ), true ) ? 'default' : $style;
	}
	
	public static function getTitle(): string {
		return get_option( self::getOptionID( 'title' ), '' );
	}
	
	public static function getQuantityType(): string {
		return get_option( self::getOptionID( 'quantity_type' ), 'range' );
	}
	
	public static function getLayoutSpacing(): string {
		return (string) get_option( self::getOptionID( 'layout_spacing' ), '' );
	}
	
	public static function getCellPadding(): string {
		return (string) get_option( self::getOptionID( 'cell_padding' ), '' );
	}
	
	public static function getTiersOrder(): string {
		return (string) get_option( self::getOptionID( 'tiers_order' ), 'asc' );
	}
	
	public static function getDiscountFormat(): string {
		return (string) get_option( self::getOptionID( 'discount_format' ), 'percentage' );
	}
	
	public static function getSelectedQuantityColor(): string {
		return get_option( self::getOptionID( 'selected_quantity_color' ), '#3858e9' );
	}
	
	public static function getDiscountBadgeColor(): string {
		return (string) get_option( self::getOptionID( 'discount_badge_color' ), '' );
	}
	
	public static function getActiveTierBorder(): string {
		return 'no' === get_option( self::getOptionID( 'table_active_border' ), 'yes' ) ? 'no' : 'yes';
	}
	
	public static function getTableQuantityMeasurement(): array {
		return get_option( self::getOptionID( 'table_quantity_measurement' ), array(
			'singular' => '',
			'plural'   => '',
		) );
	}
	
	public static function getBlocksQuantityMeasurement(): array {
		return get_option( self::getOptionID( 'blocks_quantity_measurement' ), array(
			'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
			'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
		) );
	}
	
	public static function getTableColumnsTitles(): array {
		return array(
			'head_quantity_text' => get_option( self::getOptionID( 'quantity_column_title' ),
				__( 'Quantity', 'tier-pricing-table' ) ),
			'head_discount_text' => get_option( self::getOptionID( 'discount_column_title' ),
				__( 'Discount', 'tier-pricing-table' ) ),
			'head_price_text'    => get_option( self::getOptionID( 'price_column_title' ),
				__( 'Price', 'tier-pricing-table' ) ),
		);
	}
	
	public static function blocksShowDiscount(): bool {
		return 'yes' === get_option( self::getOptionID( 'blocks_show_discount' ), 'yes' );
	}
	
	public static function isShowOriginalProductPriceInOptions(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_original_product_price' ), 'yes' );
	}
	
	public static function isShowTotalInOptions(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_total' ), 'yes' );
	}
	
	public static function getOptionText(): string {
		return get_option( self::getOptionID( 'options_option_text' ),
			'<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>' );
	}
	
	public static function isShowDefaultOption(): bool {
		return 'yes' === get_option( self::getOptionID( 'options_show_default_option' ), 'yes' );
	}
	
	public static function getDefaultOptionText(): string {
		return get_option( self::getOptionID( 'options_default_option_text' ),
			'<strong>Buy {tp_quantity} pieces</strong>' );
	}
	
	public static function getPlainTextTemplate(): string {
		return get_option( self::getOptionID( 'plain_text_template' ),
			'<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>' );
	}
	
	public static function isShowFirstPlainTextTier(): bool {
		return 'yes' === get_option( self::getOptionID( 'plain_text_show_first_tier' ), 'yes' );
	}
	
	public static function getFirstTierPlainTextTemplate(): string {
		return get_option( self::getOptionID( 'plain_text_first_tier_template' ),
			'<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>' );
	}
	
	public static function isClickableTableRows(): bool {
		return 'yes' === get_option( self::getOptionID( 'clickable_table_rows' ), 'yes' );
	}
	
	public static function useReducedStyles(): bool {
		return 'yes' === get_option( self::getOptionID( 'use_reduced_styles' ), 'yes' );
	}
	
	public static function isDynamicPrice(): bool {
		return 'yes' === get_option( self::getOptionID( 'dynamic_price' ), 'yes' );
	}
	
	public static function showQuantityField(): bool {
		return 'yes' === get_option( self::getOptionID( 'show_quantity_field' ), 'no' );
	}
	
}