<?php namespace TierPricingTable\Core;

/**
 * The colour the active theme uses for buttons or accents, read from high-confidence sources only: the
 * button background of a block theme (resolved through its palette) and the settings of a few classic
 * themes whose option keys are known. Anything else is "unknown", never a guess.
 *
 * A detected colour is only returned when it is a hex colour that white text stays readable on.
 */
class ThemeColor {

	/**
	 * Minimum contrast ratio between the colour and white text (the highlighted tier prints white text).
	 */
	const MIN_CONTRAST = 3.0;

	/**
	 * Known classic themes: stylesheet or template slug => reader.
	 */
	public static function detect(): ?string {
		$color = null;

		if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$color = self::fromBlockTheme();
		} else {
			$color = self::fromClassicTheme();
		}

		$color = apply_filters( 'tiered_pricing_table/install/theme_color', $color );

		return self::isUsable( $color ) ? self::normalize( $color ) : null;
	}

	/**
	 * Block themes: the button background from the global styles, resolved through the palette.
	 */
	public static function fromBlockTheme(): ?string {
		if ( ! function_exists( 'wp_get_global_styles' ) ) {
			return null;
		}

		$background = wp_get_global_styles( array( 'elements', 'button', 'color', 'background' ) );

		return is_string( $background ) ? self::resolvePreset( $background, self::palette() ) : null;
	}

	/**
	 * Classic themes with known colour settings. The defaults are the themes' own defaults, used when the
	 * setting was never saved, because that is what the storefront shows.
	 */
	public static function fromClassicTheme(): ?string {
		$theme = wp_get_theme();
		$slugs = array_unique( array_filter( array( strtolower( (string) $theme->get_stylesheet() ), strtolower( (string) $theme->get_template() ) ) ) );

		foreach ( $slugs as $slug ) {
			switch ( $slug ) {
				case 'storefront':
					return (string) get_theme_mod( 'storefront_accent_color', '#96588a' );
				case 'astra':
					return self::fromAstra( (array) get_option( 'astra-settings', array() ) );
				case 'oceanwp':
					return (string) get_theme_mod( 'ocean_primary_color', '#13aff0' );
				case 'flatsome':
					return (string) get_theme_mod( 'color_primary', '#446084' );
			}
		}

		return null;
	}

	/**
	 * Astra stores the theme colour as a palette reference (var(--ast-global-color-N)) in recent versions
	 * and as a plain hex in older ones.
	 */
	public static function fromAstra( array $settings ): ?string {
		$value = isset( $settings['theme-color'] ) ? (string) $settings['theme-color'] : '';

		if ( preg_match( '/var\(--ast-global-color-(\d+)\)/', $value, $m ) ) {
			$palette = $settings['global-color-palette']['palette'] ?? array();

			return isset( $palette[ (int) $m[1] ] ) ? (string) $palette[ (int) $m[1] ] : null;
		}

		return $value ?: null;
	}

	/**
	 * Palette of the current global settings as slug => colour; custom entries override theme entries,
	 * theme entries override WordPress defaults.
	 */
	public static function palette(): array {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return array();
		}

		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		$map     = array();

		foreach ( array( 'default', 'theme', 'custom' ) as $origin ) {
			foreach ( (array) ( $palette[ $origin ] ?? array() ) as $entry ) {
				if ( isset( $entry['slug'], $entry['color'] ) ) {
					$map[ (string) $entry['slug'] ] = (string) $entry['color'];
				}
			}
		}

		return $map;
	}

	/**
	 * A global-styles value to a colour: a palette reference in either notation, or a literal.
	 */
	public static function resolvePreset( string $value, array $palette ): ?string {
		$value = trim( $value );

		if ( preg_match( '/^var:preset\|color\|([a-z0-9-]+)$/i', $value, $m ) || preg_match( '/^var\(--wp--preset--color--([a-z0-9-]+)\)$/i', $value, $m ) ) {
			return $palette[ strtolower( $m[1] ) ] ?? null;
		}

		return $value ?: null;
	}

	/**
	 * A 3- or 6-digit hex colour that white text is readable on.
	 */
	public static function isUsable( $color ): bool {
		if ( ! is_string( $color ) || ! preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', trim( $color ) ) ) {
			return false;
		}

		return self::contrastWithWhite( $color ) >= self::MIN_CONTRAST;
	}

	public static function normalize( string $color ): string {
		$hex = ltrim( strtolower( trim( $color ) ), '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return '#' . $hex;
	}

	/**
	 * WCAG contrast ratio between the colour and white.
	 */
	public static function contrastWithWhite( string $color ): float {
		$hex      = ltrim( self::normalize( $color ), '#' );
		$channels = array_map( function ( $pair ) {
			$c = hexdec( $pair ) / 255;

			return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		}, str_split( $hex, 2 ) );

		$luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];

		return round( 1.05 / ( $luminance + 0.05 ), 2 );
	}
}
