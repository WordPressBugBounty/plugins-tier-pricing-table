<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\PriceManager;

use TierPricingTable\Addons\GlobalTieredPricing\PricingRule\RuleSettings;
use TierPricingTable\Forms\Form;
use DateTime;
use DateTimeZone;
use Exception;
use WC_Product;
use WP_Term;
use WP_User;

class GlobalPricingRule {
	
	/**
	 * Rule ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Is suspended
	 *
	 * @var bool
	 */
	public $isSuspended = false;
	
	/**
	 * Regular pricing type
	 *
	 * @var string
	 */
	public $pricingType;
	
	/**
	 * Regular price
	 *
	 * @var ?float
	 */
	public $regularPrice;
	
	/**
	 * Sale price
	 *
	 * @var ?float
	 */
	public $salePrice;
	
	/**
	 * Percentage Discount
	 *
	 * @var ?float
	 */
	public $discount;
	
	/**
	 * Percentage Discount
	 *
	 * @var string
	 */
	public $discountType = 'sale_price';
	
	/**
	 * Applying type
	 *
	 * @var string
	 */
	public $applyingType;
	
	/**
	 * Tax status
	 *
	 * @var string
	 */
	public $taxStatus = '';
	
	/**
	 * Tax class
	 *
	 * @var string
	 */
	public $taxClass = '';
	
	/**
	 * Tiered Pricing type
	 *
	 * @var string
	 */
	public $tieredPricingType;
	
	/**
	 * Percentage Tiered Pricing Rules
	 *
	 * @var array
	 */
	public $percentageTieredPricingRules = array();
	
	/**
	 * Fixed Tiered Pricing Rules
	 *
	 * @var array
	 */
	public $fixedTieredPricingRules = array();
	
	/**
	 * Amount-off tiers: quantity => amount taken off the product price per unit.
	 *
	 * @var array
	 */
	public $amountTieredPricingRules = array();
	
	const KIND_PRODUCT = 'product';
	
	const KIND_ORDER = 'order';
	
	/**
	 * What the rule changes: product prices (tiers and base prices per product) or the order total (a
	 * discount on the cart once its subtotal reaches a tier).
	 *
	 * @var string
	 */
	public $kind = self::KIND_PRODUCT;
	
	/**
	 * Order-total tiers sorted by threshold: each a "from" subtotal and the discount from it.
	 *
	 * @var array<int, array{from: float, discount: float}>
	 */
	public $orderTiers = array();
	
	/**
	 * How the order discount is taken: a "percentage" of the discounted lines or a "fixed" amount off.
	 *
	 * @var string
	 */
	public $orderDiscountType = 'fixed';
	
	/**
	 * The discount line's label in the cart; placeholders {discount} and {threshold}.
	 *
	 * @var string
	 */
	public $orderLabel = '';
	
	/**
	 * What the order tiers compare with: the "amount" of the counted lines or their item "quantity".
	 *
	 * @var string
	 */
	public $orderBasis = 'amount';
	
	/**
	 * When the order ships free: "none", "always" (every order under the rule), from a counted "amount"
	 * or from a counted "quantity" of items.
	 *
	 * @var string
	 */
	public $orderFreeShipping = 'none';
	
	/**
	 * The subtotal or number of items from which the order ships free (the "amount" and "quantity" modes).
	 *
	 * @var float|null
	 */
	public $orderFreeShippingFrom = null;
	
	/**
	 * Whether the storefront shows the free shipping bar (the progress towards the threshold) for this rule.
	 *
	 * @var bool
	 */
	public $orderShippingBar = false;
	
	/**
	 * The bar's message until the threshold is reached; empty uses the store default. Placeholders
	 * {remaining}, {threshold} and {progress}.
	 *
	 * @var string
	 */
	public $orderShippingBarMessage = '';
	
	/**
	 * The bar's message once free shipping is reached; empty uses the store default. Placeholder {threshold}.
	 *
	 * @var string
	 */
	public $orderShippingBarReached = '';
	
	/**
	 * The minimum amount of the counted lines before the order can be placed, null for none.
	 *
	 * @var float|null
	 */
	public $orderMinimumAmount = null;
	
	/**
	 * The minimum number of items in the counted lines before the order can be placed, null for none.
	 *
	 * @var int|null
	 */
	public $orderMinimumQuantity = null;
	
	/**
	 * Included categories
	 *
	 * @var array
	 */
	public $includedProductCategories = array();
	
	/**
	 * Excluded categories
	 *
	 * @var array
	 */
	public $excludedProductCategories = array();
	
	/**
	 * Whether the included and excluded categories cover their subcategories too.
	 *
	 * @var bool
	 */
	public $includeSubcategories = false;
	
	/**
	 * Included tags
	 *
	 * @var array
	 */
	public $includedProductTags = array();
	
	/**
	 * Excluded tags
	 *
	 * @var array
	 */
	public $excludedProductTags = array();
	
	/**
	 * Included brands
	 *
	 * @var array
	 */
	public $includedProductBrands = array();
	
	/**
	 * Excluded brands
	 *
	 * @var array
	 */
	public $excludedProductBrands = array();
	
	/**
	 * Included attribute values: term ids of global product attributes (pa_*). They narrow the rule to the
	 * products and variations with these values, on top of the products, categories, tags and brands.
	 *
	 * @var array
	 */
	public $includedAttributes = array();
	
	/**
	 * Excluded attribute values: term ids of global product attributes (pa_*).
	 *
	 * @var array
	 */
	public $excludedAttributes = array();
	
	/**
	 * The attribute values resolved to slugs, by term id list and language: see self::attributeSlugs().
	 *
	 * @var array<string, array<string, array<int, string>>>
	 */
	protected $attributeSlugs = array();
	
	/**
	 * Included products
	 *
	 * @var array
	 */
	public $includedProducts = array();
	
	/**
	 * Excluded products
	 *
	 * @var array
	 */
	public $excludedProducts = array();
	
	/**
	 * Included product roles
	 *
	 * @var array
	 */
	public $includedUsersRole = array();
	
	/**
	 * Excluded product roles
	 *
	 * @var array
	 */
	public $excludedUsersRole = array();
	
	/**
	 * Included users
	 *
	 * @var array
	 */
	public $includedUsers = array();
	
	/**
	 * Excluded users
	 *
	 * @var array
	 */
	public $excludedUsers = array();
	
	/**
	 * Product minimum purchase quantity
	 *
	 * @var int|null
	 */
	public $minimum;
	
	/**
	 * Mix and match minimum quantity
	 *
	 * @var bool|null
	 */
	public $mixAndMatchMinQuantity = null;
	
	public $priorityOptions;
	
	const DEFAULT_PRIORITY = 10;
	
	/**
	 * The pseudo role of a visitor who is not logged in, selectable in the role lists. The same key the
	 * wholesale registration add-on uses (NonLoggedInUsers\Roles::GUEST).
	 */
	const GUEST_ROLE = 'guest';
	
	/**
	 * The roles the matcher reads for a user: a visitor who is not logged in has the guest pseudo role.
	 */
	public static function rolesOf( WP_User $user ): array {
		return $user->ID ? (array) $user->roles : array( self::GUEST_ROLE );
	}
	
	/**
	 * Whether a role key may be stored in the rule's role lists: an existing WordPress role or the guest pseudo role.
	 */
	public static function isSelectableRole( $role ): bool {
		return self::GUEST_ROLE === $role || ( function_exists( 'wp_roles' ) && array_key_exists( $role, wp_roles()->roles ) );
	}
	
	/**
	 * Order among global rules: the lowest number is applied first; equal numbers, the newest rule first.
	 *
	 * @var int
	 */
	public $priority = self::DEFAULT_PRIORITY;
	
	/**
	 * First day the rule applies (Y-m-d in the site's timezone, inclusive), null = no start.
	 *
	 * @var ?string
	 */
	public $startDate = null;
	
	/**
	 * Last day the rule applies (Y-m-d in the site's timezone, inclusive), null = no end.
	 *
	 * @var ?string
	 */
	public $endDate = null;
	
	/**
	 * post_date_gmt of the rule: orders rules with the same priority (newest first).
	 *
	 * @var string
	 */
	public $createdAt = '';
	
	/**
	 * Array with custom data from 3rd-party addons
	 *
	 * @var array
	 */
	public $data = array();
	
	public function getId(): int {
		return $this->id;
	}
	
