<?php namespace TierPricingTable\Integrations\Plugins;

use TierPricingTable\PriceManager;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

/**
 * Express checkout buttons (Apple Pay, Google Pay, Link) of WooCommerce Stripe Gateway and WooPayments on the
 * product page.
 *
 * The gateways price the payment sheet on the product page themselves, outside the cart: Stripe as the product
 * price times the quantity on every quantity change, both gateways for one unit on page load. The sheet showed the
 * regular price for a quantity that earns a tier price (the order, built from the cart, was charged correctly).
 * While a gateway prices the product here, its price is the tier price for that quantity, the way the cart prices it.
 */
class ExpressCheckout extends PluginIntegrationAbstract {

	/**
	 * Products the gateway prices at a quantity in this request: product or parent id => quantity.
	 *
	 * @var array<int, int>
	 */
	protected $quantities = array();

	/**
	 * @var bool
	 */
	protected $resolving = false;

	public function getTitle(): string {
		return 'WooCommerce Stripe Gateway & WooPayments express checkout';
	}

	public function getDescription(): string {
		return __( 'Show the tiered price in the Apple Pay, Google Pay and Link payment sheet on product pages.',
			'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'express-checkout';
	}

	public function getAuthorURL(): string {
		return 'https://woocommerce.com/products/stripe/';
	}

	public function getIconURL(): string {
		return $this->getContainer()->getFileManager()->locateAsset( 'admin/integrations/woocommerce-develop.jpeg' );
	}

	public function run() {
		// Stripe re-prices the sheet on every quantity change; its own handler runs at priority 10.
		add_action( 'wc_ajax_wc_stripe_get_selected_product_data', array( $this, 'priceSelectedProduct' ), 1 );

		// The amount on page load, for products whose quantity box starts above one.
		add_filter( 'wc_stripe_payment_request_product_data', array( $this, 'priceStartQuantity' ), 10, 2 );
		add_filter( 'wcpay_payment_request_product_data', array( $this, 'priceStartQuantity' ), 10, 2 );

		// Ahead of every other price filter, as the cart sets the tier price before them.
		add_filter( 'woocommerce_product_get_price', array( $this, 'applyTierPrice' ), - 100, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'applyTierPrice' ), - 100, 2 );
	}

	/**
	 * Reads which product and quantity the gateway is about to price. Read only: the gateway's handler checks the
	 * nonce before it answers.
	 */
	public function priceSelectedProduct() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$productId = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$quantity  = isset( $_POST['qty'] ) ? absint( $_POST['qty'] ) : 1;
		// phpcs:enable

		if ( $productId && $quantity > 0 ) {
			$this->quantities[ $productId ] = $quantity;
		}
	}

	/**
	 * @param  mixed  $price
	 * @param  mixed  $product
	 *
	 * @return mixed
	 */
	public function applyTierPrice( $price, $product ) {

		if ( empty( $this->quantities ) || $this->resolving || ! $product instanceof WC_Product ) {
			return $price;
		}

		$quantity = $this->quantities[ $product->get_id() ] ?? ( $this->quantities[ $product->get_parent_id() ] ?? 0 );

		if ( ! $quantity || ! TierPricingTablePlugin::isSimpleProductSupported( $product ) ) {
			return $price;
		}

		$this->resolving = true;

		$tierPrice = PriceManager::getPricingRule( $product->get_id() )->getTierPrice( $product->is_sold_individually() ? 1 : $quantity,
			false, 'cart' );

		$this->resolving = false;

		if ( ! is_numeric( $tierPrice ) ) {
			return $price;
		}

		// As in the cart: the tier price already holds the role and customer prices.
		$product->update_meta_data( 'tiered_pricing_cart_price_calculated', 'yes' );

		return $tierPrice;
	}

	/**
	 * Scales the gateway's one-unit amounts to the quantity the product page starts at, at that quantity's tier price.
	 *
	 * @param  mixed  $data
	 * @param  mixed  $product
	 *
	 * @return mixed
	 */
	public function priceStartQuantity( $data, $product ) {

		if ( ! is_array( $data ) || ! $product instanceof WC_Product || empty( $data['displayItems'][0]['amount'] ) || empty( $data['total']['amount'] ) ) {
			return $data;
		}

		$quantity = $this->getStartQuantity( $product );

		if ( $quantity < 2 ) {
			return $data;
		}

		$unitPrice = (float) $product->get_price();

		// A copy, so the page's own product object keeps its prices.
		$priced = clone $product;

		$this->quantities[ $product->get_id() ] = $quantity;

		$tierUnitPrice = (float) $priced->get_price();

		unset( $this->quantities[ $product->get_id() ] );

		if ( $unitPrice <= 0 ) {
			return $data;
		}

		// Prices, taxes and the gateways' minor units are all proportional to the unit price.
		$factor = $tierUnitPrice / $unitPrice * $quantity;
		$total  = 0;

		foreach ( $data['displayItems'] as $index => $item ) {

			if ( ! empty( $item['amount'] ) ) {
				$data['displayItems'][ $index ]['amount'] = (int) round( $item['amount'] * $factor );
				$total                                   += $data['displayItems'][ $index ]['amount'];
			}
		}

		$data['displayItems'][0]['label'] .= ' (x' . $quantity . ')';
		$data['total']['amount']           = $total;

		return $data;
	}

	protected function getStartQuantity( WC_Product $product ): int {

		$args = apply_filters( 'woocommerce_quantity_input_args', array(
			'input_value' => $product->get_min_purchase_quantity(),
			'min_value'   => $product->get_min_purchase_quantity(),
			'max_value'   => $product->get_max_purchase_quantity(),
			'step'        => 1,
		), $product );

		return max( 1, (int) ( $args['input_value'] ?? 1 ), (int) ( $args['min_value'] ?? 1 ) );
	}

	public function getIntegrationCategory(): string {
		return 'other';
	}
}
