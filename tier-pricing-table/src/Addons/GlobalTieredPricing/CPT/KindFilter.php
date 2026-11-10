<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WP_Query;

/**
 * A rule type filter on the rules list: product pricing rules or order rules, shown once an Order rule
 * exists. Rules saved before the types existed have no type meta and count as product pricing rules.
 */
class KindFilter {

	const QUERY_VAR = 'tpt_rule_kind';

	public function __construct() {
		add_action( 'restrict_manage_posts', array( $this, 'renderDropdown' ) );
		add_action( 'pre_get_posts', array( $this, 'filterQuery' ) );
	}

	/**
	 * The chosen type from the request, or an empty string for all.
	 */
	public static function getRequestedKind(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a read-only list filter
		$kind = isset( $_GET[ self::QUERY_VAR ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_VAR ] ) ) : '';

		return in_array( $kind, GlobalPricingRule::getKinds(), true ) ? $kind : '';
	}

	/**
	 * The type dropdown next to the list's filters.
	 *
	 * @param  mixed  $postType
	 */
	public function renderDropdown( $postType ) {
		// the filter has a point once an Order rule exists
		if ( GlobalTieredPricingCPT::SLUG !== $postType || ! GlobalTieredPricingCPT::hasOrderRules() ) {
			return;
		}

		$current = self::getRequestedKind();
		$options = array(
			''                               => __( 'All rule types', 'tier-pricing-table' ),
			GlobalPricingRule::KIND_PRODUCT  => __( 'Product prices', 'tier-pricing-table' ),
			GlobalPricingRule::KIND_ORDER    => __( 'Cart rules', 'tier-pricing-table' ),
		);
		?>
		<label for="tpt-rule-kind-filter" class="screen-reader-text"><?php esc_html_e( 'Filter by rule type', 'tier-pricing-table' ); ?></label>
		<select name="<?php echo esc_attr( self::QUERY_VAR ); ?>" id="tpt-rule-kind-filter">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function filterQuery( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || GlobalTieredPricingCPT::SLUG !== $query->get( 'post_type' ) ) {
			return;
		}

		$kind = self::getRequestedKind();

		if ( '' === $kind ) {
			return;
		}

		if ( GlobalPricingRule::KIND_PRODUCT === $kind ) {
			$clause = array(
				'relation' => 'OR',
				array( 'key' => '_tpt_rule_kind', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_tpt_rule_kind', 'value' => GlobalPricingRule::KIND_PRODUCT ),
			);
		} else {
			$clause = array( 'key' => '_tpt_rule_kind', 'value' => $kind );
		}

		$metaQuery = (array) $query->get( 'meta_query' );

		$metaQuery[] = $clause;

		$query->set( 'meta_query', $metaQuery );
	}
}
