<?php

namespace TierPricingTable\Admin\Notifications\Notifications;

/**
 * The Black Friday offer: a coupon for the premium version, shown on the free plan from three days
 * before Black Friday to the Monday after, on the plugin's own screens and the dashboard. One
 * notification per year, so a dismissal lasts for that year's offer only.
 */
abstract class BlackFriday extends Notification {
    const DAYS_BEFORE = 3;

    const DAYS_AFTER = 4;

    public function getTemplate() : string {
        return 'admin/notifications/black-friday.php';
    }

    public function getId() : string {
        return 'black-friday-' . $this->getBlackFridayDate();
    }

    /**
     * @return string Y-m-d
     */
    public abstract function getBlackFridayDate() : string;

    /**
     * The coupon for this year's offer.
     *
     * @return array{code: string, percent: int}
     */
    public function getCoupon() : array {
        $coupon = apply_filters( 'tiered_pricing_table/black_friday/coupon', array(
            'code'    => 'BF25OFF',
            'percent' => 25,
        ), $this->getBlackFridayDate() );
        return array(
            'code'    => (string) ($coupon['code'] ?? 'BF25OFF'),
            'percent' => (int) ($coupon['percent'] ?? 25),
        );
    }

    /**
     * The last day of the offer, the Monday after Black Friday.
     */
    public function getLastDay() : int {
        return strtotime( $this->getBlackFridayDate() . ' + ' . (self::DAYS_AFTER - 1) . ' days' );
    }

    public function isBlackFriday() : bool {
        $blackFriday = $this->getBlackFridayDate();
        $start = strtotime( "{$blackFriday} - " . self::DAYS_BEFORE . ' days' );
        $end = strtotime( "{$blackFriday} + " . self::DAYS_AFTER . ' days' );
        return time() > $start && time() < $end;
    }

    protected function isDashboard() : bool {
        return 'index.php' === ($GLOBALS['pagenow'] ?? '');
    }

    public function passedRequirements() : bool {
        if ( !$this->isPluginPage() && !$this->isDashboard() ) {
            return false;
        }
        return $this->isBlackFriday();
    }

    public function isActive() : bool {
        return $this->passedRequirements() && !$this->isSeen();
    }

}
