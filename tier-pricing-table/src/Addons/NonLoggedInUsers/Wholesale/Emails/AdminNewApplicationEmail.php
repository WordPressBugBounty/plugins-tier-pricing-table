<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminNewApplicationEmail extends AbstractApplicationEmail {

	public function __construct() {
		$this->id             = 'tpt_wholesale_admin_new_application';
		$this->title          = __( 'Wholesale application (admin)', 'tier-pricing-table' );
		$this->description    = __( 'Sent to the store owner when a customer applies for a wholesale account.', 'tier-pricing-table' );
		$this->template_html  = 'emails/admin-new-application.php';
		$this->template_plain = 'emails/plain/admin-new-application.php';
		$this->customer_email = false;

		parent::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	protected function getTriggerAction(): string {
		return 'tiered_pricing_table/wholesale/application_submitted';
	}

	public function get_default_subject() {
		return __( '[{site_title}] New wholesale application from {applicant_name}', 'tier-pricing-table' );
	}

	public function get_default_heading() {
		return __( 'New wholesale application', 'tier-pricing-table' );
	}
}
