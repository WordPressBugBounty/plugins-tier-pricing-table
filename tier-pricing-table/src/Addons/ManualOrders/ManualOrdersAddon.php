<?php namespace TierPricingTable\Addons\ManualOrders;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\CalculationLogic;
use TierPricingTable\PriceManager;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

class ManualOrdersAddon extends AbstractAddon {

	protected $items = array();

	/**
	 * Customer the order is re-calculated for, per order ID.
	 *
	 * @var int[]
	 */
	protected $customers = array();

	public function getName(): string {
		return __( 'Tiered prices in admin orders', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'A "Re-calculate with tiered pricing" button for orders you create or edit, priced for the order\'s customer and role.', 'tier-pricing-table' );
	}

	public function getIcon(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 14H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>';
	}

	public function getSlug(): string {
		return 'manual-orders';
	}

	public function getGroup(): string {
		return self::GROUP_WHOLESALE;
	}

	public function getItemPrice( WC_Order_Item_Product $item ) {
		$product = $item->get_product();

		// Product (or its variation) may have been deleted after the order was created.
		if ( ! $product ) {
			$quantity = $item->get_quantity();

			return $quantity ? ( (float) $item->get_total() / $quantity ) : (float) $item->get_total();
		}

		$productPrice      = $product->get_price();
		$tieredPricingRule = PriceManager::getPricingRule( $product->get_id() );

		// Item has no tiered pricing rule. Return regular price (which also can be adjusted by pricing rule, but on the get_price hook)
		if ( ! $tieredPricingRule->getRules() ) {
			return $productPrice;
		}

		$tieredPrice = $tieredPricingRule->getTierPrice( $this->getTotalItemQuantity( $item ), false );

		return false !== $tieredPrice ? $tieredPrice : $productPrice;
	}

	public function getTotalItemQuantity( WC_Order_Item_Product $baseItem ): int {

		$quantity = $baseItem->get_quantity();

		$orderItems = $baseItem->get_order()->get_items();

		if ( empty( $orderItems ) || count( $orderItems ) < 2 ) {
			return $quantity;
		}

		$calculatedItems = [ $baseItem->get_id() ];

		// Consider different variations
		if ( CalculationLogic::considerProductVariationAsOneProduct() ) {
			foreach ( $orderItems as $item ) {

				/**
				 * Item type
				 *
				 * @var WC_Order_Item_Product $item
				 */

				// Item quantity is already calculated
				if ( in_array( $item->get_id(), $calculatedItems ) ) {
					continue;
				}

				if ( $item->get_product_id() === $baseItem->get_product_id() ) {
					$quantity          += $item->get_quantity();
					$calculatedItems[] = $item->get_id();
				}
			}
		}

		$baseItemPricingRule = PriceManager::getPricingRule( $baseItem->get_product_id() );

		if ( 'global-rules' !== $baseItemPricingRule->provider ) {
			return $quantity;
		}

		if ( empty( $baseItemPricingRule->providerData['applying_type'] ) || 'cross' !== $baseItemPricingRule->providerData['applying_type'] ) {
			return $baseItem->get_quantity();
		}

		foreach ( $orderItems as $item ) {

			// Item quantity is already calculated
			if ( in_array( $item->get_id(), $calculatedItems ) ) {
				continue;
			}

			$pricingRule = PriceManager::getPricingRule( $item->get_product_id() );

			if ( 'global-rules' !== $pricingRule->provider ) {
				continue;
			}

			if ( empty( $pricingRule->providerData['rule_id'] ) || $pricingRule->providerData['rule_id'] !== $baseItemPricingRule->providerData['rule_id'] ) {
				continue;
			}

			$quantity += $item->get_quantity();
		}

		return $quantity;
	}

	/**
	 * The customer to price the order for: the one picked in the order form (sent with the re-calculation, so an
	 * order that is not saved yet is priced for the right customer), or the order's saved customer.
	 *
	 * @param  int  $orderId
	 * @param  array  $items  Posted order items form.
	 *
	 * @return int User ID, 0 for a guest.
	 */
	protected function getRecalculationCustomerId( int $orderId, array $items ): int {
		if ( isset( $items['tiered_pricing_customer_id'] ) ) {
			$customerId = absint( $items['tiered_pricing_customer_id'] );

			return $customerId && get_userdata( $customerId ) ? $customerId : 0;
		}

		$order = wc_get_order( $orderId );

		return $order ? (int) $order->get_customer_id() : 0;
	}

