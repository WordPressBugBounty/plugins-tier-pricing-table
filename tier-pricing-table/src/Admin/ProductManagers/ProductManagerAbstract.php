<?php namespace TierPricingTable\Admin\ProductManagers;

use TierPricingTable\Core\ServiceContainerTrait;

/**
 * Class ProductManagerAbstract
 *
 * @package TierPricingTable\Admin\ProductManagers
 */
abstract class ProductManagerAbstract {

	use ServiceContainerTrait;

	/**
	 * Product Manager constructor.
	 *
	 * Register menu items and handlers
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Register manager hooks
	 */
	abstract protected function hooks();
}
