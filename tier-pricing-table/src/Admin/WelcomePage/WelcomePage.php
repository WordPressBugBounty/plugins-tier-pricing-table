<?php namespace TierPricingTable\Admin\WelcomePage;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings as WholesaleSettings;
use TierPricingTable\Admin\Notifications\Notifications\ActivationNotification;
use TierPricingTable\Admin\PluginInstaller\PluginInstaller;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

/**
 * The page opened after activation: the steps to a working setup, each with its state read from
 * the store's data, a few pointers, what changed in this version and the support links.
 */
class WelcomePage {

	use ServiceContainerTrait;

	const PAGE_SLUG = 'tiered-pricing-table-welcome';

	/** Set once every step is done; the settings reset clears it with the other settings. */
	const DONE_OPTION = Settings::SETTINGS_PREFIX . 'getting_started_done';

	/** Ids of the optional steps the store chose to skip; cleared by the settings reset as well. */
	const SKIPPED_OPTION = Settings::SETTINGS_PREFIX . 'getting_started_skipped';

	const SKIP_ACTION = 'tpt_getting_started_skip';

	public function __construct() {
		add_action( 'admin_menu', function () {
			add_submenu_page( '__none__', __( 'Getting started with Tiered Pricing Table', 'tier-pricing-table' ),
				__( 'Getting started', 'tier-pricing-table' ), 'manage_options', self::PAGE_SLUG,
				array( $this, 'render' ), 99 );
		} );

		add_action( 'admin_post_' . self::SKIP_ACTION, array( $this, 'handleSkip' ) );

		// The page is a guided screen: other plugins' notices would land in its header.
		add_action( 'in_admin_header', function () {
			if ( self::isCurrentPage() ) {
				remove_all_actions( 'admin_notices' );
				remove_all_actions( 'all_admin_notices' );
				remove_all_actions( 'network_admin_notices' );
				remove_all_actions( 'user_admin_notices' );
			}
		}, 1 );
	}

