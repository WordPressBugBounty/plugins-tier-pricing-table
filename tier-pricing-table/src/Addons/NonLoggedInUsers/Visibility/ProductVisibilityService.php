<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WP_Query;

/**
 * Applies the visibility rules on the storefront: hidden products leave every product list, related
 * and upsell lists, menus and the sitemap, cannot be bought, and their pages redirect or 404; hidden
 * categories leave category lists, menus and their archives. Shop managers always see everything.
 */
class ProductVisibilityService {

	/** @var int[]|null */
	protected $hiddenProducts = null;

	/** @var int[]|null */
	protected $hiddenCategories = null;

	protected bool $collecting = false;

	public function __construct() {
		add_action( 'save_post_product', array( VisibilityMeta::class, 'bumpVersion' ) );
		add_action( 'woocommerce_update_product', array( VisibilityMeta::class, 'bumpVersion' ) );
		add_action( 'set_object_terms', array( $this, 'onTermsSet' ), 10, 4 );
		add_action( 'delete_product_cat', array( VisibilityMeta::class, 'bumpVersion' ) );

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_action( 'pre_get_posts', array( $this, 'filterQuery' ) );
		add_filter( 'woocommerce_rest_product_object_query', array( $this, 'filterRestQuery' ), 10, 2 );
		add_filter( 'woocommerce_related_products', array( $this, 'filterIds' ) );
		add_filter( 'woocommerce_product_get_upsell_ids', array( $this, 'filterIds' ) );
		add_filter( 'woocommerce_product_get_cross_sell_ids', array( $this, 'filterIds' ) );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filterIsVisible' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filterIsPurchasable' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validateAddToCart' ), 10, 2 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'checkCartItems' ) );
		add_filter( 'terms_clauses', array( $this, 'filterTermsClauses' ), 10, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'guardStoreApi' ), 10, 3 );
		add_filter( 'woocommerce_get_breadcrumb', array( $this, 'filterBreadcrumb' ) );
		add_filter( 'term_links-product_cat', array( $this, 'filterTermLinks' ) );
		add_action( 'woocommerce_product_meta_start', array( $this, 'bufferProductMeta' ) );
		add_action( 'woocommerce_product_meta_end', array( $this, 'flushProductMeta' ) );
		add_filter( 'wp_get_nav_menu_items', array( $this, 'filterMenuItems' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'filterSitemapQuery' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'guardSingleViews' ), 5 );
	}

	/* --- who is looking ------------------------------------------------------------------------ */

	public static function isExempt(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	public static function isGuest(): bool {
		return ! is_user_logged_in();
	}

	/**
	 * @return string[]
	 */
	public static function getRoles(): array {
		return self::isGuest() ? array() : array_values( array_map( 'strval', TierPricingTablePlugin::getCurrentUserRoles() ) );
	}

	public static function isProductVisibleFor( int $productId, array $roles, bool $isGuest ): bool {
		return VisibilityRule::isProductVisible(
			VisibilityMeta::getProductRule( $productId ),
			VisibilityMeta::getProductCategoryRules( $productId ),
			$roles,
			$isGuest
		);
	}

	public function isProductHidden( int $productId ): bool {
		return in_array( $productId, $this->getHiddenProducts(), true );
	}

	public function isCategoryHidden( int $termId ): bool {
		return in_array( $termId, $this->getHiddenCategories(), true );
	}

	/* --- the hidden sets (cached per visitor kind) --------------------------------------------- */

	/**
	 * @return int[]
	 */
	public function getHiddenProducts(): array {
		if ( null !== $this->hiddenProducts ) {
			return $this->hiddenProducts;
		}

		if ( self::isExempt() ) {
			return $this->hiddenProducts = array();
		}

		$this->hiddenProducts = $this->remember( 'products', function () {
			return self::collectHiddenProducts( self::getRoles(), self::isGuest() );
		} );

		return $this->hiddenProducts;
	}

	/**
	 * @return int[]
	 */
	public function getHiddenCategories(): array {
		if ( null !== $this->hiddenCategories ) {
			return $this->hiddenCategories;
		}

		if ( self::isExempt() ) {
			return $this->hiddenCategories = array();
		}

		$this->hiddenCategories = $this->remember( 'categories', function () {
			return self::collectHiddenCategories( self::getRoles(), self::isGuest() );
		} );

		return $this->hiddenCategories;
	}

	protected function remember( string $what, callable $compute ): array {
		$key = 'tpt_visibility_' . $what . '_' . md5( VisibilityMeta::getVersion() . '|' . implode( ',', self::getRoles() ) . '|' . ( self::isGuest() ? 'guest' : 'user' ) );
		$ids = get_transient( $key );

		if ( ! is_array( $ids ) ) {
			$this->collecting = true;
			$ids              = array_values( array_unique( array_map( 'intval', (array) $compute() ) ) );
			$this->collecting = false;

			set_transient( $key, $ids, 12 * HOUR_IN_SECONDS );
		}

		return $ids;
	}

	/**
	 * @return int[] restricted categories a visitor with these roles may not see, with their child categories
	 */
	public static function collectHiddenCategories( array $roles, bool $isGuest ): array {
		$hidden = array();

		foreach ( VisibilityMeta::getRestrictedCategoryIds() as $termId ) {
			if ( ! VisibilityRule::canSee( VisibilityMeta::getCategoryRule( $termId ), $roles, $isGuest ) ) {
				$hidden[] = $termId;

				foreach ( get_term_children( $termId, 'product_cat' ) as $child ) {
					$hidden[] = (int) $child;
				}
			}
		}

		return $hidden;
	}

	/**
	 * @return int[] products a visitor with these roles may not see (not cached; the storefront uses getHiddenProducts())
	 */
	public static function collectHiddenProducts( array $roles, bool $isGuest ): array {
		$hidden = array();

		foreach ( self::findProductsWithOwnRule() as $productId ) {
			if ( ! VisibilityRule::canSee( VisibilityMeta::getProductRule( $productId ), $roles, $isGuest ) ) {
				$hidden[] = $productId;
			}
		}

		// products in a hidden category that follow the category rules
		foreach ( self::findProductsFollowingCategories( self::collectHiddenCategories( $roles, $isGuest ) ) as $productId ) {
			$hidden[] = $productId;
		}

		return $hidden;
	}

	/**
	 * @return int[] every product that is hidden from somebody: an own rule, or a restricting category it follows
	 */
	public static function collectRestrictedProducts(): array {
		return array_values( array_unique( array_merge(
			self::findProductsWithOwnRule(),
			self::findProductsFollowingCategories( VisibilityMeta::getRestrictedCategoryIds() )
		) ) );
	}

	/**
	 * @return int[] products with a restricting rule of their own
	 */
	protected static function findProductsWithOwnRule(): array {
		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tpt_visibility' => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => VisibilityMeta::PRODUCT_MODE,
					'value'   => array( VisibilityRule::MODE_INCLUDE, VisibilityRule::MODE_EXCLUDE ),
					'compare' => 'IN',
				),
			),
		) );

		return array_map( 'intval', $ids );
	}

	/**
	 * @param  int[]  $categoryIds
	 *
	 * @return int[] products in these categories (or their children) that have no rule of their own
	 */
	protected static function findProductsFollowingCategories( array $categoryIds ): array {
		if ( ! $categoryIds ) {
			return array();
		}

		$ids = get_posts( array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tpt_visibility' => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array_map( 'intval', $categoryIds ),
					'include_children' => true,
				),
			),
		) );

		$following = array();

		foreach ( $ids as $productId ) {
			if ( VisibilityRule::MODE_INHERIT === VisibilityMeta::getProductRule( (int) $productId )['mode'] ) {
				$following[] = (int) $productId;
			}
		}

		return $following;
	}

	/* --- lists ------------------------------------------------------------------------------- */

	public function filterQuery( WP_Query $query ) {
		if ( $this->collecting || $query->get( 'tpt_visibility' ) || is_admin() ) {
			return;
		}

		$postType = $query->get( 'post_type' );
		$types    = array_filter( (array) $postType );
		$isProduct = in_array( 'product', $types, true );
		$isBroad   = $query->is_main_query() && ( $query->is_search() || $query->is_post_type_archive( 'product' ) || $query->is_tax( get_object_taxonomies( 'product' ) ) );

		if ( ! $isProduct && ! $isBroad ) {
			return;
		}

		// a hidden product's own page is handled on template_redirect (login page or not-found page)
		if ( $query->is_main_query() && $query->is_singular() ) {
			return;
		}

		$hidden = $this->getHiddenProducts();

		if ( ! $hidden ) {
			return;
		}

		$query->set( 'post__not_in', array_values( array_unique( array_merge( (array) $query->get( 'post__not_in' ), $hidden ) ) ) );
	}

	public function filterRestQuery( array $args, $request ): array {
		$hidden = $this->getHiddenProducts();

		if ( $hidden ) {
			$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $hidden ) ) );
		}

		return $args;
	}

	public function filterIds( $ids ) {
		if ( ! is_array( $ids ) ) {
			return $ids;
		}

		$hidden = $this->getHiddenProducts();

		return $hidden ? array_values( array_diff( array_map( 'intval', $ids ), $hidden ) ) : $ids;
	}

	public function filterIsVisible( $visible, $productId ) {
		return $visible && ! $this->isProductHidden( (int) $productId );
	}

	public function filterIsPurchasable( $purchasable, $product ) {
		if ( $product instanceof WC_Product && $this->isProductHidden( $this->rootId( $product ) ) ) {
			return false;
		}

		return $purchasable;
	}

	public function validateAddToCart( $passed, $productId ) {
		if ( $passed && $this->isProductHidden( $this->rootId( (int) $productId ) ) ) {
			wc_add_notice( __( 'This product is not available to you.', 'tier-pricing-table' ), 'error' );

			return false;
		}

		return $passed;
	}

	public function checkCartItems() {
		if ( ! WC()->cart ) {
			return;
		}

		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$productId = (int) ( $item['product_id'] ?? 0 );

			if ( $productId && $this->isProductHidden( $productId ) ) {
				WC()->cart->remove_cart_item( $key );
				wc_add_notice( __( 'An item that is no longer available to you was removed from your cart.', 'tier-pricing-table' ), 'notice' );
			}
		}
	}

	/**
	 * Hidden categories leave category lists: widgets, blocks, dropdowns, menus and the Store API,
	 * whether they go through get_terms() or straight through WP_Term_Query.
	 *
	 * Only listing queries are touched: the per-object queries behind get_the_terms() and the
	 * "get everything" queries that build WordPress' own term caches stay complete, so no cache
	 * ever stores a visitor's view. The clause is part of the SQL, so the term query cache keeps
	 * a visitor's list apart from a customer's.
	 */
	public function filterTermsClauses( $clauses, $taxonomies, $args ) {
		if ( $this->collecting || ! did_action( 'init' ) || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
			return $clauses;
		}

		if ( ! empty( $args['tpt_visibility'] ) || ! empty( $args['object_ids'] ) || 'all' === ( $args['get'] ?? '' ) ) {
			return $clauses;
		}

		if ( ! in_array( (string) ( $args['fields'] ?? 'all' ), array( 'all', 'all_with_object_id', 'ids', 'names', 'slugs', 'id=>name', 'id=>slug' ), true ) ) {
			return $clauses;
		}

		$hidden = $this->getHiddenCategories();

		if ( $hidden ) {
			$clauses['where'] .= ' AND t.term_id NOT IN (' . implode( ',', array_map( 'intval', $hidden ) ) . ')';
		}

		return $clauses;
	}

	/**
	 * The Store API's single product and single category routes return nothing hidden.
	 *
	 * @param  mixed  $result
	 * @param  \WP_REST_Server  $server
	 * @param  \WP_REST_Request  $request
	 */
	public function guardStoreApi( $result, $server, $request ) {
		if ( null !== $result ) {
			return $result;
		}

		$route = (string) $request->get_route();

		if ( preg_match( '#^/wc/store/v\d+/products/categories/(\d+)$#', $route, $m ) ) {
			return $this->isCategoryHidden( (int) $m[1] ) ? $this->notFound() : $result;
		}

		if ( preg_match( '#^/wc/store/v\d+/products/([^/]+)$#', $route, $m ) ) {
			$productId = is_numeric( $m[1] ) ? (int) $m[1] : 0;

			if ( ! $productId ) {
				$post      = get_page_by_path( sanitize_title( urldecode( $m[1] ) ), OBJECT, 'product' );
				$productId = $post ? (int) $post->ID : 0;
			}

			return $productId && $this->isProductHidden( $this->rootId( $productId ) ) ? $this->notFound() : $result;
		}

		return $result;
	}

	protected function notFound(): \WP_Error {
		return new \WP_Error( 'woocommerce_rest_product_invalid_id', __( 'Invalid product ID.', 'tier-pricing-table' ), array( 'status' => 404 ) );
	}

	/**
	 * A visible product inside a hidden category does not name that category in its breadcrumb.
	 */
	public function filterBreadcrumb( $crumbs ) {
		$links = $this->getHiddenCategoryLinks();

		if ( ! $links || ! is_array( $crumbs ) ) {
			return $crumbs;
		}

		return array_values( array_filter( $crumbs, function ( $crumb ) use ( $links ) {
			return ! in_array( untrailingslashit( (string) ( $crumb[1] ?? '' ) ), $links, true );
		} ) );
	}

	/**
	 * ... nor in its "Category:" line.
	 */
	public function filterTermLinks( $termLinks ) {
		$links = $this->getHiddenCategoryLinks();

		if ( ! $links || ! is_array( $termLinks ) ) {
			return $termLinks;
		}

		return array_values( array_filter( $termLinks, function ( $html ) use ( $links ) {
			foreach ( $links as $link ) {
				if ( false !== strpos( (string) $html, 'href="' . esc_url( $link ) ) ) {
					return false;
				}
			}

			return true;
		} ) );
	}

	/**
	 * When every category of the product is hidden, the empty "Category:" line goes too.
	 */
	public function bufferProductMeta() {
		if ( $this->getHiddenCategories() ) {
			ob_start();
		}
	}

	public function flushProductMeta() {
		if ( ! $this->getHiddenCategories() ) {
			return;
		}

		$html = (string) ob_get_clean();

		echo preg_replace( '#<span class="posted_in">(?:(?!</span>)(?!<a ).)*</span>#s', '', $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @return string[] permalinks (without the trailing slash) of the hidden categories
	 */
	protected function getHiddenCategoryLinks(): array {
		$links = array();

		foreach ( $this->getHiddenCategories() as $termId ) {
			$link = get_term_link( $termId, 'product_cat' );

			if ( ! is_wp_error( $link ) ) {
				$links[] = untrailingslashit( $link );
			}
		}

		return $links;
	}

	public function filterMenuItems( $items ) {
		if ( ! is_array( $items ) || is_admin() || ! did_action( 'init' ) ) {
			return $items;
		}

		return array_values( array_filter( $items, function ( $item ) {
			if ( 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
				return ! $this->isCategoryHidden( (int) $item->object_id );
			}

			if ( 'post_type' === $item->type && 'product' === $item->object ) {
				return ! $this->isProductHidden( (int) $item->object_id );
			}

			return true;
		} ) );
	}

	public function filterSitemapQuery( array $args, string $postType ): array {
		if ( 'product' !== $postType ) {
			return $args;
		}

		// the sitemap is read by crawlers, that is, guests
		$this->hiddenProducts = null;
		$hidden               = self::collectHiddenProducts( array(), true );
		$this->hiddenProducts = null;

		if ( $hidden ) {
			$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $hidden ) ) );
		}

		return $args;
	}

	/* --- single views -------------------------------------------------------------------------- */

	public function guardSingleViews() {
		if ( is_singular( 'product' ) && $this->isProductHidden( (int) get_queried_object_id() ) ) {
			$this->deny();
		}

		if ( is_tax( 'product_cat' ) && $this->isCategoryHidden( (int) get_queried_object_id() ) ) {
			$this->deny();
		}
	}

	/**
	 * A guest is sent to the login page (with the way back), everybody else gets a not-found page.
	 */
	protected function deny() {
		if ( self::isGuest() && VisibilitySettings::redirectGuestsFromHidden() ) {
			wp_safe_redirect( ClosedStoreService::getLoginUrl( $this->currentUrl() ) );
			exit;
		}

		// the template loader, which runs right after this action, renders the not-found page
		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	protected function currentUrl(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $path );
	}

	protected function rootId( $product ): int {
		$product = $product instanceof WC_Product ? $product : wc_get_product( (int) $product );

		if ( ! $product ) {
			return 0;
		}

		return (int) ( $product->get_parent_id() ?: $product->get_id() );
	}

	public function onTermsSet( $objectId, $terms, $ttIds, $taxonomy ) {
		if ( 'product_cat' === $taxonomy ) {
			VisibilityMeta::bumpVersion();
		}
	}
}
