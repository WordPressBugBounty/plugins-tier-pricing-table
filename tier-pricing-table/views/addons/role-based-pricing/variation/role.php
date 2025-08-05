<?php use TierPricingTable\Core\FileManager;

defined( 'ABSPATH' ) || die;

/**
 * Available variables
 *
 * @var int $minimum_amount
 * @var int $loop
 * @var string $role
 * @var string $type
 * @var array $price_rules_fixed
 * @var array $price_rules_percentage
 * @var float $regular_price
 * @var float $sale_price
 * @var FileManager $fileManager
 *
 */

global $wp_roles;

$roleName = isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : $role;
?>

<div class="tpt-role-based-role tpt-role-based-role--<?php echo esc_attr($role); ?>"
	 data-role-slug="<?php echo esc_attr($role); ?>" data-role-name="<?php echo esc_attr($roleName); ?>">
	<div class="tpt-role-based-role__header">
		<div class="tpt-role-based-role__name">
			<b><?php echo esc_attr($roleName); ?></b>
		</div>
		<div class="tpt-role-based-role__actions">
			<span class="tpt-role-based-role__action-toggle-view tpt-role-based-role__action-toggle-view--open"></span>
			<a href="#" class="tpt-role-based-role-action--delete"><?php esc_attr_e( 'Remove', 'woocommerce' ); ?></a>
		</div>
	</div>
	<div class="tpt-role-based-role__content">
		<?php

		$fileManager->includeTemplate( 'addons/role-based-pricing/variation/add-price-rules.php', array(
			'minimum_amount'         => $minimum_amount,
			'price_rules_fixed'      => $price_rules_fixed,
			'price_rules_percentage' => $price_rules_percentage,
			'regular_price'          => $regular_price,
			'sale_price'             => $sale_price,
			'type'                   => $type,
			'isFree'                 => false,
			'role'                   => $role,
			'loop'                   => $loop,
		) );

		?>
	</div>
</div>
