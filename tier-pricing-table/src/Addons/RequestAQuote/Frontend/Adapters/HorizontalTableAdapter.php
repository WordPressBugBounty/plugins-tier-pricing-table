<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend\Adapters;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PricingRule;

class HorizontalTableAdapter extends AbstractLayoutAdapter {

	/**
	 * The entry of the horizontal table being rendered: found while its columns print, shown under it.
	 *
	 * @var array|null
	 */
	private $pending = null;

	public function registerHooks() {
		add_action( 'tiered_pricing_table/horizontal-table/after_columns', array( $this, 'render' ), 10, 2 );
		add_action( 'tiered_pricing_table/tiered_pricing/after', array( $this, 'renderUnderTable' ), 5, 0 );
	}

	/**
	 * The quote entry does not become one more column: the table scrolls sideways when it runs out of room,
	 * and the last column is the first to leave the screen. It is kept for the strip under the table.
	 */
	public function render( PricingRule $pricingRule, $settings = array() ) {
		$this->pending = null;

		$form = $this->getValidFormForIntegratedPosition( $pricingRule );
		if ( ! $form ) {
			return;
		}

		$this->pending = array(
			'form'      => $form,
			'productId' => $pricingRule->getProductId(),
		);
	}

	public function renderUnderTable() {
		if ( ! $this->pending ) {
			return;
		}

		$form      = $this->pending['form'];
		$productId = $this->pending['productId'];

		$this->pending = null;

		ServiceContainer::getInstance()->getFileManager()->includeTemplate(
			'frontend/integrated/horizontal-table.php',
			array(
				'form'       => $form,
				'productId'  => $productId,
				'buttonHtml' => $this->getQuoteButtonHtml( $form, $productId, 'button wp-element-button', self::ENTRY_BUTTON_STYLE, 'tpt-raq-table' ),
			),
			plugin_dir_path( dirname( __DIR__ ) ) . 'views/'
		);
	}
}
