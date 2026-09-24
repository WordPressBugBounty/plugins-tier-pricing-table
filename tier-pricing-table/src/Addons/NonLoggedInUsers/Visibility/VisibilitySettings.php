<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\Settings;

class VisibilitySettings {

	const PREFIX = Settings::SETTINGS_PREFIX . 'visibility_';

	public static function optionId( string $key ): string {
		return self::PREFIX . $key;
	}

	protected static function get( string $key, $default = null ) {
		return get_option( self::optionId( $key ), $default );
	}

	/**
	 * Role-based catalog visibility: the "Who can see" rules on products and categories. Off by default,
	 * so the product and category screens stay as they are for stores that do not need it.
	 */
	public static function isEnabled(): bool {
		return 'yes' === self::get( 'enabled', 'no' );
	}

	/**
	 * Closed store: visitors who are not logged in are sent to the login page.
	 */
	public static function isClosedStore(): bool {
		return 'yes' === self::get( 'closed_store', 'no' );
	}

	/**
	 * @return int[] page ids visitors may open while the store is closed (besides My Account and the wholesale registration page)
	 */
	public static function getOpenPageIds(): array {
		return array_values( array_filter( array_map( 'intval', TPTCheckboxListOption::toArray( self::get( 'closed_store_pages', '' ) ) ) ) );
	}

	/**
	 * A guest who opens a hidden product or category: login page (yes) or a not-found page (no).
	 */
	public static function redirectGuestsFromHidden(): bool {
		return 'yes' === self::get( 'hidden_redirect', 'yes' );
	}
}
