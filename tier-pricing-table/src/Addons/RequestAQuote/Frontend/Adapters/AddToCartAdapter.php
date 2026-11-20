<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend\Adapters;

use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteButtonRules;
use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteFormDisplay;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\TierPricingTablePlugin;

class AddToCartAdapter extends AbstractLayoutAdapter {
	
	protected bool $hasRendered = false;
	
	public function registerHooks() {
		// Hook into the single product page add to cart area
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'renderButtonNextToCart' ), -10 );
		
		// Fallback for non-purchasable or out-of-stock products
		add_action( 'woocommerce_single_product_summary', array( $this, 'renderCustomCartForm' ), 31 );
		// the same for block themes, whose product template has no woocommerce_single_product_summary
		add_filter( 'render_block_woocommerce/add-to-cart-with-options', array( $this, 'appendToAddToCartBlock' ), 10, 3 );
		
		// Hook to hide the add to cart button via CSS if configured
		add_action( 'wp_head', array( $this, 'maybeHideAddToCartButton' ) );

		// "Hide Add to Cart" means the product is bought through a quote only: no add to cart by link, shop page,
		// AJAX, the Store API (blocks) or the Quick Order Form, and shop pages link to the product instead
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'refuseHiddenAddToCart' ), 20, 4 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'linkToProductPage' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'quoteButtonText' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_description', array( $this, 'quoteButtonDescription' ), 20, 2 );
		add_filter( 'woocommerce_product_supports', array( $this, 'noAjaxAddToCart' ), 20, 3 );
	}

	/**
	 * Whether a product (or variation) is bought through a quote only: its quote form hides Add to cart.
	 * Used outside the product page (product feeds), so it builds its own adapter.
	 *
	 * @param  int  $productId
	 *
	 * @return bool
	 */
	public static function isQuoteOnly( int $productId ): bool {
		return null !== ( new self() )->getHidingForm( $productId );
	}

	/**
	 * The product's quote form shows its button next to Add to cart and hides Add to cart.
	 *
	 * @param  int  $productId  A product or a variation.
	 *
	 * @return RequestQuoteForm|null
	 */
	protected function getHidingForm( int $productId ): ?RequestQuoteForm {
		if ( $productId <= 0 ) {
			return null;
		}

		$form = $this->getValidFormForNextToCartPosition( PriceManager::getPricingRule( $productId ) );

		return $form && $form->getHideAddToCart() ? $form : null;
	}

	/**
	 * @param  bool  $passed
	 * @param  int  $productId
	 * @param  int|float  $quantity
	 * @param  int  $variationId
	 *
	 * @return bool
	 */
	public function refuseHiddenAddToCart( $passed, $productId = 0, $quantity = 1, $variationId = 0 ) {
		if ( ! $passed || ! $this->getHidingForm( (int) ( $variationId ? $variationId : $productId ) ) ) {
			return $passed;
		}

		wc_add_notice( __( 'This product is available on request only. Please request a quote for it on the product page.', 'tier-pricing-table' ), 'error' );

		return false;
	}

	/**
	 * @param  string  $url
	 * @param  \WC_Product|null  $product
	 *
	 * @return string
	 */
	public function linkToProductPage( $url, $product = null ) {
		return $product instanceof \WC_Product && $this->getHidingForm( $product->get_id() ) ? $product->get_permalink() : $url;
	}

	/**
	 * @param  string  $text
	 * @param  \WC_Product|null  $product
	 *
	 * @return string
	 */
	public function quoteButtonText( $text, $product = null ) {
		$form = $product instanceof \WC_Product ? $this->getHidingForm( $product->get_id() ) : null;

		if ( ! $form ) {
			return $text;
		}

		// a form for multiple products has its own "Add to quote" button on shop pages: this one only links
		return QuoteListSettings::formAddsToList( $form ) ? __( 'View product', 'tier-pricing-table' ) : $form->getPromptText();
	}

	/**
	 * What screen readers hear for the shop page link: it no longer adds to the cart.
	 *
	 * @param  string  $description
	 * @param  \WC_Product|null  $product
	 *
	 * @return string
	 */
	public function quoteButtonDescription( $description, $product = null ) {
		if ( ! $product instanceof \WC_Product || ! $this->getHidingForm( $product->get_id() ) ) {
			return $description;
		}

		/* translators: 1: the link text, like "Request a quote", 2: product name */
		return sprintf( __( '%1$s: &ldquo;%2$s&rdquo;', 'tier-pricing-table' ), $this->quoteButtonText( '', $product ), $product->get_name() );
	}

	/**
	 * @param  bool  $supports
	 * @param  string  $feature
	 * @param  \WC_Product|null  $product
	 *
	 * @return bool
	 */
	public function noAjaxAddToCart( $supports, $feature = '', $product = null ) {
		if ( 'ajax_add_to_cart' !== $feature || ! $product instanceof \WC_Product ) {
			return $supports;
		}

		return $this->getHidingForm( $product->get_id() ) ? false : $supports;
	}
	
	public function renderButtonNextToCart() {
		global $product;

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$pricingRule = PriceManager::getPricingRule( $product->get_id() );
		$form        = $this->getValidFormForNextToCartPosition( $pricingRule );

		if ( ! $form ) {
			return;
		}

		$this->hasRendered = true;

		echo wp_kses_post( $this->getStandaloneButton( $form, $product ) );
	}

	/**
	 * The quote button outside a pricing layout: next to Add to cart, or by itself on a product page.
	 */
	protected function getStandaloneButton( RequestQuoteForm $form, \WC_Product $product ): string {
		QuoteFormDisplay::addModalToRender( $form, $product->get_id() );
		QuoteFormDisplay::enqueueButtonStyle();

		$classes = QuoteButtonRules::getClasses( $form, 'button alt wp-element-button' ) . ' tpt-request-quote-trigger tpt-quote-next-to-cart ' . QuoteListSettings::getTriggerClass( $form );

		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/integrated/quote-button.php', array(
			'form'        => $form,
			'productId'   => $product->get_id(),
			'classes'     => trim( $classes ),
			'idAttr'      => 'tpt-raq-link-' . $product->get_id(),
			'autoOpenQty' => $form->getAutoOpenQuantity(),
			'style'       => '',
			'content'     => esc_html( $form->getPromptText() ),
		), plugin_dir_path( dirname( __DIR__ ) ) . 'views/' );
	}

	/**
	 * The form whose button has to stand by itself on this product page, because the place it normally has is
	 * missing:
	 * - next to Add to cart: the product has no add to cart form (no price, out of stock);
	 * - inside or below the pricing layout: the product has no pricing layout (no price, or no tiers), and no
	 *   quote button was printed for it.
	 *
	 * @param  \WC_Product  $product
	 * @param  bool  $layoutsDone  The product's pricing layout, if it has one, was printed already.
	 *
	 * @return RequestQuoteForm|null
	 */
	protected function getStandaloneForm( \WC_Product $product, bool $layoutsDone ): ?RequestQuoteForm {
		$pricingRule = PriceManager::getPricingRule( $product->get_id() );
		$formId      = $this->getAssignedFormId( $pricingRule, null );
		$form        = $formId ? $this->getForm( $formId ) : null;

		if ( ! $form || ! QuoteButtonRules::showsFor( $form, $product ) ) {
			return null;
		}

		$form->setOverrides( $this->getEntityOverrides( $pricingRule, null ) );

		$buyable = $product->is_purchasable() && $product->is_in_stock();

		if ( 'next_to_add_to_cart' === $form->getDisplayPosition() ) {
			// with an add to cart form the button is printed next to it
			return $buyable ? null : $form;
		}

		// a product with options gets its pricing layouts (and their buttons) per variation, some of them later
		if ( ! $layoutsDone || TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
			return null;
		}

		return isset( QuoteFormDisplay::$renderedModals[ $product->get_id() ] ) ? null : $form;
	}

	/**
	 * Classic themes: after the add to cart form (and the pricing layout inside it) had its chance.
	 */
	public function renderCustomCartForm() {
		if ( $this->hasRendered ) {
			return; // Standard button was already rendered
		}

		global $product;

		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		// block themes run this hook before the add to cart block: appendToAddToCartBlock() decides there
		$form = $this->getStandaloneForm( $product, ! $this->usesBlockTemplate() );

		if ( ! $form ) {
			return;
		}

		$this->hasRendered = true; // Mark as rendered so we don't render again if called elsewhere
		?>
		<div class="tpt-custom-quote-cart tpt-quote-standalone">
			<?php echo wp_kses_post( $this->getStandaloneButton( $form, $product ) ); ?>
		</div>
		<?php
	}

	/**
	 * Block themes: after the add to cart block, which is empty for a product without a price and holds the
	 * pricing layout for the others.
	 *
	 * @param  string  $content
	 * @param  array  $block
	 * @param  \WP_Block|null  $instance
	 */
	public function appendToAddToCartBlock( $content, $block = array(), $instance = null ) {
		if ( $this->hasRendered ) {
			return $content;
		}

		$productId = $instance instanceof \WP_Block ? (int) ( $instance->context['postId'] ?? 0 ) : 0;
		$product   = $productId ? wc_get_product( $productId ) : null;

		if ( ! $product instanceof \WC_Product ) {
			global $product;
		}

		$form = $product instanceof \WC_Product ? $this->getStandaloneForm( $product, true ) : null;

		if ( ! $form ) {
			return $content;
		}

		$this->hasRendered = true;

		return $content . '<div class="tpt-custom-quote-cart tpt-quote-standalone">' . $this->getStandaloneButton( $form, $product ) . '</div>';
	}

	/**
	 * The product page is built from a block template (block themes), where the classic summary hooks run
	 * before the add to cart block.
	 */
	protected function usesBlockTemplate(): bool {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	public function maybeHideAddToCartButton() {
		if ( ! is_product() ) {
			return;
		}
		
		$productId = get_the_ID();
		if ( ! $productId ) {
			return;
		}
		
		$pricingRule = PriceManager::getPricingRule( $productId );
		$form        = $this->getValidFormForNextToCartPosition( $pricingRule );
		
		if ( $form && $form->getHideAddToCart() ) {
			// the classic form's button, and the button of the block form (block themes)
			echo '<style>.single_add_to_cart_button, form.wc-block-add-to-cart-with-options .wp-block-woocommerce-product-button { display: none !important; }</style>';
		}
	}
	
	/**
	 * Check if the given pricing rule has a valid form configured for the next_to_add_to_cart position.
	 *
	 * @param  PricingRule  $pricingRule
	 *
	 * @return RequestQuoteForm|false
	 */
	protected function getValidFormForNextToCartPosition( $pricingRule ) {
		$formId = $this->getAssignedFormId( $pricingRule, null );
		if ( ! $formId ) {
			return false;
		}
		
		$form = $this->getForm( $formId );
		if ( ! $form ) {
			return false;
		}
		
		if ( 'next_to_add_to_cart' !== $form->getDisplayPosition() ) {
			return false;
		}
		
		$overrides = $this->getEntityOverrides( $pricingRule, null );
		$form->setOverrides( $overrides );
		
		return $form;
	}
}
