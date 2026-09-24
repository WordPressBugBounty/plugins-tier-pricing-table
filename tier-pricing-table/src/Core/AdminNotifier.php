<?php namespace TierPricingTable\Core;

class AdminNotifier {

	const ERROR = 'error';

	const WARNING = 'warning';

	const SUCCESS = 'success';

	const INFO = 'info';

	/**
	 * Notification key
	 *
	 * @var string
	 */
	private $key = 'u2code_admin_notifications';

	/**
	 * AdminNotifier constructor.
	 */
	public function __construct() {
		// Flash messages are processed on admin_init: the current user is not known yet when the plugin boots,
		// and processing on every request would let a frontend visit consume a notice meant for an admin.
		add_action( 'admin_init', array( $this, 'process' ) );
	}

	/**
	 * Per-user notification key. Resolved lazily so the current user is already determined.
	 *
	 * @return string
	 */
	private function getKey(): string {
		return $this->key . get_current_user_id();
	}

	/**
	 * Add message to show on admin_notices action
	 *
	 * @param string $message
	 * @param string $type
	 * @param bool $isDismissible
	 */
	public function push( $message, $type = self::SUCCESS, $isDismissible = false ) {
		$dismissible = $isDismissible ? 'is-dismissible' : '';
		add_action( 'admin_notices', function () use ( $message, $type, $dismissible ) {
			echo "<div class='notice notice-" . esc_attr($type) . ' ' . esc_attr($dismissible) . "'><p>" . wp_kses_post($message) . '</p></div>';
		} );
	}

	/**
	 * Save flash message to show during next request
	 *
	 * @param string $message
	 * @param string $type
	 * @param bool $isDismissible
	 */
	public function flash( $message, $type = self::SUCCESS, $isDismissible = false ) {
		$message  = array( 'message' => $message, 'type' => $type, 'dismissible' => $isDismissible );
		$messages = get_transient( $this->getKey() );

		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		$messages[] = $message;

		set_transient( $this->getKey(), $messages, MINUTE_IN_SECONDS );
	}

	/**
	 * Show flash messages
	 */
	public function process() {
		$messages = get_transient( $this->getKey() );

		//Resolve conflict with background process
		if ( ! wp_doing_ajax() ) {
			if ( is_array( $messages ) ) {

				delete_transient( $this->getKey() );

				foreach ( $messages as $message ) {
					$this->push( $message['message'], $message['type'], $message['dismissible'] );
				}
			}
		}
	}
}
