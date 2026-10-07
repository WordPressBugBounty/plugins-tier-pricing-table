<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

/**
 * Finds global pricing rules that can meet on the same product for the same customer at the same time,
 * so the admin learns which rule wins instead of finding out on the storefront.
 *
 * Scopes are compared the way the matcher reads them: a rule without product limits covers every product
 * except its exclusions, a rule without customer limits covers every customer (visitors included) except
 * its exclusions. Listed products are resolved to their terms and listed customers to their roles through
 * the callbacks, so the check stays testable.
 */
class RuleOverlaps {
	
	/**
	 * @var callable(int): array{categories: int[], tags: int[], brands: int[]}
	 */
	protected $productTerms;
	
	/**
	 * @var callable(int): string[]
	 */
	protected $userRoles;
	
	/**
	 * @var int
	 */
	protected $now;
	
	public function __construct( ?callable $productTerms = null, ?callable $userRoles = null, ?int $now = null ) {
		$this->productTerms = $productTerms ? $productTerms : array( $this, 'readProductTerms' );
		$this->userRoles    = $userRoles ? $userRoles : array( $this, 'readUserRoles' );
		$this->now          = null === $now ? time() : $now;
	}
	
	/**
	 * Rules among $rules that overlap with $rule, in the order given (the rule itself is skipped).
	 *
	 * @param  GlobalPricingRule[]  $rules
	 *
	 * @return GlobalPricingRule[]
	 */
	public function findOverlapping( GlobalPricingRule $rule, array $rules ): array {
		$overlapping = array();
		
		foreach ( $rules as $other ) {
			if ( (int) $other->id === (int) $rule->id ) {
				continue;
			}
			
			if ( $this->overlap( $rule, $other ) ) {
				$overlapping[] = $other;
			}
		}
		
		return $overlapping;
	}
	
	/**
	 * The overlapping rules with a higher priority than $rule, so $rule is skipped for the products and
	 * customers it shares with them.
	 *
	 * @param  GlobalPricingRule[]  $rules
	 *
	 * @return GlobalPricingRule[]
	 */
	public function findOverriding( GlobalPricingRule $rule, array $rules ): array {
		return array_values( array_filter( $this->findOverlapping( $rule, $rules ), function ( GlobalPricingRule $other ) use ( $rule ) {
			return $other->outranks( $rule );
		} ) );
	}
	
	public function overlap( GlobalPricingRule $a, GlobalPricingRule $b ): bool {
		return self::schedulesIntersect( $a, $b, $this->now ) && $this->customersIntersect( $a, $b ) && $this->productsIntersect( $a, $b );
	}
	
	/**
	 * Whether the two date windows still share a moment from now on (open ends count as unbounded).
	 * Windows that only met in the past, an ended campaign for instance, are no overlap any more.
	 */
	public static function schedulesIntersect( GlobalPricingRule $a, GlobalPricingRule $b, ?int $now = null ): bool {
		$now   = null === $now ? time() : $now;
		$start = max( $a->getStartTimestamp() ?? PHP_INT_MIN, $b->getStartTimestamp() ?? PHP_INT_MIN );
		$end   = min( $a->getEndTimestamp() ?? PHP_INT_MAX, $b->getEndTimestamp() ?? PHP_INT_MAX );
		
		return $start <= $end && $end >= $now;
	}
	
	protected function productsIntersect( GlobalPricingRule $a, GlobalPricingRule $b ): bool {
		$aLimited = $this->hasProductLimits( $a );
		$bLimited = $this->hasProductLimits( $b );
		
		if ( ! $aLimited && ! $bLimited ) {
			return true;
		}
		
		if ( ! $aLimited ) {
			return ! $this->productScopeExcludedBy( $b, $a );
		}
		
		if ( ! $bLimited ) {
			return ! $this->productScopeExcludedBy( $a, $b );
		}
		
		return $this->productScopesShare( $a, $b );
	}
	
	protected function hasProductLimits( GlobalPricingRule $rule ): bool {
		return ! empty( $rule->getIncludedProducts() ) || ! empty( $rule->getEffectiveIncludedCategories() )
		       || ! empty( $rule->getIncludedProductTags() ) || ! empty( $rule->getIncludedProductBrands() );
	}
	
