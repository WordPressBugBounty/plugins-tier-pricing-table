<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\Integrations\Plugins\MixMatch\Settings as MixMatchSettings;
use WC_Product;

/**
 * Compatibility with WooCommerce Mix and Match Products.
 *
 * Container lines are never re-priced by tiered pricing: the Mix and Match plugin owns the
 * container's price, and re-pricing it would charge the tier price on top of the contents.
 *
 * Child items (the products inside a container) are excluded by default. The "Apply tiered pricing
 * inside containers" setting lets tiered pricing discount them — only for containers with per-item
 * pricing, where every child line carries its own real price. Children of statically priced
 * containers always stay untouched.
 */
class MixMatch extends PluginIntegrationAbstract {

	public function run() {

		add_action( 'plugins_loaded', function () {

			if ( ! class_exists( 'WC_Mix_and_Match' ) ) {
				return;
			}

			add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
				$sections[] = new MixMatchSettings();

				return $sections;
			} );
		} );

		// Charging + subtotal column.
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation',
			array( $this, 'filterNeedPriceRecalculation' ), 10, 2 );

		// Price column / mini cart — kept in sync with the charging decision.
		add_filter( 'tiered_pricing_table/cart/need_price_recalculation/item',
			array( $this, 'filterNeedPriceRecalculation' ), 10, 2 );
	}

	/**
	 * Decide whether a cart item may be re-priced by tiered pricing.
	 *
	 * @param  bool   $state
	 * @param  array  $cartItem
	 *
	 * @return bool
	 */
	public function filterNeedPriceRecalculation( $state, $cartItem ) {

		// The container line itself: never re-price it. Its price belongs to Mix and Match, and a
		// matching pricing rule would otherwise charge the tier price on top of the contents.
		if ( isset( $cartItem['mnm_contents'] ) || isset( $cartItem['mnm_config'] ) ) {
			return false;
		}

		if ( isset( $cartItem['mnm_container'] ) ) {

			if ( ! $this->shouldPriceChildItems() ) {
				return false;
			}

			// Only children of per-item-priced containers carry their own real price. Children of
			// statically priced containers are handled entirely by Mix and Match.
			return $this->isChildOfPerItemPricedContainer( $cartItem ) ? $state : false;
		}

		return $state;
	}

	/**
	 * Whether the "Apply tiered pricing inside containers" setting is enabled.
	 *
	 * @return bool
	 */
	protected function shouldPriceChildItems(): bool {
		return $this->getContainer()->getSettings()->get( MixMatchSettings::PRICE_CHILD_ITEMS_KEY, 'no' ) === 'yes';
	}

	/**
	 * Whether a child cart item belongs to a container with per-item pricing.
	 *
	 * @param  array  $cartItem
	 *
	 * @return bool
	 */
	protected function isChildOfPerItemPricedContainer( array $cartItem ): bool {

		$containerItem = null;

		if ( function_exists( 'wc_mnm_get_cart_item_container' ) ) {
			$containerItem = wc_mnm_get_cart_item_container( $cartItem );
		} elseif ( isset( $cartItem['mnm_container'], wc()->cart->cart_contents[ $cartItem['mnm_container'] ] ) ) {
			$containerItem = wc()->cart->cart_contents[ $cartItem['mnm_container'] ];
		}

		$container = is_array( $containerItem ) ? ( $containerItem['data'] ?? null ) : null;

		if ( ! ( $container instanceof WC_Product ) || ! is_callable( array( $container, 'is_priced_per_product' ) ) ) {
			return false;
		}

		return (bool) $container->is_priced_per_product();
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/mix-match-icon.png' );
	}

	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/woocommerce-mix-and-match-products/';
	}

	public function getTitle(): string {
		return 'Mix&Match for WooCommerce';
	}

	public function getDescription(): string {
		return __( 'Apply tiered pricing rules correctly to Mix and Match products.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'mix-match-for-woocommerce';
	}

	public function getIntegrationCategory(): string {
		return 'custom_product_types';
	}
}
