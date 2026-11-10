<?php
/**
 * The order tiers of a global pricing rule.
 *
 * @var array $tiers Rows with "from" and "discount".
 * @var string $discount_type "percentage" or "fixed".
 * @var string $basis "amount" (the subtotal) or "quantity" (the item count).
 * @var bool $percentage_available Whether the percentage type can be chosen (premium).
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$rows = ! empty( $tiers ) ? $tiers : array( array( 'from' => '', 'discount' => '' ) );

// a premium type the plan cannot apply is never preselected
$discount_type = ! $percentage_available && 'percentage' === $discount_type ? 'fixed' : $discount_type;
?>
<div class="tiered-pricing-form-block">
	<label for="tpt_order_basis"><?php esc_html_e( 'Tiers on', 'tier-pricing-table' ); ?></label>
	<select name="tpt_order_basis" id="tpt_order_basis">
		<option value="amount" <?php selected( 'amount', $basis ); ?>>
			<?php esc_html_e( 'The subtotal of the counted lines', 'tier-pricing-table' ); ?>
		</option>
		<option value="quantity" <?php selected( 'quantity', $basis ); ?>>
			<?php esc_html_e( 'The number of items in the counted lines', 'tier-pricing-table' ); ?>
		</option>
	</select>
</div>

<div class="tiered-pricing-form-block">
	<label for="tpt_order_discount_type"><?php esc_html_e( 'Discount type', 'tier-pricing-table' ); ?></label>
	<select name="tpt_order_discount_type" id="tpt_order_discount_type">
		<option value="fixed" <?php selected( 'fixed', $discount_type ); ?>>
			<?php esc_html_e( 'Fixed amount', 'tier-pricing-table' ); ?>
		</option>
		<option value="percentage" <?php selected( 'percentage', $discount_type ); ?> <?php disabled( ! $percentage_available ); ?>>
			<?php echo esc_html( $percentage_available ? __( 'Percentage', 'tier-pricing-table' ) : __( 'Percentage (premium)', 'tier-pricing-table' ) ); ?>
		</option>
	</select>
</div>

<div class="tiered-pricing-form-block">
	<label><?php esc_html_e( 'Tiers', 'tier-pricing-table' ); ?></label>

	<div class="tiered-pricing-pricing-rules-form tpt-order-tiers" role="form">
		<div class="tiered-pricing-pricing-rules-form__rules">
			<?php foreach ( $rows as $row ) : ?>
				<div class="tiered-pricing-pricing-rules-form-row">
					<div class="tiered-pricing-pricing-rules-form-row__inputs" style="width: 75%;">
						<input type="text"
							   class="wc_input_price"
							   value="<?php echo esc_attr( '' === (string) $row['from'] ? '' : wc_format_localized_price( $row['from'] ) ); ?>"
							   placeholder="<?php esc_attr_e( 'From', 'tier-pricing-table' ); ?>"
							   name="tpt_order_tier_from[]">

						<input type="text"
							   class="wc_input_price"
							   value="<?php echo esc_attr( '' === (string) $row['discount'] ? '' : wc_format_localized_price( $row['discount'] ) ); ?>"
							   placeholder="<?php esc_attr_e( 'Discount', 'tier-pricing-table' ); ?>"
							   name="tpt_order_tier_discount[]">
					</div>
					<div class="tiered-pricing-pricing-rules-form-row__actions">
						<button type="button" class="notice-dismiss tiered-pricing-pricing-rules-form__remove"></button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="tiered-pricing-pricing-rules-form__buttons">
			<button type="button" class="button tiered-pricing-pricing-rules-form__add-new">
				<?php esc_html_e( 'New tier', 'tier-pricing-table' ); ?>
			</button>
		</div>
	</div>

	<p class="description tpt-order-tiers__description">
		<?php esc_html_e( '"From" is a subtotal in the store currency or a number of items, as chosen above. A fixed discount is an amount in the store currency; a percentage one is a number such as 10, taken off the discounted lines.', 'tier-pricing-table' ); ?>
	</p>
</div>
