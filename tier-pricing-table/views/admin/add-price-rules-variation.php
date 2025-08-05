<?php if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * @var int $i
 * @var int $minimum
 * @var string $type
 * @var bool $isFree
 * @var array $price_rules
 * @var array $price_rules_fixed
 * @var array $price_rules_percentage
 */

?>

<script>
    jQuery(document).on('woocommerce_variations_loaded', function ($) {
        jQuery('[data-tiered-price-type-select]').on('change', function () {
            var $wrapper = jQuery(this).closest('.woocommerce_variation');
            $wrapper.find('[data-tiered-price-type]').css('display', 'none');
            $wrapper.find('[data-tiered-price-type-' + this.value + ']').css('display', 'block');
        });
    });
</script>

<p class="form-field form-row">
    <label for="tiered-price-type-select"><?php _e( "Tiered pricing type", 'tier-pricing-table' ); ?></label>
    <br>
    <select name="tiered_price_rules_type[<?php echo $i; ?>]" id="tiered-price-type-select" style="width: 48%"
            data-tiered-price-type-select>
        <option value="fixed" <?php selected( 'fixed', $type ); ?> ><?php _e( 'Fixed',
				'tier-pricing-table' ); ?></option>

		<?php if ( $isFree ): ?>
            <option disabled><?php _e( 'Percentage (Only in premium version)', 'tier-pricing-table' ); ?></option>
		<?php else: ?>
            <option value="percentage" <?php selected( 'percentage', $type ); ?> ><?php _e( 'Percentage',
					'tier-pricing-table' ); ?></option>
		<?php endif; ?>
    </select>
</p>


<p class="form-field form-row" style="width: 48%">
    <label for="tiered-price-start-from">
		<?php
		esc_attr_e( 'Tiered pricing minimum quantity',
			'tier-pricing-table' );
		?>
    </label>
	<?php
	echo wc_help_tip( __( 'Please note that it uses just for building a table. For real restriction use specialized plugins.',
		'tier-pricing-table' ) );

	$disabled = $isFree ? 'disabled' : '';
	?>
    <br>
    <input type="number" placeholder="1" value="<?php echo esc_attr( $minimum ); ?>" min="1"
           id="tiered-price-start-from"
           name="_tiered_pricing_minimum[<?php echo esc_attr( $i ); ?>]" <?php echo $disabled; ?>>
	<?php if ( $disabled ): ?>
    <p class="description" style="color: red">Available only in the premium version</p>
    <a target="_blank"
       href="<?php echo esc_attr( tpt_fs_activation_url() ) ?>"><?php _e( 'Upgrade', 'tier-pricing-table' ); ?></a>
<?php endif; ?>
</p>

<p class="form-field form-row <?php echo $type === 'percentage' ? 'hidden' : ''; ?>" data-tiered-price-type-fixed
   data-tiered-price-type style="margin-top: 0" data-price-rules-wrapper>
    <label><?php _e( "Tiered price", 'tier-pricing-table' ); ?></label>
    <br>
	<?php $j = 0; ?>
	<?php if ( ! empty( $price_rules_fixed ) ): ?>
		<?php foreach ( $price_rules_fixed as $amount => $price ): ?>
            <span data-price-rules-container>
                <span data-price-rules-input-wrapper>
                    <input type="number" value="<?php echo $amount; ?>" min="2"
                           placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                           class="price-quantity-rule price-quantity-rule--variation"
                           name="tiered_price_fixed_quantity[<?php echo $i; ?>][<?php echo $j; ?>]">
                    <input type="text" value="<?php echo wc_format_localized_price( $price ); ?>"
                           placeholder="<?php _e( 'Price', 'tier-pricing-table' ); ?>"
                           class="wc_input_price price-quantity-rule--variation"
                           name="tiered_price_fixed_price[<?php echo $i; ?>][<?php echo $j; ?>]">
                </span>
                <span class="notice-dismiss remove-price-rule" data-remove-price-rule
                      style="vertical-align: middle;"></span>
                <br>
                <br>
            </span>

			<?php $j ++; ?>

		<?php endforeach; ?>
	<?php endif; ?>

    <span data-price-rules-container>
        <span data-price-rules-input-wrapper>
            <input type="number" min="2" placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                   class="price-quantity-rule price-quantity-rule--variation"
                   name="tiered_price_fixed_quantity[<?php echo $i; ?>][<?php echo $j; ?>]">
            <input type="text" placeholder="<?php _e( 'Price', 'tier-pricing-table' ); ?>"
                   class="wc_input_price  price-quantity-rule--variation"
                   name="tiered_price_fixed_price[<?php echo $i; ?>][<?php echo $j; ?>]">
        </span>
        <span class="notice-dismiss remove-price-rule" data-remove-price-rule style="vertical-align: middle;"></span>
        <br>
        <br>
    </span>

    <button class="button" data-add-new-price-rule><?php _e( "New tier", 'tier-pricing-table' ); ?></button>
</p>

<p class="form-field form-row <?php echo $type === 'fixed' ? 'hidden' : ''; ?>" data-tiered-price-type-percentage
   data-tiered-price-type style="margin-top: 0" data-price-rules-wrapper>
    <label><?php _e( "Tiered price", 'tier-pricing-table' ); ?></label>
    <br>

	<?php $k = 0; ?>

	<?php if ( ! empty( $price_rules_percentage ) ): ?>

		<?php foreach ( $price_rules_percentage as $amount => $discount ): ?>
            <span data-price-rules-container>
                <span data-price-rules-input-wrapper>
                    <input type="number" value="<?php echo $amount; ?>" min="2"
                           placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                           class="price-quantity-rule price-quantity-rule--variation"
                           name="tiered_price_percent_quantity[<?php echo $i; ?>][<?php echo $k; ?>]">
                    <input type="number" value="<?php echo $discount; ?>"
                           placeholder="<?php _e( 'Percent discount', 'tier-pricing-table' ); ?>"
                           class="wc_input_price price-quantity-rule--variation"
                           name="tiered_price_percent_discount[<?php echo $i; ?>][<?php echo $k; ?>]"
                           step="any"
                    >
                </span>
                <span class="notice-dismiss remove-price-rule" data-remove-price-rule
                      style="vertical-align: middle;"></span>
                <br>
                <br>
            </span>

			<?php $k ++; ?>

		<?php endforeach; ?>
	<?php endif; ?>

    <span data-price-rules-container>
        <span data-price-rules-input-wrapper>
            <input type="number" min="2" placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                   class="price-quantity-rule price-quantity-rule--variation"
                   name="tiered_price_percent_quantity[<?php echo $i; ?>][<?php echo $k; ?>]">
            <input type="text" placeholder="<?php _e( 'Percent discount', 'tier-pricing-table' ); ?>"
                   class="wc_input_price  price-quantity-rule--variation"
                   name="tiered_price_percent_discount[<?php echo $i; ?>][<?php echo $k; ?>]" step="any">
        </span>
        <span class="notice-dismiss remove-price-rule" data-remove-price-rule style="vertical-align: middle;"></span>
        <br>
        <br>
    </span>

    <button class="button" data-add-new-price-rule><?php _e( "New tier", 'tier-pricing-table' ); ?></button>
</p>