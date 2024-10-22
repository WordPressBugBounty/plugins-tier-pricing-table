=== Tiered Pricing Table for WooCommerce ===

Contributors: bycrik, freemius
Tags: woocommerce, tiered pricing, dynamic price, price, wholesale
Requires at least: 4.2
Tested up to: 6.6.5
Requires PHP: 7.2
Stable tag: 5.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The plugin enables you to set quantity-based discounts and display these prices on the product page using various templates.

== Description ==

**All-in-one and most complete dynamic pricing plugin for WooCommerce.**

[youtube https://www.youtube.com/watch?v=fQKv3Z9DtE4]

[**Documentation**](https://tiered-pricing.com/documentation/user/) | [**Plugin Demo**](https://demo.tiered-pricing.com/) | [**Visit plugin website**](https://tiered-pricing.com/) | [**Contact us**](https://tiered-pricing.com/contact-us/)

**Tiered Pricing Table for WooCommerce** lets you easily set flexible pricing rules based on product quantity. You can apply custom pricing to individual products or categories, all users, specific user roles, or customer accounts.

The plugin allows you to fine-tune your pricing to meet the needs of your diverse customer base, whether you're a retailer, wholesaler, or service provider.

💡 **Key features**:

✅ **Quantity-based pricing (Volume pricing)**
Set different prices for different quantities of products.

✅ **Role-based pricing**
Set different prices for user roles or specific customers, including quantity-based prices.

✅ **Minimum \ Maximum \ Quantity step**
Control the minimum, maximum, and quantity step of a product that users can purchase of a specific product.

✅ **Multiple display templates for pricing on the product page \ product catalog**
 You can show the tiered prices via:
➖ **Table**
➖ **Blocks**
➖ **Options**
➖ **Dropdown**
➖ **Horizontal Table**
➖ **Plain text**
➖ **Tooltip**
*See screenshots for examples*
-

✅ **Import\Export (WP All Import supported)**
Import tiered prices in bulk, including role-based and minimum order quantity rules.

✅ **Format your prices on the product catalog with discounts in mind**
Show the lowest price or range from the lowest to the highest price.

The clean interface and powerful functionality allow you to make any custom pricing without a headache.

**All features**:

*  Show tiered pricing at different places on the product page and product catalog
*  Show saving amount to users (difference between original and discounted price)
*  Various customization (titles, colors, positions, and many others)
*  Built-in cache to provide the best performance
*  Import\Export
*  REST API
*  Debug mode
*  Many other little useful features

**Premium features**:

*   Percentage quantity-based discounts
*   Role-based pricing (including base prices and min/max order quantity rules)
*   Custom columns for pricing table
*   Hide prices and prevent purchasing for non-logged-in users
*   Min\max order quantity control per product or category
*   Cart upsells (motivates users to purchase more to get a discount)
*   Totals on the product page
*   Clickable tiered pricing options
*   Show the lowest price or a range of prices instead of default product price
*   Show the tiered price in the cart as a discount
*   Show the total price (multiplied by selected quantity) on the product page instead of the default price

🔌 **Integration with 3rd-party plugins**:

✔️  **WP All Import**
✔️  **Elementor**
✔️  **WPML**
✔️  **WPML Multicurrency**
✔️  **WooCommerce Product Add-ons**
✔️  **Aelia Multicurrency**
✔️  **Yith Request a Quote**
✔️  **Request a Quote by Addify**
✔️  **Product Bundles for WooCommerce**
✔️  **WOOCS** (WooCommerce Currency Switcher by FOX)
✔️  **WCCS** (WooCommerce Currency Switcher by WP Experts)
✔️  **WCPA** (WooCommerce Custom Product Addons)
✔️  **Product Fields** (Product Addons by StudioWombat)
✔️  **WooCommerce Deposits**
✔️  **Mix&Match for WooCommerce**

**Check out our site to get more information about the [Tiered Pricing Table for WooCommerce](https://tiered-pricing.com/)**

Feel free to **[Contact us](https://tiered-pricing.com/contact-us/)** if you have any questions.

Set up a **[demo](https://demo.tiered-pricing.com/)** to see how the plugin's features work.

== Screenshots ==

1. Tiered Pricing on the product page
2. Set up Tiered Pricing
3. Global pricing rules
4. Tiered Pricing in the cart
4. Tiered Pricing in the product catalog

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/tier-price-table` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the WooCommerce->Settings Name screen to configure the plugin

After installing the plugin set up your own settings

== Frequently Asked Questions ==

= What does the import format look like? =

"quantity:price,quantity:price"

For example:
"10:20,20:18" - in this case 20.00$ at 10 pieces, $18.00 at 20 pieces or more.
The exact format is used for the percentage-based rules:
"quantity:discount,quantity:discount"

Please note that you must use a dot as a decimal separator because a comma separates the pricing rules.

You can change the rules separator (in case you use a comma as a decimal separator) using the "tiered_pricing_table/rules_separator" hook.

For example, the following code will change the separator to "&":

add_filter('tiered_pricing_table/rules_separator', function(){
   return '&'
});

= Can I show the pricing table/pricing blocks/pricing options using a shortcode? =

The plugin includes the [tiered-pricing-table] shortcode that can be customized with various attributes.

= Can I show the pricing table/pricing blocks/pricing options via Elementor? =

Yes! Look for the "Tiered Pricing Table" widget.

= Can I show the pricing table/pricing blocks/pricing options via Gutenberg block? =

Yes! Look for the "Tiered Pricing" block.

= Can I apply tiered pricing for manual (admin-made) orders? =

Yes!
Each order has the "recalculate with tiered pricing" button, which recalculates the cost according to the tiered pricing rules.

== Changelog ==

= 5.1.3 [2024-10-22] =
* Fix: Global pricing rules issue.

= 5.1.2 [2024-10-20] =
* Update: Update Freemius SDK to the latest version of 2.9.0.

= 5.1.1 [2024-10-20] =
* Enhance: Minor improvements.

= 5.1.0 [2024-10-19] =
* New: Priority options for global pricing rules.
* New: Redesign global pricing rules form.
* Fix: Maximum order quantity in the cart.
* Enhance: Additional tips over the plugin.

= 5.0.4 [2024-10-06] =
* New: Two additional layouts for blocks.
* Enhance: Additional tips over the plugin.
* Enhance: Minor improvements.

= 5.0.3 [2024-09-27] =
* Fix: Multiple quantity fields on the product page.
* Fix: WOOCS integration.
* Update: Freemius updated to the latest version of 2.8.1.
* Enhance: Custom columns form updates.
* Enhance: Global pricing rules: make the form responsive.
* Enhance: Minor improvements.

= 5.0.2 [2024-09-10] =
* Update: Minor fixes and improvements.

= 5.0.1 [2024-08-19] =
* Update: Minor fixes and improvements.

= 5.0.0 [2024-08-14] =
* New: Show tiered pricing block in the product catalog.
* New: Compatibility with the new WooCommerce react-based product editor.
* New: New API for the tiered pricing fields.
* New: Integration with Addify Request a Quote plugin.
* Update: Freemius updated to the latest version of 2.7.3.
* Update: Frontend script updated.
* Update: Hew hooks added.
* Update: Removed the legacy hooks support.
* Update: WooCommerce & WordPress compatibility.
* Fix: Plaintext template variables issue.
* Fix: Custom columns: total column always shows the price with taxes.
* Fix: Options template: do not show "total" label in the free version.

= 4.3.3 [2024-07-08] =
* Fix: Do not show crossed-out total in "options" template if there are no discounts.

= 4.3.2 [2024-07-02] =
* Fix: Wombat product addons (free) integration.
* Fix: Price formatting for some 3rd-party plugins that use AJAX to update loop.
* Update: Freemius updated to the latest version of 2.7.3.
* Update: Minor improvements.
* Update: WooCommerce & WordPress compatibility.

= 4.3.1 [2024-06-17] =
* Fix: Error when the plugin is deactivated and items with tiered pricing are in the cart.

= 4.3.0 [2024-06-14] =
* New: Non-logged-in users options: hide prices and prevent purchasing.
* New: Prevent premium version be used without a valid license.
* Fix: tiered pricing in the cart&checkout blocks.
* Fix: types warnings on PHP 8.0 or above.
* Fix: Wombat product addons integration.
* Update: WooCommerce & WordPress compatibility.

= 4.2.4 [2024-05-10] =
* New: integration with Global Pricing rules for woocommerce: do not apply tiered pricing on the free items.
* Fix: tiered pricing in the cart&checkout blocks.
* Fix: types warnings on PHP 8.0 or above.
* Fix: maximum and group of quantity for variations.
* Update: WooCommerce & WordPress compatibility.

= 4.2.3 [2024-03-25] =
* New: Notice about cart&checkout blocks for the upsells feature.
* Fix: WP All Import running via CLI.
* Fix: Yith Request a Quote integration.

= 4.2.2 [2024-02-08] =
* New: Wombat product addons integration.
* New: New fields to import for WP All Import integration.
* Fix: CSS fixes.

= 4.2.1 [2024-01-29] =
* Fix: Allow set 0 quantity in the cart.
* Fix: Return default template to the product advanced options.

= 4.2.0 [2024-01-24] =
* New: New type of displaying - plain text.
* New: Discounts notifications.
* Fix: Empty price on variable products with the same price.
* Fix: Cache dependency.
* Update: REST API updated.

= 4.1.0 [2023-12-19] =
* New: New type of displaying - horizontal table.
* New: Show cart item subtotal as a discount.
* New: Excluding products\users for global pricing rules.
* New: Choose how to apply percentage discount: on sale or regular price.
* Update: updated WPML.config to recognize "you save" template

= 4.0.7 [2023-11-27] =
* Update: removed "product has no rules" option.
* Fix: issue when premium and free version are both activated.
* Fix: case when +/- buttons on quantity field may not work correctly in some themes.

= 4.0.6 [2023-11-20] =
* New: increase performance for the variable products: do not check if child have tiered pricing.
* Update: move freemius init function to the main plugin file.
* Fix: saving global pricing rule - save pricing type (Individual or Mix&Match)

= 4.0.5 [2023-11-13] =
* Fix: issue when comma used as a thousand separator

= 4.0.4 [2023-11-10] =
* Fix: cache issues
* Fix: free version limits

= 4.0.3 [2023-11-03] =
* Fix: Global rules mix and match pricing strategy

= 4.0.2 [2023-11-03] =
* Fix: Percentage discount calculations in templates for fixed pricing rules.

= 4.0.1 [2023-11-03] =
* Fix: Tiered fixed price cannot be higher than 99.

= 4.0.0 [2023-11-02] =
* New: New global pricing rules form
* New: Maximum and "group of quantity" quantity options
* New: Percentage discounts for regular prices for role-based pricing rules
* New: Gutenberg blocks for tiered pricing
* New: Base unit name per product
* New: Custom columns for pricing table
* New: "You save" feature
* New: Notice when tiered pricing is set incorrectly
* New: Debug mode
* New: Minimum PHP version is 7.2
* New: Yith request a quote integration
* New: Calculation logic settings
* Update: Codebase redesign
* Update: Settings page updated
* Update: Redesigned tiered pricing for manual orders
* Update: Cache and performance updates
* Fix: a bunch of minor issues

= 3.6.2 [2023-09-08] =
* Fix: WPML Multicurrency integration fatal error

= 3.6.1 [2023-09-07] =
* Fix: WPML Multicurrency integration issue

= 3.6.0 [2023-09-06] =
* Fix: Cart upsells
* Fix: Rounding issue
* Fix: Minimum order quantity - do not remove item from cart if the qty is less than minimum. Adjust qty instead.
* New: WP Multicurrency integration
* New: Rebuilt integrations tab

= 3.5.1 [2023-07-05] =
* Fix: Clickable pricing for variable products
* Fix: Pull right pricing when variation is specified in URL
* Fix: CSS for dropdown

= 3.5.0 [2023-06-30] =
* New: New type of displaying - dropdown
* Fix: Issue when regular prices is replaces by 1$
* Fix: Upsell {tp_actual_discount} variable

= 3.4.3 [2023-06-20] =
* New: Integration with WCCS
* Fix: Coupons potential error
* Fix: Displaying price with taxes on product page

= 3.4.2 [2023-05-25] =
* New: HPOS support
* Fix: Minimum order quantity issue for user roles
* Fix: Rounding price hook

= 3.4.1 [2023-04-11] =
* Fix: Fix default variations

= 3.4.0 [2023-03-30] =
* New: Cache: performance increased for large variable products
* New: Advanced settings for products: select default variation, mark products that does not use tiered pricing.
* New: Quantity measurement fields in the settings
* Fix: Fix role based rules for manual orders
* Fix: Fix taxes for manual orders

= 3.3.5 [2023-03-21] =
* New: Freemius SDK updated to 2.5.5
* New: Support "woocommerce_price_trim_zeros" hook
* New: Support role-based rules for manual orders
* New: New hook to override the rules separator during the import
* Fix: WCPA integration

= 3.3.4 [2023-03-07] =
* Fix: critical MOQ issue with variable products

= 3.3.3 [2023-03-06] =
* Fix: Legacy hooks infinity loop
* Fix: MOQ custom add to cart handlers
* New: Extended WPML config
* New: New hook for formatting variation prices

= 3.3.2 [2023-03-01] =
* Fix: Show tiered pricing via shortcode/elementor widget even if the global display option is disabled.
* Fix: Saving percentage tiered pricing rules for variation
* New: Show parent category for selected category
* New: Added more legacy hooks
* New: Make MOQ validation string translatable

= 3.3.1 [2023-01-26] =
* Fix: Tooltip layout
* Fix: Discount calculations on tiered pricing layouts
* Fix: Do not run frontend script on product that does not have tiered pricing
* New: Legacy hooks

= 3.3.0 [2023-01-18] =
* New: Supports {price_excluding_tax} and {price_including_tax} price suffix variables
* New: Showing discounted total price with original total crossed out
* New: Cache for price manager
* New: Trial button
* Fix: move to tiered_pricing_table/price/pricing_rule hook

= 3.2.0 [2023-01-13] =
* New: Cart upsell
* Fix: CSS issues
* Fix: typos

= 3.1.1 [2023-01-10] =
* New: Notice with global rules on tiered pricing tab
* Fix: issue with global pricing rules
* Fix: price without taxes issue
* Fix: typos

= 3.1.0 [2023-01-07] =
* New: new way to display the tiered pricing - options
* New: tiered pricing template can be selected per product
* New: little enhancements
* Fix: Firefox JS issue
* Fix: hidden "quick-edit" for products

= 3.0.1 [2023-01-02] =
* Fix: Default variation table
* Fix: Manual orders are active by default (unable to change order total for admin-made orders)

= 3.0.0 [2022-12-29] =
* New: Refactoring the plugin structure
* New: Refactoring the frontend script
* New: Global Tiered Pricing rules
* New: Tiered Pricing Blocks
* New: Elementor integration
* New: Settings redesign (added sections, many new settings, refactoring settings script)
* New: Discount column for fixed rules
* New: Tiered Pricing shortcode
* New: Tiered Pricing coupons management
* New: WOOCS integration
* Fix: Double pricing suffix on simple products
* Fix: Minor bugs

= 2.8.2 [2022-10-12] =
* Fix: Premium upgrading
* Fix: WCPA Integration

= 2.8.1 [2022-09-23] =
* New: Aelia Multicurrency Integration
* New: WCPA Integration
* New: WooCommerce Bundles Integration
* New: Role-based rules for API
* New: support role-based rules in WooCommerce Import
* New: New Hooks
* Fix: Catalog prices
* Bugs fixes & minor improvements

= 2.8.0 [2022-05-29] =
* New: REST API
* New: WordPress 6.0 support
* New: WooCommerce 6.6 support
* Bugs fixes & minor improvements

= 2.7.0 [2022-04-25] =
* New: static quantities for the pricing table
* New: Pricing cache for variable products
* New: WP All Import: "tiered pricing" import option
* Fix: Bugs fixes & minor improvements

= 2.6.1 [2022-03-04] =
* Security fix
* Fix: WooCommerce Subscription variable products support
* Minor improvements

= 2.6.0 [2021-10-24] =
* Fix: Minor bugs
* WPML extended support

= 2.5.0 [2021-08-09] =
* Freemius update
* Bugs fixes
* Performance improvements
* Improved role-based pricing
* WPML support

= 2.4.1 [2020-12-22] =
* Freemius update
* Bugs fixes
* Minor improvements

= 2.4.0 [2020-09-19] =
* Role-based pricing for the premium version
* Bug fixes
* Minor improves

= 2.3.7 [2020-04-22] =
* Addon fixes
* Price Suffix fix
* Minor improves

= 2.3.6 [2020-03-17] =
* WooCommerce 4 variations fix

= 2.3.5 [2020-02-17] =
* Fix issues
* Category tiers in the premium version

= 2.3.4 [2020-02-08] =
* Fix Ajax issues
* Fix assets issues

= 2.3.3 [2019-11-27] =
* Fix tax issue
* Added ability to calculate the tiered price based on all variations
* Added ability to set bulk rules for variable product
* Added support minimum quantity in the PREMIUM version
* Added summary table in PREMIUM version
* minor fixes
* Fixes for the popular themes

= 2.3.2 [2019-10-28] =
* Fix upgrading

= 2.3.1 [2019-09-16] =
* Fix the jQuery issue

= 2.3.0 [2019-07-19] =
* Fix critical bug

= 2.2.3 [2019-07-15] =
* Fixed bugs
* Added hooks

= 2.2.1 [2019-06-04] =
* Fixed bugs
* Added total price feature

= 2.2.0 [2019-05-07] =
* Added Import\Export tiered pricing
* Clickable quantity rows (Premium)
* Fix with some themes
* Fix the mini-cart issue

= 2.1.2 [2019-04-04] =
* Fixes
* Trial mode

= 2.1.1 [2019-03-26] =
* Fixes
* Premium variable catalog prices

= 2.1.0 [2019-03-24] =
* Support taxes
* Do not show the table head if column titles are blank
* Fix Updater
* Fix little issues

= 2.0.2 [2019-03-18] =
* Fix JS calculation prices
* Remove the table from variation tier tables

= 2.0.0 [2019-03-18] =
* Fix bugs
* JS updating prices on the product page
* Tooltip border
* Premium version

= 1.1.0 [2019-01-20] =
* Fix bug with comma as a thousand separators.
* Minor updates

= 1.0.0 [2018-08-28] =
* Initial Release