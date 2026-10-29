<?php namespace TierPricingTable\Admin\ProductPage;

use TierPricingTable\Core\ServiceContainerTrait;

/**
 * The shared chrome of the role and customer prices: one list of rules (the role and customer blocks
 * render their rows into it), an empty state, one picker that adds a role or a customer, and a footer
 * with the roles management link. Used in the product's Tiered Pricing tab and inside
 * every variation.
 */
class RoleCustomerPricingUI {

	use ServiceContainerTrait;

	public function __construct() {
		// the role block renders at 11 and the customer block at 12 inside every variation
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'variationStart' ), 10, 3 );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'variationEnd' ), 99, 3 );
	}

	public static function isActive(): bool {
		return (bool) apply_filters( 'tiered_pricing_table/admin/role_customer_pricing_tab_active', false );
	}

	public function variationStart( $loop, $variationData, $variation ) {
		if ( self::isActive() ) {
			self::start( (int) $variation->ID, (int) $loop );
		}
	}

	public function variationEnd( $loop, $variationData, $variation ) {
		if ( self::isActive() ) {
			self::end( (int) $variation->ID, (int) $loop );
		}
	}

	/**
	 * Opens the wrapper the two blocks render into.
	 */
	public static function start( int $productId, ?int $loop = null ) {
		$classes = 'tpt-rc' . ( null === $loop ? '' : ' tpt-rc--variation' );
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-product-id="<?php echo esc_attr( $productId ); ?>" data-loop="<?php echo esc_attr( (string) $loop ); ?>">
			<div class="tpt-rc__list">
		<?php
	}

	/**
	 * Closes the list and renders the empty state, the picker and the footer.
	 */
	public static function end( int $productId, ?int $loop = null ) {
		$settingsLink = add_query_arg( array( 'section' => 'advanced' ), \TierPricingTable\Core\ServiceContainer::getInstance()->getSettings()->getLink() );
		?>
			</div>

			<div class="tpt-rc__empty" hidden>
				<?php esc_html_e( 'No role or customer prices on this product yet.', 'tier-pricing-table' ); ?>
				<span class="tpt-rc__empty-hint"><?php esc_html_e( 'A price set here replaces the product\'s pricing for that role or customer.', 'tier-pricing-table' ); ?></span>
			</div>

			<div class="tpt-rc__add-row">
				<select class="tpt-rc__picker" data-placeholder="<?php esc_attr_e( 'Add pricing for a role or a customer…', 'tier-pricing-table' ); ?>"></select>
				<button type="button" class="button button-primary tpt-rc__add"><?php esc_html_e( 'Add', 'tier-pricing-table' ); ?></button>
			</div>

			<div class="tpt-rc__foot">
					<?php
						printf(
							/* translators: %s: link to roles management */
							esc_html__( 'Roles are managed under %s.', 'tier-pricing-table' ),
							'<a href="' . esc_url( $settingsLink . '#roles' ) . '" target="_blank">' . esc_html__( 'Settings → Advanced → Roles Management', 'tier-pricing-table' ) . '</a>'
						);
					?>
			</div>
		</div>
		<?php
	}

	/**
	 * Data for the picker script.
	 */
	public static function scriptData(): array {
		return array(
			'searchNonce' => wp_create_nonce( 'search-customers' ),
			'currency'    => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ),
			'i18n'        => array(
				'roles'        => __( 'Roles', 'tier-pricing-table' ),
				'customers'    => __( 'Customers', 'tier-pricing-table' ),
				'typeToSearch' => __( 'Type a name or e-mail to search customers', 'tier-pricing-table' ),
				'noRoles'      => __( 'Every role already has a price', 'tier-pricing-table' ),
				'productPrice' => __( 'Product price', 'tier-pricing-table' ),
				'flat'         => __( 'Flat %s', 'tier-pricing-table' ),
				'sale'         => __( 'Sale %s', 'tier-pricing-table' ),
				'off'          => __( '%s%% off', 'tier-pricing-table' ),
				'noTiers'      => __( 'No tiers', 'tier-pricing-table' ),
				'tier'         => __( '%d tier', 'tier-pricing-table' ),
				'tiers'        => __( '%d tiers', 'tier-pricing-table' ),
				'min'          => __( 'Min %d', 'tier-pricing-table' ),
			),
		);
	}

	/**
	 * One-line summary of a role or customer rule: base price, tiers, minimum.
	 *
	 * @param  object  $rule  RoleBasedPricingRule or UserBasedPricingRule
	 */
	public static function summary( $rule ): array {
		$parts = array();

		if ( 'percentage' === $rule->getPricingType() && $rule->getDiscount() ) {
			$parts[] = sprintf( __( '%s%% off', 'tier-pricing-table' ), self::number( $rule->getDiscount() ) );
		} elseif ( 'flat' === $rule->getPricingType() && ( $rule->getSalePrice() || $rule->getRegularPrice() ) ) {
			$price   = $rule->getSalePrice() ?: $rule->getRegularPrice();
			$parts[] = sprintf( $rule->getSalePrice() ? __( 'Sale %s', 'tier-pricing-table' ) : __( 'Flat %s', 'tier-pricing-table' ), self::price( $price ) );
		} else {
			$parts[] = __( 'Product price', 'tier-pricing-table' );
		}

		$tiers   = count( $rule->getTieredPricingRules() );
		$parts[] = $tiers ? sprintf( _n( '%d tier', '%d tiers', $tiers, 'tier-pricing-table' ), $tiers ) : __( 'No tiers', 'tier-pricing-table' );

		if ( $rule->getMinimumOrderQuantity() ) {
			$parts[] = sprintf( __( 'Min %d', 'tier-pricing-table' ), $rule->getMinimumOrderQuantity() );
		}

		return $parts;
	}

	public static function renderSummary( $rule ) {
		echo '<span class="tpt-rc__summary">';
		foreach ( self::summary( $rule ) as $part ) {
			echo '<span>' . esc_html( $part ) . '</span>';
		}
		echo '</span>';
	}

	protected static function price( $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( (float) $amount ) ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' );
	}

	protected static function number( $value ): string {
		return rtrim( rtrim( number_format( (float) $value, 2, '.', '' ), '0' ), '.' );
	}
}
