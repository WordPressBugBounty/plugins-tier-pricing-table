<?php namespace TierPricingTable\Addons\LayoutConfigurator;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Sections\GeneralSection\GeneralSection;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

/**
 * Layout configurator: one settings field that controls every product-page layout option
 * (layout, design style, title, position, quantity format and unit, column headers, text
 * templates, tooltip icon, colours and the layout toggles) with a live preview inside a
 * product-page skeleton. The custom table columns add-on renders its editor inside it. Rendered by a React app (js-source, built
 * to build/ with wp-scripts).
 *
 * Every option keeps its own id and is submitted through hidden inputs inside the field, so
 * WooCommerce's settings save path is unchanged. The other fields of the settings page keep
 * reading the options through the hidden inputs (the app dispatches change events on them).
 */
class LayoutConfigurator {

	use ServiceContainerTrait;

	const FIELD_TYPE = 'tpt_layout_configurator';

	const SCRIPT_HANDLE = 'tiered-pricing/settings/layout-configurator';

	/**
	 * Options managed by the configurator: id (without prefix) => default.
	 */
	const OPTIONS = array(
		'display'                 => 'yes',
		'display_type'            => 'table',
		'pricing_table_style'     => 'default',
		'pricing_blocks_style'    => 'default',
		'pricing_options_style'   => 'default',
		'pricing_dropdown_style'   => 'default',
		'pricing_plain_text_style' => 'default',
		'table_title'             => '',
		'position_hook'           => 'woocommerce_before_add_to_cart_button',
		'quantity_type'           => 'range',
		'tiers_order'             => 'asc',
		'compact_layout'          => 'no',
		'layout_spacing'          => '',
		'cell_padding'            => '',
		'discount_badge_color'    => '',
		'table_active_border'     => 'yes',
		'selected_quantity_color' => '#3858e9',
		'show_discount_column'    => 'yes',
		'discount_format'         => 'percentage',
		// options / dropdown layouts
		'options_option_text'                 => '',
		'options_show_default_option'         => 'yes',
		'options_default_option_text'         => '',
		'options_show_original_product_price' => 'yes',
		'options_show_total'                  => 'yes',
		// tooltip layout
		'tooltip_color'  => '#3858e9',
		'tooltip_size'   => '15',
		'tooltip_border' => 'yes',
		// table layouts: column headers (an empty header hides the column)
		'head_quantity_text' => '',
		'head_discount_text' => '',
		'head_price_text'    => '',
		// plain text layout
		'plain_text_template'            => '',
		'plain_text_show_first_tier'     => 'yes',
		'plain_text_first_tier_template' => '',
		// layouts with selectable tiers
		'clickable_table_rows' => 'yes',
		// product page price line
		'product_page_price_format'     => 'custom',
		'update_price_on_product_page'  => 'yes',
		'show_tiered_price_as_discount' => 'yes',
		'show_total_price'              => 'no',
		'show_total_price_non_tiered'   => 'no',
	);

	/**
	 * "You Save" badge options (the add-on's), managed here while that add-on is on.
	 */
	const YOU_SAVE_OPTIONS = array(
		'you_save_enabled'             => 'yes',
		'you_save_consider_sale_price' => 'yes',
		'you_save_non_tiered'          => 'no',
		'you_save_template'            => '',
		'you_save_text_color'          => '#FF0000',
	);

	const YOU_SAVE_ADDON_SLUG = 'you-save';

	/**
	 * Pricing summary block options (the add-on's), managed here while that add-on is on.
	 */
	const SUMMARY_OPTIONS = array(
		'display_summary'            => 'yes',
		'display_summary_non_tiered' => 'no',
		'summary_title'              => '',
		'summary_position_hook'      => 'woocommerce_after_add_to_cart_button',
		'summary_type'               => 'table',
		'summary_total_label'        => '',
		'summary_each_label'         => '',
	);

	const SUMMARY_ADDON_SLUG = 'pricing-summary';

	/**
	 * Shop & category price format options (the catalog prices add-on's), managed here while it is on.
	 */
	const CATALOG_PRICE_OPTIONS = array(
		'tiered_price_at_catalog'                 => 'yes',
		'tiered_price_at_catalog_for_variable'    => 'no',
		'tiered_price_at_catalog_type'            => 'lowest',
		'tiered_price_at_catalog_custom_template' => '',
		'lowest_prefix'                           => '',
	);

	const CATALOG_PRICE_ADDON_SLUG = 'catalog-prices';

	/**
	 * Cart page price options (the cart options add-on's), managed here while it is on.
	 */
	const CART_OPTIONS = array(
		'show_discount_in_cart'                   => 'yes',
		'show_subtotal_as_discount_in_cart'       => 'yes',
		'consider_sale_price_as_discount_in_cart' => 'no',
		'cart_total_savings'                      => 'no',
		'cart_total_savings_label'                => '',
	);

	const CART_ADDON_SLUG = 'cart-options';

	/**
	 * Cart upsell message options (the cart upsells add-on's), managed here while it is on.
	 */
	const UPSELL_OPTIONS = array(
		'cart_upsell_enabled'  => 'no',
		'cart_upsell_template' => '',
		'cart_upsell_color'    => '#059669',
		'cart_upsell_link'     => 'yes',
		'cart_upsell_progress' => 'no',
	);

	const UPSELL_ADDON_SLUG = 'cart-upsells';

	/**
	 * Options stored as HTML templates (sanitised with wp_kses_post, like the text-template field).
	 */
	const HTML_OPTIONS = array( 'options_option_text', 'options_default_option_text', 'plain_text_template', 'plain_text_first_tier_template', 'you_save_template', 'tiered_price_at_catalog_custom_template', 'cart_upsell_template', 'badge_template' );

