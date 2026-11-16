<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend;

use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which products a form's quote button shows for, and how the button looks. Shared by the product page, the
 * shop pages and the quote list, so they agree.
 */
class QuoteButtonRules {

	/**
	 * A product that can be bought always gets the button. One that cannot:
	 * - next to Add to cart: when the form says "Show for non-purchasable products";
	 * - inside or below the pricing table: when it has no price ("price on request"), not when it is only
	 *   out of stock.
	 */
	public static function showsFor( RequestQuoteForm $form, WC_Product $product ): bool {
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			return true;
		}

		if ( 'next_to_add_to_cart' === $form->getDisplayPosition() ) {
			return $form->getShowForNonPurchasable();
		}

		return '' === (string) $product->get_price();
	}

	/**
	 * The classes of the form's button.
	 *
	 * @param  RequestQuoteForm  $form
	 * @param  string  $legacy  The classes this place used before forms had a button style.
	 *
	 * @return string
	 */
	public static function getClasses( RequestQuoteForm $form, string $legacy ): string {
		switch ( $form->getButtonStyle() ) {
			case 'outline':
				return 'button wp-element-button tpt-quote-button tpt-quote-button--outline';
			case 'filled':
				return 'button alt wp-element-button tpt-quote-button';
			case 'link':
				return 'tpt-quote-button tpt-quote-button--link';
			default:
				return $legacy;
		}
	}
}
