<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRulesRepository;
use TierPricingTable\TierPricingTablePlugin;
use WC_Cart;
use WC_Product;
use WP_User;

/**
 * Which order rule a cart is under, which of its tiers the cart has reached, and which minimums the cart
 * misses. A rule counts the lines after tiered prices, before shipping, with or without tax per the
 * settings: their amount and their item count. The discount comes from one rule (the highest-priority one
 * matching the customer with a counted line); the minimums of every matching rule must be met.
 */
class OrderDiscountResolver {

	/**
	 * Rules handed in (tests), otherwise the published order rules.
	 *
	 * @var GlobalPricingRule[]|null
	 */
	protected $rules;

	public function __construct( ?array $rules = null ) {
		$this->rules = $rules;
	}

	/**
	 * The order rules to consider, in priority order.
	 *
	 * @return GlobalPricingRule[]
	 */
	public function getRules(): array {
		return is_array( $this->rules ) ? $this->rules : GlobalPricingRulesRepository::getInstance()->getOrderDiscountRules();
	}

	/**
	 * The discount the cart has earned, or null when no rule applies or no tier is reached.
	 */
	public function resolve( WC_Cart $cart, ?WP_User $user = null ): ?OrderDiscount {
		return $this->resolveLines( self::linesOf( $cart ), $user, $cart );
	}

	/**
	 * The same for lines built elsewhere (the Pricing test's cart).
	 *
	 * @param  array  $lines  As linesOf() returns them.
	 */
	public function resolveLines( array $lines, ?WP_User $user = null, ?WC_Cart $cart = null ): ?OrderDiscount {
		$user = $user ? $user : TierPricingTablePlugin::getCurrentUser();
		$rule = $this->findRule( $lines, $user );

		if ( ! $rule || ( empty( $rule->getOrderTiers() ) && ! $rule->hasOrderFreeShipping() ) ) {
			return null;
		}

		$counted      = self::counted( $rule, $lines );
		$value        = self::basisValue( $rule, $counted );
		$tier         = $rule->getOrderTierFor( $value );
		$freeShipping = $rule->earnsFreeShipping( (float) $counted['amount'], (int) $counted['quantity'] );

		if ( ! $tier && ! $freeShipping ) {
			return null;
		}

		$discount = new OrderDiscount( $rule, $tier, $counted['amount'], $rule->isOrderScopeMatching() ? $counted['product_ids'] : null, $counted['quantity'], $freeShipping );

		return apply_filters( 'tiered_pricing_table/global_pricing/order_discount/resolved', $discount, $cart, $user );
	}

	/**
	 * What the customer is missing for the next tier of the rule they are under: the rule, the tier, the
	 * counted value and the remaining amount or items. Null when no rule with tiers applies or the top
	 * tier is reached.
	 */
	public function nextTier( WC_Cart $cart, ?WP_User $user = null ): ?array {
		return $this->nextTierForLines( self::linesOf( $cart ), $user );
	}

	/**
	 * The same for lines built elsewhere.
	 *
	 * @param  array  $lines  As linesOf() returns them.
	 */
	public function nextTierForLines( array $lines, ?WP_User $user = null ): ?array {
		$user = $user ? $user : TierPricingTablePlugin::getCurrentUser();
		$rule = $this->findRule( $lines, $user );

		if ( ! $rule || empty( $rule->getOrderTiers() ) ) {
			return null;
		}

		$counted = self::counted( $rule, $lines );
		$value   = self::basisValue( $rule, $counted );
		$tier    = $rule->getNextOrderTierFor( $value );

		if ( ! $tier ) {
			return null;
		}

		return array(
			'rule'      => $rule,
			'tier'      => $tier,
			'basis'     => $rule->getOrderBasis(),
			'subtotal'  => $counted['amount'],
			'quantity'  => $counted['quantity'],
			'remaining' => $rule->isOrderBasisQuantity() ? (int) ceil( $tier['from'] - $value ) : round( $tier['from'] - $value, wc_get_price_decimals() ),
		);
	}

	/**
	 * The minimums the cart misses: one entry per unmet minimum of every order rule that matches the
	 * customer and counts a line of the cart (every rule, not only the one giving the discount).
	 *
	 * @return array<int, array{rule: GlobalPricingRule, type: string, minimum: float|int, actual: float|int}>
	 */
	public function missedMinimums( WC_Cart $cart, ?WP_User $user = null ): array {
		return $this->missedMinimumsForLines( self::linesOf( $cart ), $user );
	}

