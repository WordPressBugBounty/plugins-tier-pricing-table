<?php namespace TierPricingTable\Addons\ProductCatalogLoop;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WP_Block;

class QuantityFieldHandler {
	
	/** The Interactivity API module behind the box next to the Product Button block. */
	const BLOCK_MODULE = 'tiered-pricing-table-catalog-quantity';
	
	/** Whether the wrapper around the quantity box and the add-to-cart button is open for the current loop item. */
	protected bool $wrapperOpen = false;
	
	public function __construct() {
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'renderQuantityField' ), 9 );
		// WooCommerce prints the add-to-cart button at priority 10; the wrapper closes right after it
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'closeWrapper' ), 11 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueQuantityHandlerScript' ) );
		
		// Product Collection and block themes: the box goes into the Product Button block, before the
		// catalog pricing is added around that block (priority 10)
		add_filter( 'render_block_woocommerce/product-button', array( $this, 'renderInProductButtonBlock' ), 9, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'registerBlockModule' ) );
	}
	
	/**
	 * Simple products that can be bought in any quantity get the box.
	 */
	protected function wantsQuantityField( ?WC_Product $product ): bool {
		return $product && TierPricingTablePlugin::isSimpleProductSupported( $product ) && ! $product->is_sold_individually() && $product->is_purchasable() && $product->is_in_stock();
	}
	
	public function registerBlockModule() {
		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module( self::BLOCK_MODULE,
				ServiceContainer::getInstance()->getFileManager()->locateJSAsset( 'frontend/catalog-quantity' ),
				array( '@wordpress/interactivity' ), TierPricingTablePlugin::VERSION );
		}
	}
	
	/**
	 * The quantity box next to the Product Button block's button, on one line. The block keeps the
	 * quantity it adds in its Interactivity API context; the box's module writes the typed quantity there.
	 *
	 * @param  string  $content
	 * @param  array  $block
	 * @param  WP_Block|mixed  $instance
	 *
	 * @return string
	 */
	public function renderInProductButtonBlock( $content, $block, $instance ) {
		if ( ! function_exists( 'wp_enqueue_script_module' ) || ! ( $instance instanceof WP_Block ) || empty( $instance->context['postId'] ) ) {
			return $content;
		}
		
		// only the button of a product list: inside the add-to-cart-with-options form the block has its own quantity selector
		if ( false === strpos( $content, 'actions.addCartItem' ) ) {
			return $content;
		}
		
		$product = wc_get_product( (int) $instance->context['postId'] );
		
		if ( ! $this->wantsQuantityField( $product ) ) {
			return $content;
		}
		
		$quantity = woocommerce_quantity_input( array(
			'min_value' => 1,
			'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity(),
		), $product, false );
		
		if ( ! $quantity ) {
			return $content;
		}
		
		// the box is its own interactive region; the input feeds the button block's context
		$quantity = preg_replace( '/<div class="quantity/', '<div data-wp-interactive="tiered-pricing-table/catalog-quantity" class="quantity', $quantity, 1 );
		$quantity = preg_replace( '/<input\b/', '<input data-wp-on--input="actions.update" data-wp-on--change="actions.update" data-wp-on--keydown="actions.submitOnEnter"', $quantity, 1 );
		
		// the block's own top spacing sits on the button; the row takes it over so both parts align
		$rowStyle = '';
		
		if ( preg_match( '/<button\b[^>]*\sstyle="([^"]*)"/', $content, $m ) && preg_match( '/margin-top:[^;"]+/', $m[1], $marginTop ) ) {
			$rowStyle = ' style="' . esc_attr( $marginTop[0] ) . ';"';
			$content  = preg_replace_callback( '/(<button\b[^>]*\sstyle=")([^"]*)(")/', function ( $mm ) use ( $marginTop ) {
				return $mm[1] . trim( str_replace( $marginTop[0], '', $mm[2] ), '; ' ) . $mm[3];
			}, $content, 1 );
		}
		
		$wrapped = preg_replace_callback( '/<button\b.*?<\/button>/s', function ( $mm ) use ( $quantity, $rowStyle ) {
			return '<div class="tpt-loop-purchase"' . $rowStyle . '>' . $quantity . $mm[0] . '</div>';
		}, $content, 1, $count );
		
		if ( ! $count ) {
			return $content;
		}
		
		wp_enqueue_script_module( self::BLOCK_MODULE );
		
		return $wrapped;
	}
	
	public function renderQuantityField() {
		// the product button block has no place for the field, and WooCommerce fires this classic hook there too
		if ( ProductCatalogLoop::isRenderingProductBlocks() ) {
			return;
		}
		
		$product = wc_get_product( get_the_ID() );
		
		if ( $this->wantsQuantityField( $product ) ) {
			// the quantity box and the add-to-cart button share one line (a flex row, see main.css)
			if ( apply_filters( 'tiered_pricing_table/catalog/quantity_inline', true, $product ) ) {
				echo '<div class="tpt-loop-purchase">';
				$this->wrapperOpen = true;
			}
			
			woocommerce_quantity_input( array(
				'min_value' => 1,
				'max_value' => $product->backorders_allowed() ? '' : $product->get_stock_quantity(),
			) );
		}
	}
	
	public function closeWrapper() {
		if ( $this->wrapperOpen ) {
			echo '</div>';
			$this->wrapperOpen = false;
		}
	}
	
	public function enqueueQuantityHandlerScript() {
		
		$handle = 'tpt-catalog-quantity-input-handler';
		
		wp_register_script( $handle, '', array( 'jquery' ), TierPricingTablePlugin::VERSION, true );
		
		wp_enqueue_script( $handle );
		
		$inlineJs = '
			jQuery(".type-product").on("click", ".quantity input", function() {
				return false;
			});

			jQuery(".type-product").on("change input", ".quantity .qty", function() {
				var add_to_cart_button = jQuery(this).closest(".product").find(".add_to_cart_button");

				// For AJAX add-to-cart actions
				add_to_cart_button.attr("data-quantity", jQuery(this).val());

				// For non-AJAX add-to-cart actions (the Product Button block is a <button> and keeps its own quantity)
				if (add_to_cart_button.is("a")) {
					add_to_cart_button.attr("href", "?add-to-cart=" + add_to_cart_button.attr("data-product_id") + "&quantity=" + jQuery(this).val());
				}
			});

			// Trigger on Enter press
			jQuery(".woocommerce .products").on("keypress", ".quantity .qty", function(e) {
				if ((e.which || e.keyCode) === 13) {
					jQuery(this).parents(".product").find(".add_to_cart_button").trigger("click");
				}
			});
		';
		
		wp_add_inline_script( $handle, $inlineJs );
	}
}
