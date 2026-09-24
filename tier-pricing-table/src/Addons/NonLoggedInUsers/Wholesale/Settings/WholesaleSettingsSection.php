<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT\ApplicationCPT;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;

class WholesaleSettingsSection extends SectionAbstract {

	const SLUG = 'wholesale';

	public function getName(): string {
		return __( 'Wholesale', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return self::SLUG;
	}

	public function getSettings(): array {
		$premium  = tpt_fs()->can_use_premium_code__premium_only();
		$settings = array();

		$settings[] = array(
				'title' => __( 'Wholesale registration', 'tier-pricing-table' ),
				'desc'  => $this->getIntro( $premium ),
				'id'    => Settings::optionId( 'section_registration' ),
				'type'  => 'title',
		);

		$settings = array_merge( $settings, array(
			array(
				'title'   => __( 'Enable wholesale registration', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'enabled' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'Publishes the application form, the approval queue and the e-mails. Switch it on to see and set up the options below; nothing goes live until you save.',
					'tier-pricing-table' ),
			),
			array(
				'title'    => __( 'Wholesale role', 'tier-pricing-table' ),
				'id'       => Settings::optionId( 'role' ),
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => $this->getRoleOptions(),
				'default'  => Settings::getRole(),
				'desc'     => sprintf(
				/* translators: %s: link to Roles Management */
					__( 'The role approved applicants get. Create roles under %s, then set up prices for the role on products or in a global pricing rule.', 'tier-pricing-table' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings&section=advanced#roles' ) ) . '">' . esc_html__( 'Advanced → Roles Management', 'tier-pricing-table' ) . '</a>'
				),
				'desc_tip' => false,
			),
			array(
				'title'   => __( 'Approval', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'approval' ),
				'type'    => 'select',
				'options' => array(
					Settings::APPROVAL_MANUAL => __( 'Review every application (approve or reject by hand)',
						'tier-pricing-table' ),
					Settings::APPROVAL_AUTO   => __( 'Approve automatically', 'tier-pricing-table' ),
				),
				'default' => Settings::APPROVAL_MANUAL,
				'desc'    => __( 'Pending applicants keep the regular customer role until they are approved.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Registration page', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'registration_page' ),
				'type'    => 'single_select_page',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'desc'    => sprintf(
				/* translators: %s: shortcode */
					__( 'The page that contains the %s shortcode. Used for the link on the login form and in e-mails.',
						'tier-pricing-table' ),
					'<code>[tiered_pricing_wholesale_registration]</code>'
				),
			),
			array(
				'title'   => __( 'After login, send wholesale customers to', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'login_redirect_page' ),
				'type'    => 'single_select_page',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'desc'    => __( 'Optional. A wholesale landing page, a catalog category or the shop. Leave empty to keep the default My Account redirect.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Link on the login form', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'login_form_link' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Show an "Apply for a wholesale account" link under the My Account login form. Needs the registration page above.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Link text', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'login_form_link_text' ),
				'type'    => 'text',
				'default' => __( 'Apply for a wholesale account', 'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::optionId( 'section_registration' ),
			),

			array(
				'title' => __( 'Application form', 'tier-pricing-table' ),
				'desc'  => __( 'Name, e-mail and (when WooCommerce does not generate passwords) a password are always asked. Build the rest of the form here.',
					'tier-pricing-table' ),
				'id'    => Settings::optionId( 'section_form' ),
				'type'  => 'title',
			),
			array(
				'title' => __( 'Fields', 'tier-pricing-table' ),
				'id'    => Settings::optionId( 'form_fields' ),
				'type'  => FormFieldsOption::FIELD_TYPE,
				'desc'  => __( 'Company, Phone and Website are copied to the customer account; every other field is stored with the application and shown in the queue and the e-mails. A dropdown takes one option per line.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Terms page', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'terms_page' ),
				'type'    => 'single_select_page',
				'class'   => 'wc-enhanced-select',
				'default' => '',
				'desc'    => __( 'Optional. Adds an "I agree to the terms" checkbox linking to this page.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Form title', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'form_title' ),
				'type'    => 'text',
				'default' => __( 'Wholesale account', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Intro text', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'form_intro' ),
				'type'    => 'textarea',
				'css'     => 'min-height: 70px;',
				'default' => __( 'Fill in the form below. We review every application and e-mail you as soon as your account is approved.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Button text', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'submit_text' ),
				'type'    => 'text',
				'default' => __( 'Apply for a wholesale account', 'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::optionId( 'section_form' ),
			),

			array(
				'title' => __( 'Spam protection', 'tier-pricing-table' ),
				'desc'  => sprintf(
				/* translators: %s: link to the Google reCAPTCHA admin console */
					__( 'The form already has a honeypot. For reCAPTCHA v3, create a key pair of type "Score based (v3)" in the %s and enter it here; these keys are separate from the Request a Quote keys.', 'tier-pricing-table' ),
					'<a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">' . esc_html__( 'reCAPTCHA admin console', 'tier-pricing-table' ) . '</a>'
				),
				'id'    => Settings::optionId( 'section_spam' ),
				'type'  => 'title',
			),
			array(
				'title'   => __( 'reCAPTCHA v3', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'recaptcha' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => $this->getRecaptchaDescription(),
			),
			array(
				'title'       => __( 'Site key', 'tier-pricing-table' ),
				'id'          => Settings::optionId( 'recaptcha_site_key' ),
				'type'        => 'text',
				'default'     => '',
				'css'         => 'min-width: 400px;',
				'placeholder' => '6L…',
			),
			array(
				'title'       => __( 'Secret key', 'tier-pricing-table' ),
				'id'          => Settings::optionId( 'recaptcha_secret_key' ),
				'type'        => 'password',
				'default'     => '',
				'css'         => 'min-width: 400px;',
				'placeholder' => '6L…',
				'desc'        => __( 'Kept on the server; used only to verify submissions with Google.', 'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::optionId( 'section_spam' ),
			),

			array(
				'title' => __( 'Messages', 'tier-pricing-table' ),
				'desc'  => __( 'Shown on the registration page. The e-mails are edited under WooCommerce → Settings → Emails.',
					'tier-pricing-table' ),
				'id'    => Settings::optionId( 'section_messages' ),
				'type'  => 'title',
			),
			array(
				'title'   => __( 'Application received', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'pending_message' ),
				'type'    => 'textarea',
				'css'     => 'min-height: 70px;',
				'default' => __( 'Thank you! Your application has been received. We will e-mail you when it is approved.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Approved', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'approved_message' ),
				'type'    => 'textarea',
				'css'     => 'min-height: 70px;',
				'default' => __( 'Your wholesale account is ready. You are logged in and see wholesale prices from now on.',
					'tier-pricing-table' ),
				'desc'    => __( 'Shown after automatic approval, and to approved customers who open the registration page.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Rejected', 'tier-pricing-table' ),
				'id'      => Settings::optionId( 'rejected_message' ),
				'type'    => 'textarea',
				'css'     => 'min-height: 70px;',
				'default' => __( 'We could not approve your wholesale application. Contact us if you have questions.',
					'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => Settings::optionId( 'section_messages' ),
			),
		) );

		// groups other modules put on this tab after the registration settings: the guest options, for one
		foreach ( (array) apply_filters( 'tiered_pricing_table/settings/wholesale_subsections', array() ) as $subsection ) {
			if ( class_exists( $subsection ) ) {
				$settings = array_merge( $settings, ( new $subsection() )->getWrappedSettings() );
			}
		}

		return $settings;
	}

	protected function getIntro( bool $premium ): string {
		$intro = __( 'Customers apply for a wholesale account with the registration form; you approve them under WooCommerce → Wholesale Applications, and they get the wholesale role with its prices.',
			'tier-pricing-table' );

		// the queue (its post type and menu) only exists while the feature is on
		if ( ! Settings::isEnabled() ) {
			return $intro . ' ' . esc_html__( 'The feature is off until you enable it below.', 'tier-pricing-table' );
		}

		$queueUrl = admin_url( 'edit.php?post_type=' . ApplicationCPT::POST_TYPE );

		return $intro . ' <a href="' . esc_url( $queueUrl ) . '">' . esc_html__( 'Open the applications queue', 'tier-pricing-table' ) . '</a>';
	}

	protected function getRoleOptions(): array {
		$options = array( '' => __( 'Select a role…', 'tier-pricing-table' ) );

		if ( ! function_exists( 'wp_roles' ) ) {
			return $options;
		}

		foreach ( wp_roles()->roles as $key => $role ) {
			if ( in_array( $key, array( 'administrator', 'editor', 'author', 'contributor', 'shop_manager' ), true ) ) {
				continue;
			}

			$options[ $key ] = translate_user_role( $role['name'] );
		}

		return $options;
	}


	protected function getRecaptchaDescription(): string {
		$keys = Settings::getRecaptchaKeys();

		if ( $keys['site'] && $keys['secret'] ) {
			return __( 'Checks every submission with Google reCAPTCHA v3 and rejects low scores.', 'tier-pricing-table' );
		}

		return __( 'Needs the site key and the secret key below; the switch does nothing until both are set.',
			'tier-pricing-table' );
	}
}
