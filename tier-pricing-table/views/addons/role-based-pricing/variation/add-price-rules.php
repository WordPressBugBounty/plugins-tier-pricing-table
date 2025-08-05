<?php if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Available variables
 *
 * @var string $role
 * @var string $type
 * @var int $minimum_amount
 * @var int $loop
 * @var float $regular_price
 * @var float $sale_price
 */
?>

<p style="width: 48%"
   class="form-field tiered_pricing_roles_regular_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]_field show_if_simple show_if_variable">
	<label for="tiered_pricing_roles_regular_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]"><?php echo esc_attr( __( 'Regular price', 'tier-pricing-table' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?> </label>

	<input type="text" value="<?php echo esc_attr( wc_format_localized_price( $regular_price ) ); ?>"
		   placeholder="<?php esc_attr_e( 'Leave empty to don\'t change it', 'tier-pricing-table' ); ?>"
		   class="wc_input_price"
		   name="tiered_pricing_roles_regular_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]">
</p>

<p style="width: 48%"
   class="form-field tiered_pricing_roles_sale_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]_field show_if_simple show_if_variable">
	<label for="tiered_pricing_roles_sale_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]"><?php echo esc_attr( __( 'Sale price', 'tier-pricing-table' ) . ' (' . get_woocommerce_currency_symbol() . ')' ); ?></label>

	<input type="text" value="<?php echo esc_attr( wc_format_localized_price( $sale_price ) ); ?>"
		   placeholder="<?php esc_attr_e( 'Leave empty to don\'t change it', 'tier-pricing-table' ); ?>"
		   class="wc_input_price"
		   name="tiered_pricing_roles_sale_price_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]">
</p>

<p class="form-field form-row" style="width: 48%">
	<label for="tiered-price-start-from">
		<?php esc_attr_e( 'Tiered pricing minimum quantity', 'tier-pricing-table' ); ?>
	</label>
	<?php
	// translators: %s = role name
	$tip = sprintf( __( 'Set if you sell the product <b>for %s</b> only from a specific quantity that is more than 1', 'tier-pricing-table' ), $role );
	echo wc_help_tip( $tip );
	?>
	<br>
	<input type="number"
		   placeholder="1"
		   value="<?php echo esc_attr( $minimum_amount ); ?>"
		   min="1"
		   id="tiered-price-start-from"
		   name="tiered_pricing_minimum_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]"
	>
</p>

<p class="form-field">
	<label for="tiered-price-type-select"
		   style="display: block"><?php esc_attr_e( 'Tiered pricing type', 'tier-pricing-table' ); ?></label>
	<select name="tiered_price_rules_type_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>]"
			id="tiered-price-type-select"
			style="width: 50%"
			data-role-tiered-price-type-select>
		<option value="fixed" <?php selected( 'fixed', $type ); ?> >
			<?php
			esc_attr_e( 'Fixed',
				'tier-pricing-table' );
			?>
		</option>
		<option value="percentage" <?php selected( 'percentage', $type ); ?> >
			<?php
			esc_attr_e( 'Percentage',
				'tier-pricing-table' );
			?>
		</option>

	</select>
</p>

<p class="form-field <?php echo esc_attr( 'percentage' === $type  ? 'hidden' : '' ); ?>"
   data-role-tiered-price-type-fixed
   data-role-tiered-price-type>
	<label style="display: block"><?php esc_attr_e( 'Tiered price', 'tier-pricing-table' ); ?></label>
	<span data-price-rules-wrapper>
		<?php if ( ! empty( $price_rules_fixed ) ) : ?>
			<?php foreach ( $price_rules_fixed as $amount => $price ) : ?>
				<span data-price-rules-container>
					<span data-price-rules-input-wrapper>
						<input type="number" value="<?php echo esc_attr( $amount ); ?>" min="2"
							   placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
							   class="price-quantity-rule price-quantity-rule--simple"
							   name="tiered_price_fixed_quantity_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
						<input type="text" value="<?php echo esc_attr( wc_format_localized_price( $price ) ); ?>"
							   placeholder="<?php esc_attr_e( 'Price', 'tier-pricing-table' ); ?>"
							   class="wc_input_price price-quantity-rule--simple"
							   name="tiered_price_fixed_price_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
					</span>
					<span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
					<br>
					<br>
				</span>

			<?php endforeach; ?>
		<?php endif; ?>

		<span data-price-rules-container>
			<span data-price-rules-input-wrapper>
				<input type="number" min="2" placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
					   class="price-quantity-rule price-quantity-rule--simple"
					   name="tiered_price_fixed_quantity_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
				<input type="text" placeholder="<?php esc_attr_e( 'Price', 'tier-pricing-table' ); ?>"
					   class="wc_input_price  price-quantity-rule--simple"
					   name="tiered_price_fixed_price_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
			</span>
			<span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
			<br>
			<br>
		</span>
	<button data-add-new-price-rule class="button"><?php esc_attr_e( 'New tier', 'tier-pricing-table' ); ?></button>
	</span>
</p>

<p class="form-field <?php echo esc_attr( 'fixed' === $type ? 'hidden' : '' ); ?>"
   data-role-tiered-price-type-percentage
   data-role-tiered-price-type>
	<label style="display: block"><?php esc_attr_e( 'Tiered price', 'tier-pricing-table' ); ?></label>
	<span data-price-rules-wrapper>
		<?php if ( ! empty( $price_rules_percentage ) ) : ?>
			<?php foreach ( $price_rules_percentage as $amount => $discount ) : ?>
				<span data-price-rules-container>
					<span data-price-rules-input-wrapper>
						<input type="number" value="<?php echo esc_attr( $amount ); ?>" min="2"
							   placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
							   class="price-quantity-rule price-quantity-rule--simple"
							   name="tiered_price_percent_quantity_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
						<input type="number" value="<?php echo esc_attr( $discount ); ?>" max="99"
							   placeholder="<?php esc_attr_e( 'Percent discount', 'tier-pricing-table' ); ?>"
							   class="price-quantity-rule--simple"
							   step="any"
							   name="tiered_price_percent_discount_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
					</span>
					<span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
					<br>
					<br>
				</span>

			<?php endforeach; ?>
		<?php endif; ?>

		<span data-price-rules-container>
			<span data-price-rules-input-wrapper>
				<input type="number" min="2" placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
					   class="price-quantity-rule price-quantity-rule--simple"
					   name="tiered_price_percent_quantity_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
				<input type="number" max="99"
					   placeholder="<?php esc_attr_e( 'Percent discount', 'tier-pricing-table' ); ?>"
					   class="price-quantity-rule--simple"
					   step="any"
					   name="tiered_price_percent_discount_roles_variable[<?php echo esc_attr( $loop ); ?>][<?php echo esc_attr( $role ); ?>][]">
			</span>
			<span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
			<br>
			<br>
		</span>
	<button data-add-new-price-rule class="button"><?php esc_attr_e( 'New tier', 'tier-pricing-table' ); ?></button>

	</span>
</p>
