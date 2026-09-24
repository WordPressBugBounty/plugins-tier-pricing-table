<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomerApplicationReceivedEmail extends AbstractApplicationEmail {

	public function __construct() {
		$this->id             = 'tpt_wholesale_customer_received';
		$this->title          = __( 'Wholesale application received', 'tier-pricing-table' );
		$this->description    = __( 'Sent to the applicant when the application is waiting for approval.', 'tier-pricing-table' );
		$this->template_html  = 'emails/customer-application-received.php';
		$this->template_plain = 'emails/plain/customer-application-received.php';
		$this->customer_email = true;

		parent::__construct();
	}

	protected function getTriggerAction(): string {
		return 'tiered_pricing_table/wholesale/application_submitted';
	}

	public function trigger( $applicationId ) {
		// with automatic approval the "approved" e-mail follows right away; this one would only add noise
		if ( Settings::isAutoApproval() ) {
			return;
		}

		parent::trigger( $applicationId );
	}

	public function get_default_subject() {
		return __( 'We received your wholesale application', 'tier-pricing-table' );
	}

	public function get_default_heading() {
		return __( 'Thank you for applying', 'tier-pricing-table' );
	}
}
