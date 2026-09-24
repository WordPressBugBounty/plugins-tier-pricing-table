<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;

/**
 * The "Who can see" column of the product categories list: the category's own rule, or the rule of
 * a parent category that covers it.
 */
class CategoryListColumn {

	const COLUMN = 'tpt_visibility';

	public function __construct() {
		add_filter( 'manage_edit-product_cat_columns', array( $this, 'addColumn' ), 20 );
		add_filter( 'manage_product_cat_custom_column', array( $this, 'renderColumn' ), 10, 3 );
		add_action( 'admin_head-edit-tags.php', array( $this, 'printStyles' ) );
	}

	public function addColumn( $columns ) {
		$columns = is_array( $columns ) ? $columns : array();
		$result  = array();

		foreach ( $columns as $key => $label ) {
			if ( 'posts' === $key ) {
				$result[ self::COLUMN ] = __( 'Who can see', 'tier-pricing-table' );
			}

			$result[ $key ] = $label;
		}

		if ( ! isset( $result[ self::COLUMN ] ) ) {
			$result[ self::COLUMN ] = __( 'Who can see', 'tier-pricing-table' );
		}

		return $result;
	}

	public function renderColumn( $content, $column, $termId ) {
		if ( self::COLUMN !== $column ) {
			return $content;
		}

		return wp_kses_post( $this->describe( (int) $termId ) );
	}

	/**
	 * @return string HTML
	 */
	public function describe( int $termId ): string {
		$rule = VisibilityMeta::getCategoryRule( $termId );

		if ( VisibilityRule::isRestricting( $rule ) ) {
			return RuleText::ownLine( $rule );
		}

		$lines = array();

		foreach ( RuleText::restrictingCategoryRules( get_ancestors( $termId, 'product_cat', 'taxonomy' ) ) as $ancestorId => $ancestorRule ) {
			$lines[] = RuleText::line( $ancestorRule, RuleText::categoryName( $ancestorId ) );
		}

		return $lines ? implode( '<br>', $lines ) : RuleText::defaultLine();
	}

	public function printStyles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'product_cat' === $screen->taxonomy ) {
			RuleText::printStyles();
		}
	}
}
