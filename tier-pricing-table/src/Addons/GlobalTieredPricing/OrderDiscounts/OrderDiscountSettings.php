<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSettings;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\Settings;

/**
 * Store-wide behaviour of the order rules: which subtotal the tiers compare with, whether the discount
 * survives a customer coupon, the "add X more" message, the minimum order messages, and where and how the
 * free shipping bar shows. Edited on the Order rules settings page (a settings section reached from the
 * rule editor and the rules list). The rules themselves carry the tiers, the minimum, the scope, the label and the bar's own
 * messages.
 */
class OrderDiscountSettings {

	const BASIS_DISPLAY = 'display';

	const BASIS_EXCLUDING_TAX = 'excl';

	const BASIS_INCLUDING_TAX = 'incl';

	public static function optionId( string $key ): string {
		return Settings::SETTINGS_PREFIX . 'order_discount_' . $key;
	}

	/**
	 * Which subtotal the tiers are compared with: the one the cart displays (with or without tax, per the
	 * WooCommerce setting), or always without or always with tax.
	 */
	public static function getSubtotalBasis(): string {
		$basis = (string) get_option( self::optionId( 'subtotal_basis' ), self::BASIS_DISPLAY );

		return in_array( $basis, array( self::BASIS_DISPLAY, self::BASIS_EXCLUDING_TAX, self::BASIS_INCLUDING_TAX ), true ) ? $basis : self::BASIS_DISPLAY;
	}

	/**
	 * Whether the order discount stays when the customer applies a coupon of their own. An individual-use
	 * coupon always replaces it.
	 */
	public static function keepWithCoupons(): bool {
		return 'yes' === get_option( self::optionId( 'keep_with_coupons' ), 'yes' );
	}

	/**
	 * The message shown until the next tier is reached; empty hides it.
	 */
	public static function getNudgeTemplate(): string {
		return (string) get_option( self::optionId( 'nudge_message' ), self::defaultNudgeTemplate() );
	}

	public static function defaultNudgeTemplate(): string {
		return __( 'Add {remaining} more to get {discount} off your order.', 'tier-pricing-table' );
	}

	/* --- the free shipping bar ------------------------------------------------------------------- */

	const BAR_PLACEMENT_CART = 'cart';

	const BAR_PLACEMENT_CHECKOUT = 'checkout';

	const BAR_PLACEMENT_MINI_CART = 'minicart';

	const BAR_PLACEMENT_PRODUCT = 'product';

	/**
	 * Where the bar can show on its own, besides the shortcode and the block.
	 */
	public static function getBarPlacementOptions(): array {
		return array(
			self::BAR_PLACEMENT_CART      => __( 'Classic cart, under the cart totals', 'tier-pricing-table' ),
			self::BAR_PLACEMENT_CHECKOUT  => __( 'Classic checkout, under the order review total', 'tier-pricing-table' ),
			self::BAR_PLACEMENT_MINI_CART => __( 'Mini cart (the cart widget)', 'tier-pricing-table' ),
			self::BAR_PLACEMENT_PRODUCT   => __( 'Product page, above the add to cart form', 'tier-pricing-table' ),
		);
	}

	public static function defaultBarPlacements(): array {
		return array( self::BAR_PLACEMENT_CART, self::BAR_PLACEMENT_CHECKOUT, self::BAR_PLACEMENT_MINI_CART );
	}

	/**
	 * The chosen placements; the default three until the option is saved, none when every box is unticked.
	 *
	 * @return string[]
	 */
	public static function getBarPlacements(): array {
		$stored = get_option( self::optionId( 'bar_placements' ), null );

		if ( is_null( $stored ) || false === $stored ) {
			return self::defaultBarPlacements();
		}

		return array_values( array_intersect( TPTCheckboxListOption::toArray( $stored ), array_keys( self::getBarPlacementOptions() ) ) );
	}

	public static function defaultBarColor(): string {
		return '#3858e9';
	}

	public static function defaultBarReachedColor(): string {
		return '#1e8a4c';
	}

	/**
	 * The fill colour of the bar while the threshold is not reached.
	 */
	public static function getBarColor(): string {
		$color = sanitize_hex_color( (string) get_option( self::optionId( 'bar_color' ), self::defaultBarColor() ) );

		return $color ? $color : self::defaultBarColor();
	}

	/**
	 * The fill colour once free shipping is reached.
	 */
	public static function getBarReachedColor(): string {
		$color = sanitize_hex_color( (string) get_option( self::optionId( 'bar_reached_color' ), self::defaultBarReachedColor() ) );

		return $color ? $color : self::defaultBarReachedColor();
	}

