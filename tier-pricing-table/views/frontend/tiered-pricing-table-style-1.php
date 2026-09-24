<?php
	/**
	 * Available variables
	 *
	 * @var PricingRule $pricing_rule
	 * @var array $price_rules
	 * @var string $pricing_type
	 * @var string $real_price
	 * @var string $product_name
	 * @var WC_Product $product
	 * @var string $id
	 * @var int $product_id
	 * @var int $minimum
	 * @var array $settings
	 */

	use TierPricingTable\CalculationLogic;
	use TierPricingTable\PriceManager;
	use TierPricingTable\PricingTable;
	use TierPricingTable\PricingRule;
	use TierPricingTable\Settings\Settings;

	if ( ! defined( 'WPINC' ) ) {
		die;
	}

	$sale_price = $product->get_sale_price();

	if ( $sale_price ) {
		$sale_price = wc_get_price_to_display( $product, array(
				'price' => $sale_price,
		) );
	}

	$regular_price = wc_get_price_to_display( $product, array(
			'price' => $product->get_regular_price(),
	) );

	$price = wc_get_price_to_display( $product, array(
			'price' => $product->get_price(),
	) );

	$price_incl_taxes = wc_get_price_including_tax( wc_get_product( $product_id ), array(
			'price' => $real_price,
	) );

	$price_excl_taxes = wc_get_price_excluding_tax( wc_get_product( $product_id ), array(
			'price' => $real_price,
	) );

	// discount of every tier (percent), for the biggest discount
	$tierDiscounts = array();
	foreach ( $pricing_rule->getRules() as $tierQuantity => $tierPrice ) {
		$tierDiscounts[ $tierQuantity ] = 'percentage' === $pricing_rule->getType()
			? (float) $tierPrice
			: (float) PriceManager::calculateDiscount( CalculationLogic::calculateDiscountBasedOnRegularPrice() ? $product->get_regular_price() : $product->get_price(),
				$pricing_rule->getTierPrice( $tierQuantity, false ) );
	}
	$maxDiscount = $tierDiscounts ? max( $tierDiscounts ) : 0;

?>

