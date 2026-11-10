<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

/**
 * The order discount a cart has earned: the rule, the tier it reached, what the rule counted and the lines
 * it applies to. Knows how it reaches WooCommerce (as a virtual coupon) and how it is labelled.
 */
class OrderDiscount {

	/**
	 * The rule that gives the discount.
	 *
	 * @var GlobalPricingRule
	 */
	protected $rule;

	/**
	 * The tier the cart reached, null when the cart only earns free shipping.
	 *
	 * @var array{from: float, discount: float}|null
	 */
	protected $tier;

	/**
	 * Whether the cart ships free.
	 *
	 * @var bool
	 */
	protected $freeShipping;

	/**
	 * The counted subtotal.
	 *
	 * @var float
	 */
	protected $subtotal;

	/**
	 * The counted number of items.
	 *
	 * @var int
	 */
	protected $quantity;

	/**
	 * The product IDs of the discounted lines when the rule covers only its matching products, null for
	 * the whole cart.
	 *
	 * @var int[]|null
	 */
	protected $productIds;

	public function __construct( GlobalPricingRule $rule, ?array $tier, float $subtotal, ?array $productIds = null, int $quantity = 0, bool $freeShipping = false ) {
		$this->rule         = $rule;
		$this->tier         = $tier;
		$this->subtotal     = $subtotal;
		$this->productIds   = $productIds;
		$this->quantity     = $quantity;
		$this->freeShipping = $freeShipping;
	}

	public function getRule(): GlobalPricingRule {
		return $this->rule;
	}

	public function getTier(): ?array {
		return $this->tier;
	}

	/**
	 * Whether a discount tier is reached (the cart may only earn free shipping).
	 */
	public function hasTier(): bool {
		return is_array( $this->tier );
	}

	public function getThreshold(): float {
		return $this->tier ? (float) $this->tier['from'] : 0.0;
	}

	public function getDiscount(): float {
		return $this->tier ? (float) $this->tier['discount'] : 0.0;
	}

	public function hasFreeShipping(): bool {
		return $this->freeShipping;
	}

	public function getSubtotal(): float {
		return $this->subtotal;
	}

	public function getQuantity(): int {
		return $this->quantity;
	}

	public function getProductIds(): ?array {
		return $this->productIds;
	}

	public function isPercentage(): bool {
		return 'percentage' === $this->rule->getOrderDiscountType();
	}

	public function getCode(): string {
		return self::codeFor( $this->rule );
	}

	/**
	 * The coupon code of a rule: its title as a slug, with the rule id appended when another order rule has
	 * the same title, or an id-based code when the title is empty or a real coupon owns that code.
	 */
	public static function codeFor( GlobalPricingRule $rule ): string {
		$code = sanitize_title( $rule->getTitle() );

		if ( '' === $code || wc_get_coupon_id_by_code( $code ) ) {
			$code = 'order-discount-' . $rule->getId();
		} elseif ( self::titleShared( $rule, $code ) ) {
			$code .= '-' . $rule->getId();
		}

		return (string) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/coupon_code', $code, $rule );
	}

	/**
	 * Where the sibling rules come from (tests hand in a list); the published rules otherwise.
	 *
	 * @var callable|null
	 */
	protected static $rulesProvider = null;

	public static function useRulesProvider( ?callable $provider ) {
		self::$rulesProvider = $provider;
	}

	/**
	 * Whether another published order rule slugs to the same code.
	 */
	protected static function titleShared( GlobalPricingRule $rule, string $code ): bool {
		$rules = self::$rulesProvider ? call_user_func( self::$rulesProvider ) : GlobalTieredPricingCPT::getGlobalRules( false, false );

		foreach ( (array) $rules as $other ) {
			if ( $other instanceof GlobalPricingRule && $other->isOrderRule() && $other->getId() !== $rule->getId() && sanitize_title( $other->getTitle() ) === $code ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The default label of the discount line when the rule has none.
	 */
	public static function defaultLabel(): string {
		return __( 'Order discount {discount}', 'tier-pricing-table' );
	}

	/**
	 * "10%" or the amount as a plain price.
	 */
	public static function formatDiscount( float $discount, bool $percentage ): string {
		if ( $percentage ) {
			return wc_format_decimal( $discount, false, true ) . '%';
		}

		return self::plainPrice( $discount );
	}

	/**
	 * A tier threshold as text: a plain price, or a number of items for a rule counting items.
	 */
	public static function formatThreshold( float $threshold, bool $items ): string {
		if ( $items ) {
			/* translators: %s: number of items */
			return sprintf( _n( '%s item', '%s items', (int) ceil( $threshold ), 'tier-pricing-table' ), number_format_i18n( (int) ceil( $threshold ) ) );
		}

		return self::plainPrice( $threshold );
	}

	/**
	 * A price as plain text: no markup, no entities.
	 */
	public static function plainPrice( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The discount line's label with its placeholders filled in, as plain text.
	 */
	public function getLabel(): string {
		if ( ! $this->hasTier() ) {
			// free shipping only: WooCommerce prints "Free shipping coupon" where the amount would be
			return (string) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/label', __( 'Free shipping', 'tier-pricing-table' ), $this );
		}

		$label = $this->rule->getOrderLabel();
		$label = '' !== $label ? $label : self::defaultLabel();

		$label = str_replace(
			array( '{discount}', '{threshold}' ),
			array( self::formatDiscount( $this->getDiscount(), $this->isPercentage() ), self::formatThreshold( $this->getThreshold(), $this->rule->isOrderBasisQuantity() ) ),
			$label
		);

		return (string) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/label', $label, $this );
	}

	/**
	 * What WooCommerce needs to build the coupon: a percent or fixed-cart coupon limited to the discounted
	 * lines, usable next to other coupons.
	 */
	public function getCouponProps(): array {
		return self::couponPropsFor( $this->rule, $this->getDiscount(), $this->productIds, $this->freeShipping );
	}

	public static function couponPropsFor( GlobalPricingRule $rule, float $amount, ?array $productIds = null, bool $freeShipping = false ): array {
		$props = array(
			'code'               => self::codeFor( $rule ),
			'description'        => $rule->getTitle(),
			'discount_type'      => 'percentage' === $rule->getOrderDiscountType() ? 'percent' : 'fixed_cart',
			'amount'             => $amount,
			'product_ids'        => $productIds ? array_values( array_map( 'intval', $productIds ) ) : array(),
			'individual_use'     => false,
			'free_shipping'      => $freeShipping,
			'exclude_sale_items' => false,
			'usage_limit'        => 0,
			'virtual'            => true,
		);

		return (array) apply_filters( 'tiered_pricing_table/global_pricing/order_discount/coupon_props', $props, $rule );
	}
}