	/**
	 * Options that only work with premium code; shown locked otherwise.
	 */
	const PREMIUM_OPTIONS = array( 'options_show_total', 'clickable_table_rows' );

	/**
	 * Options stored as singular/plural pairs (quantity unit labels), keyed by the layouts they apply to.
	 */
	const UNIT_OPTIONS = array(
		'table_quantity_measurement'  => array( 'table', 'tooltip', 'horizontal-table' ),
		'blocks_quantity_measurement' => array( 'blocks' ),
	);

	/**
	 * Shop & category page options, managed by the configurator while the catalog add-on is on:
	 * configurator key => option id (without the settings prefix). Keys shared with the product page
	 * mean the same thing there; the last four are catalog-only.
	 */
	const CATALOG_OPTIONS = array(
		'display'                             => 'shop_loop_display_enabled',
		'display_type'                        => 'shop_loop_display_layout',
		'pricing_table_style'                 => 'shop_loop_display_pricing_table_style',
		'pricing_blocks_style'                => 'shop_loop_display_pricing_blocks_style',
		'pricing_options_style'               => 'shop_loop_display_pricing_options_style',
		'pricing_dropdown_style'              => 'shop_loop_display_pricing_dropdown_style',
		'pricing_plain_text_style'            => 'shop_loop_display_pricing_plain_text_style',
		'table_title'                         => 'shop_loop_display_title',
		'position_hook'                       => 'shop_loop_display_position',
		'quantity_type'                       => 'shop_loop_display_quantity_type',
		'tiers_order'                         => 'shop_loop_display_tiers_order',
		'layout_spacing'                      => 'shop_loop_display_layout_spacing',
		'cell_padding'                        => 'shop_loop_display_cell_padding',
		'discount_badge_color'                => 'shop_loop_display_discount_badge_color',
		'table_active_border'                 => 'shop_loop_display_table_active_border',
		'selected_quantity_color'             => 'shop_loop_display_selected_quantity_color',
		'show_discount_column'                => 'shop_loop_display_blocks_show_discount',
		'discount_format'                     => 'shop_loop_display_discount_format',
		'options_option_text'                 => 'shop_loop_display_options_option_text',
		'options_show_default_option'         => 'shop_loop_display_options_show_default_option',
		'options_default_option_text'         => 'shop_loop_display_options_default_option_text',
		'options_show_original_product_price' => 'shop_loop_display_options_show_original_product_price',
		'options_show_total'                  => 'shop_loop_display_options_show_total',
		'head_quantity_text'                  => 'shop_loop_display_quantity_column_title',
		'head_discount_text'                  => 'shop_loop_display_discount_column_title',
		'head_price_text'                     => 'shop_loop_display_price_column_title',
		'plain_text_template'                 => 'shop_loop_display_plain_text_template',
		'plain_text_show_first_tier'          => 'shop_loop_display_plain_text_show_first_tier',
		'plain_text_first_tier_template'      => 'shop_loop_display_plain_text_first_tier_template',
		'clickable_table_rows'                => 'shop_loop_display_clickable_table_rows',
		// catalog-only
		'layout_settings'                     => 'shop_loop_display_layout_settings',
		'use_reduced_styles'                  => 'shop_loop_display_use_reduced_styles',
		'dynamic_price'                       => 'shop_loop_display_dynamic_price',
		'show_quantity_field'                 => 'shop_loop_display_show_quantity_field',
		'scope'                               => 'shop_loop_display_scope',
		'contexts'                            => 'shop_loop_display_contexts',
		'categories'                          => 'shop_loop_display_categories',
		'tiers_limit'                         => 'shop_loop_display_tiers_limit',
		'badge_enabled'                       => 'shop_loop_display_badge_enabled',
		'badge_template'                      => 'shop_loop_display_badge_template',
		'badge_position'                      => 'shop_loop_display_badge_position',
		'badge_color'                         => 'shop_loop_display_badge_color',
	);

	const CATALOG_UNIT_OPTIONS = array(
		'table_quantity_measurement'  => 'shop_loop_display_table_quantity_measurement',
		'blocks_quantity_measurement' => 'shop_loop_display_blocks_quantity_measurement',
	);

	const CATALOG_PREMIUM_OPTIONS = array( 'options_show_total', 'clickable_table_rows', 'show_quantity_field' );

