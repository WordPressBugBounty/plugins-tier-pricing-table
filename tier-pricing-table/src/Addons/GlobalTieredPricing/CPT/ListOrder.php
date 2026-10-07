<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WP_Query;

/**
 * The rules list shows the rules in the order the storefront checks them (lowest priority number first,
 * newest first among equal numbers) unless a column sort is chosen.
 */
class ListOrder {

	const ORDERBY = 'tpt_order';

	public function __construct() {
		add_filter( 'posts_clauses', array( $this, 'orderClauses' ), 10, 2 );
	}

	public function orderClauses( array $clauses, WP_Query $query ): array {
		if ( ! is_admin() || ! $query->is_main_query() || GlobalTieredPricingCPT::SLUG !== $query->get( 'post_type' ) ) {
			return $clauses;
		}

		$orderby = (string) $query->get( 'orderby' );

		if ( '' !== $orderby && self::ORDERBY !== $orderby ) {
			return $clauses; // another column sorts the list
		}

		global $wpdb;

		// no column chosen: the resolution order (WordPress defaults "order" to DESC, which must not flip it)
		$direction = self::ORDERBY === $orderby && 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';
		$dateOrder = 'ASC' === $direction ? 'DESC' : 'ASC';

		// rules saved before priorities existed have no meta row and sit at the default
		$clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS tpt_priority ON tpt_priority.post_id = {$wpdb->posts}.ID AND tpt_priority.meta_key = '_tpt_priority' ";
		$clauses['orderby'] = sprintf( 'COALESCE( tpt_priority.meta_value + 0, %d ) %s, %s.post_date %s', GlobalPricingRule::DEFAULT_PRIORITY, $direction, $wpdb->posts, $dateOrder );

		return $clauses;
	}
}
