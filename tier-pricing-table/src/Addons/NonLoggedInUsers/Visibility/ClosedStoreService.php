<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings as WholesaleSettings;
use WP_Error;

/**
 * Closed store: visitors who are not logged in are sent to the login page. My Account (login,
 * registration, lost password), the wholesale registration page and the pages chosen in the settings
 * stay open. Visitors get no products from the REST APIs or the feeds either, and cannot buy.
 */
class ClosedStoreService {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'guard' ), 1 );
		add_filter( 'rest_pre_dispatch', array( $this, 'guardStoreApi' ), 10, 3 );
		// after WooCommerce, which sets post__in on product feeds and archives (loop_shop_post_in)
		add_action( 'pre_get_posts', array( $this, 'emptyProductQueries' ), 1000 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filterPurchasable' ), 20 );
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'filterPurchasable' ), 20 );
		add_filter( 'woocommerce_login_redirect', array( $this, 'redirectBack' ), 20 );
	}

	public function guard() {
		if ( ! VisibilitySettings::isClosedStore() || is_user_logged_in() ) {
			return;
		}

		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}

		// feeds and robots stay; a not-found page tells nothing and must not loop through the login page
		if ( is_feed() || is_robots() || is_trackback() || is_favicon() || is_404() ) {
			return;
		}

		if ( $this->isOpenPage() ) {
			return;
		}

		wp_safe_redirect( self::getLoginUrl( $this->currentUrl() ) );
		exit;
	}

	protected function isOpenPage(): bool {
		$open = array_merge( array( (int) wc_get_page_id( 'myaccount' ), WholesaleSettings::getRegistrationPageId() ), VisibilitySettings::getOpenPageIds() );
		$open = array_values( array_filter( array_map( 'intval', $open ), function ( $id ) {
			return $id > 0;
		} ) );

		if ( $open && is_page( $open ) ) {
			return true;
		}

		// the WooCommerce login form on a page that is not the My Account page (a login shortcode, for example)
		if ( is_page() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'woocommerce_my_account' ) ) {
			return true;
		}

		return (bool) apply_filters( 'tiered_pricing_table/closed_store/is_open_page', false );
	}

	/**
	 * While the store is closed, visitors get nothing from the Store API (it answers under /wc/store/v1 and
	 * /wc/store alike) but their empty cart, nothing from WooCommerce's catalog routes for AI agents, and no
	 * products or product categories from WordPress's own routes.
	 *
	 * @param  mixed  $result
	 * @param  \WP_REST_Server  $server
	 * @param  \WP_REST_Request  $request
	 */
	public function guardStoreApi( $result, $server, $request ) {
		if ( null !== $result || ! self::isClosedFor() ) {
			return $result;
		}

		$route = (string) $request->get_route();

		// reading the cart stays: a mini cart on an open page asks for it
		$readsCart = 'GET' === $request->get_method() && preg_match( '#^/wc/store(?:/v\d+)?/cart/?$#', $route );

		$closed = ( preg_match( '#^/wc/store(?:/|$)#', $route ) && ! $readsCart )
		          || preg_match( '#^/wc/ucp(?:/|$)#', $route )
		          || preg_match( '#^/wp/v2/(?:product|product_variation|product_cat|product_tag|product_brand)(?:/|$)#', $route );

		if ( $closed ) {
			return new WP_Error( 'tpt_closed_store', __( 'Please log in to browse the store.', 'tier-pricing-table' ), array( 'status' => 401 ) );
		}

		return $result;
	}

	/**
	 * Product queries made for visitors through the REST APIs (search included) and the feeds find nothing
	 * while the store is closed. Other post types in the same query stay.
	 *
	 * @param  \WP_Query  $query
	 */
	public function emptyProductQueries( $query ) {
		if ( ! self::isClosedFor() || ! ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || $query->is_feed() ) ) {
			return;
		}

		$productTypes = array( 'product', 'product_variation' );
		$postType     = $query->get( 'post_type' );

		if ( 'any' === $postType ) {
			$postType = array_values( get_post_types( array( 'exclude_from_search' => false ) ) );
		}

		$types = array_filter( (array) $postType );

		if ( $types && array_intersect( $types, $productTypes ) ) {
			$others = array_values( array_diff( $types, $productTypes ) );

			if ( $others ) {
				$query->set( 'post_type', $others );
			} else {
				$query->set( 'post__in', array( 0 ) );
			}

			return;
		}

		// a product category or tag feed names no post type
		if ( $query->is_tax( get_object_taxonomies( 'product' ) ) ) {
			$query->set( 'post__in', array( 0 ) );
		}
	}

	/**
	 * Nothing can be bought by a visitor while the store is closed: no add-to-cart links, no cart and no checkout
	 * (WooCommerce takes products that cannot be bought out of the cart before checkout).
	 *
	 * @param  bool  $purchasable
	 *
	 * @return bool
	 */
	public function filterPurchasable( $purchasable ) {
		return $purchasable && ! self::isClosedFor();
	}

	/**
	 * Whether the store is closed to the current visitor. Background work (cron, WP-CLI) is not a visitor:
	 * product feeds for marketplaces and subscription renewals keep working.
	 */
	public static function isClosedFor(): bool {
		if ( ! VisibilitySettings::isClosedStore() || is_user_logged_in() ) {
			return false;
		}

		return ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI );
	}

	/**
	 * After the login the visitor lands where the closed store stopped them.
	 */
	public function redirectBack( $redirect ) {
		$back = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $back && wp_validate_redirect( $back, '' ) ? $back : $redirect;
	}

	/**
	 * The login page (My Account, or wp-login.php without it) with the way back.
	 */
	public static function getLoginUrl( string $redirectTo = '' ): string {
		$pageId = (int) wc_get_page_id( 'myaccount' );

		if ( $pageId < 1 || 'publish' !== get_post_status( $pageId ) ) {
			return wp_login_url( $redirectTo );
		}

		$url = get_permalink( $pageId );

		return $redirectTo ? add_query_arg( 'redirect_to', rawurlencode( $redirectTo ), $url ) : $url;
	}

	protected function currentUrl(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';

		return esc_url_raw( ( is_ssl() ? 'https://' : 'http://' ) . $host . $path );
	}
}
