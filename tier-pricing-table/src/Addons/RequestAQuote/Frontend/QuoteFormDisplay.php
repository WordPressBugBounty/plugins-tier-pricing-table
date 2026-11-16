<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend;

use TierPricingTable\Addons\RequestAQuote\Frontend\API\SubmitQuoteEndpoint;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;

class QuoteFormDisplay {

	/**
	 * The admin-ajax action that hands the form a fresh REST nonce.
	 */
	public const NONCE_ACTION = 'tpt_quote_nonce';

	public static array $renderedModals = array();

	public static function addModalToRender( $form, $productId ) {
		self::$renderedModals[ $productId ] = $form;
	}

	/**
	 * The product a modal is keyed by: variations share their parent's modal.
	 *
	 * @param  int  $productId
	 *
	 * @return int
	 */
	public static function getModalProductId( $productId ): int {
		$product = wc_get_product( $productId );

		if ( $product && $product->get_parent_id() ) {
			return (int) $product->get_parent_id();
		}

		return (int) $productId;
	}

	/**
	 * Make sure a variable product's modal is printed in wp_footer even if no quote button rendered
	 * during the page request: with more variations than the AJAX threshold, every variation's
	 * pricing table (and its button) is fetched by AJAX later, and no default variation may be
	 * selected at all. Fires only in the main page render, never in the AJAX handler.
	 *
	 * @param  \WC_Product|mixed  $parentProduct
	 * @param  mixed              $variationId
	 * @param  array              $settings
	 */
	public function registerVariableProductModal( $parentProduct, $variationId = null, $settings = array() ) {

		if ( ! ( $parentProduct instanceof \WC_Product ) || ! TierPricingTablePlugin::isVariableProductSupported( $parentProduct ) ) {
			return;
		}

		// Only the product page switches variations (and loads their tables by AJAX). Tables in shop
		// loops, related products etc. never do, so they don't need a modal registered up front.
		if ( ( $settings['display_context'] ?? 'product-page' ) !== 'product-page' ) {
			return;
		}

		$parentId = $parentProduct->get_id();

		if ( isset( self::$renderedModals[ $parentId ] ) ) {
			return;
		}

		$formId = PriceManager::getPricingRule( $parentId )->data['tier_pricing_table_quote_form_id'] ?? null;
		$form   = $formId ? RequestQuoteForm::get( (string) $formId ) : null;

		if ( $form ) {
			self::addModalToRender( $form, $parentId );
		}
	}

	public function __construct() {
		// Initialize and register hooks for Layout Adapters
		( new Adapters\TableAdapter() )->registerHooks();
		( new Adapters\HorizontalTableAdapter() )->registerHooks();
		( new Adapters\BlocksAdapter() )->registerHooks();
		( new Adapters\OptionsAdapter() )->registerHooks();
		( new Adapters\DropdownAdapter() )->registerHooks();
		( new Adapters\PlainTextAdapter() )->registerHooks();
		( new Adapters\AddToCartAdapter() )->registerHooks();

		add_action( 'tiered_pricing_table/before_rendering_tiered_pricing', array( $this, 'registerVariableProductModal' ), 10, 3 );
		add_action( 'wp_footer', array( $this, 'renderFormModal' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'registerAssets' ) );
		add_action( 'template_redirect', array( $this, 'handleSuccessNotice' ) );

		add_action( 'wp_ajax_' . self::NONCE_ACTION, array( $this, 'sendFreshNonce' ) );
		add_action( 'wp_ajax_nopriv_' . self::NONCE_ACTION, array( $this, 'sendFreshNonce' ) );
	}

	/**
	 * A fresh REST nonce for a logged-in customer whose page is older than its nonce (a tab left open for a
	 * day). Guests need none. admin-ajax is never kept by page caches.
	 */
	public function sendFreshNonce() {
		nocache_headers();

		wp_send_json_success( array(
			'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
		) );
	}

	public function handleSuccessNotice() {
		if ( isset( $_GET['tier_pricing_table_quote_success'] ) && ! empty( $_GET['form_id'] ) ) {
			$formId  = sanitize_text_field( $_GET['form_id'] );
			$form    = RequestQuoteForm::get( $formId );
			$message = __( 'Your quote request has been submitted successfully!', 'tier-pricing-table' );

			if ( $form ) {
				$message = $form->getSuccessMessage();
			}

			if ( function_exists( 'wc_add_notice' ) ) {
				if ( function_exists( 'WC' ) && isset( WC()->session ) && ! WC()->session->has_session() ) {
					WC()->session->set_customer_session_cookie( true );
				}
				wc_add_notice( $message, 'success' );
				wp_safe_redirect( remove_query_arg( array( 'tier_pricing_table_quote_success', 'form_id' ) ) );
				exit;
			}
		}
	}

