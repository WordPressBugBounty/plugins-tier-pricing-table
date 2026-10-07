<?php namespace TierPricingTable\Addons\Tools\PricingTest;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WC_Product;
use WP_User;

/**
 * Explains why a global pricing rule matches, or does not match, a product and a customer.
 * Pure: reads the rule, the product and the user and returns sentences; PricingTester runs the pricing.
 */
class RuleTester {

	/**
	 * Why a rule matches, or does not match, a product and a customer. Mirrors
	 * GlobalPricingRule::matchRequirements() step by step; the verdict itself comes from that method.
	 *
	 * @return array{matches: bool, reasons: string[]}
	 */
	public function explain( GlobalPricingRule $rule, WP_User $user, WC_Product $product ): array {
		$parent = $product->is_type( array( 'variation', 'subscription-variation' ) ) ? wc_get_product( $product->get_parent_id() ) : $product;
		$parent = $parent ? $parent : $product;

		$productIds = array( (int) $product->get_id(), (int) $parent->get_id() );
		$categories = array_map( 'intval', (array) $parent->get_category_ids() );
		$tags       = array_map( 'intval', (array) $parent->get_tag_ids() );
		$brands     = wp_get_post_terms( $parent->get_id(), 'product_brand', array( 'fields' => 'ids' ) );
		$brands     = is_wp_error( $brands ) ? array() : array_map( 'intval', (array) $brands );
		$roles      = GlobalPricingRule::rolesOf( $user ); // a visitor has the guest pseudo role

		$reasons = array();

		// 1. product exclusions
		if ( array_intersect( $productIds, $rule->getExcludedProducts() ) ) {
			$reasons[] = __( 'The product is listed under the excluded products.', 'tier-pricing-table' );
		}
		foreach ( array(
			array( $categories, $rule->getEffectiveExcludedCategories(), 'product_cat', __( 'The product is in an excluded category: %s.', 'tier-pricing-table' ) ),
			array( $tags, $rule->getExcludedProductTags(), 'product_tag', __( 'The product has an excluded tag: %s.', 'tier-pricing-table' ) ),
			array( $brands, $rule->getExcludedProductBrands(), 'product_brand', __( 'The product has an excluded brand: %s.', 'tier-pricing-table' ) ),
		) as list( $productTerms, $excluded, $taxonomy, $message ) ) {
			$hit = array_intersect( $productTerms, array_map( 'intval', $excluded ) );
			if ( $hit ) {
				$reasons[] = sprintf( $message, $this->termNames( $hit, $taxonomy ) );
			}
		}

		// 2. customer exclusions
		if ( $user->ID && in_array( $user->ID, array_map( 'intval', $rule->getExcludedUsers() ), true ) ) {
			$reasons[] = __( 'The customer is listed under the excluded customers.', 'tier-pricing-table' );
		}
		$excludedRoles = array_intersect( $roles, $rule->getExcludedUserRoles() );
		if ( $excludedRoles ) {
			$reasons[] = sprintf( __( 'The customer has an excluded role: %s.', 'tier-pricing-table' ), $this->roleNames( $excludedRoles ) );
		}

		if ( $reasons ) {
			return array( 'matches' => false, 'reasons' => $reasons );
		}

		// 3. product limitations
		$limited  = false;
		$matched  = array();
		$checks   = array(
			array( $productIds, $rule->getIncludedProducts(), null, __( 'The product is listed under the rule\'s products.', 'tier-pricing-table' ) ),
			array( $categories, $rule->getEffectiveIncludedCategories(), 'product_cat', __( 'The product is in the rule\'s category: %s.', 'tier-pricing-table' ) ),
			array( $tags, $rule->getIncludedProductTags(), 'product_tag', __( 'The product has the rule\'s tag: %s.', 'tier-pricing-table' ) ),
			array( $brands, $rule->getIncludedProductBrands(), 'product_brand', __( 'The product has the rule\'s brand: %s.', 'tier-pricing-table' ) ),
		);
		foreach ( $checks as list( $productTerms, $included, $taxonomy, $message ) ) {
			if ( empty( $included ) ) {
				continue;
			}
			$limited = true;
			$hit     = array_intersect( $productTerms, array_map( 'intval', $included ) );
			if ( $hit ) {
				$matched[] = $taxonomy ? sprintf( $message, $this->termNames( $hit, $taxonomy ) ) : $message;
			}
		}

		if ( $limited && ! $matched ) {
			$scope = array();
			if ( $rule->getIncludedProductCategories() ) {
				$scope[] = sprintf(
					$rule->isIncludeSubcategories()
						/* translators: %s: category names */
						? __( 'categories %s and their subcategories', 'tier-pricing-table' )
						/* translators: %s: category names */
						: __( 'categories %s', 'tier-pricing-table' ),
					$this->termNames( $rule->getIncludedProductCategories(), 'product_cat' )
				);
			}
			if ( $rule->getIncludedProductTags() ) {
				$scope[] = sprintf( __( 'tags %s', 'tier-pricing-table' ), $this->termNames( $rule->getIncludedProductTags(), 'product_tag' ) );
			}
			if ( $rule->getIncludedProductBrands() ) {
				$scope[] = sprintf( __( 'brands %s', 'tier-pricing-table' ), $this->termNames( $rule->getIncludedProductBrands(), 'product_brand' ) );
			}
			if ( $rule->getIncludedProducts() ) {
				$scope[] = sprintf( _n( '%d selected product', '%d selected products', count( $rule->getIncludedProducts() ), 'tier-pricing-table' ), count( $rule->getIncludedProducts() ) );
			}
			$own = $categories ? sprintf( __( 'The product is in %s.', 'tier-pricing-table' ), $this->termNames( $categories, 'product_cat' ) ) : __( 'The product has no category.', 'tier-pricing-table' );

			return array(
				'matches' => false,
				'reasons' => array( sprintf( __( 'The rule is limited to %s, and the product is in none of them.', 'tier-pricing-table' ), implode( '; ', $scope ) ), $own ),
			);
		}

		$reasons = $matched ? $matched : array( __( 'The rule applies to every product (no products, categories, tags or brands selected).', 'tier-pricing-table' ) );

		// 4. and 5. customer limitations
		$includedRoles = $rule->getIncludedUserRoles();
		$includedUsers = array_map( 'intval', $rule->getIncludedUsers() );

		if ( empty( $includedRoles ) && empty( $includedUsers ) ) {
			$reasons[] = __( 'The rule applies to every customer (no roles or customers selected).', 'tier-pricing-table' );

			return array( 'matches' => true, 'reasons' => $reasons );
		}

		if ( $user->ID && in_array( $user->ID, $includedUsers, true ) ) {
			$reasons[] = __( 'The customer is listed under the rule\'s customers.', 'tier-pricing-table' );

			return array( 'matches' => true, 'reasons' => $reasons );
		}

		$roleHit = array_intersect( $roles, $includedRoles );
		if ( $roleHit ) {
			$reasons[] = sprintf( __( 'The customer has the rule\'s role: %s.', 'tier-pricing-table' ), $this->roleNames( $roleHit ) );

			return array( 'matches' => true, 'reasons' => $reasons );
		}

		$who = $user->ID
			? ( $user->roles ? sprintf( __( 'This customer has the roles %s.', 'tier-pricing-table' ), $this->roleNames( $user->roles ) ) : __( 'This customer has no role.', 'tier-pricing-table' ) )
			: __( 'A visitor who is not logged in only matches rules that include "Guests (not logged in)" or have no customer limits.', 'tier-pricing-table' );
		$limits = array();
		if ( $includedRoles ) {
			$limits[] = sprintf( __( 'roles %s', 'tier-pricing-table' ), $this->roleNames( $includedRoles ) );
		}
		if ( $includedUsers ) {
			$limits[] = sprintf( _n( '%d selected customer', '%d selected customers', count( $includedUsers ), 'tier-pricing-table' ), count( $includedUsers ) );
		}

		return array(
			'matches' => false,
			'reasons' => array( sprintf( __( 'The rule is limited to %s.', 'tier-pricing-table' ), implode( ' ' . __( 'and', 'tier-pricing-table' ) . ' ', $limits ) ), $who ),
		);
	}

