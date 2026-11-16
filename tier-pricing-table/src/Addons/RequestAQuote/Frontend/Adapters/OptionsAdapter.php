<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend\Adapters;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PricingRule;

class OptionsAdapter extends AbstractLayoutAdapter {

	public function registerHooks() {
		add_action( 'tiered_pricing_table/options/after_options', array( $this, 'renderFormPrompt' ), 10, 1 );
		add_action( 'tiered_pricing_table/options/options', array( $this, 'render' ), 10, 2 );
	}

	public function render( PricingRule $pricingRule, $settings = array() ) {
		$form = $this->getValidFormForIntegratedPosition( $pricingRule );
		if ( ! $form ) {
			return;
		}

		$optionsStyle = $settings['options_style'] ?? 'default';

		$productId = $pricingRule->getProductId();
		ServiceContainer::getInstance()->getFileManager()->includeTemplate(
			'frontend/integrated/options.php',
			array(
				'form' => $form,
				'productId' => $productId,
				'optionsStyle' => $optionsStyle,
				'addsToList' => $this->addsToList( $form ),
				'buttonHtml' => $this->getQuoteButtonHtml( $form, $productId, 'button alt wp-element-button', self::ENTRY_BUTTON_STYLE )
			),
			plugin_dir_path( dirname( __DIR__ ) ) . 'views/'
		);
	}
}