	public function setId( int $id ) {
		$this->id = $id;
	}
	
	public function getDiscount(): ?float {
		return $this->discount;
	}
	
	public function setDiscount( ?float $discount ) {
		$this->discount = $discount;
	}
	
	public function getDiscountType(): string {
		return $this->discountType;
	}
	
	public function setDiscountType( string $discountType ) {
		$this->discountType = in_array( $discountType,
			array( 'sale_price', 'regular_price' ) ) ? $discountType : 'sale_price';
	}
	
	public function getTieredPricingType(): string {
		return PriceManager::getAvailablePricingType( $this->tieredPricingType );
	}
	
	public function setTieredPricingType( string $tieredPricingType ) {
		
		$this->tieredPricingType = in_array( $tieredPricingType, PriceManager::getPricingTypes(), true ) ? $tieredPricingType : 'fixed';
	}
	
	public function getPercentageTieredPricingRules(): array {
		return $this->percentageTieredPricingRules;
	}
	
	public function setPercentageTieredPricingRules( array $percentageTieredPricingRules ) {
		$this->percentageTieredPricingRules = $percentageTieredPricingRules;
	}
	
	public function getFixedTieredPricingRules(): array {
		return $this->fixedTieredPricingRules;
	}
	
	public function setFixedTieredPricingRules( array $fixedTieredPricingRules ) {
		$this->fixedTieredPricingRules = $fixedTieredPricingRules;
	}
	
	public function getAmountTieredPricingRules(): array {
		return $this->amountTieredPricingRules;
	}
	
	public function setAmountTieredPricingRules( array $amountTieredPricingRules ) {
		$this->amountTieredPricingRules = $amountTieredPricingRules;
	}
	
	public function getApplyingType(): string {
		return $this->applyingType;
	}
	
	public function setApplyingType( string $applyingType ) {
		$this->applyingType = in_array( $applyingType, array( 'individual', 'cross' ) ) ? $applyingType : 'cross';
	}
	
	public function getPricingType(): string {
		return $this->pricingType;
	}
	
	public function setPricingType( string $priceType ) {
		$this->pricingType = in_array( $priceType, array( 'percentage', 'flat' ) ) ? $priceType : 'flat';
	}
	
	public function getTieredPricingRules(): array {
		
		// getTieredPricingType() already falls back to fixed prices for a premium type on the free plan.
		if ( $this->getTieredPricingType() === 'percentage' ) {
			return $this->getPercentageTieredPricingRules();
		}
		
		if ( $this->getTieredPricingType() === 'amount' ) {
			return $this->getAmountTieredPricingRules();
		}
		
		return $this->getFixedTieredPricingRules();
	}
	
	public function getTaxStatus(): string {
		return $this->taxStatus;
	}
	
	public function setTaxStatus( string $taxStatus ) {
		$this->taxStatus = $taxStatus;
	}
	
	public function getTaxClass(): string {
		return $this->taxClass;
	}
	
	public function setTaxClass( string $taxClass ) {
		$this->taxClass = $taxClass;
	}
	
	public function getRegularPrice(): ?float {
		return $this->regularPrice;
	}
	
	public function setRegularPrice( ?float $regularPrice ) {
		$this->regularPrice = ! Form::isEmpty( $regularPrice ) ? floatval( $regularPrice ) : null;
		
	}
	
	public function getSalePrice(): ?float {
		return $this->salePrice;
	}
	
	public function setSalePrice( ?float $salePrice ) {
		$this->salePrice = $salePrice;
	}
	
	public static function getKinds(): array {
		return array( self::KIND_PRODUCT, self::KIND_ORDER );
	}
	
	public function getKind(): string {
		return $this->kind;
	}
	
	public function setKind( string $kind ) {
		$this->kind = in_array( $kind, self::getKinds(), true ) ? $kind : self::KIND_PRODUCT;
	}
	
	/**
	 * Whether the rule discounts the order total instead of pricing products.
	 */
	public function isOrderRule(): bool {
		return self::KIND_ORDER === $this->kind;
	}
	
	public function getOrderTiers(): array {
		return $this->orderTiers;
	}
	
	public function setOrderTiers( array $orderTiers ) {
		$this->orderTiers = self::normalizeOrderTiers( $orderTiers, $this->getOrderDiscountType() );
	}
	
	/**
	 * Tiers as the form or the API hands them (rows with "from" and "discount", or a threshold => discount
	 * map): positive numbers only, a percentage capped at 100, one row per threshold, sorted by threshold.
	 */
	public static function normalizeOrderTiers( array $tiers, string $discountType = 'fixed' ): array {
		$byThreshold = array();
		
		foreach ( $tiers as $key => $tier ) {
			if ( is_array( $tier ) ) {
				$from     = $tier['from'] ?? null;
				$discount = $tier['discount'] ?? null;
			} else {
				$from     = $key;
				$discount = $tier;
			}
			
			$from     = is_numeric( $from ) ? (float) $from : 0.0;
			$discount = is_numeric( $discount ) ? (float) $discount : 0.0;
			
			if ( 'percentage' === $discountType ) {
				$discount = min( 100.0, $discount );
			}
			
			if ( $from <= 0 || $discount <= 0 ) {
				continue;
			}
			
			$byThreshold[ (string) $from ] = array( 'from' => $from, 'discount' => $discount );
		}
		
		usort( $byThreshold, function ( array $a, array $b ) {
			return $a['from'] <=> $b['from'];
		} );
		
		return array_values( $byThreshold );
	}
	
	public function getOrderDiscountType(): string {
		return $this->orderDiscountType;
	}
	
	public function setOrderDiscountType( string $orderDiscountType ) {
		$this->orderDiscountType = in_array( $orderDiscountType, array( 'percentage', 'fixed' ), true ) ? $orderDiscountType : 'fixed';
	}
	
	/**
	 * Whether the rule has any product condition (included or excluded products, categories, tags, brands,
	 * attribute values).
	 */
	public function hasProductConditions(): bool {
		$conditions = array(
			$this->getIncludedProducts(),
			$this->getIncludedProductCategories(),
			$this->getIncludedProductTags(),
			$this->getIncludedProductBrands(),
			$this->getIncludedAttributes(),
			$this->getExcludedProducts(),
			$this->getExcludedProductCategories(),
			$this->getExcludedProductTags(),
			$this->getExcludedProductBrands(),
			$this->getExcludedAttributes(),
		);
		
		return (bool) array_filter( $conditions );
	}
	
	/**
	 * Whether an order rule counts and discounts only the cart lines matching its product conditions:
	 * it does as soon as it has any, and covers the whole cart without.
	 */
	public function isOrderScopeMatching(): bool {
		return $this->hasProductConditions();
	}
	
	public function getOrderLabel(): string {
		return $this->orderLabel;
	}
	
	public function setOrderLabel( string $orderLabel ) {
		$this->orderLabel = trim( $orderLabel );
	}
	
	public function getOrderBasis(): string {
		return $this->orderBasis;
	}
	
	public function setOrderBasis( string $orderBasis ) {
		$this->orderBasis = in_array( $orderBasis, array( 'amount', 'quantity' ), true ) ? $orderBasis : 'amount';
	}
	
	/**
	 * Whether the tiers count items instead of the subtotal.
	 */
	public function isOrderBasisQuantity(): bool {
		return 'quantity' === $this->orderBasis;
	}
	
	public static function getOrderFreeShippingModes(): array {
		return array( 'none', 'always', 'amount', 'quantity' );
	}
	
	public function getOrderFreeShipping(): string {
		return $this->orderFreeShipping;
	}
	
	public function setOrderFreeShipping( string $orderFreeShipping ) {
		$this->orderFreeShipping = in_array( $orderFreeShipping, self::getOrderFreeShippingModes(), true ) ? $orderFreeShipping : 'none';
	}
	
	public function getOrderFreeShippingFrom(): ?float {
		return $this->orderFreeShippingFrom;
	}
	
	public function setOrderFreeShippingFrom( $orderFreeShippingFrom ) {
		$this->orderFreeShippingFrom = is_numeric( $orderFreeShippingFrom ) && (float) $orderFreeShippingFrom > 0 ? (float) $orderFreeShippingFrom : null;
	}
	
	/**
	 * Whether the rule gives free shipping: always, or from a threshold that is set.
	 */
	public function hasOrderFreeShipping(): bool {
		if ( 'always' === $this->orderFreeShipping ) {
			return true;
		}
		
		return in_array( $this->orderFreeShipping, array( 'amount', 'quantity' ), true ) && ! is_null( $this->orderFreeShippingFrom );
	}
	
