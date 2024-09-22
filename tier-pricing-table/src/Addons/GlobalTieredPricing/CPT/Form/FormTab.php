<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;

abstract class FormTab {

	use ServiceContainerTrait;

	/**
	 * Form
	 *
	 * @var Form
	 */
	protected $form;

	public function __construct( Form $form ) {
		$this->form = $form;
	}

	abstract public function getId();

	abstract public function getTitle();

	abstract public function getDescription();

	abstract public function render( GlobalPricingRule $pricingRule );
}
