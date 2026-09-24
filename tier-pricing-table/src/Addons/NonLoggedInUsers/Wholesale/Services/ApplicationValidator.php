<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services;

/**
 * Validates and sanitizes the registration form input. Pure: the environment (the form's fields,
 * whether an e-mail is taken) is passed in, so it can be unit-tested without WordPress.
 */
class ApplicationValidator {

	const MIN_PASSWORD_LENGTH = 8;

	/**
	 * @param  array  $input  raw form values
	 * @param  array  $config  {
	 *     @type array    $fields         the form's fields: [ key, label, type, required, options ] each
	 *     @type bool     $isGuest        true when a new customer account has to be created
	 *     @type bool     $needsPassword  true when the form asks for a password
	 *     @type bool     $needsTerms     true when the terms checkbox is shown
	 *     @type callable $emailExists    fn( string $email ): bool
	 * }
	 *
	 * @return array{data: array, errors: array<string, string>}
	 */
	public static function validate( array $input, array $config ): array {
		$fields        = (array) ( $config['fields'] ?? array() );
		$isGuest       = ! empty( $config['isGuest'] );
		$needsPassword = ! empty( $config['needsPassword'] );
		$needsTerms    = ! empty( $config['needsTerms'] );
		$emailExists   = $config['emailExists'] ?? null;

		$errors = array();
		$data   = array();

		if ( '' !== trim( (string) ( $input['tpt_wholesale_website_url'] ?? '' ) ) ) {
			// the honeypot field is hidden from people; a value means a bot filled the form
			$errors['spam'] = __( 'The form could not be submitted. Please try again.', 'tier-pricing-table' );

			return array( 'data' => $data, 'errors' => $errors );
		}

		if ( $isGuest ) {
			$data['first_name'] = self::text( $input['first_name'] ?? '' );
			$data['last_name']  = self::text( $input['last_name'] ?? '' );
			$data['email']      = self::email( $input['email'] ?? '' );

			if ( '' === $data['first_name'] ) {
				$errors['first_name'] = __( 'Please enter your first name.', 'tier-pricing-table' );
			}

			if ( '' === $data['last_name'] ) {
				$errors['last_name'] = __( 'Please enter your last name.', 'tier-pricing-table' );
			}

			if ( '' === $data['email'] ) {
				$errors['email'] = __( 'Please enter a valid e-mail address.', 'tier-pricing-table' );
			} elseif ( is_callable( $emailExists ) && $emailExists( $data['email'] ) ) {
				$errors['email'] = __( 'An account with this e-mail address already exists. Please log in and apply from your account.',
					'tier-pricing-table' );
			}

			if ( $needsPassword ) {
				$password = (string) ( $input['password'] ?? '' );

				if ( strlen( $password ) < self::MIN_PASSWORD_LENGTH ) {
					$errors['password'] = sprintf(
					/* translators: %d: minimum number of characters */
						__( 'Please choose a password of at least %d characters.', 'tier-pricing-table' ),
						self::MIN_PASSWORD_LENGTH
					);
				} else {
					$data['password'] = $password;
				}
			}
		}

		foreach ( $fields as $field ) {
			$key = (string) ( $field['key'] ?? '' );

			if ( '' === $key ) {
				continue;
			}

			$raw      = $input[ $key ] ?? '';
			$type     = (string) ( $field['type'] ?? 'text' );
			$required = ! empty( $field['required'] );
			$label    = (string) ( $field['label'] ?? $key );
			$error    = '';

			switch ( $type ) {
				case 'textarea':
					$value = self::textarea( $raw );
					break;
				case 'email':
					$value = self::email( $raw );

					if ( '' !== trim( (string) $raw ) && '' === $value ) {
						$error = __( 'Please enter a valid e-mail address.', 'tier-pricing-table' );
					}
					break;
				case 'url':
					$value = self::url( $raw );

					if ( '' !== trim( (string) $raw ) && '' === $value ) {
						$error = __( 'Please enter a valid website address.', 'tier-pricing-table' );
					}
					break;
				case 'number':
					$value = self::text( $raw );

					if ( '' !== $value && ! is_numeric( $value ) ) {
						$error = __( 'Please enter a number.', 'tier-pricing-table' );
						$value = '';
					}
					break;
				case 'select':
					$value   = self::text( $raw );
					$options = array_map( 'strval', (array) ( $field['options'] ?? array() ) );

					if ( '' !== $value && ! in_array( $value, $options, true ) ) {
						$error = __( 'Please choose one of the options.', 'tier-pricing-table' );
						$value = '';
					}
					break;
				case 'checkbox':
					$value = empty( $raw ) ? '' : '1';
					break;
				default:
					$value = self::text( $raw );
			}

			$data[ $key ] = $value;

			if ( '' === $error && $required && '' === $value ) {
				$error = 'checkbox' === $type
					/* translators: %s: field label */
					? sprintf( __( 'Please tick "%s".', 'tier-pricing-table' ), $label )
					: __( 'This field is required.', 'tier-pricing-table' );
			}

			if ( '' !== $error ) {
				$errors[ $key ] = $error;
			}
		}

		if ( $needsTerms && empty( $input['terms'] ) ) {
			$errors['terms'] = __( 'Please accept the terms to continue.', 'tier-pricing-table' );
		}

		return array( 'data' => $data, 'errors' => $errors );
	}

	protected static function text( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = function_exists( 'sanitize_text_field' ) ? sanitize_text_field( $value ) : trim( strip_tags( $value ) );

		return mb_substr( $value, 0, 200 );
	}

	protected static function textarea( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $value ) : trim( strip_tags( $value ) );

		return mb_substr( $value, 0, 2000 );
	}

	protected static function email( $value ): string {
		$value = strtolower( trim( is_scalar( $value ) ? (string) $value : '' ) );

		return filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
	}

	protected static function url( $value ): string {
		$value = trim( is_scalar( $value ) ? (string) $value : '' );

		if ( '' === $value ) {
			return '';
		}

		if ( ! preg_match( '#^https?://#i', $value ) ) {
			$value = 'https://' . $value;
		}

		return filter_var( $value, FILTER_VALIDATE_URL ) ? mb_substr( $value, 0, 200 ) : '';
	}
}
