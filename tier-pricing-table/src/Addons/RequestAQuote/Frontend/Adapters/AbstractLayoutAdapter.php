<?php namespace TierPricingTable\Addons\RequestAQuote\Frontend\Adapters;

use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteButtonRules;
use TierPricingTable\Addons\RequestAQuote\Frontend\QuoteFormDisplay;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PricingRule;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings;

abstract class AbstractLayoutAdapter {

	/**
	 * The button of a quote entry inside a pricing layout: compact, so the entry is about as tall as a
	 * price tier. Inline, because theme rules for buttons would win over a stylesheet.
	 */
	const ENTRY_BUTTON_STYLE = 'padding: 5px 12px; margin: 0; line-height: 1.4;';

	/**
	 * Register the necessary hooks for this adapter.
	 */
	abstract public function registerHooks();
	
	
	/**
	 * The product's pricing layout is the tooltip: its table only shows while the pointer is over an icon,
	 * which is no place for a button. The button then stands by itself under the price (AddToCartAdapter).
	 * Variations keep the button in their tooltip: nothing else is printed per variation.
	 */
	protected function isInTooltip( $productId ): bool {
		$product = $productId ? wc_get_product( $productId ) : null;

		if ( ! $product || $product->get_parent_id() ) {
			return false;
		}

		$layout = \TierPricingTable\Admin\ProductPage\TieredPricingTab::getProductTemplate( $product->get_id() );

		if ( 'default' === $layout || ! $layout ) {
			$layout = ServiceContainer::getInstance()->getSettings()->get( 'display_type', 'table' );
		}

		return 'tooltip' === $layout;
	}

	public function renderFormPrompt( PricingRule $pricingRule, $productId = null ) {
		if ( ! is_scalar( $productId ) ) {
			$productId = null;
		}

		if ( $this->isInTooltip( $pricingRule->getProductId() ) ) {
			return;
		}
		// Try to find the associated form ID for this pricing rule.
		// For now, we will assume the form ID is attached to the pricing rule object or product meta
		$formId = $this->getAssignedFormId( $pricingRule, $productId );
		
		if ( ! $formId ) {
			return;
		}
		
		$form = $this->getForm( $formId );
		
		if ( ! $form ) {
			return;
		}
		
		$displayPosition = $form->getDisplayPosition();
		
		// This method only handles "after" position
		if ( 'after' !== $displayPosition ) {
			return;
		}
		
		$overrides = $this->getEntityOverrides( $pricingRule, $productId );
		$form->setOverrides( $overrides );
		
		ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'frontend/integrated/prompt.php', array(
			'form'       => $form,
			'productId'  => $pricingRule->getProductId(),
			'buttonHtml' => $this->getQuoteButtonHtml( $form, $pricingRule->getProductId(), '', '' ),
		), plugin_dir_path( dirname( __DIR__ ) ) . 'views/' );
	}
	
	/**
	 * Renders the Request a Quote trigger button.
	 *
	 * @param  RequestQuoteForm  $form
	 * @param  int  $productId
	 * @param  string  $additionalClasses
	 * @param  string  $style
	 * @param  string  $idPrefix
	 * @param  bool  $isHidden  If true, the button is rendered visually hidden (useful for wrappers with onclick).
	 * @param  string  $forceStyle  'link' shows a text link whatever the form's button style (layouts made of text).
	 */
	protected function getQuoteButtonHtml(
		$form,
		$productId,
		string $additionalClasses = 'button alt wp-element-button',
		string $style = 'padding: 5px 10px; margin: 0;',
		string $idPrefix = 'tpt-raq-link',
		bool $isHidden = false,
		string $forceStyle = ''
	): string {
		// One modal per product family: variations share their parent's modal. Variation tables can be
		// loaded by AJAX after the page (and its wp_footer) has been rendered, so a modal keyed by the
		// variation ID would never reach the page. The JS resolves a variation's trigger to the parent
		// modal and writes the selected variation into the modal's product field.
		$parentId = QuoteFormDisplay::getModalProductId( $productId );

		QuoteFormDisplay::addModalToRender( $form, $parentId );
		$autoOpenQty = $form->getAutoOpenQuantity();
		
		if ( $isHidden ) {
			$style   = 'display:none;';
			$content = '';
		} else {
			$content = esc_html( $form->getPromptText() );

			if ( 'link' === $forceStyle ) {
				$additionalClasses = 'tpt-quote-button tpt-quote-button--link';
				$style             = '';
			} else {
				// the form's button style; forms saved before it keep the classes of this layout
				$additionalClasses = QuoteButtonRules::getClasses( $form, $additionalClasses );

				if ( 'link' === $form->getButtonStyle() ) {
					$style = '';
				}
			}
		}

		QuoteFormDisplay::enqueueButtonStyle();
		
		// a form that adds to the quote list hands its buttons to the list script
		$classes = implode( ' ', array_filter( array( 'tpt-request-quote-trigger', QuoteListSettings::getTriggerClass( $form ), trim( $additionalClasses ) ) ) );
		$idAttr  = $idPrefix . '-' . esc_attr( $productId );
		
		return ServiceContainer::getInstance()->getFileManager()->renderTemplate( 'frontend/integrated/quote-button.php',
			array(
				'form'        => $form,
				'productId'   => $productId,
				'parentId'    => $parentId,
				'classes'     => $classes,
				'idAttr'      => $idAttr,
				'autoOpenQty' => $autoOpenQty,
				'style'       => $style,
				'content'     => $content,
			), plugin_dir_path( dirname( __DIR__ ) ) . 'views/' );
	}
	
	/**
	 * Check if the given pricing rule has a valid form configured for the integrated position.
	 *
	 * @param  PricingRule  $pricingRule
	 *
	 * @return RequestQuoteForm|false
	 */
	protected function getValidFormForIntegratedPosition( PricingRule $pricingRule ) {
		$formId = $this->getAssignedFormId( $pricingRule, null );
		if ( ! $formId ) {
			return false;
		}
		
		$form = $this->getForm( $formId );
		if ( ! $form ) {
			return false;
		}
		
		if ( 'integrated' !== $form->getDisplayPosition() ) {
			return false;
		}
		
		$overrides = $this->getEntityOverrides( $pricingRule, null );
		$form->setOverrides( $overrides );
		
		return $form;
	}
	
	/**
	 * The form adds products to the quote list (its entries say "Add to quote" and show what is in the list).
	 */
	protected function addsToList( $form ): bool {
		return QuoteListSettings::formAddsToList( $form );
	}

	protected function getAssignedFormId( PricingRule $pricingRule ) {
		return $pricingRule->data['tier_pricing_table_quote_form_id'] ?? null;
	}
	
	protected function getEntityOverrides( PricingRule $pricingRule, $productId = null ) {
		return array(
			'auto_open_quantity'    => $pricingRule->data['tier_pricing_table_quote_auto_open_quantity'] ?? '',
			'integrated_label_text' => $pricingRule->data['tier_pricing_table_quote_integrated_label_text'] ?? '',
		);
	}
	
	/**
	 * Fetch a form by its ID.
	 *
	 * @param  string  $formId
	 *
	 * @return RequestQuoteForm|null
	 */
	protected function getForm( $formId ) {
		if ( ! tpt_fs()->can_use_premium_code__premium_only() && ! current_user_can( 'manage_options' ) ) {
			return null;
		}
		return RequestQuoteForm::get( $formId );
	}
}
