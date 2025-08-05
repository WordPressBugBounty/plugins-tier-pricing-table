jQuery(document).ready(function ($) {

    if (tieredPricingGlobalData === undefined) {
        return;
    }

    /**
     *
     * Tiered Pricing Table Tooltip
     *
     */
    const TieredPriceTableTooltip = function (tieredPricingTable) {

        this.tieredPricingTable = tieredPricingTable;

        this.productPageManager = new ProductPageManager(tieredPricingTable);
        this.dataProvider = new DataProvider(tieredPricingTable);

        this.init = function ($wrapper) {
            this.tieredPricingTable.init($wrapper);
            this.initTooltip();
        };

        this.$getPricingElement = function () {
            return this.tieredPricingTable.$wrapper.find('[data-tiered-pricing-table]');
        }

        this.$getPricingElementPart = function () {
            return this.tieredPricingTable.$wrapper.find('[data-tiered-pricing-table]').find('tbody').find('tr');
        }

        this.initTooltip = function () {
            const self = this.tieredPricingTable;

            if (this.tieredPricingTable.dataProvider.isTooltipBorder()) {
                self.$getPricingElement().css('border', '2px solid ' + this.tieredPricingTable.dataProvider.getActiveTierColor());
            }

            jQuery(document).uiTooltip({
                items: '.tiered-pricing-tooltip-icon',
                tooltipClass: "tiered-pricing-tooltip",
                content: function () {
                    self.productPageManager.$getQuantityField().trigger('change');
                    return self.$getPricingElement().clone();
                },
                hide: {
                    effect: "fade",
                },
                position: {
                    my: "center bottom-40",
                    at: "center bottom",
                    using: function (position) {
                        jQuery(this).css(position);
                    }
                },
                close: function (e, tooltip) {
                    tooltip.tooltip.innerHTML = '';
                }
            });
        };
    }

    /**
     *
     * Tiered Pricing Table
     *
     */
    const TieredPriceTable = function () {

        this.formatting = new Formatting(tieredPricingGlobalData.currencyOptions);
        this.productPageManager = new ProductPageManager(this);
        this.dataProvider = new DataProvider(this);

        this.init = function ($wrapper) {
            this.$wrapper = $wrapper;
            this.formatting.priceSuffix = this.dataProvider.getPriceSuffix();
            this.productPageManager.bindEvents();
        };

        this.$getPricingElement = function () {
            return this.$wrapper.find('[data-tiered-pricing-table]');
        }

        this.$getPricingElementPart = function () {
            return this.$wrapper.find('[data-tiered-pricing-table]').find('tbody').find('tr');
        }
    }

    /**
     *
     * Tiered Pricing Blocks
     *
     */
    const TieredPricingBlocks = function () {
        this.formatting = new Formatting(tieredPricingGlobalData.currencyOptions);
        this.productPageManager = new ProductPageManager(this);
        this.dataProvider = new DataProvider(this);

        this.init = function ($wrapper) {
            this.$wrapper = $wrapper;
            this.formatting.priceSuffix = this.dataProvider.getPriceSuffix();
            this.productPageManager.bindEvents();
        };

        this.$getPricingElement = function () {
            return this.$wrapper.find('.tiered-pricing-blocks');
        }

        this.$getPricingElementPart = function () {
            return this.$wrapper.find('.tiered-pricing-blocks').find('.tiered-pricing-block');
        }
    }

    /**
     *
     * Tiered Pricing Options
     *
     */
    const TieredPricingOptions = function () {

        this.formatting = new Formatting(tieredPricingGlobalData.currencyOptions);
        this.productPageManager = new ProductPageManager(this);
        this.dataProvider = new DataProvider(this);

        this.init = function ($wrapper) {
            this.$wrapper = $wrapper;
            this.formatting.priceSuffix = this.dataProvider.getPriceSuffix();
            this.productPageManager.bindEvents();

            if (this.dataProvider.isPremium()) {
                $(document).on('tiered_price_update', (this.updateTotals).bind(this));
            }
        };

        this.updateTotals = function (event, data) {
            let originalTotal = $('<del>');
            originalTotal.html(data.__instance.formatting.formatPrice(data.__instance.dataProvider.getPriceByTieredQuantity(false) * data.quantity, false));
            let discountedTotal = data.__instance.formatting.formatPrice(data.price * data.quantity, false);

            this.$getPricingElementPart().find('.tiered-pricing-option-total__original_total').html(originalTotal);
            this.$getPricingElementPart().find('.tiered-pricing-option-total__discounted_total').html(discountedTotal);
        }

        this.$getPricingElement = function () {
            return this.$wrapper.find('.tiered-pricing-options');
        }

        this.$getPricingElementPart = function () {
            return this.$wrapper.find('.tiered-pricing-option');
        }
    }

    /**
     *
     * Formatting
     *
     */
    const Formatting = function (currencyOptions) {

        this.currencyOptions = currencyOptions;

        this.priceSuffix = null;

        this.formatNumber = function (number, decimals, dec_point, thousands_sep) {

            let i, j, kw, kd, km;

            if (isNaN(decimals = Math.abs(decimals))) {
                decimals = this.currencyOptions.decimals;
            }
            if (dec_point === undefined) {
                dec_point = this.currencyOptions.decimal_separator;
            }
            if (thousands_sep === undefined) {
                thousands_sep = this.currencyOptions.thousand_separator;
            }

            i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

            if ((j = i.length) > 3) {
                j = j % 3;
            } else {
                j = 0;
            }

            km = (j ? i.substr(0, j) + thousands_sep : "");
            kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);

            kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");
            return km + kw + kd;
        };

        this.formatPrice = function (price, includeSuffix = true) {

            price = this.formatNumber(price, this.currencyOptions.decimals, this.currencyOptions.decimal_separator, this.currencyOptions.thousand_separator);

            let currency = '<span class="woocommerce-Price-currencySymbol">' + this.currencyOptions.currency_symbol + '</span>';
            let priceSuffixPart = includeSuffix ? ' %3$s ' : '';

            let template = '<span class="woocommerce-Price-amount amount">' + this.currencyOptions.price_format + priceSuffixPart + '</span>';

            return $('<textarea />').html(template.replace('%2$s', price).replace('%1$s', currency).replace('%3$s', this.priceSuffix)).text();
        };
    }

    /**
     *
     * Product Page Manager
     *
     */
    const ProductPageManager = function (tieredPricingInstance) {

        this.tieredPricingInstance = tieredPricingInstance;
        this.defaultProductPrice = null;

        this.bindEvents = function () {

            this.$getQuantityField().on('change input', (function (event) {
                this.updatePricingByQuantity($(event.target).val());
            }).bind(this));

            if (this.tieredPricingInstance.dataProvider.isClickableRows()) {
                $(document).on('click', tieredPricingInstance.$getPricingElementPart(), this.setQuantityByClick.bind(this));
            }

            // Handle variable products
            if (tieredPricingInstance.dataProvider.isVariableProduct()) {
                $(".single_variation_wrap").on("show_variation", this.loadVariationPricing.bind(this));

                $(document).on('reset_data', (function () {
                    tieredPricingInstance.$wrapper.html('');

                    if (this.tieredPricingInstance.dataProvider.isShowTotalPrice()) {
                        if ($('.product').find('.tiered-pricing-dynamic-price-wrapper').first().length && this.defaultProductPrice) {
                            this.updateProductPriceHTML(this.defaultProductPrice, true);
                        }
                    }
                }).bind(this));
            }
        }

        this.loadVariationPricing = function (event, variation) {

            $.post(document.location.origin + document.location.pathname + '?wc-ajax=get_pricing_table', {
                variation_id: variation['variation_id'],
                nonce: tieredPricingGlobalData.loadVariationTieredPricingNonce
            }, (function (response) {
                tieredPricingInstance.$wrapper.html(response);

                if (!response) {
                    this.updateProductPriceHTML(this.tieredPricingInstance.formatting.formatPrice(variation.display_price), true);
                    this.$getQuantityField().val(variation.min_qty);
                } else {
                    this.$getQuantityField().trigger('change');
                }

                if (this.tieredPricingInstance.dataProvider.getDisplayType() === 'tooltip' && this.tieredPricingInstance.dataProvider.isTooltipBorder()) {
                    this.$getPricingElement().css('border', '2px solid ' + this.tieredPricingInstance.dataProvider.getActiveTierColor());
                }

            }).bind(this));
        };

        this.setQuantityByClick = function (e) {

            const pricing = $(e.target).closest(tieredPricingInstance.$getPricingElementPart());

            if (pricing.length > 0) {
                const qty = parseInt(pricing.data('tiered-quantity'));

                if (qty > 0) {
                    this.$getQuantityField().val(qty);
                }
            }

            this.$getQuantityField().trigger('change');
        };

        this.$getQuantityField = function () {
            return $('form.cart').find('[name=quantity]');
        }

        this.updatePricingByQuantity = function (quantity) {

            this.resetPricingElementActive();

            quantity = Math.max(1, quantity);

            const pricing = tieredPricingInstance.dataProvider.getPricingByQuantity(quantity);

            if (!pricing) {
                return;
            }

            if (tieredPricingInstance.dataProvider.isShowTotalPrice()) {
                this.updateProductPriceHTML(tieredPricingInstance.formatting.formatPrice(pricing.price * quantity), true);

                if (this.tieredPricingInstance.dataProvider.isVariableProduct()) {
                    // Change single variation price if we use variable price to show the total
                    this.updateProductPriceHTML(pricing.priceHtml, false, $('.tiered-pricing-dynamic-price-wrapper').not('.tiered-pricing-dynamic-price-wrapper--variable').first());
                }
            } else {
                this.updateProductPriceHTML(pricing.priceHtml);
            }

            $(document).trigger('tiered_price_update', {
                price: pricing.price,
                quantity,
                price_excl_tax: pricing.price_excl_tax,
                __instance: tieredPricingInstance
            });

            this.setPricingElementActive(pricing.tieredQuantity);
        }

        this.updateProductPriceHTML = function (priceHtml, wipeDiscount = false, priceContainer = false) {

            wipeDiscount = wipeDiscount === undefined ? false : wipeDiscount;

            if (!priceContainer) {
                priceContainer = $('.tiered-pricing-dynamic-price-wrapper').first();

                // Allow 3rd-party plugin creating a function to specify the price container
                if (typeof tieredPriceTableGetProductPriceContainer != "undefined") {
                    priceContainer = tieredPriceTableGetProductPriceContainer();
                }
            }

            // Store default price before modification
            if (!this.defaultProductPrice) {
                this.defaultProductPrice = priceContainer.html();
            }

            if (wipeDiscount) {
                priceContainer.html(priceHtml);
                return;
            }

            if (priceContainer.children('ins').length > 0) {
                priceContainer.find('ins').html(priceHtml);
            } else {
                priceContainer.find('span:first').html(priceHtml);
            }
        };

        this.setPricingElementActive = function (quantity) {
            this.tieredPricingInstance.$getPricingElement().find('[data-tiered-quantity="' + quantity + '"]').addClass('tiered-pricing--active')
        }

        this.resetPricingElementActive = function () {
            this.tieredPricingInstance.$getPricingElement().find('[data-tiered-quantity]').removeClass('tiered-pricing--active')
        }

        setTimeout((function () {
            this.$getQuantityField().trigger('change');
        }).bind(this), 300);
    }

    /**
     *
     * Data Provider
     *
     */
    const DataProvider = function (tieredPricingInstance) {

        this.tieredPricingInstance = tieredPricingInstance;
        this.settings = null;

        this.getSettings = function () {

            if (!this.settings) {
                this.settings = tieredPricingInstance.$wrapper.data('settings');
            }

            return this.settings;
        }

        this.isVariableProduct = function () {
            return tieredPricingGlobalData.supportedVariableProductTypes.includes(this.getProductType());
        }

        this.getPricingByQuantity = function (quantity) {

            if (this.tieredPricingInstance.$getPricingElement().length > 0) {

                // Default pricing
                let pricing = {
                    price: this.getPriceByTieredQuantity(0),
                    price_excl_tax: this.getPriceByTieredQuantity(0, 'excl_tax'),
                    priceHtml: this.getPriceByTieredQuantity(0, 'html'),
                    tieredQuantity: this.getMinimum()
                };

                for (let tieredQuantity in this.getPricingRules()) {

                    tieredQuantity = parseInt(tieredQuantity);

                    if (quantity >= tieredQuantity) {

                        pricing.price = this.getPriceByTieredQuantity(tieredQuantity);
                        pricing.price_excl_tax = this.getPriceByTieredQuantity(tieredQuantity, 'excl_tax');
                        pricing.priceHtml = this.getPriceByTieredQuantity(tieredQuantity, 'html');
                        pricing.tieredQuantity = tieredQuantity;

                        break;
                    }
                }

                return pricing;
            }

            return false;
        }

        this.getPricingRules = function () {
            let rawPricingRules = JSON.parse(this.tieredPricingInstance.$getPricingElement().attr('data-price-rules'));

            return Object.keys(rawPricingRules).sort((a, b) => a - b).reverse().reduce(
                (obj, key) => {
                    // Prevent from sorting numeric keys automatically
                    obj[key + ' '] = rawPricingRules[key];
                    return obj;
                },
                {}
            );
        }

        this.getMinimum = function () {
            let min = this.tieredPricingInstance.$getPricingElement().data('minimum');

            min = min ? parseInt(min) : 1;

            return min;
        };

        this.getProductName = function () {
            return this.tieredPricingInstance.$getPricingElement().data('product-name');
        }

        this.getPriceSuffix = function () {
            // Allow external plugins modifying suffix
            if (typeof tieredPriceTableGetProductPriceSuffix !== "undefined") {
                return tieredPriceTableGetProductPriceSuffix();
            }

            return this.tieredPricingInstance.$wrapper.data('product-price-suffix');
        }

        this.getProductType = function () {
            return this.tieredPricingInstance.$wrapper.data('product-type');
        }

        this.getPriceByTieredQuantity = function (tieredQuantity, type = 'regular') {

            if (!tieredQuantity) {
                tieredQuantity = this.getMinimum();
            }

            const pricingElement = this.tieredPricingInstance.$getPricingElement().find('[data-tiered-quantity="' + tieredQuantity + '"]');

            if (pricingElement.length < 1) {
                return false;
            }

            if (type === 'html') {
                return pricingElement.find('[data-price-html]').html();
            }

            if (type === 'excl_tax') {
                return parseFloat(pricingElement.data('tiered-price-exclude-taxes'));
            }

            return parseFloat(pricingElement.data('tiered-price'));
        }

        this.isClickableRows = function () {
            return this.isPremium() && this.getSettings().clickable_rows;
        }

        this.isShowTotalPrice = function () {
            return this.isPremium() && this.getSettings().show_total_price;
        }

        this.isTooltipBorder = function () {
            return this.getSettings().tooltip_border;
        }

        this.getDisplayType = function () {
            return this.getSettings().display_type;
        }

        this.getActiveTierColor = function () {
            return this.getSettings().active_tier_color;
        }

        this.isPremium = function () {
            return tieredPricingGlobalData.isPremium === 'yes'
        }
    }

    /**
     *
     * Initialization
     *
     */
    document.tieredPricingInstances = [];

    jQuery.each(jQuery('.tpt__tiered-pricing'), function (index, wrapper) {

        let displayType = jQuery(wrapper).data('display-type');
        let tieredPricingInstance;

        if (displayType === 'options') {
            tieredPricingInstance = new TieredPricingOptions();
        } else if (displayType === 'blocks') {
            tieredPricingInstance = new TieredPricingBlocks();
        } else if (displayType === 'table' || displayType === 'tooltip') {
            tieredPricingInstance = new TieredPriceTable();

            if (displayType === 'tooltip') {
                jQuery.widget.bridge('uiTooltip', $.ui.tooltip);
                tieredPricingInstance = new TieredPriceTableTooltip(tieredPricingInstance);
            }
        }

        // Break if no display handler
        if (!tieredPricingInstance) {
            return;
        }

        document.tieredPricingInstances.push(tieredPricingInstance);

        setTimeout(function () {
            tieredPricingInstance.init($(wrapper));
        }, 150);
    });

});

/**
 *
 * Summary Table
 *
 */
(function ($) {

    $(document).on('tiered_price_update', function (event, data) {
        $('[data-tier-pricing-table-summary]').removeClass('tier-pricing-summary-table--hidden');

        $('[data-tier-pricing-table-summary-product-qty]').text(data.__instance.formatting.formatNumber(data.quantity, 0));
        $('[data-tier-pricing-table-summary-product-price]').html(data.__instance.formatting.formatPrice(data.price, false));
        $('[data-tier-pricing-table-summary-total]').html(data.__instance.formatting.formatPrice(data.price * data.quantity, false));
        $('[data-tier-pricing-table-summary-product-name]').html(data.__instance.dataProvider.getProductName());
    });

    $(document).on('reset_data', function () {
        $('[data-tier-pricing-table-summary]').addClass('tier-pricing-summary-table--hidden');
    });

    $(document).on('found_variation', function () {
        $('[data-tier-pricing-table-summary]').addClass('tier-pricing-summary-table--hidden');
    });

})(jQuery);