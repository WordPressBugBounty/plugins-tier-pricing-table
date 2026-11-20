<?php namespace TierPricingTable\Services\Feeds;

use TierPricingTable\PriceManager;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

/**
 * What product feeds advertise for a product that cannot be bought one at a time (a minimum quantity or a
 * "group of" step).
 *
 * Google's rule: submit the total price of the smallest quantity a shopper can buy, and show that total (or the unit
 * price and the minimum) on the product page. The store chooses between the price of one item (WooCommerce's
 * default), that total, or leaving such products out of feeds.
 *
 * All amounts are on the stored basis, like get_price(): feed plugins add their own tax and currency.
 *
 * @package TierPricingTable\Services\Feeds
 */
class MinimumQuantityPrice {

	const OPTION = 'feeds_minimum_quantity';

	const MODE_UNIT    = 'unit';
	const MODE_TOTAL   = 'total';
	const MODE_EXCLUDE = 'exclude';

	/**
	 * Minimum quantities and "group of" steps are premium features: without them every product is bought one at a
	 * time and the setting has nothing to do.
	 */
	public static function isAvailable(): bool {
		return tpt_fs()->can_use_premium_code__premium_only();
	}

	public static function getMode(): string {
		if ( ! self::isAvailable() ) {
			return self::MODE_UNIT;
		}

		$mode = (string) get_option( Settings::SETTINGS_PREFIX . self::OPTION, self::MODE_UNIT );

		return in_array( $mode, array( self::MODE_UNIT, self::MODE_TOTAL, self::MODE_EXCLUDE ), true ) ? $mode : self::MODE_UNIT;
	}

	public static function isTotalMode(): bool {
		return self::MODE_TOTAL === self::getMode();
	}

	/**
	 * The smallest quantity the current pricing user can buy: the minimum, rounded up to the "group of" step.
	 */
	public static function getQuantity( WC_Product $product ): int {
		if ( $product->is_sold_individually() || ! TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
			return 1;
		}

		$pricingRule = PriceManager::getPricingRule( $product->get_id() );
		$quantity    = max( 1, (int) $pricingRule->getMinimum() );
		$step        = (int) ( $pricingRule->data['group_of_quantity'] ?? 0 );

		if ( $step > 1 ) {
			$quantity = (int) ( ceil( max( $quantity, $step ) / $step ) * $step );
		}

		return max( 1, (int) apply_filters( 'tiered_pricing_table/feeds/minimum_quantity', $quantity, $product, $pricingRule ) );
	}

	/**
	 * The prices of the smallest quantity, or null when one item can be bought (or the product has no price).
	 *
	 * A tier that applies at that quantity is the price shoppers pay, so it replaces the product's own sale: a tier
	 * is a quantity price, not a sale Google could show as a strikethrough.
	 *
	 * @return array{quantity: int, regular: float, price: float, sale: float|null, tier: bool}|null
	 */
	public static function getTotals( WC_Product $product ): ?array {
		$quantity = self::getQuantity( $product );

		if ( $quantity <= 1 ) {
			return null;
		}

		$active = (float) $product->get_price();

		if ( $active <= 0 ) {
			return null;
		}

		$tier = PriceManager::getPricingRule( $product->get_id() )->getTierPrice( $quantity, false, 'cart' );

		if ( false !== $tier && is_numeric( $tier ) && (float) $tier > 0 ) {
			$total = (float) $tier * $quantity;

			$totals = array(
				'quantity' => $quantity,
				'regular'  => $total,
				'price'    => $total,
				'sale'     => null,
				'tier'     => true,
			);
		} else {
			$regular = (float) $product->get_regular_price();
			$onSale  = $product->is_on_sale() && '' !== (string) $product->get_sale_price();

			$totals = array(
				'quantity' => $quantity,
				'regular'  => ( $regular > 0 ? $regular : $active ) * $quantity,
				'price'    => $active * $quantity,
				'sale'     => $onSale ? (float) $product->get_sale_price() * $quantity : null,
				'tier'     => false,
			);
		}

		return apply_filters( 'tiered_pricing_table/feeds/minimum_quantity_totals', $totals, $product );
	}

	/**
	 * How much a price a feed plugin read for one item has to be multiplied by to become the total of the smallest
	 * quantity: 'regular', 'price' or 'sale', matching what the plugin read. Null when nothing changes.
	 *
	 * Plugins that pass a price with their own tax and currency already applied are scaled with this, so their tax
	 * and currency handling stays theirs.
	 */
	public static function getFactor( WC_Product $product, string $kind ): ?float {
		$totals = self::getTotals( $product );

		if ( ! $totals ) {
			return null;
		}

		$unit = array(
			'regular' => (float) $product->get_regular_price(),
			'price'   => (float) $product->get_price(),
			'sale'    => (float) $product->get_sale_price(),
		);

		$unitPrice = $unit[ $kind ] ?? 0.0;

		if ( $unitPrice <= 0 ) {
			return null;
		}

		// under a tier there is one price; a sale price read for one item becomes that price too
		$total = 'regular' === $kind ? $totals['regular'] : ( 'sale' === $kind ? ( $totals['sale'] ?? $totals['price'] ) : $totals['price'] );

		return $total / $unitPrice;
	}
}
