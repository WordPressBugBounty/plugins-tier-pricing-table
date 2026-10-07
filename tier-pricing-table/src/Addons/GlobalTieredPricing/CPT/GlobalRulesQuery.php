<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

/**
 * Query arguments for the global pricing rules that take part in pricing.
 */
class GlobalRulesQuery {

	/**
	 * Published rules that are not suspended.
	 *
	 * A "!=" comparison alone would also drop every rule without a "_tpt_is_suspended" row (rules
	 * created before the suspend action existed and not saved since), because the comparison needs
	 * the meta row to exist. Rules without the row are active.
	 *
	 * @param  string  $postType  The rules' post type.
	 *
	 * @return array get_posts() arguments.
	 */
	public static function args( string $postType ): array {
		return array(
			'numberposts' => - 1,
			'post_type'   => $postType,
			'post_status' => 'publish',
			'fields'      => 'ids',
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => '_tpt_is_suspended',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_tpt_is_suspended',
					'value'   => 'yes',
					'compare' => '!=',
				),
			),
		);
	}
}
