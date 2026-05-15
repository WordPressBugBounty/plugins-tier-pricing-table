<?php namespace TierPricingTable\Integrations\Plugins\SEOPress;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;

class Settings extends SectionAbstract {
	
	public function getName(): string {
		return __( 'SEOPress', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'seopress';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title' => __( 'SEOPress', 'tier-pricing-table' ),
				'id'    => \TierPricingTable\Settings\Settings::SETTINGS_PREFIX . '_subsection_' . $this->getSlug(),
				'desc'  => __( 'Configure the integration with SEOPress plugin to use tiered pricing variables in your SEO metadata.',
					'tier-pricing-table' ),
				'type'  => 'title',
			),
			array(
				'title'   => __( 'Enable custom variables', 'tier-pricing-table' ),
				'id'      => \TierPricingTable\Settings\Settings::SETTINGS_PREFIX . 'seopress_enable_variables',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Enable you to use the %%lowest_price%% and %%price_range%% variables to display the lowest price and price range of products with tiered pricing in SEOPress metadata.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Enhance product schema with tiered pricing offers', 'tier-pricing-table' ),
				'id'      => \TierPricingTable\Settings\Settings::SETTINGS_PREFIX . 'seopress_enhance_schema',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'Enhance the product schema with tiered pricing offers. Adds an offer for each tier and the lowest price as the main offer.',
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