	const CATALOG_ADDON_SLUG = 'shop-loop-display';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		$htmlOptions = self::HTML_OPTIONS;
		foreach ( self::HTML_OPTIONS as $key ) {
			if ( isset( self::CATALOG_OPTIONS[ $key ] ) ) {
				$htmlOptions[] = self::CATALOG_OPTIONS[ $key ];
			}
		}
		foreach ( $htmlOptions as $id ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( $id ), function ( $value, $option, $rawValue ) {
				return wp_kses_post( is_string( $rawValue ) ? $rawValue : '' );
			}, 10, 3 );
		}

		// numbers: pixels within a range, or empty where empty means "auto"
		self::sanitizeNumber( 'tooltip_size', 8, 64, '15' );
		self::sanitizeNumber( 'layout_spacing', 0, 80, '' );
		self::sanitizeNumber( 'shop_loop_display_layout_spacing', 0, 80, '' );
		self::sanitizeNumber( 'cell_padding', 0, 60, '' );
		self::sanitizeNumber( 'shop_loop_display_cell_padding', 0, 60, '' );
		self::sanitizeNumber( 'shop_loop_display_tiers_limit', 0, 20, '' );

		// lists stored as comma-separated strings: known context keys, category ids
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( 'shop_loop_display_contexts' ), function ( $value ) {
			$known = array_keys( \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getContextOptions() );

			return implode( ',', array_values( array_intersect( \TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption::toArray( $value ), $known ) ) );
		} );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( 'shop_loop_display_categories' ), function ( $value ) {
			return implode( ',', array_filter( array_map( 'intval', \TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption::toArray( $value ) ) ) );
		} );

		// colours: a hex colour, or the option's default
		foreach ( array( 'selected_quantity_color' => '#3858e9', 'tooltip_color' => '#3858e9', 'you_save_text_color' => '#FF0000', 'cart_upsell_color' => '#059669', 'shop_loop_display_selected_quantity_color' => '#3858e9' ) as $id => $default ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( $id ), function ( $value ) use ( $default ) {
				$color = is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : null;

				return $color ? $color : $default;
			} );
		}

		// optional colours: a hex colour, or empty (the design style's own colours)
		foreach ( array( 'discount_badge_color', 'shop_loop_display_discount_badge_color', 'shop_loop_display_badge_color' ) as $id ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( $id ), function ( $value ) {
				$color = is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : null;

				return $color ? $color : '';
			} );
		}

		// choices: only a known value is saved; anything else keeps the stored value. The allowed values are
		// resolved when a save happens (they carry translated labels, which must not load this early).
		foreach ( self::CHOICE_OPTIONS as $id ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( $id ), function ( $value ) use ( $id ) {
				$allowed = self::getChoiceLists()[ $id ] ?? null;

				if ( null === $allowed || ( is_string( $value ) && in_array( $value, $allowed, true ) ) ) {
					return $value;
				}

				return ServiceContainer::getInstance()->getSettings()->get( $id, $allowed[0] ?? '' );
			} );
		}
	}

	/**
	 * Single-choice options whose saved value is checked against getChoiceLists().
	 */
	const CHOICE_OPTIONS = array(
		'shop_loop_display_badge_position',
		'shop_loop_display_scope',
		'display_type', 'pricing_table_style', 'pricing_blocks_style', 'pricing_options_style', 'pricing_dropdown_style', 'pricing_plain_text_style',
		'quantity_type', 'tiers_order', 'discount_format', 'product_page_price_format', 'position_hook', 'summary_type', 'tiered_price_at_catalog_type',
		'shop_loop_display_layout', 'shop_loop_display_pricing_table_style', 'shop_loop_display_pricing_blocks_style', 'shop_loop_display_pricing_options_style',
		'shop_loop_display_pricing_dropdown_style', 'shop_loop_display_pricing_plain_text_style', 'shop_loop_display_quantity_type', 'shop_loop_display_tiers_order',
		'shop_loop_display_discount_format', 'shop_loop_display_layout_settings', 'shop_loop_display_position',
	);

	/**
	 * Allowed values per single-choice option (product page and shop keys).
	 */
	public static function getChoiceLists(): array {
		$layouts = array_keys( TierPricingTablePlugin::getAvailablePricingLayouts() );
		$styles  = GeneralSection::getStyleOptions();
		$catalog = GeneralSection::getStyleOptions( true );
		$lists   = array(
			'display_type'                   => $layouts,
			'pricing_table_style'            => array_keys( $styles['table'] ),
			'pricing_blocks_style'           => array_keys( $styles['blocks'] ),
			'pricing_options_style'          => array_keys( $styles['options'] ),
			'pricing_dropdown_style'         => array_keys( $styles['dropdown'] ),
			'pricing_plain_text_style'       => array_keys( $styles['plain-text'] ),
			'quantity_type'                  => array( 'range', 'static' ),
			'tiers_order'                    => array( 'asc', 'desc' ),
			'discount_format'                => array( 'percentage', 'amount', 'both' ),
			'product_page_price_format'      => array( 'custom', 'same_as_catalog' ),
			'shop_loop_display_badge_position' => array( 'top-left', 'top-right' ),
			'shop_loop_display_scope'          => array( 'everywhere', 'selected' ),
			'position_hook'                  => array_merge( array_keys( self::getPositionOptions() ), array( GeneralSection::NONE_POSITION ) ),
			'shop_loop_display_layout'       => array_values( array_diff( $layouts, array( 'tooltip' ) ) ),
			'shop_loop_display_pricing_table_style'      => array_keys( $catalog['table'] ),
			'shop_loop_display_pricing_blocks_style'     => array_keys( $catalog['blocks'] ),
			'shop_loop_display_pricing_options_style'    => array_keys( $catalog['options'] ),
			'shop_loop_display_pricing_dropdown_style'   => array_keys( $catalog['dropdown'] ),
			'shop_loop_display_pricing_plain_text_style' => array_keys( $catalog['plain-text'] ),
			'shop_loop_display_quantity_type'   => array( 'range', 'static' ),
			'shop_loop_display_tiers_order'     => array( 'asc', 'desc' ),
			'shop_loop_display_discount_format' => array( 'percentage', 'amount', 'both' ),
			'shop_loop_display_layout_settings' => array( 'default', 'custom' ),
			'shop_loop_display_position'        => array_keys( self::getCatalogPositionOptions() ),
		);

		if ( self::isSummaryAvailable() ) {
			$lists['summary_type'] = array( 'detailed', 'compact', 'inline' );
		}
		if ( self::isCatalogPriceAvailable() ) {
			$lists['tiered_price_at_catalog_type'] = array( 'lowest', 'range', 'custom' );
		}

		return $lists;
	}

	/**
	 * Save a numeric option as an integer within a range; an empty value stays empty when allowed.
	 */
	protected static function sanitizeNumber( string $id, int $min, int $max, string $empty ) {
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::getOptionId( $id ), function ( $value ) use ( $min, $max, $empty ) {
			if ( ! is_scalar( $value ) || '' === trim( (string) $value ) || ! is_numeric( $value ) ) {
				return $empty;
			}

			return (string) max( $min, min( $max, (int) $value ) );
		} );
	}

	/**
	 * Option defaults, including the translated template defaults.
	 */
	public static function getDefaults(): array {
		$defaults = self::OPTIONS;

		$defaults['options_option_text']         = __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>', 'tier-pricing-table' );
		$defaults['options_default_option_text'] = __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' );
		$defaults['head_quantity_text']          = __( 'Quantity', 'tier-pricing-table' );
		$defaults['head_discount_text']          = __( 'Discount (%)', 'tier-pricing-table' );
		$defaults['head_price_text']             = __( 'Price', 'tier-pricing-table' );
		$defaults['plain_text_template']         = __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>', 'tier-pricing-table' );
		$defaults['plain_text_first_tier_template'] = __( '<strong>Buy {tp_quantity} pieces for {tp_price} each</strong>', 'tier-pricing-table' );
		// stores that used the old "tiered price at product page" switch keep the catalog format
		$defaults['product_page_price_format'] = 'yes' === ServiceContainer::getInstance()->getSettings()->get( 'tiered_price_at_product_page', 'no' ) ? 'same_as_catalog' : 'custom';

		if ( self::isYouSaveAvailable() ) {
			$defaults                      = array_merge( $defaults, self::YOU_SAVE_OPTIONS );
			$defaults['you_save_template'] = __( 'You save {tp_ys_total_price}', 'tier-pricing-table' );
		}
		if ( self::isSummaryAvailable() ) {
			$defaults                        = array_merge( $defaults, self::SUMMARY_OPTIONS );
			$defaults['summary_total_label'] = __( 'Total:', 'tier-pricing-table' );
			$defaults['summary_each_label']  = __( 'Each: ', 'tier-pricing-table' );
		}
		if ( self::isCatalogPriceAvailable() ) {
			$defaults                                            = array_merge( $defaults, self::CATALOG_PRICE_OPTIONS );
			$defaults['tiered_price_at_catalog_custom_template'] = __( 'From {tp_lowest_price} instead of {tp_original_price}', 'tier-pricing-table' );
			$defaults['lowest_prefix']                           = __( 'From', 'tier-pricing-table' );
		}
		if ( self::isCartAvailable() ) {
			$defaults                             = array_merge( $defaults, self::CART_OPTIONS );
			$defaults['cart_total_savings_label'] = __( 'Total savings', 'tier-pricing-table' );
		}
		if ( self::isUpsellAvailable() ) {
			$defaults                         = array_merge( $defaults, self::UPSELL_OPTIONS );
			$defaults['cart_upsell_template'] = __( 'Add <b>{tp_required_quantity}</b> more to get <b>{tp_next_price}</b> each', 'tier-pricing-table' );
		}

		return $defaults;
	}

	public static function isCartAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::CART_ADDON_SLUG );
	}

	public static function isUpsellAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::UPSELL_ADDON_SLUG );
	}

	public static function isSummaryAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::SUMMARY_ADDON_SLUG );
	}

	public static function isCatalogPriceAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::CATALOG_PRICE_ADDON_SLUG );
	}

	/**
	 * Whether the "You Save" add-on is on, i.e. the configurator also manages the badge options.
	 */
	public static function isYouSaveAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::YOU_SAVE_ADDON_SLUG );
	}

	/**
	 * Sample amounts for the skeleton's price line and summary: the preview product at the first discounted tier.
	 */
	public function getSamples(): array {
		$plain = function ( $html ) {
			return html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES, 'UTF-8' );
		};

		$price     = LayoutPreview::PRICE;
		$tiers     = LayoutPreview::SAMPLE_TIERS;
		$quantity  = (int) array_key_first( $tiers );
		$discount  = (float) reset( $tiers );
		$tierPrice = round( $price * ( 1 - $discount ), 2 );
		$lowest    = round( $price * ( 1 - max( $tiers ) ), 2 );

		// the next tier after the sample quantity, for the cart upsell message
		$quantities   = array_keys( $tiers );
		$nextQuantity = (int) ( $quantities[1] ?? $quantities[0] );
		$nextPrice    = round( $price * ( 1 - (float) $tiers[ $nextQuantity ] ), 2 );
		$percent      = function ( float $from, float $to ) {
			$discount = $from > 0 ? ( $from - $to ) / $from * 100 : 0;

			return number_format( $discount, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		};

		$tierList = array( array( 'quantity' => 1, 'price' => (float) $price ) );
		foreach ( $tiers as $tierQuantity => $tierDiscount ) {
			$tierList[] = array( 'quantity' => (int) $tierQuantity, 'price' => round( $price * ( 1 - (float) $tierDiscount ), 2 ) );
		}

		return array(
			'regular'     => (float) $price,
			'tiers'       => $tierList,
			'upsell'      => array(
				'requiredQuantity' => max( 0, $nextQuantity - $quantity ),
				'targetQuantity'   => $nextQuantity,
				'progress'         => $nextQuantity > 0 ? (int) round( $quantity / $nextQuantity * 100 ) : 0,
				'nextPrice'        => $plain( wc_price( $nextPrice ) ),
				'nextDiscount'     => $percent( $tierPrice, $nextPrice ),
				'actualDiscount'   => $percent( $price, $tierPrice ),
			),
			'quantity'    => $quantity,
			'price'       => $plain( wc_price( $price ) ),
			'tierPrice'   => $plain( wc_price( $tierPrice ) ),
			'lowest'      => $plain( wc_price( $lowest ) ),
			'total'       => $plain( wc_price( $price * $quantity ) ),
			'tierTotal'   => $plain( wc_price( $tierPrice * $quantity ) ),
			'saving'      => $plain( wc_price( $price - $tierPrice ) ),
			'savingTotal' => $plain( wc_price( ( $price - $tierPrice ) * $quantity ) ),
			'maxDiscount' => (int) round( max( $tiers ) * 100 ) . '%',
			'maxSaving'   => $plain( wc_price( $price - $lowest ) ),
			'lowestQuantity' => (int) max( array_keys( $tiers ) ),
			'discount'    => (int) round( $discount * 100 ),
			'productName' => __( 'Product name', 'tier-pricing-table' ),
		);
	}

	/**
	 * Whether premium code is available (the woocommerce.com build has no Freemius and is always premium).
	 */
	public static function isPremium(): bool {
		$premium = function_exists( 'tpt_fs' ) ? (bool) tpt_fs()->can_use_premium_code() : true;

		return (bool) apply_filters( 'tiered_pricing_table/settings/is_premium', $premium );
	}

	public static function getOptionId( string $id ): string {
		return Settings::SETTINGS_PREFIX . $id;
	}

	/**
	 * Settings entries for the options the configurator saves: rendered by nothing, saved by
	 * WooCommerce from the hidden inputs.
	 */
	public static function getSavedOnlyFields(): array {
		$fields = array();
		foreach ( self::getDefaults() as $id => $default ) {
			if ( 'display_type' === $id ) {
				continue; // the configurator field itself carries this id
			}
			$fields[] = array(
				'id'      => self::getOptionId( $id ),
				'type'    => 'tpt_saved_only',
				'default' => $default,
			);
		}
		$others = self::getUnitDefaults();
		if ( self::isCatalogAvailable() ) {
			$others = array_merge( $others, self::getCatalogDefaults(), self::getCatalogUnitDefaults() );
		}
		foreach ( $others as $id => $default ) {
			$fields[] = array(
				'id'      => self::getOptionId( $id ),
				'type'    => 'tpt_saved_only',
				'default' => $default,
			);
		}

		return $fields;
	}

	/**
	 * Default singular/plural unit labels per unit option.
	 */
	public static function getUnitDefaults(): array {
		return array(
			'table_quantity_measurement'  => array( 'singular' => '', 'plural' => '' ),
			'blocks_quantity_measurement' => array(
				'singular' => _n( 'piece', 'pieces', 1, 'tier-pricing-table' ),
				'plural'   => _n( 'piece', 'pieces', 2, 'tier-pricing-table' ),
			),
		);
	}

	/**
	 * Stored unit labels, normalised to singular/plural strings.
	 */
	public static function getUnitValues(): array {
		$values = array();
		foreach ( self::getUnitDefaults() as $id => $default ) {
			$stored        = ServiceContainer::getInstance()->getSettings()->get( $id, $default );
			$stored        = is_array( $stored ) ? $stored : array();
			$values[ $id ] = array(
				'singular' => isset( $stored['singular'] ) ? (string) $stored['singular'] : $default['singular'],
				'plural'   => isset( $stored['plural'] ) ? (string) $stored['plural'] : $default['plural'],
			);
		}

		return $values;
	}

	/**
	 * Whether the catalog add-on is on, i.e. the configurator also manages the shop & category options.
	 */
	public static function isCatalogAvailable(): bool {
		return AbstractAddon::isAddonEnabled( self::CATALOG_ADDON_SLUG );
	}

	/**
	 * Catalog option defaults: option id => default. Shared options default like the product page ones;
	 * the design styles follow the product page until a catalog style is saved.
	 */
	public static function getCatalogDefaults(): array {
		$product  = self::getDefaults();
		$defaults = array();
		foreach ( self::CATALOG_OPTIONS as $key => $id ) {
			$defaults[ $id ] = $product[ $key ] ?? '';
		}
		$defaults['shop_loop_display_enabled']               = 'no';
		$defaults['shop_loop_display_position']              = 'woocommerce_after_shop_loop_item__6';
		$defaults['shop_loop_display_pricing_table_style']      = self::catalogStyle( 'table', GeneralSection::getPricingTableStyle() );
		$defaults['shop_loop_display_pricing_blocks_style']     = self::catalogStyle( 'blocks', GeneralSection::getPricingBlocksStyle() );
		$defaults['shop_loop_display_pricing_options_style']    = self::catalogStyle( 'options', GeneralSection::getPricingOptionsStyle() );
		$defaults['shop_loop_display_pricing_dropdown_style']   = self::catalogStyle( 'dropdown', GeneralSection::getPricingDropdownStyle() );
		$defaults['shop_loop_display_pricing_plain_text_style'] = self::catalogStyle( 'plain-text', GeneralSection::getPricingPlainTextStyle() );
		$defaults['shop_loop_display_discount_column_title'] = __( 'Discount', 'tier-pricing-table' );
		$defaults['shop_loop_display_layout_settings']       = 'default';
		$defaults['shop_loop_display_use_reduced_styles']    = 'yes';
		$defaults['shop_loop_display_dynamic_price']         = 'yes';
		$defaults['shop_loop_display_show_quantity_field']   = 'no';
		$defaults['shop_loop_display_scope']                 = 'everywhere';
		$defaults['shop_loop_display_contexts']              = '';
		$defaults['shop_loop_display_categories']            = '';
		$defaults['shop_loop_display_tiers_limit']           = '';
		$defaults['shop_loop_display_badge_enabled']         = 'no';
		$defaults['shop_loop_display_badge_template']        = \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getDefaultBadgeTemplate();
		$defaults['shop_loop_display_badge_position']        = 'top-left';
		$defaults['shop_loop_display_badge_color']           = '';

		return $defaults;
	}

	/**
	 * A design style as shop and category pages render it: a withheld style falls back to the default.
	 */
	public static function catalogStyle( string $layout, string $style ): string {
		$excluded = GeneralSection::getCatalogExcludedStyles();

		return in_array( $style, (array) ( $excluded[ $layout ] ?? array() ), true ) ? 'default' : $style;
	}

	public static function getCatalogUnitDefaults(): array {
		$defaults = array();
		foreach ( self::getUnitDefaults() as $key => $default ) {
			$defaults[ self::CATALOG_UNIT_OPTIONS[ $key ] ] = $default;
		}

		return $defaults;
	}

	/**
	 * Stored catalog values (scalars as strings, unit labels normalised), keyed by option id.
	 */
	public static function getCatalogValues(): array {
		$settings = ServiceContainer::getInstance()->getSettings();
		$values   = array();
		// options where an empty string is a value of its own (an empty header hides that column)
		$keepEmpty = array( 'shop_loop_display_title', 'shop_loop_display_quantity_column_title', 'shop_loop_display_discount_column_title', 'shop_loop_display_price_column_title' );
		foreach ( self::getCatalogDefaults() as $id => $default ) {
			$value         = $settings->get( $id, $default );
			$values[ $id ] = is_scalar( $value ) && ( '' !== $value || in_array( $id, $keepEmpty, true ) ) ? (string) $value : (string) $default;
		}
		foreach ( self::getCatalogUnitDefaults() as $id => $default ) {
			$stored        = $settings->get( $id, $default );
			$stored        = is_array( $stored ) ? $stored : array();
			$values[ $id ] = array(
				'singular' => isset( $stored['singular'] ) ? (string) $stored['singular'] : $default['singular'],
				'plural'   => isset( $stored['plural'] ) ? (string) $stored['plural'] : $default['plural'],
			);
		}

		return $values;
	}

	public static function getCatalogPositionOptions(): array {
		return array(
			'woocommerce_after_shop_loop_item__6'  => __( 'Above the add to cart button', 'tier-pricing-table' ),
			'woocommerce_after_shop_loop_item__15' => __( 'Below the add to cart button', 'tier-pricing-table' ),
			'woocommerce_shop_loop_item_title__5'  => __( 'Above the product title', 'tier-pricing-table' ),
			'woocommerce_shop_loop_item_title__15' => __( 'Below the product title', 'tier-pricing-table' ),
		);
	}

	public static function getPositionOptions(): array {
		return array(
			'woocommerce_before_add_to_cart_button'     => __( 'Above the add to cart button', 'tier-pricing-table' ),
			'woocommerce_after_add_to_cart_button'      => __( 'Below the add to cart button', 'tier-pricing-table' ),
			'woocommerce_before_add_to_cart_form'       => __( 'Above the add to cart form', 'tier-pricing-table' ),
			'woocommerce_after_add_to_cart_form'        => __( 'Below the add to cart form', 'tier-pricing-table' ),
			'woocommerce_single_product_summary'        => __( 'Above the product title', 'tier-pricing-table' ),
			'woocommerce_before_single_product_summary' => __( 'Before the product summary', 'tier-pricing-table' ),
			'woocommerce_after_single_product_summary'  => __( 'After the product summary', 'tier-pricing-table' ),
		);
	}

	public static function getStyleOptions(): array {
		return GeneralSection::getStyleOptions();
	}

	/**
	 * Configuration handed to the app.
	 */
	public function getConfig(): array {
		$values = array();
		foreach ( self::getDefaults() as $id => $default ) {
			$value         = $this->getContainer()->getSettings()->get( $id, $default );
			$values[ $id ] = is_scalar( $value ) ? (string) $value : $default;
		}
		// classic "none" position: shown as automatic display switched off, position back to the default
		$values['display']       = GeneralSection::isAutomaticDisplayEnabled() ? 'yes' : 'no';
		$values['position_hook'] = GeneralSection::getPositionHook();
		$values                  = array_merge( $values, self::getUnitValues() );
		if ( self::isCatalogAvailable() ) {
			$values = array_merge( $values, self::getCatalogValues() );
		}

		/** @var LayoutPreview $preview */
		$preview = $this->getContainer()->get( 'settings.layout_preview' );
		$samples = $this->getSamples();

		return array(
			'prefix'        => Settings::SETTINGS_PREFIX,
			'title'         => __( 'Pricing Display', 'tier-pricing-table' ),
			'values'        => $values,
			'layouts'       => TierPricingTablePlugin::getAvailablePricingLayouts(),
			'styles'        => self::getStyleOptions(),
			'positions'     => self::getPositionOptions(),
			'compactFor'    => array( 'table', 'options', 'blocks', 'dropdown', 'horizontal-table' ),
			// styles whose gap between tiers the "Spacing" option controls (the others keep their own)
			'spacingFor'    => array(
				'blocks'  => array( 'default', 'style-1', 'style-2', 'style-3', 'style-4', 'style-5', 'style-7' ),
				'options' => array( 'default', 'style-3', 'style-4', 'style-5' ),
				'table'   => array( 'style-2', 'style-3', 'style-4' ),
			),
			'spacingMax'    => 40,
			// layouts whose cell padding the "Cell padding" option controls
			'cellPaddingFor' => array( 'table', 'horizontal-table' ),
			'cellPaddingMax' => 30,
			// design styles whose discount badge the "Discount badge colour" option recolours: the colour shown for "Auto"
			// ('active' is the active tier colour) and what the colour paints: a tint with coloured text, a solid badge
			// with white text, or the savings bar and its percentage
			'badgeColorStyles' => array( 'table' => array(
				'style-2' => array( 'default' => '#b91c1c', 'kind' => 'tint' ),
				'style-3' => array( 'default' => 'active', 'kind' => 'solid' ),
				'style-4' => array( 'default' => 'active', 'kind' => 'solid' ),
				'style-5' => array( 'default' => 'active', 'kind' => 'bar' ),
				'style-6' => array( 'default' => '#059669', 'kind' => 'tint' ),
			) ),
			// design styles that mark the active tier with a left border the "Active tier left border" toggle controls
			'activeBorderStyles' => array( 'table' => array( 'style-1', 'style-5', 'style-6' ) ),
			'discountFor'   => array( 'blocks', 'options' ),
			// options styles that print a discount label (the toggle applies to those only)
			'discountStyles' => array( 'options' => array( 'style-3', 'style-4', 'style-5', 'style-6' ) ),
			// layouts (and, for options, the design styles) that print a discount per tier
			'discountFormatFor'    => array( 'table', 'horizontal-table', 'tooltip', 'blocks', 'options', 'dropdown' ),
			'discountFormatStyles' => array( 'options' => array( 'style-3', 'style-4', 'style-5', 'style-6' ), 'dropdown' => array( 'style-1' ) ),
			'unitFor'       => self::UNIT_OPTIONS,
			'tooltipFor'    => array( 'tooltip' ),
			'columnsFor'    => array( 'table', 'horizontal-table', 'tooltip' ),
			'clickableFor'  => array( 'table', 'horizontal-table', 'blocks', 'options', 'tooltip', 'dropdown', 'plain-text' ),
			'plainTextFor'  => array( 'plain-text' ),
			'customColumns' => has_action( 'tiered_pricing_table/settings/table_columns/end' ),
			'catalog'       => self::isCatalogAvailable() ? array(
				'keys'           => self::CATALOG_OPTIONS,
				'unitKeys'       => self::CATALOG_UNIT_OPTIONS,
				'layouts'        => array_diff_key( TierPricingTablePlugin::getAvailablePricingLayouts(), array( 'tooltip' => true ) ),
				'positions'      => self::getCatalogPositionOptions(),
				'premiumOptions' => self::CATALOG_PREMIUM_OPTIONS,
				'excludedStyles' => GeneralSection::getCatalogExcludedStyles(),
				'contexts'       => \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getContextOptions(),
				'scopes'         => \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getScopeOptions(),
				// the chosen categories' names; other categories are searched through WooCommerce's AJAX category search
				'categoryNames'  => (object) \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getCategoryNames( \TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption::toArray( ServiceContainer::getInstance()->getSettings()->get( 'shop_loop_display_categories', '' ) ) ),
				'categorySearch' => array( 'url' => admin_url( 'admin-ajax.php' ), 'action' => 'woocommerce_json_search_categories', 'nonce' => wp_create_nonce( 'search-categories' ) ),
				'badgePositions' => \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::getBadgePositionOptions(),
			) : null,
			'premium'       => self::isPremium(),
			'premiumOptions' => self::PREMIUM_OPTIONS,
			'optionsLayout' => array(
				'templates'   => array( 'options', 'dropdown' ),
				'original'    => array( 'options', 'dropdown' ),
				'defaultOpt'  => array( 'options' ),
				'total'       => array( 'options' ),
				'totalStyles' => array( 'default', 'style-1', 'style-2', 'style-3', 'style-4', 'style-5', 'style-6' ),
			),
			'placeholders'  => array(
				'options_option_text'         => array( 'tp_quantity', 'tp_discount', 'tp_rounded_discount', 'tp_base_unit_name' ),
				'options_default_option_text' => array( 'tp_quantity', 'tp_base_unit_name' ),
				'plain_text_template'            => array( 'tp_quantity', 'tp_discount', 'tp_price', 'tp_rounded_discount', 'tp_base_unit_name' ),
				'plain_text_first_tier_template' => array( 'tp_quantity', 'tp_price', 'tp_base_unit_name' ),
				'you_save_template'              => array( 'tp_ys_price', 'tp_ys_total_price', 'tp_ys_percentage_discount' ),
				'tiered_price_at_catalog_custom_template' => array( 'tp_lowest_price', 'tp_prices_range', 'tp_original_price' ),
				'cart_upsell_template'           => array( 'tp_required_quantity', 'tp_next_price', 'tp_next_discount', 'tp_actual_discount' ),
				'badge_template'                 => array( 'tp_max_discount', 'tp_lowest_price', 'tp_lowest_quantity', 'tp_max_saving' ),
			),
			'placeholder'   => function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_single' ) : '',
			'youSave'       => self::isYouSaveAvailable(),
			'samples'       => $samples,
			'summary'       => self::isSummaryAvailable() ? array(
				'positions' => self::getPositionOptions(),
				'types'     => array(
					'detailed' => __( 'Detailed', 'tier-pricing-table' ),
					'table'    => __( 'Compact', 'tier-pricing-table' ),
					'inline'   => __( 'Inline labels', 'tier-pricing-table' ),
				),
			) : null,
			'cart'          => self::isCartAvailable() || self::isUpsellAvailable() ? array(
				'prices'  => self::isCartAvailable(),
				'upsells' => self::isUpsellAvailable(),
			) : null,
			'catalogPrice'  => self::isCatalogPriceAvailable() ? array(
				'types' => array(
					'lowest' => array(
						'label'   => __( 'Lowest price', 'tier-pricing-table' ),
						/* translators: %s: example price */
						'example' => sprintf( __( 'e.g. From %s', 'tier-pricing-table' ), $samples['lowest'] ),
					),
					'range'  => array(
						'label'   => __( 'Price range', 'tier-pricing-table' ),
						/* translators: 1: lowest example price, 2: highest example price */
						'example' => sprintf( __( 'e.g. %1$s - %2$s', 'tier-pricing-table' ), $samples['lowest'], $samples['price'] ),
					),
					'custom' => array(
						'label'   => __( 'Custom template', 'tier-pricing-table' ),
						'example' => __( 'Your own text built from variables', 'tier-pricing-table' ),
					),
				),
			) : null,
			'priceFormats'  => array(
				'same_as_catalog' => array(
					'label'   => __( 'Match shop & categories', 'tier-pricing-table' ),
					'example' => __( 'Lowest price or a price range, as on the shop page', 'tier-pricing-table' ),
				),
				'custom'          => array(
					'label'   => __( 'Price for the selected quantity', 'tier-pricing-table' ),
					'example' => __( 'Follows the quantity the customer enters', 'tier-pricing-table' ),
				),
			),
			'quantityTypes' => array(
				'range'  => array( 'label' => __( 'Range', 'tier-pricing-table' ), 'example' => __( '10 - 24 pieces', 'tier-pricing-table' ) ),
				'static' => array( 'label' => __( 'Static values', 'tier-pricing-table' ), 'example' => __( '10 pieces', 'tier-pricing-table' ) ),
			),
			'tierOrders'    => array(
				'asc'  => array( 'label' => __( 'Ascending', 'tier-pricing-table' ), 'example' => __( 'Smallest quantity first', 'tier-pricing-table' ) ),
				'desc' => array( 'label' => __( 'Descending', 'tier-pricing-table' ), 'example' => __( 'Biggest discount first', 'tier-pricing-table' ) ),
			),
			'discountFormats' => array(
				'percentage' => array( 'label' => __( 'Percentage', 'tier-pricing-table' ), 'example' => $samples['discount'] . '%' ),
				'amount'     => array( 'label' => __( 'Amount saved', 'tier-pricing-table' ), 'example' => $samples['saving'] ),
				'both'       => array( 'label' => __( 'Both', 'tier-pricing-table' ), 'example' => $samples['discount'] . '% (' . $samples['saving'] . ')' ),
			),
			'ajax'          => array(
				'url'    => admin_url( 'admin-ajax.php' ),
				'action' => LayoutPreview::AJAX_ACTION,
				'nonce'  => wp_create_nonce( LayoutPreview::AJAX_ACTION ),
			),
			'stylesheets'   => $preview->getStylesheetURLs(),
			'inlineStyles'  => $preview->getInlineStyles(),
			// WooCommerce scopes its classic price colours to themes that are not block themes
			'blockTheme'    => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			'currency'      => array(
				'price'             => html_entity_decode( wp_strip_all_tags( wc_price( LayoutPreview::PRICE ) ), ENT_QUOTES, 'UTF-8' ),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ),
				'position'          => get_option( 'woocommerce_currency_pos', 'left' ),
				'decimals'          => wc_get_price_decimals(),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
			),
		);
	}

	public function enqueueAssets() {
		$dir       = plugin_dir_path( __FILE__ ) . 'build/';
		$assetFile = file_exists( $dir . 'index.asset.php' ) ? include $dir . 'index.asset.php' : array( 'dependencies' => array(), 'version' => TierPricingTablePlugin::VERSION );

		wp_enqueue_script( self::SCRIPT_HANDLE, plugins_url( 'build/index.js', __FILE__ ), $assetFile['dependencies'], $assetFile['version'], true );
		wp_set_script_translations( self::SCRIPT_HANDLE, 'tier-pricing-table', $this->getContainer()->getFileManager()->getPluginDirectory() . 'languages' );

		if ( file_exists( $dir . 'style-index.css' ) ) {
			wp_enqueue_style( self::SCRIPT_HANDLE, plugins_url( 'build/style-index.css', __FILE__ ), array( 'wp-components' ), $assetFile['version'] );
		} else {
			wp_enqueue_style( 'wp-components' );
		}

		wp_add_inline_script( self::SCRIPT_HANDLE, 'window.tptLayoutConfigurator = ' . wp_json_encode( $this->getConfig() ) . ';', 'before' );
	}

	public function render( $value ) {
		$this->enqueueAssets();

		$value = wp_parse_args( $value, array(
			'id'    => self::getOptionId( 'display_type' ),
			'title' => '',
			'desc'  => '',
		) );

		$values = $this->getConfig()['values'];
		?>
		<?php
		// WooCommerce renders fields inside <table class="form-table">, whose fixed layout keeps a
		// spanning cell inside the label column. The configurator needs the full width, so it closes
		// the table, renders as a block and reopens the table for the fields that follow.
		?>
		</table>
		<div id="tpt-layout-configurator" class="tpt-layout-configurator" aria-label="<?php echo esc_attr( $value['title'] ); ?>">
			<?php foreach ( $values as $id => $stored ) : ?>
				<?php if ( is_array( $stored ) ) : ?>
					<?php foreach ( array( 'singular', 'plural' ) as $form ) : ?>
						<input type="hidden"
							   name="<?php echo esc_attr( self::getOptionId( $id ) . '[' . $form . ']' ); ?>"
							   value="<?php echo esc_attr( $stored[ $form ] ); ?>"
							   data-tpt-lc-option="<?php echo esc_attr( $id . '.' . $form ); ?>">
					<?php endforeach; ?>
				<?php else : ?>
					<input type="hidden"
						   name="<?php echo esc_attr( self::getOptionId( $id ) ); ?>"
						   id="<?php echo esc_attr( self::getOptionId( $id ) ); ?>"
						   value="<?php echo esc_attr( $stored ); ?>"
						   data-tpt-lc-option="<?php echo esc_attr( $id ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>
			<div class="tpt-layout-configurator__app" data-tpt-lc-root>
				<p class="tpt-layout-configurator__loading"><?php esc_html_e( 'Loading the layout configurator…', 'tier-pricing-table' ); ?></p>
			</div>
			<?php if ( has_action( 'tiered_pricing_table/settings/table_columns/end' ) ) : ?>
				<?php // Custom table columns add-on. The app moves this block under the controls and shows it for table layouts. ?>
				<div class="tpt-layout-configurator__custom-columns" data-tpt-lc-custom-columns hidden>
					<?php do_action( 'tiered_pricing_table/settings/table_columns/after_fields' ); ?>
					<?php do_action( 'tiered_pricing_table/settings/table_columns/end' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( ! empty( $value['desc'] ) ) : ?>
				<p class="description tpt-layout-configurator__desc"><?php echo esc_html( $value['desc'] ); ?></p>
			<?php endif; ?>
		</div>
		<table class="form-table">
		<?php
	}
}
