<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings;

/**
 * The application form's field definitions: what the store owner built in the settings, normalised.
 *
 * A field is [ 'key', 'label', 'type', 'required', 'options' ]. Keys are generated from the label once
 * and then kept, so renaming a label does not change where the value goes. The keys company, phone,
 * tax_id, website and message are recognised and copied to the customer record; every other key is a
 * custom field stored on the application only.
 */
class FormFields {

	const KNOWN_KEYS = array( 'company', 'phone', 'tax_id', 'website', 'message' );

	public static function getTypes(): array {
		return array(
			'text'     => __( 'Text', 'tier-pricing-table' ),
			'textarea' => __( 'Paragraph', 'tier-pricing-table' ),
			'tel'      => __( 'Phone', 'tier-pricing-table' ),
			'email'    => __( 'E-mail', 'tier-pricing-table' ),
			'url'      => __( 'Website', 'tier-pricing-table' ),
			'number'   => __( 'Number', 'tier-pricing-table' ),
			'select'   => __( 'Dropdown', 'tier-pricing-table' ),
			'checkbox' => __( 'Checkbox', 'tier-pricing-table' ),
		);
	}

	/**
	 * The fields a fresh install starts with.
	 */
	public static function getDefaults(): array {
		return array(
			self::normalize( array( 'key' => 'company', 'label' => __( 'Company', 'tier-pricing-table' ), 'type' => 'text', 'required' => true ) ),
			self::normalize( array( 'key' => 'phone', 'label' => __( 'Phone', 'tier-pricing-table' ), 'type' => 'tel' ) ),
		);
	}

	/**
	 * Suggested rows for the "Add field" menu: the known keys with their labels and types.
	 */
	public static function getPresets(): array {
		return array(
			'company' => array( 'label' => __( 'Company', 'tier-pricing-table' ), 'type' => 'text' ),
			'phone'   => array( 'label' => __( 'Phone', 'tier-pricing-table' ), 'type' => 'tel' ),
			'tax_id'  => array( 'label' => __( 'Tax / VAT ID', 'tier-pricing-table' ), 'type' => 'text' ),
			'website' => array( 'label' => __( 'Website', 'tier-pricing-table' ), 'type' => 'url' ),
			'message' => array( 'label' => __( 'Tell us about your business', 'tier-pricing-table' ), 'type' => 'textarea' ),
		);
	}

	/**
	 * Decodes the stored JSON into a clean list of fields; falls back to the defaults.
	 */
	public static function fromJson( $json ): array {
		if ( is_array( $json ) ) {
			$rows = $json;
		} else {
			$rows = json_decode( (string) $json, true );
		}

		if ( ! is_array( $rows ) ) {
			return self::getDefaults();
		}

		return self::sanitizeList( $rows );
	}

	public static function toJson( array $fields ): string {
		return (string) wp_json_encode( array_values( $fields ) );
	}

	/**
	 * Cleans a list of raw rows (from the settings form or stored JSON): drops rows without a label,
	 * generates missing keys from the label and keeps every key unique.
	 */
	public static function sanitizeList( array $rows ): array {
		$fields = array();
		$used   = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$field = self::normalize( $row );

			if ( '' === $field['label'] ) {
				continue;
			}

			$base = $field['key'] ?: self::keyFromLabel( $field['label'] );
			$key  = $base;
			$n    = 2;

			while ( isset( $used[ $key ] ) ) {
				$key = $base . '_' . $n ++;
			}

			$used[ $key ] = true;
			$field['key'] = $key;
			$fields[]     = $field;
		}

		return $fields;
	}

	public static function normalize( array $row ): array {
		$type  = (string) ( $row['type'] ?? 'text' );
		$type  = array_key_exists( $type, self::getTypes() ) ? $type : 'text';
		$label = self::cleanText( $row['label'] ?? '' );

		$options = $row['options'] ?? array();

		if ( ! is_array( $options ) ) {
			$options = preg_split( '/\r\n|\r|\n|,/', (string) $options );
		}

		$options = array_values( array_filter( array_map( array( __CLASS__, 'cleanText' ), (array) $options ), 'strlen' ) );

		return array(
			'key'      => self::cleanKey( $row['key'] ?? '' ),
			'label'    => mb_substr( $label, 0, 100 ),
			'type'     => $type,
			'required' => ! empty( $row['required'] ) && 'no' !== $row['required'],
			'options'  => 'select' === $type ? $options : array(),
		);
	}

	public static function keyFromLabel( string $label ): string {
		$key = function_exists( 'remove_accents' ) ? remove_accents( $label ) : $label;
		$key = strtolower( trim( (string) preg_replace( '/[^a-zA-Z0-9]+/', '_', $key ), '_' ) );
		$key = mb_substr( $key, 0, 40 );

		return $key ?: 'field';
	}

	public static function cleanKey( $key ): string {
		return strtolower( (string) preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $key ) );
	}

	public static function isKnownKey( string $key ): bool {
		return in_array( $key, self::KNOWN_KEYS, true );
	}

	protected static function cleanText( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';

		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : trim( strip_tags( $value ) );
	}
}
