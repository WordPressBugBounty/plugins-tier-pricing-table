<?php

	use TierPricingTable\Forms\Form;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	/**
	 * @var string $entity_id
	 * @var ?string $role
	 * @var ?string $loop
	 * @var ?string $custom_prefix
	 * @var string $tiered_pricing_type
	 * @var array $percentage_rules
	 * @var array $fixed_rules
	 * @var array $amount_rules
	 */

	// Ensure arrays and apply filters
	$percentage_rules = is_array( $percentage_rules ) ? $percentage_rules : array();
	$fixed_rules      = is_array( $fixed_rules ) ? $fixed_rules : array();
	$amount_rules     = isset( $amount_rules ) && is_array( $amount_rules ) ? $amount_rules : array();
	$fieldsWidth      = apply_filters( 'tiered_pricing_table/admin/tiered_pricing_rules_form/inputs_width', 50 );

	// Define types to loop through for the UI blocks
	$pricing_blocks = array(
			'fixed'      => $fixed_rules,
			'percentage' => $percentage_rules,
			'amount'     => $amount_rules,
	);

	/**
	 * Helper function to render a single rule row.
	 */
	if ( ! function_exists( 'tpt_render_pricing_row' ) ) {
		function tpt_render_pricing_row(
				$amount,
				$value,
				$type,
				$role,
				$loop,
				$custom_prefix,
				$fieldsWidth,
				$entity_id
		) {
			$is_fixed      = ( 'fixed' === $type );
			$is_percentage = ( 'percentage' === $type );

			// Map field keys based on type: a price per unit, a percentage off, or an amount off per unit.
			$qty_key = $type . '_quantities';
			$val_key = $is_fixed ? 'fixed_prices' : $type . '_discounts';

			$qty_name = Form::getFieldName( $qty_key, $role, $loop, $custom_prefix ) . '[]';
			$val_name = Form::getFieldName( $val_key, $role, $loop, $custom_prefix ) . '[]';

			// Format value and attributes
			$val_display = $is_percentage ? $value : wc_format_localized_price( $value );
			$placeholders = array(
					'fixed'      => __( 'Price', 'tier-pricing-table' ),
					'percentage' => __( 'Discount', 'tier-pricing-table' ),
					'amount'     => __( 'Amount off', 'tier-pricing-table' ),
			);
			$placeholder  = $placeholders[ $type ] ?? $placeholders['fixed'];
			?>
			<div class="tiered-pricing-pricing-rules-form-row">
				<div class="tiered-pricing-pricing-rules-form-row__inputs"
					 style="width: <?php echo esc_attr( $fieldsWidth ); ?>%;">

					<input type="number"
						   min="2"
						   value="<?php echo esc_attr( $amount ); ?>"
						   placeholder="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
						   name="<?php echo esc_attr( $qty_name ); ?>">

					<input type="<?php echo $is_percentage ? 'number' : 'text'; ?>"
							<?php echo $is_percentage ? 'max="99"' : ''; ?>
						   step="any"
						   class="<?php echo $is_percentage ? '' : 'wc_input_price'; ?>"
						   value="<?php echo esc_attr( $val_display ); ?>"
						   placeholder="<?php echo esc_attr( $placeholder ); ?>"
						   name="<?php echo esc_attr( $val_name ); ?>">

					<?php
						do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/inputs', $entity_id, $amount,
								$role, $loop, $custom_prefix, $type );
					?>
				</div>
				<div class="tiered-pricing-pricing-rules-form-row__actions">
					<button type="button" class="notice-dismiss tiered-pricing-pricing-rules-form__remove"></button>
				</div>
			</div>
			<?php
		}
	}
?>

	<div class="tiered-pricing-rules-form">

		<?php do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/form_begin', $entity_id, $role, $loop,
				$custom_prefix ); ?>

		<div class="<?php echo esc_attr( is_null( $loop ) ? 'tiered-pricing-form-block' : 'tiered-pricing-form-variation-block' ); ?> tiered-pricing-rules-form__type">

			<label for="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>">
				<?php esc_html_e( 'Tiered pricing type', 'tier-pricing-table' ); ?>
			</label>

			<select name="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>"
					id="<?php echo esc_attr( Form::getFieldName( 'type', $role, $loop, $custom_prefix ) ); ?>">

				<option value="fixed" <?php selected( 'fixed', $tiered_pricing_type ); ?>>
					<?php esc_html_e( 'Fixed prices', 'tier-pricing-table' ); ?>
				</option>

				<option value="percentage" <?php selected( 'percentage',
						$tiered_pricing_type ); ?> <?php disabled( ! tpt_fs()->can_use_premium_code() ); ?>>
					<?php echo tpt_fs()->can_use_premium_code() ? esc_html__( 'Percentage discounts',
							'tier-pricing-table' ) : esc_html__( 'Percentage discounts (only in premium version)',
							'tier-pricing-table' ); ?>
				</option>

				<option value="amount" <?php selected( 'amount', $tiered_pricing_type ); ?>>
					<?php esc_html_e( 'Amount discounts', 'tier-pricing-table' ); ?>
				</option>
			</select>
		</div>

		<?php do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/after_pricing_type', $entity_id, $role,
				$loop, $custom_prefix ); ?>

		<?php foreach ( $pricing_blocks as $type => $rules ) : ?>
			<div class="<?php echo esc_attr( is_null( $loop ) ? 'tiered-pricing-form-block' : 'tiered-pricing-form-variation-block' ); ?>
             tiered-pricing-rules-form__<?php echo esc_attr( $type ); ?>
             <?php echo $type !== $tiered_pricing_type ? 'hidden' : ''; ?>">

				<label><?php esc_html_e( 'Tiered price', 'tier-pricing-table' ); ?></label>

				<div class="tiered-pricing-pricing-rules-form" role="form">
					<div class="tiered-pricing-pricing-rules-form__rules">
						<?php
							foreach ( $rules as $amount => $value ) {
								tpt_render_pricing_row( $amount, $value, $type, $role, $loop, $custom_prefix,
										$fieldsWidth, $entity_id );
							}
							tpt_render_pricing_row( '', '', $type, $role, $loop, $custom_prefix, $fieldsWidth,
									$entity_id );
						?>
					</div>

					<div class="tiered-pricing-pricing-rules-form__buttons">
						<button type="button" class="button tiered-pricing-pricing-rules-form__add-new">
							<?php esc_html_e( 'New tier', 'tier-pricing-table' ); ?>
						</button>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<?php do_action( 'tiered_pricing_table/admin/tiered_pricing_rules_form/form_end', $entity_id, $role, $loop,
				$custom_prefix ); ?>
	</div>
<?php
