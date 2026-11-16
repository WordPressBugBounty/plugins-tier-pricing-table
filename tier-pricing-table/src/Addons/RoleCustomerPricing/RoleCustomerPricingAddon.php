<?php namespace TierPricingTable\Addons\RoleCustomerPricing;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Settings\Settings;

/**
 * "Role and customer prices": the one Modules switch for the product's Role & Customer Prices section. The role
 * part (RoleBasedPricingAddon) and the customer part (UserBasedPricingAddon) stay separate add-ons without
 * cards of their own; both follow this switch.
 *
 * Stores that switched off only one of the two former modules keep that part off until they change this
 * switch: the first change of it (off, then on again) drops the former per-part switches.
 */
class RoleCustomerPricingAddon extends AbstractAddon {

	const SLUG = 'role-customer-prices';

	const ROLE_PART     = 'role-based-rules';
	const CUSTOMER_PART = 'user-based-rules';

	public function __construct() {
		parent::__construct();

		$option = self::getOptionName( self::SLUG );

		// the first save of the switch on the Modules screen adds the option: only a value other than what the
		// screen showed is a change
		add_action( 'add_option_' . $option, function ( $name, $value ) {
			if ( $value !== ( self::getLegacyDefault() ? 'yes' : 'no' ) ) {
				self::forgetLegacyParts();
			}
		}, 10, 2 );

		add_action( 'update_option_' . $option, function () {
			self::forgetLegacyParts();
		} );
	}

	public function getName(): string {
		return __( 'Role and customer prices', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Give a role, such as Wholesale, or a single customer their own price, tiers and quantity limits in the Role & Customer Prices section of a product.', 'tier-pricing-table' );
	}

	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
	}

	public function getSlug(): string {
		return self::SLUG;
	}

	public function getGroup(): string {
		return self::GROUP_PRICING;
	}

	/**
	 * Before the switch is first saved, it is on when either former module was on.
	 */
	protected function isActiveByDefault(): bool {
		return self::getLegacyDefault();
	}

	/**
	 * The parts do the work.
	 */
	public function run() {
	}

	/**
	 * Whether a part (ROLE_PART or CUSTOMER_PART) runs: the switch is on and the part was not switched off on its
	 * own before the two modules became one.
	 */
	public static function isPartEnabled( string $part ): bool {
		return self::isAddonEnabled( self::SLUG, self::getLegacyDefault() ) && 'no' !== get_option( self::getOptionName( $part ) );
	}

	protected static function getLegacyDefault(): bool {
		return 'no' !== get_option( self::getOptionName( self::ROLE_PART ) ) || 'no' !== get_option( self::getOptionName( self::CUSTOMER_PART ) );
	}

	protected static function forgetLegacyParts() {
		delete_option( self::getOptionName( self::ROLE_PART ) );
		delete_option( self::getOptionName( self::CUSTOMER_PART ) );
	}

	protected static function getOptionName( string $slug ): string {
		return Settings::SETTINGS_PREFIX . '_addon_' . $slug;
	}
}
