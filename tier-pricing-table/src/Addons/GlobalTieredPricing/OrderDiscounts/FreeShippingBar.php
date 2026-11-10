<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
use WC_Cart;
use WP_User;

/**
 * The free shipping bar: how far the cart is from the free shipping of the order rule it is under. It follows
 * the rule in play (the one that also gives the discount), shows where the Order rules settings say (classic
 * cart totals, classic checkout, mini cart, product page), as a shortcode and as a block, and the standalone
 * bars refresh with the cart fragments when something is added to the cart.
 */
class FreeShippingBar {

	use ServiceContainerTrait;

	const SHORTCODE = 'tiered_pricing_free_shipping_bar';

	const BLOCK = 'tiered-pricing-table/free-shipping-bar';

	/**
	 * The wrapper of the standalone bars (product page, shortcode, block): the cart fragments replace it.
	 */
	const LIVE_CLASS = 'tpt-free-shipping-bar-live';

	/**
	 * Finds the rule the cart is under.
	 *
	 * @var OrderDiscountResolver
	 */
	protected $resolver;

	public function __construct( ?OrderDiscountResolver $resolver = null ) {
		$this->resolver = $resolver ? $resolver : new OrderDiscountResolver();

		// after the next tier message (priority 10) in the same places
		add_action( 'woocommerce_cart_totals_after_order_total', array( $this, 'renderCartRow' ), 11 );
		add_action( 'woocommerce_review_order_after_order_total', array( $this, 'renderCheckoutRow' ), 11 );
		add_action( 'woocommerce_widget_shopping_cart_before_buttons', array( $this, 'renderMiniCart' ), 11 );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'renderProduct' ) );

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'addFragment' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'renderShortcode' ) );

		if ( did_action( 'init' ) ) {
			$this->registerBlock();
		} else {
			add_action( 'init', array( $this, 'registerBlock' ) );
		}
	}

	/* --- the progress ---------------------------------------------------------------------------- */

	/**
	 * The progress of the current cart (or the given one) towards free shipping: the rule, the mode, the
	 * threshold, the counted value, what remains, whether it is reached and the percentage. Null when no
	 * order rule with the bar covers the customer, or when the rule the cart is under has no bar.
	 */
	public function progress( ?WC_Cart $cart = null, ?WP_User $user = null ): ?array {
		$user  = $user ? $user : TierPricingTablePlugin::getCurrentUser();
		$cart  = $cart ? $cart : ( function_exists( 'WC' ) && WC()->cart instanceof WC_Cart ? WC()->cart : null );
		$lines = $cart ? OrderDiscountResolver::linesOf( $cart ) : array();
		$rule  = $lines ? $this->resolver->findRule( $lines, $user ) : $this->ruleForEmptyCart( $user );

		if ( ! $rule || ! $rule->hasOrderShippingBar() ) {
			return null;
		}

		$progress = self::progressFor( $rule, OrderDiscountResolver::counted( $rule, $lines ) );

		return apply_filters( 'tiered_pricing_table/global_pricing/order_discount/shipping_bar', $progress, $cart, $user );
	}

	/**
	 * With nothing in the cart, the first order rule in priority order that covers the customer and
	 * carries the bar: the bar then starts at zero.
	 */
	protected function ruleForEmptyCart( WP_User $user ): ?GlobalPricingRule {
		foreach ( $this->resolver->getRules() as $rule ) {
			if ( $rule->hasOrderShippingBar() && $rule->matchesUser( $user ) ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * The calculation itself: the counted value against the rule's threshold.
	 *
	 * @param  array  $counted  As OrderDiscountResolver::counted() returns it.
	 *
	 * @return array{rule: GlobalPricingRule, mode: string, items: bool, threshold: float, value: float, remaining: float|int, reached: bool, percent: int}
	 */
	public static function progressFor( GlobalPricingRule $rule, array $counted ): array {
		$mode      = $rule->getOrderFreeShipping();
		$items     = 'quantity' === $mode;
		$threshold = 'always' === $mode ? 0.0 : (float) $rule->getOrderFreeShippingFrom();
		$value     = (float) ( $items ? $counted['quantity'] : $counted['amount'] );
		$reached   = $rule->earnsFreeShipping( (float) $counted['amount'], (int) $counted['quantity'] );

		if ( $reached || $threshold <= 0 ) {
			$remaining = 0;
		} else {
			$remaining = $items ? (int) ceil( $threshold - $value ) : round( $threshold - $value, wc_get_price_decimals() );
		}

		$percent = $reached ? 100 : ( $threshold > 0 ? (int) min( 99, floor( $value / $threshold * 100 ) ) : 0 );

		return array(
			'rule'      => $rule,
			'mode'      => $mode,
			'items'     => $items,
			'threshold' => $threshold,
			'value'     => $value,
			'remaining' => $remaining,
			'reached'   => $reached,
			'percent'   => $percent,
		);
	}

	/**
	 * The message of a progress: the rule's own template or the store default, with {remaining} and
	 * {threshold} as prices or item counts and {progress} as a percentage. Safe HTML.
	 */
	public static function messageFor( array $progress ): string {
		$rule = $progress['rule'];

		if ( $progress['reached'] ) {
			$template = '' !== $rule->getOrderShippingBarReached() ? $rule->getOrderShippingBarReached() : OrderDiscountSettings::getBarReachedTemplate();
		} else {
			$template = '' !== $rule->getOrderShippingBarMessage() ? $rule->getOrderShippingBarMessage() : OrderDiscountSettings::getBarTemplate();
		}

		$format = function ( $value ) use ( $progress ) {
			return $progress['items'] ? esc_html( OrderDiscount::formatThreshold( (float) $value, true ) ) : wc_price( $value );
		};

		return str_replace(
			array( '{remaining}', '{threshold}', '{progress}' ),
			array( $format( $progress['remaining'] ), $format( $progress['threshold'] ), esc_html( number_format_i18n( $progress['percent'] ) . '%' ) ),
			$template
		);
	}

	/* --- the markup ------------------------------------------------------------------------------ */

	/**
	 * The bar for a context (cart, checkout, minicart, product, shortcode, block), or an empty string when
	 * there is nothing to show.
	 */
	public function render( string $context = 'standalone' ): string {
		$progress = $this->progress();

		if ( ! $progress ) {
			return '';
		}

		ob_start();

		$this->getContainer()->getFileManager()->includeTemplate( 'addons/global-rules/free-shipping-bar.php', array(
			'context'      => $context,
			'message'      => self::messageFor( $progress ),
			'percent'      => (int) $progress['percent'],
			'reached'      => (bool) $progress['reached'],
			'color'        => $progress['reached'] ? OrderDiscountSettings::getBarReachedColor() : OrderDiscountSettings::getBarColor(),
			'height'       => OrderDiscountSettings::getBarHeight(),
			'rule'         => $progress['rule'],
		) );

		return (string) ob_get_clean();
	}

	/**
	 * A standalone bar in its live wrapper: the wrapper stays (empty) when there is nothing to show, so the
	 * cart fragments can fill it later.
	 */
	public function renderLive( string $context = 'standalone', array $attributes = array() ): string {
		$this->enqueueFragmentsScript();

		$attributes['class'] = trim( self::LIVE_CLASS . ' ' . ( $attributes['class'] ?? '' ) );
		$html                = '<div';

		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . sanitize_key( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return $html . '>' . $this->render( $context ) . '</div>';
	}

	/**
	 * WooCommerce's cart fragments script refreshes the standalone bars after an add to cart and after the
	 * cart page updates its quantities; WooCommerce loads it only where a mini cart widget is, so a page
	 * with a standalone bar asks for it.
	 */
	protected function enqueueFragmentsScript() {
		static $asked = false;

		if ( $asked || ! function_exists( 'wp_script_is' ) ) {
			return;
		}

		$asked   = true;
		$enqueue = function () {
			if ( wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
				wp_enqueue_script( 'wc-cart-fragments' );
			}
		};

		if ( did_action( 'wp_enqueue_scripts' ) ) {
			$enqueue();
		} else {
			// block themes render the content before the head, where WooCommerce registers its scripts
			add_action( 'wp_enqueue_scripts', $enqueue, 20 );
		}
	}

	protected function isPlaced( string $placement ): bool {
		return in_array( $placement, OrderDiscountSettings::getBarPlacements(), true );
	}

	/**
	 * A row under the order total of the classic cart totals.
	 */
	public function renderCartRow() {
		$this->renderRow( OrderDiscountSettings::BAR_PLACEMENT_CART );
	}

	/**
	 * A row under the order total of the classic checkout's order review.
	 */
	public function renderCheckoutRow() {
		$this->renderRow( OrderDiscountSettings::BAR_PLACEMENT_CHECKOUT );
	}

	protected function renderRow( string $placement ) {
		if ( ! $this->isPlaced( $placement ) ) {
			return;
		}

		$bar = $this->render( $placement );

		if ( '' !== $bar ) {
			$this->getContainer()->getFileManager()->includeTemplate( 'addons/global-rules/free-shipping-bar-row.php', array( 'bar' => $bar ) );
		}
	}

	/**
	 * Above the mini cart's buttons; the widget's own fragment refreshes it.
	 */
	public function renderMiniCart() {
		if ( $this->isPlaced( OrderDiscountSettings::BAR_PLACEMENT_MINI_CART ) ) {
			echo $this->render( 'minicart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template
		}
	}

	/**
	 * Above the add to cart form of the product page, refreshed by the cart fragments.
	 */
	public function renderProduct() {
		if ( $this->isPlaced( OrderDiscountSettings::BAR_PLACEMENT_PRODUCT ) ) {
			echo $this->renderLive( 'product' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the template
		}
	}

	/**
	 * [tiered_pricing_free_shipping_bar]
	 */
	public function renderShortcode(): string {
		return $this->renderLive( 'shortcode' );
	}

	/**
	 * The block: nothing in the editor's preview when there is nothing to show (its placeholder says so),
	 * the live wrapper on the storefront.
	 */
	public function renderBlock(): string {
		$bar = $this->render( 'block' );

		if ( '' === $bar && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '';
		}

		$this->enqueueFragmentsScript();

		$attributes = function_exists( 'get_block_wrapper_attributes' ) ? get_block_wrapper_attributes( array( 'class' => self::LIVE_CLASS ) ) : 'class="' . esc_attr( self::LIVE_CLASS ) . '"';

		return '<div ' . $attributes . '>' . $bar . '</div>';
	}

	public function registerBlock() {
		if ( ! function_exists( 'register_block_type' ) || ( class_exists( 'WP_Block_Type_Registry' ) && \WP_Block_Type_Registry::get_instance()->is_registered( self::BLOCK ) ) ) {
			return;
		}

		register_block_type( dirname( __DIR__, 3 ) . '/Blocks/free-shipping-bar', array(
			'render_callback' => array( $this, 'renderBlock' ),
		) );
	}

	/**
	 * The standalone bars follow the cart: WooCommerce replaces every live wrapper with this fragment when
	 * something is added to the cart.
	 *
	 * @param  mixed  $fragments
	 */
	public function addFragment( $fragments ) {
		$fragments = is_array( $fragments ) ? $fragments : array();

		$fragments[ '.' . self::LIVE_CLASS ] = $this->renderLive();

		return $fragments;
	}
}