	/**
	 * The same for lines built elsewhere.
	 *
	 * @param  array  $lines  As linesOf() returns them.
	 */
	public function missedMinimumsForLines( array $lines, ?WP_User $user = null ): array {
		$user   = $user ? $user : TierPricingTablePlugin::getCurrentUser();
		$missed = array();

		if ( ! $lines ) {
			return $missed;
		}

		foreach ( $this->getRules() as $rule ) {
			$minimum = $rule->getEffectiveOrderMinimum();

			if ( ( $minimum['amount'] <= 0 && $minimum['quantity'] <= 0 ) || ! $rule->matchesUser( $user ) ) {
				continue;
			}

			$counted = self::counted( $rule, $lines );

			if ( empty( $counted['product_ids'] ) ) {
				continue;
			}

			if ( $minimum['amount'] > 0 && $counted['amount'] + 0.00001 < $minimum['amount'] ) {
				$missed[] = array( 'rule' => $rule, 'type' => 'amount', 'minimum' => (float) $minimum['amount'], 'actual' => $counted['amount'] );
			}

			if ( $minimum['quantity'] > 0 && $counted['quantity'] < $minimum['quantity'] ) {
				$missed[] = array( 'rule' => $rule, 'type' => 'quantity', 'minimum' => (int) $minimum['quantity'], 'actual' => $counted['quantity'] );
			}
		}

		return (array) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/missed_minimums', $missed, $lines, $user );
	}

	/**
	 * The rule the cart is under: the first one in priority order that matches the customer and counts at
	 * least one line of the cart.
	 *
	 * @param  array  $lines  As linesOf() returns them.
	 */
	public function findRule( array $lines, WP_User $user ): ?GlobalPricingRule {
		$found = null;

		foreach ( $this->getRules() as $rule ) {
			if ( ! $rule->matchesUser( $user ) ) {
				continue;
			}

			$counted = self::counted( $rule, $lines );

			if ( empty( $counted['product_ids'] ) ) {
				continue;
			}

			$found = $rule;
			break;
		}

		return apply_filters( 'tiered_pricing_table/global_pricing/order_discount/rule', $found, $lines, $user );
	}

	/**
	 * Whether the counted amounts include tax: per the setting, or as the cart displays its prices.
	 */
	public static function countsTax( ?WC_Cart $cart = null ): bool {
		$basis = OrderDiscountSettings::getSubtotalBasis();

		if ( OrderDiscountSettings::BASIS_DISPLAY === $basis ) {
			return $cart ? $cart->display_prices_including_tax() : 'incl' === get_option( 'woocommerce_tax_display_cart' );
		}

		return OrderDiscountSettings::BASIS_INCLUDING_TAX === $basis;
	}

	/**
	 * A counted line for a product, a quantity and the unit price the customer pays, with or without tax
	 * per the subtotal basis.
	 *
	 * @return array{key: string, product: WC_Product, quantity: int, amount: float}
	 */
	public static function lineFor( WC_Product $product, int $quantity, float $unitPrice, bool $includingTax, string $key = '' ): array {
		$args   = array( 'qty' => $quantity, 'price' => $unitPrice );
		$amount = $includingTax ? wc_get_price_including_tax( $product, $args ) : wc_get_price_excluding_tax( $product, $args );

		return array(
			'key'      => '' !== $key ? $key : (string) $product->get_id(),
			'product'  => $product,
			'quantity' => $quantity,
			'amount'   => (float) $amount,
		);
	}

	/**
	 * The cart as counted lines: the product, the quantity and the amount the customer pays for the line
	 * (the current price, so tiered prices are in, with or without tax per the subtotal basis setting).
	 *
	 * @return array<int, array{key: string, product: WC_Product, quantity: int, amount: float}>
	 */
	public static function linesOf( WC_Cart $cart ): array {
		$includingTax = self::countsTax( $cart );
		$lines        = array();

		foreach ( $cart->get_cart() as $key => $item ) {
			$product = $item['data'] ?? null;

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$quantity = (int) ( $item['quantity'] ?? 0 );
			$price    = (float) $product->get_price();

			if ( $quantity <= 0 || $price <= 0 ) {
				continue;
			}

			$lines[] = self::lineFor( $product, $quantity, $price, $includingTax, (string) $key );
		}

		return (array) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/lines', $lines, $cart );
	}

	/**
	 * What a rule counts in the cart (the lines matching its product conditions; every line without any):
	 * the amount, the number of items and the product IDs of the counted lines.
	 *
	 * @return array{amount: float, quantity: int, product_ids: int[]}
	 */
	public static function counted( GlobalPricingRule $rule, array $lines ): array {
		$amount     = 0.0;
		$quantity   = 0;
		$productIds = array();

		foreach ( $lines as $line ) {
			if ( ! $rule->matchesProduct( $line['product'] ) ) {
				continue;
			}

			$amount      += (float) $line['amount'];
			$quantity    += (int) $line['quantity'];
			$productIds[] = (int) $line['product']->get_id();
		}

		return array(
			'amount'      => round( $amount, wc_get_price_decimals() ),
			'quantity'    => $quantity,
			'product_ids' => array_values( array_unique( $productIds ) ),
		);
	}

	/**
	 * The counted value the rule's tiers compare with: the amount or the item count.
	 */
	public static function basisValue( GlobalPricingRule $rule, array $counted ): float {
		return (float) ( $rule->isOrderBasisQuantity() ? $counted['quantity'] : $counted['amount'] );
	}
}
