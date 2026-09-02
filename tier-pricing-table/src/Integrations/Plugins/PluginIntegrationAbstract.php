<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTIntegrationOption;
use TierPricingTable\Settings\Settings;

abstract class PluginIntegrationAbstract {
	
	use ServiceContainerTrait;
	
	abstract public function getTitle(): string;
	
	abstract public function getDescription(): string;
	
	abstract public function getSlug(): string;
	
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
			'official'             => $this->isOfficial(),
			'action_links'         => $this->getActionLinks(),
			'meta_pills'           => $this->getMetaPills(),
		);
		
		return $integrations;
	}
	
	/**
	 * Whether the integrated plugin is made by U2Code (the makers of this plugin).
	 * Official integrations get a badge on the integrations screen and are listed first in their category.
	 */
	public function isOfficial(): bool {
		return false;
	}
	
	/**
	 * Optional action links rendered under the integration description
	 * (e.g. install / activate the integrated plugin).
	 *
	 * @return array<int, array{url: string, label: string, target?: string}>
	 */
	public function getActionLinks(): array {
		return array();
	}
	
	/**
	 * Optional short feature highlights rendered as pills under the integration title.
	 *
	 * @return array<int, string>
	 */
	public function getMetaPills(): array {
		return array();
	}
	
	public function getIconURL(): ?string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/placeholder.png' );
	}
	
	public function getAuthorURL(): ?string {
		return null;
	}
	
	public function isEnabled(): bool {
		
		$isEnabledByDefault = $this->isActiveByDefault() ? 'yes' : 'no';
		
		return $this->getContainer()->getSettings()->get( '_integration_' . $this->getSlug(),
				$isEnabledByDefault ) === 'yes';
	}
	
	protected function isActiveByDefault(): bool {
		return true;
	}
	
	public function getIntegrationCategory(): string {
		return 'other';
	}
}