	protected function termNames( array $ids, string $taxonomy ): string {
		$names = array();
		foreach ( $ids as $id ) {
			$term    = get_term( (int) $id, $taxonomy );
			$names[] = $term && ! is_wp_error( $term ) ? $term->name : '#' . (int) $id;
		}

		return implode( ', ', $names );
	}

	public function roleNames( array $roles ): string {
		$names = array();
		$all   = function_exists( 'wp_roles' ) ? wp_roles()->role_names : array();
		foreach ( $roles as $role ) {
			if ( GlobalPricingRule::GUEST_ROLE === $role ) {
				$names[] = __( 'Guests (not logged in)', 'tier-pricing-table' );
				continue;
			}
			$names[] = isset( $all[ $role ] ) ? translate_user_role( $all[ $role ] ) : $role;
		}

		return implode( ', ', $names );
	}

	/**
	 * Human label of the provider that produced the pricing.
	 */
	public static function providerLabel( string $provider ): string {
		switch ( $provider ) {
			case 'role-based':
				return __( 'a role price on the product', 'tier-pricing-table' );
			case 'user-based':
				return __( 'a customer price on the product', 'tier-pricing-table' );
			case 'global-rules':
				return __( 'a global pricing rule', 'tier-pricing-table' );
			case 'product':
				return __( 'the product\'s own pricing', 'tier-pricing-table' );
			default:
				return $provider ? $provider : __( 'the product\'s own pricing', 'tier-pricing-table' );
		}
	}
}
