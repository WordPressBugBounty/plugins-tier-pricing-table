<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;

/**
 * The "Who can see" column of the products list: the product's own rule, or the category rules it
 * follows when it has none.
 */
class ProductListColumn {

	const COLUMN = 'tpt_visibility';

	public function __construct() {
		add_filter( 'manage_edit-product_columns', array( $this, 'addColumn' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( $this, 'renderColumn' ), 10, 2 );
		add_action( 'admin_head-edit.php', array( $this, 'printStyles' ) );
	}

	public function addColumn( $columns ) {
		$columns = is_array( $columns ) ? $columns : array();
		$result  = array();

		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;

			if ( 'product_cat' === $key ) {
				$result[ self::COLUMN ] = __( 'Who can see', 'tier-pricing-table' );
			}
		}

		if ( ! isset( $result[ self::COLUMN ] ) ) {
			$result[ self::COLUMN ] = __( 'Who can see', 'tier-pricing-table' );
		}

		return $result;
	}

	public function renderColumn( $column, $productId ) {
		if ( self::COLUMN !== $column ) {
			return;
		}

		echo wp_kses_post( $this->describe( (int) $productId ) );
	}

	/**
	 * @return string HTML
	 */
	public function describe( int $productId ): string {
		$rule = VisibilityMeta::getProductRule( $productId );

		if ( VisibilityRule::MODE_INHERIT !== $rule['mode'] ) {
			return RuleText::ownLine( $rule );
		}

		$lines = array();

		foreach ( RuleText::restrictingCategoryRules( $this->getCategoryIds( $productId ) ) as $termId => $categoryRule ) {
			$lines[] = RuleText::line( $categoryRule, RuleText::categoryName( $termId ) );
		}

		return $lines ? implode( '<br>', $lines ) : RuleText::defaultLine();
	}

	/**
	 * @return int[] the product's categories with their ancestors
	 */
	protected function getCategoryIds( int $productId ): array {
		$termIds = wp_get_post_terms( $productId, 'product_cat', array( 'fields' => 'ids' ) );

		if ( is_wp_error( $termIds ) ) {
			return array();
		}

		$all = array();

		foreach ( $termIds as $termId ) {
			$all[] = (int) $termId;

			foreach ( get_ancestors( (int) $termId, 'product_cat', 'taxonomy' ) as $ancestor ) {
				$all[] = (int) $ancestor;
			}
		}

		return $all;
	}

	public function printStyles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( $screen && 'edit-product' === $screen->id ) {
			RuleText::printStyles();
		}
	}
}
