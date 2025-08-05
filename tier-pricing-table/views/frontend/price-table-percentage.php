<?php
/**
 * Percentage pricing table
 *
 * @var array $price_rules
 * @var string $real_price
 * @var string $product_name
 * @var int $product_id
 * @var int $minimum
 * @var array $settings
 */

use TierPricingTable\PriceManager;

if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * @var array $price_rules
 * @var string $real_price
 * @var int $product_id ;
 */
?>

<?php if ( ! empty( $price_rules ) ) : ?>
    <div class="clear"></div>
    <div class="price-rules-table-wrapper">
		<?php if ( ! empty( $settings['table_title'] ) ) : ?>

            <h3 style="clear:both;  margin: 20px 0;"><?php echo esc_attr( $settings['table_title'] ); ?></h3>
		<?php endif; ?>

		<?php do_action( 'before_percentage_tier_pricing_table' ); ?>

        <table class="shop_table price-rules-table <?php echo esc_attr( $settings['table_css_class'] ); ?>"
               data-price-rules-table
               data-product-id="<?php echo esc_attr( $product_id ); ?>"
               data-price-rules="<?php echo esc_attr( json_encode( $price_rules ) ); ?>"
               data-minimum="<?php echo esc_attr( $minimum ); ?>"
               data-product-name="<?php echo esc_attr( $product_name ); ?>">

			<?php if ( '' != $settings['head_quantity_text'] && '' != $settings['head_price_text'] ) : ?>
                <thead>
                <tr>
                    <th>
                        <span class="nobr"><?php echo esc_attr( sanitize_text_field( $settings['head_quantity_text'] ) ); ?></span>
                    </th>
					<?php if ( 'yes' === $settings['show_discount_column'] ) : ?>
                        <th>
                            <span class="nobr"><?php echo esc_attr( sanitize_text_field( $settings['head_discount_text'] ) ); ?></span>
                        </th>
					<?php endif; ?>
                    <th>
                        <span class="nobr"><?php echo esc_attr( sanitize_text_field( $settings['head_price_text'] ) ); ?></span>
                    </th>
					<?php
					do_action( 'tier_pricing_table_percentage_header_columns', $price_rules, $real_price,
						$product_id );
					?>
                </tr>
                </thead>
			<?php endif; ?>

            <tbody>
            <tr data-price-rules-amount="<?php echo esc_attr( $minimum ); ?>"
                data-price-rules-price="
				<?php
			    echo esc_attr( wc_get_price_to_display( wc_get_product( $product_id ),
				    array( 'price' => $real_price, ) ) );
			    ?>
				"
                data-price-rules-price-exclude-taxes="
				<?php
			    echo esc_attr( wc_get_price_excluding_tax( wc_get_product( $product_id ),
				    array( 'price' => $real_price, ) ) );
			    ?>
				"
                data-price-rules-row>
                <td>
					<?php if ( 1 >= array_keys( $price_rules )[0] - $minimum || $settings['quantity_type'] === "static" ) : ?>
                        <span><?php echo esc_attr( number_format_i18n( $minimum ) ); ?></span>
					<?php else : ?>
                        <span><?php echo esc_attr( number_format_i18n( $minimum ) ); ?> - <?php echo esc_attr( number_format_i18n( array_keys( $price_rules )[0] - 1 ) ); ?></span>
					<?php endif; ?>
                </td>
				<?php if ( 'yes' === $settings['show_discount_column'] ) : ?>
                    <td>
                        —
                    </td>
				<?php endif; ?>
                <td>
					<span data-price-rules-formated-price>
						<?php
						echo wp_kses_post( wc_price( wc_get_price_to_display( wc_get_product( $product_id ),
							array( 'price' => $real_price, ) ) ) );
						?>
					</span>
                </td>
            </tr>

			<?php $iterator = new ArrayIterator( $price_rules ); ?>

			<?php while ( $iterator->valid() ) : ?>
				<?php
				$percentageDiscount = $iterator->current();
				$current_quantity   = $iterator->key();

				$iterator->next();

				$quantity = number_format_i18n( $current_quantity );

				if ( $iterator->valid() ) {

					if ( intval( $iterator->key() - 1 != $current_quantity ) && $settings['quantity_type'] === 'range' ) {
						$quantity .= ' - ' . number_format_i18n( intval( $iterator->key() - 1 ) );
					}

				} else {
					$quantity .= '+';
				}
				?>
                <tr data-price-rules-amount="<?php echo esc_attr( $current_quantity ); ?>"
                    data-price-rules-price="
					<?php
				    echo esc_attr( PriceManager::getPriceByRules( $current_quantity,
					    $product_id ) );
				    ?>
					"
                    data-price-rules-price-exclude-taxes="
					<?php
				    echo esc_attr(
					    wc_get_price_excluding_tax( wc_get_product( $product_id ),
						    array(
							    'price' => PriceManager::getPriceByRules( $current_quantity,
								    $product_id )
						    ) ) )
				    ?>
					"
                    data-price-rules-row>
                    <td>
                        <span><?php echo esc_attr( $quantity ); ?></span>
                    </td>
					<?php if ( 'yes' === $settings['show_discount_column'] ) : ?>
                        <td>
                            <span><?php echo esc_attr( round( $percentageDiscount, 2 ) ); ?> %</span>
                        </td>
					<?php endif; ?>
                    <td>
						<span data-price-rules-formated-price>
							<?php
							echo wp_kses_post( wc_price( PriceManager::getPriceByRules( $current_quantity,
								$product_id ) ) );
							?>
						</span>
                    </td>
                </tr>

				<?php
				do_action( 'tier_pricing_table_percentage_body_columns', $iterator, $price_rules, $real_price,
					$product_id );
				?>

			<?php endwhile; ?>

            </tbody>
        </table>

		<?php do_action( 'after_percentage_tier_pricing_table' ); ?>

    </div>

    <style>
        <?php
		if (  'yes' === $settings['clickable_table_rows']   && tpt_fs()->is_premium()) {
			echo '.price-rules-table-wrapper table tr {cursor: pointer; }';
		}
		?>

        .price-rule-active td {
            background-color: <?php echo esc_attr($settings['selected_quantity_color']); ?> !important;
        }

        .price-rules-table-wrapper {
        <?php echo ( 'tooltip' ===  $settings['display_type']  ||  'yes' !== $settings['display'] ) ? 'display: none; ' : ''; ?>
        }
    </style>
<?php endif; ?>