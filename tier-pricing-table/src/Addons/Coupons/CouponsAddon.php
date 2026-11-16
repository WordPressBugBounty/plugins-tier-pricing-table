<?php namespace TierPricingTable\Addons\Coupons;

use Exception;
use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\PriceManager;
use WC_Cart;
use WC_Coupon;
use WC_Discounts;
use WC_Product;

/**
 * How a coupon and tiered prices work together, chosen per coupon on its General tab:
 *
 * - stack:   the coupon applies on top of the tier price (WooCommerce's default behaviour);
 * - regular: tiered prices are switched off for the products the coupon covers, which pay the regular price;
 * - exclude: products that have a tier price keep it and are left out of the coupon, like "Exclude sale items".
 *
 * Coupons saved with the former "Disable tiered pricing" checkbox read as "regular".
 */
class CouponsAddon extends AbstractAddon {

	const MODE_STACK   = 'stack';
	const MODE_REGULAR = 'regular';
	const MODE_EXCLUDE = 'exclude';

	const MODE_META_KEY   = '_tiered_pricing_coupon_mode';
	const LEGACY_META_KEY = '_disable_tiered_pricing';

	public function getName(): string {
		return __( 'Coupons and tiered prices', 'tier-pricing-table' );
	}

	public function run() {
		add_action( 'woocommerce_coupon_options', array( $this, 'addTieredPricingOption' ), 10, 2 );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'saveTieredPricingOption' ), 10, 2 );

		// "regular": no tier price for the items such a coupon covers
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation', array( $this, 'checkAppliedCoupons' ), 999, 3 );
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item', array( $this, 'checkAppliedCoupons' ), 999, 3 );

		// "exclude": items with a tier price are left out of the coupon
		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'excludeTieredProduct' ), 20, 4 );
		add_filter( 'woocommerce_coupon_get_items_to_apply', array( $this, 'excludeTieredItems' ), 20, 2 );
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'validateCartCouponWithTieredItems' ), 20, 3 );
	}

	/* --- the coupon's choice ----------------------------------------------------------------------- */

	public static function getModes(): array {
		return array(
			self::MODE_STACK   => __( 'Apply on top of tiered prices', 'tier-pricing-table' ),
			self::MODE_REGULAR => __( 'Switch tiered prices off', 'tier-pricing-table' ),
			self::MODE_EXCLUDE => __( 'Skip products with a tiered price', 'tier-pricing-table' ),
		);
	}

	public static function getCouponMode( WC_Coupon $coupon ): string {
		$mode = (string) $coupon->get_meta( self::MODE_META_KEY );

		if ( array_key_exists( $mode, self::getModes() ) ) {
			return $mode;
		}

		// coupons saved with the former checkbox
		return 'yes' === $coupon->get_meta( self::LEGACY_META_KEY ) ? self::MODE_REGULAR : self::MODE_STACK;
	}

	public function addTieredPricingOption( $couponId, $coupon ) {
		$coupon = $coupon instanceof WC_Coupon ? $coupon : new WC_Coupon( $couponId );

		woocommerce_wp_select( array(
			'id'          => self::MODE_META_KEY,
			'label'       => __( 'Tiered pricing', 'tier-pricing-table' ),
			'options'     => self::getModes(),
			'value'       => self::getCouponMode( $coupon ),
			'desc_tip'    => true,
			'description' => __( 'How this coupon works with quantity discounts. "Apply on top" takes the coupon off the tier price. "Switch tiered prices off" charges the regular price for the products the coupon covers. "Skip products" keeps their tier price and leaves them out of the coupon, like "Exclude sale items".',
				'tier-pricing-table' ),
		) );
	}

	public function saveTieredPricingOption( $couponId ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checks the coupon form's nonce before this action
		$mode = isset( $_POST[ self::MODE_META_KEY ] ) ? sanitize_key( wp_unslash( $_POST[ self::MODE_META_KEY ] ) ) : self::MODE_STACK;
		$mode = array_key_exists( $mode, self::getModes() ) ? $mode : self::MODE_STACK;

		update_post_meta( $couponId, self::MODE_META_KEY, $mode );

		// kept in step for code that still reads the former checkbox
		update_post_meta( $couponId, self::LEGACY_META_KEY, self::MODE_REGULAR === $mode ? 'yes' : 'no' );
	}

	/* --- "regular": tiered prices switched off ------------------------------------------------------- */

	/**
	 * Whether the cart item gets its tier price: not while a "regular" coupon that covers it is applied.
	 *
	 * @param  bool  $recalculate
	 * @param  array  $item  cart item
	 * @param  WC_Cart|null  $cart
	 */
	public function checkAppliedCoupons( $recalculate, $item, $cart = null ) {

		if ( ! $recalculate || empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
			return $recalculate;
		}

		$cart = $cart instanceof WC_Cart ? $cart : ( function_exists( 'WC' ) ? WC()->cart : null );

		if ( ! $cart ) {
			return $recalculate;
		}

		foreach ( $cart->get_coupons() as $coupon ) {
			if ( $coupon instanceof WC_Coupon
				 && self::MODE_REGULAR === self::getCouponMode( $coupon )
				 && $this->couponCoversProduct( $coupon, $item['data'], $item ) ) {
				return false;
			}
		}

		return $recalculate;
	}

	/**
	 * A cart coupon ("Fixed cart discount") discounts every item; a product coupon the items it is valid for.
	 */
	protected function couponCoversProduct( WC_Coupon $coupon, WC_Product $product, array $item ): bool {

		if ( $coupon->is_type( wc_get_cart_coupon_types() ) ) {
			return true;
		}

		return $coupon->is_valid_for_product( $product, $item );
	}

	/* --- "exclude": items with a tier price left out --------------------------------------------- */

	/**
	 * Product coupons (percentage, fixed product): not valid for an item that has a tier price.
	 *
	 * @param  bool  $valid
	 * @param  WC_Product  $product
	 * @param  WC_Coupon  $coupon
	 * @param  array|mixed  $values  the cart item
	 */
	public function excludeTieredProduct( $valid, $product, $coupon, $values = array() ) {

		if ( ! $valid || ! $coupon instanceof WC_Coupon || self::MODE_EXCLUDE !== self::getCouponMode( $coupon ) ) {
			return $valid;
		}

		return is_array( $values ) && $this->hasTierPrice( $values ) ? false : $valid;
	}

	/**
	 * Every coupon type (WooCommerce 8.8+): the items the discount is spread over, without the ones that have a
	 * tier price. A "Fixed cart discount" is then shared by the other items.
	 *
	 * @param  array  $items
	 * @param  WC_Coupon  $coupon
	 */
	public function excludeTieredItems( $items, $coupon ) {

		if ( ! is_array( $items ) || ! $coupon instanceof WC_Coupon || self::MODE_EXCLUDE !== self::getCouponMode( $coupon ) ) {
			return $items;
		}

		return array_values( array_filter( $items, function ( $item ) {
			return ! ( isset( $item->object ) && is_array( $item->object ) && $this->hasTierPrice( $item->object ) );
		} ) );
	}

	/**
	 * A cart coupon that would leave out every item of the cart is refused with a message, as WooCommerce does for a
	 * product coupon that fits no item.
	 *
	 * @param  bool  $valid
	 * @param  WC_Coupon  $coupon
	 * @param  WC_Discounts|null  $discounts
	 *
	 * @throws Exception The reason the coupon does not apply.
	 */
	public function validateCartCouponWithTieredItems( $valid, $coupon, $discounts = null ) {

		if ( ! $valid || ! $coupon instanceof WC_Coupon || ! $discounts instanceof WC_Discounts
			 || self::MODE_EXCLUDE !== self::getCouponMode( $coupon ) || ! $coupon->is_type( wc_get_cart_coupon_types() ) ) {
			return $valid;
		}

		$items = $discounts->get_items_to_validate();

		if ( empty( $items ) ) {
			return $valid;
		}

		foreach ( $items as $item ) {
			if ( ! isset( $item->object ) || ! is_array( $item->object ) || ! $this->hasTierPrice( $item->object ) ) {
				return $valid;
			}
		}

		throw new Exception( esc_html__( 'This coupon does not apply to products that already have a quantity discount.', 'tier-pricing-table' ), 109 );
	}

	/**
	 * Whether a cart item is charged a tier price: the cart marks the items it re-prices; an item it has not
	 * re-priced yet in this request is worked out from its quantity.
	 */
	protected function hasTierPrice( array $cartItem ): bool {

		if ( empty( $cartItem['data'] ) || ! $cartItem['data'] instanceof WC_Product ) {
			return false;
		}

		if ( 'yes' === $cartItem['data']->get_meta( 'tiered_pricing_cart_price_calculated' ) ) {
			return true;
		}

		if ( ! $this->checkAppliedCoupons( true, $cartItem ) ) {
			return false;
		}

		$quantity = (int) ( $cartItem['tiered_pricing_data']['total_item_quantity'] ?? ( $cartItem['quantity'] ?? 0 ) );

		return $quantity > 0 && false !== PriceManager::getPricingRule( $cartItem['data']->get_id() )->getTierPrice( $quantity, false, 'cart' );
	}

	/* --- module card ------------------------------------------------------------------------------- */

	public function getDescription(): string {
		return __( 'Choose per coupon whether it applies on top of tiered prices, switches them off, or skips products that already have a tiered price.', 'tier-pricing-table' );
	}

	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/></svg>';
	}

	public function getSlug(): string {
		return 'coupons';
	}

	public function getGroup(): string {
		return self::GROUP_PRICING;
	}
}