	public function renderFormModal() {
		// the buttons of a form that adds to the quote list never open it
		$modals = array_filter( self::$renderedModals, function ( $form ) {
			return ! QuoteListSettings::formAddsToList( $form );
		} );

		if ( empty( $modals ) ) {
			return;
		}

		// the form's script and styles load only on pages where a form can open (printed with the footer scripts)
		self::enqueueAssets();

		foreach ( $modals as $productId => $form ) :
			$product = wc_get_product( $productId );

			if ( ! $product ) {
				continue;
			}
			ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'frontend/quote-modal.php', array(
					'form'        => $form,
					'productId'   => $productId,
					'product'     => $product,
					'formDisplay' => $this,
			), plugin_dir_path( dirname( __FILE__ ) ) . 'views/' );
		endforeach;
	}

	/**
	 * Render the form fields natively in PHP.
	 *
	 * @param  RequestQuoteForm  $form
	 * @param  int  $productId
	 * @param  bool  $skipQuantity  Leave out the field synced with the product quantity (the quote list has a
	 *                              quantity per product).
	 */
	public static function renderFormFields( $form, $productId = 0, bool $skipQuantity = false ) {
		$fields = $form->getFields();
		if ( empty( $fields ) ) {
			return;
		}

		$quantityFieldName = $skipQuantity ? $form->getQuantityFieldName() : '';

		$currentUser    = wp_get_current_user();
		$defaultName    = $currentUser->exists() ? $currentUser->display_name : '';
		$defaultEmail   = $currentUser->exists() ? $currentUser->user_email : '';
		$emailFieldName = $form->getEmailFieldName();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type        = $field['type'] ?? 'text';
			$name        = $field['name'] ?? '';

			if ( '' !== $quantityFieldName && $name === $quantityFieldName ) {
				continue;
			}

			$label       = $field['label'] ?? '';
			$placeholder = $field['placeholder'] ?? '';
			$description = $field['description'] ?? '';
			$required    = ! empty( $field['required'] ) ? 'required' : '';

			// the same list the server accepts
			$options = RequestQuoteForm::getFieldOptions( $field );

			$value = '';
			if ( $name === 'name' ) {
				$value = $defaultName;
			} elseif ( '' !== $name && $name === $emailFieldName ) {
				$value = $defaultEmail;
			}

			?>
			<div class="tpt-quote-field-wrapper">
				<?php if ( $type === 'heading' ) : ?>
					<h3 style="margin: 0 0 10px 0; color: inherit;"><?php echo esc_html( $label ); ?></h3>
				<?php elseif ( $type === 'paragraph' ) : ?>
					<p style="margin: 0 0 10px 0; color: inherit;"><?php echo wp_kses_post( $label ); ?></p>
				<?php else : ?>
					<?php
					$titleFormat = $field['titleFormat'] ?? '';
					if ( $titleFormat !== 'hidden' ) :
						?>
						<label for="tpt_field_<?php echo esc_attr( $name ); ?>">
							<?php echo esc_html( $label ); ?>
							<?php if ( $required ) : ?>
								<span class="required" style="color: #d63638; font-weight: normal; margin-left: 4px;"
								      title="required">*</span>
							<?php endif; ?>
						</label>
					<?php endif; ?>

					<?php if ( $type === 'select' ) : ?>
						<select id="tpt_field_<?php echo esc_attr( $name ); ?>"
						        name="<?php echo esc_attr( $name ); ?>" <?php echo $required ? 'required' : ''; ?>>
							<?php if ( ! empty( $field['hasPlaceholder'] ) && $placeholder ) : ?>
								<option value="" disabled selected
								        hidden><?php echo esc_html( $placeholder ); ?></option>
							<?php endif; ?>
							<?php foreach ( $options as $opt ) : ?>
								<option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
							<?php endforeach; ?>
						</select>

					<?php elseif ( $type === 'radio' ) : ?>
						<div class="tpt-radio-group">
							<?php foreach ( $options as $opt ) : ?>
								<label>
									<input type="radio" name="<?php echo esc_attr( $name ); ?>"
									       value="<?php echo esc_attr( $opt ); ?>" <?php echo $required ? 'required' : ''; ?>>
									<?php echo esc_html( $opt ); ?>
								</label>
							<?php endforeach; ?>
						</div>

					<?php elseif ( $type === 'checkbox' ) : ?>
						<div class="tpt-radio-group">
							<?php
								$isMultiple = count( $options ) > 1;
								$chkName    = $isMultiple ? $name . '[]' : $name;
								$chkReq     = $isMultiple ? '' : ( $required ? 'required' : '' );
								foreach ( $options as $opt ) :
									?>
									<label>
										<input type="checkbox" name="<?php echo esc_attr( $chkName ); ?>"
										       value="<?php echo esc_attr( $opt ); ?>" <?php echo esc_attr( $chkReq ); ?>>
										<?php echo esc_html( $opt ); ?>
									</label>
								<?php endforeach; ?>
						</div>

					<?php elseif ( $type === 'textarea' ) : ?>
						<textarea id="tpt_field_<?php echo esc_attr( $name ); ?>"
						          name="<?php echo esc_attr( $name ); ?>"
						          maxlength="1500" <?php echo $required ? 'required' : ''; ?> <?php echo ( ! empty( $field['hasPlaceholder'] ) && $placeholder ) ? 'placeholder="' . esc_attr( $placeholder ) . '"' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>

					<?php elseif ( $type === 'file' ) : ?>
						<?php
						$allowedTypes = ! empty( $field['allowedTypes'] ) ? esc_attr( '.' . str_replace( ',', ',.',
										str_replace( ' ', '', $field['allowedTypes'] ) ) ) : '';
						?>
						<input type="file" id="tpt_field_<?php echo esc_attr( $name ); ?>"
						       name="<?php echo esc_attr( $name ); ?>"
								<?php if ( $allowedTypes ) : ?>accept="<?php echo esc_attr( $allowedTypes ); ?>"<?php endif; ?>
								<?php echo $required ? 'required' : ''; ?>>

					<?php else : ?>
						<?php
						$syncClass     = ( $type === 'number' && ! empty( $field['syncWithQuantity'] ) ) ? 'tpt-quote-sync-quantity' : '';
						$maxLength = in_array( $type, array( 'text', 'email', 'tel', 'url' ), true ) ? 255 : 0;

						$min = 1;
						if ( $type === 'number' && ! empty( $field['syncWithQuantity'] ) && $productId ) {
							$pricingRule = PriceManager::getPricingRule( $productId );
							$ruleMin     = $pricingRule->getMinimum();
							if ( $ruleMin > 0 ) {
								$min = $ruleMin;
							}
						}

						$minValue = ( $type === 'number' ) ? (string) $min : '';

						if ( $type === 'date' && ! empty( $field['disablePastDates'] ) ) {
							$minValue = wp_date( 'Y-m-d' );
						}
						?>
						<input type="<?php echo esc_attr( $type ); ?>" id="tpt_field_<?php echo esc_attr( $name ); ?>"
						       name="<?php echo esc_attr( $name ); ?>"
						       class="<?php echo esc_attr( $syncClass ); ?>"
						       <?php if ( '' !== self::getAutocomplete( (string) $type, (string) $name ) ) : ?>autocomplete="<?php echo esc_attr( self::getAutocomplete( (string) $type, (string) $name ) ); ?>"<?php endif; ?>

						       value="<?php echo esc_attr( $value ); ?>" <?php if ( $maxLength ) : ?>maxlength="<?php echo esc_attr( $maxLength ); ?>"<?php endif; ?> <?php if ( '' !== $minValue ) : ?>min="<?php echo esc_attr( $minValue ); ?>"<?php endif; ?> <?php echo $required ? 'required' : ''; ?> <?php echo ( ! empty( $field['hasPlaceholder'] ) && $placeholder ) ? 'placeholder="' . esc_attr( $placeholder ) . '"' : ''; ?>>
					<?php endif; ?>

					<?php if ( ! empty( $field['hasDescription'] ) && $description ) : ?>
						<p class="tpt-field-description"
						   style="font-size: 12px; margin: 4px 0 0 0;"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>

				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * What a field asks for, so browsers can fill it in: by the field's type, or by the usual keys.
	 *
	 * @return string An autocomplete token, '' when the field's purpose is not known.
	 */
	public static function getAutocomplete( string $type, string $name ): string {
		if ( 'email' === $type ) {
			return 'email';
		}

		if ( 'text' !== $type ) {
			return '';
		}

		$tokens = array(
			'name'       => 'name',
			'full_name'  => 'name',
			'first_name' => 'given-name',
			'last_name'  => 'family-name',
			'company'    => 'organization',
			'phone'      => 'tel',
			'tel'        => 'tel',
			'address'    => 'street-address',
			'city'       => 'address-level2',
			'postcode'   => 'postal-code',
			'zip'        => 'postal-code',
			'country'    => 'country-name',
		);

		return $tokens[ strtolower( $name ) ] ?? '';
	}

	/**
	 * The form's script and styles, loaded by enqueueAssets() only where a quote form can open.
	 */
	public function registerAssets() {
		// reCAPTCHA v3: only the site key goes to the browser
		$siteKey = SubmitQuoteEndpoint::getRecaptchaKeys()['site'];

		if ( '' !== $siteKey ) {
			wp_register_script( 'google-recaptcha-v3',
					add_query_arg( 'render', rawurlencode( $siteKey ), 'https://www.google.com/recaptcha/api.js' ),
					array(), null, true );
		}

		wp_register_style( 'tpt-quote-form', plugins_url( '../assets/css/quote-form.css', __FILE__ ), array(),
				TierPricingTablePlugin::VERSION );

		// the buttons' own small stylesheet: in the head on shop pages, so buttons never flash unstyled
		wp_register_style( 'tpt-quote-button', plugins_url( '../assets/css/quote-button.css', __FILE__ ), array(),
				TierPricingTablePlugin::VERSION );

		if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() ) ) {
			wp_enqueue_style( 'tpt-quote-button' );
		}

		wp_register_script( 'tpt-quote-form', plugins_url( '../assets/js/quote-form.js', __FILE__ ), array( 'jquery' ),
				TierPricingTablePlugin::VERSION, true );
	}

	/**
	 * The quote buttons' stylesheet, for buttons printed outside the shop pages (a pricing table in a page or a
	 * widget): it is then printed with the footer.
	 */
	public static function enqueueButtonStyle() {
		if ( wp_style_is( 'tpt-quote-button', 'registered' ) && ! wp_style_is( 'tpt-quote-button', 'enqueued' ) ) {
			wp_enqueue_style( 'tpt-quote-button' );
		}
	}

	/**
	 * Load the form's script and styles on this page. Safe to call more than once, up to the footer scripts.
	 */
	public static function enqueueAssets() {
		if ( wp_script_is( 'tpt-quote-form', 'enqueued' ) || ! wp_script_is( 'tpt-quote-form', 'registered' ) ) {
			return;
		}

		$forms = array_map( function ( $form ) {
			$data = $form->toArray();

			// the shop only needs how each form looks and behaves, not where it shows or for whom
			unset( $data['fields'], $data['show_on'], $data['show_on_products'], $data['show_on_categories'], $data['exclude_products'], $data['show_on_roles'] );

			return $data;
		}, RequestQuoteForm::getAll() );

		$siteKey = SubmitQuoteEndpoint::getRecaptchaKeys()['site'];

		if ( '' !== $siteKey && wp_script_is( 'google-recaptcha-v3', 'registered' ) ) {
			wp_enqueue_script( 'google-recaptcha-v3' );
		}

		wp_enqueue_style( 'tpt-quote-form' );
		wp_enqueue_script( 'tpt-quote-form' );

		wp_localize_script( 'tpt-quote-form', 'tptQuoteFormConfig', array(
				'forms'            => $forms,
				'restUrl'          => esc_url_raw( rest_url( 'tier-pricing-table/v1/quote-request' ) ),
				// only logged-in customers send a nonce; a guest's would be cached with the page and expire
				'nonce'            => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonceAction'      => self::NONCE_ACTION,
				'recaptchaSiteKey' => $siteKey,
				'recaptchaAction'  => SubmitQuoteEndpoint::RECAPTCHA_ACTION,
				'i18n'             => array(
						'defaultModalTitle'     => __( 'Request a Quote', 'tier-pricing-table' ),
						'defaultSubmitText'     => __( 'Submit Request', 'tier-pricing-table' ),
						'defaultSubmittingText' => __( 'Submitting...', 'tier-pricing-table' ),
						'recaptchaUnavailable'  => __( 'The spam check could not load. Please reload the page and try again.', 'tier-pricing-table' ),
						'recaptchaFailed'       => __( 'The spam check failed. Please try again.', 'tier-pricing-table' ),
						'genericError'          => __( 'An error occurred.', 'tier-pricing-table' ),
						'networkError'          => __( 'Your request could not be sent. Check your connection and try again.', 'tier-pricing-table' ),
						'close'                 => __( 'Close', 'tier-pricing-table' ),
						'successTitle'          => __( 'Your request is sent', 'tier-pricing-table' ),
						/* translators: %s: request number, like #123 */
						'reference'             => __( 'Your request number is %s.', 'tier-pricing-table' ),
						/* translators: %s: e-mail address */
						'copySent'              => __( 'We e-mailed a copy to %s.', 'tier-pricing-table' ),
				),
		) );
	}

}
