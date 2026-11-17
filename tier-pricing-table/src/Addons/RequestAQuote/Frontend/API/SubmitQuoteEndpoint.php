<?php

namespace TierPricingTable\Addons\RequestAQuote\Frontend\API;

use TierPricingTable\Addons\RequestAQuote\CPT\QuoteRequestCPT;
use TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Addons\RequestAQuote\QuoteFiles;
use TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListService;
use TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings;
use TierPricingTable\PriceManager;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_Error;
class SubmitQuoteEndpoint extends WP_REST_Controller {
    /**
     * The reCAPTCHA v3 action name the form script sends and the server expects back.
     */
    public const RECAPTCHA_ACTION = 'tpt_quote_request';

    /**
     * The longest value a typed field may keep, in characters; other typed fields keep 255. The same limits
     * as the form's own maxlength attributes. Choices are checked against the field's options instead.
     */
    protected const MAX_LENGTHS = array(
        'textarea' => 1500,
    );

    /**
     * The hidden field only bots fill in.
     */
    public const HONEYPOT_FIELD = 'tpt_quote_website_url';

    /**
     * The parameter a quote list request carries its lines in: JSON, a list of {id, qty}.
     */
    public const LIST_ITEMS_PARAM = 'quote_items';

    /**
     * The reCAPTCHA keys from the global quote settings, trimmed. Both are empty unless both are set.
     *
     * @return array{site: string, secret: string}
     */
    public static function getRecaptchaKeys() : array {
        $globalSettings = get_option( 'tier_pricing_table_quote_global_settings', array() );
        $globalSettings = ( is_array( $globalSettings ) ? $globalSettings : array() );
        $siteKey = trim( (string) ($globalSettings['recaptcha_site_key'] ?? '') );
        $secretKey = trim( (string) ($globalSettings['recaptcha_secret_key'] ?? '') );
        if ( '' === $siteKey || '' === $secretKey ) {
            return array(
                'site'   => '',
                'secret' => '',
            );
        }
        return array(
            'site'   => $siteKey,
            'secret' => $secretKey,
        );
    }

    /**
     * The lowest reCAPTCHA v3 score the store accepts: from 0.0 (everything) to 1.0 (very strict), 0.5 by default.
     */
    public static function getRecaptchaThreshold() : float {
        $globalSettings = get_option( 'tier_pricing_table_quote_global_settings', array() );
        $threshold = ( is_array( $globalSettings ) ? $globalSettings['recaptcha_score_threshold'] ?? '' : '' );
        return ( is_numeric( $threshold ) ? max( 0.0, min( 1.0, (float) $threshold ) ) : 0.5 );
    }

    public function __construct() {
        add_action( 'rest_api_init', array($this, 'register_routes') );
    }

