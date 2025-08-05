jQuery(document).ready(function ($) {

    const TieredPricingSettingsPage = function () {
        this.rows = [];

        this.init = function (rows) {
            this.rows = rows;
            this.renderSettings();
        }

        this.getRowById = function (id) {
            return this.rows.find((row) => row.id = id);
        }

        this.renderSettings = function () {
            this.rows.forEach(row => row.showBeShown() ? row.show() : row.hide());
        }
    }

    const TieredPricingSettingsRow = function (id, dependencies = {}) {
        this.prefix = 'tier_pricing_table_';
        this.id = id;
        this.settingsPage = null;
        this.dependencies = dependencies;

        this.init = function (settingsPage) {
            this.settingsPage = settingsPage;
            this.$getRow(false).on('change', () => this.settingsPage.renderSettings());
        }

        this.show = function () {
            this.$getRow().closest('tr').show();
        }

        this.hide = function () {
            this.$getRow().closest('tr').hide();
        }

        this.isChecked = function () {
            return this.$getRow().is(':checked');
        }

        this.isValueEqual = function (value) {

            return this.$getRow().val() === value;
        }

        this.showBeShown = function () {

            let pass = true;

            for (const [rowID, value] of Object.entries(this.dependencies)) {
                const row = this.settingsPage.getRowById(rowID);

                if (!row) {
                    continue;
                }

                if (value === ':checked') {
                    pass = pass && row.isChecked();
                    continue;
                }


                if (value === ':unchecked') {
                    pass = pass && !row.isChecked();
                    continue;
                }

                // there is multiple values to pass
                if (typeof value === 'string') {
                    pass = pass && row.isValueEqual(value);
                    continue;
                }

                // there is multiple values to pass
                if (value.constructor === Array) {

                    let _pass = false;

                    value.forEach((_value) => {
                        _pass = _pass || row.isValueEqual(_value);
                    });
                    pass = pass && _pass;
                }
            }
            return pass;
        }

        this.$getRow = function (any = true) {
            let input = $('[name=' + this.prefix + this.id + ']');

            if (any && input.is(':radio')) {
                input = input.filter(':checked');
            }

            return input;
        }
    }

    // Product Page Option
    const display = new TieredPricingSettingsRow('display');
    const displayTypeRow = new TieredPricingSettingsRow('display_type', {
        'display': ':checked'
    });
    const quantityType = new TieredPricingSettingsRow('quantity_type', {
        'display': ':checked'
    });
    const tooltipColor = new TieredPricingSettingsRow('tooltip_color', {
        'display': ':checked',
        'display_type': 'tooltip'
    });
    const tooltipSize = new TieredPricingSettingsRow('tooltip_size', {
        'display': ':checked',
        'display_type': 'tooltip'
    });
    const tooltipBorder = new TieredPricingSettingsRow('tooltip_border', {
        'display': ':checked',
        'display_type': 'tooltip'
    });
    const pricingTitle = new TieredPricingSettingsRow('table_title', {
        'display': ':checked',
        'display_type': ['blocks', 'table', 'options']
    });
    const pricingPlace = new TieredPricingSettingsRow('position_hook', {
        'display': ':checked',
        'display_type': ['blocks', 'table', 'options']
    });
    const activeTierColor = new TieredPricingSettingsRow('selected_quantity_color', {
        'display': ':checked',
    });
    const quantityColumnTitle = new TieredPricingSettingsRow('head_quantity_text', {
        'display': ':checked',
        'display_type': ['tooltip', 'table']
    });
    const priceColumnTitle = new TieredPricingSettingsRow('head_price_text', {
        'display': ':checked',
        'display_type': ['tooltip', 'table']
    });
    const showDiscountColumn = new TieredPricingSettingsRow('show_discount_column', {
        'display': ':checked',
        'display_type': ['blocks', 'table', 'tooltip']
    });
    const discountColumnTitle = new TieredPricingSettingsRow('head_discount_text', {
        'display': ':checked',
        'show_discount_column': ':checked',
        'display_type': ['tooltip', 'table']
    });
    const showTotalPrice = new TieredPricingSettingsRow('show_total_price', {
        'tiered_price_at_product_page': ':unchecked',
    });

    const OPTIONS_optionText = new TieredPricingSettingsRow('options_option_text', {
        'display': ':checked',
        'display_type': 'options'
    });

    const OPTIONS_showDefaultOption = new TieredPricingSettingsRow('options_show_default_option', {
        'display': ':checked',
        'display_type': 'options'
    });

    const OPTIONS_defaultOptionText = new TieredPricingSettingsRow('options_default_option_text', {
        'display': ':checked',
        'options_show_default_option': ':checked',
        'display_type': 'options',
    });

    const OPTIONS_showOriginalProductPrice = new TieredPricingSettingsRow('options_show_original_product_price', {
        'display': ':checked',
        'display_type': 'options'
    });

    const OPTIONS_showTotal = new TieredPricingSettingsRow('options_show_total', {
        'display': ':checked',
        'display_type': 'options'
    });

    // Catalog prices
    const catalogPrices = new TieredPricingSettingsRow('tiered_price_at_catalog');
    const catalogPricesForVariableProducts = new TieredPricingSettingsRow('tiered_price_at_catalog_for_variable', {
        'tiered_price_at_catalog': ':checked'
    });
    const catalogPricesForVariableProductsCache = new TieredPricingSettingsRow('tiered_price_at_catalog_cache_for_variable', {
        'tiered_price_at_catalog': ':checked',
        'tiered_price_at_catalog_for_variable': ':checked'
    });
    const catalogPricesForProductPage = new TieredPricingSettingsRow('tiered_price_at_product_page', {
        'tiered_price_at_catalog': ':checked',
        'show_total_price': ':unchecked',
    });
    const catalogPricesType = new TieredPricingSettingsRow('tiered_price_at_catalog_type', {
        'tiered_price_at_catalog': ':checked'
    });
    const lowestPricePrefix = new TieredPricingSettingsRow('lowest_prefix', {
        'tiered_price_at_catalog': ':checked',
        'tiered_price_at_catalog_type': 'lowest'
    });

    // Cart
    const cartUpsellEnabled = new TieredPricingSettingsRow('cart_upsell_enabled');
    const cartUpsellTemplate = new TieredPricingSettingsRow('cart_upsell_template', {
        'cart_upsell_enabled': ':checked'
    });
    const cartUpsellColor = new TieredPricingSettingsRow('cart_upsell_color', {
        'cart_upsell_enabled': ':checked'
    });

    // Summary block
    const displaySummary = new TieredPricingSettingsRow('display_summary');
    const summaryTitle = new TieredPricingSettingsRow('summary_title', {
        'display_summary': ':checked'
    });
    const summaryType = new TieredPricingSettingsRow('summary_type', {
        'display_summary': ':checked'
    });
    const summaryTotalLabel = new TieredPricingSettingsRow('summary_total_label', {
        'display_summary': ':checked',
        'summary_type': 'inline',
    });
    const summaryEachLabel = new TieredPricingSettingsRow('summary_each_label', {
        'display_summary': ':checked',
        'summary_type': 'inline',
    });
    const summaryPosition = new TieredPricingSettingsRow('summary_position_hook', {
        'display_summary': ':checked',
    });

    let rows = [
        // product page
        display, displayTypeRow, quantityType, tooltipColor, tooltipSize, tooltipBorder, pricingTitle, pricingPlace,
        activeTierColor, quantityColumnTitle, priceColumnTitle, showDiscountColumn, discountColumnTitle, showTotalPrice,
        OPTIONS_optionText, OPTIONS_showDefaultOption, OPTIONS_defaultOptionText, OPTIONS_showOriginalProductPrice, OPTIONS_showTotal,
        // Catalog prices
        catalogPrices, catalogPricesForVariableProducts, catalogPricesForVariableProductsCache, catalogPricesForProductPage,
        catalogPricesType, lowestPricePrefix,

        //Cart
        cartUpsellEnabled, cartUpsellTemplate, cartUpsellColor,

        // Summary block
        displaySummary, summaryTitle, summaryType, summaryTotalLabel, summaryEachLabel, summaryPosition
    ];

    const productPageSettingPage = new TieredPricingSettingsPage();

    rows.forEach(row => row.init(productPageSettingPage));

    productPageSettingPage.init(rows);
});
