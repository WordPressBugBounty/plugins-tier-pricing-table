<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomerApplicationApprovedEmail extends AbstractApplicationEmail {

	public function __construct() {
		$this->id             = 'tpt_wholesale_customer_approved';
		$this->title          = __( 'Wholesale application approved', 'tier-pricing-table' );
		$this->description    = __( 'Sent to the applicant when the wholesale account is approved.', 'tier-pricing-table' );
		$this->template_html  = 'emails/customer-application-approved.php';
		$this->template_plain = 'emails/plain/customer-application-approved.php';
		$this->customer_email = true;

		parent::__construct();
	}

	protected function getTriggerAction(): string {
		return 'tiered_pricing_table/wholesale/application_approved';
	}

	public function get_default_subject() {
		return __( 'Your wholesale account at {site_title} is approved', 'tier-pricing-table' );
	}

	public function get_default_heading() {
		return __( 'Welcome aboard', 'tier-pricing-table' );
	}
}
