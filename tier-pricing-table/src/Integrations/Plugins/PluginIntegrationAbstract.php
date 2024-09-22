<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTIntegrationOption;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Settings;

abstract class PluginIntegrationAbstract {

	use ServiceContainerTrait;

	abstract public function getTitle();

	abstract public function getDescription();

	abstract public function getSlug();

	abstract public function run();

	public function __construct() {

		add_filter( 'tiered_pricing_table/settings/integrations_settings', array(
			$this,
			'addToIntegrationsSettings',
		) );

		if ( $this->isEnabled() ) {
			$this->run();
		}
	}

	public function addToIntegrationsSettings( $integrations ) {
		$integrations[] = array(
			'title'                => $this->getTitle(),
			'id'                   => Settings::SETTINGS_PREFIX . '_integration_' . $this->getSlug(),
			'default'              => $this->isActiveByDefault() ? 'yes' : 'no',
			'desc'                 => $this->getDescription(),
			'type'                 => TPTIntegrationOption::FIELD_TYPE,
			'icon_url'             => $this->getIconURL(),
			'author_url'           => $this->getAuthorURL(),
			'integration_category' => $this->getIntegrationCategory(),
		);

		return $integrations;
	}

	public function getIconURL() {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/placeholder.png' );
	}

	public function getAuthorURL() {
		return null;
	}

	public function isEnabled() {
		return $this->getContainer()->getSettings()->get( '_integration_' . $this->getSlug(), 'yes' ) === 'yes';
	}

	protected function isActiveByDefault() {
		return true;
	}

	public function getIntegrationCategory() {
		return 'other';
	}
}
