<?php namespace TierPricingTable\Admin;

use TierPricingTable\Admin\ProductPage\AdvanceOptionsForVariableProduct;
use TierPricingTable\Admin\ProductPage\Product;
use TierPricingTable\Admin\ProductPage\TieredPricingTab;
use TierPricingTable\Admin\ProductPage\RoleCustomerPricingUI;
use TierPricingTable\Admin\PluginInstaller\PluginInstaller;
use TierPricingTable\Admin\WelcomePage\WelcomePage;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
use TierPricingTable\Admin\Tips\TipsManager;

/**
 * Class Admin
 *
 * @package TierPricingTable\Admin
 */
class Admin {
	
	use ServiceContainerTrait;
	
	/**
	 * Array of Managers
	 *
	 * @var array
	 */
	private $managers;
	
	/**
	 * Admin constructor.
	 *
	 * Register menu items and handlers
	 *
	 */
	public function __construct() {
		new Product();
		new TieredPricingTab();
		new RoleCustomerPricingUI();
		new AdvanceOptionsForVariableProduct();
		new TipsManager();
		
		new WelcomePage();
		new PluginInstaller();
		
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ], 10, 2 );
	}
	
	/**
	 * The screens that show the plugin's own admin UI: products, pricing rules, quote requests, wholesale
	 * applications, the plugin's settings tab and the Getting started page. Every other admin page loads none of
	 * the plugin's admin script and styles. The filter `tiered_pricing_table/admin/load_assets` adds a screen.
	 */
	public static function isPluginScreen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- which screen this is, nothing is saved
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// phpcs:enable

		// the post types the plugin adds fields, columns or screens to (as strings: a module may be off)
		$postTypes = array( 'product', 'tpt-global-rule', 'tpt-quote-request', 'tpt-wholesale-app' );

		$isPluginScreen = ( $screen && in_array( $screen->post_type, $postTypes, true ) && in_array( $screen->base, array( 'post', 'edit' ), true ) )
			|| ( 'wc-settings' === $page && \TierPricingTable\Settings\Settings::SETTINGS_PAGE === $tab )
			|| WelcomePage::PAGE_SLUG === $page;

		return (bool) apply_filters( 'tiered_pricing_table/admin/load_assets', $isPluginScreen, $screen );
	}

	/**
	 * The plugin's admin script and styles, only where its admin UI shows (see isPluginScreen()).
	 */
	public function enqueueAssets() {
		if ( ! self::isPluginScreen() ) {
			return;
		}

		wp_enqueue_script( 'tiered-pricing-table-admin-js',
			$this->getContainer()->getFileManager()->locateJSAsset( 'admin/main' ), [ 'jquery' ],
			TierPricingTablePlugin::VERSION );
		wp_localize_script( 'tiered-pricing-table-admin-js', 'tptRoleCustomerUI', RoleCustomerPricingUI::scriptData() );
		wp_enqueue_style( 'tiered-pricing-table-admin-css',
			$this->getContainer()->getFileManager()->locateCSSAsset( 'admin/style.css' ), array(),
			TierPricingTablePlugin::VERSION );
	}
}