	/**
	 * The height of the bar in pixels.
	 */
	public static function getBarHeight(): int {
		$height = absint( get_option( self::optionId( 'bar_height' ), 8 ) );

		return $height > 0 ? min( $height, 40 ) : 8;
	}

	/**
	 * The store-wide message until the threshold is reached; a rule's own message replaces it.
	 */
	public static function getBarTemplate(): string {
		return (string) get_option( self::optionId( 'bar_message' ), self::defaultBarTemplate() );
	}

	public static function defaultBarTemplate(): string {
		return __( 'Add {remaining} more for free shipping.', 'tier-pricing-table' );
	}

	/**
	 * The store-wide message once free shipping is reached; a rule's own message replaces it.
	 */
	public static function getBarReachedTemplate(): string {
		return (string) get_option( self::optionId( 'bar_reached_message' ), self::defaultBarReachedTemplate() );
	}

	public static function defaultBarReachedTemplate(): string {
		return __( 'You have unlocked free shipping!', 'tier-pricing-table' );
	}

	/**
	 * The option ids by key, without the translated defaults: safe to call before init.
	 *
	 * @return array<string, string>
	 */
	public static function getOptionIds(): array {
		return array(
			'subtotal_basis'      => self::optionId( 'subtotal_basis' ),
			'keep_with_coupons'   => self::optionId( 'keep_with_coupons' ),
			'nudge_message'       => self::optionId( 'nudge_message' ),
			'amount_message'      => CartRulesSettings::optionId( 'amount_message' ),
			'quantity_message'    => CartRulesSettings::optionId( 'quantity_message' ),
			'bar_placements'      => self::optionId( 'bar_placements' ),
			'bar_message'         => self::optionId( 'bar_message' ),
			'bar_reached_message' => self::optionId( 'bar_reached_message' ),
			'bar_color'           => self::optionId( 'bar_color' ),
			'bar_reached_color'   => self::optionId( 'bar_reached_color' ),
			'bar_height'          => self::optionId( 'bar_height' ),
		);
	}

	/**
	 * Every store-wide option of the order rules: key => id, default and the kind the customizer sanitises
	 * it as (choice, switch, template, list, color, number). The minimum order messages keep the ids of the
	 * old cart rules.
	 */
	public static function getOptionDefinitions(): array {
		return array(
			'subtotal_basis'      => array( 'id' => self::optionId( 'subtotal_basis' ), 'default' => self::BASIS_DISPLAY, 'kind' => 'choice', 'choices' => array( self::BASIS_DISPLAY, self::BASIS_EXCLUDING_TAX, self::BASIS_INCLUDING_TAX ) ),
			'keep_with_coupons'   => array( 'id' => self::optionId( 'keep_with_coupons' ), 'default' => 'yes', 'kind' => 'switch' ),
			'nudge_message'       => array( 'id' => self::optionId( 'nudge_message' ), 'default' => self::defaultNudgeTemplate(), 'kind' => 'template' ),
			'amount_message'      => array( 'id' => CartRulesSettings::optionId( 'amount_message' ), 'default' => CartRulesSettings::defaultAmountMessage(), 'kind' => 'template' ),
			'quantity_message'    => array( 'id' => CartRulesSettings::optionId( 'quantity_message' ), 'default' => CartRulesSettings::defaultQuantityMessage(), 'kind' => 'template' ),
			'bar_placements'      => array( 'id' => self::optionId( 'bar_placements' ), 'default' => implode( ',', self::defaultBarPlacements() ), 'kind' => 'list', 'choices' => array_keys( self::getBarPlacementOptions() ) ),
			'bar_message'         => array( 'id' => self::optionId( 'bar_message' ), 'default' => self::defaultBarTemplate(), 'kind' => 'template' ),
			'bar_reached_message' => array( 'id' => self::optionId( 'bar_reached_message' ), 'default' => self::defaultBarReachedTemplate(), 'kind' => 'template' ),
			'bar_color'           => array( 'id' => self::optionId( 'bar_color' ), 'default' => self::defaultBarColor(), 'kind' => 'color' ),
			'bar_reached_color'   => array( 'id' => self::optionId( 'bar_reached_color' ), 'default' => self::defaultBarReachedColor(), 'kind' => 'color' ),
			'bar_height'          => array( 'id' => self::optionId( 'bar_height' ), 'default' => '8', 'kind' => 'number', 'min' => 2, 'max' => 40 ),
		);
	}
}
