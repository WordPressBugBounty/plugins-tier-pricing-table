<?php defined( 'ABSPATH' ) || die;

use TierPricingTable\Core\FileManager;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingAddon;

/**
 * Available variables
 *
 * @var FileManager $fileManager
 * @var int $product_id
 */
?>
<div class="form-field tpt-role-based-block" id="tpt-role-based-block-<?php echo esc_attr( $product_id ); ?>"
	 data-product-type="simple"
	 data-add-action="<?php echo esc_attr( RoleBasedPricingAddon::GET_ROLE_ROW_HTML__ACTION ); ?>"
	 data-add-action-nonce="<?php echo esc_attr( wp_create_nonce( RoleBasedPricingAddon::GET_ROLE_ROW_HTML__ACTION ) ); ?>"
	 data-product-id="<?php echo esc_attr( $product_id ); ?>"
	 data-loop="1">
	<label class="tpt-role-based-block__name"><?php esc_attr_e( 'Role based pricing', 'tier-pricing-table' ); ?></label>
	<div class="tpt-role-based-block__content">

		<div class="tpt-role-based-roles">

			<?php

			$presentRoles = array();

			foreach ( wp_roles()->roles as $WPRole => $role_data ) {

				if ( RoleBasedPriceManager::roleHasRules( $WPRole, $product_id, 'edit' ) ) {

					$fileManager->includeTemplate( 'addons/role-based-pricing/simple/role.php', array(
						'fileManager'            => $fileManager,
						'minimum_amount'         => RoleBasedPriceManager::getProductQtyMin( $product_id, $WPRole, 'edit' ),
						'price_rules_fixed'      => RoleBasedPriceManager::getFixedPriceRules( $product_id, $WPRole, 'edit' ),
						'price_rules_percentage' => RoleBasedPriceManager::getPercentagePriceRules( $product_id, $WPRole, 'edit' ),
						'type'                   => RoleBasedPriceManager::getPricingType( $product_id, $WPRole, 'fixed', 'edit' ),
						'regular_price'          => RoleBasedPriceManager::getProductRegularRolePrice( $product_id, $WPRole, 'edit' ),
						'sale_price'             => RoleBasedPriceManager::getProductSaleRolePrice( $product_id, $WPRole, 'edit' ),
						'role'                   => $WPRole
					) );

					$presentRoles[] = $WPRole;
				}
			}
			?>
		</div>

		<div class="tpt-role-based-no-roles"
			 style="<?php echo esc_attr( ! empty( $presentRoles ) ? 'display: none;' : '' ); ?>">
			<span><?php esc_attr_e( 'Set up separate rules for different roles of customers. Choose a role and click the "Setup for role" button.', 'tier-pricing-table' ); ?></span>
			<p class="description"
			   style="display: block; margin: 0"><?php esc_attr_e( 'If you do not use this feature, you can disable this functionality at the settings to not complicate the interface. ', 'tier-pricing-table' ); ?></p>
		</div>

		<div class="tpt-role-based-adding-form">
			<select name="" id="" class="tpt-role-based-adding-form__role-selector">
				<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
					<?php if ( ! in_array( $key, $presentRoles ) ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $WPRole['name'] ); ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>

			<button class="button tpt-role-based-adding-form__add-button"> <?php esc_attr_e( 'Setup for role', 'tier-pricing-table' ); ?></button>

			<div class="clear"></div>
		</div>

		<select name="tiered_price_rules_roles_to_delete[]" class="tiered_price_rules_roles_to_delete" multiple
				style="display:none;">
			<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $WPRole['name'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
