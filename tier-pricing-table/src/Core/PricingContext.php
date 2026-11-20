<?php namespace TierPricingTable\Core;

use Throwable;
use WP_User;

/**
 * Who the plugin's prices are calculated for, for a stretch of code: a product feed job prices products as a
 * logged-out visitor, whoever happens to run the job.
 *
 * Only the plugin's own pricing follows the scope (TierPricingTablePlugin::getCurrentUser()). The WordPress user,
 * nonces, capabilities and other plugins stay as they are.
 *
 * @package TierPricingTable\Core
 */
class PricingContext {

	/**
	 * Open scopes, innermost last.
	 *
	 * @var WP_User[]
	 */
	protected static $stack = array();

	/**
	 * Price everything inside $callback for $user (new WP_User( 0 ) is a visitor).
	 *
	 * @param  WP_User  $user
	 * @param  callable  $callback
	 *
	 * @return mixed What the callback returns.
	 * @throws Throwable What the callback throws, after the scope is closed.
	 */
	public static function runAs( WP_User $user, callable $callback ) {
		self::begin( $user );

		try {
			return $callback();
		} finally {
			self::end();
		}
	}

	public static function runAsGuest( callable $callback ) {
		return self::runAs( new WP_User( 0 ), $callback );
	}

	/**
	 * The plugin's rule cache is kept per user (PriceManager), so opening and closing a scope keeps the rules
	 * already built for either user.
	 */
	public static function begin( WP_User $user ): void {
		self::$stack[] = $user;
	}

	public static function end(): void {
		if ( empty( self::$stack ) ) {
			return;
		}

		array_pop( self::$stack );
	}

	/**
	 * The user of the innermost open scope, or null outside any scope.
	 */
	public static function current(): ?WP_User {
		return empty( self::$stack ) ? null : end( self::$stack );
	}

	public static function isActive(): bool {
		return ! empty( self::$stack );
	}
}
