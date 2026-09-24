<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\FormFields;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;
use WC_Customer;
use WP_Error;
use WP_User;

/**
 * Submits, approves and rejects wholesale applications.
 */
class ApplicationService {

	/**
	 * Whether the registration form has to ask for a password (WooCommerce can generate one instead).
	 */
	public static function needsPassword(): bool {
		return 'yes' !== get_option( 'woocommerce_registration_generate_password', 'yes' );
	}

	/**
	 * Handles a form submission: validates, creates the customer account for guests, stores the
	 * application, and approves it right away when the store approves automatically.
	 *
	 * @param  array  $input  raw form values
	 * @param  WP_User|null  $currentUser  the logged-in customer, or null for a guest
	 *
	 * @return WholesaleApplication|WP_Error
	 */
	public function submit( array $input, ?WP_User $currentUser ) {
		$isGuest = null === $currentUser;

		$fields = Settings::getFormFields();

		$validated = ApplicationValidator::validate( $input, array(
			'fields'        => $fields,
			'isGuest'       => $isGuest,
			'needsPassword' => $isGuest && self::needsPassword(),
			'needsTerms'    => Settings::getTermsPageId() > 0,
			'emailExists'   => function ( string $email ): bool {
				return false !== email_exists( $email );
			},
		) );

		if ( ! empty( $validated['errors'] ) ) {
			$error = new WP_Error();

			foreach ( $validated['errors'] as $field => $message ) {
				$error->add( $field, $message );
			}

			return $error;
		}

		$data = $validated['data'];

		if ( $isGuest ) {
			$userId = $this->createCustomer( $data );

			if ( is_wp_error( $userId ) ) {
				return $userId;
			}

			$user = get_user_by( 'id', $userId );
		} else {
			$user = $currentUser;

			$existing = WholesaleApplication::findForUser( $user->ID );

			if ( $existing && $existing->isPending() ) {
				return new WP_Error( 'pending', __( 'You already have a pending application.', 'tier-pricing-table' ) );
			}

			$data['first_name'] = $user->first_name;
			$data['last_name']  = $user->last_name;
			$data['email']      = $user->user_email;
		}

		$this->saveBusinessDetails( $user, $data );

		// the recognised keys have their own meta; everything else the store owner added is kept as custom fields
		$data['custom_fields'] = array();

		foreach ( $fields as $field ) {
			$key = (string) $field['key'];

			if ( FormFields::isKnownKey( $key ) || ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$data['custom_fields'][ $key ] = array(
				'label' => (string) $field['label'],
				'type'  => (string) $field['type'],
				'value' => (string) $data[ $key ],
			);

			unset( $data[ $key ] );
		}

		$data['user_id']        = $user->ID;
		$data['requested_role'] = Settings::getRole();
		unset( $data['password'] );

		$application = WholesaleApplication::create( $data );

		if ( ! $application ) {
			return new WP_Error( 'save', __( 'The application could not be saved. Please try again.', 'tier-pricing-table' ) );
		}

		do_action( 'tiered_pricing_table/wholesale/application_submitted', $application->getId(), $application );

		if ( Settings::isAutoApproval() ) {
			$this->approve( $application, 0 );
		}

		return $application;
	}

	/**
	 * @return int|WP_Error the new user id
	 */
	protected function createCustomer( array $data ) {
		$password = $data['password'] ?? '';

		$userId = wc_create_new_customer( $data['email'], '', $password, array(
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
		) );

		if ( is_wp_error( $userId ) ) {
			return $userId;
		}

		return (int) $userId;
	}

	protected function saveBusinessDetails( WP_User $user, array $data ) {
		try {
			$customer = new WC_Customer( $user->ID );

			if ( ! empty( $data['first_name'] ) && ! $customer->get_first_name() ) {
				$customer->set_first_name( $data['first_name'] );
			}

			if ( ! empty( $data['last_name'] ) && ! $customer->get_last_name() ) {
				$customer->set_last_name( $data['last_name'] );
			}

			if ( ! empty( $data['company'] ) ) {
				$customer->set_billing_company( $data['company'] );
			}

			if ( ! empty( $data['phone'] ) ) {
				$customer->set_billing_phone( $data['phone'] );
			}

			if ( ! empty( $data['website'] ) ) {
				wp_update_user( array( 'ID' => $user->ID, 'user_url' => $data['website'] ) );
			}

			$customer->save();
		} catch ( \Exception $e ) {
			// the application still holds the details; the customer record is a convenience
			unset( $e );
		}
	}

	/**
	 * Gives the applicant the wholesale role and marks the application approved.
	 *
	 * @param  int  $decidedBy  admin user id, 0 for automatic approval
	 */
	public function approve( WholesaleApplication $application, int $decidedBy ): bool {
		if ( $application->isApproved() ) {
			return true;
		}

		$user = $application->getUser();
		$role = $application->getRequestedRole() ?: Settings::getRole();

		if ( ! $user || ! $role || ! wp_roles()->is_role( $role ) ) {
			return false;
		}

		$user->set_role( $role );

		if ( ! $application->setStatus( WholesaleApplication::STATUS_APPROVED, $decidedBy ) ) {
			return false;
		}

		do_action( 'tiered_pricing_table/wholesale/application_approved', $application->getId(), $application, $decidedBy );

		return true;
	}

	/**
	 * Marks the application rejected; the user keeps the role they have.
	 */
	public function reject( WholesaleApplication $application, int $decidedBy, string $reason = '' ): bool {
		if ( $application->isRejected() ) {
			return true;
		}

		$role = $application->getRequestedRole() ?: Settings::getRole();
		$user = $application->getUser();

		// an approved application that is rejected later takes the role away again
		if ( $application->isApproved() && $user && $role && in_array( $role, (array) $user->roles, true ) ) {
			$user->set_role( 'customer' );
		}

		if ( ! $application->setStatus( WholesaleApplication::STATUS_REJECTED, $decidedBy, $reason ) ) {
			return false;
		}

		do_action( 'tiered_pricing_table/wholesale/application_rejected', $application->getId(), $application, $decidedBy );

		return true;
	}

	/**
	 * Whether the user already has the wholesale role.
	 */
	public static function isWholesaleUser( ?WP_User $user ): bool {
		$role = Settings::getRole();

		return $user && $role && in_array( $role, (array) $user->roles, true );
	}
}
