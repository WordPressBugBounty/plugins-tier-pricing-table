<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails;

class EmailsManager {

	const ACTIONS = array(
		'tiered_pricing_table/wholesale/application_submitted',
		'tiered_pricing_table/wholesale/application_approved',
		'tiered_pricing_table/wholesale/application_rejected',
	);

	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'registerEmails' ) );

		// WooCommerce loads its mailer (and with it these e-mail classes) only for the actions in this
		// list; each action is then re-fired as "<action>_notification", which the e-mails listen to
		add_filter( 'woocommerce_email_actions', array( $this, 'registerActions' ) );
	}

	public function registerActions( $actions ): array {
		return array_values( array_unique( array_merge( (array) $actions, self::ACTIONS ) ) );
	}

	public function registerEmails( array $emails ): array {
		$emails['TPT_Wholesale_Admin_New_Application']  = new AdminNewApplicationEmail();
		$emails['TPT_Wholesale_Customer_Received']      = new CustomerApplicationReceivedEmail();
		$emails['TPT_Wholesale_Customer_Approved']      = new CustomerApplicationApprovedEmail();
		$emails['TPT_Wholesale_Customer_Rejected']      = new CustomerApplicationRejectedEmail();

		return $emails;
	}
}
