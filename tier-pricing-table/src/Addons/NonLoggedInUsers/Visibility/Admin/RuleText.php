<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;

/**
 * A rule in words, for the list-table columns.
 */
class RuleText {

	public static function describe( array $rule ): string {
		$roles = self::roleLabels( $rule['roles'] ?? array() );

		switch ( $rule['mode'] ?? '' ) {
			case VisibilityRule::MODE_INCLUDE:
				/* translators: %s: list of roles */
				return $roles ? sprintf( __( 'Only %s', 'tier-pricing-table' ), $roles ) : __( 'Nobody', 'tier-pricing-table' );
			case VisibilityRule::MODE_EXCLUDE:
				/* translators: %s: list of roles */
				return $roles ? sprintf( __( 'Everyone except %s', 'tier-pricing-table' ), $roles ) : __( 'Everyone', 'tier-pricing-table' );
			default:
				return __( 'Everyone', 'tier-pricing-table' );
		}
	}

	public static function roleLabels( array $roles ): string {
		$options = VisibilityMeta::getRoleOptions();
		$labels  = array();

		foreach ( $roles as $role ) {
			$labels[] = VisibilityRule::GUEST === $role ? __( 'Guests', 'tier-pricing-table' ) : ( $options[ $role ] ?? $role );
		}

		return implode( ', ', $labels );
	}

	/**
	 * A rule with the name of where it comes from (a category), as HTML.
	 */
	public static function line( array $rule, string $source = '' ): string {
		$html = esc_html( self::describe( $rule ) );

		if ( '' !== $source ) {
			$html .= ' <span class="tpt-visibility-column__source">' . esc_html( $source ) . '</span>';
		}

		return $html;
	}

	public static function defaultLine(): string {
		return '<span class="tpt-visibility-column__default">' . esc_html__( 'Everyone', 'tier-pricing-table' ) . '</span>';
	}

	public static function ownLine( array $rule ): string {
		return '<span class="tpt-visibility-column__own">' . esc_html( self::describe( $rule ) ) . '</span>';
	}

	/**
	 * @param  int[]  $termIds
	 *
	 * @return array<int, array> term id => rule, for the categories that restrict
	 */
	public static function restrictingCategoryRules( array $termIds ): array {
		$rules = array();

		foreach ( array_unique( array_map( 'intval', $termIds ) ) as $termId ) {
			$rule = VisibilityMeta::getCategoryRule( $termId );

			if ( VisibilityRule::isRestricting( $rule ) ) {
				$rules[ $termId ] = $rule;
			}
		}

		return $rules;
	}

	public static function categoryName( int $termId ): string {
		$term = get_term( $termId, 'product_cat' );

		return $term && ! is_wp_error( $term ) ? $term->name : '#' . $termId;
	}

	public static function printStyles() {
		?>
		<style>
			.wp-list-table .column-tpt_visibility { width: 11%; }
			.tpt-visibility-column__source { display: block; color: #787c82; }
			.tpt-visibility-column__source::before { content: "("; }
			.tpt-visibility-column__source::after { content: ")"; }
			.tpt-visibility-column__default { color: #787c82; }
		</style>
		<?php
	}
}
