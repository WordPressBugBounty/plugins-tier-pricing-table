<?php namespace TierPricingTable\Frontend;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
use WP_Post;

/**
 * Class Frontend
 *
 * The frontend script and stylesheet load on every page: product prices, the savings badge and the catalog
 * markup can appear anywhere (product grids on the home page, widgets, the mini cart). The filter
 * `tiered_pricing_table/frontend/enqueue_assets` switches them off on custom conditions; a page that renders
 * a pricing table (shortcode, block, widget, product grid) still loads them.
 *
 * @package TierPricingTable\Frontend
 */
class Frontend {

	use ServiceContainerTrait;

	const SCRIPT_HANDLE = 'tiered-pricing-table-front-js';

	const STYLE_HANDLE = 'tiered-pricing-table-front-css';

	public function __construct() {

		new PricingTableShortcode();

		add_action( 'wp_enqueue_scripts', array( $this, 'registerAssets' ), 10, 1 );
	}

	public function registerAssets() {

		self::register();

		if ( self::shouldEnqueueByDefault() ) {
			self::enqueueAssets();
		}
	}

	/**
	 * Whether the assets load on the current page before any pricing table renders: yes, everywhere, unless
	 * `tiered_pricing_table/frontend/enqueue_assets` returns false. Pages that render a pricing table load them
	 * regardless.
	 */
	public static function shouldEnqueueByDefault(): bool {
		return (bool) apply_filters( 'tiered_pricing_table/frontend/enqueue_assets', true );
	}

	/**
	 * Enqueue the registered assets. Renderers call it, so a pricing table on a page outside WooCommerce still
	 * gets its script and styles (printed in the footer when the page head is already sent).
	 */
	public static function enqueueAssets() {

		self::register();

		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_enqueue_style( self::STYLE_HANDLE );
	}

	public static function register() {

		if ( wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		$fileManager = ServiceContainer::getInstance()->getFileManager();

		wp_register_script( self::SCRIPT_HANDLE, $fileManager->locateJSAsset( 'frontend/product-tiered-pricing-table' ),
			array( 'jquery' ), TierPricingTablePlugin::VERSION );

		wp_register_style( self::STYLE_HANDLE, $fileManager->locateCSSAsset( 'frontend/main.css' ), null,
			TierPricingTablePlugin::VERSION );

		wp_localize_script( self::SCRIPT_HANDLE, 'tieredPricingGlobalData', [
			'version'                         => TierPricingTablePlugin::VERSION,
			'loadVariationTieredPricingNonce' => wp_create_nonce( 'get_pricing_table' ),
			'isPremium'                       => ! tpt_fs()->can_use_premium_code() ? 'no' : 'yes',
			'currencyOptions'                 => [
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimals'           => wc_get_price_decimals(),
				'price_format'       => get_woocommerce_price_format(),
				'trim_zeros'         => apply_filters( 'woocommerce_price_trim_zeros', false ),
			],
			'supportedVariableProductTypes'   => TierPricingTablePlugin::getSupportedVariableProductTypes(),
			'supportedSimpleProductTypes'     => TierPricingTablePlugin::getSupportedSimpleProductTypes(),
		] );
	}
}
