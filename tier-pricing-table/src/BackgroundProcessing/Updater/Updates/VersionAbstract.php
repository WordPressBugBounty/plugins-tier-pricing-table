<?php namespace TierPricingTable\BackgroundProcessing\Updater\Updates;

use TierPricingTable\BackgroundProcessing\Updater\Updater;

abstract class VersionAbstract {

	protected $version;

	public function getVersion() {
		return $this->version;
	}

	protected function setCurrentDBVersion() {
		update_option( Updater::DB_OPTION, $this->getVersion() );
	}
}
