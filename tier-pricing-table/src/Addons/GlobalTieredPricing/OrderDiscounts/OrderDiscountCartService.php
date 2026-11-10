<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSettings;
use TierPricingTable\Core\ServiceContainerTrait;
use WC_Cart;
use WP_Error;

/**
 * Puts the order rules to work in the cart: resolves the discount after the tiered prices are set and
 * applies it as a virtual coupon, blocks the checkout while a minimum order is not met (classic pages and
 * blocks), and shows what is missing for the next tier.
 */
class OrderDiscountCartService {

	use ServiceContainerTrait;

	/**
	 * Finds the rule and the tier a cart is under.
	 *
	 * @var OrderDiscountResolver
	 */
	protected $resolver;

	/**
	 * Applies the discount as a virtual coupon.
	 *
	 * @var OrderDiscountCoupon
	 */
	protected $coupon;

	public function __construct( ?OrderDiscountResolver $resolver = null, ?OrderDiscountCoupon $coupon = null ) {
		$this->resolver = $resolver ? $resolver : new OrderDiscountResolver();
		$this->coupon   = $coupon ? $coupon : new OrderDiscountCoupon();

		// after the tiered prices (priority 99999), so the tiers count what the customer actually pays
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'applyToCart' ), 100000 );

		add_action( 'woocommerce_check_cart_items', array( $this, 'checkMinimums' ) );
		add_action( 'woocommerce_store_api_cart_errors', array( $this, 'addStoreApiErrors' ), 10, 2 );

		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'renderNudgeRow' ) );
		add_action( 'woocommerce_review_order_after_order_total', array( $this, 'renderNudgeRow' ) );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'renderNudgeNotice' ) );
	}

	/**
	 * Resolve and apply the discount once the tiered prices of the lines are set.
	 *
	 * @param  mixed  $cart
	 */
	public function applyToCart( $cart ) {
		if ( ! $cart instanceof WC_Cart || ( is_admin() && ! wp_doing_ajax() ) ) {
			return;
		}

		$this->coupon->sync( $cart, $this->resolver->resolve( $cart ) );
	}

	public function getCoupon(): OrderDiscountCoupon {
		return $this->coupon;
	}

	/* --- minimum order --------------------------------------------------------------------------- */

	/**
	 * The messages for the minimums the cart misses, keyed by rule and type; empty when the cart passes.
	 *
	 * @param  mixed  $cart
	 *
	 * @return string[]
	 */
	public function getMinimumErrors( $cart ): array {
		if ( ! $cart instanceof WC_Cart || $cart->is_empty() ) {
			return array();
		}

		$errors = array();

		foreach ( $this->resolver->missedMinimums( $cart ) as $missed ) {
			$key = $missed['rule']->getId() . '_' . $missed['type'];

			if ( 'amount' === $missed['type'] ) {
				$errors[ $key ] = str_replace(
					array( '{minimum}', '{subtotal}' ),
					array( OrderDiscount::plainPrice( (float) $missed['minimum'] ), OrderDiscount::plainPrice( (float) $missed['actual'] ) ),
					CartRulesSettings::getAmountMessage()
				);
			} else {
				$errors[ $key ] = str_replace(
					array( '{minimum}', '{count}' ),
					array( number_format_i18n( (int) $missed['minimum'] ), number_format_i18n( (int) $missed['actual'] ) ),
					CartRulesSettings::getQuantityMessage()
				);
			}
		}

		return array_unique( $errors );
	}

	public function checkMinimums() {
		foreach ( $this->getMinimumErrors( WC()->cart ) as $message ) {
			if ( ! wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
		}
	}

	/**
	 * The same messages for the Cart and Checkout blocks.
	 *
	 * @param  mixed  $errors
	 * @param  mixed  $cart
	 */
	public function addStoreApiErrors( $errors, $cart ) {
		if ( ! $errors instanceof WP_Error ) {
			return;
		}

		foreach ( $this->getMinimumErrors( $cart ) as $key => $message ) {
			$errors->add( 'tpt_order_minimum_' . $key, $message );
		}
	}

	/* --- the nudge ------------------------------------------------------------------------------- */

	/**
	 * The "add X more" message for the current cart, or an empty string when there is nothing to say.
	 */
	public function getNudgeMessage(): string {
		$template = trim( OrderDiscountSettings::getNudgeTemplate() );
		$cart     = function_exists( 'WC' ) ? WC()->cart : null;

		if ( '' === $template || ! $cart instanceof WC_Cart ) {
			return '';
		}

		$next = $this->resolver->nextTier( $cart );

		if ( ! $next || $next['remaining'] <= 0 ) {
			return '';
		}

		$rule       = $next['rule'];
		$percentage = 'percentage' === $rule->getOrderDiscountType();
		$items      = $rule->isOrderBasisQuantity();

		$message = str_replace(
			array( '{remaining}', '{discount}', '{threshold}' ),
			array(
				$items ? esc_html( OrderDiscount::formatThreshold( (float) $next['remaining'], true ) ) : wc_price( $next['remaining'] ),
				OrderDiscount::formatDiscount( (float) $next['tier']['discount'], $percentage ),
				$items ? esc_html( OrderDiscount::formatThreshold( (float) $next['tier']['from'], true ) ) : wc_price( $next['tier']['from'] ),
			),
			$template
		);

		return (string) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/nudge', $message, $next, $cart );
	}

	/**
	 * A row under the order total of the cart totals and the checkout order review.
	 */
	public function renderNudgeRow() {
		$message = $this->getNudgeMessage();

		if ( '' !== $message ) {
			$this->getContainer()->getFileManager()->includeTemplate( 'addons/global-rules/order-discount-nudge-row.php', array( 'message' => $message ) );
		}
	}

	/**
	 * A line above the mini cart's buttons.
	 */
	public function renderNudgeNotice() {
		$message = $this->getNudgeMessage();

		if ( '' !== $message ) {
			$this->getContainer()->getFileManager()->includeTemplate( 'addons/global-rules/order-discount-nudge-notice.php', array( 'message' => $message ) );
		}
	}
}
