<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WP_Query;

/**
 * The views above the rules list: Active, Scheduled, Expired, Suspended and Skipped with their counts,
 * each filtering the list. They replace WordPress's "Published" view, which they add up to; All, Drafts
 * and Trash stay.
 */
class StatusViews {

	const QUERY_VAR = 'tpt_status';

	public function __construct() {
		add_filter( 'views_edit-' . GlobalTieredPricingCPT::SLUG, array( $this, 'views' ) );
		add_action( 'pre_get_posts', array( $this, 'filterList' ) );
	}

	/**
	 * @return array<string, string> status => label, in display order.
	 */
	public static function labels(): array {
		return array(
			'active'    => __( 'Active', 'tier-pricing-table' ),
			'scheduled' => __( 'Scheduled', 'tier-pricing-table' ),
			'expired'   => __( 'Expired', 'tier-pricing-table' ),
			'suspended' => __( 'Suspended', 'tier-pricing-table' ),
			'skipped'   => __( 'Skipped', 'tier-pricing-table' ),
		);
	}

	/**
	 * Published rules grouped by effective status (ids), computed once per request.
	 *
	 * @return array<string, int[]>
	 */
	public static function groups(): array {
		static $groups = null;

		if ( null !== $groups ) {
			return $groups;
		}

		$groups = array_fill_keys( array_keys( self::labels() ), array() );

		$ids = get_posts( array(
			'post_type'   => GlobalTieredPricingCPT::SLUG,
			'post_status' => 'publish',
			'numberposts' => - 1,
			'fields'      => 'ids',
		) );

		foreach ( $ids as $id ) {
			$groups[ GlobalPricingRule::build( $id )->getEffectiveStatus() ][] = (int) $id;
		}

		return $groups;
	}

	public static function current(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only list filter
		$status = isset( $_GET[ self::QUERY_VAR ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) ) : '';

		return isset( self::labels()[ $status ] ) ? $status : '';
	}

	public function views( array $views ): array {
		$groups  = self::groups();
		$current = self::current();
		$added   = array();

		foreach ( self::labels() as $status => $label ) {
			$count = count( $groups[ $status ] );

			if ( ! $count ) {
				continue; // like WordPress, empty statuses are not listed
			}

			$added[ 'tpt_' . $status ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%s)</span></a>',
				esc_url( add_query_arg( array( 'post_type' => GlobalTieredPricingCPT::SLUG, self::QUERY_VAR => $status ), admin_url( 'edit.php' ) ) ),
				$current === $status ? ' class="current" aria-current="page"' : '',
				esc_html( $label ),
				esc_html( number_format_i18n( $count ) )
			);
		}

		unset( $views['publish'] );

		$result = array();
		foreach ( $views as $key => $view ) {
			$result[ $key ] = $view;
			if ( 'all' === $key ) {
				$result += $added;
			}
		}

		if ( ! isset( $views['all'] ) ) {
			$result = $added + $result;
		}

		return $result;
	}

	public function filterList( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || GlobalTieredPricingCPT::SLUG !== $query->get( 'post_type' ) ) {
			return;
		}

		$status = self::current();

		if ( ! $status ) {
			return;
		}

		$ids = self::groups()[ $status ];

		$query->set( 'post_status', 'publish' );
		$query->set( 'post__in', $ids ? $ids : array( 0 ) );
	}
}
