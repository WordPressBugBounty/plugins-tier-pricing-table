<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PricingRule;
use WC_Product;

/**
 * Discount Rules for WooCommerce (Flycart), free and PRO.
 *
 * Both plugins set the price of a cart line on "woocommerce_before_calculate_totals": Discount Rules at
 * priority 1000, Tiered Pricing at 99999, so without this integration the tier price wins and the cart
 * shows one price while charging another. With the integration on, Discount Rules takes priority: while
 * a rule discounts a product, its tiers are switched off (no pricing table, no cart recalculation) and
 * the discounted price applies at every quantity. When the rule ends, the tiers come back.
 *
 * Free gift items that Discount Rules adds to the cart are never re-priced, whether the integration is
 * on or off.
 */
class DiscountRulesForWooCommerce extends PluginIntegrationAbstract {

	/** The property Discount Rules sets on a cart line's product once it has applied a product discount. */
	const CART_ITEM_MARKER = 'awdr_product_original_price';

	public function __construct() {
		parent::__construct();

		// Free gifts stay free regardless of the toggle: re-pricing them is never right.
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', array( $this, 'skipFreeGift' ), 10, 2 );
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item', array( $this, 'skipFreeGift' ), 10, 2 );
	}

	public function run() {
		// After the product, role, customer and global rules (priorities 1 to 30) have built the tiers.
		add_filter( 'tiered_pricing_table/price/pricing_rule', array( $this, 'stepAsideForDiscountedProduct' ), 100, 2 );

		// Discounts that only show up in the cart (quantity-based rules): leave the line Discount Rules priced.
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', array( $this, 'skipDiscountedCartItem' ), 10, 2 );
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item', array( $this, 'skipDiscountedCartItem' ), 10, 2 );
	}

	public static function isDiscountRulesLoaded(): bool {
		return class_exists( '\Wdr\App\Router' ) && class_exists( '\Wdr\App\Controllers\ManageDiscount' )
		       && ! empty( \Wdr\App\Router::$manage_discount );
	}

	/**
	 * @param  bool  $recalculate
	 * @param  array  $cartItem
	 *
	 * @return bool
	 */
	public function skipFreeGift( $recalculate, $cartItem ) {
		return self::isFreeGift( (array) $cartItem ) ? false : $recalculate;
	}

	/**
	 * @param  bool  $recalculate
	 * @param  array  $cartItem
	 *
	 * @return bool
	 */
	public function skipDiscountedCartItem( $recalculate, $cartItem ) {
		return self::isDiscountedCartItem( (array) $cartItem ) ? false : $recalculate;
	}

	/**
	 * Empty the tiers of a product that Discount Rules is discounting right now.
	 *
	 * @param  PricingRule  $pricingRule
	 * @param  int  $productId
	 *
	 * @return PricingRule
	 */
	public function stepAsideForDiscountedProduct( $pricingRule, $productId ) {
		static $checking = array();

		if ( ! self::isDiscountRulesLoaded() || isset( $checking[ $productId ] ) || ! ( $pricingRule instanceof PricingRule ) ) {
			return $pricingRule;
		}

		$product = wc_get_product( $productId );

		if ( ! $product instanceof WC_Product ) {
			return $pricingRule;
		}

		// Discount Rules reads the product price, and the price filters ask Tiered Pricing for this product again.
		$checking[ $productId ] = true;

		try {
			$discount = \Wdr\App\Controllers\ManageDiscount::calculateInitialAndDiscountedPrice( $product, 1 );
		} catch ( \Throwable $e ) {
			$discount = null;
		}

		unset( $checking[ $productId ] );

		if ( self::isProductDiscount( $discount ) ) {
			$pricingRule->setRules( array() );
		}

		return $pricingRule;
	}

	/**
	 * Whether a Discount Rules price calculation holds a real discount.
	 *
	 * @param  mixed  $discount  Return value of ManageDiscount::calculateInitialAndDiscountedPrice().
	 */
	public static function isProductDiscount( $discount ): bool {
		if ( ! is_array( $discount ) || ! isset( $discount['initial_price'], $discount['discounted_price'] ) ) {
			return false;
		}

		return is_numeric( $discount['initial_price'] ) && is_numeric( $discount['discounted_price'] )
		       && (float) $discount['discounted_price'] < (float) $discount['initial_price'];
	}

	/**
	 * Whether Discount Rules has applied a product discount to this cart line in the current request.
	 */
	public static function isDiscountedCartItem( array $cartItem ): bool {
		$product = $cartItem['data'] ?? null;

		if ( ! ( $product instanceof WC_Product ) || ! isset( $product->{self::CART_ITEM_MARKER} ) ) {
			return false;
		}

		$original = $product->{self::CART_ITEM_MARKER};

		return is_numeric( $original ) && (float) $product->get_price( 'edit' ) < (float) $original;
	}

	public static function isFreeGift( array $cartItem ): bool {
		return isset( $cartItem['wdr_free_product'] );
	}

	public function getAuthorURL(): string {
		return 'https://wordpress.org/plugins/woo-discount-rules/';
	}

	public function getTitle(): string {
		return __( 'Discount Rules for WooCommerce', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Discount Rules takes priority: while one of its rules discounts a product, the tiers of that product are switched off and the discounted price applies at every quantity. Free gift items are never re-priced.', 'tier-pricing-table' );
	}

	public function getMetaPills(): array {
		return array(
			__( 'Discounts take priority', 'tier-pricing-table' ),
			__( 'Free gifts stay free', 'tier-pricing-table' ),
		);
	}

	public function getSlug(): string {
		return 'discount-rules-for-woocommerce';
	}

	public function getIntegrationCategory(): string {
		return 'other';
	}
}
