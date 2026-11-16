<?php namespace TierPricingTable\Addons\QuickOrder;

use TierPricingTable\CalculationLogic;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\TierPricingTablePlugin;
use WC_Data_Store;
use WC_Product;

/**
 * What the quick order form may show and order, priced the way the cart prices it.
 *
 * Every product the form lists goes through the same checks as the shop: published, visible to this customer
 * (catalog visibility by role), purchasable, inside the categories the form is limited to. Prices are the
 * display prices of this customer: role and customer prices included, tiers computed by the same pricing rule
 * the cart uses, with the quantity limits of that rule.
 */
class QuickOrderService {

	const SEARCH_LIMIT     = 20;
	const SHEET_PAGE_SIZE  = 20;
	const VARIATIONS_LIMIT = 150;

	/**
	 * Quantities already in the cart by count key (see getCountKey()).
	 *
	 * @var array<string, int>|null
	 */
	protected $cartQuantities;

	/**
	 * The form's categories with their child categories.
	 *
	 * @var int[]|null
	 */
	protected $scopeCategories;

	/* --- access ---------------------------------------------------------------------------------- */

	public function canUse(): bool {

		if ( ! QuickOrderSettingsSection::isEnabled() ) {
			return false;
		}

		// The form shows prices: a guest who may not see prices may not use it either.
		if ( ! is_user_logged_in() && 'yes' === get_option( 'tier_pricing_table_non_logged_in_users_hide_prices', 'no' ) ) {
			return false;
		}

		switch ( QuickOrderSettingsSection::getAudience() ) {
			case QuickOrderSettingsSection::AUDIENCE_LOGGED_IN:
				$allowed = is_user_logged_in();
				break;
			case QuickOrderSettingsSection::AUDIENCE_ROLES:
				$allowed = (bool) array_intersect( TierPricingTablePlugin::getCurrentUserRoles(), QuickOrderSettingsSection::getRoles() );
				break;
			default:
				$allowed = true;
		}

		// Store managers can always open the form, to check it.
		$allowed = $allowed || current_user_can( 'manage_woocommerce' );

		return (bool) apply_filters( 'tiered_pricing_table/quick_order/can_use', $allowed );
	}

	/* --- what the form lists --------------------------------------------------------------------- */

	/**
	 * Products and variations whose name or SKU matches the term.
	 */
	public function search( string $term ): array {
		$term = trim( wp_strip_all_tags( $term ) );

		if ( strlen( $term ) < 2 ) {
			return array();
		}

		$ids = WC_Data_Store::load( 'product' )->search_products( $term, '', true, false, self::SEARCH_LIMIT * 3 );

		$entries  = array();
		$scores   = array();
		$selected = array();

		foreach ( array_filter( array_map( 'intval', $ids ) ) as $id ) {
			$product   = wc_get_product( $id );
			$variation = null;

			if ( ! $product ) {
				continue;
			}

			// A variation is offered through its product.
			if ( $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() ) ) {
				$variation = $product;
				$id        = $product->get_parent_id();
				$product   = wc_get_product( $id );

				if ( ! $product ) {
					continue;
				}
			}

			// A variation found by its own SKU (not by the product's name) opens with that variation chosen.
			if ( $variation && $this->matchesSku( $variation, $term ) && ! $this->matchesSku( $product, $term ) ) {
				$isExact = 0 === strcasecmp( (string) $variation->get_sku(), $term );

				if ( ! isset( $selected[ $id ] ) || $isExact ) {
					$selected[ $id ] = $variation->get_id();
				}
			}

			$score = $this->getSearchScore( $variation && isset( $selected[ $id ] ) ? $variation : $product, $product, $term );

			if ( isset( $entries[ $id ] ) ) {
				$scores[ $id ] = min( $scores[ $id ], $score );
				continue;
			}

			if ( count( $entries ) >= self::SEARCH_LIMIT ) {
				continue;
			}

			$entry = $this->getEntry( $product );

			if ( $entry ) {
				$entries[ $id ] = $entry;
				$scores[ $id ]  = $score;
			}
		}

