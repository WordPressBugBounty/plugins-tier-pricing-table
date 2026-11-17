<?php namespace TierPricingTable\Frontend;

use WC_Product;

/**
 * Quantity limits in WooCommerce's block add-to-cart form (block themes).
 *
 * The classic form reads the quantity box's limits from `woocommerce_quantity_input_args`, where the plugin sets
 * a product's minimum, maximum and quantity step, and starts the box at the minimum. The
 * `woocommerce/add-to-cart-with-options` block takes its starting quantity, the limits its script checks and
 * the quantity it adds to the cart from WC_Product::get_min_purchase_quantity(), get_max_purchase_quantity()
 * and get_purchase_quantity_step() instead, so a product with a minimum of 10 started at 1 there.
 *
 * While that block renders, those three return what the classic form gets. Nothing changes anywhere else.
 */
class BlockQuantityLimits {

	const BLOCK = 'woocommerce/add-to-cart-with-options';

	/**
	 * How many add-to-cart-with-options blocks are rendering right now.
	 *
	 * @var int
	 */
	protected $depth = 0;

	/**
	 * Set while the classic arguments are read, so the filters below do not run inside themselves.
	 *
	 * @var bool
	 */
	protected $reading = false;

	public function __construct() {
		// last, so a block another filter short-circuits is not counted
		add_filter( 'pre_render_block', array( $this, 'enterBlock' ), PHP_INT_MAX, 2 );
		add_filter( 'render_block_' . self::BLOCK, array( $this, 'leaveBlock' ), 1 );
	}

	/**
	 * @param  string|null  $preRender
	 * @param  array  $parsedBlock
	 *
	 * @return string|null
	 */
	public function enterBlock( $preRender, $parsedBlock ) {
		if ( null === $preRender && is_array( $parsedBlock ) && self::BLOCK === ( $parsedBlock['blockName'] ?? '' ) ) {
			if ( 0 === $this->depth++ ) {
				add_filter( 'woocommerce_quantity_input_min', array( $this, 'filterMin' ), 9999, 2 );
				add_filter( 'woocommerce_quantity_input_max', array( $this, 'filterMax' ), 9999, 2 );
				add_filter( 'woocommerce_quantity_input_step', array( $this, 'filterStep' ), 9999, 2 );
			}
		}

		return $preRender;
	}

	/**
	 * @param  string  $content
	 *
	 * @return string
	 */
	public function leaveBlock( $content ) {
		if ( $this->depth > 0 && 0 === --$this->depth ) {
			remove_filter( 'woocommerce_quantity_input_min', array( $this, 'filterMin' ), 9999 );
			remove_filter( 'woocommerce_quantity_input_max', array( $this, 'filterMax' ), 9999 );
			remove_filter( 'woocommerce_quantity_input_step', array( $this, 'filterStep' ), 9999 );
		}

		return $content;
	}

	/**
	 * @param  int|float  $min
	 * @param  WC_Product|mixed  $product
	 *
	 * @return int|float
	 */
	public function filterMin( $min, $product ) {
		$args = $this->classicArgs( $product, array( 'min_value' => $min ) );

		return $this->positive( $args['min_value'] ?? null, $min );
	}

	/**
	 * @param  int|float  $max  -1 for no limit.
	 * @param  WC_Product|mixed  $product
	 *
	 * @return int|float
	 */
	public function filterMax( $max, $product ) {
		$args = $this->classicArgs( $product, array( 'max_value' => $max ) );

		return $this->positive( $args['max_value'] ?? null, $max );
	}

	/**
	 * @param  int|float  $step
	 * @param  WC_Product|mixed  $product
	 *
	 * @return int|float
	 */
	public function filterStep( $step, $product ) {
		$args = $this->classicArgs( $product, array( 'step' => $step ) );

		return $this->positive( $args['step'] ?? null, $step );
	}

	/**
	 * The quantity arguments the classic form gets for this product, starting from the given values.
	 */
	protected function classicArgs( $product, array $defaults ): array {
		if ( $this->reading || ! $product instanceof WC_Product ) {
			return $defaults;
		}

		$this->reading = true;

		// every key the classic form passes, so other filters on these arguments find what they expect
		$args = apply_filters( 'woocommerce_quantity_input_args', array_merge( array(
			'input_value' => 1,
			'min_value'   => 1,
			'max_value'   => -1,
			'step'        => 1,
		), $defaults ), $product );

		$this->reading = false;

		return is_array( $args ) ? $args : $defaults;
	}

	/**
	 * A limit the classic form set (a positive number), or the value WooCommerce had.
	 *
	 * @param  mixed  $value
	 * @param  int|float  $fallback
	 *
	 * @return int|float
	 */
	protected function positive( $value, $fallback ) {
		return is_numeric( $value ) && $value > 0 ? $value + 0 : $fallback;
	}
}
