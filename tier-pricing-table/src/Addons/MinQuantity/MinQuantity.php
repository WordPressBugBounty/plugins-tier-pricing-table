<?php namespace TierPricingTable\Addons\MinQuantity;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Cart;
use WC_Product;

class MinQuantity extends AbstractAddon {
	
	public function getName(): string {
		return __( 'Minimum order quantity validation', 'tier-pricing-table' );
	}
	
	public function run() {
		
		add_action( 'woocommerce_before_calculate_totals', function ( WC_Cart $cart ) {
			foreach ( $cart->get_cart_contents() as $cartItemKey => $cartItem ) {
				if ( $cartItem['data'] instanceof WC_Product ) {
					
					$productId = ! empty( $cartItem['variation_id'] ) ? $cartItem['variation_id'] : $cartItem['product_id'];
					
					$pricingRule = PriceManager::getPricingRule( $productId );
					$min         = $pricingRule->getMinimum();
					
					if ( ! $min ) {
						return;
					}
					
					if ( $this->getProductCartQuantity( $cartItem['product_id'], 'product', $cart ) < $min ) {
						$cart->cart_contents[ $cartItemKey ]['quantity'] = $min;
						
						// translators: %1$s: item name, %2$s: minimum quantity
						wc_add_notice( sprintf( __( 'Minimum quantity for the %1$s is %2$d', 'tier-pricing-table' ),
							$cartItem['data']->get_name(), $min ), 'error' );
					}
				}
			}
		} );
		
		add_filter( 'woocommerce_quantity_input_args', function ( $args, $product = null ) {
			
			if ( $product instanceof WC_Product && TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
				$pricingRule = PriceManager::getPricingRule( $product->get_id() );
				$min         = $pricingRule->getMinimum();
				
				if ( ! $min ) {
					return $args;
				}
				
				$min               = max( 1, $min - $this->getProductCartQuantity( $product->get_id() ) );
				$args['min_value'] = $min;
			}
			
			return $args;
		}, 9999, 2 );
		
		// Quantity field in the cart
		add_filter( 'woocommerce_quantity_input_args', function ( $args, ?WC_Product $product ) {
			
			if ( ! apply_filters( 'tiered_pricing_table/minimum_quantity/control_cart_quantity_field', true,
				$product ) ) {
				return $args;
			}
			
			if ( ! is_cart() ) {
				return $args;
			}
			
			if ( $product instanceof WC_Product && TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
				$pricingRule = PriceManager::getPricingRule( $product->get_id() );
				$min         = $pricingRule->getMinimum();
				
				if ( ! $min ) {
					return $args;
				}
				
				$args['min_value'] = $pricingRule->getMinimum();
			}
			
			return $args;
		}, 10, 3 );
		
		add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $productId, $quantity ) {
			$productId = intval( $productId );
			$quantity  = intval( $quantity );
			
			$pricingRule = PriceManager::getPricingRule( $productId );
			$min         = $pricingRule->getMinimum();
			
			if ( ! $min ) {
				return $passed;
			}
			
			$min = max( 1, $min - $this->getProductCartQuantity( $productId ) );
			
			if ( $quantity < $min ) {
				
				// translators: %s: minimum quantity
				wc_add_notice( sprintf( __( 'Minimum quantity for the product is %s', 'tier-pricing-table' ), $min ),
					'error' );
				
				return false;
			}
			
			return $passed;
			
		}, 10, 3 );
		
		add_filter( 'woocommerce_update_cart_validation', function ( $passed, $cart_item_key, $values, $quantity ) {
			
			$product = $values['data'] ?? null;
			
			if ( ! ( $product instanceof WC_Product ) ) {
				return $passed;
			}
			
			$pricingRule = PriceManager::getPricingRule( $product->get_id() );
			
			if ( ! $pricingRule->getMinimum() ) {
				return $passed;
			}
			
			$min = max( 1, $pricingRule->getMinimum() - $this->getProductCartQuantity( $values['product_id'] ) );
			
			if ( $quantity && $quantity < $pricingRule->getMinimum() ) {
				
				// translators: %s: minimum quantity
				wc_add_notice( sprintf( __( 'Minimum quantity for the product is %s', 'tier-pricing-table' ), $pricingRule->getMinimum() ),
					'error' );
				
				return false;
			}
			
			return $passed;
			
		}, 10, 4 );
		
		add_filter( 'woocommerce_available_variation', function ( $variation ) {
			$pricingRule = PriceManager::getPricingRule( (int) $variation['variation_id'] );
			
			$min = $pricingRule->getMinimum();
			
			if ( ! $min ) {
				return $variation;
			}
			
			$min = max( 1, $min - $this->getProductCartQuantity( (int) $variation['variation_id'], 'variation' ) );
			
			$variation['min_qty']   = $min;
			$variation['qty_value'] = $min;
			
			return $variation;
		} );
		
		add_action( 'wp_head', function () {
			
			if ( ! is_product() ) {
				return;
			}
			
			?>
			<script>
				// Handle Minimum Quantities by Tiered Pricing Table
				(function ($) {

					$(document).on('found_variation', function (event, variation) {
						if (typeof variation.qty_value !== "undefined") {
							// update quantity field with a new minimum
							$('form.cart').find('[name=quantity]').val(variation.qty_value)
						}

						if (typeof variation.min_qty !== "undefined") {
							// update quantity field with a new minimum
							$('form.cart').find('[name=quantity]').attr('min', variation.min_qty);
						}
					});

				})(jQuery);
			</script>
			<?php
		} );
	}
	
	protected function getProductCartQuantity( $productId, $type = 'product', $cart = null ) {
		$qty = 0;
		
		$cart = $cart ? $cart : wc()->cart;
		
		if ( $cart && is_array( $cart->cart_contents ) ) {
			foreach ( $cart->cart_contents as $cartItem ) {
				
				if ( 'variation' === $type ) {
					$compare = ! empty( $cartItem['variation_id'] ) ? $cartItem['variation_id'] : 0;
				} else {
					$compare = $cartItem['product_id'];
				}
				
				if ( $compare == $productId ) {
					$qty += $cartItem['quantity'];
				}
			}
		}
		
		return apply_filters( 'tiered_pricing_table/minimum_quantity/item_quantity', $qty, $productId );
	}
	
	public function getDescription(): string {
		return __( 'Enforce minimum order quantities for products based on tiered pricing rules.', 'tier-pricing-table' );
	}
	
	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>';
	}
	
	public function getSlug(): string {
		return 'minimum-quantity';
	}
}
