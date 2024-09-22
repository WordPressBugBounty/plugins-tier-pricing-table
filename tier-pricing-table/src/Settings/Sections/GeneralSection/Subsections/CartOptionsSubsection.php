<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CartOptionsSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Tiered pricing in cart', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'How tiered pricing will be shown in the cart.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'cart';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'                => __( 'Show cart item price as a discount', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'show_discount_in_cart',
				'desc' => __( 'Show the crossed-out original price with a discounted price beside it. For example: ',
						'tier-pricing-table' ) . ' <b><del>$10.00</del> <ins>$8.00</ins><b>',
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'yes',
			),
			array(
				'title'                => __( 'Show original cart item subtotal as a discount', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart',
				'desc' => __( 'Show the crossed-out subtotal based on original price with a discounted subtotal beside it.',
						'tier-pricing-table' ),
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'yes',
			),
		);
	}
	
	
	/**
	 * When cart item has a tiered price, show its subtotal as a discount with the original subtotal crossed out.
	 *
	 * @return bool
	 */
	public static function showSubtotalInCartAsDiscount(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart', 'yes' ) === 'yes';
	}
	
	/**
	 * Do global pricing rules have a higher priority than product level rules.
	 *
	 * @return bool
	 */
	public static function globalRulesOverrideProductLevelRules(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'override_prices_by_global_rules', 'no' ) === 'yes';
	}
}
