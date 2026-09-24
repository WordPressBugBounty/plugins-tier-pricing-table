<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;

class PaymentMethodsSubsection extends SubsectionAbstract {

	public function getTitle(): string {
		return __( 'Payment methods by role', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		if ( ! $this->getGateways() ) {
			return __( 'No payment method is enabled yet. Enable payment methods under WooCommerce → Settings → Payments, then choose here who may use each.', 'tier-pricing-table' );
		}

		return __( 'Who may use each enabled payment method, for example invoice payment for wholesale customers only. Leave a method empty to keep it available to everyone.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'payment-methods';
	}

	public function getSettings(): array {
		$settings = array();

		foreach ( $this->getGateways() as $id => $title ) {
			$settings[] = array(
				'title'   => $title,
				'id'      => CartRulesSettings::optionId( 'gateway_' . sanitize_key( $id ) ),
				'type'    => TPTCheckboxListOption::FIELD_TYPE,
				'display' => 'checkboxes',
				'options' => Roles::getOptions(),
				'default' => '',
			);
		}

		return $settings;
	}

	/**
	 * @return array<string, string> gateway id => title, enabled gateways only
	 */
	protected function getGateways(): array {
		$gateways = array();

		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return $gateways;
		}

		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( 'yes' === $gateway->enabled ) {
				$gateways[ (string) $gateway->id ] = wp_strip_all_tags( $gateway->get_method_title() ?: $gateway->get_title() );
			}
		}

		return $gateways;
	}
}