	public static function isCurrentPage(): bool {
		return is_admin() && isset( $_GET['page'] ) && self::PAGE_SLUG === sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Skip an optional step, or take a skip back (`undo=1`). Undoing forgets that the setup was
	 * complete, so the links to the page come back until the step is done or skipped again.
	 */
	public function handleSkip() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'tier-pricing-table' ) );
		}

		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '';

		check_admin_referer( self::SKIP_ACTION . '_' . $step );

		$optional = array();
		foreach ( self::getData()['steps'] as $candidate ) {
			if ( ! empty( $candidate['optional'] ) && ! empty( $candidate['id'] ) ) {
				$optional[] = $candidate['id'];
			}
		}

		if ( in_array( $step, $optional, true ) ) {
			$skipped = self::skippedSteps();

			if ( ! empty( $_GET['undo'] ) ) {
				$skipped = array_values( array_diff( $skipped, array( $step ) ) );
				delete_option( self::DONE_OPTION );
			} elseif ( ! in_array( $step, $skipped, true ) ) {
				$skipped[] = $step;
			}

			if ( $skipped ) {
				update_option( self::SKIPPED_OPTION, $skipped, false );
			} else {
				delete_option( self::SKIPPED_OPTION );
			}
		}

		wp_safe_redirect( self::getURL() );
		exit;
	}

	/**
	 * @return string[] Ids of the skipped optional steps.
	 */
	public static function skippedSteps(): array {
		return array_values( array_filter( array_map( 'sanitize_key', (array) get_option( self::SKIPPED_OPTION, array() ) ) ) );
	}

	/**
	 * A skipped optional step counts as done and carries the links to skip or to undo the skip.
	 */
	protected static function applySkips( array $steps ): array {
		$skipped = self::skippedSteps();

		foreach ( $steps as &$step ) {
			if ( empty( $step['optional'] ) || empty( $step['id'] ) ) {
				continue;
			}

			$base = admin_url( 'admin-post.php?action=' . self::SKIP_ACTION . '&step=' . rawurlencode( $step['id'] ) );

			$step['skipped']  = in_array( $step['id'], $skipped, true );
			$step['done']     = $step['done'] || $step['skipped'];
			$step['skip_url'] = wp_nonce_url( $base, self::SKIP_ACTION . '_' . $step['id'] );
			$step['undo_url'] = wp_nonce_url( $base . '&undo=1', self::SKIP_ACTION . '_' . $step['id'] );
		}

		return $steps;
	}

	public function render() {
		ActivationNotification::setInactive();
		$data = self::getData();
		self::isComplete( $data );
		$this->getContainer()->getFileManager()->includeTemplate( 'admin/welcome-page/welcome-page.php', $data );
	}

	/** @var array|null Steps done, total and left, once counted in this request. */
	protected static $progress = null;

	/**
	 * How far the setup is. Counted once per request.
	 *
	 * @return array{done: int, total: int, left: int}
	 */
	public static function progress( ?array $data = null ): array {
		if ( null === self::$progress || null !== $data ) {
			$steps = (array) ( ( $data ?? self::getData() )['steps'] ?? array() );
			$done  = count( array_filter( $steps, function ( $step ) {
				return ! empty( $step['done'] );
			} ) );

			self::$progress = array( 'done' => $done, 'total' => count( $steps ), 'left' => count( $steps ) - $done );
		}

		return self::$progress;
	}

	/**
	 * Whether every step is done. Remembered in an option once true, so the links to the page can
	 * disappear without the plugins list and the settings page querying the store on every load.
	 */
	public static function isComplete( ?array $data = null ): bool {
		if ( 'yes' === get_option( self::DONE_OPTION ) ) {
			return true;
		}

		if ( self::progress( $data )['left'] > 0 ) {
			return false;
		}

		update_option( self::DONE_OPTION, 'yes', false );

		return true;
	}

	public static function getURL(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Everything the template shows.
	 */
	public static function getData(): array {
		$premium  = function_exists( 'tpt_fs' ) && tpt_fs()->can_use_premium_code();
		$settings = admin_url( 'admin.php?page=wc-settings&tab=' . Settings::SETTINGS_PAGE );
		$product  = self::latestProductWithTiers();
		$rules    = self::publishedRules();

		$steps = array(
			array(
				'id'     => 'look',
				'title'  => __( 'Choose how tiers look', 'tier-pricing-table' ),
				'text'   => __( 'Pick a layout, the colours and the texts in the live preview. The choice applies to every product.', 'tier-pricing-table' ),
				'button' => __( 'Open the configurator', 'tier-pricing-table' ),
				'url'    => $settings,
				'done'   => false !== get_option( Settings::SETTINGS_PREFIX . 'display_type', false ),
			),
			array(
				'id'     => 'product',
				'title'  => __( 'Add tiers to a product', 'tier-pricing-table' ),
				'text'   => __( 'On the General tab of a product, under the price: a tiered pricing type and the quantities with their prices or discounts.', 'tier-pricing-table' ),
				'button' => $product ? __( 'Edit a product with tiers', 'tier-pricing-table' ) : __( 'Open your products', 'tier-pricing-table' ),
				'url'    => $product ? get_edit_post_link( $product['edit'], 'raw' ) : admin_url( 'edit.php?post_type=product' ),
				'done'   => (bool) $product,
			),
			array(
				'id'     => 'storefront',
				'title'    => __( 'See it in your store', 'tier-pricing-table' ),
				'text'     => __( 'The pricing table shows above the add-to-cart button, and the price follows the quantity the customer enters.', 'tier-pricing-table' ),
				'button'   => __( 'View the product', 'tier-pricing-table' ),
				'url'      => $product ? get_permalink( $product['view'] ) : '',
				'done'     => (bool) $product,
				'disabled' => $product ? '' : __( 'Available once a product has tiers.', 'tier-pricing-table' ),
			),
			array(
				'id'     => 'rules',
				'title'    => __( 'Price many products at once', 'tier-pricing-table' ),
				'text'     => __( 'A global pricing rule gives categories, tags, brands or customer roles one set of tiers.', 'tier-pricing-table' ),
				'button'   => $rules ? __( 'Open the pricing rules', 'tier-pricing-table' ) : __( 'Add a pricing rule', 'tier-pricing-table' ),
				'url'      => $rules ? admin_url( 'edit.php?post_type=' . GlobalTieredPricingCPT::SLUG ) : admin_url( 'post-new.php?post_type=' . GlobalTieredPricingCPT::SLUG ),
				'done'     => $rules > 0,
				'optional' => true,
			),
			array(
				'id'     => 'wholesale',
				'title'    => __( 'Sell to wholesale customers', 'tier-pricing-table' ),
				'text'     => __( 'Let trade customers apply for an account, keep the catalog private, and give the Wholesale role its own prices.', 'tier-pricing-table' ),
				'button'   => __( 'Open the wholesale settings', 'tier-pricing-table' ),
				'url'      => $settings . '&section=wholesale',
				'done'     => WholesaleSettings::isEnabled() || self::hasRoleOrCustomerPrices(),
				'optional' => true,
			),
		);

		$steps = self::applySkips( $steps );

		return apply_filters( 'tiered_pricing_table/welcome_page/data', array(
			'premium'     => $premium,
			'steps'       => $steps,
			'tips'        => array(
				array(
					'title' => __( 'Import and export', 'tier-pricing-table' ),
					'text'  => __( 'Tiers travel with the WooCommerce CSV importer and exporter.', 'tier-pricing-table' ),
					'url'   => TierPricingTablePlugin::getDocumentationURL() . '#import-and-export',
				),
				array(
					'title' => __( 'Request a quote', 'tier-pricing-table' ),
					'text'  => __( 'A quote form for customers who want a price on a large order.', 'tier-pricing-table' ),
					'url'   => $settings . '&section=request-a-quote',
				),
				array(
					'title' => __( 'Pricing labels', 'tier-pricing-table' ),
					'text'  => __( 'Mark a tier as the best value or the most popular, with an icon and a colour.', 'tier-pricing-table' ),
					'url'   => $settings . '&section=tier-labels',
				),
				array(
					'title' => __( 'Modules', 'tier-pricing-table' ),
					'text'  => __( 'Switch off the features you do not use under Advanced.', 'tier-pricing-table' ),
					'url'   => $settings . '&section=advanced',
				),
			),
			'plugins'     => PluginInstaller::getPlugins(),
			'links'       => array(
				'docs'      => TierPricingTablePlugin::getDocumentationURL(),
				'support'   => TierPricingTablePlugin::getContactUsURL(),
				'settings'  => $settings,
				'changelog' => 'https://wordpress.org/plugins/tier-pricing-table/#developers',
				'review'    => 'https://wordpress.org/support/plugin/tier-pricing-table/reviews/#new-post',
			),
		) );
	}

	/**
	 * The most recently edited published product (or variation) with tiers: the id to edit and the id to view.
	 *
	 * @return array{edit: int, view: int}|null
	 */
	protected static function latestProductWithTiers(): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT p.ID, p.post_type, p.post_parent FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			 WHERE pm.meta_key IN ('_fixed_price_rules', '_percentage_price_rules') AND pm.meta_value NOT IN ('', 'a:0:{}')
			   AND p.post_status = 'publish' AND p.post_type IN ('product', 'product_variation')
			 ORDER BY p.post_modified DESC LIMIT 1"
		);

		if ( ! $row ) {
			return null;
		}

		$product = 'product_variation' === $row->post_type ? (int) $row->post_parent : (int) $row->ID;

		return array( 'edit' => $product, 'view' => $product );
	}

	protected static function publishedRules(): int {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", GlobalTieredPricingCPT::SLUG ) );
	}

	/**
	 * Any role or customer price on any product (meta "_<role>_tiered_price_pricing_type").
	 */
	protected static function hasRoleOrCustomerPrices(): bool {
		global $wpdb;

		return (bool) $wpdb->get_var( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_%\\_tiered\\_price\\_pricing\\_type' LIMIT 1" );
	}
}
