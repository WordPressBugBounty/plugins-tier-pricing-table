<?php

namespace TierPricingTable\Addons\RequestAQuote;

use TierPricingTable\Addons\AbstractAddon;
class RequestAQuoteAddon extends AbstractAddon {
    public function getName() : string {
        return __( 'Request a quote', 'tier-pricing-table' );
    }

    public function getDescription() : string {
        return __( 'Quote forms on the product page. Requests arrive under WooCommerce → Quote Requests and convert to orders.', 'tier-pricing-table' );
    }

    public function getIcon() : string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>';
    }

    public function getSlug() : string {
        return 'request-a-quote';
    }

    public function getGroup() : string {
        return self::GROUP_WHOLESALE;
    }

    public function run() {
        // Always init settings and forms REST API so users can create forms
        new Settings\Settings();
        add_action( 'rest_api_init', function () {
            ( new API\FormsEndpoint() )->register_routes();
            ( new API\SettingsEndpoint() )->register_routes();
        } );
        // Conditionally init the rest if forms exist
        $forms = Models\RequestQuoteForm::getAll();
        if ( empty( $forms ) ) {
            return;
        }
        new Frontend\QuoteFormDisplay();
        new Frontend\API\SubmitQuoteEndpoint();
        new Integration\PricingRulesIntegration();
        // several products in one request: the list lives in the shopper's browser, the server prices and checks it
        new QuoteList\QuoteListDisplay();
        new QuoteList\QuoteListEndpoints();
    }

}
