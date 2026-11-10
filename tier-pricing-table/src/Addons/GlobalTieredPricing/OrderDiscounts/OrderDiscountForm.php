<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainer;

/**
 * The order tiers in the rule editor: the discount type and the rows of "subtotal from" and "discount".
 */
class OrderDiscountForm {

	/**
	 * The discount type and the tier rows of a rule.
	 *
	 * @param  bool  $percentageAvailable  Whether the percentage type can be chosen (premium).
	 */
	public static function render( GlobalPricingRule $rule, bool $percentageAvailable = true ) {
		ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'addons/global-rules/order-discount-form.php', array(
			'tiers'                => $rule->getOrderTiers(),
			'discount_type'        => $rule->getOrderDiscountType(),
			'basis'                => $rule->getOrderBasis(),
			'percentage_available' => $percentageAvailable,
		) );
	}

	/**
	 * The posted rows as tiers (unsorted, unfiltered: the rule normalizes them).
	 *
	 * @param  array  $data  The request data.
	 *
	 * @return array<int, array{from: string, discount: string}>
	 */
	public static function tiersFromRequest( array $data ): array {
		$thresholds = isset( $data['tpt_order_tier_from'] ) ? (array) $data['tpt_order_tier_from'] : array();
		$discounts  = isset( $data['tpt_order_tier_discount'] ) ? (array) $data['tpt_order_tier_discount'] : array();
		$tiers      = array();

		foreach ( $thresholds as $index => $threshold ) {
			$tiers[] = array(
				'from'     => wc_format_decimal( sanitize_text_field( wp_unslash( (string) $threshold ) ) ),
				'discount' => wc_format_decimal( sanitize_text_field( wp_unslash( (string) ( $discounts[ $index ] ?? '' ) ) ) ),
			);
		}

		return $tiers;
	}
}
