<?php

use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\LookupService;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Available variables
 *
 * @var  FileManager $fileManager
 * @var  GlobalPricingRule $priceRule
 */

?>
<style type="text/css">
    #edit-slug-box, #minor-publishing-actions {
        display: none
    }
</style>

<div class="panel woocommerce_options_panel">
    <div class="options_group">

        <div class="tpt-global-pricing-title">
            <hr>
            <h4><?php esc_attr_e( 'Choose the products and/or categories to apply the pricing rule', 'tier-pricing-table' ); ?></h4>
        </div>

        <p class="form-field">
            <label for="tpt_included_categories"><?php esc_html_e( 'Apply for categories', 'tier-pricing-table' ); ?></label>

            <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="tpt_included_categories"
                    name="tpt_included_categories[]"
                    data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'tier-pricing-table' ); ?>"
                    data-action="woocommerce_json_search_tpt_categories">

				<?php foreach ( $priceRule->getIncludedProductCategories() as $categoryId ) : ?>
					<?php $category = get_term_by( 'id', $categoryId, 'product_cat' ); ?>

					<?php if ( $category ) : ?>
                        <option selected
                                value="<?php echo esc_attr( $categoryId ); ?>"><?php echo esc_attr( LookupService::getCategoryLabel( $category ) ); ?></option>
					<?php endif; ?>

				<?php endforeach; ?>
            </select>

			<?php echo wc_help_tip( __( 'Choose the categories for which this pricing rule will apply. The rule applies to all products in the category.', 'tier-pricing-table' ) ); ?>
        </p>

        <p class="form-field">
            <label for="tpt_included_products"><?php esc_html_e( 'Apply for specific products', 'tier-pricing-table' ); ?></label>

            <select class="wc-product-search" multiple="multiple" style="width: 50%;" id="tpt_included_products"
                    name="tpt_included_products[]"
                    data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'tier-pricing-table' ); ?>"
                    data-action="woocommerce_json_search_products">

				<?php foreach ( $priceRule->getIncludedProducts() as $productId ) : ?>

					<?php $product = wc_get_product( $productId ); ?>

					<?php if ( $product ) : ?>
                        <option selected
                                value="<?php echo esc_attr( $productId ); ?>"><?php echo esc_attr( $product->get_name() ); ?></option>
					<?php endif; ?>

				<?php endforeach; ?>
            </select>

			<?php echo wc_help_tip( __( 'Pick up products for which you want to apply the pricing rule.', 'tier-pricing-table' ) ); ?>
        </p>

        <div class="tpt-global-pricing-title">
            <hr>
            <h4><?php esc_attr_e( 'Choose the user role and/or customers\' accounts to apply the pricing rule', 'tier-pricing-table' ); ?></h4>
        </div>

        <p class="form-field">
            <label for="tpt_included_user_roles"><?php esc_html_e( 'Include user roles', 'tier-pricing-table' ); ?></label>

            <select class="tpt-select-woo" multiple="multiple" style="width: 50%;" id="tpt_included_user_roles"
                    name="tpt_included_user_roles[]"
                    data-placeholder="<?php esc_attr_e( 'Select for a customer role&hellip;', 'tier-pricing-table' ); ?>">

				<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
					<?php if ( ! in_array( $key, array() ) ) : ?>
                        <option
							<?php selected( in_array( $key, $priceRule->getIncludedUserRoles() ) ); ?>
                                value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_attr( $WPRole['name'] ); ?>
                        </option>
					<?php endif; ?>
				<?php endforeach; ?>
            </select>

			<?php echo wc_help_tip( __( 'Choose to what user roles this rule will be relevant. Applies to all users with those roles.', 'tier-pricing-table' ) ); ?>
        </p>

        <p class="form-field">
            <label for="tpt_included_users"><?php esc_html_e( 'Include specific customers', 'tier-pricing-table' ); ?></label>

            <select class="rbp-select-woo wc-product-search" multiple="multiple" style="width: 50%;"
                    id="tpt_included_users"
                    name="tpt_included_users[]"
                    data-action="woocommerce_json_search_tpt_customers"
                    data-placeholder="<?php esc_attr_e( 'Select for a customer&hellip;', 'tier-pricing-table' ); ?>">

				<?php foreach ( $priceRule->getIncludedUsers() as $userId ) : ?>
					<?php $user = get_user_by( 'id', $userId ); ?>
					<?php if ( $user ) : ?>
                        <option selected
                                value="<?php echo esc_attr( $userId ); ?>"><?php echo esc_attr( $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')' ); ?></option>
					<?php endif; ?>

				<?php endforeach; ?>
            </select>

			<?php echo wc_help_tip( __( 'Pick up separate user accounts, which will be affected by this rule. ', 'tier-pricing-table' ) ); ?>
        </p>
    </div>
</div>