	/**
	 * Whether a counted amount and number of items earn the rule's free shipping.
	 */
	public function earnsFreeShipping( float $amount, int $quantity ): bool {
		switch ( $this->orderFreeShipping ) {
			case 'always':
				return true;
			case 'amount':
				return ! is_null( $this->orderFreeShippingFrom ) && $amount >= $this->orderFreeShippingFrom;
			case 'quantity':
				return ! is_null( $this->orderFreeShippingFrom ) && $quantity >= $this->orderFreeShippingFrom;
		}
		
		return false;
	}
	
	public function isOrderShippingBarEnabled(): bool {
		return $this->orderShippingBar;
	}
	
	/**
	 * Switch the free shipping bar on or off.
	 *
	 * @param  bool|string  $enabled  A boolean, or the stored "yes" / "no".
	 */
	public function setOrderShippingBar( $enabled ) {
		$this->orderShippingBar = is_string( $enabled ) ? in_array( $enabled, array( 'yes', '1' ), true ) : (bool) $enabled;
	}
	
	/**
	 * Whether the storefront shows the bar: switched on, the rule grants free shipping, and the plan can
	 * show it (the bar is premium).
	 */
	public function hasOrderShippingBar(): bool {
		return $this->orderShippingBar && $this->hasOrderFreeShipping() && ! in_array( 'shipping_bar', $this->getUnavailableOrderOptions(), true );
	}
	
	public function getOrderShippingBarMessage(): string {
		return $this->orderShippingBarMessage;
	}
	
	public function setOrderShippingBarMessage( string $message ) {
		$this->orderShippingBarMessage = trim( $message );
	}
	
	public function getOrderShippingBarReached(): string {
		return $this->orderShippingBarReached;
	}
	
	public function setOrderShippingBarReached( string $message ) {
		$this->orderShippingBarReached = trim( $message );
	}
	
	public function getOrderMinimumAmount(): ?float {
		return $this->orderMinimumAmount;
	}
	
	public function setOrderMinimumAmount( $orderMinimumAmount ) {
		$this->orderMinimumAmount = is_numeric( $orderMinimumAmount ) && (float) $orderMinimumAmount > 0 ? (float) $orderMinimumAmount : null;
	}
	
	public function getOrderMinimumQuantity(): ?int {
		return $this->orderMinimumQuantity;
	}
	
	public function setOrderMinimumQuantity( $orderMinimumQuantity ) {
		$this->orderMinimumQuantity = is_numeric( $orderMinimumQuantity ) && (int) $orderMinimumQuantity > 0 ? (int) $orderMinimumQuantity : null;
	}
	
	/**
	 * Whether the rule requires a minimum amount or number of items.
	 */
	public function hasOrderMinimum(): bool {
		return ! is_null( $this->orderMinimumAmount ) || ! is_null( $this->orderMinimumQuantity );
	}
	
	/**
	 * The minimum the storefront enforces: the rule's one, or none on a plan that cannot apply it.
	 *
	 * @return array{amount: float, quantity: int}
	 */
	public function getEffectiveOrderMinimum(): array {
		if ( in_array( 'minimum', $this->getUnavailableOrderOptions(), true ) ) {
			return array( 'amount' => 0.0, 'quantity' => 0 );
		}
		
		return array( 'amount' => (float) $this->orderMinimumAmount, 'quantity' => (int) $this->orderMinimumQuantity );
	}
	
	/**
	 * The premium options an order rule uses that the current plan cannot apply: the "percentage" discount,
	 * the "minimum" order and the free "shipping_bar". Empty on the premium plan.
	 *
	 * @return string[]
	 */
	public function getUnavailableOrderOptions(): array {
		if ( tpt_fs()->can_use_premium_code() ) {
			return array();
		}
		
		$unavailable = array();
		
		if ( 'percentage' === $this->getOrderDiscountType() ) {
			$unavailable[] = 'percentage';
		}
		
		if ( $this->hasOrderMinimum() ) {
			$unavailable[] = 'minimum';
		}
		
		if ( $this->orderShippingBar ) {
			$unavailable[] = 'shipping_bar';
		}
		
		return $unavailable;
	}
	
	/**
	 * The highest order tier a subtotal reaches, or null below the first one.
	 */
	public function getOrderTierFor( float $subtotal ): ?array {
		$reached = null;
		
		foreach ( $this->getOrderTiers() as $tier ) {
			if ( $subtotal >= $tier['from'] ) {
				$reached = $tier;
			}
		}
		
		return $reached;
	}
	
	/**
	 * The next order tier above a subtotal, or null at the top.
	 */
	public function getNextOrderTierFor( float $subtotal ): ?array {
		foreach ( $this->getOrderTiers() as $tier ) {
			if ( $subtotal < $tier['from'] ) {
				return $tier;
			}
		}
		
		return null;
	}
	
	/**
	 * Create instance from array
	 *
	 * @param  array  $data
	 *
	 * @return self
	 */
	public static function fromArray( array $data ): self {
		
		$applyingType = $data['applying_type'] ?? 'individual';
		$applyingType = in_array( $applyingType, array( 'individual', 'cross' ) ) ? $applyingType : 'cross';
		
		$tieredPricingType = $data['tiered_pricing_type'] ?? 'fixed';
		$tieredPricingType = in_array( $tieredPricingType, PriceManager::getPricingTypes(), true ) ? $tieredPricingType : 'fixed';
		
		$percentageRules = isset( $data['percentage_rules'] ) ? (array) $data['percentage_rules'] : array();
		$fixedRules      = isset( $data['fixed_rules'] ) ? (array) $data['fixed_rules'] : array();
		$amountRules     = isset( $data['amount_rules'] ) ? (array) $data['amount_rules'] : array();
		
		$pricingType = $data['pricing_type'] ?? 'flat';
		$pricingType = in_array( $pricingType, array( 'flat', 'percentage' ) ) ? $pricingType : 'flat';
		
		$regularPrice = $data['regular_price'] ?? null;
		$salePrice    = $data['sale_price'] ?? null;
		$discount     = $data['discount'] ?? null;
		$discountType = $data['discount_type'] ?? 'sale_price';
		
		$minimum = $data['minimum'] ?? null;
		
		$mixAndMatchValue = $data['mix_and_match_minimum'] ?? '';
		$mixAndMatch      = ( $mixAndMatchValue === '' ) ? null : ( $mixAndMatchValue === 'yes' );
		
		$taxStatus = $data['tax_status'] ?? '';
		$taxClass  = $data['tax_class'] ?? '';
		
		$self = new self();
		
		$self->setTaxStatus( $taxStatus );
		$self->setTaxClass( $taxClass );
		
		$self->setPricingType( (string) $pricingType );
		$self->setRegularPrice( Form::isEmpty( $regularPrice ) ? null : (float) $regularPrice );
		$self->setSalePrice( Form::isEmpty( $salePrice ) ? null : (float) $salePrice );
		$self->setDiscount( Form::isEmpty( $discount ) ? null : (float) $discount );
		$self->setDiscountType( (string) $discountType );
		
		$self->setApplyingType( $applyingType );
		$self->setTieredPricingType( $tieredPricingType );
		$self->setPercentageTieredPricingRules( $percentageRules );
		$self->setFixedTieredPricingRules( $fixedRules );
		$self->setAmountTieredPricingRules( $amountRules );
		
		$self->setMinimum( Form::isEmpty( $minimum ) ? null : (int) $minimum );
		$self->setMixAndMatchMinQuantity( $mixAndMatch );
		
		// Order total
		$self->setKind( (string) ( $data['kind'] ?? self::KIND_PRODUCT ) );
		$self->setOrderDiscountType( (string) ( $data['order_discount_type'] ?? 'fixed' ) );
		$self->setOrderTiers( isset( $data['order_tiers'] ) && is_array( $data['order_tiers'] ) ? $data['order_tiers'] : array() );
		$self->setOrderLabel( (string) ( $data['order_label'] ?? '' ) );
		$self->setOrderBasis( (string) ( $data['order_basis'] ?? 'amount' ) );
		$self->setOrderFreeShipping( (string) ( $data['order_free_shipping'] ?? ( ! empty( $data['order_free_shipping_from'] ) ? 'amount' : 'none' ) ) );
		$self->setOrderFreeShippingFrom( $data['order_free_shipping_from'] ?? null );
		$self->setOrderShippingBar( $data['order_shipping_bar'] ?? false );
		$self->setOrderShippingBarMessage( (string) ( $data['order_shipping_bar_message'] ?? '' ) );
		$self->setOrderShippingBarReached( (string) ( $data['order_shipping_bar_reached'] ?? '' ) );
		$self->setOrderMinimumAmount( $data['order_minimum_amount'] ?? null );
		$self->setOrderMinimumQuantity( $data['order_minimum_quantity'] ?? null );
		
		$self->setPriority( $data['priority'] ?? self::DEFAULT_PRIORITY );
		$self->setStartDate( $data['start_date'] ?? null );
		$self->setEndDate( $data['end_date'] ?? null );
		
		return $self;
	}
	
