<?php namespace TierPricingTable\Addons\RequestAQuote\Emails;

use TierPricingTable\Addons\RequestAQuote\CPT\QuoteRequestCPT;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomerQuoteEmail extends \WC_Email {
	
	public function __construct() {
		$this->id             = 'tier_pricing_table_quote_request_customer';
		$this->title          = __( 'Quote Request (Customer)', 'tier-pricing-table' );
		$this->description    = __( 'This email is sent to the customer to acknowledge their quote request.',
			'tier-pricing-table' );
		$this->template_html  = 'emails/customer-quote-request.php';
		$this->template_plain = 'emails/plain/customer-quote-request.php';
		$this->template_base  = dirname( __DIR__ ) . '/views/';
		
		// Call parent constructor
		parent::__construct();
		
		// a customer e-mail: WooCommerce lists the customer as the recipient and sends it in the site's language
		$this->customer_email = true;
		
		add_action( 'tiered_pricing_table/request_quote/quote_request_submitted', array( $this, 'trigger' ), 10, 1 );
	}
	
	public function trigger( $postId ) {
		$this->object = new \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest( $postId );
		
		if ( ! $this->object || ! $this->object->getId() ) {
			return;
		}
		
		// Check form settings
		$formId = $this->object->getMeta('_form_id');
		$form   = RequestQuoteForm::get( $formId );
		
		if ( $form && ! $form->isCustomerEmailEnabled() ) {
			return; // Customer email is disabled for this form
		}
		
		$this->setup_locale();
		
		// Determine the customer's email address
		$this->recipient = $this->getCustomerEmail();
		
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(),
				$this->get_attachments() );
		}
		
		$this->restore_locale();
	}
	
	/**
	 * The address saved with the request (from the form's first Email field), or the customer's account e-mail.
	 *
	 * @return string
	 */
	private function getCustomerEmail(): string {
		$email = (string) $this->object->getCustomerEmail();

		if ( is_email( $email ) ) {
			return $email;
		}

		$user = $this->object->getUserId() ? get_userdata( $this->object->getUserId() ) : false;

		return $user && is_email( $user->user_email ) ? (string) $user->user_email : '';
	}
	
	public function get_default_subject() {
		return __( '[{site_title}] Your quote request #{quote_number}: {product_name}', 'tier-pricing-table' );
	}
	
	public function get_default_heading() {
		return __( 'Quote Request Received', 'tier-pricing-table' );
	}
	
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'quotePost'     => $this->object,
			'emailHeading'  => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $this,
		), '', $this->template_base );
	}
	
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'quotePost'     => $this->object,
			'emailHeading'  => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'         => $this,
		), '', $this->template_base );
	}
	
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'tier-pricing-table' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'tier-pricing-table' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'tier-pricing-table' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the email subject line. Leave blank to use the default subject: ',
						'tier-pricing-table' ) . '<code>' . $this->get_default_subject() . '</code>',
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'tier-pricing-table' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: ',
						'tier-pricing-table' ) . '<code>' . $this->get_default_heading() . '</code>',
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'tier-pricing-table' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'tier-pricing-table' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
	
	public function format_string( $string ) {
		$productName = __( 'Sample Product', 'tier-pricing-table' );
		
		if ( $this->object && $this->object instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ) {
			$productId = $this->object->getProductId();
			$product   = wc_get_product( $productId );
			if ( $product ) {
				$productName = $product->get_name();
			}

			// a quote list request: "Bolt and 2 more products"
			if ( $this->object->isList() ) {
				$productName = $this->object->getSummaryName();
			}
		}
		
		$string = str_replace( '{product_name}', $productName, $string );

		// the number the customer saw when they sent the request ("Your request number is #123")
		$quoteNumber = $this->object instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ? (string) $this->object->getId() : '123';
		$string      = str_replace( '{quote_number}', $quoteNumber, $string );
		
		return parent::format_string( $string );
	}
	
	public function get_submitted_fields() {
		if ( ! $this->object || ! ( $this->object instanceof \TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest ) ) {
			return array(
				array( 'label' => __( 'Sample Name', 'tier-pricing-table' ), 'value' => 'John Doe' ),
				array( 'label' => __( 'Sample Email', 'tier-pricing-table' ), 'value' => 'john@example.com' ),
			);
		}
		
		// files: their name only; the customer cannot open the store's copy
		return $this->object->getSubmittedFields( false );
	}
}
