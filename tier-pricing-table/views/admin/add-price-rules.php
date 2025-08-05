<?php if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Available variables
 *
 * @var bool $isFree
 * @var string $prefix
 * @var string $type
 */
?>

    <script>
        jQuery(document).ready(function ($) {
            $('[data-tiered-price-type-select]').on('change', function () {
                $('[data-tiered-price-type]').css('display', 'none');
                $('[data-tiered-price-type-' + this.value + ']').css('display', 'block');
            });
        });
    </script>

    <p class="form-field">
        <label for="tiered-price-type-select"><?php _e( "Tiered pricing type", 'tier-pricing-table' ); ?></label>
        <select name="tiered_price_rules_type_<?php echo $prefix ?>" id="tiered-price-type-select" style="width: 50%"
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

    <p class="form-field <?php echo $type === 'percentage' ? 'hidden' : ''; ?>" data-tiered-price-type-fixed
       data-tiered-price-type>
        <label><?php _e( "Tiered price", 'tier-pricing-table' ); ?></label>
        <span data-price-rules-wrapper>
        <?php if ( ! empty( $price_rules_fixed ) ): ?>
	        <?php foreach ( $price_rules_fixed as $amount => $price ): ?>
                <span data-price-rules-container>
                    <span data-price-rules-input-wrapper>
                        <input type="number" value="<?php echo $amount; ?>" min="2"
                               placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                               class="price-quantity-rule price-quantity-rule--simple"
                               name="tiered_price_fixed_quantity_<?php echo $prefix ?>[]">
                        <input type="text" value="<?php echo wc_format_localized_price( $price ); ?>"
                               placeholder="<?php _e( 'Price', 'tier-pricing-table' ); ?>"
                               class="wc_input_price price-quantity-rule--simple"
                               name="tiered_price_fixed_price_<?php echo $prefix ?>[]">
                    </span>
                    <span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
                    <br>
                    <br>
                </span>

	        <?php endforeach; ?>
        <?php endif; ?>

        <span data-price-rules-container>
            <span data-price-rules-input-wrapper>
                <input type="number" min="2" placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                       class="price-quantity-rule price-quantity-rule--simple"
                       name="tiered_price_fixed_quantity_<?php echo $prefix ?>[]">
                <input type="text" placeholder="<?php _e( 'Price', 'tier-pricing-table' ); ?>"
                       class="wc_input_price  price-quantity-rule--simple"
                       name="tiered_price_fixed_price_<?php echo $prefix ?>[]">
            </span>
            <span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
            <br>
            <br>
        </span>
    <button data-add-new-price-rule class="button"><?php _e( 'New tier', 'tier-pricing-table' ); ?></button>
    </span>
    </p>

    <p class="form-field <?php echo $type === 'fixed' ? 'hidden' : ''; ?>" data-tiered-price-type-percentage
       data-tiered-price-type>
        <label><?php _e( "Tiered price", 'tier-pricing-table' ); ?></label>
        <span data-price-rules-wrapper>
        <?php if ( ! empty( $price_rules_percentage ) ): ?>
	        <?php foreach ( $price_rules_percentage as $amount => $discount ): ?>
                <span data-price-rules-container>
                    <span data-price-rules-input-wrapper>
                        <input type="number" value="<?php echo $amount; ?>" min="2"
                               placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                               class="price-quantity-rule price-quantity-rule--simple"
                               name="tiered_price_percent_quantity_<?php echo $prefix ?>[]">
                        <input type="number" value="<?php echo $discount; ?>" max="99"
                               placeholder="<?php _e( 'Percent discount', 'tier-pricing-table' ); ?>"
                               class="price-quantity-rule--simple"
                               name="tiered_price_percent_discount_<?php echo $prefix ?>[]" step="any">
                    </span>
                    <span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
                    <br>
                    <br>
                </span>

	        <?php endforeach; ?>
        <?php endif; ?>

        <span data-price-rules-container>
            <span data-price-rules-input-wrapper>
                <input type="number" min="2" placeholder="<?php _e( 'Quantity', 'tier-pricing-table' ); ?>"
                       class="price-quantity-rule price-quantity-rule--simple"
                       name="tiered_price_percent_quantity_<?php echo $prefix ?>[]">
                <input type="number" max="99" placeholder="<?php _e( 'Percent discount', 'tier-pricing-table' ); ?>"
                       class="price-quantity-rule--simple" name="tiered_price_percent_discount_<?php echo $prefix ?>[]"
                       step="any">
            </span>
            <span class="notice-dismiss remove-price-rule" data-remove-price-rule></span>
            <br>
            <br>
        </span>
    <button data-add-new-price-rule class="button"><?php _e( 'New tier', 'tier-pricing-table' ); ?></button>

    </span>
    </p>

<?php wp_nonce_field( 'save_simple_product_tier_price_data', '_simple_product_tier_nonce' ); ?>