	/**
	 * Validate
	 *
	 * @throws Exception
	 */
	public function validatePricing() {
		
		// an order rule is about its tiers and its minimum
		if ( $this->isOrderRule() ) {
			$unavailable = $this->getUnavailableOrderOptions();
			$hasTiers    = ! empty( $this->getOrderTiers() );
			$hasMinimum  = $this->hasOrderMinimum() && ! in_array( 'minimum', $unavailable, true );
			$hasShipping = $this->hasOrderFreeShipping();
			
			if ( ! $hasTiers && ! $this->hasOrderMinimum() && ! $hasShipping ) {
				throw new Exception( esc_html__( 'The rule has no discount tiers, no free shipping threshold and no minimum order, so the storefront skips it. Add them on the Discount, Free shipping and Minimum order tabs.', 'tier-pricing-table' ) );
			}
			
			if ( $hasTiers && in_array( 'percentage', $unavailable, true ) ) {
				throw new Exception( esc_html__( 'The rule gives a percentage discount, which the premium version applies, so the storefront skips it. Switch to a fixed amount off, or upgrade.', 'tier-pricing-table' ) );
			}
			
			if ( ! $hasTiers && ! $hasMinimum && ! $hasShipping ) {
				throw new Exception( esc_html__( 'The rule only sets a minimum order, which the premium version applies, so the storefront skips it.', 'tier-pricing-table' ) );
			}
			
			return;
		}
		
		// only the fields of the chosen pricing type reach the storefront: a discount left over from
		// "percentage" changes nothing in "flat" mode, and the other way round
		// a percentage discount is premium: without it only flat prices apply
		$percentageSkipped = false;
		
		if ( 'percentage' === $this->getPricingType() ) {
			$valid = (float) $this->getDiscount() > 0;
			
			if ( $valid && ! tpt_fs()->can_use_premium_code() ) {
				$valid             = false;
				$percentageSkipped = true;
			}
		} else {
			$valid = ! Form::isEmpty( $this->getRegularPrice() ) || ! Form::isEmpty( $this->getSalePrice() );
		}
		
		$valid = $valid || ! empty( $this->getTieredPricingRules() );
		$valid = $valid || ! Form::isEmpty( $this->getMinimum() );
		// a mix & match choice alone changes how product-level minimums count across variations
		$valid = $valid || ! is_null( $this->getMixAndMatchMinQuantity() );
		$valid = $valid || $this->getSettings()->getPriorityType() === 'flexible';
		
		$valid = apply_filters( 'tiered_pricing_table/global_pricing/validation', $valid, $this );
		
		if ( ! $valid && $percentageSkipped ) {
			throw new Exception( esc_html__( 'The rule gives a percentage discount, which the premium version applies, so the storefront skips it. Set flat prices instead, or upgrade.',
				'tier-pricing-table' ) );
		}
		
		if ( ! $valid ) {
			throw new Exception( esc_html__( 'The pricing rule does not affect either prices or product quantity. The rule will be skipped.',
				'tier-pricing-table' ) );
		}
	}
	
	public function isValidPricing(): bool {
		try {
			$this->validatePricing();
		} catch ( Exception $e ) {
			return false;
		}
		
		return true;
	}
	
	public function getMinimum(): ?int {
		return $this->minimum;
	}
	
	public function setMinimum( ?int $minimum ) {
		$this->minimum = intval( $minimum ) > 1 ? $minimum : null;
	}
	
	public function getMixAndMatchMinQuantity(): ?bool {
		return $this->mixAndMatchMinQuantity;
	}
	
	public function setMixAndMatchMinQuantity( ?bool $mixAndMatch ) {
		$this->mixAndMatchMinQuantity = $mixAndMatch;
	}
	
	public function getIncludedProductCategories(): array {
		return $this->includedProductCategories;
	}
	
	public function getExcludedProductCategories(): array {
		return $this->excludedProductCategories;
	}
	
	public function setIncludedProductCategories( array $includedProductCategories ) {
		$this->includedProductCategories = $includedProductCategories;
	}
	
	public function setExcludedProductCategories( array $excludedProductCategories ) {
		$this->excludedProductCategories = $excludedProductCategories;
	}
	
	public function isIncludeSubcategories(): bool {
		return $this->includeSubcategories;
	}
	
	public function setIncludeSubcategories( bool $includeSubcategories ) {
		$this->includeSubcategories = $includeSubcategories;
	}
	
	/**
	 * The included categories as the matcher reads them: with every subcategory when the switch is on.
	 */
	public function getEffectiveIncludedCategories(): array {
		return $this->includeSubcategories ? self::withSubcategories( $this->includedProductCategories ) : $this->includedProductCategories;
	}
	
	/**
	 * The excluded categories as the matcher reads them: with every subcategory when the switch is on.
	 */
	public function getEffectiveExcludedCategories(): array {
		return $this->includeSubcategories ? self::withSubcategories( $this->excludedProductCategories ) : $this->excludedProductCategories;
	}
	
	/**
	 * The ids plus all their descendant product categories.
	 */
	public static function withSubcategories( array $ids ): array {
		$all = array();
		
		foreach ( array_map( 'intval', $ids ) as $id ) {
			$all[] = $id;
			
			$children = get_term_children( $id, 'product_cat' );
			
			if ( is_array( $children ) ) {
				$all = array_merge( $all, array_map( 'intval', $children ) );
			}
		}
		
		return array_values( array_unique( $all ) );
	}
	
	public function getIncludedProductTags(): array {
		return $this->includedProductTags;
	}
	
	public function getExcludedProductTags(): array {
		return $this->excludedProductTags;
	}
	
	public function setIncludedProductTags( array $includedProductTags ) {
		$this->includedProductTags = $includedProductTags;
	}
	
	public function setExcludedProductTags( array $excludedProductTags ) {
		$this->excludedProductTags = $excludedProductTags;
	}
	
	public function getIncludedProductBrands(): array {
		return $this->includedProductBrands;
	}
	
	public function getExcludedProductBrands(): array {
		return $this->excludedProductBrands;
	}
	
	public function setIncludedProductBrands( array $includedProductBrands ) {
		$this->includedProductBrands = $includedProductBrands;
	}
	
	public function setExcludedProductBrands( array $excludedProductBrands ) {
		$this->excludedProductBrands = $excludedProductBrands;
	}
	
	public function getIncludedAttributes(): array {
		return $this->includedAttributes;
	}
	
	public function getExcludedAttributes(): array {
		return $this->excludedAttributes;
	}
	
	public function setIncludedAttributes( array $includedAttributes ) {
		$this->includedAttributes = $includedAttributes;
		$this->attributeSlugs     = array();
	}
	
	public function setExcludedAttributes( array $excludedAttributes ) {
		$this->excludedAttributes = $excludedAttributes;
		$this->attributeSlugs     = array();
	}
	
	public function getIncludedProducts(): array {
		return $this->includedProducts;
	}
	
	public function getExcludedProducts(): array {
		return $this->excludedProducts;
	}
	
	public function setIncludedProducts( array $includedProducts ) {
		$this->includedProducts = $includedProducts;
	}
	
	public function setExcludedProducts( array $excludedProducts ) {
		$this->excludedProducts = $excludedProducts;
	}
	
	public function getIncludedUserRoles(): array {
		return $this->includedUsersRole;
	}
	
	public function getExcludedUserRoles(): array {
		return $this->excludedUsersRole;
	}
	
	public function setIncludedUsersRole( array $includedUsersRole ) {
		$this->includedUsersRole = $includedUsersRole;
	}
	
