<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Frontend;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services\ApplicationService;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\TierPricingTablePlugin;
use WP_Error;

/**
 * The [tiered_pricing_wholesale_registration] shortcode: renders the application form (or the
 * applicant's status) and handles its submission.
 */
class RegistrationForm {

	const SHORTCODE = 'tiered_pricing_wholesale_registration';
	const NONCE     = 'tpt_wholesale_register';
	const QUERY_VAR = 'tpt-wholesale';

	protected ?WP_Error $errors = null;

	protected array $values = array();

	protected bool $rendered = false;

	public function __construct() {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'maybeHandleSubmission' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'registerAssets' ) );
	}

	public function registerAssets() {
		wp_register_style( 'tpt-wholesale-registration', plugins_url( '../assets/css/wholesale-registration.css', __FILE__ ), array(), TierPricingTablePlugin::VERSION );
	}

	/**
	 * Processes the posted form before any output, so the result can be a redirect.
	 */
	public function maybeHandleSubmission() {
		if ( empty( $_POST['tpt_wholesale_action'] ) || 'register' !== $_POST['tpt_wholesale_action'] ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			$this->errors = new WP_Error( 'nonce', __( 'The form has expired. Please try again.', 'tier-pricing-table' ) );

			return;
		}

		$input = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above; the validator sanitizes every value it uses

		if ( ! $this->verifyRecaptcha( $input ) ) {
			$this->errors = new WP_Error( 'recaptcha', __( 'The spam check failed. Please try again.', 'tier-pricing-table' ) );
			$this->values = $input;

			return;
		}

		$currentUser = is_user_logged_in() ? wp_get_current_user() : null;
		$result      = ( new ApplicationService() )->submit( $input, $currentUser );

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			$this->values = $input;

			return;
		}

		$outcome = $result->isApproved() ? 'approved' : 'submitted';

		if ( $result->isApproved() && ! $currentUser && $result->getUserId() ) {
			wc_set_customer_auth_cookie( $result->getUserId() );
		}

		wp_safe_redirect( add_query_arg( self::QUERY_VAR, $outcome, $this->currentUrl() ) );
		exit;
	}

	public function render( $atts = array() ): string {
		$this->rendered = true;

		wp_enqueue_style( 'tpt-wholesale-registration' );

		$user        = is_user_logged_in() ? wp_get_current_user() : null;
		$application = $user ? WholesaleApplication::findForUser( $user->ID ) : null;
		$outcome     = isset( $_GET[ self::QUERY_VAR ] ) ? sanitize_key( $_GET[ self::QUERY_VAR ] ) : '';
		$fileManager = ServiceContainer::getInstance()->getFileManager();
		$viewsPath   = plugin_dir_path( dirname( __FILE__ ) ) . 'views/';

		ob_start();

		if ( $user && ApplicationService::isWholesaleUser( $user ) ) {
			$fileManager->includeTemplate( 'frontend/application-status.php', array(
				'status'  => 'approved',
				'message' => Settings::getApprovedMessage(),
				'user'    => $user,
			), $viewsPath );
		} elseif ( ( $application && $application->isPending() ) || 'submitted' === $outcome ) {
			$fileManager->includeTemplate( 'frontend/application-status.php', array(
				'status'  => 'pending',
				'message' => Settings::getPendingMessage(),
				'user'    => $user,
			), $viewsPath );
		} elseif ( $application && $application->isRejected() ) {
			$fileManager->includeTemplate( 'frontend/application-status.php', array(
				'status'  => 'rejected',
				'message' => Settings::getRejectedMessage(),
				'user'    => $user,
			), $viewsPath );
		} else {
			$this->enqueueRecaptcha();

			$fileManager->includeTemplate( 'frontend/registration-form.php', array(
				'user'          => $user,
				'fields'        => Settings::getFormFields(),
				'needsPassword' => null === $user && ApplicationService::needsPassword(),
				'termsPageId'   => Settings::getTermsPageId(),
				'errors'        => $this->errors,
				'values'        => $this->values,
				'title'         => Settings::getFormTitle(),
				'intro'         => Settings::getFormIntro(),
				'submitText'    => Settings::getSubmitText(),
				'recaptchaKey'  => Settings::useRecaptcha() ? Settings::getRecaptchaKeys()['site'] : '',
				'loginUrl'      => wp_login_url( $this->currentUrl() ),
				'form'          => $this,
			), $viewsPath );
		}

		return (string) ob_get_clean();
	}

	/**
	 * The posted value of a field, for re-filling the form after a validation error.
	 */
	public function value( string $field ): string {
		return isset( $this->values[ $field ] ) && is_scalar( $this->values[ $field ] ) ? (string) $this->values[ $field ] : '';
	}

	public function error( string $field ): string {
		return $this->errors instanceof WP_Error ? (string) $this->errors->get_error_message( $field ) : '';
	}

	/**
	 * The URL of the page the form is on, without the outcome flag.
	 */
	protected function currentUrl(): string {
		if ( is_singular() && get_queried_object_id() ) {
			$url = (string) get_permalink( get_queried_object_id() );
		} else {
			// the request URI already contains the site's path, so it is combined with the host only
			$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : (string) wp_parse_url( home_url(), PHP_URL_HOST );
			$path = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
			$url  = ( is_ssl() ? 'https://' : 'http://' ) . $host . $path;
		}

		return remove_query_arg( self::QUERY_VAR, esc_url_raw( $url ) );
	}

	protected function enqueueRecaptcha() {
		if ( ! Settings::useRecaptcha() ) {
			return;
		}

		wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( Settings::getRecaptchaKeys()['site'] ), array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	protected function verifyRecaptcha( array $input ): bool {
		if ( ! Settings::useRecaptcha() ) {
			return true;
		}

		$token = isset( $input['g-recaptcha-response'] ) ? sanitize_text_field( $input['g-recaptcha-response'] ) : '';

		if ( '' === $token ) {
			return false;
		}

		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
			'timeout' => 10,
			'body'    => array(
				'secret'   => Settings::getRecaptchaKeys()['secret'],
				'response' => $token,
			),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return ! empty( $body['success'] ) && (float) ( $body['score'] ?? 0 ) >= 0.5;
	}
}
