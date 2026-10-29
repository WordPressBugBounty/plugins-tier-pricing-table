<?php

	use TierPricingTable\Addons\UserBasedPricing\UserBasedPricingRule;
	use TierPricingTable\Core\ServiceContainer;
	use TierPricingTable\Forms\MinimumOrderQuantityForm;
	use TierPricingTable\Forms\RegularPricingForm;
	use TierPricingTable\Forms\TieredPricingRulesForm;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * Available variables
	 *
	 * @var WC_Product $product
	 * @var UserBasedPricingRule $pricing_rule
	 * @var int $loop
	 */

	$fileManager = ServiceContainer::getInstance()->getFileManager();

	$roleKey = 'user_' . $pricing_rule->getUserId();

?>

<?php if ( ! tpt_fs()->can_use_premium_code() ) : ?>
	<div class="tpt-rc__premium">
		<span class="tpt-badge tpt-badge--premium"><?php esc_html_e( 'Premium', 'tier-pricing-table' ); ?></span>
		<span class="tpt-rc__premium-text"><?php esc_html_e( 'Customer prices are a premium feature. The fields below are read-only in the free version.', 'tier-pricing-table' ); ?></span>
		<a class="button tpt-rc__premium-button" target="_blank" rel="noopener" href="<?php echo esc_url( tpt_fs_activation_url() ); ?>"><?php esc_html_e( 'Upgrade to premium', 'tier-pricing-table' ); ?></a>
	</div>
<?php endif; ?>

<?php
	if ( ! $product->is_type( 'variable' ) ) {
		RegularPricingForm::render( $roleKey, $loop, $pricing_rule->getRegularPrice(),
				$pricing_rule->getSalePrice(), $pricing_rule->getPricingType(), $pricing_rule->getDiscount(),
				$pricing_rule->getDiscountType() );
		?>
		<hr style="border-color: #f5f5f5;border-top: none;">
		<?php
	}

	MinimumOrderQuantityForm::render( $roleKey, $loop, $pricing_rule->getMinimumOrderQuantity() );

	do_action( 'tiered_pricing_table/admin/user_based_rules/after_minimum_order_quantity_field',
			$pricing_rule->getProductId(), $pricing_rule->getUserId(), $loop );

?>
<hr style="border-color: #f5f5f5;border-top: none;">
<?php

	TieredPricingRulesForm::render( $pricing_rule->getProductId(), $roleKey, $loop,
			$pricing_rule->getTieredPricingType(), $pricing_rule->getPercentageTieredPricingRules(),
			$pricing_rule->getFixedTieredPricingRules() );

	do_action( 'tiered_pricing_table/admin/user_based_rules/after_tiered_pricing_rules_field',
			$pricing_rule->getProductId(), $pricing_rule->getUserId(), $loop );
?>
