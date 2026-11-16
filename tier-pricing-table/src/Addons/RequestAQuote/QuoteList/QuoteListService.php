<?php namespace TierPricingTable\Addons\RequestAQuote\QuoteList;

use TierPricingTable\CalculationLogic;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\Services\ProductAccess;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The products in a quote list. The list itself lives in the shopper's browser; the server only checks the lines
 * it is sent and prices them for this customer, so no session or cookie is needed and pages stay cacheable.
 */
class QuoteListService {

	/**
	 * The largest quantity one line may ask for.
	 */
	public const MAX_QUANTITY = 1000000;

	/**
	 * Clean the lines a browser sent: whole product IDs, whole quantities of at least 1, one line per product
	 * (repeated products are added together), at most QuoteListSettings::MAX_LINES lines.
	 *
	 * @param  mixed  $raw  A list of {id, qty}.
	 *
	 * @return array<int, array{id: int, qty: int, parent?: int}>
	 */
	public static function normalizeLines( $raw ): array {
		$lines   = array();
		$parents = array();

		foreach ( is_array( $raw ) ? $raw : array() as $line ) {
			if ( ! is_array( $line ) ) {
				continue;
			}

			$id  = isset( $line['id'] ) && is_numeric( $line['id'] ) ? (int) $line['id'] : 0;
			$qty = isset( $line['qty'] ) && is_numeric( $line['qty'] ) ? (int) $line['qty'] : 0;

			if ( $id < 1 || $qty < 1 ) {
				continue;
			}

			$lines[ $id ] = min( self::MAX_QUANTITY, ( $lines[ $id ] ?? 0 ) + $qty );

			// the product a variation belongs to, as the browser knows it (only used to mark buttons there)
			if ( isset( $line['parent'] ) && is_numeric( $line['parent'] ) && (int) $line['parent'] > 0 ) {
				$parents[ $id ] = (int) $line['parent'];
			}
		}

		$lines = array_slice( $lines, 0, QuoteListSettings::MAX_LINES, true );

		$normalized = array();

		foreach ( $lines as $id => $qty ) {
			$line = array( 'id' => (int) $id, 'qty' => (int) $qty );

			if ( isset( $parents[ $id ] ) ) {
				$line['parent'] = $parents[ $id ];
			}

			$normalized[] = $line;
		}

		return $normalized;
	}

	/**
	 * A product the list may hold: a simple product or a variation the shopper may see. A variable product
	 * itself cannot be quoted, only one of its variations.
	 */
	public static function getQuotableProduct( int $id ): ?WC_Product {
		$product = wc_get_product( $id );

		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$isVariation = $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() );

		if ( ! $isVariation && ! $product->is_type( TierPricingTablePlugin::getSupportedSimpleProductTypes() ) ) {
			return null;
		}

		if ( ! ProductAccess::canView( $product ) ) {
			return null;
		}

