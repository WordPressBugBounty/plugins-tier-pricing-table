<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

class ShippingMethodsSubsection extends SubsectionAbstract {

	public function getTitle(): string {
		return __( 'Shipping methods by role', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		if ( ! $this->getMethods() ) {
			return __( 'No shipping method is set up yet. Add shipping zones and methods under WooCommerce → Settings → Shipping, then choose here who may use each.', 'tier-pricing-table' );
		}

		return __( 'Who may use each shipping method of each zone, for example free shipping for retail customers only. Leave a method empty to keep it available to everyone.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'shipping-methods';
	}

	public function getSettings(): array {
		$settings = array();

		foreach ( $this->getMethods() as $instanceId => $title ) {
			$settings[] = array(
				'title'   => $title,
				'id'      => CartRulesSettings::optionId( 'shipping_' . $instanceId ),
				'type'    => TPTCheckboxListOption::FIELD_TYPE,
				'display' => 'checkboxes',
				'options' => Roles::getOptions(),
				'default' => '',
			);
		}

		return $settings;
	}

	/**
	 * @return array<int, string> instance id => "Zone: Method", enabled methods only
	 */
	protected function getMethods(): array {
		if ( ! class_exists( WC_Shipping_Zones::class ) ) {
			return array();
		}

		$zones = array();

		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			$zones[] = new WC_Shipping_Zone( (int) $zone['id'] );
		}

		$zones[] = new WC_Shipping_Zone( 0 ); // locations not covered by the other zones

		$methods = array();

		foreach ( $zones as $zone ) {
			foreach ( $zone->get_shipping_methods( true ) as $method ) {
				$methods[ (int) $method->get_instance_id() ] = wp_strip_all_tags( $zone->get_zone_name() . ': ' . $method->get_title() );
			}
		}

		return $methods;
	}
}
