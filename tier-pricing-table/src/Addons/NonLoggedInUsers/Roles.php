<?php namespace TierPricingTable\Addons\NonLoggedInUsers;

/**
 * The roles a wholesale rule can name: the customer-facing WordPress roles plus "guest" for visitors
 * who are not logged in.
 */
class Roles {

	const GUEST = 'guest';

	/**
	 * @return array<string, string> role key => label
	 */
	public static function getOptions(): array {
		$options = array( self::GUEST => __( 'Guests (not logged in)', 'tier-pricing-table' ) );

		if ( ! function_exists( 'wp_roles' ) ) {
			return $options;
		}

		foreach ( wp_roles()->roles as $key => $role ) {
			if ( in_array( $key, array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ), true ) ) {
				continue;
			}

			$options[ $key ] = translate_user_role( $role['name'] );
		}

		return $options;
	}

	public static function label( string $role ): string {
		if ( self::GUEST === $role ) {
			return __( 'Guests', 'tier-pricing-table' );
		}

		return self::getOptions()[ $role ] ?? $role;
	}
}