		return $product;
	}

	/**
	 * The lines as the list shows them, priced for this customer. Lines whose product is gone or hidden are
	 * left out and reported in 'removed'.
	 *
	 * @param  array<int, array{id: int, qty: int}>  $lines
	 *
	 * @return array{items: array, removed: int[], total: ?float, totalHtml: string, showPrices: bool}
	 */
	public static function getList( array $lines ): array {
		$items   = array();
		$removed = array();

		foreach ( $lines as $line ) {
			$product = self::getQuotableProduct( $line['id'] );

			if ( ! $product ) {
				$removed[] = $line['id'];
				continue;
			}

			$items[] = array( 'product' => $product, 'qty' => $line['qty'] );
		}

		$priced    = self::price( $items );
		$total     = 0.0;
		$hasTotal  = (bool) $priced;
		$showPrice = false;

		foreach ( $priced as $item ) {
			if ( null === $item['unitPrice'] ) {
				$hasTotal = false;
			} else {
				$total    += $item['lineTotal'];
				$showPrice = true;
			}
		}

		return array(
			'items'      => $priced,
			'removed'    => $removed,
			'total'      => $hasTotal ? $total : null,
			'totalHtml'  => $hasTotal ? wc_price( $total ) : '',
			'showPrices' => $showPrice,
		);
	}

	/**
	 * The lines to store with a request: the product name as the shopper saw it (with the variation's options),
	 * the SKU, the quantity and the unit price for this customer at the combined quantity, without tax as the
	 * store enters prices ('' when the product has no price).
	 *
	 * @param  array<int, array{id: int, qty: int}>  $lines
	 *
	 * @return array|WP_Error
	 */
	public static function getRequestItems( array $lines ) {
		$items = array();

		foreach ( $lines as $line ) {
			$product = self::getQuotableProduct( $line['id'] );

			if ( ! $product ) {
				return new WP_Error( 'quote_list_product',
					__( 'A product in your quote list is no longer available. Please check your list and try again.', 'tier-pricing-table' ),
					array( 'status' => 400 ) );
			}

			$items[] = array( 'product' => $product, 'qty' => $line['qty'] );
		}

		if ( ! $items ) {
			return new WP_Error( 'quote_list_empty', __( 'Your quote list is empty.', 'tier-pricing-table' ), array( 'status' => 400 ) );
		}

		$quantities = self::getCombinedQuantities( $items );
		$stored     = array();

		foreach ( $items as $item ) {
			/** @var WC_Product $product */
			$product     = $item['product'];
			$pricingRule = PriceManager::getPricingRule( $product->get_id() );
			$combined    = $quantities[ self::getCountKey( $product, $pricingRule ) ] ?? $item['qty'];

			$price = $pricingRule->getTierPrice( $combined, false );

			if ( false === $price || ! is_numeric( $price ) ) {
				$price = $product->get_price();
			}

			$stored[] = array(
				'product_id' => $product->get_id(),
				'name'       => self::getDisplayName( $product ),
				'sku'        => (string) $product->get_sku(),
				'quantity'   => $item['qty'],
				'price'      => is_numeric( $price ) ? (string) $price : '',
			);
		}

		return $stored;
	}

	/**
	 * Price every line for this customer. Lines that count together towards a tier in the cart (variations of
	 * one product when the store sums them, the products of a mix and match rule) count together here too.
	 *
	 * @param  array<int, array{product: WC_Product, qty: int}>  $items
	 */
	protected static function price( array $items ): array {
		$quantities = self::getCombinedQuantities( $items );
		$priced     = array();

		foreach ( $items as $item ) {
			/** @var WC_Product $product */
			$product     = $item['product'];
			$id          = $product->get_id();
			$isVariation = $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() );
			$parentId    = $isVariation ? $product->get_parent_id() : $id;
			$pricingRule = PriceManager::getPricingRule( $id );
			$countKey    = self::getCountKey( $product, $pricingRule );
			$combined    = $quantities[ $countKey ] ?? $item['qty'];

			list( $min, $max, $step ) = self::getQuantityLimits( $product, $pricingRule );

			$unitPrice = null;
			$nextTier  = null;

			if ( self::canShowPrices( $parentId, $isVariation ? $id : null ) ) {
				$unitPrice = self::getDisplayUnitPrice( $product, $pricingRule, $combined );
				$nextTier  = null !== $unitPrice ? self::getNextTier( $pricingRule, $combined, $unitPrice ) : null;
			}

			$priced[] = array(
				'id'            => $id,
				'parentId'      => $isVariation ? $parentId : 0,
				'name'          => $isVariation ? get_the_title( $parentId ) : $product->get_name(),
				'attributes'    => $isVariation ? self::formatVariation( $product ) : '',
				'sku'           => (string) $product->get_sku(),
				'url'           => $product->get_permalink(),
				'image'         => self::getImage( $product ),
				'qty'           => $item['qty'],
				'min'           => $min,
				'max'           => $max,
				'step'          => $step,
				'unitPrice'     => $unitPrice,
				'unitPriceHtml' => null !== $unitPrice ? wc_price( $unitPrice ) : '',
				'lineTotal'     => null !== $unitPrice ? $unitPrice * $item['qty'] : null,
				'lineTotalHtml' => null !== $unitPrice ? wc_price( $unitPrice * $item['qty'] ) : '',
				'nextTier'      => $nextTier,
			);
		}

		return $priced;
	}

	/**
	 * The quantity each group of lines adds up to, keyed by getCountKey().
	 *
	 * @param  array<int, array{product: WC_Product, qty: int}>  $items
	 *
	 * @return array<string, int>
	 */
	protected static function getCombinedQuantities( array $items ): array {
		$quantities = array();

		foreach ( $items as $item ) {
			$key                = self::getCountKey( $item['product'], PriceManager::getPricingRule( $item['product']->get_id() ) );
			$quantities[ $key ] = ( $quantities[ $key ] ?? 0 ) + $item['qty'];
		}

		return $quantities;
	}

	/**
	 * Lines with the same key count together towards a tier, as in the cart.
	 */
	protected static function getCountKey( WC_Product $product, PricingRule $pricingRule ): string {
		if ( 'global-rules' === $pricingRule->provider && 'cross' === ( $pricingRule->providerData['applying_type'] ?? '' ) && ! empty( $pricingRule->providerData['rule_id'] ) ) {
			return 'rule-' . $pricingRule->providerData['rule_id'];
		}

		if ( $product->get_parent_id() && CalculationLogic::considerProductVariationAsOneProduct() ) {
			return 'product-' . $product->get_parent_id();
		}

		return 'item-' . $product->get_id();
	}

	/**
	 * The unit price at a quantity as the shop shows prices (with or without tax), null without a price.
	 */
	protected static function getDisplayUnitPrice( WC_Product $product, PricingRule $pricingRule, int $quantity ): ?float {
		$tierPrice = $pricingRule->getTierPrice( $quantity, true, 'shop' );

		if ( false !== $tierPrice && is_numeric( $tierPrice ) ) {
			return (float) $tierPrice;
		}

		if ( '' === (string) $product->get_price() ) {
			return null;
		}

		return (float) wc_get_price_to_display( $product );
	}

	/**
	 * The next tier above the combined quantity, when it lowers the unit price: how many more and at what price.
	 */
	protected static function getNextTier( PricingRule $pricingRule, int $quantity, float $unitPrice ): ?array {
		$quantities = array_map( 'intval', array_keys( $pricingRule->getRules() ) );
		sort( $quantities );

		foreach ( $quantities as $tierQuantity ) {
			if ( $tierQuantity <= $quantity ) {
				continue;
			}

			$tierPrice = $pricingRule->getTierPrice( $tierQuantity, true, 'shop' );

			if ( false !== $tierPrice && is_numeric( $tierPrice ) && (float) $tierPrice < $unitPrice ) {
				return array(
					'more'      => $tierQuantity - $quantity,
					'priceHtml' => wc_price( (float) $tierPrice ),
				);
			}
		}

		return null;
	}

	/**
	 * Prices in the list follow the store's choices: the list setting, and no prices for visitors the store
	 * hides them from (the same switch that hides the pricing table).
	 */
	protected static function canShowPrices( int $parentId, ?int $variationId ): bool {
		if ( ! QuoteListSettings::showPrices() ) {
			return false;
		}

		return false !== apply_filters( 'tiered_pricing_table/should_render_pricing_table', true, $parentId, $variationId, array() );
	}

	/**
	 * Minimum, maximum (null for none) and step, from the pricing rule. Stock does not limit a quote.
	 */
	protected static function getQuantityLimits( WC_Product $product, PricingRule $pricingRule ): array {
		$step = max( 1, (int) ( $pricingRule->data['group_of_quantity'] ?? 1 ) );
		$min  = max( 1, (int) $pricingRule->getMinimum(), $step );
		$max  = (int) ( $pricingRule->data['maximum_quantity'] ?? 0 );

		if ( $product->is_sold_individually() ) {
			return array( 1, 1, 1 );
		}

		return array( $min, $max > 0 ? max( $max, $min ) : null, $step );
	}

	/**
	 * A variation's options with their names: "Colour: Blue, Size: Large". A global attribute that was deleted
	 * leaves only its key and slug on the product ("pa_color", "blue"): those are made readable.
	 */
	public static function formatVariation( WC_Product $variation ): string {
		$parts = array();

		foreach ( $variation->get_attributes() as $name => $value ) {
			$value = (string) $value;

			if ( '' === $value ) {
				continue;
			}

			$label = wc_attribute_label( $name, $variation );

			if ( taxonomy_exists( $name ) ) {
				$term = get_term_by( 'slug', $value, $name );

				if ( $term && ! is_wp_error( $term ) && '' !== $term->name ) {
					$value = $term->name;
				}
			} elseif ( 0 === strpos( $name, 'pa_' ) ) {
				if ( $label === $name ) {
					$label = ucfirst( str_replace( array( '-', '_' ), ' ', substr( $name, 3 ) ) );
				}

				$value = ucfirst( str_replace( '-', ' ', $value ) );
			}

			$parts[] = wp_strip_all_tags( $label ) . ': ' . wp_strip_all_tags( rawurldecode( $value ) );
		}

		return implode( ', ', $parts );
	}

	/**
	 * The product's name with a variation's options, as e-mails and the admin show it.
	 */
	public static function getDisplayName( WC_Product $product ): string {
		if ( $product->get_parent_id() ) {
			$attributes = self::formatVariation( $product );

			return get_the_title( $product->get_parent_id() ) . ( '' !== $attributes ? ' (' . $attributes . ')' : '' );
		}

		return $product->get_name();
	}

	protected static function getImage( WC_Product $product ): string {
		$imageId = $product->get_image_id();

		if ( ! $imageId && $product->get_parent_id() ) {
			$parent  = wc_get_product( $product->get_parent_id() );
			$imageId = $parent ? $parent->get_image_id() : 0;
		}

		$url = $imageId ? wp_get_attachment_image_url( $imageId, 'woocommerce_gallery_thumbnail' ) : '';

		return $url ? $url : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
	}
}
