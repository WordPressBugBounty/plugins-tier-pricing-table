<?php

namespace TierPricingTable\Admin\Notifications\Notifications;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\TierPricingTablePlugin;
/**
 * Class Notifications
 *
 * @package TierPricingTable\Admin\Notifications
 */
class TwoMonthsUsingDiscount extends Notification {
    use ServiceContainerTrait;
    /**
     * One month of use is enough to have an opinion, and most upgrade decisions are made early.
     * The class and the stored id keep their original names: the id is what remembers a dismissal.
     */
    public function getMonthsNumberToUsePluginToSeeNotification() : int {
        return 1;
    }

    public function getId() : string {
        return 'two-months-using-discount';
    }

    public function getTemplate() : string {
        return 'admin/notifications/feedback-discount.php';
    }

    public function passedRequirements() : bool {
        if ( !$this->isPluginPage() ) {
            return false;
        }
        $activationDate = TierPricingTablePlugin::getPluginActivationDate();
        if ( !$activationDate ) {
            return false;
        }
        // If activation date is more than {number} months ago
        return time() - $activationDate > MONTH_IN_SECONDS * $this->getMonthsNumberToUsePluginToSeeNotification();
    }

    public function isActive() : bool {
        return $this->passedRequirements() && !$this->isSeen() && !$this->isSnoozed();
    }

}
