<?php namespace TierPricingTable\Core;

/**
 * Knows when a REST request is being dispatched: a request to the REST API itself, or an internal
 * rest_do_request() during a page render (the wishlist block prefetches its items that way).
 *
 * Markup that only the frontend script can complete, like the savings badge, stays out of those responses:
 * their consumers render the price elsewhere, often through a sanitizer that keeps the bare text.
 */
class RestContext {

	/**
	 * Dispatches in progress (batch requests nest them).
	 *
	 * @var int
	 */
	protected static $depth = 0;

	/**
	 * @var bool
	 */
	protected static $hooked = false;

	public static function init() {

		if ( self::$hooked ) {
			return;
		}

		self::$hooked = true;

		add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'enter' ), 1 );
		add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'leave' ), PHP_INT_MAX );
	}

	/**
	 * @param  mixed  $response
	 *
	 * @return mixed
	 */
	public static function enter( $response ) {
		self::$depth ++;

		return $response;
	}

	/**
	 * @param  mixed  $response
	 *
	 * @return mixed
	 */
	public static function leave( $response ) {
		self::$depth = max( 0, self::$depth - 1 );

		return $response;
	}

	public static function isDispatching(): bool {
		return self::$depth > 0 || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
	}
}