	public function setExcludedUsersRole( array $excludedUsersRole ) {
		$this->excludedUsersRole = $excludedUsersRole;
	}
	
	public function getIncludedUsers(): array {
		return $this->includedUsers;
	}
	
	public function getExcludedUsers(): array {
		return $this->excludedUsers;
	}
	
	public function setIncludedUsers( array $includedUsers ) {
		$this->includedUsers = $includedUsers;
	}
	
	public function setExcludedUsers( array $excludedUsers ) {
		$this->excludedUsers = $excludedUsers;
	}
	
	public function getPriority(): int {
		return $this->priority;
	}
	
	/**
	 * @param  mixed  $priority  Anything non-numeric (an empty meta row, for instance) falls back to the default.
	 */
	public function setPriority( $priority ) {
		$this->priority = is_numeric( $priority ) ? max( 0, (int) $priority ) : self::DEFAULT_PRIORITY;
	}
	
	public function getStartDate(): ?string {
		return $this->startDate;
	}
	
	public function setStartDate( $date ) {
		$this->startDate = self::normalizeDate( $date );
	}
	
	public function getEndDate(): ?string {
		return $this->endDate;
	}
	
	public function setEndDate( $date ) {
		$this->endDate = self::normalizeDate( $date );
	}
	
	public function hasSchedule(): bool {
		return null !== $this->startDate || null !== $this->endDate;
	}
	
	public function getCreatedAt(): string {
		return $this->createdAt;
	}
	
	public function setCreatedAt( string $createdAt ) {
		$this->createdAt = $createdAt;
	}
	
	/**
	 * A "Y-m-d" string or null; anything else (a partial date, "0000-00-00", a timestamp) is dropped.
	 */
	protected static function normalizeDate( $date ): ?string {
		$date = is_string( $date ) ? trim( $date ) : '';
		
		if ( '' === $date ) {
			return null;
		}
		
		$parsed = DateTime::createFromFormat( '!Y-m-d', $date );
		
		return $parsed && $parsed->format( 'Y-m-d' ) === $date ? $date : null;
	}
	
	/**
	 * 00:00:00 of the start date in the site's timezone, as a timestamp; null without a start date.
	 */
	public function getStartTimestamp(): ?int {
		return null === $this->startDate ? null : self::dayBoundary( $this->startDate, false );
	}
	
	/**
	 * 23:59:59 of the end date in the site's timezone, as a timestamp; null without an end date.
	 */
	public function getEndTimestamp(): ?int {
		return null === $this->endDate ? null : self::dayBoundary( $this->endDate, true );
	}
	
	protected static function dayBoundary( string $date, bool $endOfDay ): int {
		$timezone = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
		$moment   = new DateTime( $date . ( $endOfDay ? ' 23:59:59' : ' 00:00:00' ), $timezone );
		
		return $moment->getTimestamp();
	}
	
	/**
	 * "active", "scheduled" (starts later) or "expired" (ended) at the given moment (default: now).
	 */
	public function getScheduleStatus( ?int $now = null ): string {
		$now   = null === $now ? time() : $now;
		$start = $this->getStartTimestamp();
		$end   = $this->getEndTimestamp();
		
		if ( null !== $start && $now < $start ) {
			return 'scheduled';
		}
		
		if ( null !== $end && $now > $end ) {
			return 'expired';
		}
		
		return 'active';
	}
	
	public function isWithinSchedule( ?int $now = null ): bool {
		return 'active' === $this->getScheduleStatus( $now );
	}
	
	const EFFECTIVE_STATUSES = array( 'active', 'scheduled', 'expired', 'suspended', 'skipped' );
	
	/**
	 * What the storefront makes of a published rule, in order of precedence: "suspended", "expired",
	 * "skipped" (changes neither prices nor quantity limits), "scheduled" (starts later) or "active".
	 */
	public function getEffectiveStatus( ?int $now = null ): string {
		if ( $this->isSuspended() ) {
			return 'suspended';
		}
		
		$schedule = $this->getScheduleStatus( $now );
		
		if ( 'expired' === $schedule ) {
			return 'expired';
		}
		
		// shown before "scheduled" so an empty campaign rule gets fixed before it starts
		if ( ! $this->isValidPricing() ) {
			return 'skipped';
		}
		
		return $schedule;
	}
	
	/**
	 * Whether a higher-priority rule can take products and customers away from this one: only a rule
	 * that is (or will be) in force. A suspended, expired or skipped rule is not applied anyway.
	 */
	public function canBeOverridden( ?int $now = null ): bool {
		return in_array( $this->getEffectiveStatus( $now ), array( 'active', 'scheduled' ), true );
	}
	
	/**
	 * usort() callback: the order the rules are matched in. Lowest priority number first; among equal
	 * numbers the newest rule first (today's behaviour for rules that never had a priority), then by ID.
	 */
	public static function compareOrder( GlobalPricingRule $a, GlobalPricingRule $b ): int {
		if ( $a->getPriority() !== $b->getPriority() ) {
			return $a->getPriority() <=> $b->getPriority();
		}
		
		if ( $a->getCreatedAt() !== $b->getCreatedAt() ) {
			return strcmp( $b->getCreatedAt(), $a->getCreatedAt() );
		}
		
		return ( (int) $b->id ) <=> ( (int) $a->id );
	}
	
	/**
	 * Whether this rule has a higher priority than another one (and so is applied where both match).
	 */
	public function outranks( GlobalPricingRule $other ): bool {
		return self::compareOrder( $this, $other ) < 0;
	}
	
	/**
	 * The rule's title, or "Rule #ID" for a rule saved without one.
	 */
	public static function titleOf( int $id ): string {
		$title = get_the_title( $id );
		
		/* translators: %d: rule ID */
		return '' !== trim( (string) $title ) ? $title : sprintf( __( 'Rule #%d', 'tier-pricing-table' ), $id );
	}
	
	public function getTitle(): string {
		return self::titleOf( (int) $this->id );
	}
	
	public function getSettings(): RuleSettings {
		if ( ! $this->priorityOptions ) {
			$this->priorityOptions = new RuleSettings( $this );
		}
		
		return $this->priorityOptions;
	}
	
	public function asArray(): array {
		return array(
			// Pricing
			'pricing_type'          => $this->getPricingType(),
			'regular_price'         => $this->getRegularPrice(),
			'sale_price'            => $this->getSalePrice(),
			'discount'              => $this->getDiscount(),
			'discount_type'         => $this->getDiscountType(),
			
			// Tiered Pricing
			'applying_type'         => $this->getApplyingType(),
			'tiered_pricing_type'   => $this->getTieredPricingType(),
			'percentage_rules'      => $this->getPercentageTieredPricingRules(),
			'fixed_rules'           => $this->getFixedTieredPricingRules(),
			'amount_rules'          => $this->getAmountTieredPricingRules(),
			
			// Order total
			'kind'                  => $this->getKind(),
			'order_tiers'           => $this->getOrderTiers(),
			'order_discount_type'   => $this->getOrderDiscountType(),
			'order_label'           => $this->getOrderLabel(),
			'order_basis'           => $this->getOrderBasis(),
			'order_free_shipping'   => $this->getOrderFreeShipping(),
			'order_free_shipping_from' => $this->getOrderFreeShippingFrom(),
			'order_shipping_bar'    => $this->isOrderShippingBarEnabled(),
			'order_shipping_bar_message' => $this->getOrderShippingBarMessage(),
			'order_shipping_bar_reached' => $this->getOrderShippingBarReached(),
			'order_minimum_amount'  => $this->getOrderMinimumAmount(),
			'order_minimum_quantity' => $this->getOrderMinimumQuantity(),
			
			// MOQ
			'minimum'               => $this->getMinimum(),
			'mix_and_match_minimum' => $this->getMixAndMatchMinQuantity(),
			
			// Tax
			'tax_status'            => $this->getTaxStatus(),
			'tax_class'             => $this->getTaxClass(),
			
			// Order and schedule
			'priority'              => $this->getPriority(),
			'start_date'            => $this->getStartDate(),
			'end_date'              => $this->getEndDate(),
			
			// Applying rules
			'included_categories'   => $this->getIncludedProductCategories(),
			'include_subcategories' => $this->isIncludeSubcategories(),
			'included_tags'         => $this->getIncludedProductTags(),
			'included_brands'       => $this->getIncludedProductBrands(),
			'included_products'     => $this->getIncludedProducts(),
			'included_attributes'   => $this->getIncludedAttributes(),
			'included_users'        => $this->getIncludedUsers(),
			'included_users_role'   => $this->getIncludedUserRoles(),
			
			'excluded_categories' => $this->getExcludedProductCategories(),
			'excluded_tags'       => $this->getExcludedProductTags(),
			'excluded_brands'     => $this->getExcludedProductBrands(),
			'excluded_products'   => $this->getExcludedProducts(),
			'excluded_attributes' => $this->getExcludedAttributes(),
			'excluded_users'      => $this->getExcludedUsers(),
			'excluded_users_role' => $this->getExcludedUserRoles(),
			
			'rule_id'      => $this->getId(),
			'is_suspended' => $this->isSuspended(),
		);
	}
	
