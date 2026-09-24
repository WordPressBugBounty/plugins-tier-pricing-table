<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

/**
 * Who may see a product or a category. Pure: no WordPress calls, so it is unit-tested on its own.
 *
 * A rule is [ 'mode' => everyone|include|exclude|inherit, 'roles' => string[] ]. Roles are WordPress role
 * keys plus the pseudo role "guest" for visitors who are not logged in.
 */
class VisibilityRule {

	const MODE_INHERIT  = 'inherit';
	const MODE_EVERYONE = 'everyone';
	const MODE_INCLUDE  = 'include';
	const MODE_EXCLUDE  = 'exclude';

	const GUEST = 'guest';

	public static function getModes(): array {
		return array( self::MODE_INHERIT, self::MODE_EVERYONE, self::MODE_INCLUDE, self::MODE_EXCLUDE );
	}

	public static function normalize( $mode, $roles ): array {
		$mode = in_array( $mode, self::getModes(), true ) ? $mode : self::MODE_INHERIT;

		if ( is_string( $roles ) ) {
			$roles = explode( ',', $roles );
		}

		$roles = array_values( array_unique( array_filter( array_map( function ( $role ) {
			return strtolower( trim( (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $role ) ) );
		}, (array) $roles ), 'strlen' ) ) );

		return array( 'mode' => $mode, 'roles' => $roles );
	}

	/**
	 * Whether the rule restricts anybody at all.
	 */
	public static function isRestricting( array $rule ): bool {
		return in_array( $rule['mode'] ?? '', array( self::MODE_INCLUDE, self::MODE_EXCLUDE ), true );
	}

	/**
	 * Whether a visitor with these roles passes a single rule.
	 *
	 * @param  array  $rule
	 * @param  string[]  $userRoles  the visitor's roles; empty for a guest
	 * @param  bool  $isGuest
	 */
	public static function canSee( array $rule, array $userRoles, bool $isGuest ): bool {
		$mode  = $rule['mode'] ?? self::MODE_INHERIT;
		$roles = (array) ( $rule['roles'] ?? array() );

		if ( ! self::isRestricting( $rule ) ) {
			return true;
		}

		$matches = $isGuest ? in_array( self::GUEST, $roles, true ) : (bool) array_intersect( $userRoles, $roles );

		return self::MODE_INCLUDE === $mode ? $matches : ! $matches;
	}

	/**
	 * Whether a product is visible: its own rule decides when it has one, otherwise every rule of its
	 * categories (and their parents) must let the visitor through.
	 *
	 * @param  array  $productRule
	 * @param  array[]  $categoryRules
	 * @param  string[]  $userRoles
	 * @param  bool  $isGuest
	 */
	public static function isProductVisible( array $productRule, array $categoryRules, array $userRoles, bool $isGuest ): bool {
		if ( self::MODE_INHERIT !== ( $productRule['mode'] ?? self::MODE_INHERIT ) ) {
			return self::canSee( $productRule, $userRoles, $isGuest );
		}

		foreach ( $categoryRules as $categoryRule ) {
			if ( ! self::canSee( $categoryRule, $userRoles, $isGuest ) ) {
				return false;
			}
		}

		return true;
	}
}
