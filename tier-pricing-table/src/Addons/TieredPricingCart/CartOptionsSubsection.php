<?php namespace TierPricingTable\Addons\TieredPricingCart;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CartOptionsSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Cart Page Pricing', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Manage how tiered pricing and discounts appear on the cart page.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'cart';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Show original item price crossed out', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_discount_in_cart',
				'desc'    => __( 'Show a crossed-out regular price next to the discounted tiered price in the cart. For example: ',
						'tier-pricing-table' ) . ' <b><del>$10.00</del> <ins>$8.00</ins><b>',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Show original subtotal crossed out', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart',
				'desc'    => __( 'Show a crossed-out regular subtotal next to the discounted subtotal in the cart.',
					'tier-pricing-table' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Cross out regular price instead of sale price', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'consider_sale_price_as_discount_in_cart',
				'desc'    => __( 'When crossing out a price, always display the regular price rather than a sale price.',
					'tier-pricing-table' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
			),
			array(
				'title'   => __( 'Show a total savings row', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'cart_total_savings',
				'desc'    => __( 'A row in the cart totals and in the classic checkout\'s order review with the amount the tiered prices take off the cart. Not available with the Checkout block.', 'tier-pricing-table' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
			),
			array(
				'title'   => __( 'Total savings row label', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'cart_total_savings_label',
				'type'    => 'text',
				'default' => __( 'Total savings', 'tier-pricing-table' ),
				'css'     => 'width: 20em;',
			),
		);
	}

	public static function showTotalSavings(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'cart_total_savings', 'no' ) === 'yes';
	}

	public static function getTotalSavingsLabel(): string {
		$label = (string) get_option( Settings::SETTINGS_PREFIX . 'cart_total_savings_label', '' );

		return '' !== trim( $label ) ? $label : __( 'Total savings', 'tier-pricing-table' );
	}
	
	
	/**
	 * When a cart item has a tiered price, show its subtotal as a discount with the original subtotal crossed out.
	 *
	 * @return bool
	 */
	public static function showSubtotalInCartAsDiscount(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'show_subtotal_as_discount_in_cart', 'yes' ) === 'yes';
	}
	
	/**
	 * Do global pricing rules have a higher priority than product level rules?
	 *
	 * @return bool
	 */
	public static function globalRulesOverrideProductLevelRules(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'override_prices_by_global_rules', 'no' ) === 'yes';
	}
}