<?php if ( ! empty( $pricing_rule->getRules() ) ) : ?>
	<div class="clear"></div>

	<div class="tiered-pricing-wrapper">
		<?php if ( ! empty( $settings['title'] ) ) : ?>
			<h3 style="clear:both; margin: 20px 0; font-weight: 600;"><?php echo esc_attr( $settings['title'] ); ?></h3>
		<?php endif; ?>

		<?php do_action( 'tiered_pricing_table/tiered_pricing/before', $pricing_rule ); ?>

		<?php
			$columns = 0;
			if ( $settings['quantity_column_title'] ) $columns++;
			if ( $settings['discount_column_title'] ) $columns++;
			if ( $settings['price_column_title'] ) $columns++;

			// Detect custom columns via output buffering
			ob_start();
			do_action( 'tiered_pricing_table/tiered_pricing/header_columns', $pricing_rule );
			$custom_head = ob_get_clean();
			$columns += substr_count( $custom_head, '<th' );
		?>

		<div class="tiered-pricing-table tiered-pricing-table--styled tiered-pricing-table--style-1 <?php echo ( isset( $settings['compact_layout'] ) && $settings['compact_layout'] === 'yes' ) ? 'tiered-pricing-table--slim' : ''; ?>"

		     <?php echo PricingTable::layoutStyleAttribute( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper ?>
		     id="<?php echo esc_attr( $id ); ?>"
		     data-tiered-pricing-table
		     data-product-id="<?php echo esc_attr( $product_id ); ?>"
		     data-price-rules="<?php echo esc_attr( json_encode( $pricing_rule->getRules() ) ); ?>"
		     data-minimum="<?php echo esc_attr( $minimum ); ?>"
		     data-product-name="<?php echo esc_attr( $product_name ); ?>"
		     data-regular-price="<?php echo esc_attr( $regular_price ); ?>"
		     data-sale-price="<?php echo esc_attr( $sale_price ); ?>"
		     data-price="<?php echo esc_attr( $price ); ?>"
		     data-product-price-suffix="<?php echo esc_attr( $product->get_price_suffix() ); ?>"
		>
			<div class="tiered-pricing-table-header" style="grid-template-columns: repeat(<?php echo (int) $columns; ?>, 1fr);">
				<?php if ( $settings['quantity_column_title'] ) : ?>
					<div class="tiered-pricing-table-header-qty"><?php echo esc_attr( $settings['quantity_column_title'] ); ?></div>
				<?php endif; ?>

				<?php if ( $settings['discount_column_title'] ) : ?>
					<div class="tiered-pricing-table-header-discount"><?php echo esc_attr( $settings['discount_column_title'] ); ?></div>
				<?php endif; ?>

				<?php if ( $settings['price_column_title'] ) : ?>
					<div class="tiered-pricing-table-header-price"><?php echo esc_attr( $settings['price_column_title'] ); ?></div>
				<?php endif; ?>

				<?php echo wp_kses_post( str_replace( array( '<th', '</th>' ), array( '<div', '</div>' ), $custom_head ) );?>
			</div>

			<div class="tiered-pricing-table-body">
				<?php $tptTierRows = array(); ob_start(); ?>
				<div class="tiered-pricing-table-row tiered-pricing--active"
				     data-tiered-quantity="<?php echo esc_attr( $minimum ); ?>"
				     data-tiered-price="<?php echo esc_attr( $price ); ?>"
				     data-tiered-price-exclude-taxes="<?php echo esc_attr( $price_excl_taxes ); ?>"
				     data-tiered-price-include-taxes="<?php echo esc_attr( $price_incl_taxes ); ?>"
				     style="grid-template-columns: repeat(<?php echo (int) $columns; ?>, 1fr);">

					<?php if ( $settings['quantity_column_title'] ) : ?>
						<div>
							<?php if ( 1 >= array_keys( $pricing_rule->getRules() )[0] - $minimum || 'static' === $settings['quantity_type'] ) : ?>
								<span>
									<span class="tiered-pricing-table-row-qty"><?php echo esc_attr( number_format_i18n( $minimum ) ); ?></span>
									<span class="tiered-pricing-table-row-qty-unit"><?php echo esc_attr( ' ' . ( $minimum > 1 ? $settings['quantity_measurement_plural'] : $settings['quantity_measurement_singular'] ) ); ?></span>
								</span>
							<?php else : ?>
								<span>
									<span class="tiered-pricing-table-row-qty"><?php echo esc_attr( number_format_i18n( $minimum ) ); ?> - <?php echo esc_attr( number_format_i18n( array_keys( $pricing_rule->getRules() )[0] - 1 ) ); ?></span>
									<span class="tiered-pricing-table-row-qty-unit"><?php echo esc_attr( ' ' . $settings['quantity_measurement_plural'] ); ?></span>
								</span>
							<?php endif; ?>

							<?php do_action( 'tiered_pricing_table/table/label', $pricing_rule, $minimum, array(
									'id'    => $id,
									'style' => 'default',
							) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $settings['discount_column_title'] ) : ?>
						<?php
						$discountAmount = 0;
						if ( CalculationLogic::calculateDiscountBasedOnRegularPrice() && $product->is_on_sale() ) {
							$discountAmount = PriceManager::calculateDiscount( $product->get_regular_price(),
									$product->get_sale_price() );
						}
						?>
						<div>
							<?php if ( $discountAmount > 0 ) : ?>
								<span class="tiered-pricing-table-row-discount"><?php echo wp_kses_post( PricingTable::formatDiscount( $discountAmount, $pricing_rule, $product, null, $settings ) ); ?></span>
							<?php else : ?>
								<span class="tiered-pricing-table-row-discount tiered-pricing-table-row-discount--empty">&mdash;</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $settings['price_column_title'] ) : ?>
						<div class="tiered-pricing-table-row-price">
							<?php
								echo wp_kses_post( wc_price( wc_get_price_to_display( wc_get_product( $product_id ),
										array( 'price' => $real_price ) ) ) );
							?>
						</div>
					<?php endif; ?>

					<?php
						ob_start();
						do_action( 'tiered_pricing_table/tiered_pricing/row_columns', $pricing_rule, null );
						$custom_cols = ob_get_clean();
						echo wp_kses_post( str_replace( array( '<td', '</td>' ), array( '<div', '</div>' ), $custom_cols ) );
					?>
				</div>
				<?php $tptTierRows[] = ob_get_clean(); ?>

				<?php $iterator = new ArrayIterator( $pricing_rule->getRules() ); ?>

				<?php while ( $iterator->valid() ) : ob_start(); ?>
					<?php
					$currentPrice    = $iterator->current();
					$currentQuantity = $iterator->key();

					$iterator->next();

					$discountAmount = $tierDiscounts[ $currentQuantity ];

					$quantity = number_format_i18n( $currentQuantity );

					if ( $iterator->valid() ) {

						if ( intval( $iterator->key() - 1 != $currentQuantity ) && 'range' === $settings['quantity_type'] ) {
							$quantity .= ' - ' . number_format_i18n( intval( $iterator->key() - 1 ) );
						}

					} else {
						$quantity .= apply_filters( 'tiered_pricing_table/tiered_pricing/last_tier_postfix', '+',
								$currentQuantity, $pricing_rule, 'table' );
					}

					$quantityUnit = $settings['quantity_measurement_plural'];

					$currentProductPrice = $pricing_rule->getTierPrice( $currentQuantity );

					$currentProductPriceExcludeTaxes = wc_get_price_excluding_tax( wc_get_product( $product_id ), array(
							'price' => $pricing_rule->getTierPrice( $currentQuantity, false ),
					) );

					$currentProductPriceIncludeTaxes = wc_get_price_including_tax( wc_get_product( $product_id ), array(
							'price' => $pricing_rule->getTierPrice( $currentQuantity, false ),
					) );

					?>
					<div class="tiered-pricing-table-row"
					     data-tiered-quantity="<?php echo esc_attr( $currentQuantity ); ?>"
					     data-tiered-price="<?php echo esc_attr( $currentProductPrice ); ?>"
					     data-tiered-price-exclude-taxes="<?php echo esc_attr( $currentProductPriceExcludeTaxes ); ?>"
					     data-tiered-price-include-taxes="<?php echo esc_attr( $currentProductPriceIncludeTaxes ); ?>"
					     style="grid-template-columns: repeat(<?php echo (int) $columns; ?>, 1fr);">

						<?php if ( $settings['quantity_column_title'] ) : ?>
							<div>
								<span class="tiered-pricing-table-row-qty"><?php echo esc_attr( $quantity ); ?></span>
								<span class="tiered-pricing-table-row-qty-unit"> <?php echo esc_attr( $quantityUnit ); ?></span>
								<?php do_action( 'tiered_pricing_table/table/label', $pricing_rule, $currentQuantity, array(
										'id'    => $id,
										'style' => 'default',
								) ); ?>
							</div>
						<?php endif; ?>

						<?php if ( $settings['discount_column_title'] ) : ?>
							<div>
								<?php if ( $discountAmount > 0 ) : ?>
									<span class="tiered-pricing-table-row-discount"><?php echo wp_kses_post( PricingTable::formatDiscount( $discountAmount, $pricing_rule, $product, $currentQuantity, $settings ) ); ?></span>
								<?php else : ?>
									<span class="tiered-pricing-table-row-discount tiered-pricing-table-row-discount--empty">&mdash;</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $settings['price_column_title'] ) : ?>
							<div class="tiered-pricing-table-row-price">
								<?php echo wp_kses_post( wc_price( $pricing_rule->getTierPrice( $currentQuantity ) ) ); ?>
							</div>
						<?php endif; ?>

						<?php
							ob_start();
							do_action( 'tiered_pricing_table/tiered_pricing/row_columns', $pricing_rule, $currentQuantity );
							$custom_cols = ob_get_clean();
							echo wp_kses_post( str_replace( array( '<td', '</td>' ), array( '<div', '</div>' ), $custom_cols ) );
						?>
					</div>
				<?php $tptTierRows[] = ob_get_clean(); endwhile; ?>
				<?php echo PricingTable::orderTiers( $tptTierRows, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rows escaped when rendered above ?>
				<?php do_action( 'tiered_pricing_table/tiered_pricing/rows', $pricing_rule, $settings, 'tiered-pricing-table-style-1' ); ?>
				<?php do_action( 'tiered_pricing_table/table/tfoot', $pricing_rule, $settings, 'tiered-pricing-table-style-1' ); ?>
			</div>
		</div>

		<?php do_action( 'tiered_pricing_table/tiered_pricing/after', $pricing_rule, $product_id ); ?>
	</div>

	<style>
		<?php
		if ( $settings['clickable_rows'] && tpt_fs()->can_use_premium_code() ) {
			echo esc_attr( '#' . $id ) . ' .tiered-pricing-table-row { cursor: pointer; }';
		}
		?>

		<?php echo esc_attr( '#' . $id ); ?> .tiered-pricing--active {
			background-color: <?php echo esc_attr( Settings::hex2rgba( $settings['active_tier_color'], 0.06 ) ); ?> !important;
			border-left-color: <?php echo esc_attr( 'no' === ( $settings['active_tier_border'] ?? 'yes' ) ? 'transparent' : $settings['active_tier_color'] ); ?> !important;
		}

		<?php echo esc_attr( '#' . $id ); ?> .tiered-pricing-table-row-discount:not(.tiered-pricing-table-row-discount--empty) {
			color: <?php echo esc_attr( $settings['active_tier_color'] ); ?>;
		}

	</style>
<?php endif; ?>
