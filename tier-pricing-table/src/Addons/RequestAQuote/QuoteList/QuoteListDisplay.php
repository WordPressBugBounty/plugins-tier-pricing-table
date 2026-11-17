<?php namespace TierPricingTable\Addons\RequestAQuote\QuoteList;

use TierPricingTable\Addons\RequestAQuote\Frontend\API\SubmitQuoteEndpoint;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The quote list on the storefront: the list page (shortcode), the drawer that opens when a product is added,
 * the floating "Quote list" button, "Add to quote" on shop pages and "Request a quote for this cart" on the cart.
 */
class QuoteListDisplay {

	public const SHORTCODE = 'tiered_pricing_quote_list';

	/**
	 * A "Quote list (3)" link for a header or menu.
	 */
	public const LINK_SHORTCODE = 'tiered_pricing_quote_list_link';

	public const SCRIPT_HANDLE = 'tpt-quote-list';

	protected bool $isListPage = false;

	/**
	 * Products whose card got its button from the product button block: WooCommerce also runs the classic loop
	 * hook at the end of a block card, which then skips once.
	 *
	 * @var array<int, int>
	 */
	protected array $blockButtons = array();

	public function __construct() {
		QuoteListSettings::$running = true;

		add_shortcode( self::SHORTCODE, array( $this, 'renderShortcode' ) );
		add_shortcode( self::LINK_SHORTCODE, array( $this, 'renderLinkShortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueAssets' ), 20 );
		add_action( 'wp_footer', array( $this, 'renderDrawer' ) );

		// shop and category pages: classic templates and the product blocks of block themes
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'renderLoopButton' ), 15 );
		add_filter( 'render_block_woocommerce/product-button', array( $this, 'appendLoopButtonToBlock' ), 10, 3 );

		// the cart: the classic cart page and the Cart block
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'renderCartButton' ), 30 );
		add_filter( 'render_block_woocommerce/proceed-to-checkout-block', array( $this, 'appendCartButtonToBlock' ) );
	}

	/* --- the list page ------------------------------------------------------------------------------- */

	public function renderShortcode(): string {
		if ( ! QuoteListSettings::isAvailable() ) {
			// a store manager sees why the page is empty; shoppers see nothing
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return '<p class="tpt-quote-list__notice">' . esc_html__( 'The quote list is off: no quote form is set to "Multiple products", or the list has no page. Set both in WooCommerce → Settings → Tiered Pricing → Request a Quote.', 'tier-pricing-table' ) . '</p>';
			}

			return '';
		}

		$this->isListPage = true;

		// the shortcode can run after wp_enqueue_scripts (block themes render content first): the script is
		// printed in the footer either way
		$this->enqueueScript();

		// the form's field styles, and the spam check, only where requests are sent (registered by QuoteFormDisplay)
		wp_enqueue_style( 'tpt-quote-form' );

		if ( wp_script_is( 'google-recaptcha-v3', 'registered' ) ) {
			wp_enqueue_script( 'google-recaptcha-v3' );
		}

		$form      = QuoteListSettings::getForm();
		$listForms = QuoteListSettings::getListForms();

		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/quote-list.php', array(
			'form'          => $form,
			// what the store's quote buttons say (the first form for multiple products)
			'buttonText'    => $listForms ? $listForms[0]->getQuoteListButtonText() : __( 'Add to quote', 'tier-pricing-table' ),
			'shopUrl'       => wc_get_page_permalink( 'shop' ),
			'honeypotField' => SubmitQuoteEndpoint::HONEYPOT_FIELD,
		), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
	}

	/**
	 * "Quote list" with the number of products, for a header, a menu or a widget. Updated in the browser, so
	 * cached pages show the right number.
	 *
	 * @param  array|string  $atts  text: the link text.
	 */
	public function renderLinkShortcode( $atts = array() ): string {
		if ( ! QuoteListSettings::isAvailable() ) {
			return '';
		}

		$atts = shortcode_atts( array( 'text' => __( 'Quote list', 'tier-pricing-table' ) ), $atts, self::LINK_SHORTCODE );

		// read once into a local (QIT flags shortcode attributes used straight in the output); the template escapes it
		$text = sanitize_text_field( (string) $atts['text'] );

		$this->enqueueScript();

		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/quote-list-link.php', array( // nosemgrep
			'pageUrl' => QuoteListSettings::getPageUrl(),
			'text'    => $text,
		), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
	}

	/* --- assets ------------------------------------------------------------------------------------ */

	public function enqueueAssets() {
		if ( ! QuoteListSettings::isAvailable() ) {
			return;
		}

		$this->enqueueScript();
	}

	protected function enqueueScript() {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		$assets = plugins_url( 'assets/', dirname( __FILE__ ) );

		// its buttons use the quote buttons' styles (registered by QuoteFormDisplay)
		wp_enqueue_style( self::SCRIPT_HANDLE, $assets . 'css/quote-list.css', array( 'tpt-quote-button' ), TierPricingTablePlugin::VERSION );
		wp_enqueue_script( self::SCRIPT_HANDLE, $assets . 'js/quote-list.js', array( 'jquery' ), TierPricingTablePlugin::VERSION, true );

		wp_localize_script( self::SCRIPT_HANDLE, 'tptQuoteListConfig', $this->getScriptConfig() );
	}

	protected function getScriptConfig(): array {
		$form     = QuoteListSettings::getForm();
		$loggedIn = is_user_logged_in();
		$siteKey  = SubmitQuoteEndpoint::getRecaptchaKeys()['site'];

		return array(
			// one list per site: sites in subfolders of one domain share the browser's storage
			'storageKey'      => 'tptQuoteList_' . substr( md5( home_url() ), 0, 8 ),
			'ajaxUrl'         => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'submitUrl'       => esc_url_raw( rest_url( 'tier-pricing-table/v1/quote-request' ) ),
			'cartUrl'         => esc_url_raw( rest_url( 'wc/store/v1/cart' ) ),
			'pageUrl'         => QuoteListSettings::getPageUrl(),
			'formId'          => $form ? $form->getId() : '',
			'listParam'       => SubmitQuoteEndpoint::LIST_ITEMS_PARAM,
			'maxLines'        => QuoteListSettings::MAX_LINES,
			'isLoggedIn'      => $loggedIn,
			// guests send no nonce: a nonce cached with the page would expire (see SubmitQuoteEndpoint)
			'nonce'           => $loggedIn ? wp_create_nonce( 'wp_rest' ) : '',
			'syncNonce'       => $loggedIn ? wp_create_nonce( QuoteListEndpoints::SYNC_NONCE ) : '',
			'nonceAjaxAction' => \TierPricingTable\Addons\RequestAQuote\Frontend\QuoteFormDisplay::NONCE_ACTION,
			'adminAjaxUrl'    => admin_url( 'admin-ajax.php' ),
			'recaptchaKey'    => $siteKey,
			'recaptchaAction' => SubmitQuoteEndpoint::RECAPTCHA_ACTION,
			'successAction'   => $form ? $form->getSuccessAction() : 'message',
			// the customer gets a copy by e-mail: the thank-you says where it went
			'customerEmail'   => $form ? $form->isCustomerEmailEnabled() : false,
			'successUrl'      => $form ? $form->getSuccessRedirectUrl() : '',
			'successMessage'  => $form ? wp_strip_all_tags( $form->getSuccessMessage() ) : '',
			'i18n'            => array(
				'added'           => __( 'Added to your quote list.', 'tier-pricing-table' ),
				'addedMany'       => __( 'The products were added to your quote list.', 'tier-pricing-table' ),
				'updated'         => __( 'Quantity updated.', 'tier-pricing-table' ),
				'removed'         => __( 'Removed from your quote list.', 'tier-pricing-table' ),
				/* translators: %s: product name */
				'removeItem'      => __( 'Remove %s', 'tier-pricing-table' ),
				/* translators: %s: product name */
				'quantityOf'      => __( 'Quantity of %s', 'tier-pricing-table' ),
				'decrease'        => __( 'Decrease quantity', 'tier-pricing-table' ),
				'increase'        => __( 'Increase quantity', 'tier-pricing-table' ),
				'each'            => __( 'each', 'tier-pricing-table' ),
				'onRequest'       => __( 'Price on request', 'tier-pricing-table' ),
				/* translators: 1: how many more, 2: price */
				'nextTier'        => __( 'Add %1$s more for %2$s each', 'tier-pricing-table' ),
				/* translators: %s: SKU */
				'sku'             => __( 'SKU: %s', 'tier-pricing-table' ),
				/* translators: %s: the smallest quantity that can be requested */
				'minimum'         => __( 'Min. %s', 'tier-pricing-table' ),
				/* translators: %s: the quantity step, like 5 for 5, 10, 15 */
				'stepOf'          => __( 'in steps of %s', 'tier-pricing-table' ),
				'estimatedTotal'  => __( 'Estimated total', 'tier-pricing-table' ),
				'estimateNote'    => __( 'Estimated with your current prices. The final price is in the quote we send you.', 'tier-pricing-table' ),
				'noPricesNote'    => __( 'We will send you prices in the quote.', 'tier-pricing-table' ),
				'chooseOptions'   => __( 'Choose the product options first.', 'tier-pricing-table' ),
				'listFull'        => __( 'Your quote list is full. Send this request first, then start a new one.', 'tier-pricing-table' ),
				'unavailable'     => __( 'Some products are no longer available and were removed from your list.', 'tier-pricing-table' ),
				'loadError'       => __( 'Your quote list could not be loaded. Please reload the page.', 'tier-pricing-table' ),
				'genericError'    => __( 'Something went wrong. Please try again.', 'tier-pricing-table' ),
				'cartEmpty'       => __( 'Your cart is empty.', 'tier-pricing-table' ),
				'emptyDrawer'     => __( 'Your quote list is empty.', 'tier-pricing-table' ),
				'inList'          => __( 'In your quote list', 'tier-pricing-table' ),
				/* translators: %s: quantity */
				'inListQty'       => __( 'In your quote list (%s)', 'tier-pricing-table' ),
				/* translators: %s: quantity */
				'nowInList'       => __( 'Already in your quote list: the quantity is now %s.', 'tier-pricing-table' ),
				/* translators: %s: e-mail address */
				'copySent'        => __( 'We e-mailed a copy to %s.', 'tier-pricing-table' ),
				'cartCopied'      => __( 'The products in your cart were added to your quote list. Your cart stays as it is.', 'tier-pricing-table' ),
				'youAskedFor'     => __( 'You asked for a quote for:', 'tier-pricing-table' ),
				'sending'         => __( 'Sending…', 'tier-pricing-table' ),
				'recaptchaFailed' => __( 'The spam check failed. Please try again.', 'tier-pricing-table' ),
				/* translators: %s: request number */
				'reference'       => __( 'Your request number is %s.', 'tier-pricing-table' ),
			),
		);
	}

	/* --- the drawer and the floating button ------------------------------------------------------- */

	public function renderDrawer() {
		if ( ! QuoteListSettings::isAvailable() || ! wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'frontend/quote-list-drawer.php', array(
			'pageUrl'      => QuoteListSettings::getPageUrl(),
			'isListPage'   => $this->isListPage,
			'showFloating' => QuoteListSettings::showFloatingButton(),
		), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
	}

	/* --- shop and category pages ------------------------------------------------------------------ */

	public function renderLoopButton() {
		global $product;

		if ( $product instanceof WC_Product && ! empty( $this->blockButtons[ $product->get_id() ] ) ) {
			$this->blockButtons[ $product->get_id() ]--;

			return;
		}

		if ( $product instanceof WC_Product ) {
			echo $this->getLoopButton( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template
		}
	}

	/**
	 * @param  string  $content
	 * @param  array  $block
	 * @param  \WP_Block|null  $instance
	 */
	public function appendLoopButtonToBlock( $content, $block, $instance = null ) {
		$productId = $instance instanceof \WP_Block ? (int) ( $instance->context['postId'] ?? 0 ) : 0;
		$product   = $productId ? wc_get_product( $productId ) : null;

		$button = $product ? $this->getLoopButton( $product ) : '';

		if ( '' !== $button ) {
			$this->blockButtons[ $product->get_id() ] = ( $this->blockButtons[ $product->get_id() ] ?? 0 ) + 1;
		}

		return $content . $button;
	}

	/**
	 * "Add to quote" for a simple product whose quote form adds to the list (variable products need their
	 * options chosen on the product page).
	 */
	protected function getLoopButton( WC_Product $product ): string {
		if ( ! QuoteListSettings::isAvailable() || ! QuoteListSettings::showOnShopPages() || is_product() ) {
			return '';
		}

		if ( ! $product->is_type( TierPricingTablePlugin::getSupportedSimpleProductTypes() ) || $product->get_parent_id() ) {
			return '';
		}

		$pricingRule = PriceManager::getPricingRule( $product->get_id() );
		$formId      = $pricingRule->data['tier_pricing_table_quote_form_id'] ?? '';

		// a form that still exists (products can keep the ID of a deleted form) and adds to the list
		$form = empty( $formId ) ? null : \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm::get( (string) $formId );

		if ( ! QuoteListSettings::formAddsToList( $form ) ) {
			return '';
		}

		// the same rule as the product page (a product without a price, one that is out of stock)
		if ( ! \TierPricingTable\Addons\RequestAQuote\Frontend\QuoteButtonRules::showsFor( $form, $product ) ) {
			return '';
		}

		$this->enqueueScript();

		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/quote-list-loop-button.php', array(
			'product'  => $product,
			'quantity' => max( 1, (int) $pricingRule->getMinimum() ),
			'text'     => $form->getQuoteListButtonText(),
			// outlined unless the form asks for another style
			'classes'  => \TierPricingTable\Addons\RequestAQuote\Frontend\QuoteButtonRules::getClasses( $form, 'button wp-element-button tpt-quote-button tpt-quote-button--outline' ),
		), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
	}

	/* --- the cart --------------------------------------------------------------------------------- */

	public function renderCartButton() {
		echo $this->getCartButton(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template
	}

	public function appendCartButtonToBlock( $content ) {
		return $content . $this->getCartButton();
	}

	protected function getCartButton(): string {
		if ( ! QuoteListSettings::isAvailable() || ! QuoteListSettings::showOnCart() ) {
			return '';
		}

		$this->enqueueScript();

		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/quote-list-cart-button.php', array(
			'pageUrl' => QuoteListSettings::getPageUrl(),
		), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
	}
}
