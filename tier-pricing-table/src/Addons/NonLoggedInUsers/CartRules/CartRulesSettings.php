<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\Settings;

class CartRulesSettings {

	const PREFIX = Settings::SETTINGS_PREFIX . 'cart_rules_';

	public function __construct() {
		new RoleMinimumsOption();

		// WooCommerce keeps computed shipping rates in the session until its shipping version changes;
		// a changed rule must show on the next cart load, not on the next cart change
		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $hook ) {
			add_action( $hook, array( $this, 'onOptionChanged' ) );
		}
	}

	public function onOptionChanged( $option ) {
		if ( 0 === strpos( (string) $option, self::PREFIX ) && class_exists( 'WC_Cache_Helper' ) ) {
			\WC_Cache_Helper::get_transient_version( 'shipping', true );
		}
	}

	public static function optionId( string $key ): string {
		return self::PREFIX . $key;
	}

	protected static function get( string $key, $default = null ) {
		return get_option( self::optionId( $key ), $default );
	}

	public static function isEnabled(): bool {
		return 'yes' === self::get( 'enabled', 'no' );
	}

	/**
	 * @return array<string, array{amount: float, quantity: int}>
	 */
	public static function getMinimums(): array {
		return CartRules::normalizeMinimums( self::get( 'minimums', '' ) );
	}

	public static function getAmountMessage(): string {
		return (string) self::get( 'amount_message', self::defaultAmountMessage() );
	}

	public static function getQuantityMessage(): string {
		return (string) self::get( 'quantity_message', self::defaultQuantityMessage() );
	}

	public static function defaultAmountMessage(): string {
		return __( 'A minimum order of {minimum} is required to check out. Your order subtotal is {subtotal}.', 'tier-pricing-table' );
	}

	public static function defaultQuantityMessage(): string {
		return __( 'A minimum of {minimum} items is required to check out. Your cart has {count}.', 'tier-pricing-table' );
	}

	/**
	 * @return string[] roles that may use the payment method; empty for everyone
	 */
	public static function getGatewayRoles( string $gatewayId ): array {
		return TPTCheckboxListOption::toArray( self::get( 'gateway_' . sanitize_key( $gatewayId ), '' ) );
	}

	/**
	 * @return string[] roles that may use the shipping method instance; empty for everyone
	 */
	public static function getShippingRoles( int $instanceId ): array {
		return TPTCheckboxListOption::toArray( self::get( 'shipping_' . $instanceId, '' ) );
	}
}
