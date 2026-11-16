<?php namespace TierPricingTable\Addons\ImportExport;

use TierPricingTable\PriceManager;

use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

class WoocommerceImportService {
	
	/**
	 * Import constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'addColumnsToImporter' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns',
			array( $this, 'addColumnToMappingScreen' ) );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'processImport' ), 10, 2 );
	}
	
	/**
	 * Register the 'Tiered pricing' column in the importer.
	 */
	public function addColumnsToImporter( array $columns ): array {
		
		$columns['tiered_pricing_table'] = array(
			'name'    => __( 'Tiered Pricing', 'tier-pricing-table' ),
			'options' => array(
				'tiered_price_type'       => __( 'Tiered pricing type', 'tier-pricing-table' ),
				'tiered_price_fixed'      => __( 'Fixed pricing rules', 'tier-pricing-table' ),
				'tiered_price_percentage' => __( 'Percentage pricing rules', 'tier-pricing-table' ),
				'tiered_price_amount'     => __( 'Amount pricing rules', 'tier-pricing-table' ),
				'tiered_price_minimum'    => __( 'Minimum order quantity', 'tier-pricing-table' ),
			),
		);
		
		return apply_filters( 'tiered_pricing_table/import/woocommerce/import_columns', $columns );
	}
	
	/**
	 * Add automatic mapping support for 'Tiered pricing'.
	 */
	public function addColumnToMappingScreen( array $columns ): array {
		
		$columns['Tiered Pricing — Fixed pricing rules']      = 'tiered_price_fixed';
		$columns['Tiered Pricing — Percentage pricing rules'] = 'tiered_price_percentage';
		$columns['Tiered Pricing — Amount pricing rules']     = 'tiered_price_amount';
		$columns['Tiered Pricing — Tiered pricing type']      = 'tiered_price_type';
		$columns['Tiered Pricing — Minimum order quantity']   = 'tiered_price_minimum';
		
		return apply_filters( 'tiered_pricing_table/import/woocommerce/mapping_screen_columns', $columns );
	}
	
	/**
	 * Process the data read from the CSV file.
	 */
	public function processImport( WC_Product $product, array $data ): WC_Product {
		
		$columns = array(
			'tiered_price_fixed'      => '_fixed_price_rules',
			'tiered_price_percentage' => '_percentage_price_rules',
			'tiered_price_amount'     => '_amount_price_rules',
		);
		
		foreach ( $columns as $column => $metaKey ) {
			if ( ! isset( $data[ $column ] ) ) {
				continue;
			}
			
			$rules = self::decodeExportCell( (string) $data[ $column ] );
			
			// a cell that is not empty but cannot be read (a spreadsheet turned "20:100" into "21:40:00") keeps the
			// product's tiers as they are instead of erasing them; the import log says which cell was skipped
			if ( null === $rules ) {
				self::logSkippedCell( $product, $column, (string) $data[ $column ] );
				continue;
			}
			
			$product->update_meta_data( $metaKey, $rules );
		}
		
		if ( isset( $data['tiered_price_type'] ) ) {
			
			if ( in_array( $data['tiered_price_type'], PriceManager::getPricingTypes(), true ) ) {
				$product->update_meta_data( '_tiered_price_rules_type', $data['tiered_price_type'] );
			}
		}
		
		if ( isset( $data['tiered_price_minimum'] ) ) {
			
			$minimum = (int) $data['tiered_price_minimum'];
			
			$product->update_meta_data( '_tiered_price_minimum_qty', $minimum );
		}
		
		return $product;
	}
	
	/**
	 * Decode a tier cell of the import file. An empty cell clears the tiers (an empty array); a cell with any rule
	 * that cannot be read gives null, so the product's tiers are left as they are.
	 *
	 * @param  string  $data
	 *
	 * @return array|null
	 */
	public static function decodeExportCell( string $data ): ?array {
		if ( '' === trim( $data ) ) {
			return array();
		}
		
		$separators = preg_quote( ':=' . TierPricingTablePlugin::getRuleSeparator(), '/' );
		
		foreach ( explode( TierPricingTablePlugin::getRulesSeparator(), $data ) as $rule ) {
			if ( '' !== trim( $rule ) && ! preg_match( '/^\s*(\d+)\s*[' . $separators . ']\s*(\d+(?:\.\d+)?)\s*$/', $rule ) ) {
				return null;
			}
		}
		
		return self::decodeExport( $data );
	}
	
	/**
	 * A line in WooCommerce's logs (WooCommerce, Status, Logs, source "tiered-pricing-import") for a tier cell the
	 * import left out.
	 */
	protected static function logSkippedCell( WC_Product $product, string $column, string $value ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		
		wc_get_logger()->warning( sprintf(
			'Product %1$s (%2$s): the column "%3$s" could not be read and was skipped, the product keeps its tiers. Value: "%4$s". Write tiers as quantity=price, for example 10=90,20=80.',
			$product->get_id() ? '#' . $product->get_id() : 'new',
			$product->get_sku() ? $product->get_sku() : $product->get_name(),
			$column,
			substr( $value, 0, 200 )
		), array( 'source' => 'tiered-pricing-import' ) );
	}
	
	/**
	 * Decode the export string format ("10:90,20:80") into an array of pricing rules.
	 *
	 * Each rule is "quantity:price" (or "quantity=price", which spreadsheets leave alone, while they
	 * turn "20:100" into a time). Anything else, for example a cell a spreadsheet already turned
	 * into "21:40:00", is ignored instead of being imported as a wrong tier.
	 */
	public static function decodeExport( string $data ): array {
		$rules = explode( TierPricingTablePlugin::getRulesSeparator(), $data );
		
		// ":" and "=" always work; a store may export with another separator through the filter.
		$separators = preg_quote( ':=' . TierPricingTablePlugin::getRuleSeparator(), '/' );
		
		$data = array();
		
		foreach ( $rules as $rule ) {
			if ( preg_match( '/^\s*(\d+)\s*[' . $separators . ']\s*(\d+(?:\.\d+)?)\s*$/', $rule, $matches ) ) {
				$data[ intval( $matches[1] ) ] = $matches[2];
			}
		}
		
		$data = array_filter( $data );
		
		$data = array_filter( $data, function ( $itemKey ) {
			return is_numeric( $itemKey ) && $itemKey > 1;
		}, ARRAY_FILTER_USE_KEY );
		
		return ! empty( $data ) ? $data : array();
	}
}
