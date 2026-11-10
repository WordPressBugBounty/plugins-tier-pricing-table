<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * The "Order rules settings" section: the store-wide options of the order rules in a customizer with a
 * live preview. Left out of the settings navigation; reached from the order rule editor and the rules
 * list, where the options matter.
 */
class OrderRulesSettingsSection extends SectionAbstract {

	const SLUG = 'order-rules';

	public function getName(): string {
		return __( 'Cart rules settings', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return self::SLUG;
	}

	public function isHiddenFromNavigation(): bool {
		return true;
	}

	/**
	 * The section's URL, with a card to open: "order-discount", "minimum-order" or "free-shipping-bar".
	 */
	public static function url( string $card = '' ): string {
		$url = admin_url( 'admin.php?page=wc-settings&tab=' . Settings::SETTINGS_PAGE . '&section=' . self::SLUG );

		return '' !== $card ? $url . '#' . $card : $url;
	}

	public function getSettings(): array {
		$definitions = OrderDiscountSettings::getOptionDefinitions();
		$rulesUrl    = admin_url( 'edit.php?post_type=' . GlobalTieredPricingCPT::SLUG );

		$settings = array(
			array(
				'title' => __( 'Cart rules settings', 'tier-pricing-table' ),
				'type'  => 'title',
				'id'    => Settings::SETTINGS_PREFIX . 'order_rules_section',
				'desc'  => sprintf(
					/* translators: %s: link to the pricing rules list */
					__( 'How the Order pricing rules read and look in the cart, at checkout, in the mini cart and on product pages: the discount behaviour and the next tier message, the minimum order messages, and where and how the free shipping bar shows. %s', 'tier-pricing-table' ),
					'<a href="' . esc_url( $rulesUrl ) . '">' . esc_html__( 'Back to the pricing rules', 'tier-pricing-table' ) . '</a>'
				),
			),
			array(
				'title' => __( 'Cart rules settings', 'tier-pricing-table' ),
				'id'    => $definitions['nudge_message']['id'],
				'type'  => OrderRulesCustomizer::FIELD_TYPE,
			),
		);

		// the other options are saved from the customizer's hidden inputs; rendered by nothing
		foreach ( $definitions as $key => $option ) {
			if ( 'nudge_message' === $key ) {
				continue;
			}

			$settings[] = array(
				'id'      => $option['id'],
				'type'    => 'tpt_saved_only',
				'default' => $option['default'],
			);
		}

		$settings[] = array( 'type' => 'sectionend', 'id' => Settings::SETTINGS_PREFIX . 'order_rules_section' );

		return $settings;
	}
}