	public function save() {
		$dataToUpdate = array(
			// Pricing
			'_tpt_pricing_type'          => $this->getPricingType(),
			'_tpt_regular_price'         => $this->getRegularPrice(),
			'_tpt_sale_price'            => $this->getSalePrice(),
			'_tpt_discount'              => $this->getDiscount(),
			'_tpt_discount_type'         => $this->getDiscountType(),
			
			// Tiered Pricing
			'_tpt_applying_type'         => $this->getApplyingType(),
			'_tpt_tiered_pricing_type'   => $this->getTieredPricingType(),
			'_tpt_percentage_rules'      => $this->getPercentageTieredPricingRules(),
			'_tpt_fixed_rules'           => $this->getFixedTieredPricingRules(),
			'_tpt_amount_rules'          => $this->getAmountTieredPricingRules(),
			
			// Order total
			'_tpt_rule_kind'             => $this->getKind(),
			'_tpt_order_tiers'           => $this->getOrderTiers(),
			'_tpt_order_discount_type'   => $this->getOrderDiscountType(),
			'_tpt_order_label'           => $this->getOrderLabel(),
			'_tpt_order_basis'           => $this->getOrderBasis(),
			'_tpt_order_free_shipping'   => $this->getOrderFreeShipping(),
			'_tpt_order_free_shipping_from' => is_null( $this->getOrderFreeShippingFrom() ) ? '' : $this->getOrderFreeShippingFrom(),
			'_tpt_order_shipping_bar'    => $this->isOrderShippingBarEnabled() ? 'yes' : 'no',
			'_tpt_order_shipping_bar_message' => $this->getOrderShippingBarMessage(),
			'_tpt_order_shipping_bar_reached' => $this->getOrderShippingBarReached(),
			'_tpt_order_minimum_amount'  => is_null( $this->getOrderMinimumAmount() ) ? '' : $this->getOrderMinimumAmount(),
			'_tpt_order_minimum_quantity' => is_null( $this->getOrderMinimumQuantity() ) ? '' : $this->getOrderMinimumQuantity(),
			
			// MOQ
			'_tpt_minimum'               => $this->getMinimum(),
			'_tpt_mix_and_match_minimum' => is_null( $this->getMixAndMatchMinQuantity() ) ? '' : wc_bool_to_string( $this->getMixAndMatchMinQuantity() ),
			
			// Tax
			'_tpt_tax_status'            => $this->getTaxStatus(),
			'_tpt_tax_class'             => $this->getTaxClass(),
			
			// Order and schedule
			'_tpt_priority'              => $this->getPriority(),
			'_tpt_start_date'            => (string) $this->getStartDate(),
			'_tpt_end_date'              => (string) $this->getEndDate(),
			
			// Applying rules
			'_tpt_included_categories'   => $this->getIncludedProductCategories(),
			'_tpt_include_subcategories' => wc_bool_to_string( $this->isIncludeSubcategories() ),
			'_tpt_included_tags'         => $this->getIncludedProductTags(),
			'_tpt_included_brands'       => $this->getIncludedProductBrands(),
			'_tpt_included_products'     => $this->getIncludedProducts(),
			'_tpt_included_attributes'   => $this->getIncludedAttributes(),
			'_tpt_included_users'        => $this->getIncludedUsers(),
			'_tpt_included_user_roles'   => $this->getIncludedUserRoles(),
			
			'_tpt_excluded_categories' => $this->getExcludedProductCategories(),
			'_tpt_excluded_tags'       => $this->getExcludedProductTags(),
			'_tpt_excluded_brands'     => $this->getExcludedProductBrands(),
			'_tpt_excluded_products'   => $this->getExcludedProducts(),
			'_tpt_excluded_attributes' => $this->getExcludedAttributes(),
			'_tpt_excluded_users'      => $this->getExcludedUsers(),
			'_tpt_excluded_user_roles' => $this->getExcludedUserRoles(),
			
			'_tpt_is_suspended' => wc_bool_to_string( $this->isSuspended() ),
		);
		
		foreach ( $dataToUpdate as $key => $value ) {
			update_post_meta( $this->getId(), $key, $value );
		}
	}
	
	public static function build( $ruleId ): self {
		
		// Simple data to read
		$dataToRead = array(
			// Pricing
			'_tpt_pricing_type'          => 'pricing_type',
			'_tpt_sale_price'            => 'sale_price',
			'_tpt_regular_price'         => 'regular_price',
			'_tpt_discount'              => 'discount',
			'_tpt_discount_type'         => 'discount_type',
			
			// Tiered Pricing
			'_tpt_applying_type'         => 'applying_type',
			'_tpt_tiered_pricing_type'   => 'tiered_pricing_type',
			
			// Order total
			'_tpt_rule_kind'             => 'kind',
			'_tpt_order_discount_type'   => 'order_discount_type',
			'_tpt_order_label'           => 'order_label',
			'_tpt_order_basis'           => 'order_basis',
			'_tpt_order_free_shipping'   => 'order_free_shipping',
			'_tpt_order_free_shipping_from' => 'order_free_shipping_from',
			'_tpt_order_shipping_bar'    => 'order_shipping_bar',
			'_tpt_order_shipping_bar_message' => 'order_shipping_bar_message',
			'_tpt_order_shipping_bar_reached' => 'order_shipping_bar_reached',
			'_tpt_order_minimum_amount'  => 'order_minimum_amount',
			'_tpt_order_minimum_quantity' => 'order_minimum_quantity',
			
			// Moq
			'_tpt_minimum'               => 'minimum',
			'_tpt_mix_and_match_minimum' => 'mix_and_match_minimum',
			
			// Tax
			'_tpt_tax_status'            => 'tax_status',
			'_tpt_tax_class'             => 'tax_class',
			
			'_tpt_is_suspended' => 'is_suspended',
			
			// Order and schedule
			'_tpt_priority'   => 'priority',
			'_tpt_start_date' => 'start_date',
			'_tpt_end_date'   => 'end_date',
		);
		
		$data = array();
		
		foreach ( $dataToRead as $key => $name ) {
			$data[ $name ] = get_post_meta( $ruleId, $key, true );
		}
		
		$orderTiers          = get_post_meta( $ruleId, '_tpt_order_tiers', true );
		$data['order_tiers'] = is_array( $orderTiers ) ? $orderTiers : array();
		
		$priceRule = self::fromArray( $data );
		$priceRule->setCreatedAt( (string) get_post_field( 'post_date_gmt', $ruleId ) );
		$existingRoles = wp_roles()->roles;
		
		$includedCategoriesIds = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_categories', true ) ) );
		$includedTagsIds       = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_tags', true ) ) );
		$includedBrandsIds     = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_brands', true ) ) );
		$includedProductsIds   = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_products', true ) ) );
		$includedAttributes    = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_attributes', true ) ) );
		$includedUsersRole     = array_filter( (array) get_post_meta( $ruleId, '_tpt_included_user_roles', true ),
			array( self::class, 'isSelectableRole' ) );
		$includedUsers         = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_included_users', true ) ) );
		
		
		$excludedCategoriesIds = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_categories', true ) ) );
		$excludedTagsIds       = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_tags', true ) ) );
		$excludedBrandsIds     = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_brands', true ) ) );
		$excludedProductsIds   = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_products', true ) ) );
		$excludedAttributes    = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_attributes', true ) ) );
		$excludedUsersRole     = array_filter( (array) get_post_meta( $ruleId, '_tpt_excluded_user_roles', true ),
			array( self::class, 'isSelectableRole' ) );
		$excludedUsers         = array_filter( array_map( 'intval',
			(array) get_post_meta( $ruleId, '_tpt_excluded_users', true ) ) );
		
		
		$isSuspended = get_post_meta( $ruleId, '_tpt_is_suspended', true ) === 'yes';
		
		$priceRule->setPercentageTieredPricingRules( self::readPricingRules( 'percentage', $ruleId ) );
		$priceRule->setFixedTieredPricingRules( self::readPricingRules( 'fixed', $ruleId ) );
		$priceRule->setAmountTieredPricingRules( self::readPricingRules( 'amount', $ruleId ) );
		
		$priceRule->setIncludedProductCategories( $includedCategoriesIds );
		$priceRule->setIncludeSubcategories( 'yes' === get_post_meta( $ruleId, '_tpt_include_subcategories', true ) );
		$priceRule->setIncludedProductTags( $includedTagsIds );
		$priceRule->setIncludedProductBrands( $includedBrandsIds );
		$priceRule->setIncludedUsers( $includedUsers );
		$priceRule->setIncludedUsersRole( $includedUsersRole );
		$priceRule->setIncludedProducts( $includedProductsIds );
		$priceRule->setIncludedAttributes( array_values( $includedAttributes ) );
		
		$priceRule->setExcludedProductCategories( $excludedCategoriesIds );
		$priceRule->setExcludedProductTags( $excludedTagsIds );
		$priceRule->setExcludedProductBrands( $excludedBrandsIds );
		$priceRule->setExcludedUsers( $excludedUsers );
		$priceRule->setExcludedUsersRole( $excludedUsersRole );
		$priceRule->setExcludedProducts( $excludedProductsIds );
		$priceRule->setExcludedAttributes( array_values( $excludedAttributes ) );
		
		$priceRule->setIsSuspended( $isSuspended );
		
		$priceRule->setId( $ruleId );
		
		return apply_filters( 'tiered_pricing_table/global_pricing/after_built_rule', $priceRule );
	}
	
