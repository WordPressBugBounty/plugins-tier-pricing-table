<?php namespace TierPricingTable\Integrations\Plugins\MixMatch;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;

class Settings extends SectionAbstract {

	const PRICE_CHILD_ITEMS_KEY = 'mix_match_price_child_items';

	public function getName(): string {
		return 'Mix & Match';
	}

	public function getSlug(): string {
		return 'mix_match';
	}

	public function getSettings(): array {
		return array(
			array(
				'title' => 'Mix & Match',
				'id'    => \TierPricingTable\Settings\Settings::SETTINGS_PREFIX . '_subsection_' . $this->getSlug(),
				'desc'  => __( 'Configure how tiered pricing works with WooCommerce Mix and Match Products.',
					'tier-pricing-table' ),
				'type'  => 'title',
			),
			array(
				'title'                => __( 'Apply tiered pricing inside containers', 'tier-pricing-table' ),
				'id'                   => \TierPricingTable\Settings\Settings::SETTINGS_PREFIX . self::PRICE_CHILD_ITEMS_KEY,
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
				'extended_description' => __( 'Apply tiered pricing rules to the products inside a Mix and Match container. Works only for containers with per-item pricing: their contents are priced individually, so tiered pricing can discount them. Containers with a static price always keep the price set by the Mix and Match plugin.',
					'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
			),
		);
	}

	public function isIntegration(): bool {
		return true;
	}
}
