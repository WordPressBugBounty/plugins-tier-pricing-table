<?php namespace TierPricingTable\Admin\Notifications\Notifications;

use TierPricingTable\Core\ServiceContainerTrait;

/**
 * Class Notifications
 *
 * @package TierPricingTable\Admin\Notifications
 */
class BlackFriday2030 extends BlackFriday {
	
	public function getBlackFridayDate(): string {
		return '2030-11-29';
	}
}
