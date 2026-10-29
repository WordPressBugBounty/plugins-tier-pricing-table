<?php defined( 'ABSPATH' ) || die;

	use TierPricingTable\Addons\RoleBasedPricing\ProductManager;
	use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingRule;
	use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
	use TierPricingTable\Core\ServiceContainer;

	/**
	 * Available variables
	 *
	 * @var int $product_id
	 * @var int $loop
	 */

	$product = wc_get_product( $product_id );

	if ( ! $product ) {
		return;
	}

	$loop = ! is_null( $loop ) ? $loop : null;

	$fileManager = ServiceContainer::getInstance()->getFileManager();

?>

<div class="form-field tpt-role-based-block" id="tpt-role-based-block-<?php echo esc_attr( $product_id ); ?>"
     data-product-type="<?php echo esc_attr( $product->get_type() ); ?>"
     data-add-action="<?php echo esc_attr( ProductManager::GET_ROLE_ROW_HTML__ACTION ); ?>"
     data-add-action-nonce="<?php echo esc_attr( wp_create_nonce( ProductManager::GET_ROLE_ROW_HTML__ACTION ) ); ?>"
     data-product-id="<?php echo esc_attr( $product_id ); ?>"
     data-loop="<?php echo esc_attr( $loop ); ?>">
	<div class="tpt-role-based-block__content">
		<div class="tpt-role-based-roles">
			<?php

				$presentRoles = array();

				foreach ( RoleBasedPriceManager::getAvailableRoles() as $WPRole => $roleLabel ) {

					if ( RoleBasedPriceManager::roleHasRules( $WPRole, $product_id, 'edit' ) ) {

						$roleBasedRule = RoleBasedPricingRule::build( $product_id, $WPRole );

						$fileManager->includeTemplate( 'addons/role-based-pricing/role.php', array(
								'pricing_rule' => $roleBasedRule,
								'role'         => $WPRole,
								'product_id'   => $product_id,
								'product'      => $product,
								'loop'         => $loop,
						) );

						$presentRoles[] = $WPRole;
					}
				}
			?>
		</div>


		<div class="tpt-role-based-adding-form tpt-rc__hidden-form">
			<select class="tpt-role-based-adding-form__role-selector">
				<?php foreach ( RoleBasedPriceManager::getAvailableRoles() as $key => $roleLabel ) : ?>
					<?php if ( ! in_array( $key, $presentRoles ) ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $roleLabel ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>

			<button class="button tpt-role-based-adding-form__add-button">
				<?php
					esc_attr_e( 'Add role pricing', 'tier-pricing-table' );
				?>
			</button>
			<div class="clear"></div>
		</div>

		<?php $rolesToDeleteName = ! is_null( $loop ) ? "tiered_price_rules_roles_to_delete_variation[$loop][]" : 'tiered_price_rules_roles_to_delete[]'; ?>

		<select name="<?php echo esc_attr( $rolesToDeleteName ); ?>" class="tiered_price_rules_roles_to_delete" multiple
		        style="display:none;">
			<?php foreach ( RoleBasedPriceManager::getAvailableRoles() as $key => $roleLabel ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $roleLabel ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
