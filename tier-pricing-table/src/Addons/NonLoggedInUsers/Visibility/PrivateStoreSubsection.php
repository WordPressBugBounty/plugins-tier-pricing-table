<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility;

use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;

/**
 * The "Private store" group of the Wholesale tab.
 */
class PrivateStoreSubsection extends SubsectionAbstract {

	public function getTitle(): string {
		return __( 'Private store', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Close the store to visitors, or show parts of the catalog to selected roles only. Both are off by default.', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'private-store';
	}

	public function getSettings(): array {
		return array(
			array(
				'title'   => __( 'Closed store', 'tier-pricing-table' ),
				'id'      => VisibilitySettings::optionId( 'closed_store' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'Visitors who are not logged in are sent to the login page. My Account, the wholesale registration page and the pages below stay open. Exclude the store from page caching for visitors.', 'tier-pricing-table' ),
			),
			array(
				'title'       => __( 'Pages visitors may open', 'tier-pricing-table' ),
				'id'          => VisibilitySettings::optionId( 'closed_store_pages' ),
				'type'        => TPTCheckboxListOption::FIELD_TYPE,
				'display'     => 'select',
				'options'     => $this->getPageOptions(),
				'default'     => '',
				'placeholder' => __( 'Select pages…', 'tier-pricing-table' ),
				'desc'        => __( 'For example the home page, About, Contact, Terms and Privacy.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Catalog visibility by role', 'tier-pricing-table' ),
				'id'      => VisibilitySettings::optionId( 'enabled' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'Adds a "Who can see" rule to every product (Tiered Pricing tab, Additional Options) and every product category (category screen): everyone, only the selected roles, or everyone except the selected roles; guests count as a role. Use it for wholesale-only or retail-only products and categories. Hidden products leave the shop, search, related lists, menus, the sitemap and the Store API and cannot be bought. Shop managers always see everything.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Hidden products and categories', 'tier-pricing-table' ),
				'id'      => VisibilitySettings::optionId( 'hidden_redirect' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Send a visitor who opens a hidden product or category to the login page. Off: everyone who may not see it gets a not-found page.', 'tier-pricing-table' ),
			),
		);
	}

	protected function getPageOptions(): array {
		$options = array();

		foreach ( get_pages( array( 'post_status' => 'publish', 'number' => 500 ) ) as $page ) {
			$options[ (string) $page->ID ] = $page->post_title ?: '#' . $page->ID;
		}

		return $options;
	}
}
