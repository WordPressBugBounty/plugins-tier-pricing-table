<?php namespace TierPricingTable\Admin\Notifications\Notifications;

use TierPricingTable\Core\ServiceContainerTrait;

/**
 * Class Notifications
 *
 * @package TierPricingTable\Admin\Notifications
 */
class BlackFriday2026 extends BlackFriday {
	
	public function getBlackFridayDate(): string {
		return '2024-11-27';
	}
}