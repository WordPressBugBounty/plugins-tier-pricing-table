<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscount;
use Exception;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Core\ServiceContainerTrait;

/**
 * What the rule changes, only when set: base price, the tiers as a small table, quantity limits.
 */
class Pricing {

	use ServiceContainerTrait;

	public function getName(): string {
		return __( 'Pricing', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		try {
			$rule->validatePricing();
		} catch ( Exception $e ) {
			?>
			<div class="tpt-rules-list__line tpt-rules-list__warn">
				<span class="dashicons dashicons-warning"></span>
				<span><?php echo esc_html( $e->getMessage() ); ?></span>
			</div>
			<?php
		}

		if ( $rule->isOrderRule() ) {
			$this->renderOrderTiers( $rule );

			return;
		}

		$this->renderBasePrice( $rule );
		$this->renderTiers( $rule );
		$this->renderQuantityLimits( $rule );
	}

	/**
	 * An order rule: the scope, its minimum order, and its tiers as a small table.
	 */
	protected function renderOrderTiers( GlobalPricingRule $rule ) {
		$chips = array();

		if ( $rule->getOrderMinimumAmount() ) {
			/* translators: %s: amount */
			$chips[] = sprintf( __( 'Min <b>%s</b>', 'tier-pricing-table' ), wc_price( $rule->getOrderMinimumAmount() ) );
		}

		if ( $rule->getOrderMinimumQuantity() ) {
			/* translators: %s: number of items */
			$chips[] = sprintf( __( 'Min <b>%s items</b>', 'tier-pricing-table' ), esc_html( number_format_i18n( $rule->getOrderMinimumQuantity() ) ) );
		}

		if ( $chips ) {
			$this->renderLine( __( 'Minimum order', 'tier-pricing-table' ), $chips );
		}

		if ( $rule->hasOrderFreeShipping() ) {
			if ( 'always' === $rule->getOrderFreeShipping() ) {
				$chip = __( '<b>always</b>', 'tier-pricing-table' );
			} else {
				$from = 'quantity' === $rule->getOrderFreeShipping()
					? esc_html( OrderDiscount::formatThreshold( (float) $rule->getOrderFreeShippingFrom(), true ) )
					: wc_price( $rule->getOrderFreeShippingFrom() );
				/* translators: %s: subtotal or number of items */
				$chip = sprintf( __( 'from <b>%s</b>', 'tier-pricing-table' ), $from );
			}
			$chips = array( $chip );
			if ( $rule->isOrderShippingBarEnabled() ) {
				$chips[] = __( 'with the progress bar', 'tier-pricing-table' );
			}
			$this->renderLine( __( 'Free shipping', 'tier-pricing-table' ), $chips );
		}

		$tiers = $rule->getOrderTiers();

		if ( empty( $tiers ) ) {
			return;
		}

		$percentage = 'percentage' === $rule->getOrderDiscountType();
		$items      = $rule->isOrderBasisQuantity();
		$label      = sprintf(
			/* translators: 1: "fixed amount off" or "percentage off", 2: "by subtotal" or "by item count" */
			__( 'Cart discount (%1$s, %2$s)', 'tier-pricing-table' ),
			$percentage ? __( 'percentage off', 'tier-pricing-table' ) : __( 'fixed amount off', 'tier-pricing-table' ),
			$items ? __( 'by item count', 'tier-pricing-table' ) : __( 'by subtotal', 'tier-pricing-table' )
		);
		?>
		<div class="tpt-rules-list__line tpt-rules-list__line--chips">
			<span class="tpt-rules-list__muted"><?php echo esc_html( $label ); ?></span>
		</div>
		<table class="tpt-rules-list__tiers">
			<thead>
			<tr>
				<th><?php echo $items ? esc_html__( 'Items', 'tier-pricing-table' ) : esc_html__( 'Subtotal', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Discount', 'tier-pricing-table' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $tiers as $tier ) : ?>
				<tr>
					<td><?php echo $items ? esc_html( number_format_i18n( (int) ceil( $tier['from'] ) ) ) : wp_kses_post( wc_price( $tier['from'] ) ); ?>+</td>
					<td class="is-tier"><?php echo $percentage ? esc_html( OrderDiscount::formatDiscount( (float) $tier['discount'], true ) ) : wp_kses_post( wc_price( $tier['discount'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	protected function renderBasePrice( GlobalPricingRule $rule ) {
		$chips = array();

		if ( 'flat' === $rule->getPricingType() ) {
			if ( $rule->getRegularPrice() ) {
				$chips[] = __( 'Regular', 'tier-pricing-table' ) . ' <b>' . wc_price( $rule->getRegularPrice() ) . '</b>';
			}
			if ( $rule->getSalePrice() ) {
				$chips[] = __( 'Sale', 'tier-pricing-table' ) . ' <b>' . wc_price( $rule->getSalePrice() ) . '</b>';
			}
		} elseif ( $rule->getDiscount() ) {
			$chips[] = sprintf(
				/* translators: 1: percentage, 2: "sale price" or "regular price" */
				__( 'Discount <b>%1$s%%</b> off the %2$s', 'tier-pricing-table' ),
				esc_html( $rule->getDiscount() ),
				'sale_price' === $rule->getDiscountType() ? __( 'sale price', 'tier-pricing-table' ) : __( 'regular price', 'tier-pricing-table' )
			);
		}

		if ( $chips ) {
			$this->renderLine( __( 'Base price', 'tier-pricing-table' ), $chips );
		}
	}

	/**
	 * The tiers as a small table: quantity ranges (or thresholds, per the plugin's quantity type
	 * setting) and the price or discount, with a base row for the quantities below the first tier.
	 */
	protected function renderTiers( GlobalPricingRule $rule ) {
		$percentage = 'percentage' === $rule->getTieredPricingType();
		$amountOff  = 'amount' === $rule->getTieredPricingType();
		$tiers      = $percentage ? $rule->getPercentageTieredPricingRules() : ( $amountOff ? $rule->getAmountTieredPricingRules() : $rule->getFixedTieredPricingRules() );

		if ( empty( $tiers ) ) {
			return;
		}

		ksort( $tiers );
		$quantities = array_map( 'intval', array_keys( $tiers ) );
		$values     = array_values( $tiers );
		$minimum    = $rule->getMinimum() ? (int) $rule->getMinimum() : 1;
		$ranges     = 'range' === $this->getContainer()->getSettings()->get( 'quantity_type', 'range' );

		// what the customer pays below the first tier
		$base = __( 'Regular price', 'tier-pricing-table' );
		if ( 'flat' === $rule->getPricingType() ) {
			if ( $rule->getSalePrice() ) {
				$base = wc_price( $rule->getSalePrice() );
			} elseif ( $rule->getRegularPrice() ) {
				$base = wc_price( $rule->getRegularPrice() );
			}
		} elseif ( $rule->getDiscount() ) {
			/* translators: %s: percentage */
			$base = esc_html( sprintf( __( '%s%% off', 'tier-pricing-table' ), $rule->getDiscount() ) );
		}

		$rows = array();
		if ( $quantities[0] > $minimum ) {
			$rows[] = array( $this->quantityLabel( $minimum, $quantities[0] - 1, $ranges ), $base, false );
		}

		foreach ( $quantities as $index => $quantity ) {
			$next    = $quantities[ $index + 1 ] ?? null;
			$label   = null === $next ? number_format_i18n( $quantity ) . '+' : $this->quantityLabel( $quantity, $next - 1, $ranges );
			if ( $percentage ) {
				$value = esc_html( $values[ $index ] . '%' );
			} elseif ( $amountOff ) {
				/* translators: %s: amount */
				$value = sprintf( esc_html__( '%s off', 'tier-pricing-table' ), wc_price( $values[ $index ] ) );
			} else {
				$value = wc_price( $values[ $index ] );
			}
			$rows[]  = array( $label, $value, true );
		}

		$label = sprintf(
			/* translators: 1: "fixed" or "percentage", 2: "individual" or "mix & match" */
			__( 'Tiers (%1$s, %2$s)', 'tier-pricing-table' ),
			$percentage ? __( 'percentage', 'tier-pricing-table' ) : __( 'fixed', 'tier-pricing-table' ),
			'individual' === $rule->getApplyingType() ? __( 'individual', 'tier-pricing-table' ) : __( 'mix & match', 'tier-pricing-table' )
		);
		?>
		<div class="tpt-rules-list__line tpt-rules-list__line--chips">
			<span class="tpt-rules-list__muted"><?php echo esc_html( $label ); ?></span>
		</div>
		<table class="tpt-rules-list__tiers">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Quantity', 'tier-pricing-table' ); ?></th>
				<th><?php echo $percentage ? esc_html__( 'Discount', 'tier-pricing-table' ) : esc_html__( 'Price', 'tier-pricing-table' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $rows as list( $quantityLabel, $value, $isTier ) ) : ?>
				<tr>
					<td><?php echo esc_html( $quantityLabel ); ?></td>
					<td class="<?php echo $isTier ? 'is-tier' : ''; ?>"><?php echo wp_kses_post( $value ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * "10 - 29" with ranges on, "10" otherwise (or when the range is a single quantity).
	 */
	protected function quantityLabel( int $from, int $to, bool $ranges ): string {
		if ( $to <= $from || ! $ranges ) {
			return number_format_i18n( $from );
		}

		return number_format_i18n( $from ) . ' - ' . number_format_i18n( $to );
	}

	protected function renderQuantityLimits( GlobalPricingRule $rule ) {
		$chips = array();

		if ( $rule->getMinimum() ) {
			/* translators: %s: quantity */
			$chips[] = sprintf( __( 'Min <b>%s</b>', 'tier-pricing-table' ), esc_html( number_format_i18n( $rule->getMinimum() ) ) );
		}
		if ( ! empty( $rule->data['maximum_quantity'] ) ) {
			/* translators: %s: quantity */
			$chips[] = sprintf( __( 'Max <b>%s</b>', 'tier-pricing-table' ), esc_html( number_format_i18n( (int) $rule->data['maximum_quantity'] ) ) );
		}
		if ( ! empty( $rule->data['group_of_quantity'] ) ) {
			/* translators: %s: quantity */
			$chips[] = sprintf( __( 'Step <b>%s</b>', 'tier-pricing-table' ), esc_html( number_format_i18n( (int) $rule->data['group_of_quantity'] ) ) );
		}

		if ( $chips ) {
			$this->renderLine( __( 'Quantity', 'tier-pricing-table' ), $chips );
		}
	}

	/**
	 * @param  string[]  $chips  Escaped HTML.
	 */
	protected function renderLine( string $label, array $chips ) {
		?>
		<div class="tpt-rules-list__line tpt-rules-list__line--chips">
			<span class="tpt-rules-list__muted"><?php echo esc_html( $label ); ?></span>
			<?php foreach ( $chips as $chip ) : ?>
				<span class="tpt-rules-list__chip"><?php echo wp_kses_post( $chip ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
