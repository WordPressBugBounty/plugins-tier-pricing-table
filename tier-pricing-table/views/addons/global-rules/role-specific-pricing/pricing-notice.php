<?php use MeowCrew\RoleAndCustomerBasedPricing\Entity\GlobalPricingRule;

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

<h4>
	<?php esc_html_e( 'Please note that rules in products have higher priority overriding the pricing rules you set here.
Priorities are the following:', 'tier-pricing-table' ); ?>
</h4>

<h4>
	<?php esc_html_e( 'Priorities are the following:', 'tier-pricing-table' ); ?>
</h4>

<ul class="tpt-global-pricing-notice-list">
    <li><?php esc_html_e( 'Single product rules (single variation)', 'tier-pricing-table' ); ?></li>
    <li><?php esc_html_e( 'Variable product rules (if a product is a variable)', 'tier-pricing-table' ); ?></li>
    <li><?php esc_html_e( 'Global rules', 'tier-pricing-table' ); ?></li>
</ul>
