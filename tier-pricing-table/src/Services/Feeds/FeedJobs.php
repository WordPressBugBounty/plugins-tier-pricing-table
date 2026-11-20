<?php namespace TierPricingTable\Services\Feeds;

use ActionScheduler;
use TierPricingTable\Core\PricingContext;
use Throwable;
use WP_User;

/**
 * Product feed jobs price products as a logged-out visitor.
 *
 * Google checks a feed against the product page as a logged-out visitor sees it. Feed plugins build their feeds
 * in background jobs (Action Scheduler) that run as whoever triggered them: the administrator who saved a product
 * or opened wp-admin, or nobody when WP-Cron runs them. With role prices or pricing rules for some roles, the same
 * feed would then flip between the administrator's prices and the visitor's prices from one run to the next.
 *
 * Feed integrations register the hooks of their jobs here. While one of those jobs runs, the plugin prices
 * everything for a visitor (PricingContext). The WordPress user is not switched.
 *
 * @package TierPricingTable\Services\Feeds
 */
class FeedJobs {

	/**
	 * Hook names of feed jobs. A name ending in "*" matches every hook that starts with it.
	 *
	 * @var string[]
	 */
	protected static $hooks = array();

	/**
	 * Action IDs whose job opened a visitor scope.
	 *
	 * @var int[]
	 */
	protected static $open = array();

	/**
	 * Whether the Action Scheduler hooks are added.
	 *
	 * @var bool
	 */
	protected static $listening = false;

	public static function addJobHook( string $hook ): void {
		if ( '' === $hook ) {
			return;
		}

		self::$hooks[] = $hook;

		self::listen();
	}

	protected static function listen(): void {
		if ( self::$listening ) {
			return;
		}

		self::$listening = true;

		// a scope left open by an action that stopped early (Action Scheduler returns without "after" for a
		// corrupted action) is closed before the next action runs
		add_action( 'action_scheduler_before_execute', array( __CLASS__, 'closeAll' ), 0 );
		add_action( 'action_scheduler_begin_execute', array( __CLASS__, 'begin' ), 0 );
		add_action( 'action_scheduler_after_execute', array( __CLASS__, 'end' ), PHP_INT_MAX );
		add_action( 'action_scheduler_failed_execution', array( __CLASS__, 'end' ), PHP_INT_MAX );
	}

	/**
	 * Opens a visitor scope when the action that starts is a feed job.
	 *
	 * @param  int|string  $actionId
	 */
	public static function begin( $actionId ): void {
		$hook = self::getActionHook( (int) $actionId );

		if ( ! self::isFeedJob( $hook ) ) {
			return;
		}

		PricingContext::begin( new WP_User( 0 ) );

		self::$open[] = (int) $actionId;
	}

	/**
	 * Closes the scope of a feed job that finished or failed.
	 *
	 * @param  int|string  $actionId
	 */
	public static function end( $actionId ): void {
		$index = array_search( (int) $actionId, self::$open, true );

		if ( false === $index ) {
			return;
		}

		unset( self::$open[ $index ] );

		PricingContext::end();
	}

	/**
	 * A feed batch that runs inside an ordinary request (an administrator's admin-ajax call): the rest of the
	 * request is priced as a visitor. Inside a watched job, or when a scope is already open, nothing changes.
	 */
	public static function beginForRequest(): void {
		if ( PricingContext::isActive() ) {
			return;
		}

		PricingContext::begin( new WP_User( 0 ) );

		// closed with the scopes of jobs, so an Action Scheduler run that goes on to other actions is not affected
		self::$open[] = 0;
		self::listen();
	}

	public static function closeAll(): void {
		while ( ! empty( self::$open ) ) {
			array_pop( self::$open );
			PricingContext::end();
		}
	}

	public static function isFeedJob( string $hook ): bool {
		if ( '' === $hook ) {
			return false;
		}

		$hooks = (array) apply_filters( 'tiered_pricing_table/feeds/job_hooks', self::$hooks );

		foreach ( $hooks as $jobHook ) {
			$jobHook = (string) $jobHook;

			if ( '*' === substr( $jobHook, - 1 ) ? 0 === strpos( $hook, substr( $jobHook, 0, - 1 ) ) : $hook === $jobHook ) {
				return true;
			}
		}

		return false;
	}

	protected static function getActionHook( int $actionId ): string {
		if ( ! $actionId || ! class_exists( 'ActionScheduler' ) ) {
			return '';
		}

		try {
			$action = ActionScheduler::store()->fetch_action( (string) $actionId );
		} catch ( Throwable $e ) {
			return '';
		}

		return $action && method_exists( $action, 'get_hook' ) ? (string) $action->get_hook() : '';
	}
}