    public function register_routes() {
        register_rest_route( 'tier-pricing-table/v1', '/quote-request', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'submitQuote'),
            'permission_callback' => array($this, 'permissionCallback'),
        ) );
    }

    /**
     * A public form: anyone may submit. Guests send no nonce: a nonce printed into a page that a page cache
     * keeps would expire and block every request, and every guest shares the same nonce anyway, so it stops no
     * one. Spam is held back by the honeypot, the rate limit and, when keys are set, reCAPTCHA. Logged-in
     * customers send the REST nonce; WordPress checks it and treats a request without one as a guest's.
     *
     * @return true
     */
    public function permissionCallback( WP_REST_Request $request ) {
        return true;
    }

    /**
     * How many quote requests one visitor may send in a period. Store managers have no limit.
     *
     * @return array{requests: int, period: int} Requests 0 turns the limit off; the period is in seconds.
     */
    public static function getRateLimit() : array {
        /**
         * How many quote requests one visitor (an IP address, or a customer account) may send in a period.
         *
         * @param array $limit 'requests' (0 turns the limit off) and 'period' in seconds. Default 10 an hour.
         */
        $limit = apply_filters( 'tiered_pricing_table/request_quote/rate_limit', array(
            'requests' => 10,
            'period'   => HOUR_IN_SECONDS,
        ) );
        $limit = ( is_array( $limit ) ? $limit : array() );
        return array(
            'requests' => max( 0, (int) ($limit['requests'] ?? 10) ),
            'period'   => max( MINUTE_IN_SECONDS, (int) ($limit['period'] ?? HOUR_IN_SECONDS) ),
        );
    }

    /**
     * The transient that counts the current visitor's requests: by account when logged in, else by IP address.
     *
     * @return string
     */
    protected static function getRateLimitKey() : string {
        if ( is_user_logged_in() ) {
            return 'tpt_quote_rate_u' . get_current_user_id();
        }
        $ip = ( class_exists( 'WC_Geolocation' ) ? \WC_Geolocation::get_ip_address() : (( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' )) );
        // hashed with the site's salt: the address itself is not stored
        return 'tpt_quote_rate_' . substr( wp_hash( (string) $ip ), 0, 32 );
    }

    protected static function isRateLimited() : bool {
        $limit = self::getRateLimit();
        if ( !$limit['requests'] || current_user_can( 'manage_woocommerce' ) ) {
            return false;
        }
        $sent = get_transient( self::getRateLimitKey() );
        return is_array( $sent ) && (int) ($sent['count'] ?? 0) >= $limit['requests'];
    }

    /**
     * Count a saved request. The period starts with the first request and is not extended by later ones.
     */
    protected static function countRequest() {
        $limit = self::getRateLimit();
        if ( !$limit['requests'] || current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $key = self::getRateLimitKey();
        $sent = get_transient( $key );
        $now = time();
        if ( !is_array( $sent ) || (int) ($sent['start'] ?? 0) + $limit['period'] <= $now ) {
            $sent = array(
                'count' => 0,
                'start' => $now,
            );
        }
        $sent['count'] = (int) $sent['count'] + 1;
        set_transient( $key, $sent, max( 1, (int) $sent['start'] + $limit['period'] - $now ) );
    }

    public function submitQuote( WP_REST_Request $request ) {
        $params = $request->get_params();
        // the honeypot field is hidden from people; a value means a bot filled the form
        $honeypot = $params[self::HONEYPOT_FIELD] ?? '';
        if ( !is_scalar( $honeypot ) || '' !== trim( (string) $honeypot ) ) {
            return new WP_Error('spam_detected', __( 'The form could not be submitted. Please try again.', 'tier-pricing-table' ), array(
                'status' => 400,
            ));
        }
        // The free plan shows the form to administrators only, as a preview: nobody else may send it
        if ( !tpt_fs()->can_use_premium_code__premium_only() && !current_user_can( 'manage_options' ) ) {
            return new WP_Error('invalid_form_attachment', __( 'This form is not available for the selected product.', 'tier-pricing-table' ), array(
                'status' => 403,
            ));
        }
        $formId = ( isset( $params['form_id'] ) ? sanitize_text_field( $params['form_id'] ) : '' );
        // A quote list request: the list page's form, with the lines the shopper collected
        $isList = array_key_exists( self::LIST_ITEMS_PARAM, $params );
        $listLines = array();
        $productId = 0;
        $product = null;
        if ( $isList ) {
            $form = ( QuoteListSettings::isEnabled() ? QuoteListSettings::getForm() : null );
            if ( !$form || (string) $form->getId() !== $formId ) {
                return new WP_Error('quote_list_unavailable', __( 'The quote list is not available.', 'tier-pricing-table' ), array(
                    'status' => 403,
                ));
            }
            $rawLines = $params[self::LIST_ITEMS_PARAM];
            $listLines = QuoteListService::normalizeLines( ( is_string( $rawLines ) ? json_decode( $rawLines, true ) : $rawLines ) );
            if ( !$listLines ) {
                return new WP_Error('quote_list_empty', __( 'Your quote list is empty.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
        } else {
            $productId = ( isset( $params['product_id'] ) ? intval( $params['product_id'] ) : 0 );
            $form = RequestQuoteForm::get( $formId );
            $product = wc_get_product( $productId );
            if ( !$product || !$form ) {
                return new WP_Error('invalid_data', __( 'Form ID or Product ID is missing.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
            // Additional Validation: Check if the product has this form attached
            $pricingRule = PriceManager::getPricingRule( $productId );
            $attachedFormId = $pricingRule->data['tier_pricing_table_quote_form_id'] ?? null;
            if ( (string) $attachedFormId !== (string) $form->getId() ) {
                return new WP_Error('invalid_form_attachment', __( 'This form is not available for the selected product.', 'tier-pricing-table' ), array(
                    'status' => 403,
                ));
            }
        }
        if ( self::isRateLimited() ) {
            return new WP_Error('too_many_requests', __( 'You have sent too many quote requests. Please try again later.', 'tier-pricing-table' ), array(
                'status' => 429,
            ));
        }
        $secretKey = self::getRecaptchaKeys()['secret'];
        if ( '' !== $secretKey ) {
            $recaptchaResponse = ( isset( $params['g-recaptcha-response'] ) ? sanitize_text_field( $params['g-recaptcha-response'] ) : '' );
            if ( empty( $recaptchaResponse ) ) {
                return new WP_Error('spam_detected', __( 'Anti-Spam verification failed. Please try again.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
            $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
            $verifyResponse = wp_remote_post( $verifyUrl, array(
                'body' => array(
                    'secret'   => $secretKey,
                    'response' => $recaptchaResponse,
                ),
            ) );
            if ( is_wp_error( $verifyResponse ) ) {
                return new WP_Error('spam_verification_error', __( 'Could not verify reCAPTCHA.', 'tier-pricing-table' ), array(
                    'status' => 500,
                ));
            }
            $verifyBody = wp_remote_retrieve_body( $verifyResponse );
            $verifyData = json_decode( $verifyBody );
            if ( !$verifyData || !isset( $verifyData->success ) || !$verifyData->success ) {
                return new WP_Error('spam_detected', __( 'Anti-Spam verification failed. Token invalid.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
            // A token made for another action (another form on the site using the same keys) is not accepted
            if ( isset( $verifyData->action ) && self::RECAPTCHA_ACTION !== $verifyData->action ) {
                return new WP_Error('spam_detected', __( 'Anti-Spam verification failed. Token invalid.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
            /**
             * The lowest reCAPTCHA v3 score (0.0 to 1.0) a quote request may have.
             *
             * @param float $threshold The store's setting (Request a Quote, Settings), 0.5 by default.
             */
            $threshold = (float) apply_filters( 'tiered_pricing_table/request_quote/recaptcha_score_threshold', self::getRecaptchaThreshold() );
            // Check score (v3 only)
            if ( isset( $verifyData->score ) && $verifyData->score < $threshold ) {
                return new WP_Error('spam_detected', __( 'Anti-Spam verification failed. Score too low.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
        }
        // without reCAPTCHA keys the REST nonce was verified by the permission callback
        // Only the form's own fields are kept, each checked by its type. Files come last: nothing is uploaded
        // until every other field is valid.
        // a list has a quantity per line, so the form's quantity field is not part of it
        $customFields = $this->readFields( $form, $params, $isList );
        if ( is_wp_error( $customFields ) ) {
            return $customFields;
        }
        // every line checked and priced for this customer, never with the browser's prices
        $listItems = ( $isList ? QuoteListService::getRequestItems( $listLines ) : array() );
        if ( is_wp_error( $listItems ) ) {
            return $listItems;
        }
        return rest_ensure_response( array(
            'success' => true,
            'message' => __( 'Your quote request has been submitted successfully!', 'tier-pricing-table' ),
            'post_id' => 0,
        ) );
        $fileUploadResult = $this->processFileUploads( $form, $customFields, (array) $request->get_file_params() );
        if ( is_wp_error( $fileUploadResult ) ) {
            return $fileUploadResult;
        }
        // Fields have roles by type, not by name: the first Email field and the Number field synced with
        // the product quantity, so they keep working when the owner replaces the default fields.
        $emailField = $form->getEmailFieldName();
        $quantityField = $form->getQuantityFieldName();
        $customerEmail = ( $emailField ? (string) ($customFields[$emailField]['value'] ?? '') : '' );
        $quantity = ( $quantityField ? (int) ($customFields[$quantityField]['value'] ?? 0) : 0 );
        $quantity = max( 1, $quantity );
        if ( !is_email( $customerEmail ) && is_user_logged_in() ) {
            $customerEmail = (string) wp_get_current_user()->user_email;
        }
        $quote = new QuoteRequest();
        if ( $isList ) {
            $quote->setItems( $listItems );
            $more = count( $listItems ) - 1;
            $title = ( $more ? sprintf( _n(
                '%1$s and %2$d more product',
                '%1$s and %2$d more products',
                $more,
                'tier-pricing-table'
            ), $listItems[0]['name'], $more ) : $listItems[0]['name'] );
            // translators: 1: Product name, 2: Date.
            $quote->setTitle( sprintf( __( '%1$s - %2$s', 'tier-pricing-table' ), $title, wp_date( 'Y-m-d H:i:s' ) ) );
            $content = "Quote Request for Products:\n";
            foreach ( $listItems as $item ) {
                $content .= '- ' . $item['name'] . (( '' !== $item['sku'] ? ' (SKU ' . $item['sku'] . ')' : '' )) . ' x ' . $item['quantity'] . "\n";
            }
            $content .= "\n";
        } else {
            $quote->setProductId( $productId );
            // translators: 1: Product name, 2: Date.
            $quote->setTitle( sprintf( __( '%1$s - %2$s', 'tier-pricing-table' ), $product->get_name(), wp_date( 'Y-m-d H:i:s' ) ) );
            $content = "Quote Request for Product: " . $product->get_name() . " (ID: " . $productId . ")\n\n";
        }
        foreach ( $customFields as $key => $field ) {
            $content .= self::getFieldLabel( $field, $key ) . ': ' . $field['value'] . "\n";
        }
        $quote->setCustomFields( $customFields );
        $quote->setCustomerEmail( $customerEmail );
        $quote->setContent( $content );
        $quote->updateMetaData( '_form_id', $formId );
        // Save form ID as regular meta since it's not a primary property anymore
        if ( get_current_user_id() > 0 ) {
            $quote->setUserId( get_current_user_id() );
        }
        if ( !$isList ) {
            $quote->setQuantity( $quantity );
            // Calculate tier price based on customer's context
            $pricingRule = PriceManager::getPricingRule( $productId );
            $tierPrice = $pricingRule->getTierPrice( $quantity, false );
            if ( !$tierPrice && $tierPrice <= 0 ) {
                $tierPrice = $product->get_price();
            }
            $quote->setPrice( $tierPrice );
        }
        $postId = $quote->save();
        if ( !$postId ) {
            return new WP_Error('insert_failed', __( 'Could not save the quote request.', 'tier-pricing-table' ), array(
                'status' => 500,
            ));
        }
        // every product of a list, so the requests list can be filtered by any of them
        foreach ( ( $isList ? QuoteRequest::getItemProductIds( $listItems ) : array() ) as $itemProductId ) {
            add_post_meta( $postId, QuoteRequest::ITEM_PRODUCT_META, $itemProductId );
        }
        self::countRequest();
        // Trigger event for emails and other integrations
        do_action( 'tiered_pricing_table/request_quote/quote_request_submitted', $postId );
        return rest_ensure_response( array(
            'success' => true,
            'message' => __( 'Your quote request has been submitted successfully!', 'tier-pricing-table' ),
            'post_id' => $postId,
        ) );
    }

    /**
     * The form's own fields from the request, checked and cleaned by type. Anything else in the request is
     * ignored. Headings and paragraphs carry no value; files are handled by processFileUploads().
     *
     * @param  RequestQuoteForm  $form
     * @param  array  $params
     *
     * @return array|WP_Error Each field's settings with its 'value', keyed by field name, in form order.
     */
    protected function readFields( RequestQuoteForm $form, array $params, bool $skipQuantity = false ) {
        $fields = array();
        $errors = array();
        $quantityField = $form->getQuantityFieldName();
        foreach ( $form->getFields() as $field ) {
            if ( !is_array( $field ) || empty( $field['name'] ) ) {
                continue;
            }
            $name = (string) $field['name'];
            $type = ( isset( $field['type'] ) ? (string) $field['type'] : 'text' );
            if ( in_array( $type, array('heading', 'paragraph', 'file'), true ) || $skipQuantity && $name === $quantityField ) {
                continue;
            }
            $label = self::getErrorLabel( $field );
            $sent = array_key_exists( $name, $params );
            $raw = ( $sent ? $params[$name] : '' );
            // only Checkboxes with several options send a list
            if ( is_array( $raw ) && 'checkbox' !== $type ) {
                // translators: %s: field title.
                $errors[$name] = sprintf( __( '%s is not valid.', 'tier-pricing-table' ), $label );
                continue;
            }
            $values = array();
            foreach ( (array) $raw as $item ) {
                if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
                    $values[] = trim( (string) $item );
                }
            }
            if ( empty( $values ) ) {
                if ( !empty( $field['required'] ) ) {
                    // translators: %s: field title.
                    $errors[$name] = sprintf( __( '%s is required.', 'tier-pricing-table' ), $label );
                } elseif ( $sent ) {
                    $fields[$name] = array_merge( $field, array(
                        'value' => '',
                    ) );
                }
                continue;
            }
            $value = $values[0];
            switch ( $type ) {
                case 'select':
                case 'radio':
                case 'checkbox':
                    $options = RequestQuoteForm::getFieldOptions( $field );
                    if ( array_diff( $values, $options ) || 'checkbox' !== $type && count( $values ) > 1 ) {
                        // translators: %s: field title.
                        $errors[$name] = sprintf( __( '%s is not valid.', 'tier-pricing-table' ), $label );
                        continue 2;
                    }
                    $value = implode( ', ', array_map( 'sanitize_text_field', array_unique( $values ) ) );
                    break;
                case 'email':
                    if ( !is_email( $value ) ) {
                        // translators: %s: field title.
                        $errors[$name] = sprintf( __( '%s needs a valid email address.', 'tier-pricing-table' ), $label );
                        continue 2;
                    }
                    $value = sanitize_email( $value );
                    break;
                case 'number':
                    if ( $name === $quantityField ) {
                        if ( false === filter_var( $value, FILTER_VALIDATE_INT, array(
                            'options' => array(
                                'min_range' => 1,
                            ),
                        ) ) ) {
                            // translators: %s: field title.
                            $errors[$name] = sprintf( __( '%s needs a whole number of 1 or more.', 'tier-pricing-table' ), $label );
                            continue 2;
                        }
                        $value = (string) (int) $value;
                    } elseif ( !is_numeric( $value ) ) {
                        // translators: %s: field title.
                        $errors[$name] = sprintf( __( '%s needs a number.', 'tier-pricing-table' ), $label );
                        continue 2;
                    }
                    break;
                case 'date':
                    $date = \DateTime::createFromFormat( '!Y-m-d', $value );
                    if ( !$date || $date->format( 'Y-m-d' ) !== $value ) {
                        // translators: %s: field title.
                        $errors[$name] = sprintf( __( '%s needs a date.', 'tier-pricing-table' ), $label );
                        continue 2;
                    }
                    if ( !empty( $field['disablePastDates'] ) && $value < wp_date( 'Y-m-d' ) ) {
                        // translators: %s: field title.
                        $errors[$name] = sprintf( __( '%s cannot be a past date.', 'tier-pricing-table' ), $label );
                        continue 2;
                    }
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field( $value );
                    break;
                default:
                    $value = sanitize_text_field( $value );
            }
            $maxLength = self::MAX_LENGTHS[$type] ?? 255;
            if ( !in_array( $type, array('select', 'radio', 'checkbox'), true ) && mb_strlen( $value ) > $maxLength ) {
                $value = mb_substr( $value, 0, $maxLength );
            }
            $fields[$name] = array_merge( $field, array(
                'value' => $value,
            ) );
        }
        if ( $errors ) {
            return new WP_Error('invalid_fields', implode( "\n", $errors ), array(
                'status' => 400,
                'fields' => $errors,
            ));
        }
        return $fields;
    }

    /**
     * A field's title for the stored request: its label, or its name made readable.
     *
     * @param  array  $field
     * @param  string  $name
     *
     * @return string
     */
    public static function getFieldLabel( array $field, string $name ) : string {
        $label = ( isset( $field['label'] ) ? trim( (string) $field['label'] ) : '' );
        return ( '' !== $label ? $label : ucfirst( str_replace( '_', ' ', $name ) ) );
    }

    /**
     * A field's title for an error message: its label, its placeholder, or "This field".
     *
     * @param  array  $field
     *
     * @return string
     */
    protected static function getErrorLabel( array $field ) : string {
        foreach ( array('label', 'placeholder') as $key ) {
            $text = ( isset( $field[$key] ) ? trim( wp_strip_all_tags( (string) $field[$key] ) ) : '' );
            if ( '' !== $text ) {
                return $text;
            }
        }
        return __( 'This field', 'tier-pricing-table' );
    }

    /**
     * Upload the files sent for the form's own File fields. Every file is checked before any is saved.
     *
     * @param  RequestQuoteForm  $form
     * @param  array  $customFields  The fields read so far; uploaded files are added.
     *
     * @return true|WP_Error
     */
    private function processFileUploads( RequestQuoteForm $form, array &$customFields, array $files ) {
        $uploads = array();
        foreach ( $form->getFields() as $fieldConfig ) {
            if ( !is_array( $fieldConfig ) || 'file' !== ($fieldConfig['type'] ?? '') || empty( $fieldConfig['name'] ) ) {
                continue;
            }
            $key = (string) $fieldConfig['name'];
            $file = ( isset( $files[$key] ) && is_array( $files[$key] ) ? $files[$key] : null );
            $hasFile = $file && !empty( $file['name'] ) && is_string( $file['name'] ) && isset( $file['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $file['error'];
            if ( !$hasFile ) {
                if ( !empty( $fieldConfig['required'] ) ) {
                    // translators: %s: field title.
                    return new WP_Error('invalid_fields', sprintf( __( '%s is required.', 'tier-pricing-table' ), self::getErrorLabel( $fieldConfig ) ), array(
                        'status' => 400,
                    ));
                }
                continue;
            }
            // too big for the server, or cut off: tell the customer instead of dropping the file
            if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
                return new WP_Error('upload_failed', __( 'File upload failed.', 'tier-pricing-table' ), array(
                    'status' => 400,
                ));
            }
            // Validate Max Size
            if ( !empty( $fieldConfig['maxFileSize'] ) && (float) $fieldConfig['maxFileSize'] > 0 ) {
                $maxBytes = (float) $fieldConfig['maxFileSize'] * 1024 * 1024;
                if ( $file['size'] > $maxBytes ) {
                    // translators: 1: File name, 2: Max file size in MB.
                    return new WP_Error('file_too_large', sprintf( __( 'File %1$s exceeds the maximum allowed size of %2$s MB.', 'tier-pricing-table' ), esc_html( $file['name'] ), esc_html( $fieldConfig['maxFileSize'] ) ), array(
                        'status' => 400,
                    ));
                }
            }
            // Validate Extension
            if ( !empty( $fieldConfig['allowedTypes'] ) ) {
                $allowedExts = array_map( 'strtolower', array_map( 'trim', explode( ',', str_replace( '.', '', $fieldConfig['allowedTypes'] ) ) ) );
                $fileExt = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
                if ( !in_array( $fileExt, $allowedExts, true ) ) {
                    // translators: 1: File extension, 2: Allowed extensions.
                    return new WP_Error('invalid_file_type', sprintf( __( 'File type .%1$s is not allowed. Allowed: %2$s', 'tier-pricing-table' ), esc_html( $fileExt ), esc_html( $fieldConfig['allowedTypes'] ) ), array(
                        'status' => 400,
                    ));
                }
            }
            $uploads[$key] = array($fieldConfig, $file);
        }
        if ( !$uploads ) {
            return true;
        }
        $saved = array();
        foreach ( $uploads as $key => list( $fieldConfig, $file ) ) {
            // a private folder, under a name nobody can guess; store managers download it through WordPress
            $stored = QuoteFiles::upload( $file );
            if ( is_wp_error( $stored ) ) {
                // keep no half-sent request's files
                array_map( 'wp_delete_file', $saved );
                return $stored;
            }
            $saved[] = $stored['file'];
            $fieldConfig['value'] = $stored['name'];
            $fieldConfig['file_path'] = $stored['file'];
            $customFields[$key] = $fieldConfig;
        }
        return true;
    }

}
