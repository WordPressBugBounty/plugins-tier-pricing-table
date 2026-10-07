<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\Formatter;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use WP_Term;

/**
 * Two lines: the products the rule covers and the customers it covers, exclusions included. Products,
 * terms and customers link to their edit screens; the names are built as escaped HTML.
 */
class AppliesTo {

	const MAX_NAMES = 5;

	public function getName(): string {
		return __( 'Applies to', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		$this->renderLine( 'dashicons-archive', $this->productsText( $rule ), $this->productExceptionsText( $rule ) );
		$this->renderLine( 'dashicons-admin-users', $this->customersText( $rule ), $this->customerExceptionsText( $rule ) );
	}

	/**
	 * @param  string  $html  Escaped HTML.
	 * @param  string  $exceptions  Escaped HTML.
	 */
	protected function renderLine( string $icon, string $html, string $exceptions ) {
		?>
		<div class="tpt-rules-list__line">
			<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
			<span>
				<?php echo wp_kses_post( $html ); ?>
				<?php if ( $exceptions ) : ?>
					<span class="tpt-rules-list__muted"><?php echo wp_kses_post( $exceptions ); ?></span>
				<?php endif; ?>
			</span>
		</div>
		<?php
	}

	protected function productsText( GlobalPricingRule $rule ): string {
		$parts = array_filter( array(
			$this->named( __( 'Products', 'tier-pricing-table' ), $this->productNames( $rule->getIncludedProducts() ) ),
			$this->named( $rule->isIncludeSubcategories() ? __( 'Categories and subcategories', 'tier-pricing-table' ) : __( 'Categories', 'tier-pricing-table' ), $this->termNames( $rule->getIncludedProductCategories() ) ),
			$this->named( __( 'Tags', 'tier-pricing-table' ), $this->termNames( $rule->getIncludedProductTags() ) ),
			$this->named( __( 'Brands', 'tier-pricing-table' ), $this->termNames( $rule->getIncludedProductBrands() ) ),
		) );

		return $parts ? implode( ' · ', $parts ) : esc_html__( 'All products', 'tier-pricing-table' );
	}

	protected function productExceptionsText( GlobalPricingRule $rule ): string {
		$names = array_merge(
			$this->productNames( $rule->getExcludedProducts() ),
			$this->termNames( $rule->getExcludedProductCategories() ),
			$this->termNames( $rule->getExcludedProductTags() ),
			$this->termNames( $rule->getExcludedProductBrands() )
		);

		/* translators: %s: list of names */
		return $names ? sprintf( esc_html__( 'except %s', 'tier-pricing-table' ), $this->shorten( $names ) ) : '';
	}

	protected function customersText( GlobalPricingRule $rule ): string {
		$parts = array_filter( array(
			$this->named( __( 'Roles', 'tier-pricing-table' ), $this->roleNames( $rule->getIncludedUserRoles() ) ),
			$this->named( __( 'Customers', 'tier-pricing-table' ), $this->customerNames( $rule->getIncludedUsers() ) ),
		) );

		return $parts ? implode( ' · ', $parts ) : esc_html__( 'All customers', 'tier-pricing-table' );
	}

	protected function customerExceptionsText( GlobalPricingRule $rule ): string {
		$names = array_merge( $this->roleNames( $rule->getExcludedUserRoles() ), $this->customerNames( $rule->getExcludedUsers() ) );

		/* translators: %s: list of names */
		return $names ? sprintf( esc_html__( 'except %s', 'tier-pricing-table' ), $this->shorten( $names ) ) : '';
	}

	/**
	 * @param  string[]  $names  Escaped HTML.
	 */
	protected function named( string $label, array $names ): string {
		return $names ? esc_html( $label ) . ': ' . $this->shorten( $names ) : '';
	}

	/**
	 * @param  string[]  $names  Escaped HTML.
	 */
	protected function shorten( array $names ): string {
		$shown = array_slice( $names, 0, self::MAX_NAMES );
		$more  = count( $names ) - count( $shown );

		/* translators: %d: number of further items */
		return implode( ', ', $shown ) . ( $more > 0 ? ' ' . esc_html( sprintf( __( '+%d more', 'tier-pricing-table' ), $more ) ) : '' );
	}

	protected function link( string $url, string $name ): string {
		return $url
			? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '</a>'
			: esc_html( $name );
	}

	/**
	 * @return string[] Escaped HTML, one per id (only the first few are loaded; the rest keep the "+N more" count right).
	 */
	protected function productNames( array $ids ): array {
		$names = array();
		foreach ( array_slice( $ids, 0, self::MAX_NAMES ) as $id ) {
			$product = wc_get_product( $id );
			$names[] = $product
				? $this->link( (string) get_edit_post_link( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id(), 'raw' ), $product->get_name() )
				: esc_html( '#' . (int) $id );
		}

		return array_pad( $names, count( $ids ), '' );
	}

	/**
	 * @return string[] Escaped HTML.
	 */
	protected function termNames( array $ids ): array {
		$names = array();
		foreach ( $ids as $id ) {
			$term = get_term( (int) $id );

			if ( $term instanceof WP_Term ) {
				$editLink = get_edit_term_link( $term );
				$names[]  = $this->link( is_string( $editLink ) ? $editLink : '', $term->name );
			} else {
				$names[] = esc_html( '#' . (int) $id );
			}
		}

		return $names;
	}

	/**
	 * @return string[] Escaped HTML.
	 */
	protected function roleNames( array $roles ): array {
		return array_map( function ( $role ) {
			return esc_html( Formatter::formatRoleString( $role ) );
		}, $roles );
	}

	/**
	 * @return string[] Escaped HTML.
	 */
	protected function customerNames( array $ids ): array {
		$names = array();
		foreach ( array_slice( $ids, 0, self::MAX_NAMES ) as $id ) {
			$user    = get_userdata( (int) $id );
			$names[] = $user ? $this->link( (string) get_edit_user_link( $user->ID ), $user->display_name ) : esc_html( '#' . (int) $id );
		}

		return array_pad( $names, count( $ids ), '' );
	}
}
