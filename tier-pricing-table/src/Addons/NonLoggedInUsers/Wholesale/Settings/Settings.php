<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings;

use TierPricingTable\Settings\Settings as MainSettings;

/**
 * Registers the "Wholesale" settings tab and reads its options.
 */
class Settings {

	const PREFIX = MainSettings::SETTINGS_PREFIX . 'wholesale_';

	const APPROVAL_MANUAL = 'manual';
	const APPROVAL_AUTO   = 'auto';

	public function __construct() {
		new FormFieldsOption();

		add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
			$sections[] = new WholesaleSettingsSection();

			return $sections;
		}, 15 );

		add_filter( 'tiered_pricing_table/settings/sections_order', function ( $order ) {
			$order[ WholesaleSettingsSection::SLUG ] = 55;

			return $order;
		} );
	}

	public static function optionId( string $key ): string {
		return self::PREFIX . $key;
	}

	protected static function get( string $key, $default = null ) {
		return get_option( self::optionId( $key ), $default );
	}

	/**
	 * The feature is shipped switched off: the store owner turns it on here once the form page and the
	 * role prices are ready.
	 */
	public static function isEnabled(): bool {
		return 'yes' === self::get( 'enabled', 'no' );
	}

	public static function getRole(): string {
		$role = (string) self::get( 'role', '' );

		if ( '' === $role && function_exists( 'wp_roles' ) && wp_roles()->is_role( 'wholesale' ) ) {
			$role = 'wholesale';
		}

		return $role;
	}

	public static function getApprovalMode(): string {
		return self::APPROVAL_AUTO === self::get( 'approval', self::APPROVAL_MANUAL ) ? self::APPROVAL_AUTO : self::APPROVAL_MANUAL;
	}

	public static function isAutoApproval(): bool {
		return self::APPROVAL_AUTO === self::getApprovalMode();
	}

	/**
	 * The application form's fields as built in the settings.
	 *
	 * @return array[] [ key, label, type, required, options ] each
	 */
	public static function getFormFields(): array {
		return FormFields::fromJson( self::get( 'form_fields', '' ) );
	}

	public static function getRegistrationPageId(): int {
		return (int) self::get( 'registration_page', 0 );
	}

	public static function getRegistrationPageUrl(): string {
		$pageId = self::getRegistrationPageId();

		return $pageId ? (string) get_permalink( $pageId ) : '';
	}

	public static function getLoginRedirectPageId(): int {
		return (int) self::get( 'login_redirect_page', 0 );
	}

	public static function showLoginFormLink(): bool {
		return 'yes' === self::get( 'login_form_link', 'yes' );
	}

	public static function getLoginFormLinkText(): string {
		return (string) self::get( 'login_form_link_text', __( 'Apply for a wholesale account', 'tier-pricing-table' ) );
	}

	public static function getFormTitle(): string {
		return (string) self::get( 'form_title', __( 'Wholesale account', 'tier-pricing-table' ) );
	}

	public static function getFormIntro(): string {
		return (string) self::get( 'form_intro',
			__( 'Fill in the form below. We review every application and e-mail you as soon as your account is approved.',
				'tier-pricing-table' ) );
	}

	public static function getSubmitText(): string {
		return (string) self::get( 'submit_text', __( 'Apply for a wholesale account', 'tier-pricing-table' ) );
	}

	public static function getPendingMessage(): string {
		return (string) self::get( 'pending_message',
			__( 'Thank you! Your application has been received. We will e-mail you when it is approved.',
				'tier-pricing-table' ) );
	}

	public static function getApprovedMessage(): string {
		return (string) self::get( 'approved_message',
			__( 'Your wholesale account is ready. You are logged in and see wholesale prices from now on.',
				'tier-pricing-table' ) );
	}

	public static function getRejectedMessage(): string {
		return (string) self::get( 'rejected_message',
			__( 'We could not approve your wholesale application. Contact us if you have questions.',
				'tier-pricing-table' ) );
	}

	public static function getTermsPageId(): int {
		return (int) self::get( 'terms_page', 0 );
	}

	public static function useRecaptcha(): bool {
		$keys = self::getRecaptchaKeys();

		return 'yes' === self::get( 'recaptcha', 'no' ) && $keys['site'] && $keys['secret'];
	}

	/**
	 * The wholesale form's own reCAPTCHA v3 keys.
	 *
	 * @return array{site: string, secret: string}
	 */
	public static function getRecaptchaKeys(): array {
		return array(
			'site'   => trim( (string) self::get( 'recaptcha_site_key', '' ) ),
			'secret' => trim( (string) self::get( 'recaptcha_secret_key', '' ) ),
		);
	}
}
