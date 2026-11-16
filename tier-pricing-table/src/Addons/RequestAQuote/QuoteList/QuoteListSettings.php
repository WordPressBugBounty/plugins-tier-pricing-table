<?php namespace TierPricingTable\Addons\RequestAQuote\QuoteList;

use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The quote list settings, kept with the other Request a Quote settings (Settings in the forms screen).
 */
class QuoteListSettings {

	public const OPTION = 'tier_pricing_table_quote_global_settings';

	/**
	 * The most lines one list may hold.
	 */
	public const MAX_LINES = 100;

	/**
	 * Set when the Request a Quote module runs: other modules (the Quick Order Form) offer the list only then.
	 */
	public static bool $running = false;

	protected static function get( string $key, $default = null ) {
		$settings = get_option( self::OPTION, array() );

		return is_array( $settings ) && array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Every form's quote buttons can add to the list (a setting in each form); the list works once at least one
	 * form does and the list has its page.
	 */
	public static function isEnabled(): bool {
		return self::getPageId() && self::getListForms() && self::getForm();
	}

	/**
	 * The list works for this visitor: a form adds to it and it has a page, and on the free plan only as an
	 * administrator's preview (like the quote buttons).
	 */
	public static function isAvailable(): bool {
		if ( ! self::$running ) {
			return false;
		}

		if ( ! tpt_fs()->can_use_premium_code__premium_only() && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return self::isEnabled();
	}

	/**
	 * The quote buttons of this form add to the list for this visitor (other forms keep opening their form).
	 */
	public static function formAddsToList( ?RequestQuoteForm $form ): bool {
		return $form && $form->addsToQuoteList() && self::isAvailable();
	}

	/**
	 * The class that hands a quote button to the list script instead of the form script.
	 */
	public static function getTriggerClass( ?RequestQuoteForm $form ): string {
		return self::formAddsToList( $form ) ? 'tpt-quote-to-list' : '';
	}

	/**
	 * The forms whose quote buttons add to the list.
	 *
	 * @return RequestQuoteForm[]
	 */
	public static function getListForms(): array {
		return array_values( array_filter( RequestQuoteForm::getAll(), function ( RequestQuoteForm $form ) {
			return $form->addsToQuoteList();
		} ) );
	}

	/**
	 * The published page that holds the list and its form, 0 when there is none.
	 */
	public static function getPageId(): int {
		$pageId = (int) self::get( 'quote_list_page_id', 0 );

		return $pageId > 0 && 'publish' === get_post_status( $pageId ) ? $pageId : 0;
	}

	public static function getPageUrl(): string {
		$pageId = self::getPageId();

		return $pageId ? (string) get_permalink( $pageId ) : '';
	}

	/**
	 * The form the list page shows: the chosen one, else the first form that adds to the list.
	 */
	public static function getForm(): ?RequestQuoteForm {
		$formId = (string) self::get( 'quote_list_form_id', '' );
		$form   = '' !== $formId ? RequestQuoteForm::get( $formId ) : null;

		if ( $form ) {
			return $form;
		}

		$forms = self::getListForms();

		return $forms ? $forms[0] : null;
	}

	/**
	 * Show estimated prices in the list (never to visitors the store hides prices from).
	 */
	public static function showPrices(): bool {
		return (bool) self::get( 'quote_list_show_prices', true );
	}

	/**
	 * "Add to quote" buttons on shop and category pages, for products with a quote form.
	 */
	public static function showOnShopPages(): bool {
		return (bool) self::get( 'quote_list_shop_buttons', true );
	}

	/**
	 * A "Request a quote for this cart" button on the cart page.
	 */
	public static function showOnCart(): bool {
		return (bool) self::get( 'quote_list_cart_button', true );
	}

	/**
	 * The floating "Quote list" button, shown while the list has products (never on the list page).
	 */
	public static function showFloatingButton(): bool {
		return (bool) self::get( 'quote_list_floating_button', true );
	}
}