	public function run() {

		// Store items before calculating
		add_action( 'woocommerce_before_save_order_items', function ( $orderId, $items ) {
			$this->items[ $orderId ] = $items;

			if ( empty( $items['calculate_tiered_pricing'] ) ) {
				return;
			}

			$this->customers[ $orderId ] = $this->getRecalculationCustomerId( (int) $orderId, (array) $items );

			$order = wc_get_order( $orderId );

			if ( $order ) {
				/**
				 * Fires before an order created in the admin is re-calculated with tiered pricing.
				 *
				 * @param  WC_Order  $order
				 * @param  int  $customerId  The customer the order is priced for, 0 for a guest.
				 */
				do_action( 'tiered_pricing_table/manual_orders/before_recalculation', $order, $this->customers[ $orderId ] );
			}
		}, 10, 2 );

		add_action( 'woocommerce_before_save_order_item', function ( WC_Order_Item $item ) {

			if ( ! ( $item instanceof WC_Order_Item_Product ) ) {
				return;
			}

			if ( ! apply_filters( 'tiered_pricing_table/manual_created_orders/modify_item_price', true, $item ) ) {
				return;
			}

			// Need to be calculated with tiered pricing
			if ( ! empty( $this->items[ $item->get_order_id() ]['calculate_tiered_pricing'] ) ) {

				$GLOBALS['tpt_current_user_id'] = $this->customers[ $item->get_order_id() ] ?? $item->get_order()->get_customer_id();

				$itemPrice = $this->getItemPrice( $item );

				$product = $item->get_product() ? $item->get_product() : false;

				if ( ! $product ) {
					return;
				}

				$itemTotal = wc_get_price_excluding_tax( $product, array(
						'price' => $itemPrice,
						'qty'   => $item->get_quantity(),
				) );

				// Nothing to change
				if ( ( float ) $item->get_total() === $itemTotal ) {
					return;
				}

				// translators: %1$s: item name, %2$s: original price, %3$s: new price
				$note = sprintf( __( 'Tiered pricing recalculations for %1$s: %2$s → %3$s', 'tier-pricing-table' ),
						$item->get_name(), wc_price( $item->get_total() / $item->get_quantity() ),
						wc_price( $itemPrice ) );

				$item->set_subtotal( $itemTotal );
				$item->set_total( $itemTotal );

				$item->calculate_taxes();

				$item->get_order()->add_order_note( $note );
			}

		}, 10, 2 );

		add_action( 'woocommerce_admin_order_items_after_line_items', function () {
			?>
			<tr style="display: none;">
				<td>
					<input type="checkbox" name="calculate_tiered_pricing">
					<input type="hidden" name="tiered_pricing_customer_id" value="" disabled>
				</td>
			</tr>
			<?php
		} );

		add_action( 'admin_head', function () {
			?>
			<script>
				jQuery(document).ready(function ($) {
					$('#woocommerce-order-items').on('click', '.calculate-tiered-pricing', function () {
						jQuery('[name=calculate_tiered_pricing]').prop('checked', true);

						// Price for the customer picked in the form, even before the order is saved.
						var $customer = jQuery('#customer_user');
						jQuery('[name=tiered_pricing_customer_id]').val($customer.length ? ($customer.val() || '0') : '').prop('disabled', !$customer.length);

						jQuery('#woocommerce-order-items button.calculate-action').trigger('click');
					});
				});

				jQuery(document.body).on('order-totals-recalculate-complete', function () {
					jQuery('[name=calculate_tiered_pricing]').prop('checked', false);
				});
			</script>
			<?php
		} );

		add_action( 'woocommerce_order_item_add_action_buttons', function ( WC_Order $order ) {
			if ( $order->is_editable() ) {
				?>
				<button type="button" class="button button-primary calculate-tiered-pricing">
					<?php esc_html_e( 'Re-calculate with tiered pricing', 'tier-pricing-table' ); ?>
				</button>
				<?php
			}
		} );
	}
}
