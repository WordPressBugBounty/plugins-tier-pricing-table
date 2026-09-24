<?php

namespace TierPricingTable\Addons\NonLoggedInUsers;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT\ApplicationAdmin;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT\ApplicationCPT;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Emails\EmailsManager;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Frontend\RegistrationForm;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services\LoginRedirect;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesService;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSettings;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSubsection;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\PaymentMethodsSubsection;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\ShippingMethodsSubsection;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin\CategoryListColumn;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin\CategoryVisibilityFields;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin\ProductListColumn;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin\ProductListFilter;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin\ProductVisibilityFields;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\ClosedStoreService;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\PrivateStoreSubsection;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\ProductVisibilityService;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilitySettings;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings as WholesaleSettings;
/**
 * Wholesale & Guest Users: what visitors may see and buy, and how customers become wholesale
 * customers (application form, approval queue, e-mails, login redirect). The slug is the one of the
 * former "Non-Logged-In Users" module, so stores keep their on/off state.
 */
class NonLoggedInUsersAddon extends AbstractAddon {
    public function getName() {
        return __( 'Wholesale & Guest Users', 'tier-pricing-table' );
    }

    public function getDescription() {
        return __( 'Hide prices or prevent purchasing for guests, close the store to visitors, show products and categories to selected roles only, set minimum orders and payment or shipping methods per role, and let customers apply for a wholesale account with an approval queue. Set up under the Wholesale tab.', 'tier-pricing-table' );
    }

    public function getIcon() : string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h9.5a6.5 6.5 0 0 1-.4-2H6c.2-.71 3.3-2 6-2 .35 0 .7.02 1.05.06.3-.7.72-1.34 1.23-1.89C13.55 14.06 12.77 14 12 14zm6.5 1a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9zm-.62 6.62-2.12-2.12 1.06-1.06 1.06 1.06 2.3-2.3 1.06 1.06-3.36 3.36z"/></svg>';
    }

    public function getSlug() {
        return 'non-logged-in-users';
    }

    public function run() {
        // the Wholesale tab: registration settings first, the guest options as its last group
        new WholesaleSettings();
        new CartRulesSettings();
        add_filter( 'tiered_pricing_table/settings/wholesale_subsections', array($this, 'addSettingsSubsection') );
        // the private store is part of the free plugin: the closed store checks its own switch on every
        // request; catalog visibility by role is an opt-in, so the product and category screens stay
        // simple by default
        new ClosedStoreService();
        if ( VisibilitySettings::isEnabled() ) {
            new ProductVisibilityService();
            if ( is_admin() ) {
                new ProductVisibilityFields();
                new CategoryVisibilityFields();
                new ProductListColumn();
                new ProductListFilter();
                new CategoryListColumn();
            }
        }
        // wholesale registration is part of the free plugin too
        if ( WholesaleSettings::isEnabled() ) {
            new ApplicationCPT();
            new ApplicationAdmin();
            new EmailsManager();
            new RegistrationForm();
            new LoginRedirect();
        } else {
            // the shortcode stays known, so a page that carries it explains itself to the store owner
            add_shortcode( RegistrationForm::SHORTCODE, array($this, 'renderDisabledNote') );
        }
        return;
        // premium: the guest options and the cart rules
        add_action( 'init', function () {
            $this->getContainer()->initService( NonLoggedInUsersService::class );
        } );
        if ( CartRulesSettings::isEnabled() ) {
            new CartRulesService();
        }
    }

    public function addSettingsSubsection( $subsections ) {
        // the private store is free; the cart rules and the guest options are premium and are shown
        // locked in the free version (PremiumSettingsManager)
        $subsections[] = PrivateStoreSubsection::class;
        $subsections[] = CartRulesSubsection::class;
        $subsections[] = PaymentMethodsSubsection::class;
        $subsections[] = ShippingMethodsSubsection::class;
        $subsections[] = NonLoggedInUsersSubsection::class;
        return $subsections;
    }

    public function renderDisabledNote() : string {
        if ( !current_user_can( 'manage_woocommerce' ) ) {
            return '';
        }
        $url = admin_url( 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings&section=wholesale' );
        return '<p class="tpt-wholesale-disabled-note"><em>' . sprintf( 
            /* translators: %s: link to the settings */
            esc_html__( 'Wholesale registration is switched off. Enable it under %s. Only shop managers see this note.', 'tier-pricing-table' ),
            '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Tiered Pricing → Wholesale', 'tier-pricing-table' ) . '</a>'
         ) . '</em></p>';
    }

}