	/**
	 * Whether everything the limited rule includes is excluded by the open (store-wide) rule.
	 */
	protected function productScopeExcludedBy( GlobalPricingRule $limited, GlobalPricingRule $open ): bool {
		$dimensions = array(
			array( $limited->getEffectiveIncludedCategories(), $open->getEffectiveExcludedCategories() ),
			array( $limited->getIncludedProductTags(), $open->getExcludedProductTags() ),
			array( $limited->getIncludedProductBrands(), $open->getExcludedProductBrands() ),
		);
		
		foreach ( $dimensions as list( $included, $excluded ) ) {
			if ( array_diff( self::ints( $included ), self::ints( $excluded ) ) ) {
				return false;
			}
		}
		
		foreach ( self::ints( $limited->getIncludedProducts() ) as $productId ) {
			if ( ! $this->productExcludedBy( $productId, $open ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Two limited rules meet when they share a product, category, tag or brand, or when a product listed
	 * in one falls under the other's categories, tags or brands.
	 */
	protected function productScopesShare( GlobalPricingRule $a, GlobalPricingRule $b ): bool {
		if ( array_intersect( self::ints( $a->getIncludedProducts() ), self::ints( $b->getIncludedProducts() ) )
		     || array_intersect( self::ints( $a->getEffectiveIncludedCategories() ), self::ints( $b->getEffectiveIncludedCategories() ) )
		     || array_intersect( self::ints( $a->getIncludedProductTags() ), self::ints( $b->getIncludedProductTags() ) )
		     || array_intersect( self::ints( $a->getIncludedProductBrands() ), self::ints( $b->getIncludedProductBrands() ) ) ) {
			return true;
		}
		
		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $listing, $other ) ) {
			foreach ( self::ints( $listing->getIncludedProducts() ) as $productId ) {
				if ( $this->productExcludedBy( $productId, $other ) ) {
					continue;
				}
				
				$terms = $this->terms( $productId );
				
				if ( array_intersect( $terms['categories'], self::ints( $other->getEffectiveIncludedCategories() ) )
				     || array_intersect( $terms['tags'], self::ints( $other->getIncludedProductTags() ) )
				     || array_intersect( $terms['brands'], self::ints( $other->getIncludedProductBrands() ) ) ) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Whether a product never matches a rule because of the rule's exclusions.
	 */
	protected function productExcludedBy( int $productId, GlobalPricingRule $rule ): bool {
		if ( in_array( $productId, self::ints( $rule->getExcludedProducts() ), true ) ) {
			return true;
		}
		
		$terms = $this->terms( $productId );
		
		return (bool) ( array_intersect( $terms['categories'], self::ints( $rule->getEffectiveExcludedCategories() ) )
		                || array_intersect( $terms['tags'], self::ints( $rule->getExcludedProductTags() ) )
		                || array_intersect( $terms['brands'], self::ints( $rule->getExcludedProductBrands() ) ) );
	}
	
	protected function customersIntersect( GlobalPricingRule $a, GlobalPricingRule $b ): bool {
		$aLimited = $this->hasCustomerLimits( $a );
		$bLimited = $this->hasCustomerLimits( $b );
		
		if ( ! $aLimited && ! $bLimited ) {
			return true;
		}
		
		if ( ! $aLimited ) {
			return ! $this->customerScopeExcludedBy( $b, $a );
		}
		
		if ( ! $bLimited ) {
			return ! $this->customerScopeExcludedBy( $a, $b );
		}
		
		if ( array_intersect( $a->getIncludedUserRoles(), $b->getIncludedUserRoles() )
		     || array_intersect( self::ints( $a->getIncludedUsers() ), self::ints( $b->getIncludedUsers() ) ) ) {
			return true;
		}
		
		// a customer listed in one rule who has a role the other rule targets
		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $listing, $other ) ) {
			foreach ( self::ints( $listing->getIncludedUsers() ) as $userId ) {
				if ( $this->userExcludedBy( $userId, $other ) ) {
					continue;
				}
				
				if ( array_intersect( $this->roles( $userId ), $other->getIncludedUserRoles() ) ) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	protected function hasCustomerLimits( GlobalPricingRule $rule ): bool {
		return ! empty( $rule->getIncludedUserRoles() ) || ! empty( $rule->getIncludedUsers() );
	}
	
	/**
	 * Whether every customer the limited rule targets is excluded by the open (every-customer) rule.
	 */
	protected function customerScopeExcludedBy( GlobalPricingRule $limited, GlobalPricingRule $open ): bool {
		if ( array_diff( $limited->getIncludedUserRoles(), $open->getExcludedUserRoles() ) ) {
			return false;
		}
		
		foreach ( self::ints( $limited->getIncludedUsers() ) as $userId ) {
			if ( ! $this->userExcludedBy( $userId, $open ) ) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function userExcludedBy( int $userId, GlobalPricingRule $rule ): bool {
		if ( in_array( $userId, self::ints( $rule->getExcludedUsers() ), true ) ) {
			return true;
		}
		
		return (bool) array_intersect( $this->roles( $userId ), $rule->getExcludedUserRoles() );
	}
	
	protected function terms( int $productId ): array {
		$terms = (array) call_user_func( $this->productTerms, $productId );
		
		return array(
			'categories' => self::ints( $terms['categories'] ?? array() ),
			'tags'       => self::ints( $terms['tags'] ?? array() ),
			'brands'     => self::ints( $terms['brands'] ?? array() ),
		);
	}
	
	protected function roles( int $userId ): array {
		return array_values( array_map( 'strval', (array) call_user_func( $this->userRoles, $userId ) ) );
	}
	
	protected static function ints( array $values ): array {
		return array_values( array_filter( array_map( 'intval', $values ) ) );
	}
	
	/**
	 * Default product resolver: the parent's terms for a variation, the product's own otherwise.
	 */
	public function readProductTerms( int $productId ): array {
		static $cache = array();
		
		if ( ! isset( $cache[ $productId ] ) ) {
			$product = wc_get_product( $productId );
			$parent  = $product && $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : $product;
			$brands  = $parent ? wp_get_post_terms( $parent->get_id(), 'product_brand', array( 'fields' => 'ids' ) ) : array();
			
			$cache[ $productId ] = array(
				'categories' => $parent ? (array) $parent->get_category_ids() : array(),
				'tags'       => $parent ? (array) $parent->get_tag_ids() : array(),
				'brands'     => is_wp_error( $brands ) ? array() : (array) $brands,
			);
		}
		
		return $cache[ $productId ];
	}
	
	/**
	 * Default customer resolver.
	 */
	public function readUserRoles( int $userId ): array {
		$user = get_userdata( $userId );
		
		return $user ? (array) $user->roles : array();
	}
}
