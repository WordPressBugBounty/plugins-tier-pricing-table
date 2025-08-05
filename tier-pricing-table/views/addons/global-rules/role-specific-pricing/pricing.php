<?php defined( 'ABSPATH' ) || die;

use MeowCrew\RoleAndCustomerBasedPricing\Core\FileManager;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;

/**
 * Available variables
 *
 * @var  FileManager $fileManager
 * @var  GlobalPricingRule $priceRule
 */
?>

<?php

$minimumInputAttrs = array(
	'min'  => 1,
	'step' => 1
);

$desc          = __( 'Set if you sell the product only from a specific quantity that is more than 1', 'tier-pricing-table' );
$tip           = true;
$premiumNotice = '';

if ( ! tpt_fs()->is_premium() ) {
	$minimumInputAttrs['disabled'] = true;
}

?>
<div class="tpt-global-pricing-title">
    <hr>
    <h4><?php esc_attr_e( 'Pricing', 'tier-pricing-table' ); ?>
		<?php if ( ! tpt_fs()->is_premium() ): ?>
            <span class="tpt_premium_subsection_label"
                  style="font-size: .8em">
                <?php esc_html_e( 'Only in premium version', 'tier-pricing-table' ); ?>
            </span>
            <a target="_blank" style="margin-left: 10px" href="<?php echo esc_attr( tpt_fs()->get_upgrade_url() ) ?>" class="button-small">
				<?php esc_html_e( 'Upgrade your plan', 'tier-pricing-table' ); ?>
            </a>
		<?php endif; ?>
    </h4>
</div>

<p class="form-field tiered_pricing_pricing_type_field">
    <label for="tpt_pricing_type"><?php esc_attr_e( 'Pricing type', 'tier-pricing-table' ); ?> </label>

    <select name="tpt_pricing_type" id="tpt_pricing_type" data-tiered-price-pricing-type-select>
        <option value="flat" <?php selected( $priceRule->getPricingType(), 'flat' ); ?>><?php esc_attr_e( 'Flat prices', 'tier-pricing-table' ); ?></option>
        <option value="percentage" <?php selected( $priceRule->getPricingType(), 'percentage' ); ?>><?php esc_attr_e( 'Percentage discount', 'tier-pricing-table' ); ?></option>
    </select>
</p>

<p class="form-field tiered_pricing_discount_field <?php echo $priceRule->getPricingType() === 'flat' ? 'hidden' : ''; ?>"
   data-tiered-price-pricing-type
   data-tiered-price-pricing-type-percentage>
    <label for="tpt_discount"><?php esc_attr_e( 'Discount (%)', 'tier-pricing-table' ); ?> </label>

    <input <?php echo ! tpt_fs()->is_premium() ? 'disabled' : ''; ?>
            type="number"
            min="0"
            max="100"
            step="any"
            value="<?php echo esc_attr( $priceRule->getDiscount() ); ?>"
            placeholder="<?php esc_attr_e( 'Leave empty to don\'t apply any', 'tier-pricing-table' );
			echo esc_attr( $premiumNotice ); ?>"
            name="tpt_discount" id="tpt_discount">
</p>

<p class="form-field tiered_pricing_regular_price_field <?php echo $priceRule->getPricingType() === 'percentage' ? 'hidden' : ''; ?>"
   data-tiered-price-pricing-type
   data-tiered-price-pricing-type-flat>
    <label for="tpt_regular_price"><?php echo esc_attr( __( 'Regular price', 'tier-pricing-table' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?> </label>

    <input <?php echo ! tpt_fs()->is_premium() ? 'disabled' : ''; ?>
            type="text"
            value="<?php echo esc_attr( wc_format_localized_price( $priceRule->getRegularPrice() ) ); ?>"
            placeholder="<?php esc_attr_e( 'Leave empty to don\'t change it', 'tier-pricing-table' );
			echo esc_attr( $premiumNotice ); ?>"
            class="wc_input_price"
            name="tpt_regular_price">
</p>

<p class="form-field tiered_pricing_sale_price_field <?php echo $priceRule->getPricingType() === 'percentage' ? 'hidden' : ''; ?>"
   data-tiered-price-pricing-type
   data-tiered-price-pricing-type-flat>
    <label for="tpt_sale_price"><?php echo esc_attr( __( 'Sale price', 'tier-pricing-table' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?></label>

    <input <?php echo ! tpt_fs()->is_premium() ? 'disabled' : ''; ?>
            type="text"
            value="<?php echo esc_attr( wc_format_localized_price( $priceRule->getSalePrice() ) ); ?>"
            placeholder="<?php esc_attr_e( 'Leave empty to don\'t change it', 'tier-pricing-table' );
			echo esc_attr( $premiumNotice ); ?>"
            class="wc_input_price"
            name="tpt_sale_price">
</p>

<div class="tpt-global-pricing-title">
    <hr>
    <h4><?php esc_attr_e( 'Tiered Pricing', 'tier-pricing-table' ); ?></h4>
</div>

<p class="form-field">
    <label for="tpt_applying_type"><?php esc_html_e( 'Applying pricing as', 'tier-pricing-table' ); ?></label>

    <select name="tpt_applying_type" id="tpt_applying_type" style="width: 50%;">
        <option value="cross" <?php selected( $priceRule->getApplyingType(), 'cross' ); ?>>
			<?php esc_html_e( 'Cross the products (calculate discounts based on
            quantity of any product in this rule)', 'tier-pricing-table' ); ?>
        </option>
        <option value="individual" <?php selected( $priceRule->getApplyingType(), 'individual' ); ?>>
			<?php esc_html_e( 'Per individual product (calculate discounts based on quantity of a particular product)', 'tier-pricing-table' ); ?>
        </option>
    </select>
</p>

<div>
	<?php

	$fileManager->includeTemplate( 'admin/add-price-rules.php', array(
		'price_rules_fixed'      => $priceRule->getFixedTieredPricingRules(),
		'price_rules_percentage' => $priceRule->getPercentageTieredPricingRules(),
		'type'                   => $priceRule->getTieredPricingType(),
		'prefix'                 => 'global',
		'isFree'                 => ! tpt_fs()->is_premium()
	) );

	?>
</div>

<div class="tpt-global-pricing-title">
    <hr>
    <h4><?php esc_attr_e( 'Minimum Order Quantity', 'tier-pricing-table' ); ?>
	    <?php if ( ! tpt_fs()->is_premium() ): ?>
            <span class="tpt_premium_subsection_label"
                  style="font-size: .8em">
                <?php esc_html_e( 'Only in premium version', 'tier-pricing-table' ); ?>
            </span>
            <a target="_blank" style="margin-left: 10px" href="<?php echo esc_attr( tpt_fs()->get_upgrade_url() ) ?>" class="button-small">
			    <?php esc_html_e( 'Upgrade your plan', 'tier-pricing-table' ); ?>
            </a>
	    <?php endif; ?>
    </h4>
</div>

<?php
woocommerce_wp_text_input( array(
	'id'                => 'tpt_minimum',
	'type'              => 'number',
	'custom_attributes' => $minimumInputAttrs,
	'value'             => $priceRule->getMinimum(),
	'label'             => __( 'Minimum quantity', 'tier-pricing-table' ),
	'description'       => $desc,
	'default'           => 1,
	'desc_tip'          => $tip
) );
?>

<script>
    jQuery(document).ready(function ($) {
        $('[data-tiered-price-pricing-type-select]').on('change', function () {
            $('[data-tiered-price-pricing-type]').css('display', 'none');
            $('[data-tiered-price-pricing-type-' + this.value + ']').css('display', 'block');
        });
    });
</script>
