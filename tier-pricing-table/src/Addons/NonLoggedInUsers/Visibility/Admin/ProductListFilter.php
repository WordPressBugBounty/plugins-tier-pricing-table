<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\ProductVisibilityService;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;
use WP_Query;

/**
 * The "Who can see" dropdown of the products list: everyone, restricted, or hidden from a given role.
 */
class ProductListFilter {

	const PARAM = 'tpt_visibility';

	public function __construct() {
		add_action( 'restrict_manage_posts', array( $this, 'render' ), 20, 2 );
		add_action( 'pre_get_posts', array( $this, 'apply' ) );
	}

	/**
	 * @return array<string, string> value => label
	 */
	public function getOptions(): array {
		$options = array(
			'everyone'   => __( 'Everyone', 'tier-pricing-table' ),
			'restricted' => __( 'Restricted', 'tier-pricing-table' ),
		);

		foreach ( VisibilityMeta::getRoleOptions() as $role => $label ) {
			/* translators: %s: role name */
			$options[ 'hidden:' . $role ] = sprintf( __( 'Hidden from %s', 'tier-pricing-table' ), VisibilityRule::GUEST === $role ? __( 'Guests', 'tier-pricing-table' ) : $label );
		}

		return $options;
	}

	public function getCurrent(): string {
		$value = isset( $_GET[ self::PARAM ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PARAM ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return array_key_exists( $value, $this->getOptions() ) ? $value : '';
	}

	public function render( $postType, $which = 'top' ) {
		if ( 'product' !== $postType || 'top' !== $which ) {
			return;
		}

		$current = $this->getCurrent();
		?>
		<select name="<?php echo esc_attr( self::PARAM ); ?>" id="tpt-visibility-filter">
			<option value=""><?php esc_html_e( 'Who can see', 'tier-pricing-table' ); ?></option>
			<?php foreach ( $this->getOptions() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function apply( WP_Query $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || 'product' !== $query->get( 'post_type' ) ) {
			return;
		}

		$current = $this->getCurrent();

		if ( '' === $current ) {
			return;
		}

		if ( 'everyone' === $current ) {
			$this->exclude( $query, ProductVisibilityService::collectRestrictedProducts() );

			return;
		}

		if ( 'restricted' === $current ) {
			$this->only( $query, ProductVisibilityService::collectRestrictedProducts() );

			return;
		}

		$role = substr( $current, strlen( 'hidden:' ) );

		$this->only( $query, ProductVisibilityService::collectHiddenProducts(
			VisibilityRule::GUEST === $role ? array() : array( $role ),
			VisibilityRule::GUEST === $role
		) );
	}

	protected function only( WP_Query $query, array $ids ) {
		$ids     = array_values( array_unique( array_map( 'intval', $ids ) ) );
		$current = array_map( 'intval', (array) $query->get( 'post__in' ) );

		if ( $current ) {
			$ids = array_values( array_intersect( $current, $ids ) );
		}

		$query->set( 'post__in', $ids ?: array( 0 ) );
	}

	protected function exclude( WP_Query $query, array $ids ) {
		$ids = array_values( array_unique( array_merge( array_map( 'intval', (array) $query->get( 'post__not_in' ) ), array_map( 'intval', $ids ) ) ) );

		if ( $ids ) {
			$query->set( 'post__not_in', $ids );
		}
	}
}
