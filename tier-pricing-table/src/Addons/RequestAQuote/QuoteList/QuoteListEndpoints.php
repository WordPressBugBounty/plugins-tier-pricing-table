<?php namespace TierPricingTable\Addons\RequestAQuote\QuoteList;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The list's lines priced for the shopper (wc-ajax: the cookie identifies a logged-in customer without a nonce,
 * and page caches never keep it), a logged-in customer's list kept in their account, and the list page made from
 * the settings screen (REST, store managers only).
 */
class QuoteListEndpoints {

	public const SYNC_NONCE = 'tpt_quote_list_sync';

	/**
	 * Where a customer's list is kept between devices.
	 */
	public const USER_META = '_tpt_quote_list';

	public function __construct() {
		add_action( 'wc_ajax_tpt_quote_list_items', array( $this, 'items' ) );
		add_action( 'wc_ajax_tpt_quote_list_sync', array( $this, 'sync' ) );
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * The lines the browser holds, checked and priced for this customer.
	 */
	public function items() {
		$this->guard();

		$lines = QuoteListService::normalizeLines( $this->readJson()['lines'] ?? array() );

		wp_send_json_success( QuoteListService::getList( $lines ) );
	}

	/**
	 * A logged-in customer's list follows them across devices and survives logging in: the browser sends its
	 * lines, they are merged with the list kept in the account (the larger quantity wins), and the merged list
	 * is kept and sent back. 'replace' keeps the browser's list as it is (after a change in the list).
	 */
	public function sync() {
		$this->guard();

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'not logged in' ), 401 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is the check
		$nonce = isset( $_SERVER['HTTP_X_TPT_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TPT_NONCE'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::SYNC_NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page and try again.', 'tier-pricing-table' ) ), 403 );
		}

		$payload = $this->readJson();
		$lines   = QuoteListService::normalizeLines( $payload['lines'] ?? array() );
		$userId  = get_current_user_id();

		if ( 'replace' !== ( $payload['mode'] ?? '' ) ) {
			$stored = QuoteListService::normalizeLines( get_user_meta( $userId, self::USER_META, true ) );
			$merged = array();

			foreach ( array_merge( $stored, $lines ) as $line ) {
				$qty                   = max( $merged[ $line['id'] ]['qty'] ?? 0, $line['qty'] );
				$merged[ $line['id'] ] = array_merge( $merged[ $line['id'] ] ?? array(), $line, array( 'qty' => $qty ) );
			}

			$lines = array_values( $merged );

			$lines = array_slice( $lines, 0, QuoteListSettings::MAX_LINES );
		}

		if ( $lines ) {
			update_user_meta( $userId, self::USER_META, $lines );
		} else {
			delete_user_meta( $userId, self::USER_META );
		}

		wp_send_json_success( array( 'lines' => $lines ) );
	}

	public function registerRoutes() {
		register_rest_route( 'tier-pricing-table/v1', '/quote-list/page', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'createPage' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_woocommerce' ) && current_user_can( 'publish_pages' );
			},
		) );
	}

	/**
	 * A published "Quote list" page with the list shortcode, remembered in the settings. The chosen page is kept,
	 * and a published page that already holds the shortcode is used instead of making a second one.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function createPage( WP_REST_Request $request ) {
		$pageId = QuoteListSettings::getPageId();

		if ( ! $pageId ) {
			$pageId = $this->findListPage();
		}

		if ( ! $pageId ) {
			$pageId = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Quote list', 'tier-pricing-table' ),
				'post_content' => '<!-- wp:shortcode -->[' . QuoteListDisplay::SHORTCODE . ']<!-- /wp:shortcode -->',
			) );

			if ( ! $pageId || is_wp_error( $pageId ) ) {
				return new WP_Error( 'page_failed', __( 'The page could not be created.', 'tier-pricing-table' ), array( 'status' => 500 ) );
			}
		}

		if ( QuoteListSettings::getPageId() !== (int) $pageId ) {
			$settings = get_option( QuoteListSettings::OPTION, array() );
			$settings = is_array( $settings ) ? $settings : array();

			$settings['quote_list_page_id'] = (int) $pageId;

			update_option( QuoteListSettings::OPTION, $settings );
		}

		return rest_ensure_response( array(
			'id'    => (int) $pageId,
			'title' => get_the_title( $pageId ),
			'url'   => get_permalink( $pageId ),
		) );
	}

	/**
	 * A published page that already shows the list (made earlier and then unset in the settings, for instance).
	 */
	protected function findListPage(): int {
		$pages = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			's'              => QuoteListDisplay::SHORTCODE,
			'posts_per_page' => 20,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		) );

		foreach ( $pages as $page ) {
			if ( has_shortcode( $page->post_content, QuoteListDisplay::SHORTCODE ) ) {
				return (int) $page->ID;
			}
		}

		return 0;
	}

	protected function guard() {
		if ( ! QuoteListSettings::isAvailable() ) {
			wp_send_json_error( array( 'message' => __( 'The quote list is not available.', 'tier-pricing-table' ) ), 404 );
		}
	}

	protected function readJson(): array {
		$raw  = file_get_contents( 'php://input' );
		$data = $raw ? json_decode( $raw, true ) : null;

		return is_array( $data ) ? $data : array();
	}
}
