<?php
	
	use TierPricingTable\Forms\Form;
	
	if ( ! defined( 'WPINC' ) ) {
		die;
	}
	
	/**
	 * Available variables
	 *
	 * @var string $role
	 * @var string $loop
	 * @var string $minimum_order_quantity
	 */
?>

<p class="form-field tpt_minimum_order_quantity <?php echo esc_attr( ! is_null( $loop ) ? 'form-row' : '' ); ?>">
    <label for="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>">
		<?php esc_html_e( 'Minimum order quantity', 'tier-pricing-table' ); ?>
    </label>
	
	<?php
		echo wp_kses_post( wc_help_tip( __( 'A minimum amount of a product a customer must order to fulfill the order.',
			'tier-pricing-table' ) ) );
	?>

    <input <?php echo ! tpt_fs()->can_use_premium_code() ? 'disabled' : ''; ?>
            type="number"
            class="short"
            min="1"
            step="1"
            style="<?php echo esc_attr( tpt_fs()->can_use_premium_code() ? '' : 'cursor: not-allowed' ); ?>"
            placeholder="<?php esc_attr_e( 'Leave empty, so don\'t add any restrictions',
				'tier-pricing-table' ); ?>"
            name="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>"
            id="<?php echo esc_attr( Form::getFieldName( 'minimum_order_quantity', $role, $loop ) ); ?>"
            value="<?php echo esc_attr( $minimum_order_quantity ); ?>"
    >

</p>
