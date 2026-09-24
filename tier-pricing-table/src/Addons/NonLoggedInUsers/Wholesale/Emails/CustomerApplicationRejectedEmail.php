<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomerApplicationRejectedEmail extends AbstractApplicationEmail {

	public function __construct() {
		$this->id             = 'tpt_wholesale_customer_rejected';
		$this->title          = __( 'Wholesale application rejected', 'tier-pricing-table' );
		$this->description    = __( 'Sent to the applicant when the application is rejected, with the reason if one was given.', 'tier-pricing-table' );
		$this->template_html  = 'emails/customer-application-rejected.php';
		$this->template_plain = 'emails/plain/customer-application-rejected.php';
		$this->customer_email = true;

		parent::__construct();
	}

	protected function getTriggerAction(): string {
		return 'tiered_pricing_table/wholesale/application_rejected';
	}

	public function get_default_subject() {
		return __( 'About your wholesale application at {site_title}', 'tier-pricing-table' );
	}

	public function get_default_heading() {
		return __( 'Your wholesale application', 'tier-pricing-table' );
	}
}
