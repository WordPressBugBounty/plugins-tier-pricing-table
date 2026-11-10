<?php namespace TierPricingTable\Addons\TaxSettings\Overrides;

use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
use TierPricingTable\Addons\TaxSettings\API\TaxSettingsEndpoints;
use WC_Order;

class RoleBasedTaxOptions {
	
	public function __construct() {
		// Hook into options early
		add_filter( 'option_woocommerce_tax_display_shop', array( $this, 'overrideDisplayShop' ), 99 );
		add_filter( 'option_woocommerce_tax_display_cart', array( $this, 'overrideDisplayCart' ), 99 );
		add_filter( 'option_woocommerce_price_display_suffix', array( $this, 'overridePriceSuffix' ), 99 );
		add_filter( 'option_woocommerce_prices_include_tax', array( $this, 'overridePricesIncludeTax' ), 99 );
		
		// Tax class
		add_filter( 'woocommerce_product_get_tax_class', array( $this, 'overrideTaxClass' ), 99, 2 );
		add_filter( 'woocommerce_product_variation_get_tax_class', array( $this, 'overrideTaxClass' ), 99, 2 );
		
		// Exempt
		add_action( 'wp', array( $this, 'setTaxExempt' ), 99 );

		// Orders created in the admin: re-calculating with tiered pricing applies the customer's tax exemption.
		add_action( 'tiered_pricing_table/manual_orders/before_recalculation', array( $this, 'setOrderTaxExempt' ), 10, 2 );
	}
	
	protected function getActiveRoleSettings() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return null;
		}
		
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			return null;
		}
		
		$settings = get_option( TaxSettingsEndpoints::OPTION_KEY, array() );
		
		if ( empty( $settings ) ) {
			return null;
		}
		
		$roles = \TierPricingTable\TierPricingTablePlugin::getCurrentUserRoles();
		
		if ( empty( $roles ) ) {
			return null;
		}
		
		// Check roles
		foreach ( $roles as $role ) {
			if ( isset( $settings[ $role ] ) ) {
				return $settings[ $role ];
			}
		}
		
		return null;
	}
	
	public function overrideDisplayShop( $value ) {
		$roleSettings = $this->getActiveRoleSettings();
		if ( $roleSettings && ! empty( $roleSettings['display_shop'] ) && 'default' !== $roleSettings['display_shop'] ) {
			return $roleSettings['display_shop']; // 'incl' or 'excl'
		}
		
		return $value;
	}
	
	public function overrideDisplayCart( $value ) {
		$roleSettings = $this->getActiveRoleSettings();
		if ( $roleSettings && ! empty( $roleSettings['display_cart'] ) && 'default' !== $roleSettings['display_cart'] ) {
			return $roleSettings['display_cart']; // 'incl' or 'excl'
		}
		
		return $value;
	}
	
	public function overridePriceSuffix( $value ) {
		$roleSettings = $this->getActiveRoleSettings();
		if ( $roleSettings && isset( $roleSettings['price_suffix'] ) && '' !== $roleSettings['price_suffix'] && 'default' !== $roleSettings['price_suffix'] ) {
			return $roleSettings['price_suffix'];
		}
		
		return $value;
	}
	
	public function overridePricesIncludeTax( $value ) {
		$roleSettings = $this->getActiveRoleSettings();
		if ( $roleSettings && ! empty( $roleSettings['prices_include_tax'] ) && 'default' !== $roleSettings['prices_include_tax'] ) {
			return $roleSettings['prices_include_tax']; // 'yes' or 'no'
		}
		
		return $value;
	}
	
	public function overrideTaxClass( $taxClass, $product ) {
		
		$roleSettings = $this->getActiveRoleSettings();
		if ( $roleSettings && ! empty( $roleSettings['tax_class'] ) && 'default' !== $roleSettings['tax_class'] ) {
			return 'standard' === $roleSettings['tax_class'] ? '' : $roleSettings['tax_class'];
		}
		
		return $taxClass;
	}
	
	public function setTaxExempt() {
		if ( ! function_exists( 'WC' ) || ! isset( WC()->customer ) ) {
			return;
		}
		
		$roleSettings = $this->getActiveRoleSettings();
		
		if ( $roleSettings && isset( $roleSettings['tax_exempt'] ) && $roleSettings['tax_exempt'] ) {
			WC()->customer->set_is_vat_exempt( true );
		}
	}

	/**
	 * Marks an order created in the admin as tax exempt when its customer's role is tax exempt, the way the checkout
	 * does for the cart. WooCommerce then removes the taxes when it calculates the order. An exemption set here is
	 * lifted again when the order is re-calculated for a customer whose role is not exempt.
	 *
	 * @param  WC_Order  $order
	 * @param  int  $customerId  0 for a guest.
	 */
	public function setOrderTaxExempt( WC_Order $order, int $customerId ) {
		$settings = get_option( TaxSettingsEndpoints::OPTION_KEY, array() );
		$user     = $customerId ? get_userdata( $customerId ) : false;
		$roles    = $user ? (array) $user->roles : array( RoleBasedPriceManager::GUEST_ROLE );
		$exempt   = false;

		foreach ( $roles as $role ) {
			if ( isset( $settings[ $role ] ) ) {
				$exempt = ! empty( $settings[ $role ]['tax_exempt'] );
				break;
			}
		}

		if ( $exempt ) {
			$order->update_meta_data( 'is_vat_exempt', 'yes' );
			$order->update_meta_data( '_tpt_vat_exempt_by_role', 'yes' );
		} elseif ( 'yes' === $order->get_meta( '_tpt_vat_exempt_by_role' ) ) {
			$order->update_meta_data( 'is_vat_exempt', 'no' );
			$order->delete_meta_data( '_tpt_vat_exempt_by_role' );
		} else {
			return;
		}

		$order->save();
	}
}
