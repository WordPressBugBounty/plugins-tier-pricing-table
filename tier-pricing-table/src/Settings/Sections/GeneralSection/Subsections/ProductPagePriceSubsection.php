<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

class ProductPagePriceSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Product Page Pricing', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Manage how tiered prices are displayed and behave on single product pages.',
			'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'product_page_price';
	}
	
	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Price display format', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'product_page_price_format',
				'type'    => TPTDisplayType::FIELD_TYPE,
				'options' => array(
					'same_as_catalog' => __( 'Match catalog display (price range or lowest price)', 'tier-pricing-table' ),
					'custom'          => __( 'Dynamic', 'tier-pricing-table' ),
				),
				'default' => ServiceContainer::getInstance()->getSettings()->get( 'tiered_price_at_product_page',
					'no' ) === 'yes' ? 'same_as_catalog' : 'custom',
			),
			array(
				'title'   => __( 'Enable dynamic price updates', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'update_price_on_product_page',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Automatically update the main product price when a user selects a different quantity.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Display tiered price as discount', 'tier-pricing-table' ),
				'id'      => Settings::SETTINGS_PREFIX . 'show_tiered_price_as_discount',
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Cross out the original price and show the discounted tiered price next to it.',
					'tier-pricing-table' ),
			),
			array(
				'title'             => __( 'Display total price', 'tier-pricing-table' ),
				'id'                => Settings::SETTINGS_PREFIX . 'show_total_price',
				'type'              => TPTSwitchOption::FIELD_TYPE,
				'default'           => 'no',
				'desc'              => __( 'Show the total calculated price based on the selected quantity and active tier.',
					'tier-pricing-table' ),
				'custom_attributes' => [ 'data-tiered-pricing-premium-option' => true ],
			),
		);
	}
	
	public static function getFormatPriceType() {
		
		$settings = ServiceContainer::getInstance()->getSettings();
		
		$default = $settings->get( 'tiered_price_at_product_page', 'no' ) === 'yes' ? 'same_as_catalog' : 'custom';
		
		return $settings->get( 'product_page_price_format', $default );
	}
}
