<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WC_Cart;
use WC_Coupon;

/**
 * The order discount reaches WooCommerce as a virtual coupon: WooCommerce computes it, shows one labelled
 * line in the cart, the checkout and the order, and counts it in Analytics. The coupon exists only while
 * its rule's tier is reached; it is applied and dropped here, never by the customer.
 */
class OrderDiscountCoupon {

	const SESSION_KEY = 'tpt_order_discount_codes';

	/**
	 * The discount applied in this calculation.
	 *
	 * @var OrderDiscount|null
	 */
	protected $wanted = null;

	/**
	 * Every published order rule by coupon code.
	 *
	 * @var array<string, GlobalPricingRule>|null
	 */
	protected $codes = null;

	/**
	 * Returns the published order rules; the CPT by default (tests hand in their own).
	 *
	 * @var callable|null
	 */
	protected $rulesProvider;

	public function __construct( ?callable $rulesProvider = null ) {
		$this->rulesProvider = $rulesProvider;

		add_filter( 'woocommerce_get_shop_coupon_data', array( $this, 'provide' ), 10, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'label' ), 10, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'removeRemoveLink' ), 10, 2 );
		add_filter( 'woocommerce_coupon_message', array( $this, 'silence' ), 10, 3 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'silence' ), 10, 3 );
	}

	/**
	 * Apply the discount the cart has earned and drop the one it no longer has. Customer coupons stay; an
	 * individual-use coupon, or any coupon when the setting says so, keeps the order discount out.
	 */
	public function sync( WC_Cart $cart, ?OrderDiscount $discount ) {
		$applied    = array_map( 'wc_format_coupon_code', $cart->get_applied_coupons() );
		$remembered = $this->rememberedCodes();
		$others     = array();
		$exclusive  = false;

		foreach ( $applied as $code ) {
			if ( $this->isOurs( $code, $remembered ) ) {
				continue;
			}

			$others[] = $code;

			$coupon    = new WC_Coupon( $code );
			$exclusive = $exclusive || $coupon->get_individual_use();
		}

		$apply = $discount && ! $exclusive && ( empty( $others ) || OrderDiscountSettings::keepWithCoupons() );

		$this->wanted = $apply ? $discount : null;

		$keep = $others;

		if ( $apply ) {
			$keep[] = $discount->getCode();
		}

		if ( $keep !== $applied ) {
			$cart->set_applied_coupons( $keep );
		}

		$this->remember( $apply ? $discount->getCode() : '' );
	}

	public function getApplied(): ?OrderDiscount {
		return $this->wanted;
	}

	/**
	 * The coupon data for our codes. The applied discount gets its real amount; any other order-rule code
	 * (left in a session by an earlier visit) is kept valid with no discount until sync() drops it.
	 *
	 * @param  mixed  $data
	 * @param  mixed  $code
	 *
	 * @return mixed
	 */
	public function provide( $data, $code ) {
		if ( ! is_string( $code ) || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $data;
		}

		$code = wc_format_coupon_code( $code );

		if ( $this->wanted && $this->wanted->getCode() === $code ) {
			return $this->wanted->getCouponProps();
		}

		$rule = $this->ruleByCode( $code );

		if ( $rule ) {
			return OrderDiscount::couponPropsFor( $rule, 0.0 );
		}

		return $data;
	}

	/**
	 * The discount line's label (WooCommerce prints it as is, so it is escaped here).
	 *
	 * @param  string  $label
	 *
	 * @return string
	 */
	public function label( $label, WC_Coupon $coupon ) {
		$code = wc_format_coupon_code( $coupon->get_code() );

		if ( $this->wanted && $this->wanted->getCode() === $code ) {
			return esc_html( $this->wanted->getLabel() );
		}

		return $label;
	}

	/**
	 * An automatic discount has no "[Remove]" link.
	 *
	 * @param  string  $html
	 *
	 * @return string
	 */
	public function removeRemoveLink( $html, WC_Coupon $coupon ) {
		if ( ! $this->isOurs( wc_format_coupon_code( $coupon->get_code() ), $this->rememberedCodes() ) ) {
			return $html;
		}

		return (string) preg_replace( '#<a [^>]*class="[^"]*woocommerce-remove-coupon[^"]*"[^>]*>.*?</a>#s', '', $html );
	}

	/**
	 * No "Coupon applied", "removed" or "does not exist" notices for an automatic discount.
	 *
	 * @param  string  $message
	 * @param  int  $messageCode
	 * @param  mixed  $coupon
	 *
	 * @return string
	 */
	public function silence( $message, $messageCode, $coupon ) {
		if ( $coupon instanceof WC_Coupon && $this->isOurs( wc_format_coupon_code( $coupon->get_code() ), $this->rememberedCodes() ) ) {
			return '';
		}

		return $message;
	}

	/**
	 * Whether a code belongs to an order rule (any published one), or was applied by this class earlier in
	 * the session (a rule since unpublished or renamed).
	 *
	 * @param  string[]  $remembered
	 */
	public function isOurs( string $code, array $remembered = array() ): bool {
		return in_array( $code, $remembered, true ) || null !== $this->ruleByCode( $code );
	}

	protected function ruleByCode( string $code ): ?GlobalPricingRule {
		if ( null === $this->codes ) {
			$this->codes = array();

			$rules = $this->rulesProvider ? call_user_func( $this->rulesProvider ) : GlobalTieredPricingCPT::getGlobalRules( false, false );

			foreach ( (array) $rules as $rule ) {
				if ( $rule instanceof GlobalPricingRule && $rule->isOrderRule() ) {
					$this->codes[ wc_format_coupon_code( OrderDiscount::codeFor( $rule ) ) ] = $rule;
				}
			}
		}

		return $this->codes[ $code ] ?? null;
	}

	/**
	 * The codes this class applied earlier in the session.
	 *
	 * @return string[]
	 */
	protected function rememberedCodes(): array {
		$session = function_exists( 'WC' ) && WC()->session ? WC()->session : null;

		return $session ? array_filter( (array) $session->get( self::SESSION_KEY, array() ) ) : array();
	}

	protected function remember( string $code ) {
		$session = function_exists( 'WC' ) && WC()->session ? WC()->session : null;

		if ( ! $session ) {
			return;
		}

		$codes = '' === $code ? array() : array( $code );

		if ( array_values( $this->rememberedCodes() ) !== $codes ) {
			$session->set( self::SESSION_KEY, $codes );
		}
	}
}
