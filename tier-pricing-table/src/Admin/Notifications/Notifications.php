<?php namespace TierPricingTable\Admin\Notifications;

use TierPricingTable\Admin\Notifications\Notifications\ActivationNotification;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2024;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2025;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2026;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2027;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2028;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2029;
use TierPricingTable\Admin\Notifications\Notifications\BlackFriday2030;
use TierPricingTable\Admin\Notifications\Notifications\Notification;
use TierPricingTable\Admin\Notifications\Notifications\TwoMonthsUsingDiscount;
use TierPricingTable\Admin\Notifications\Notifications\YearUsingDiscount;
use TierPricingTable\Core\ServiceContainerTrait;

/**
 * Class Notifications
 *
 * @package TierPricingTable\Admin\Notifications
 */
class Notifications {
	
	use ServiceContainerTrait;
	
	const CLOSE_NOTIFICATION_ACTION = 'tpt_close_notification';
	const SEEN_NOTIFICATIONS_OPTION_KEY = 'tpt_seen_notifications';

	const SNOOZE_NOTIFICATION_ACTION = 'tpt_snooze_notification';
	const SNOOZED_NOTIFICATIONS_OPTION_KEY = 'tpt_snoozed_notifications';
	const SNOOZE_PERIOD = MONTH_IN_SECONDS;
	
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}
	
	public function init() {
		$this->handleClosingNotification();
		$this->handleSnoozingNotification();
		$this->initNotifications();
	}

	/**
	 * "Remind me later": the notice stays away for a month, then comes back unless dismissed.
	 */
	public function handleSnoozingNotification() {
		add_action( 'admin_post_' . self::SNOOZE_NOTIFICATION_ACTION, function () {
			$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
			$id    = isset( $_GET['notification_id'] ) ? sanitize_text_field( wp_unslash( $_GET['notification_id'] ) ) : '';

			if ( $id && wp_verify_nonce( $nonce, self::SNOOZE_NOTIFICATION_ACTION ) ) {
				$snoozed        = $this->getSnoozedNotifications();
				$snoozed[ $id ] = time() + self::SNOOZE_PERIOD;

				update_option( self::SNOOZED_NOTIFICATIONS_OPTION_KEY, $snoozed, false );
			}

			wp_safe_redirect( wp_get_referer() ?: admin_url() );
			exit;
		} );
	}

	public function getNotificationSnoozeURL( $id ): string {
		return add_query_arg( array(
			'action'          => self::SNOOZE_NOTIFICATION_ACTION,
			'nonce'           => wp_create_nonce( self::SNOOZE_NOTIFICATION_ACTION ),
			'notification_id' => $id,
		), admin_url( 'admin-post.php' ) );
	}

	/**
	 * @return array<string, int> Notification id => timestamp the snooze ends.
	 */
	public function getSnoozedNotifications(): array {
		$snoozed = get_option( self::SNOOZED_NOTIFICATIONS_OPTION_KEY, array() );

		return is_array( $snoozed ) ? array_map( 'intval', $snoozed ) : array();
	}

	public function isNotificationSnoozed( $id ): bool {
		return ( $this->getSnoozedNotifications()[ $id ] ?? 0 ) > time();
	}
	
	public function handleClosingNotification() {
		add_action( 'admin_post_' . self::CLOSE_NOTIFICATION_ACTION, function () {
			$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : false;
			
			if ( ! wp_verify_nonce( $nonce, self::CLOSE_NOTIFICATION_ACTION ) ) {
				return wp_safe_redirect( wp_get_referer() );
			}
			
			$notificationID = isset( $_GET['notification_id'] ) ? sanitize_text_field( $_GET['notification_id'] ) : false;
			
			if ( ! $notificationID ) {
				return wp_safe_redirect( wp_get_referer() );
			}
			
			$this->markNotificationAsSeen( $notificationID );
			
			return wp_safe_redirect( wp_get_referer() );
		} );
	}
	
	public function initNotifications() {
		
		foreach ( $this->getNotifications() as $notification ) {
			if ( $notification->isActive() ) {
				
				add_action( 'admin_notices', function () use ( $notification ) {
					$this->getContainer()->getFileManager()->includeTemplate( $notification->getTemplate(), array(
						'notification' => $notification,
					) );
				}, 10 );
				
				return;
			}
		}
	}
	
	public function getNotificationCloseURL( $id ): string {
		return add_query_arg( array(
			'action'          => self::CLOSE_NOTIFICATION_ACTION,
			'nonce'           => wp_create_nonce( self::CLOSE_NOTIFICATION_ACTION ),
			'notification_id' => $id,
		), admin_url( 'admin-post.php' ) );
	}
	
	public function getSeenNotifications(): array {
		$seenNotifications = get_option( self::SEEN_NOTIFICATIONS_OPTION_KEY, array() );
		
		return is_array( $seenNotifications ) ? array_filter( $seenNotifications ) : array();
	}
	
	public function updateSeenNotifications( array $seenNotifications ) {
		update_option( self::SEEN_NOTIFICATIONS_OPTION_KEY, array_filter( $seenNotifications ) );
	}
	
	public function markNotificationAsSeen( $id ) {
		$seenNotifications = $this->getSeenNotifications();
		
		$seenNotifications[ $id ] = true;
		
		$this->updateSeenNotifications( $seenNotifications );
	}
	
	public function isNotificationSeen( $id ): bool {
		return array_key_exists( $id, $this->getSeenNotifications() );
	}
	
	/**
	 * Get notifications
	 *
	 * @return Notification[]
	 */
	public function getNotifications(): array {
		return array(
			new ActivationNotification(),
			new TwoMonthsUsingDiscount(),
			new YearUsingDiscount(),
			new BlackFriday2024(),
			new BlackFriday2025(),
			new BlackFriday2026(),
			new BlackFriday2027(),
			new BlackFriday2028(),
			new BlackFriday2029(),
			new BlackFriday2030(),
		);
	}
	
}