		foreach ( $selected as $parentId => $variationId ) {
			if ( isset( $entries[ $parentId ] ) && 'variable' === $entries[ $parentId ]['type'] ) {
				$entries[ $parentId ]['selected'] = $variationId;
			}
		}

		// Exact SKU first (typing a SKU and pressing Enter adds that product), then names that start with the
		// term, then names that contain it, then matches in the description. Ties keep the store's order.
		$order = array_flip( array_keys( $entries ) );

		uksort( $entries, function ( $a, $b ) use ( $scores, $order ) {
			return array( $scores[ $a ], $order[ $a ] ) <=> array( $scores[ $b ], $order[ $b ] );
		} );

		return array_values( $entries );
	}

	protected function matchesSku( WC_Product $product, string $term ): bool {
		$sku = (string) $product->get_sku();

		return '' !== $sku && false !== stripos( $sku, $term );
	}

	protected function getSearchScore( WC_Product $matched, WC_Product $product, string $term ): int {
		if ( 0 === strcasecmp( (string) $matched->get_sku(), $term ) ) {
			return 0;
		}

		$name = $product->get_name();

		if ( 0 === stripos( $name, $term ) || 0 === stripos( (string) $matched->get_sku(), $term ) ) {
			return 1;
		}

		if ( false !== stripos( $name, $term ) || $this->matchesSku( $matched, $term ) ) {
			return 2;
		}

		return 3;
	}

	/**
	 * Entries for exact SKUs (a pasted list) or ids (a saved order), keyed by the SKU or id they were asked for.
	 *
	 * @param  string[]  $skus
	 * @param  int[]  $ids
	 */
	public function lookup( array $skus, array $ids ): array {
		$found = array();

		foreach ( array_slice( array_unique( array_filter( array_map( 'trim', $skus ) ) ), 0, 500 ) as $sku ) {
			$productId = wc_get_product_id_by_sku( $sku );
			$entry     = $productId ? $this->getEntryFor( $productId ) : null;

			if ( $entry ) {
				$found[ 'sku:' . strtolower( $sku ) ] = $entry;
			}
		}

		foreach ( array_slice( array_unique( array_filter( array_map( 'intval', $ids ) ) ), 0, 200 ) as $id ) {
			$entry = $this->getEntryFor( $id );

			if ( $entry ) {
				$found[ 'id:' . $id ] = $entry;
			}
		}

		return $found;
	}

	/**
	 * One page of the order sheet: every product the form covers, in the shop's order.
	 */
	public function sheet( int $page ): array {
		// Variations are offered through their product, so the sheet asks for products only: each one once.
		$types = array_diff(
			array_merge( TierPricingTablePlugin::getSupportedSimpleProductTypes(), TierPricingTablePlugin::getSupportedVariableProductTypes() ),
			TierPricingTablePlugin::getSupportedVariationProductTypes()
		);

		$args = array(
			'status'     => 'publish',
			'visibility' => 'catalog',
			'type'       => array_values( $types ),
			'limit'      => self::SHEET_PAGE_SIZE + 1,
			'offset'     => max( 0, $page - 1 ) * self::SHEET_PAGE_SIZE,
			'orderby'    => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'return'     => 'ids',
		);

		$slugs = $this->getScopeCategorySlugs();

		if ( $slugs ) {
			$args['category'] = $slugs;
		}

		$ids     = wc_get_products( apply_filters( 'tiered_pricing_table/quick_order/sheet_query', $args ) );
		$hasMore = count( $ids ) > self::SHEET_PAGE_SIZE;
		$entries = array();
		$listed  = array();

		foreach ( array_slice( $ids, 0, self::SHEET_PAGE_SIZE ) as $id ) {
			$entry = $this->getEntryFor( (int) $id );

			if ( ! $entry ) {
				continue;
			}

			// A filtered query may still return variations, which map to their product: list it once.
			$entryId = (int) ( $entry['id'] ?? 0 );

			if ( $entryId && isset( $listed[ $entryId ] ) ) {
				continue;
			}

			$listed[ $entryId ] = true;
			$entries[]          = $entry;
		}

		return array(
			'entries' => $entries,
			'hasMore' => $hasMore,
		);
	}

	public function getEntryFor( int $productId ): ?array {
		$product = wc_get_product( $productId );

		if ( ! $product ) {
			return null;
		}

		// A variation is offered through its product, with that variation chosen.
		if ( $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() ) ) {
			$parent = wc_get_product( $product->get_parent_id() );
			$entry  = $parent ? $this->getEntry( $parent ) : null;

			if ( $entry && 'variable' === $entry['type'] ) {
				$entry['selected'] = $product->get_id();
			}

			return $entry;
		}

		return $this->getEntry( $product );
	}

	/**
	 * A product as the form offers it: a simple product is one orderable item; a variable product carries its
	 * orderable variations.
	 */
	public function getEntry( WC_Product $product ): ?array {

		if ( ! $this->isListable( $product ) ) {
			return null;
		}

		if ( $product->is_type( TierPricingTablePlugin::getSupportedVariableProductTypes() ) ) {
			$variations = array();

			foreach ( array_slice( $product->get_children(), 0, self::VARIATIONS_LIMIT ) as $variationId ) {
				$variation = wc_get_product( $variationId );
				$item      = $variation ? $this->getItem( $variation ) : null;

				if ( $item ) {
					$variations[] = $item;
				}
			}

			if ( empty( $variations ) ) {
				return null;
			}

			return array(
				'type'       => 'variable',
				'id'         => $product->get_id(),
				'name'       => $product->get_name(),
				'sku'        => (string) $product->get_sku(),
				'url'        => $product->get_permalink(),
				'image'      => $this->getImage( $product ),
				'priceHtml'  => $product->get_price_html(),
				'variations' => $variations,
				'selected'   => null,
			);
		}

		if ( ! $product->is_type( TierPricingTablePlugin::getSupportedSimpleProductTypes() ) ) {
			return null;
		}

		$item = $this->getItem( $product );

		if ( ! $item ) {
			return null;
		}

		$item['type']      = 'simple';
		$item['priceHtml'] = $product->get_price_html();

		return $item;
	}

	/**
	 * One orderable product or variation: its display prices for this customer at every tier, and its limits.
	 */
	public function getItem( WC_Product $product ): ?array {

		if ( ! $this->isOrderable( $product ) ) {
			return null;
		}

		$id          = $product->get_id();
		$isVariation = $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() );
		$pricingRule = PriceManager::getPricingRule( $id );

		$basePrice    = (float) wc_get_price_to_display( $product );
		$regularPrice = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );

		$tiers = array();

		foreach ( array_keys( $pricingRule->getRules() ) as $quantity ) {
			$tierPrice = $pricingRule->getTierPrice( (int) $quantity, true, 'shop' );

			if ( false !== $tierPrice && is_numeric( $tierPrice ) && (int) $quantity > 1 ) {
				$tiers[] = array( (int) $quantity, (float) $tierPrice );
			}
		}

		usort( $tiers, function ( $a, $b ) {
			return $a[0] <=> $b[0];
		} );

		list( $min, $max, $step ) = $this->getQuantityLimits( $product, $pricingRule );

		$countKey = $this->getCountKey( $product, $pricingRule );

		$item = array(
			'id'         => $id,
			'parentId'   => $isVariation ? $product->get_parent_id() : 0,
			'name'       => $isVariation ? get_the_title( $product->get_parent_id() ) : $product->get_name(),
			'attributes' => $isVariation ? wp_strip_all_tags( wc_get_formatted_variation( $product, true, false, false ) ) : '',
			'sku'        => (string) $product->get_sku(),
			'url'        => $product->get_permalink(),
			'image'      => $this->getImage( $product ),
			'stock'      => wp_strip_all_tags( wc_get_stock_html( $product ) ),
			'min'        => $min,
			'max'        => $max,
			'step'       => $step,
			'prices'     => array(
				'regular' => $regularPrice > $basePrice ? $regularPrice : $basePrice,
				'base'    => $basePrice,
				'tiers'   => $tiers,
			),
			'countKey'   => $countKey,
			'inCart'     => $this->getCartQuantity( $countKey ),
		);

		return apply_filters( 'tiered_pricing_table/quick_order/item', $item, $product );
	}

	/* --- adding to the cart ---------------------------------------------------------------------- */

	/**
	 * Adds each line through the same validation as an add to cart button, so every rule of the store applies
	 * (minimums, steps, maximums, stock, visibility). Lines that fail keep their WooCommerce message.
	 *
	 * @param  array<int, array{id: int, qty: int}>  $lines
	 */
	public function addToCart( array $lines ): array {
		$added  = array();
		$failed = array();

		// Notices from the store's own pages stay; the ones these adds raise are read per line.
		$earlierNotices = function_exists( 'wc_get_notices' ) ? wc_get_notices() : array();
		wc_clear_notices();

		foreach ( array_slice( $lines, 0, 500 ) as $line ) {
			$id       = absint( $line['id'] ?? 0 );
			$quantity = (int) ( $line['qty'] ?? 0 );

			if ( ! $id || $quantity < 1 ) {
				continue;
			}

			$message = $this->addLine( $id, $quantity );

			if ( null === $message ) {
				$added[] = array( 'id' => $id, 'qty' => $quantity );
			} else {
				$failed[] = array( 'id' => $id, 'qty' => $quantity, 'message' => $message );
			}

			wc_clear_notices();
		}

		if ( $earlierNotices ) {
			wc_set_notices( $earlierNotices );
		}

		return array(
			'added'     => $added,
			'failed'    => $failed,
			'cartCount' => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
		);
	}

	/**
	 * Adds one line to the cart.
	 *
	 * @return string|null null when the line is in the cart, the reason it is not otherwise
	 */
	protected function addLine( int $id, int $quantity ): ?string {
		$product = wc_get_product( $id );

		if ( ! $product || ! $this->isOrderable( $product ) ) {
			return __( 'This product is not available.', 'tier-pricing-table' );
		}

		$productId   = $id;
		$variationId = 0;
		$attributes  = array();

		if ( $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() ) ) {
			$productId   = $product->get_parent_id();
			$variationId = $id;
			$attributes  = $product->get_variation_attributes();

			// "Any size" variations need the customer's choice: that happens on the product page.
			if ( in_array( '', array_map( 'strval', $attributes ), true ) ) {
				return __( 'Choose the options of this product on its page.', 'tier-pricing-table' );
			}
		}

		$passed = apply_filters( 'woocommerce_add_to_cart_validation', true, $productId, $quantity, $variationId, $attributes );

		if ( $passed && false !== WC()->cart->add_to_cart( $productId, $quantity, $variationId, $attributes ) ) {
			return null;
		}

		$errors = array_map( function ( $notice ) {
			return html_entity_decode( wp_strip_all_tags( is_array( $notice ) ? ( $notice['notice'] ?? '' ) : (string) $notice ), ENT_QUOTES, 'UTF-8' );
		}, wc_get_notices( 'error' ) );

		$errors = array_values( array_filter( array_map( 'trim', $errors ) ) );

		return $errors ? implode( ' ', $errors ) : __( 'This product could not be added to the cart.', 'tier-pricing-table' );
	}

	/* --- checks ----------------------------------------------------------------------------------- */

	/**
	 * A product (or a variable product) the form may list: published, visible to this customer, in scope.
	 */
	public function isListable( WC_Product $product ): bool {

		if ( 'publish' !== $product->get_status() || ! $product->is_visible() ) {
			return false;
		}

		$supported = array_merge( TierPricingTablePlugin::getSupportedSimpleProductTypes(), TierPricingTablePlugin::getSupportedVariableProductTypes() );

		if ( ! $product->is_type( $supported ) ) {
			return false;
		}

		return $this->isInScope( $product->get_id() );
	}

	/**
	 * An item the form may price and add: a listable simple product, or an enabled variation of a listable one.
	 */
	public function isOrderable( WC_Product $product ): bool {

		if ( $product->is_type( TierPricingTablePlugin::getSupportedVariationProductTypes() ) ) {
			$parent = wc_get_product( $product->get_parent_id() );

			if ( ! $parent || ! $this->isListable( $parent ) || ! $product->variation_is_visible() ) {
				return false;
			}
		} elseif ( ! $this->isListable( $product ) ) {
			return false;
		}

		return $product->is_purchasable() && '' !== (string) $product->get_price() && $product->is_in_stock();
	}

	public function isInScope( int $productId ): bool {
		$categories = $this->getScopeCategories();

		if ( empty( $categories ) ) {
			return true;
		}

		return (bool) array_intersect( wc_get_product_term_ids( $productId, 'product_cat' ), $categories );
	}

	/**
	 * The form's categories with every child category.
	 *
	 * @return int[]
	 */
	public function getScopeCategories(): array {
		if ( null !== $this->scopeCategories ) {
			return $this->scopeCategories;
		}

		$categories = QuickOrderSettingsSection::getCategories();

		foreach ( $categories as $categoryId ) {
			$children = get_term_children( $categoryId, 'product_cat' );

			if ( is_array( $children ) ) {
				$categories = array_merge( $categories, array_map( 'intval', $children ) );
			}
		}

		$this->scopeCategories = array_values( array_unique( $categories ) );

		return $this->scopeCategories;
	}

	protected function getScopeCategorySlugs(): array {
		$slugs = array();

		foreach ( $this->getScopeCategories() as $categoryId ) {
			$term = get_term( $categoryId, 'product_cat' );

			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}

		return $slugs;
	}

	/* --- pricing helpers --------------------------------------------------------------------------- */

	/**
	 * Minimum, maximum (null for none) and step, from the pricing rule (product, variation, role or global rule)
	 * and from WooCommerce (stock, sold individually).
	 */
	protected function getQuantityLimits( WC_Product $product, PricingRule $pricingRule ): array {
		$step = max( 1, (int) ( $pricingRule->data['group_of_quantity'] ?? 1 ) );
		$min  = max( 1, (int) $pricingRule->getMinimum(), $step );
		$max  = (int) ( $pricingRule->data['maximum_quantity'] ?? 0 );

		$wooMax = $product->get_max_purchase_quantity();

		if ( $wooMax > 0 ) {
			$max = $max > 0 ? min( $max, $wooMax ) : $wooMax;
		}

		if ( $product->is_sold_individually() ) {
			$min  = 1;
			$step = 1;
			$max  = 1;
		}

		return array( $min, $max > 0 ? max( $max, $min ) : null, $step );
	}

	/**
	 * Lines with the same key count together towards a tier, as in the cart: variations of one product when the
	 * store sums variations, every product of a "mix and match" global rule, otherwise each item alone.
	 */
	protected function getCountKey( WC_Product $product, PricingRule $pricingRule ): string {

		if ( 'global-rules' === $pricingRule->provider && 'cross' === ( $pricingRule->providerData['applying_type'] ?? '' ) && ! empty( $pricingRule->providerData['rule_id'] ) ) {
			return 'rule-' . $pricingRule->providerData['rule_id'];
		}

		if ( $product->get_parent_id() && CalculationLogic::considerProductVariationAsOneProduct() ) {
			return 'product-' . $product->get_parent_id();
		}

		return 'item-' . $product->get_id();
	}

	protected function getCartQuantity( string $countKey ): int {

		if ( null === $this->cartQuantities ) {
			$this->cartQuantities = array();

			if ( function_exists( 'WC' ) && WC()->cart ) {
				foreach ( WC()->cart->get_cart_contents() as $cartItem ) {
					if ( empty( $cartItem['data'] ) || ! $cartItem['data'] instanceof WC_Product ) {
						continue;
					}

					$key = $this->getCountKey( $cartItem['data'], PriceManager::getPricingRule( $cartItem['data']->get_id() ) );

					$this->cartQuantities[ $key ] = ( $this->cartQuantities[ $key ] ?? 0 ) + (int) $cartItem['quantity'];
				}
			}
		}

		return $this->cartQuantities[ $countKey ] ?? 0;
	}

	protected function getImage( WC_Product $product ): string {
		$imageId = $product->get_image_id();

		if ( ! $imageId && $product->get_parent_id() ) {
			$parent  = wc_get_product( $product->get_parent_id() );
			$imageId = $parent ? $parent->get_image_id() : 0;
		}

		$url = $imageId ? wp_get_attachment_image_url( $imageId, 'woocommerce_gallery_thumbnail' ) : '';

		return $url ? $url : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
	}
}
