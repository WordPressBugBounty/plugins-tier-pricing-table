<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;
use WP_User;

/**
 * Sends approved wholesale customers to their landing page after login, and adds the
 * "Apply for a wholesale account" link to the WooCommerce login form.
 */
class LoginRedirect {

	public function __construct() {
		add_filter( 'woocommerce_login_redirect', array( $this, 'redirect' ), 10, 2 );
		add_filter( 'login_redirect', array( $this, 'redirect' ), 10, 3 );
		add_action( 'woocommerce_login_form_end', array( $this, 'loginFormLink' ) );
	}

	/**
	 * @param  string  $redirect
	 * @param  WP_User|string|null  $user  WooCommerce passes the user as the second argument, WordPress as the third
	 * @param  WP_User|\WP_Error|null  $wpUser
	 */
	public function redirect( $redirect, $user = null, $wpUser = null ) {
		$user = $user instanceof WP_User ? $user : ( $wpUser instanceof WP_User ? $wpUser : null );

		if ( ! $user || ! ApplicationService::isWholesaleUser( $user ) ) {
			return $redirect;
		}

		$pageId = Settings::getLoginRedirectPageId();
		$url    = $pageId ? get_permalink( $pageId ) : '';

		return $url ? $url : $redirect;
	}

	public function loginFormLink() {
		$url = Settings::getRegistrationPageUrl();

		if ( ! $url || ! Settings::showLoginFormLink() ) {
			return;
		}
		?>
		<p class="tpt-wholesale-apply-link">
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( Settings::getLoginFormLinkText() ); ?></a>
		</p>
		<?php
	}
}
