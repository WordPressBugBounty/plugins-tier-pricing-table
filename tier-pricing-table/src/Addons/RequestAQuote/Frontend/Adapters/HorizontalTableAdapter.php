<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend\Adapters;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PricingRule;

class HorizontalTableAdapter extends AbstractLayoutAdapter {

	public function registerHooks() {
		add_action( 'tiered_pricing_table/horizontal-table/after_columns', array( $this, 'render' ), 10, 2 );
	}

	public function render( PricingRule $pricingRule, $settings = array() ) {
		$form = $this->getValidFormForIntegratedPosition( $pricingRule );
		if ( ! $form ) {
			return;
		}

		$productId = $pricingRule->getProductId();

		$hasQty      = ! empty( $settings['quantity_column_title'] ) || ! isset( $settings['quantity_column_title'] );
		$hasDiscount = ! empty( $settings['discount_column_title'] ); // the layout draws the discount row from its header only
		$hasPrice    = ! empty( $settings['price_column_title'] ) || ! isset( $settings['price_column_title'] );

		// one empty cell per custom column, so the column keeps the grid's row count
		ob_start();
		do_action( 'tiered_pricing_table/tiered_pricing/header_columns', $pricingRule );
		$customColumns = substr_count( (string) ob_get_clean(), '<th' );

		ServiceContainer::getInstance()->getFileManager()->includeTemplate(
			'frontend/integrated/horizontal-table.php',
			array(
				'form' => $form,
				'productId' => $productId,
				'hasQty' => $hasQty,
				'hasDiscount' => $hasDiscount,
				'hasPrice' => $hasPrice,
				'customColumns' => $customColumns,
				'buttonHtml' => $this->getQuoteButtonHtml( $form, $productId, 'button wp-element-button', 'padding: 5px 10px;margin:0', 'tpt-raq-table' )
			),
			plugin_dir_path( dirname( __DIR__ ) ) . 'views/'
		);
	}
}
