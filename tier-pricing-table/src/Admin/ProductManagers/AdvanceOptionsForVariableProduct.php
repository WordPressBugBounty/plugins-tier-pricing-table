<?php namespace TierPricingTable\Admin\ProductManagers;

use TierPricingTable\TierPricingTablePlugin;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_Post;

/**
 * Class VariationProduct
 *
 * @package TierPricingTable\Admin\Product
 */
class AdvanceOptionsForVariableProduct {

	const PRODUCT_VARIATIONS_SEARCH_ACTION = 'woocommerce_json_search_tpt_product_variations';

	/**
	 * Register hooks
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . self::PRODUCT_VARIATIONS_SEARCH_ACTION, array(
			$this,
			'productVariationsSearchHandler'
		) );

		// Saving
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveOptions' ) );

		add_filter( 'tiered_pricing_table/admin/has_advance_product_options', function ( $has, WP_Post $post ) {
			$product = wc_get_product( $post->ID );

			if ( ! $product ) {
				return $has;
			}

			return $has || TierPricingTablePlugin::isVariableProductSupported( $product );
		}, 10, 2 );

		add_action( 'tiered_pricing_table/admin/advance_product_options', function ( $productId ) {

			$product = wc_get_product( $productId );

			if ( ! TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
				return;
			}
			?>

            <p class="form-field _tiered_pricing_default_variation show_if_variable">
                <label for="_tiered_pricing_default_variation_id"><?php esc_html_e( 'Default variation', 'tier-pricing-table' ); ?></label>

                <select class="wc-product-search" style="width: 50%;"
                        id="_tiered_pricing_default_variation_id"
                        data-allow_clear="true"
                        name="_tiered_pricing_default_variation_id"
                        data-include="<?php echo esc_attr( $productId ); ?>"
                        data-placeholder="<?php esc_attr_e( 'Search for a variation&hellip;', 'tier-pricing-table' ); ?>"
                        data-action="woocommerce_json_search_tpt_product_variations">

					<?php $default = self::getDefaultVariation( $productId, 'edit' ); ?>

					<?php if ( $default ) : ?>
                        <option selected value="<?php echo esc_attr( $default->get_id() ); ?>">
							<?php echo esc_attr( $default->get_attribute_summary() ); ?>
                        </option>
					<?php endif; ?>
                </select>

                <span class="description"
                      style="clear: left; display: block; margin-top: 35px; margin-left: 0"><?php esc_html_e( 'The pricing will be shown for the selected variation before the attributes are selected on the product page', 'tier-pricing-table' ); ?>
                            </span>
            </p>
			<?php

			/**
			 * @var WC_Product_Variable $product
			 */

			woocommerce_wp_checkbox( array(
				'id'            => '_tiered_pricing_product_has_no_rules',
				'wrapper_class' => 'show_if_variable',
				'value'         => wc_bool_to_string( self::productHasNoRules( $productId, 'edit' ) ),
				'label'         => __( 'Product does not have tiered pricing rules', 'tier-pricing-table' ),
				'description'   => __( 'Check this if the product does not have tiered pricing rules. This will increase performance as the plugin will not check every variation for rules', 'tier-pricing-table' ),
				'default'       => 'no',
			) );
		} );
	}

	/**
	 * Save advanced options related to the variable product
	 *
	 * @param int $productId
	 */
	public function saveOptions( $productId ) {

		$data = $_POST;

		$hasNoRules       = ! empty( $data['_tiered_pricing_product_has_no_rules'] );
		$defaultVariation = ! empty( $data['_tiered_pricing_default_variation_id'] ) ? intval( $data['_tiered_pricing_default_variation_id'] ) : null;

		update_post_meta( $productId, '_tiered_pricing_product_has_no_rules', wc_bool_to_string( $hasNoRules ) );
		update_post_meta( $productId, '_tiered_pricing_default_variation_id', $defaultVariation );
	}

	/**
	 * Get default variation
	 *
	 * @param $productId
	 *
	 * @return WC_Product_Variation|null
	 */
	public static function getDefaultVariation( $productId, $context = 'view' ) {
		$defaultVariationId = get_post_meta( $productId, '_tiered_pricing_default_variation_id', true );

		$defaultVariation = $defaultVariationId ? wc_get_product( $defaultVariationId ) : false;

		if ( ! ( $defaultVariation instanceof WC_Product_Variation ) ) {
			$defaultVariation = null;
		}

		if ( $context !== 'edit' ) {
			return apply_filters( 'tiered_pricing_table/product/default_variation', $defaultVariation, $productId );
		}

		return $defaultVariation;
	}

	public static function productHasNoRules( $productId, $context = 'view' ) {
		$hasNoRules = get_post_meta( $productId, '_tiered_pricing_product_has_no_rules', true ) === 'yes';

		if ( $context !== 'edit' ) {
			return apply_filters( 'tiered_pricing_table/product/product_has_no_rules', $hasNoRules, $productId );
		}

		return $hasNoRules;
	}

	public function productVariationsSearchHandler() {

		check_ajax_referer( 'search-products', 'security' );

		if ( empty( $term ) && isset( $_GET['term'] ) ) {
			$term = (string) wc_clean( wp_unslash( $_GET['term'] ) );
		}

		if ( empty( $term ) ) {
			wp_die();
		}

		$limit   = 30;
		$include = ! empty( $_GET['include'] ) ? intval( $_GET['include'] ) : false;

		if ( ! $include ) {
			return wp_send_json( array() );
		}

		$product = wc_get_product( $include );

		if ( ! TierPricingTablePlugin::isVariableProductSupported( $product ) ) {
			return wp_send_json( array() );
		}

		$results = array();

		if ( $product instanceof WC_Product_Variable ) {
			$variationsObjects = $product->get_available_variations( 'objects' );
			$variations        = array();
			$rawVariations     = array();

			foreach ( $variationsObjects as $variation ) {
				$rawVariations[ '_' . $variation->get_id() ] = rawurldecode( wp_strip_all_tags( $variation->get_attribute_summary() ) );
				$variations                                  = $rawVariations;
			}

			if ( count( $rawVariations ) > $limit ) {
				$similarTextResults = array();

				foreach ( $variationsObjects as $variation ) {
					$similarTextResults[ '_' . $variation->get_id() ] = similar_text( strtolower( $variation->get_attribute_summary() ), strtolower( $term ) );
				}

				asort( $similarTextResults );
				$similarTextResults = array_reverse( $similarTextResults );
				$similarTextResults = array_slice( $similarTextResults, 0, $limit );

				$variations = array_intersect_key( $similarTextResults, $rawVariations );

			}

			foreach ( $variations as $key => $value ) {
				$results[ str_replace( '_', '', $key ) ] = $rawVariations[ $key ];
			}
		}

		return wp_send_json( $results );
	}
}