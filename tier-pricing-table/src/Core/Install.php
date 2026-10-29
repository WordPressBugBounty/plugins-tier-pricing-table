<?php namespace TierPricingTable\Core;

use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;

/**
 * First-run bookkeeping. Records the version the plugin was installed with, so later releases can give
 * new stores different defaults without touching existing ones, and gives a fresh store its first
 * active tier colour: the theme's button or accent colour when it can be read with confidence,
 * otherwise a neutral dark colour that reads well with white text on any theme.
 *
 * Runs from the activation hook and, as a fallback for installs that never fire it (network
 * activation, some hosts, WP-CLI with themes skipped), on the first admin request.
 */
class Install {

	const VERSION_OPTION = 'tier_pricing_table_installed_version';

	/**
	 * The colour taken from the theme on a fresh install ('' when none was found), so the settings
	 * screen can say where the colour came from.
	 */
	const THEME_COLOR_OPTION = 'tier_pricing_table_theme_color';

	/**
	 * Stored for stores that existed before the marker was introduced: their install version is unknown.
	 */
	const LEGACY_VERSION = '0';

	/**
	 * Active tier colour for a fresh store when the theme gives nothing usable.
	 */
	const FRESH_INSTALL_COLOR = '#1f2937';

	public static function init() {
		add_action( 'admin_init', array( self::class, 'maybeRun' ), 1 );
	}

	public static function maybeRun() {
		if ( ! self::getInstalledVersion() ) {
			self::run();
		}
	}

	/**
	 * @param  callable|null  $hasStoredOptions  Tells whether any plugin option exists (tests inject it).
	 * @param  callable|null  $detectThemeColor  Returns the theme colour or null (tests inject it).
	 */
	public static function run( ?callable $hasStoredOptions = null, ?callable $detectThemeColor = null ) {
		if ( self::getInstalledVersion() ) {
			return;
		}

		$hasStoredOptions = $hasStoredOptions ?: array( self::class, 'hasStoredOptions' );
		$detectThemeColor = $detectThemeColor ?: array( ThemeColor::class, 'detect' );

		if ( call_user_func( $hasStoredOptions ) ) {
			// an existing store: only record that the install predates the marker
			update_option( self::VERSION_OPTION, self::LEGACY_VERSION, false );

			return;
		}

		self::applyFreshDefaults( $detectThemeColor );
	}

	/**
	 * After "Reset settings": forget the install and start over like a new store, theme colour included.
	 */
	public static function reset( ?callable $detectThemeColor = null ) {
		delete_option( self::VERSION_OPTION );
		delete_option( self::THEME_COLOR_OPTION );
		self::applyFreshDefaults( $detectThemeColor ?: array( ThemeColor::class, 'detect' ) );
	}

	protected static function applyFreshDefaults( callable $detectThemeColor ) {
		$themeColor = call_user_func( $detectThemeColor );

		update_option( Settings::SETTINGS_PREFIX . 'selected_quantity_color', $themeColor ?: self::FRESH_INSTALL_COLOR, true );
		update_option( self::THEME_COLOR_OPTION, $themeColor ?: '', false );
		update_option( self::VERSION_OPTION, TierPricingTablePlugin::VERSION, false );
	}

	/**
	 * The version the plugin was installed with, '0' for stores older than the marker, '' when not recorded yet.
	 */
	public static function getInstalledVersion(): string {
		return (string) get_option( self::VERSION_OPTION, '' );
	}

	/**
	 * Whether the store was installed with at least the given version (never true for legacy stores).
	 */
	public static function installedSince( string $version ): bool {
		$installed = self::getInstalledVersion();

		return '' !== $installed && self::LEGACY_VERSION !== $installed && version_compare( $installed, $version, '>=' );
	}

	/**
	 * The colour applied from the theme on install, '' when the default was used.
	 */
	public static function getThemeColor(): string {
		return (string) get_option( self::THEME_COLOR_OPTION, '' );
	}

	/**
	 * Any option of the plugin stored, whatever its value.
	 */
	public static function hasStoredOptions(): bool {
		global $wpdb;

		$like = $wpdb->esc_like( Settings::SETTINGS_PREFIX ) . '%';

		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 1", $like ) );
	}
}
