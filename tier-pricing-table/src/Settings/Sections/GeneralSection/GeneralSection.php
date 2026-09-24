<?php namespace TierPricingTable\Settings\Sections\GeneralSection;

use TierPricingTable\Addons\LayoutConfigurator\LayoutConfiguratorAddon;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\CalculationSubsection;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\HiddenOptionsSubsection;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\LayoutSubsection;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\ProductPagePriceSubsection;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

class GeneralSection extends SectionAbstract {
	
	/**
	 * Value of the "Layout position" option meaning "I place the layout myself" (shortcode, block, widget).
	 * The layout configurator shows it as the automatic display being switched off.
	 */
	const NONE_POSITION = '____none____';
	
	const DEFAULT_POSITION_HOOK = 'woocommerce_before_add_to_cart_button';
	
	public function getSettings() {
		$settings = array();
		
		foreach ( $this->getSubsections() as $subsection ) {
			$settings = array_merge( $settings, ( new $subsection() )->getWrappedSettings() );
		}
		
		return apply_filters( 'tiered_pricing_table/settings/general_settings', $settings );
	}
	
	protected function getSubsections() {
		$subsections = array(
			HiddenOptionsSubsection::class,
			LayoutSubsection::class,
		);
		
		// the layout configurator carries the product page price options while it is on
		if ( ! LayoutConfiguratorAddon::isActive() ) {
			$subsections[] = ProductPagePriceSubsection::class;
		}
		
		$subsections[] = CalculationSubsection::class;
		
		return apply_filters( 'tiered_pricing_table/settings/general_subsections', $subsections );
	}
	
