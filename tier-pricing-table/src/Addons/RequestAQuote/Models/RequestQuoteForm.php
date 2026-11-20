<?php namespace TierPricingTable\Addons\RequestAQuote\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RequestQuoteForm {

	/**
	 * The option every form is saved in.
	 */
	public const OPTION = 'tier_pricing_table_quote_forms';

	/**
	 * Where a form shows by itself: nowhere (only where a product or a pricing rule chooses it), on every
	 * product, or on chosen categories and products.
	 */
	public const SHOW_ON_CHOSEN = 'manual';
	public const SHOW_ON_ALL    = 'all';
	public const SHOW_ON_SOME   = 'some';
	
	private array $data;
	private array $overrides = array();
	
	public function __construct( array $data ) {
		$this->data = $data;
	}
	
	public function setOverrides( array $overrides ) {
		$this->overrides = $overrides;
	}
	
	public function getId(): string {
		return isset( $this->data['id'] ) ? (string) $this->data['id'] : '';
	}
	
	public function getName(): string {
		return isset( $this->data['name'] ) ? (string) $this->data['name'] : '';
	}
	
	public function getPromptText(): string {
		// this form's quote buttons add to the quote list
		if ( ! is_admin() && \TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings::formAddsToList( $this ) ) {
			return $this->getQuoteListButtonText();
		}

		return ! empty( $this->data['prompt_text'] ) ? (string) $this->data['prompt_text'] : __( 'Request a quote',
			'tier-pricing-table' );
	}
	
	public function getSuccessAction(): string {
		return isset( $this->data['success_action'] ) ? (string) $this->data['success_action'] : 'message';
	}
	
	public function getSuccessMessage(): string {
		return isset( $this->data['success_message'] ) ? (string) $this->data['success_message'] : __( 'Thank you for your request. We will contact you soon.',
			'tier-pricing-table' );
	}
	
	public function getSuccessRedirectUrl(): string {
		// web addresses only, also for forms saved before addresses were checked on save
		return isset( $this->data['success_redirect_url'] ) ? esc_url_raw( trim( (string) $this->data['success_redirect_url'] ), array( 'http', 'https' ) ) : '';
	}
	
	public function getModalTitle(): string {
		return $this->data['modal_title'] ?? __( 'Request a Quote', 'tier-pricing-table' );
	}
	
	public function getDisplayPosition(): string {
		return $this->data['display_position'] ?? 'integrated';
	}
	
	public function getIntegratedLabelText(): string {
		if ( ! empty( $this->overrides['integrated_label_text'] ) ) {
			return $this->overrides['integrated_label_text'];
		}
		
		$label = $this->data['integrated_label_text'] ?? '';
		if ( empty( $label ) ) {
			return __( 'Custom Quantity', 'tier-pricing-table' );
		}
		
		return $label;
	}
	
	/**
	 * How the quote button looks: 'outline' (secondary, the default of new forms), 'filled' (like Add to cart)
	 * or 'link'. Forms saved before this setting return '' and keep the look their position always had.
	 */
	public function getButtonStyle(): string {
		$style = (string) ( $this->data['button_style'] ?? '' );

		return in_array( $style, array( 'outline', 'filled', 'link' ), true ) ? $style : '';
	}

	public function getHideAddToCart(): bool {
		return ! empty( $this->data['hide_add_to_cart'] );
	}
	
	public function getShowForNonPurchasable(): bool {
		return ! empty( $this->data['show_for_non_purchasable'] );
	}

	/**
	 * The quote buttons of products with this form add the product to the quote list instead of opening the form.
	 */
	public function addsToQuoteList(): bool {
		return ! empty( $this->data['quote_list'] );
	}

	public function getShowOn(): string {
		$showOn = (string) ( $this->data['show_on'] ?? '' );

		return in_array( $showOn, array( self::SHOW_ON_ALL, self::SHOW_ON_SOME ), true ) ? $showOn : self::SHOW_ON_CHOSEN;
	}

	/**
	 * @return int[]
	 */
	public function getShowOnProducts(): array {
		return self::toIds( $this->data['show_on_products'] ?? array() );
	}

	/**
	 * @return int[] Product categories; their subcategories count too.
	 */
	public function getShowOnCategories(): array {
		return self::toIds( $this->data['show_on_categories'] ?? array() );
	}

	/**
	 * @return int[]
	 */
	public function getExcludedProducts(): array {
		return self::toIds( $this->data['exclude_products'] ?? array() );
	}

	/**
	 * @return string[] The customer roles the form shows for ("guest" for visitors), empty for everyone.
	 */
	public function getShowOnRoles(): array {
		$roles = is_array( $this->data['show_on_roles'] ?? null ) ? $this->data['show_on_roles'] : array();

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', array_map( 'strval', $roles ) ) ) ) );
	}

	/**
	 * @param  mixed  $value
	 *
	 * @return int[]
	 */
	protected static function toIds( $value ): array {
		return array_values( array_unique( array_filter( array_map( 'absint', is_array( $value ) ? $value : array() ) ) ) );
	}

	public function getQuoteListButtonText(): string {
		$text = trim( (string) ( $this->data['quote_list_button_text'] ?? '' ) );

		return '' !== $text ? $text : __( 'Add to quote', 'tier-pricing-table' );
	}
	
	public function getSubmitButtonText(): string {
		return $this->data['submit_button_text'] ?? __( 'Submit Request', 'tier-pricing-table' );
	}
	
	/**
	 * A line under the send button: what happens next ("No payment now. We reply by e-mail.").
	 */
	public function getSubmitNote(): string {
		return trim( (string) ( $this->data['submit_note'] ?? '' ) );
	}

	public function getFields(): array {
		return isset( $this->data['fields'] ) && is_array( $this->data['fields'] ) ? $this->data['fields'] : array();
	}

	/**
	 * The field that holds the customer's e-mail address: the first Email field, whatever its name.
	 *
	 * @return string The field name, or '' when the form has no Email field.
	 */
	public function getEmailFieldName(): string {
		foreach ( $this->getFields() as $field ) {
			if ( is_array( $field ) && 'email' === ( $field['type'] ?? '' ) && ! empty( $field['name'] ) ) {
				return (string) $field['name'];
			}
		}

		return '';
	}

	/**
	 * The field that holds the requested quantity: the first Number field synced with the product quantity,
	 * whatever its name. Forms saved before that option existed call it "quantity".
	 *
	 * @return string The field name, or '' when the form has no quantity field.
	 */
	public function getQuantityFieldName(): string {
		foreach ( $this->getFields() as $field ) {
			if ( is_array( $field ) && 'number' === ( $field['type'] ?? '' ) && ! empty( $field['syncWithQuantity'] ) && ! empty( $field['name'] ) ) {
				return (string) $field['name'];
			}
		}

		foreach ( $this->getFields() as $field ) {
			if ( is_array( $field ) && 'quantity' === ( $field['name'] ?? '' ) ) {
				return 'quantity';
			}
		}

		return '';
	}

	public function getAutoOpenQuantity(): string {
		if ( ! empty( $this->overrides['auto_open_quantity'] ) ) {
			return $this->overrides['auto_open_quantity'];
		}
		
		return isset( $this->data['auto_open_quantity'] ) ? (string) $this->data['auto_open_quantity'] : '';
	}
	
	public function isAdminEmailEnabled(): bool {
		return ! isset( $this->data['enable_admin_email'] ) || $this->data['enable_admin_email'] !== false;
	}
	
	public function isCustomerEmailEnabled(): bool {
		return ! isset( $this->data['enable_customer_email'] ) || $this->data['enable_customer_email'] !== false;
	}
	
	public function toArray(): array {
		$data = $this->data;
		
		$data['id']                    = $this->getId();
		$data['name']                  = $this->getName();
		$data['prompt_text']           = $this->getPromptText();
		$data['success_action']        = $this->getSuccessAction();
		$data['success_message']       = $this->getSuccessMessage();
		$data['success_redirect_url']  = $this->getSuccessRedirectUrl();
		$data['modal_title']           = $this->getModalTitle();
		$data['display_position']         = $this->getDisplayPosition();
		$data['hide_add_to_cart']         = $this->getHideAddToCart();
		$data['show_for_non_purchasable'] = $this->getShowForNonPurchasable();
		$data['quote_list']               = $this->addsToQuoteList();
		$data['integrated_label_text']    = $this->getIntegratedLabelText();
		$data['submit_button_text']    = $this->getSubmitButtonText();
		$data['auto_open_quantity']    = $this->getAutoOpenQuantity();
		$data['enable_admin_email']    = $this->isAdminEmailEnabled();
		$data['enable_customer_email'] = $this->isCustomerEmailEnabled();
		
		return $data;
	}
	
	/**
	 * Get a form by ID.
	 *
	 * @param  string  $id
	 *
	 * @return ?RequestQuoteForm
	 */
	public static function get( string $id ): ?RequestQuoteForm {
		$forms = self::getAll();
		foreach ( $forms as $form ) {
			if ( $form->getId() === $id ) {
				return $form;
			}
		}
		
		return null;
	}
	
	/**
	 * Get all forms.
	 *
	 * @return RequestQuoteForm[]
	 */
	/**
	 * The options of a Select, Radio or Checkboxes field, one per line, as the form shows them.
	 *
	 * @param  array  $field
	 *
	 * @return string[]
	 */
	public static function getFieldOptions( array $field ): array {
		$optionsText = isset( $field['options'] ) && is_string( $field['options'] ) ? $field['options'] : '';

		return array_values( array_filter( array_map( 'trim', explode( "\n", $optionsText ) ), 'strlen' ) );
	}

	/**
	 * Clean one form from the form builder before it is saved: text without markup (paragraphs and the success
	 * message keep safe HTML), web addresses only for the redirect, known values for choices, and yes/no
	 * settings as booleans. Settings this code does not know keep their type, with their text cleaned.
	 *
	 * @param  array  $form
	 *
	 * @return array
	 */
	public static function sanitize( array $form ): array {
		$clean = self::sanitizeUnknown( $form );

		foreach ( array( 'name', 'prompt_text', 'modal_title', 'submit_button_text', 'submit_note', 'integrated_label_text', 'quote_list_button_text' ) as $key ) {
			if ( array_key_exists( $key, $form ) ) {
				$clean[ $key ] = sanitize_text_field( is_scalar( $form[ $key ] ) ? (string) $form[ $key ] : '' );
			}
		}

		foreach ( array( 'hide_add_to_cart', 'show_for_non_purchasable', 'quote_list', 'enable_admin_email', 'enable_customer_email' ) as $key ) {
			if ( array_key_exists( $key, $form ) ) {
				$clean[ $key ] = (bool) $form[ $key ];
			}
		}

		// the builder makes the ID from a timestamp (a number); it is compared strictly, so it keeps its type
		if ( array_key_exists( 'id', $form ) ) {
			$clean['id'] = is_int( $form['id'] ) ? $form['id'] : sanitize_text_field( is_scalar( $form['id'] ) ? (string) $form['id'] : '' );
		}

		if ( array_key_exists( 'show_on', $form ) ) {
			$clean['show_on'] = in_array( $form['show_on'], array( self::SHOW_ON_ALL, self::SHOW_ON_SOME ), true ) ? $form['show_on'] : self::SHOW_ON_CHOSEN;
		}

		foreach ( array( 'show_on_products', 'show_on_categories', 'exclude_products' ) as $key ) {
			if ( array_key_exists( $key, $form ) ) {
				$clean[ $key ] = self::toIds( $form[ $key ] );
			}
		}

		if ( array_key_exists( 'show_on_roles', $form ) ) {
			$clean['show_on_roles'] = ( new self( array( 'show_on_roles' => $form['show_on_roles'] ) ) )->getShowOnRoles();
		}

		if ( array_key_exists( 'button_style', $form ) ) {
			$clean['button_style'] = in_array( $form['button_style'], array( 'outline', 'filled', 'link' ), true ) ? $form['button_style'] : 'outline';
		}

		if ( array_key_exists( 'display_position', $form ) ) {
			$clean['display_position'] = in_array( $form['display_position'], array( 'after', 'integrated', 'next_to_add_to_cart' ), true ) ? $form['display_position'] : 'integrated';
		}

		if ( array_key_exists( 'success_action', $form ) ) {
			$clean['success_action'] = 'redirect' === $form['success_action'] ? 'redirect' : 'message';
		}

		if ( array_key_exists( 'success_message', $form ) ) {
			$clean['success_message'] = wp_kses_post( is_scalar( $form['success_message'] ) ? (string) $form['success_message'] : '' );
		}

		if ( array_key_exists( 'success_redirect_url', $form ) ) {
			$clean['success_redirect_url'] = esc_url_raw( trim( is_scalar( $form['success_redirect_url'] ) ? (string) $form['success_redirect_url'] : '' ), array( 'http', 'https' ) );
		}

		if ( array_key_exists( 'auto_open_quantity', $form ) ) {
			$quantity                    = is_scalar( $form['auto_open_quantity'] ) ? trim( (string) $form['auto_open_quantity'] ) : '';
			$clean['auto_open_quantity'] = '' === $quantity ? '' : (string) absint( $quantity );
		}

		if ( array_key_exists( 'fields', $form ) ) {
			$clean['fields'] = array();

			foreach ( is_array( $form['fields'] ) ? $form['fields'] : array() as $field ) {
				if ( is_array( $field ) ) {
					$clean['fields'][] = self::sanitizeField( $field );
				}
			}
		}

		return $clean;
	}

	/**
	 * Clean one field of a form.
	 *
	 * @param  array  $field
	 *
	 * @return array
	 */
	protected static function sanitizeField( array $field ): array {
		$types = array( 'text', 'textarea', 'email', 'number', 'select', 'radio', 'checkbox', 'heading', 'paragraph', 'file', 'date' );
		$text  = function ( $value ): string {
			return is_scalar( $value ) ? (string) $value : '';
		};

		$clean = self::sanitizeUnknown( $field );
		$type  = in_array( $field['type'] ?? 'text', $types, true ) ? $field['type'] : 'text';

		$clean['type'] = $type;

		// the name is the key the answer is stored and sent under
		if ( array_key_exists( 'name', $field ) ) {
			$clean['name'] = preg_replace( '/[^A-Za-z0-9_-]/', '', $text( $field['name'] ) );
		}

		if ( array_key_exists( 'label', $field ) ) {
			// a Paragraph shows its text as HTML on the form
			$clean['label'] = 'paragraph' === $type ? wp_kses_post( $text( $field['label'] ) ) : sanitize_text_field( $text( $field['label'] ) );
		}

		foreach ( array( 'placeholder', 'allowedTypes' ) as $key ) {
			if ( array_key_exists( $key, $field ) ) {
				$clean[ $key ] = sanitize_text_field( $text( $field[ $key ] ) );
			}
		}

		foreach ( array( 'description', 'options' ) as $key ) {
			if ( array_key_exists( $key, $field ) ) {
				$clean[ $key ] = sanitize_textarea_field( $text( $field[ $key ] ) );
			}
		}

		foreach ( array( 'required', 'hasDescription', 'hasPlaceholder', 'syncWithQuantity', 'disablePastDates' ) as $key ) {
			if ( array_key_exists( $key, $field ) ) {
				$clean[ $key ] = (bool) $field[ $key ];
			}
		}

		if ( array_key_exists( 'titleFormat', $field ) ) {
			$clean['titleFormat'] = 'hidden' === $field['titleFormat'] ? 'hidden' : 'label';
		}

		// a half-width field shares its row with the next half-width field
		if ( array_key_exists( 'width', $field ) ) {
			$clean['width'] = 'half' === $field['width'] ? 'half' : 'full';
		}

		if ( array_key_exists( 'maxFileSize', $field ) ) {
			$size                 = trim( $text( $field['maxFileSize'] ) );
			$clean['maxFileSize'] = is_numeric( $size ) && (float) $size > 0 ? $size : '';
		}

		return $clean;
	}

	/**
	 * Clean every text in a value, keeping numbers, booleans and nesting as they are.
	 *
	 * @param  mixed  $value
	 *
	 * @return mixed
	 */
	public static function sanitizeUnknown( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();

			foreach ( $value as $key => $item ) {
				$clean[ is_int( $key ) ? $key : sanitize_text_field( (string) $key ) ] = self::sanitizeUnknown( $item );
			}

			return $clean;
		}

		return is_string( $value ) ? sanitize_text_field( $value ) : ( is_scalar( $value ) || null === $value ? $value : '' );
	}

	public static function getAll(): array {
		$rawForms = get_option( self::OPTION, array() );
		$forms    = array();
		
		if ( ! is_array( $rawForms ) ) {
			return $forms;
		}
		
		foreach ( $rawForms as $rawForm ) {
			if ( is_array( $rawForm ) ) {
				$forms[] = new self( $rawForm );
			}
		}
		
		return $forms;
	}
}
