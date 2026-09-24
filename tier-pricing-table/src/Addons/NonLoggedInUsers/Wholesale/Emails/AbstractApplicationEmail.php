<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication;
use WC_Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared plumbing of the wholesale application e-mails: templates, the application object,
 * and the standard WooCommerce e-mail settings.
 */
abstract class AbstractApplicationEmail extends WC_Email {

	/** @var WholesaleApplication|null */
	public $object;

	/** The action that sends this e-mail; receives the application id. */
	abstract protected function getTriggerAction(): string;

	public function __construct() {
		$this->template_base = dirname( __DIR__ ) . '/views/';

		parent::__construct();

		// the "_notification" variant fired by WC_Emails::send_transactional_email() (see EmailsManager)
		add_action( $this->getTriggerAction() . '_notification', array( $this, 'trigger' ), 10, 1 );
	}

	public function trigger( $applicationId ) {
		$this->object = WholesaleApplication::get( (int) $applicationId );

		if ( ! $this->object || ! $this->is_enabled() ) {
			return;
		}

		$this->setup_locale();

		if ( $this->customer_email ) {
			$this->recipient = $this->object->getEmail();
		}

		$this->placeholders['{applicant_name}'] = $this->object->getName();
		$this->placeholders['{company}']        = $this->object->getCompany();

		if ( $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	protected function getTemplateArgs( bool $plain ): array {
		return array(
			'application'   => $this->object,
			'emailHeading'  => $this->get_heading(),
			'sent_to_admin' => ! $this->customer_email,
			'plain_text'    => $plain,
			'email'         => $this,
		);
	}

	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->getTemplateArgs( false ), '', $this->template_base );
	}

	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->getTemplateArgs( true ), '', $this->template_base );
	}

	public function init_form_fields() {
		$fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'tier-pricing-table' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'tier-pricing-table' ),
				'default' => 'yes',
			),
		);

		if ( ! $this->customer_email ) {
			$fields['recipient'] = array(
				'title'       => __( 'Recipient(s)', 'tier-pricing-table' ),
				'type'        => 'text',
				/* translators: %s: admin e-mail */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'tier-pricing-table' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			);
		}

		$fields['subject'] = array(
			'title'       => __( 'Subject', 'tier-pricing-table' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => __( 'Available placeholders: {site_title}, {applicant_name}, {company}', 'tier-pricing-table' ),
			'placeholder' => $this->get_default_subject(),
			'default'     => '',
		);

		$fields['heading'] = array(
			'title'       => __( 'Email heading', 'tier-pricing-table' ),
			'type'        => 'text',
			'desc_tip'    => true,
			'description' => __( 'Available placeholders: {site_title}, {applicant_name}, {company}', 'tier-pricing-table' ),
			'placeholder' => $this->get_default_heading(),
			'default'     => '',
		);

		$fields['additional_content'] = array(
			'title'       => __( 'Additional content', 'tier-pricing-table' ),
			'description' => __( 'Text to appear below the main email content.', 'tier-pricing-table' ),
			'css'         => 'width:400px; height: 75px;',
			'placeholder' => __( 'N/A', 'tier-pricing-table' ),
			'type'        => 'textarea',
			'default'     => $this->get_default_additional_content(),
			'desc_tip'    => true,
		);

		$fields['email_type'] = array(
			'title'       => __( 'Email type', 'tier-pricing-table' ),
			'type'        => 'select',
			'description' => __( 'Choose which format of email to send.', 'tier-pricing-table' ),
			'default'     => 'html',
			'class'       => 'email_type wc-enhanced-select',
			'options'     => $this->get_email_type_options(),
			'desc_tip'    => true,
		);

		$this->form_fields = $fields;
	}
}
