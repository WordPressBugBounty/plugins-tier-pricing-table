<?php namespace TierPricingTable\Addons\CatalogPrices;

use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class CatalogPricesSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Catalog Price Format',
			'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Manage how tiered pricing appears in catalogs, loops, and widgets.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'catalog_prices';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'    => __( 'Enable tiered format', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'yes',
				'desc'     => __( 'Display the lowest tiered price or a price range instead of the standard product price.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Enable for variable products', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_for_variable',
				'type'     => TPTSwitchOption::FIELD_TYPE,
				'default'  => 'no',
				'desc'     => __( 'Apply the tiered format to variable products, using the lowest and highest prices across all variations.',
					'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Display format', 'tier-pricing-table' ),
				'id'       => Settings::SETTINGS_PREFIX . 'tiered_price_at_catalog_type',
				'type'     => TPTDisplayType::FIELD_TYPE,
				'options'  => [
					'lowest' => __( 'Show lowest price', 'tier-pricing-table' ),
					'range'  => __( 'Show range (lowest to highest)', 'tier-pricing-table' ),
				],
				'default'  => 'lowest',
				'desc'     => __( 'Choose whether to show only the lowest available price or the full price range.', 'tier-pricing-table' ),
				'desc_tip' => true,
			),
			array(
				'title'   => __( 'Lowest price prefix', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'lowest_prefix',
				'type'    => 'text',
				'default' => __( 'From', 'tier-pricing-table' ),
				'desc'    => __( 'Text displayed before the lowest price (e.g., <b>From $10.00</b>).',
					'tier-pricing-table' ),
			),
		);
	}
}
