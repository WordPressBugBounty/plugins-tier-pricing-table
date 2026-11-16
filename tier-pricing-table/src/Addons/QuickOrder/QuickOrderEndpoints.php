<?php namespace TierPricingTable\Addons\QuickOrder;

/**
 * The form's requests, on WooCommerce's wc-ajax route (the cart and the customer's session are loaded there).
 *
 * Reading (search, lookup, sheet) returns only what this customer may see and order. Adding checks the
 * customer may use the form and, for logged-in customers, the form's nonce; every line then goes through
 * WooCommerce's own add to cart validation.
 */
class QuickOrderEndpoints {

	const NONCE = 'tpt-quick-order';

	/**
	 * What the form may list and order.
	 *
	 * @var QuickOrderService
	 */
	protected $service;

	public function __construct( QuickOrderService $service ) {
		$this->service = $service;

		add_action( 'wc_ajax_tpt_quick_order_search', array( $this, 'search' ) );
		add_action( 'wc_ajax_tpt_quick_order_lookup', array( $this, 'lookup' ) );
		add_action( 'wc_ajax_tpt_quick_order_sheet', array( $this, 'sheet' ) );
		add_action( 'wc_ajax_tpt_quick_order_add', array( $this, 'add' ) );
	}

	public function search() {
		$this->guard( false );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only, the customer's own prices
		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		wp_send_json_success( array( 'entries' => $this->service->search( $term ) ) );
	}

	public function lookup() {
		$this->guard( false );

		$payload = $this->readJson();
		$skus    = array_map( 'sanitize_text_field', array_filter( (array) ( $payload['skus'] ?? array() ), 'is_scalar' ) );
		$ids     = array_map( 'absint', array_filter( (array) ( $payload['ids'] ?? array() ), 'is_scalar' ) );

		wp_send_json_success( array( 'found' => (object) $this->service->lookup( $skus, $ids ) ) );
	}

	public function sheet() {
		$this->guard( false );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read only, the customer's own prices
		$page = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;

		wp_send_json_success( $this->service->sheet( $page ) );
	}

	public function add() {
		$this->guard( true );

		$payload = $this->readJson();
		$lines   = array();

		foreach ( (array) ( $payload['lines'] ?? array() ) as $line ) {
			if ( is_array( $line ) ) {
				$lines[] = array(
					'id'  => absint( $line['id'] ?? 0 ),
					'qty' => absint( $line['qty'] ?? 0 ),
				);
			}
		}

		if ( empty( $lines ) ) {
			wp_send_json_error( array( 'message' => __( 'Add a quantity to at least one product.', 'tier-pricing-table' ) ), 400 );
		}

		wp_send_json_success( $this->service->addToCart( $lines ) );
	}

	protected function guard( bool $writing ) {

		if ( ! QuickOrderSettingsSection::isEnabled() ) {
			wp_send_json_error( array( 'message' => __( 'The quick order form is not available.', 'tier-pricing-table' ) ), 404 );
		}

		if ( ! $this->service->canUse() ) {
			wp_send_json_error( array( 'message' => QuickOrderSettingsSection::getDeniedMessage() ), 403 );
		}

		// Guests' pages are often cached, so a nonce printed in them may be stale: only accounts are checked.
		if ( $writing && is_user_logged_in() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- this is the check
			$nonce = isset( $_SERVER['HTTP_X_TPT_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TPT_NONCE'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
				wp_send_json_error( array( 'message' => __( 'Your session has expired. Reload the page and try again.', 'tier-pricing-table' ) ), 403 );
			}
		}
	}

	protected function readJson(): array {
		$raw  = file_get_contents( 'php://input' );
		$data = $raw ? json_decode( $raw, true ) : null;

		return is_array( $data ) ? $data : array();
	}
}
