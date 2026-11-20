<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

/**
 * Finds global pricing rules that can meet on the same product for the same customer at the same time,
 * so the admin learns which rule wins instead of finding out on the storefront.
 *
 * Scopes are compared the way the matcher reads them: a rule without product limits covers every product
 * except its exclusions, a rule without customer limits covers every customer (visitors included) except
 * its exclusions. Listed products are resolved to their terms and listed customers to their roles through
 * the callbacks, so the check stays testable.
 *
 * The rules list asks this for every row against every rule, so each rule is read once into a profile (sets of
 * ids, its schedule, its limits), the terms of every listed product are fetched together, and the cheap checks
 * (priority, kind, schedule) run before the scope checks.
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
	
	/**
	 * @var callable(int): ?string The attribute (taxonomy) of an attribute value's term id.
	 */
	protected $attributeOf;

	/**
	 * Whether product terms come from the store (they can then be fetched for many products at once).
	 *
	 * @var bool
	 */
	protected $readsStoreTerms;

	/**
	 * Each rule read once: GlobalPricingRule => profile array (see profile()).
	 *
	 * @var \SplObjectStorage
	 */
	protected $profiles;

	/**
	 * Product id => array{categories: array<int, true>, tags: array<int, true>, brands: array<int, true>}
	 *
	 * @var array
	 */
	protected $termSets = array();

	/**
	 * User id => array<string, true> of roles.
	 *
	 * @var array
	 */
	protected $roleSets = array();
	
	public function __construct( ?callable $productTerms = null, ?callable $userRoles = null, ?int $now = null, ?callable $attributeOf = null ) {
		$this->productTerms    = $productTerms ? $productTerms : array( $this, 'readProductTerms' );
		$this->userRoles       = $userRoles ? $userRoles : array( $this, 'readUserRoles' );
		$this->now             = null === $now ? time() : $now;
		$this->attributeOf     = $attributeOf ? $attributeOf : array( $this, 'readAttributeOf' );
		$this->readsStoreTerms = ! $productTerms;
		$this->profiles        = new \SplObjectStorage();
	}
	
	/**
	 * Rules among $rules that overlap with $rule, in the order given (the rule itself is skipped).
	 *
	 * @param  GlobalPricingRule[]  $rules
	 *
	 * @return GlobalPricingRule[]
	 */
	public function findOverlapping( GlobalPricingRule $rule, array $rules ): array {
		return $this->find( $rule, $rules, false );
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
		return $this->find( $rule, $rules, true );
	}

	/**
	 * @param  GlobalPricingRule[]  $rules
	 *
	 * @return GlobalPricingRule[]
	 */
	protected function find( GlobalPricingRule $rule, array $rules, bool $onlyOutranking ): array {
		$this->prefetch( array_merge( array( $rule ), $rules ) );

		$found = array();

		foreach ( $rules as $other ) {
			if ( (int) $other->id === (int) $rule->id ) {
				continue;
			}

			// the priority check is the cheapest, so it goes first
			if ( $onlyOutranking && ! $other->outranks( $rule ) ) {
				continue;
			}

			if ( $this->overlap( $rule, $other ) ) {
				$found[] = $other;
			}
		}

		return $found;
	}

	public function overlap( GlobalPricingRule $a, GlobalPricingRule $b ): bool {
		$pa = $this->profile( $a );
		$pb = $this->profile( $b );

		// a product rule and an order-total rule never compete: one prices products, the other discounts the cart
		if ( $pa['kind'] !== $pb['kind'] ) {
			return false;
		}

		// the date windows still share a moment from now on (open ends count as unbounded)
		$start = max( $pa['start'], $pb['start'] );
		$end   = min( $pa['end'], $pb['end'] );

		if ( $start > $end || $end < $this->now ) {
			return false;
		}

		return $this->customersIntersect( $pa, $pb ) && $this->productsIntersect( $pa, $pb );
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
	
	protected function productsIntersect( array $a, array $b ): bool {
		if ( $this->attributesApart( $a, $b ) ) {
			return false;
		}

		if ( ! $a['productLimits'] && ! $b['productLimits'] ) {
			return true;
		}

		if ( ! $a['productLimits'] ) {
			return ! $this->productScopeExcludedBy( $b, $a );
		}

		if ( ! $b['productLimits'] ) {
			return ! $this->productScopeExcludedBy( $a, $b );
		}

		return $this->productScopesShare( $a, $b );
	}

	/**
	 * Whether the rules' attribute values keep them on different items: both ask for different values of the
	 * same attribute (Print: Laser vs Print: Screen), or one excludes every value the other asks for. A
	 * product with both values (not a variation) is not counted: such rules compete only on it.
	 */
	protected function attributesApart( array $a, array $b ): bool {
		foreach ( array_intersect_key( $a['attributes'], $b['attributes'] ) as $attribute => $values ) {
			if ( ! array_intersect_key( $values, $b['attributes'][ $attribute ] ) ) {
				return true;
			}
		}

		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $including, $other ) ) {
			if ( ! $other['excludedAttributes'] ) {
				continue;
			}

			foreach ( $including['attributes'] as $values ) {
				if ( ! array_diff_key( $values, $other['excludedAttributes'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Default attribute resolver.
	 */
	public function readAttributeOf( int $termId ): ?string {
		$term = get_term( $termId );

		return $term instanceof \WP_Term ? $term->taxonomy : null;
	}

	/**
	 * Whether everything the limited rule includes is excluded by the open (store-wide) rule.
	 */
	protected function productScopeExcludedBy( array $limited, array $open ): bool {
		foreach ( array( 'categories', 'tags', 'brands' ) as $dimension ) {
			if ( array_diff_key( $limited['included'][ $dimension ], $open['excluded'][ $dimension ] ) ) {
				return false;
			}
		}

		foreach ( $limited['included']['products'] as $productId => $true ) {
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
	protected function productScopesShare( array $a, array $b ): bool {
		foreach ( array( 'products', 'categories', 'tags', 'brands' ) as $dimension ) {
			if ( self::share( $a['included'][ $dimension ], $b['included'][ $dimension ] ) ) {
				return true;
			}
		}

		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $listing, $other ) ) {
			// only a rule with terms of its own can take in a product listed by the other one
			if ( ! $other['included']['categories'] && ! $other['included']['tags'] && ! $other['included']['brands'] ) {
				continue;
			}

			foreach ( $listing['included']['products'] as $productId => $true ) {
				if ( $this->productExcludedBy( $productId, $other ) ) {
					continue;
				}

				$terms = $this->termSet( $productId );

				if ( self::share( $terms['categories'], $other['included']['categories'] )
				     || self::share( $terms['tags'], $other['included']['tags'] )
				     || self::share( $terms['brands'], $other['included']['brands'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether a product never matches a rule because of the rule's exclusions.
	 */
	protected function productExcludedBy( int $productId, array $rule ): bool {
		if ( isset( $rule['excluded']['products'][ $productId ] ) ) {
			return true;
		}

		if ( ! $rule['excluded']['categories'] && ! $rule['excluded']['tags'] && ! $rule['excluded']['brands'] ) {
			return false;
		}

		$terms = $this->termSet( $productId );

		return self::share( $terms['categories'], $rule['excluded']['categories'] )
		       || self::share( $terms['tags'], $rule['excluded']['tags'] )
		       || self::share( $terms['brands'], $rule['excluded']['brands'] );
	}

	protected function customersIntersect( array $a, array $b ): bool {
		if ( ! $a['customerLimits'] && ! $b['customerLimits'] ) {
			return true;
		}

		if ( ! $a['customerLimits'] ) {
			return ! $this->customerScopeExcludedBy( $b, $a );
		}

		if ( ! $b['customerLimits'] ) {
			return ! $this->customerScopeExcludedBy( $a, $b );
		}

		if ( self::share( $a['roles'], $b['roles'] ) || self::share( $a['users'], $b['users'] ) ) {
			return true;
		}

		// a customer listed in one rule who has a role the other rule targets
		foreach ( array( array( $a, $b ), array( $b, $a ) ) as list( $listing, $other ) ) {
			if ( ! $other['roles'] ) {
				continue;
			}

			foreach ( $listing['users'] as $userId => $true ) {
				if ( $this->userExcludedBy( $userId, $other ) ) {
					continue;
				}

				if ( self::share( $this->roleSet( $userId ), $other['roles'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Whether every customer the limited rule targets is excluded by the open (every-customer) rule.
	 */
	protected function customerScopeExcludedBy( array $limited, array $open ): bool {
		if ( array_diff_key( $limited['roles'], $open['excludedRoles'] ) ) {
			return false;
		}

		foreach ( $limited['users'] as $userId => $true ) {
			if ( ! $this->userExcludedBy( $userId, $open ) ) {
				return false;
			}
		}

		return true;
	}

	protected function userExcludedBy( int $userId, array $rule ): bool {
		if ( isset( $rule['excludedUsers'][ $userId ] ) ) {
			return true;
		}

		return $rule['excludedRoles'] && self::share( $this->roleSet( $userId ), $rule['excludedRoles'] );
	}

	/**
	 * A rule read once: its kind, schedule, limits and every id list as a set (id => true).
	 */
	protected function profile( GlobalPricingRule $rule ): array {
		if ( $this->profiles->contains( $rule ) ) {
			return $this->profiles[ $rule ];
		}

		$included = array(
			'products'   => self::set( $rule->getIncludedProducts() ),
			'categories' => self::set( $rule->getEffectiveIncludedCategories() ),
			'tags'       => self::set( $rule->getIncludedProductTags() ),
			'brands'     => self::set( $rule->getIncludedProductBrands() ),
		);

		$attributes = array();

		foreach ( self::ints( $rule->getIncludedAttributes() ) as $termId ) {
			$attribute = call_user_func( $this->attributeOf, $termId );

			if ( $attribute ) {
				$attributes[ $attribute ][ $termId ] = true;
			}
		}

		$profile = array(
			'kind'               => $rule->getKind(),
			'start'              => $rule->getStartTimestamp() ?? PHP_INT_MIN,
			'end'                => $rule->getEndTimestamp() ?? PHP_INT_MAX,
			'included'           => $included,
			'excluded'           => array(
				'products'   => self::set( $rule->getExcludedProducts() ),
				'categories' => self::set( $rule->getEffectiveExcludedCategories() ),
				'tags'       => self::set( $rule->getExcludedProductTags() ),
				'brands'     => self::set( $rule->getExcludedProductBrands() ),
			),
			'productLimits'      => (bool) ( $included['products'] || $included['categories'] || $included['tags'] || $included['brands'] ),
			'attributes'         => $attributes,
			'excludedAttributes' => self::set( $rule->getExcludedAttributes() ),
			'roles'              => self::stringSet( $rule->getIncludedUserRoles() ),
			'excludedRoles'      => self::stringSet( $rule->getExcludedUserRoles() ),
			'users'              => self::set( $rule->getIncludedUsers() ),
			'excludedUsers'      => self::set( $rule->getExcludedUsers() ),
		);

		$profile['customerLimits'] = (bool) ( $profile['roles'] || $profile['users'] );

		$this->profiles[ $rule ] = $profile;

		return $profile;
	}

	/**
	 * Reads the terms of every product and the roles of every customer the rules list, in a few queries
	 * instead of one product or user at a time.
	 *
	 * @param  GlobalPricingRule[]  $rules
	 */
	protected function prefetch( array $rules ): void {
		$products = array();
		$users    = array();

		foreach ( $rules as $rule ) {
			$profile   = $this->profile( $rule );
			$products += $profile['included']['products'];
			$users    += $profile['users'];
		}

		$products = array_keys( array_diff_key( $products, $this->termSets ) );
		$users    = array_keys( array_diff_key( $users, $this->roleSets ) );

		if ( $products && $this->readsStoreTerms ) {
			$this->fetchProductTerms( $products );
		}

		if ( $users && function_exists( 'cache_users' ) ) {
			cache_users( $users );
		}
	}

	/**
	 * The terms of many products at once: a variation takes its parent product's terms.
	 *
	 * @param  int[]  $productIds
	 */
	protected function fetchProductTerms( array $productIds ): void {
		$taxonomies = array_values( array_filter( array( 'product_cat', 'product_tag', 'product_brand' ), 'taxonomy_exists' ) );
		$keys       = array( 'product_cat' => 'categories', 'product_tag' => 'tags', 'product_brand' => 'brands' );

		foreach ( array_chunk( $productIds, 500 ) as $chunk ) {
			// id => parent id (0 for a product)
			$parents = get_posts( array(
				'post_type'        => array( 'product', 'product_variation' ),
				'post_status'      => 'any',
				'post__in'         => $chunk,
				'posts_per_page'   => count( $chunk ),
				'fields'           => 'id=>parent',
				'orderby'          => 'none',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			) );

			$owners = array();

			foreach ( $chunk as $productId ) {
				$owners[ $productId ] = isset( $parents[ $productId ] ) && $parents[ $productId ] ? (int) $parents[ $productId ] : $productId;
			}

			$byOwner = array();

			if ( $taxonomies ) {
				$terms = wp_get_object_terms( array_values( array_unique( $owners ) ), $taxonomies, array( 'fields' => 'all_with_object_id' ) );

				foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
					$byOwner[ (int) $term->object_id ][ $keys[ $term->taxonomy ] ][ (int) $term->term_id ] = true;
				}
			}

			foreach ( $owners as $productId => $owner ) {
				$this->termSets[ $productId ] = array(
					'categories' => $byOwner[ $owner ]['categories'] ?? array(),
					'tags'       => $byOwner[ $owner ]['tags'] ?? array(),
					'brands'     => $byOwner[ $owner ]['brands'] ?? array(),
				);
			}
		}
	}

	/**
	 * @return array{categories: array<int, true>, tags: array<int, true>, brands: array<int, true>}
	 */
	protected function termSet( int $productId ): array {
		if ( ! isset( $this->termSets[ $productId ] ) ) {
			$terms = (array) call_user_func( $this->productTerms, $productId );

			$this->termSets[ $productId ] = array(
				'categories' => self::set( $terms['categories'] ?? array() ),
				'tags'       => self::set( $terms['tags'] ?? array() ),
				'brands'     => self::set( $terms['brands'] ?? array() ),
			);
		}

		return $this->termSets[ $productId ];
	}

	/**
	 * @return array<string, true>
	 */
	protected function roleSet( int $userId ): array {
		if ( ! isset( $this->roleSets[ $userId ] ) ) {
			$this->roleSets[ $userId ] = self::stringSet( (array) call_user_func( $this->userRoles, $userId ) );
		}

		return $this->roleSets[ $userId ];
	}

	/**
	 * Whether two sets (value => true) have a value in common.
	 */
	protected static function share( array $a, array $b ): bool {
		if ( ! $a || ! $b ) {
			return false;
		}

		if ( count( $a ) > count( $b ) ) {
			list( $a, $b ) = array( $b, $a );
		}

		foreach ( $a as $value => $true ) {
			if ( isset( $b[ $value ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array<int, true>
	 */
	protected static function set( array $values ): array {
		return array_fill_keys( self::ints( $values ), true );
	}

	/**
	 * @return array<string, true>
	 */
	protected static function stringSet( array $values ): array {
		$set = array();

		foreach ( $values as $value ) {
			if ( '' !== (string) $value ) {
				$set[ (string) $value ] = true;
			}
		}

		return $set;
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
