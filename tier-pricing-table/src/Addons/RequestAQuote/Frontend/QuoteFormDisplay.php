<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend;

use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;

class QuoteFormDisplay {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueAssets' ) );
		add_action( 'template_redirect', array( $this, 'handleSuccessNotice' ) );
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
		if ( empty( self::$renderedModals ) ) {
			return;
		}

		foreach ( self::$renderedModals as $productId => $form ) :
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
	 */
	public function renderFormFields( $form, $productId = 0 ) {
		$fields = $form->getFields();
		if ( empty( $fields ) ) {
			return;
		}

		$currentUser  = wp_get_current_user();
		$defaultName  = $currentUser->exists() ? $currentUser->display_name : '';
		$defaultEmail = $currentUser->exists() ? $currentUser->user_email : '';

		foreach ( $fields as $field ) {
			$type        = $field['type'] ?? 'text';
			$name        = $field['name'] ?? '';
			$label       = $field['label'] ?? '';
			$placeholder = $field['placeholder'] ?? '';
			$description = $field['description'] ?? '';
			$required    = ! empty( $field['required'] ) ? 'required' : '';
			$optionsText = $field['options'] ?? '';

			$options = array();
			if ( $optionsText ) {
				$options = array_filter( array_map( 'trim', explode( "\n", $optionsText ) ) );
			}

			$value = '';
			if ( $name === 'name' ) {
				$value = $defaultName;
			} elseif ( $name === 'email' ) {
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
						       value="<?php echo esc_attr( $value ); ?>" <?php if ( $maxLength ) : ?>maxlength="<?php echo esc_attr( $maxLength ); ?>"<?php endif; ?> <?php if ( '' !== $minValue ) : ?>min="<?php echo esc_attr( $minValue ); ?>"<?php endif; ?> <?php echo $required ? 'required' : ''; ?> <?php echo ( ! empty( $field['hasPlaceholder'] ) && $placeholder ) ? 'placeholder="' . esc_attr( $placeholder ) . '"' : ''; ?>>
					<?php endif; ?>

					<?php if ( ! empty( $field['hasDescription'] ) && $description ) : ?>
						<p class="tpt-field-description"
						   style="font-size: 12px; opacity: 0.8; margin: 4px 0 0 0;"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>

				<?php endif; ?>
			</div>
			<?php
		}
	}

	function enqueueAssets() {
		// Get forms
		$formObjects = RequestQuoteForm::getAll();
		$forms       = array_map( function ( $form ) {
			$data = $form->toArray();
			unset( $data['fields'] );

			return $data;
		}, $formObjects );

		// Check for reCAPTCHA
		$globalSettings = get_option( 'tier_pricing_table_quote_global_settings', array() );
		$siteKey        = $globalSettings['recaptcha_site_key'] ?? null;

		if ( ! empty( $siteKey ) && ! empty( $globalSettings['recaptcha_secret_key'] ) ) {
			wp_enqueue_script( 'google-recaptcha-v3',
					'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $siteKey ), array(), null, true );
		}

		// Enqueue CSS
		wp_enqueue_style( 'tpt-quote-form', plugins_url( '../assets/css/quote-form.css', __FILE__ ), array(),
				TierPricingTablePlugin::VERSION );

		// Enqueue JS
		wp_enqueue_script( 'tpt-quote-form', plugins_url( '../assets/js/quote-form.js', __FILE__ ), array( 'jquery' ),
				TierPricingTablePlugin::VERSION, true );

		wp_localize_script( 'tpt-quote-form', 'tptQuoteFormConfig', array(
				'forms'   => $forms,
				'restUrl' => esc_url_raw( rest_url( 'tier-pricing-table/v1/quote-request' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
						'defaultModalTitle'     => __( 'Request a Quote', 'tier-pricing-table' ),
						'defaultSubmitText'     => __( 'Submit Request', 'tier-pricing-table' ),
						'defaultSubmittingText' => __( 'Submitting...', 'tier-pricing-table' ),
				),
		) );
	}
}