	protected static function readPricingRules( $type, $id ): array {
		
		$type = in_array( $type, PriceManager::getPricingTypes(), true ) ? $type : 'fixed';
		
		$rules = get_post_meta( $id, "_tpt_{$type}_rules", true );
		
		$rules = ! empty( $rules ) ? $rules : array();
		$rules = is_array( $rules ) ? array_filter( $rules ) : array();
		
		ksort( $rules );
		
		return $rules;
	}
	
	public function setIsSuspended( bool $isSuspended ) {
		$this->isSuspended = $isSuspended;
	}
	
	public function suspend() {
		$this->setIsSuspended( true );
	}
	
	public function reactivate() {
		$this->setIsSuspended( false );
	}
	
	public function isSuspended(): bool {
		return $this->isSuspended;
	}
	
	/**
	 * Wrapper for the main "match" function to provide the hook for 3rd party devs
	 *
	 * @param  WP_User  $user
	 * @param  WC_Product  $product
	 *
	 * @return bool
	 */
	public function matchRequirements( WP_User $user, WC_Product $product ): bool {
		$matched = $this->_matchRequirements( $user, $product );
		
		return apply_filters( 'tiered_pricing_table/global_pricing/match_requirements', $matched, $this, $user,
			$product );
	}
	
	protected function _matchRequirements( WP_User $user, WC_Product $product ): bool {
		return $this->matchesProduct( $product ) && $this->matchesUser( $user );
	}
	
	/**
	 * Term ids of variation parents read in this request: parent id => taxonomy => ids.
	 *
	 * @var array<int, array<string, int[]>>
	 */
	protected static $parentTermIds = array();
	
	/**
	 * @var bool
	 */
	protected static $forgettingTerms = false;
	
	/**
	 * Whether the product falls under the rule's product conditions: not excluded, and included when the
	 * rule limits its products.
	 */
	public function matchesProduct( WC_Product $product ): bool {

		// A variation matches by its own id and its parent's: the parent product itself is only needed
		// for its categories, tags and brands (self::getProductTermIds()).
		$productId = $product->get_id();
		$parentId  = self::isVariation( $product ) ? $product->get_parent_id() : $productId;

		/**
		 * 1. Check for product exclusion
		 *
		 * If the product in exclusion - pricing rule does not match immediately
		 */
		$excludedProducts = $this->translateIds( $this->getExcludedProducts(), 'product' );

		if ( ! empty( $excludedProducts ) ) {
			if ( in_array( $productId, $excludedProducts ) || in_array( $parentId, $excludedProducts ) ) {
				return false;
			}
		}

		$excludedProductCategories = $this->translateIds( $this->getEffectiveExcludedCategories(), 'product_cat' );

		if ( ! empty( $excludedProductCategories ) ) {
			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_cat' ), $excludedProductCategories ) ) ) {
				return false;
			}
		}

		$excludedProductTags = $this->translateIds( $this->getExcludedProductTags(), 'product_tag' );

