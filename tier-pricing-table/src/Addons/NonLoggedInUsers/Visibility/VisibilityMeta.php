<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;

/**
 * Where the visibility rules live: product meta and product_cat term meta.
 */
class VisibilityMeta {

	const PRODUCT_MODE  = '_tpt_visibility_mode';
	const PRODUCT_ROLES = '_tpt_visibility_roles';

	const TERM_MODE  = 'tpt_visibility_mode';
	const TERM_ROLES = 'tpt_visibility_roles';

	/** Bumped whenever a rule or a product's categories change; part of every cache key. */
	const VERSION_OPTION = 'tpt_visibility_version';

	public static function getProductRule( int $productId ): array {
		return VisibilityRule::normalize(
			get_post_meta( $productId, self::PRODUCT_MODE, true ),
			get_post_meta( $productId, self::PRODUCT_ROLES, true )
		);
	}

	public static function saveProductRule( int $productId, $mode, $roles ) {
		$rule = VisibilityRule::normalize( $mode, $roles );

		if ( VisibilityRule::MODE_INHERIT === $rule['mode'] ) {
			delete_post_meta( $productId, self::PRODUCT_MODE );
			delete_post_meta( $productId, self::PRODUCT_ROLES );
		} else {
			update_post_meta( $productId, self::PRODUCT_MODE, $rule['mode'] );
			update_post_meta( $productId, self::PRODUCT_ROLES, implode( ',', $rule['roles'] ) );
		}

		self::bumpVersion();
	}

	public static function getCategoryRule( int $termId ): array {
		$rule = VisibilityRule::normalize(
			get_term_meta( $termId, self::TERM_MODE, true ),
			get_term_meta( $termId, self::TERM_ROLES, true )
		);

		// a category has no parent rule to inherit from; "inherit" simply means no restriction
		if ( VisibilityRule::MODE_INHERIT === $rule['mode'] ) {
			$rule['mode'] = VisibilityRule::MODE_EVERYONE;
		}

		return $rule;
	}

	public static function saveCategoryRule( int $termId, $mode, $roles ) {
		$rule = VisibilityRule::normalize( $mode, $roles );

		if ( ! VisibilityRule::isRestricting( $rule ) ) {
			delete_term_meta( $termId, self::TERM_MODE );
			delete_term_meta( $termId, self::TERM_ROLES );
		} else {
			update_term_meta( $termId, self::TERM_MODE, $rule['mode'] );
			update_term_meta( $termId, self::TERM_ROLES, implode( ',', $rule['roles'] ) );
		}

		self::bumpVersion();
	}

	/**
	 * The rules of every category of a product, parents included.
	 *
	 * @return array[]
	 */
	public static function getProductCategoryRules( int $productId ): array {
		$termIds = wp_get_post_terms( $productId, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $termIds ) || empty( $termIds ) ) {
			return array();
		}

		$all = array();

		foreach ( $termIds as $termId ) {
			$all[] = (int) $termId;

			foreach ( get_ancestors( (int) $termId, 'product_cat', 'taxonomy' ) as $ancestor ) {
				$all[] = (int) $ancestor;
			}
		}

		$rules = array();

		foreach ( array_unique( $all ) as $termId ) {
			$rule = self::getCategoryRule( $termId );

			if ( VisibilityRule::isRestricting( $rule ) ) {
				$rules[] = $rule;
			}
		}

		return $rules;
	}

	/**
	 * Term ids of the categories that carry a restricting rule.
	 *
	 * @return int[]
	 */
	public static function getRestrictedCategoryIds(): array {
		$terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => self::TERM_MODE,
					'value'   => array( VisibilityRule::MODE_INCLUDE, VisibilityRule::MODE_EXCLUDE ),
					'compare' => 'IN',
				),
			),
			'tpt_visibility' => true, // our own get_terms filter leaves this query alone
		) );

		return is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );
	}

	/**
	 * The roles a rule can name, for the admin checkbox lists: customer-facing roles plus guests.
	 *
	 * @return array<string, string> role key => label
	 */
	public static function getRoleOptions(): array {
		return Roles::getOptions();
	}

	public static function getModeOptions( bool $forProduct ): array {
		$options = array();

		if ( $forProduct ) {
			$options[ VisibilityRule::MODE_INHERIT ] = __( 'Follow the category rules (default)', 'tier-pricing-table' );
		}

		$options[ VisibilityRule::MODE_EVERYONE ] = __( 'Everyone', 'tier-pricing-table' );
		$options[ VisibilityRule::MODE_INCLUDE ]  = __( 'Only the selected roles', 'tier-pricing-table' );
		$options[ VisibilityRule::MODE_EXCLUDE ]  = __( 'Everyone except the selected roles', 'tier-pricing-table' );

		return $options;
	}

	public static function getVersion(): string {
		return (string) get_option( self::VERSION_OPTION, '1' );
	}

	public static function bumpVersion() {
		update_option( self::VERSION_OPTION, (string) time(), false );
	}
}
