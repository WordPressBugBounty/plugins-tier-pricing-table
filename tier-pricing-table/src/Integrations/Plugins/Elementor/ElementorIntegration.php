<?php namespace TierPricingTable\Integrations\Plugins\Elementor;

use Elementor\Plugin;
use TierPricingTable\Integrations\Plugins\PluginIntegrationAbstract;

class ElementorIntegration extends PluginIntegrationAbstract {

	public function getTitle() {
		return __( 'Elementor', 'tier-pricing-table' );
	}

	public function getDescription() {
		return __( 'Provides the tiered pricing widget with a bunch of settings, such as: display type, active tier color, etc.', 'tier-pricing-table' );
	}

	public function getSlug() {
		return 'elementor';
	}

	

	public function getIconURL() {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/elementor-icon.svg' );
	}

	public function getAuthorURL() {
		return 'https://wordpress.org/plugins/elementor/';
	}

	public function run() {
		add_action( 'elementor/widgets/widgets_registered', function () {
			Plugin::instance()->widgets_manager->register( new ElementorWidget() );
		} );
	}
}
