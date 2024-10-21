<?php namespace TierPricingTable\Admin\Notifications\Notifications;

use TierPricingTable\Core\ServiceContainerTrait;

/**
 * Class Notifications
 *
 * @package TierPricingTable\Admin\Notifications
 */
class BlackFriday2028 extends BlackFriday {
	
	public function getBlackFridayDate(): string {
		return '2028-11-24';
	}
}