		if ( ! empty( $excludedProductTags ) ) {
			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_tag' ), $excludedProductTags ) ) ) {
				return false;
			}
		}

		$excludedProductBrands = $this->translateIds( $this->getExcludedProductBrands(), 'product_brand' );

		if ( ! empty( $excludedProductBrands ) ) {
			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_brand' ), $excludedProductBrands ) ) ) {
				return false;
			}
		}
		
		if ( ! empty( $this->getExcludedAttributes() ) && ! empty( $this->getExcludedAttributeHits( $product ) ) ) {
			return false;
		}
		
		/**
		 * 3. Check for rule limitation for specific products
		 *
		 * If yes - match rule only for selected product/product categories
		 */
		
		$productMatched     = false;
		$productLimitations = false;
		
		$includedProducts = $this->translateIds( $this->getIncludedProducts(), 'product' );

		if ( ! empty( $includedProducts ) ) {
			$productLimitations = true;

			if ( in_array( $productId, $includedProducts ) || in_array( $parentId, $includedProducts ) ) {
				$productMatched = true;
			}
		}

		$includedProductCategories = $this->translateIds( $this->getEffectiveIncludedCategories(), 'product_cat' );

		if ( ! empty( $includedProductCategories ) ) {
			$productLimitations = true;

			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_cat' ), $includedProductCategories ) ) ) {
				$productMatched = true;
			}
		}

		$includedProductTags = $this->translateIds( $this->getIncludedProductTags(), 'product_tag' );

		if ( ! empty( $includedProductTags ) ) {
			$productLimitations = true;

			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_tag' ), $includedProductTags ) ) ) {
				$productMatched = true;
			}
		}

		$includedProductBrands = $this->translateIds( $this->getIncludedProductBrands(), 'product_brand' );

		if ( ! empty( $includedProductBrands ) ) {
			$productLimitations = true;

			if ( ! empty( array_intersect( self::getProductTermIds( $product, 'product_brand' ), $includedProductBrands ) ) ) {
				$productMatched = true;
			}
		}
		
		// There is product limitation and the product/category does not match the rule
		if ( $productLimitations && ! $productMatched ) {
			return false;
		}
		
		// 4. Attribute values narrow what the lists above cover (or the whole store when they are empty)
		if ( ! empty( $this->getIncludedAttributes() ) && ! $this->matchesIncludedAttributes( $product ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Whether the product has the rule's attribute values: one of the chosen values for every attribute
	 * the rule names (values of one attribute are alternatives, different attributes must all match).
	 */
	public function matchesIncludedAttributes( WC_Product $product ): bool {
		$byAttribute = $this->attributeSlugs( $this->getIncludedAttributes() );
		
		// values that no longer exist (deleted terms) never widen the rule to every product
		if ( empty( $byAttribute ) ) {
			return empty( $this->getIncludedAttributes() );
		}
		
		foreach ( $byAttribute as $taxonomy => $slugs ) {
			if ( ! array_intersect( $slugs, self::getAttributeValues( $product, $taxonomy ) ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * The rule's included attribute values (term ids as saved) the product has.
	 *
	 * @return int[]
	 */
	public function getIncludedAttributeHits( WC_Product $product ): array {
		return $this->attributeHits( $product, $this->getIncludedAttributes() );
	}
	
	/**
	 * The rule's excluded attribute values (term ids as saved) the product has: any one excludes it.
	 *
	 * @return int[]
	 */
	public function getExcludedAttributeHits( WC_Product $product ): array {
		return $this->attributeHits( $product, $this->getExcludedAttributes() );
	}
	
	/**
	 * @return int[]
	 */
	protected function attributeHits( WC_Product $product, array $termIds ): array {
		$hits = array();
		
		foreach ( $this->attributeSlugs( $termIds ) as $taxonomy => $slugs ) {
			$hits = array_merge( $hits, array_keys( array_intersect( $slugs, self::getAttributeValues( $product, $taxonomy ) ) ) );
		}
		
		return $hits;
	}
	
	/**
	 * Attribute values grouped by attribute: taxonomy => array( term id as saved => slug ). The slug is the
	 * one of the current language (WPML), since a variation stores the slug of its value.
	 *
	 * Resolved once per list and language: products are checked against every rule many times per page.
	 *
	 * @return array<string, array<int, string>>
	 */
	protected function attributeSlugs( array $termIds ): array {
		
		if ( empty( $termIds ) ) {
			return array();
		}
		
		$key = implode( ',', $termIds ) . '|' . (string) apply_filters( 'wpml_current_language', '' );
		
		if ( isset( $this->attributeSlugs[ $key ] ) ) {
			return $this->attributeSlugs[ $key ];
		}
		
		if ( function_exists( '_prime_term_caches' ) ) {
			_prime_term_caches( array_map( 'intval', $termIds ), false );
		}
		
		$byAttribute = array();
		
		foreach ( $termIds as $termId ) {
			$term = get_term( (int) $termId );
			
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			
			$translatedId = $this->translateIds( array( $term->term_id ), $term->taxonomy )[0];
			
			if ( (int) $translatedId !== (int) $term->term_id ) {
				$translated = get_term( $translatedId, $term->taxonomy );
				$term       = $translated instanceof WP_Term ? $translated : $term;
			}
			
			$byAttribute[ $term->taxonomy ][ (int) $termId ] = (string) $term->slug;
		}
		
		$this->attributeSlugs[ $key ] = $byAttribute;
		
		return $byAttribute;
	}
	
	/**
	 * The values (slugs) a product has for a global attribute. A variation has the one value it is set to,
	 * or none when it is set to "Any …": the rule cannot know which value the customer picked. For an
	 * attribute the variations do not use, a variation has its parent's values.
	 *
	 * @return string[]
	 */
	public static function getAttributeValues( WC_Product $product, string $taxonomy ): array {
		$productId = $product->get_id();
		
		if ( self::isVariation( $product ) ) {
			$own = (array) $product->get_attributes(); // taxonomy => slug, "" for "Any …"
			
			if ( array_key_exists( $taxonomy, $own ) ) {
				return '' === (string) $own[ $taxonomy ] ? array() : array( (string) $own[ $taxonomy ] );
			}
			
			$productId = $product->get_parent_id();
		}
		
		// the object term cache: primed with the products of a shop page, cleared when the terms change
		$terms = get_the_terms( $productId, $taxonomy );
		
		if ( ! is_array( $terms ) ) {
			return array();
		}
		
		return array_map( function ( $term ) {
			return (string) $term->slug;
		}, $terms );
	}
	
	/**
	 * Keeps the posted term ids that are values of a global product attribute.
	 *
	 * @param  mixed  $termIds
	 *
	 * @return int[]
	 */
	public static function sanitizeAttributeTermIds( $termIds ): array {
		$valid = array();
		
		foreach ( array_unique( array_filter( array_map( 'intval', (array) $termIds ) ) ) as $termId ) {
			$term = get_term( $termId );
			
			if ( $term instanceof WP_Term && taxonomy_is_product_attribute( $term->taxonomy ) ) {
				$valid[] = $termId;
			}
		}
		
		return $valid;
	}
	
	/**
	 * Whether the customer falls under the rule's customer conditions: not excluded, and included when
	 * the rule limits its customers.
	 */
	/**
	 * The term ids a product is matched by: a product's own, a variation's parent's.
	 *
	 * Every variation is checked against every rule, so a parent's terms are read once per request
	 * (self::$parentTermIds) instead of loading the parent product for each check.
	 */
	protected static function getProductTermIds( WC_Product $product, string $taxonomy ): array {
		
		if ( ! self::isVariation( $product ) ) {
			if ( 'product_cat' === $taxonomy ) {
				return $product->get_category_ids();
			}
			
			if ( 'product_tag' === $taxonomy ) {
				return $product->get_tag_ids();
			}
			
			return self::readBrandIds( $product->get_id() );
		}
		
		$parentId = $product->get_parent_id();
		
		if ( ! isset( self::$parentTermIds[ $parentId ][ $taxonomy ] ) ) {
			self::forgetTermsOnChange();
			
			if ( 'product_brand' === $taxonomy ) {
				self::$parentTermIds[ $parentId ][ $taxonomy ] = self::readBrandIds( $parentId );
			} else {
				// categories and tags come from one load of the parent
				$parent = wc_get_product( $parentId );
				
				self::$parentTermIds[ $parentId ]['product_cat'] = $parent ? $parent->get_category_ids() : array();
				self::$parentTermIds[ $parentId ]['product_tag'] = $parent ? $parent->get_tag_ids() : array();
			}
		}
		
		return self::$parentTermIds[ $parentId ][ $taxonomy ];
	}
	
	protected static function readBrandIds( int $productId ): array {
		$brandIds = wp_get_post_terms( $productId, 'product_brand', array( 'fields' => 'ids' ) );
		
		return is_wp_error( $brandIds ) ? array() : array_map( 'intval', $brandIds );
	}
	
	protected static function isVariation( WC_Product $product ): bool {
		return $product->is_type( array( 'variation', 'subscription-variation' ) );
	}
	
	/**
	 * A parent's stored terms are dropped when its terms change or it is saved later in the same request
	 * (imports, bulk edits).
	 */
	protected static function forgetTermsOnChange() {
		
		if ( self::$forgettingTerms ) {
			return;
		}
		
		self::$forgettingTerms = true;
		
		$forget = function ( $objectId ) {
			unset( self::$parentTermIds[ (int) $objectId ] );
		};
		
		add_action( 'set_object_terms', $forget );
		add_action( 'clean_post_cache', $forget );
	}
	
	public function matchesUser( WP_User $user ): bool {
		
		/**
		 * 2. Check for user exclusion
		 *
		 * If users in exclusion - pricing rule does not match immediately
		 */
		if ( in_array( $user->ID, $this->getExcludedUsers() ) ) {
			return false;
		}
		
		$userRoles = self::rolesOf( $user );
		
		foreach ( $this->getExcludedUserRoles() as $role ) {
			if ( in_array( $role, $userRoles ) ) {
				return false;
			}
		}
		
		/**
		 * 4. If there are no user limits - match the rule immediately
		 */
		if ( empty( $this->getIncludedUserRoles() ) && empty( $this->getIncludedUsers() ) ) {
			return true;
		}
		
		/**
		 * 5. If there are user limits - check for user ID and user role.
		 */
		if ( in_array( $user->ID, $this->getIncludedUsers() ) ) {
			return true;
		}
		
		foreach ( $this->getIncludedUserRoles() as $role ) {
			if ( in_array( $role, $userRoles ) ) {
				return true;
			}
		}

		return false;
	}
	
	/**
	 * Map a rule's stored object IDs to the language currently being viewed.
	 *
	 * A rule is created in a single language and stores the product/term IDs of that language. The
	 * product being priced (and the term IDs returned by WooCommerce for it) are in the current
	 * language, so under WPML/Polylang a raw ID comparison would never match on a translation. This
	 * translates the stored IDs into the current language via the shared `wpml_object_id` filter
	 * (implemented by both WPML and Polylang) so the comparison works across every translation.
	 *
	 * When no multilingual plugin is active the filter has no listeners, so the IDs are returned
	 * unchanged with no overhead.
	 *
	 * @param  int[]   $ids   Object IDs as stored on the rule.
	 * @param  string  $type  Element type: a post type ('product') or taxonomy ('product_cat',
	 *                        'product_tag', 'product_brand').
	 *
	 * @return int[]
	 */
	protected function translateIds( array $ids, string $type ): array {

		if ( empty( $ids ) || ! has_filter( 'wpml_object_id' ) ) {
			return $ids;
		}

		$translated = array();

		foreach ( $ids as $id ) {
			// The `true` argument keeps the original ID when the object has no translation in the
			// current language, preserving the previous behaviour for untranslated objects.
			$translated[] = (int) apply_filters( 'wpml_object_id', $id, $type, true );
		}

		return $translated;
	}
}
