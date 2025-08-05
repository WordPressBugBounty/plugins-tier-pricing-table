<?php namespace TierPricingTable\BackgroundProcessing\Updater;

use TierPricingTable\TierPricingTablePlugin;

class Updater {

	const DB_OPTION = 'tiered_price_table_version';

	public function checkForUpdates() {
		return $this->compare( TierPricingTablePlugin::DB_VERSION );
	}

	/**
	 * Compare plugin versions
	 *
	 * @param $version
	 *
	 * @return mixed
	 */
	private function compare( $version ) {
		$dbVersion = get_option( self::DB_OPTION, '1.0.0' );

		return version_compare( $dbVersion, $version, '<' );
	}

	public function update() {
		foreach ( $this->getUpdates() as $version => $callback ) {
			if ( $this->compare( $version ) ) {
				call_user_func( $callback );
			}
		}
	}

	public function getUpdates() {
		return array();
	}
}
