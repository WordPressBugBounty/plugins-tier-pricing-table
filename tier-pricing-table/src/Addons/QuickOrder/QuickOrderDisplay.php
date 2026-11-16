<?php namespace TierPricingTable\Addons\QuickOrder;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
use WC_AJAX;

/**
 * Where the form shows: the [tiered_pricing_quick_order] shortcode, the Quick Order Form block and, when switched
 * on, a My Account tab. Its script and styles load only on the pages that render the form.
 */
class QuickOrderDisplay {

	use ServiceContainerTrait;

	const SHORTCODE = 'tiered_pricing_quick_order';
	const BLOCK     = 'tiered-pricing-table/quick-order';
	const ENDPOINT  = 'quick-order';

	const SCRIPT_HANDLE = 'tiered-pricing-table-quick-order';
	const STYLE_HANDLE  = 'tiered-pricing-table-quick-order';

	/**
	 * Forms rendered on this page, for their ids.
	 *
	 * @var int
	 */
	protected static $instances = 0;

	public function __construct() {
		add_shortcode( self::SHORTCODE, array( $this, 'renderShortcode' ) );

		if ( did_action( 'init' ) ) {
			$this->registerBlock();
		} else {
			add_action( 'init', array( $this, 'registerBlock' ) );
		}

		if ( QuickOrderSettingsSection::isMyAccountEnabled() ) {
			add_filter( 'woocommerce_get_query_vars', function ( $vars ) {
				$vars[ self::ENDPOINT ] = self::ENDPOINT;

				return $vars;
			} );

			add_filter( 'woocommerce_account_menu_items', array( $this, 'addMyAccountTab' ) );
			add_filter( 'woocommerce_endpoint_' . self::ENDPOINT . '_title', function () {
				return QuickOrderSettingsSection::getMyAccountTitle();
			} );
			add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', function () {
				echo $this->render( 'my-account' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template
			} );
		}
	}

	public function renderShortcode(): string {
		return $this->render( 'shortcode' );
	}

	public function registerBlock() {
		if ( ! function_exists( 'register_block_type' ) || \WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK ) ) {
			return;
		}

		register_block_type( dirname( __DIR__, 2 ) . '/Blocks/quick-order', array(
			'render_callback' => function () {
				$attributes = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes() : '';

				return '<div ' . $attributes . '>' . $this->render( 'block' ) . '</div>';
			},
		) );
	}

	/**
	 * The tab sits before "Log out", for the customers who may use the form.
	 *
	 * @param  mixed  $items
	 */
	public function addMyAccountTab( $items ) {
		$items = is_array( $items ) ? $items : array();

		if ( ! ( new QuickOrderService() )->canUse() ) {
			return $items;
		}

		$tab    = array( self::ENDPOINT => QuickOrderSettingsSection::getMyAccountTitle() );
		$logout = array_search( 'customer-logout', array_keys( $items ), true );

		if ( false === $logout ) {
			return $items + $tab;
		}

		return array_slice( $items, 0, $logout, true ) + $tab + array_slice( $items, $logout, null, true );
	}

	/**
	 * The form, or the message for customers who may not use it.
	 */
	public function render( string $context ): string {

		if ( is_admin() && ! wp_doing_ajax() ) {
			return '';
		}

		$service = new QuickOrderService();

		ob_start();

		// Switched off: nothing for shoppers; a store manager sees why the block or shortcode is empty.
		if ( ! QuickOrderSettingsSection::isEnabled() ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				$this->getContainer()->getFileManager()->includeTemplate( 'frontend/quick-order/denied.php', array(
					'message'  => sprintf(
						/* translators: %s: link to the Quick Order settings */
						__( 'The quick order form is switched off, so shoppers do not see it. %s (only store managers see this note).', 'tier-pricing-table' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings&section=quick-order' ) ) . '">' . esc_html__( 'Switch it on', 'tier-pricing-table' ) . '</a>'
					),
					'loginUrl' => '',
				) );
			}

			return (string) ob_get_clean();
		}

		if ( ! $service->canUse() ) {
			$this->getContainer()->getFileManager()->includeTemplate( 'frontend/quick-order/denied.php', array(
				'message'  => QuickOrderSettingsSection::getDeniedMessage(),
				'loginUrl' => is_user_logged_in() ? '' : wc_get_page_permalink( 'myaccount' ),
			) );

			return (string) ob_get_clean();
		}

		$this->enqueueAssets();

		++self::$instances;

		$this->getContainer()->getFileManager()->includeTemplate( 'frontend/quick-order/form.php', array(
			'formId'   => 'tpt-quick-order-' . self::$instances,
			'context'  => $context,
			'columns'  => QuickOrderSettingsSection::getColumns(),
			'paste'    => QuickOrderSettingsSection::isPasteEnabled(),
			'start'    => QuickOrderSettingsSection::getStart(),
			'accent'   => $this->getAccentColor(),
			'config'   => $this->getConfig(),
		) );

		return (string) ob_get_clean();
	}

	public function enqueueAssets() {
		$fileManager = $this->getContainer()->getFileManager();

		wp_enqueue_style( self::STYLE_HANDLE, $fileManager->locateCSSAsset( 'frontend/quick-order' ), array(), TierPricingTablePlugin::VERSION );
		wp_enqueue_script( self::SCRIPT_HANDLE, $fileManager->locateJSAsset( 'frontend/quick-order' ), array( 'jquery' ), TierPricingTablePlugin::VERSION, true );
	}

	protected function getConfig(): array {
		return array(
			'endpoint'    => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'nonce'       => wp_create_nonce( QuickOrderEndpoints::NONCE ),
			'start'       => QuickOrderSettingsSection::getStart(),
			'columns'     => QuickOrderSettingsSection::getColumns(),
			'nextTier'    => QuickOrderSettingsSection::isNextTierEnabled(),
			'cartUrl'     => wc_get_cart_url(),
			'checkoutUrl' => wc_get_checkout_url(),
			'draftKey'    => 'tpt-quick-order-' . md5( (string) get_current_user_id() . '|' . home_url() ),
			'currency'    => array(
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'decimals'          => wc_get_price_decimals(),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'format'            => get_woocommerce_price_format(),
				'trimZeros'         => (bool) apply_filters( 'woocommerce_price_trim_zeros', false ),
			),
			'i18n'        => array(
				'searching'      => __( 'Searching…', 'tier-pricing-table' ),
				'noResults'      => __( 'No products match your search.', 'tier-pricing-table' ),
				'chooseOption'   => __( 'Choose an option', 'tier-pricing-table' ),
				/* translators: %s: product name */
				'optionsFor'     => __( 'Options for %s', 'tier-pricing-table' ),
				/* translators: %s: product SKU */
				'sku'            => __( 'SKU: %s', 'tier-pricing-table' ),
				/* translators: %s: product name */
				'quantityOf'     => __( 'Quantity of %s', 'tier-pricing-table' ),
				/* translators: %s: product name */
				'remove'         => __( 'Remove %s', 'tier-pricing-table' ),
				/* translators: %d: number of items */
				'items'          => __( '%d items', 'tier-pricing-table' ),
				'item'           => __( '1 item', 'tier-pricing-table' ),
				/* translators: 1: quantity to add, 2: unit price at the next tier */
				'nextTier'       => __( 'Add %1$d more for %2$s each', 'tier-pricing-table' ),
				'tierReached'    => __( 'Best price reached', 'tier-pricing-table' ),
				/* translators: %d: quantity in the cart */
				'inCart'         => __( '%d already in your cart', 'tier-pricing-table' ),
				/* translators: %d: minimum quantity */
				'minimum'        => __( 'Minimum %d', 'tier-pricing-table' ),
				/* translators: %d: pack size */
				'step'           => __( 'In packs of %d', 'tier-pricing-table' ),
				/* translators: %d: maximum quantity */
				'maximum'        => __( 'Up to %d', 'tier-pricing-table' ),
				/* translators: %d: the quantity the line was changed to */
				'adjusted'       => __( 'Changed to %d to match the order rules', 'tier-pricing-table' ),
				'adding'         => __( 'Adding to cart…', 'tier-pricing-table' ),
				/* translators: %d: number of products */
				'added'          => __( '%d products added to your cart.', 'tier-pricing-table' ),
				'addedOne'       => __( '1 product added to your cart.', 'tier-pricing-table' ),
				'someFailed'     => __( 'Some products were not added. See the messages in the order.', 'tier-pricing-table' ),
				'viewCart'       => __( 'View cart', 'tier-pricing-table' ),
				'checkout'       => __( 'Checkout', 'tier-pricing-table' ),
				/* translators: %s: list of SKUs */
				'notFound'       => __( 'Not found: %s', 'tier-pricing-table' ),
				/* translators: %d: number of products */
				'pasteAdded'     => __( '%d products added to the order.', 'tier-pricing-table' ),
				'pasteEmpty'     => __( 'Paste at least one line: a SKU, then a quantity.', 'tier-pricing-table' ),
				'error'          => __( 'Something went wrong. Try again.', 'tier-pricing-table' ),
				'loading'        => __( 'Loading products…', 'tier-pricing-table' ),
				'sheetEmpty'     => __( 'There are no products to order here yet.', 'tier-pricing-table' ),
				'restored'       => __( 'Your unfinished order was restored.', 'tier-pricing-table' ),
				'clear'          => __( 'Clear', 'tier-pricing-table' ),
			),
		);
	}

	protected function getAccentColor(): string {
		$color = (string) $this->getContainer()->getSettings()->get( 'selected_quantity_color', '#3858e9' );

		return sanitize_hex_color( $color ) ? $color : '#3858e9';
	}

	/**
	 * The page made with "Create a page with the form", while it is still there.
	 */
	public static function getFormPage(): int {
		$pageId = (int) get_option( QuickOrderSettingsSection::getOptionID( 'page' ), 0 );

		if ( ! $pageId || ! in_array( get_post_status( $pageId ), array( 'publish', 'private', 'draft' ), true ) ) {
			return 0;
		}

		return $pageId;
	}
}
