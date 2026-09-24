<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;

/**
 * Cart-level rules per role. Pure: no WordPress calls, so it is unit-tested on its own.
 *
 * Minimums are [ role => [ 'amount' => float, 'quantity' => int ] ]; a customer with several roles
 * gets the strictest minimum. Method allow-lists are role arrays; an empty list allows everyone.
 */
class CartRules {

	/**
	 * @param  mixed  $raw  JSON string or array, as stored or posted
	 *
	 * @return array<string, array{amount: float, quantity: int}> rows with at least one minimum
	 */
	public static function normalizeMinimums( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}

		$minimums = array();

		foreach ( is_array( $raw ) ? $raw : array() as $role => $row ) {
			$role = strtolower( trim( (string) preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $role ) ) );

			if ( '' === $role || ! is_array( $row ) ) {
				continue;
			}

			$amount   = max( 0.0, (float) str_replace( ',', '.', (string) ( $row['amount'] ?? '' ) ) );
			$quantity = max( 0, (int) ( $row['quantity'] ?? 0 ) );

			if ( $amount > 0 || $quantity > 0 ) {
				$minimums[ $role ] = array( 'amount' => $amount, 'quantity' => $quantity );
			}
		}

		return $minimums;
	}

	/**
	 * The minimums that apply to a visitor: the strictest among their roles.
	 *
	 * @return array{amount: float, quantity: int}
	 */
	public static function pickMinimums( array $minimums, array $roles, bool $isGuest ): array {
		$picked = array( 'amount' => 0.0, 'quantity' => 0 );

		foreach ( $isGuest ? array( Roles::GUEST ) : $roles as $role ) {
			if ( ! isset( $minimums[ $role ] ) ) {
				continue;
			}

			$picked['amount']   = max( $picked['amount'], (float) ( $minimums[ $role ]['amount'] ?? 0 ) );
			$picked['quantity'] = max( $picked['quantity'], (int) ( $minimums[ $role ]['quantity'] ?? 0 ) );
		}

		return $picked;
	}

	/**
	 * Which minimums the cart misses.
	 *
	 * @return array{amount?: float, quantity?: int} the missed minimum(s)
	 */
	public static function missedMinimums( array $picked, float $subtotal, int $count ): array {
		$missed = array();

		if ( $picked['amount'] > 0 && $subtotal + 0.00001 < $picked['amount'] ) {
			$missed['amount'] = (float) $picked['amount'];
		}

		if ( $picked['quantity'] > 0 && $count < $picked['quantity'] ) {
			$missed['quantity'] = (int) $picked['quantity'];
		}

		return $missed;
	}

	/**
	 * Whether a visitor may use a method: an empty allow-list allows everyone.
	 */
	public static function isAllowed( array $allowedRoles, array $roles, bool $isGuest ): bool {
		$allowedRoles = array_values( array_filter( array_map( 'strval', $allowedRoles ), 'strlen' ) );

		if ( ! $allowedRoles ) {
			return true;
		}

		return $isGuest ? in_array( Roles::GUEST, $allowedRoles, true ) : (bool) array_intersect( $roles, $allowedRoles );
	}
}
