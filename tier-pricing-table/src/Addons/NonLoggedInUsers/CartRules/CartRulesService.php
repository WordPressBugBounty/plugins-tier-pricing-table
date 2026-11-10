<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\TierPricingTablePlugin;

/**
 * Applies the cart rules on the storefront: payment methods and shipping rates limited to roles. The
 * minimum order lives in the order pricing rules since 8.2.2.
 */
class CartRulesService {

	public function __construct() {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'filterGateways' ) );
		add_filter( 'woocommerce_package_rates', array( $this, 'filterRates' ), 10, 2 );
	}

	protected function isGuest(): bool {
		return ! is_user_logged_in();
	}

	/**
	 * @return string[]
	 */
	protected function getRoles(): array {
		return $this->isGuest() ? array() : array_values( array_map( 'strval', TierPricingTablePlugin::getCurrentUserRoles() ) );
	}

	/**
	 * The admin edits orders with every method available; the rules are for the storefront.
	 */
	protected function isStorefront(): bool {
		return ! is_admin() || wp_doing_ajax();
	}

	/* --- payment and shipping methods ----------------------------------------------------------- */

	public function filterGateways( $gateways ) {
		if ( ! is_array( $gateways ) || ! $this->isStorefront() ) {
			return $gateways;
		}

		foreach ( array_keys( $gateways ) as $id ) {
			if ( ! CartRules::isAllowed( CartRulesSettings::getGatewayRoles( (string) $id ), $this->getRoles(), $this->isGuest() ) ) {
				unset( $gateways[ $id ] );
			}
		}

		return $gateways;
	}

	public function filterRates( $rates, $package ) {
		if ( ! is_array( $rates ) || ! $this->isStorefront() ) {
			return $rates;
		}

		foreach ( $rates as $rateId => $rate ) {
			$instanceId = is_object( $rate ) && method_exists( $rate, 'get_instance_id' ) ? (int) $rate->get_instance_id() : 0;

			if ( $instanceId && ! CartRules::isAllowed( CartRulesSettings::getShippingRoles( $instanceId ), $this->getRoles(), $this->isGuest() ) ) {
				unset( $rates[ $rateId ] );
			}
		}

		return $rates;
	}
}
