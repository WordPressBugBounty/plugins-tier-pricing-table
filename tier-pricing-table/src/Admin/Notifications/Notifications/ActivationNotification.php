<?php namespace TierPricingTable\Admin\Notifications\Notifications;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Admin\WelcomePage\WelcomePage;

/**
 * Shown once after activation, as the way to the getting-started page for anyone the post-activation
 * redirect missed (WP-CLI, bulk activation, a reinstall). Opening the page clears it, and a store
 * that already has every step done never sees it.
 */
class ActivationNotification extends Notification {
	
	use ServiceContainerTrait;
	
	const ACTIVATION_TRANSIENT_NAME = 'tiered_pricing_table_activated';
	
	public static function setActive() {
		set_transient( self::ACTIVATION_TRANSIENT_NAME, 'yes' );
	}
	
	public static function setInactive() {
		delete_transient( self::ACTIVATION_TRANSIENT_NAME );
	}
	
	public function getId(): string {
		return 'activation-notification';
	}
	
	public function getTemplate(): string {
		return 'admin/notifications/activation-notification.php';
	}
	
	public function getCloseURL(): string {
		return $this->getContainer()->getNotificationManager()->getNotificationCloseURL( $this->getId() );
	}
	
	public function isSeen(): bool {
		return get_transient( self::ACTIVATION_TRANSIENT_NAME ) !== 'yes';
	}
	
	/**
	 * Not on the getting-started page itself (the page hides notices, and it is where the notice leads)
	 * and not on the Freemius opt-in screen, which ends on that page anyway.
	 */
	public function isActive(): bool {
		if ( $this->isSeen() ) {
			return false;
		}
		
		if ( WelcomePage::isComplete() ) {
			self::setInactive();
			
			return false;
		}
		
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		
		if ( WelcomePage::PAGE_SLUG === $page ) {
			return false;
		}
		
		return ! ( function_exists( 'tpt_fs' ) && tpt_fs()->is_activation_page() );
	}
}