	public static function deleteOptions() {
		delete_option( Settings::SETTINGS_PREFIX . 'product_page_subsection' );
		delete_option( Settings::SETTINGS_PREFIX . 'display' );
		delete_option( Settings::SETTINGS_PREFIX . 'display_type' );
		delete_option( Settings::SETTINGS_PREFIX . 'quantity_type' );
		delete_option( Settings::SETTINGS_PREFIX . 'tooltip_color' );
		delete_option( Settings::SETTINGS_PREFIX . 'tooltip_size' );
		delete_option( Settings::SETTINGS_PREFIX . 'tooltip_border' );
		delete_option( Settings::SETTINGS_PREFIX . 'table_title' );
		delete_option( Settings::SETTINGS_PREFIX . 'position_hook' );
		delete_option( Settings::SETTINGS_PREFIX . 'selected_quantity_color' );
		delete_option( Settings::SETTINGS_PREFIX . 'head_quantity_text' );
		delete_option( Settings::SETTINGS_PREFIX . 'head_price_text' );
		delete_option( Settings::SETTINGS_PREFIX . 'show_discount_column' );
		delete_option( Settings::SETTINGS_PREFIX . 'head_discount_text' );
		delete_option( Settings::SETTINGS_PREFIX . 'table_quantity_measurement' );
		delete_option( Settings::SETTINGS_PREFIX . 'blocks_quantity_measurement' );
		delete_option( Settings::SETTINGS_PREFIX . 'product_page_price_format' );
		delete_option( Settings::SETTINGS_PREFIX . 'clickable_table_rows' );
		delete_option( Settings::SETTINGS_PREFIX . 'product_page_subsection' );
		delete_option( Settings::SETTINGS_PREFIX . 'cart_checkout_subsection' );
		delete_option( Settings::SETTINGS_PREFIX . 'summarize_variations' );
		delete_option( Settings::SETTINGS_PREFIX . 'show_discount_in_cart' );
		delete_option( Settings::SETTINGS_PREFIX . 'cart_checkout_subsection' );
		delete_option( Settings::SETTINGS_PREFIX . 'catalog_prices_subsection' );
		delete_option( Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog' );
		delete_option( Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_for_variable' );
		delete_option( Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_cache_for_variable' );
		delete_option( Settings::SETTINGS_PREFIX . 'tiered_price_at_product_page' );
		delete_option( Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_type' );
		delete_option( Settings::SETTINGS_PREFIX . 'lowest_prefix' );
		delete_option( Settings::SETTINGS_PREFIX . 'premium_options' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_section' );
		delete_option( Settings::SETTINGS_PREFIX . 'display_summary' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_title' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_type' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_total_label' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_each_label' );
		delete_option( Settings::SETTINGS_PREFIX . 'summary_position_hook' );
		
		delete_option( Settings::SETTINGS_PREFIX . 'options_option_text' );
		delete_option( Settings::SETTINGS_PREFIX . 'options_show_default_option' );
		delete_option( Settings::SETTINGS_PREFIX . 'options_default_option_text' );
		delete_option( Settings::SETTINGS_PREFIX . 'options_show_original_product_price' );
		delete_option( Settings::SETTINGS_PREFIX . 'options_show_total' );
		
		delete_option( Settings::SETTINGS_PREFIX . 'show_total_price' );
		delete_option( Settings::SETTINGS_PREFIX . 'update_price_on_product_page' );
		delete_option( Settings::SETTINGS_PREFIX . 'show_tiered_price_as_discount' );
	}
	
	public function getSlug(): string {
		return 'general';
	}
	
	public function getName(): string {
		return __( 'General', 'tier-pricing-table' );
	}
	
	/**
	 * Whether the pricing layout is inserted on the product page automatically. Off means the merchant
	 * places it with a shortcode, block or widget; the plugin still renders a hidden wrapper so the
	 * live price updates keep working.
	 */
	public static function isAutomaticDisplayEnabled(): bool {
		$settings = ServiceContainer::getInstance()->getSettings();
		
		if ( self::NONE_POSITION === $settings->get( 'position_hook', self::DEFAULT_POSITION_HOOK ) ) {
			return false;
		}
		
		return 'yes' === $settings->get( 'display', 'yes' );
	}
	
	/**
	 * Hook the pricing layout is rendered on. A stored "none" position falls back to the default hook.
	 */
	public static function getPositionHook(): string {
		$hook = (string) ServiceContainer::getInstance()->getSettings()->get( 'position_hook', self::DEFAULT_POSITION_HOOK );
		
		return ( '' === $hook || self::NONE_POSITION === $hook ) ? self::DEFAULT_POSITION_HOOK : $hook;
	}
	
	/**
	 * Design style choices per layout.
	 */
	/**
	 * Design styles withheld from shop and category pages, where the layouts sit in narrow grid cards:
	 * layout => styles.
	 */
	public static function getCatalogExcludedStyles(): array {
		return (array) apply_filters( 'tiered_pricing_table/catalog/excluded_styles', array(
			'blocks' => array( 'style-8' ),
		) );
	}
	
	/**
	 * Design styles per layout. With $catalog, the styles withheld from shop and category pages are left out.
	 */
	public static function getStyleOptions( bool $catalog = false ): array {
		$styles = function ( int $count ) {
			$options = array( 'default' => __( 'Default', 'tier-pricing-table' ) );
			for ( $i = 1; $i <= $count; $i ++ ) {
				/* translators: %d: style number */
				$options[ 'style-' . $i ] = sprintf( __( 'Style #%d', 'tier-pricing-table' ), $i );
			}
			
			return $options;
		};
		
		$options = array(
			'table'      => $styles( 6 ),
			'blocks'     => $styles( 8 ),
			'options'    => $styles( 6 ),
			'dropdown'   => $styles( 1 ),
			'plain-text' => $styles( 2 ),
		);
		
		if ( $catalog ) {
			foreach ( self::getCatalogExcludedStyles() as $layout => $excluded ) {
				if ( isset( $options[ $layout ] ) ) {
					$options[ $layout ] = array_diff_key( $options[ $layout ], array_flip( (array) $excluded ) );
				}
			}
		}
		
		return $options;
	}
	
	public static function getOptionText() {
		$default = __( '<strong>Buy {tp_quantity} pieces and save {tp_rounded_discount}%</strong>',
			'tier-pricing-table' );
		
		return ServiceContainer::getInstance()->getSettings()->get( 'options_option_text', $default );
	}
	
	public static function getPlainTextTemplate() {
		$default = __( '<strong>Buy {tp_quantity} pieces for {tp_price} each and save {tp_rounded_discount}%</strong>',
			'tier-pricing-table' );
		
		return ServiceContainer::getInstance()->getSettings()->get( 'plain_text_template', $default );
	}
	
	public static function getPlainTextFirstTierTemplate() {
		$default = __( '<strong>Buy {tp_quantity} pieces for {tp_price}</strong>', 'tier-pricing-table' );
		
		return ServiceContainer::getInstance()->getSettings()->get( 'plain_text_first_tier_template', $default );
	}
	
	public static function isPlainTextFirstTierEnabled(): bool {
		return ServiceContainer::getInstance()->getSettings()->get( 'plain_text_show_first_tier', 'yes' ) === 'yes';
	}
	
	public static function getDefaultOptionText() {
		$default = __( '<strong>Buy {tp_quantity} pieces</strong>', 'tier-pricing-table' );
		
		return ServiceContainer::getInstance()->getSettings()->get( 'options_default_option_text', $default );
	}
	
	public static function isDefaultOptionEnabled(): bool {
		return ServiceContainer::getInstance()->getSettings()->get( 'options_show_default_option', 'yes' ) === 'yes';
	}
	
	public static function isShowOriginalProductPrice(): bool {
		return ServiceContainer::getInstance()->getSettings()->get( 'options_show_original_product_price',
				'yes' ) === 'yes';
	}
	
	public static function isShowOptionTotal(): bool {
		return ServiceContainer::getInstance()->getSettings()->get( 'options_show_total', 'yes' ) === 'yes';
	}
	
	public static function getPricingBlocksStyle(): string {
		return ServiceContainer::getInstance()->getSettings()->get( 'pricing_blocks_style', 'default' );
	}
	
	public static function getPricingOptionsStyle(): string {
		return ServiceContainer::getInstance()->getSettings()->get( 'pricing_options_style', 'default' );
	}
	
	public static function getPricingTableStyle(): string {
		return ServiceContainer::getInstance()->getSettings()->get( 'pricing_table_style', 'default' );
	}
	
	public static function getPricingDropdownStyle(): string {
		return ServiceContainer::getInstance()->getSettings()->get( 'pricing_dropdown_style', 'default' );
	}
	
	public static function getPricingPlainTextStyle(): string {
		return ServiceContainer::getInstance()->getSettings()->get( 'pricing_plain_text_style', 'default' );
	}
}
