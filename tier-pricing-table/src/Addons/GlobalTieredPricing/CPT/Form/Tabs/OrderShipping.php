<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderRulesSettingsSection;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\FreeShippingBar;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscountSettings;

/**
 * The shipping side of an order rule: the free shipping it grants (on every order, or from a subtotal or a
 * number of items) and the free shipping bar that shows the customer how far they are from it.
 */
class OrderShipping extends FormTab {

	public function getId(): string {
		return 'order-shipping';
	}

	public function getTitle(): string {
		return __( 'Free shipping', 'tier-pricing-table' );
	}

	public function getDescription(): string {
		return __( 'Always, or from a subtotal or item count, and the progress bar', 'tier-pricing-table' );
	}

	public function getKinds(): array {
		return array( GlobalPricingRule::KIND_ORDER );
	}

	public function render( GlobalPricingRule $pricingRule ) {
		$premium = tpt_fs()->can_use_premium_code();

		$this->renderSectionTitle( __( 'Free shipping', 'tier-pricing-table' ), array(
			'description' => __( 'The rule can grant free shipping: on every order it covers, or once the counted lines reach a subtotal or a number of items. Works with or without discount tiers; it only adds free shipping and never takes away what the shipping settings already give.', 'tier-pricing-table' ),
		) );

		woocommerce_wp_checkbox( array(
			'id'          => 'tpt_order_free_shipping_enabled',
			'label'       => __( 'Free shipping', 'tier-pricing-table' ),
			'description' => __( 'Grant free shipping through this rule', 'tier-pricing-table' ),
			'value'       => 'none' === $pricingRule->getOrderFreeShipping() ? 'no' : 'yes',
			'cbvalue'     => 'yes',
		) );
		?>
		<div class="tpt-order-free-shipping-options">
		<?php
		woocommerce_wp_select( array(
			'id'      => 'tpt_order_free_shipping',
			'label'   => __( 'When', 'tier-pricing-table' ),
			'value'   => 'none' === $pricingRule->getOrderFreeShipping() ? 'always' : $pricingRule->getOrderFreeShipping(),
			'options' => array(
				'always'   => __( 'On every order under this rule', 'tier-pricing-table' ),
				'amount'   => __( 'From a subtotal of the counted lines', 'tier-pricing-table' ),
				'quantity' => __( 'From a number of items in the counted lines', 'tier-pricing-table' ),
			),
		) );

		woocommerce_wp_text_input( array(
			'id'          => 'tpt_order_free_shipping_from',
			'label'       => __( 'From', 'tier-pricing-table' ),
			'value'       => is_null( $pricingRule->getOrderFreeShippingFrom() ) ? '' : wc_format_localized_price( $pricingRule->getOrderFreeShippingFrom() ),
			'data_type'   => 'price',
			'placeholder' => __( 'Subtotal or number of items', 'tier-pricing-table' ),
			'description' => __( 'A subtotal in the store currency, or a number of items, as chosen above.', 'tier-pricing-table' ),
			'desc_tip'    => true,
		) );

		$this->renderFreeShippingStatus();
		?>
		</div>

		<div class="tpt-order-shipping-bar">
			<?php
			$this->renderSectionTitle( __( 'Free shipping bar', 'tier-pricing-table' ), array(
				'only_for_premium' => true,
				'description'      => __( 'A progress bar towards the free shipping threshold ("Add $40 more for free shipping"), then a message once it is reached. It follows the rule the cart is under. Where it shows and how it looks is set for the whole store on the Cart rules settings page.', 'tier-pricing-table' ),
			) );

			woocommerce_wp_checkbox( array(
				'id'                => 'tpt_order_shipping_bar',
				'label'             => __( 'Progress bar', 'tier-pricing-table' ),
				'description'       => __( 'Show the free shipping bar for this rule', 'tier-pricing-table' ),
				'value'             => $pricingRule->isOrderShippingBarEnabled() ? 'yes' : 'no',
				'cbvalue'           => 'yes',
				'custom_attributes' => $premium ? array() : array( 'disabled' => 'disabled' ),
			) );
			?>
			<div class="tpt-order-shipping-bar-options">
				<?php
				woocommerce_wp_text_input( array(
					'id'                => 'tpt_order_shipping_bar_message',
					'label'             => __( 'Message until reached', 'tier-pricing-table' ),
					'value'             => $pricingRule->getOrderShippingBarMessage(),
					'placeholder'       => OrderDiscountSettings::getBarTemplate(),
					'description'       => __( 'Placeholders: {remaining}, {threshold}, {progress}. Leave it empty for the store message.', 'tier-pricing-table' ),
					'desc_tip'          => true,
					'custom_attributes' => $premium ? array() : array( 'disabled' => 'disabled' ),
				) );

				woocommerce_wp_text_input( array(
					'id'                => 'tpt_order_shipping_bar_reached',
					'label'             => __( 'Message when reached', 'tier-pricing-table' ),
					'value'             => $pricingRule->getOrderShippingBarReached(),
					'placeholder'       => OrderDiscountSettings::getBarReachedTemplate(),
					'description'       => __( 'Placeholder: {threshold}. Leave it empty for the store message.', 'tier-pricing-table' ),
					'desc_tip'          => true,
					'custom_attributes' => $premium ? array() : array( 'disabled' => 'disabled' ),
				) );

				$this->renderHint( sprintf(
					/* translators: 1: list of places, 2: link "Customize the free shipping bar", 3: shortcode */
					esc_html__( 'The bar shows in: %1$s. %2$s: the places, the colours and the store messages, with a live preview. The shortcode %3$s and the Free Shipping Bar block put it anywhere else, such as the header or the page of the Cart block; they refresh when something is added to the cart.', 'tier-pricing-table' ),
					esc_html( $this->placementsSummary() ),
					'<a href="' . esc_url( OrderRulesSettingsSection::url( 'free-shipping-bar' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Customize the free shipping bar', 'tier-pricing-table' ) . '</a>',
					'<code>[' . esc_html( FreeShippingBar::SHORTCODE ) . ']</code>'
				), array( 'show_icon' => false ) );
				?>
			</div>
		</div>
		<script>
			jQuery( function ( $ ) {
				// the options show once free shipping is granted; the threshold only for the two "from" modes;
				// the bar section once free shipping is granted, its messages once the bar is on
				var toggle = function () {
					var granted = $( '#tpt_order_free_shipping_enabled' ).is( ':checked' );
					var mode    = $( '#tpt_order_free_shipping' ).val();
					$( '.tpt-order-free-shipping-options' ).toggle( granted );
					$( '.tpt_order_free_shipping_from_field' ).toggle( granted && ( 'amount' === mode || 'quantity' === mode ) );
					$( '.tpt-order-shipping-bar' ).toggle( granted );
					$( '.tpt-order-shipping-bar-options' ).toggle( $( '#tpt_order_shipping_bar' ).is( ':checked' ) );
				};
				$( document ).on( 'change', '#tpt_order_free_shipping_enabled, #tpt_order_free_shipping, #tpt_order_shipping_bar', toggle );
				toggle();
			} );
		</script>
		<?php
	}

	/**
	 * The chosen placements as a readable list, or "nowhere on its own".
	 */
	protected function placementsSummary(): string {
		$options = OrderDiscountSettings::getBarPlacementOptions();
		$names   = array();

		foreach ( OrderDiscountSettings::getBarPlacements() as $placement ) {
			if ( isset( $options[ $placement ] ) ) {
				$names[] = strtolower( (string) preg_replace( '/,.*$/', '', $options[ $placement ] ) );
			}
		}

		return $names ? implode( ', ', $names ) : __( 'no place of its own yet (only the shortcode and the block)', 'tier-pricing-table' );
	}

	/**
	 * Whether WooCommerce's Free shipping methods will honour the coupon: at least one must require "a
	 * valid free shipping coupon" (alone or with a minimum amount). Says which zones do, or how to set it.
	 */
	protected function renderFreeShippingStatus() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return;
		}

		$zones   = \WC_Shipping_Zones::get_zones();
		$zones[] = array( 'zone_name' => __( 'Locations not covered by your other zones', 'tier-pricing-table' ), 'shipping_methods' => \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods() );
		$ready   = array();

		foreach ( $zones as $zone ) {
			foreach ( (array) ( $zone['shipping_methods'] ?? array() ) as $method ) {
				if ( 'free_shipping' === $method->id && 'yes' === $method->enabled && in_array( $method->get_option( 'requires' ), array( 'coupon', 'either', 'both' ), true ) ) {
					$ready[] = (string) ( $zone['zone_name'] ?? '' );
				}
			}
		}

		$url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );

		if ( $ready ) {
			/* translators: %s: zone names */
			$this->renderHint( sprintf( esc_html__( 'Free shipping methods that accept a free shipping coupon: %s.', 'tier-pricing-table' ), esc_html( implode( ', ', array_unique( $ready ) ) ) ), array( 'show_icon' => false ) );
		} else {
			$this->renderHint( sprintf(
				/* translators: %s: link to the shipping settings */
				esc_html__( 'No Free shipping method accepts a coupon yet, so the threshold would change nothing. In %s, add a Free shipping method to the zone and set "Free shipping requires" to "A valid free shipping coupon" (or "… OR coupon").', 'tier-pricing-table' ),
				'<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'WooCommerce → Settings → Shipping', 'tier-pricing-table' ) . '</a>'
			), array( 'custom_class' => 'tpt-global-pricing-rule-hint--warning' ) );
		}
	}

	public function getIcon(): string {
		return 'dashicons-car';
	}
}
