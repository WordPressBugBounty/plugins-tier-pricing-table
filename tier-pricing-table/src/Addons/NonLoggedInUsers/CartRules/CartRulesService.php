<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\TierPricingTablePlugin;
use WP_Error;

/**
 * Applies the cart rules on the storefront: minimum order per role in the cart and at checkout (classic
 * pages and blocks), payment methods and shipping rates limited to roles.
 */
class CartRulesService {

	public function __construct() {
		add_action( 'woocommerce_check_cart_items', array( $this, 'checkMinimums' ) );
		add_action( 'woocommerce_store_api_cart_errors', array( $this, 'addStoreApiErrors' ), 10, 2 );
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

	/* --- minimum order --------------------------------------------------------------------------- */

	/**
	 * @return string[] error messages, empty when the cart passes
	 */
	public function getMinimumErrors( $cart ): array {
		if ( ! $cart || $cart->is_empty() ) {
			return array();
		}

		$picked = CartRules::pickMinimums( CartRulesSettings::getMinimums(), $this->getRoles(), $this->isGuest() );

		if ( $picked['amount'] <= 0 && $picked['quantity'] <= 0 ) {
			return array();
		}

		$subtotal = (float) $cart->get_subtotal();

		if ( $cart->display_prices_including_tax() ) {
			$subtotal += (float) $cart->get_subtotal_tax();
		}

		$count  = (int) $cart->get_cart_contents_count();
		$missed = CartRules::missedMinimums( $picked, $subtotal, $count );
		$errors = array();

		if ( isset( $missed['amount'] ) ) {
			$errors['amount'] = str_replace(
				array( '{minimum}', '{subtotal}' ),
				array( $this->plainPrice( $missed['amount'] ), $this->plainPrice( $subtotal ) ),
				CartRulesSettings::getAmountMessage()
			);
		}

		if ( isset( $missed['quantity'] ) ) {
			$errors['quantity'] = str_replace(
				array( '{minimum}', '{count}' ),
				array( number_format_i18n( $missed['quantity'] ), number_format_i18n( $count ) ),
				CartRulesSettings::getQuantityMessage()
			);
		}

		return $errors;
	}

	protected function plainPrice( float $amount ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	public function checkMinimums() {
		foreach ( $this->getMinimumErrors( WC()->cart ) as $message ) {
			if ( ! wc_has_notice( $message, 'error' ) ) {
				wc_add_notice( $message, 'error' );
			}
		}
	}

	/**
	 * @param  WP_Error  $errors
	 * @param  \WC_Cart  $cart
	 */
	public function addStoreApiErrors( $errors, $cart ) {
		if ( ! $errors instanceof WP_Error ) {
			return;
		}

		foreach ( $this->getMinimumErrors( $cart ) as $key => $message ) {
			$errors->add( 'tpt_minimum_' . $key, $message );
		}
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
