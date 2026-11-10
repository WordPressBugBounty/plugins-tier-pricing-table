<?php namespace TierPricingTable\Addons\RoleBasedPricing\Import;

use TierPricingTable\PriceManager;

use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPricingRule;
use TierPricingTable\Addons\ImportExport\WoocommerceImportService;
use TierPricingTable\Addons\RoleBasedPricing\RoleBasedPriceManager;
use WC_Product;

class RoleBasedPricingImport {
	
	public function __construct() {
		add_filter( 'tiered_pricing_table/import/woocommerce/mapping_screen_columns',
			array( $this, 'addColumnsToMapper' ) );
		add_filter( 'tiered_pricing_table/import/woocommerce/import_columns', array( $this, 'addColumnsToImporter' ) );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'processImport' ), 10, 2 );
	}
	
	public function processImport( WC_Product $product, array $data ): WC_Product {
		
		$disabled_roles = apply_filters( 'tiered_pricing_table/role_based_rules/import_export_disabled_roles', array( 'editor', 'author', 'contributor', 'shop_manager' ) );
		
		foreach ( RoleBasedPriceManager::getAvailableRoles() as $WPRole => $roleName ) {
			
			if ( in_array( $WPRole, $disabled_roles, true ) ) {
				continue;
			}
			
			$cells = $this->getRoleCells( $WPRole, $data );
			
			// A role without its own pricing in the row stays as it is. Exports list every role, so saving those cells
			// would give each role an empty rule that hides the product's own price breaks from it.
			if ( ! $this->hasRolePricing( $cells ) ) {
				continue;
			}
			
			// Start from what the product has, so data the file does not carry (taxes, discount type) is kept.
			$roleBasedRule = $product->get_id() ? RoleBasedPricingRule::build( $product->get_id(), $WPRole,
				'edit' ) : new RoleBasedPricingRule( 0, $WPRole );
			
			foreach ( $cells as $column => $value ) {
				$this->applyCell( $roleBasedRule, $column, $value );
			}
			
			// Saved with the product, so new products from the file get their role prices too.
			foreach ( $roleBasedRule->getMetaData() as $metaKey => $metaValue ) {
				$product->update_meta_data( $metaKey, $metaValue );
			}
		}
		
		return $product;
	}
	
	/**
	 * The role's mapped cells in the row: column => trimmed value. Columns that are not mapped are left out.
	 */
	protected function getRoleCells( string $role, array $data ): array {
		
		$columns = array(
			'pricing_type',
			'regular_price',
			'sale_price',
			'discount',
			'type',
			'minimum',
			'fixed',
			'percentage',
			'amount',
		);
		
		$cells = array();
		
		foreach ( $columns as $column ) {
			$key = $role . '_tiered_price_' . $column;
			
			if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
				$cells[ $column ] = trim( (string) $data[ $key ] );
			}
		}
		
		return $cells;
	}
	
	/**
	 * Whether the row has pricing for the role: a price, a discount, a minimum, price breaks or a tiered pricing type.
	 * The regular pricing type alone does not count: earlier versions exported "flat" for every role, and "edit" as
	 * the tiered pricing type of roles without pricing.
	 */
	protected function hasRolePricing( array $cells ): bool {
		
		foreach ( $cells as $column => $value ) {
			if ( 'pricing_type' === $column ) {
				continue;
			}
			
			if ( 'type' === $column ) {
				if ( in_array( $value, PriceManager::getPricingTypes(), true ) ) {
					return true;
				}
				
				continue;
			}
			
			if ( '' !== $value ) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * An empty cell clears the value; an empty or unknown type keeps the current one.
	 */
	protected function applyCell( RoleBasedPricingRule $rule, string $column, string $value ) {
		
		switch ( $column ) {
			case 'pricing_type':
				if ( in_array( $value, array( 'flat', 'percentage' ), true ) ) {
					$rule->setPricingType( $value );
				}
				break;
			case 'regular_price':
				$rule->setRegularPrice( '' !== $value ? floatval( $value ) : null );
				break;
			case 'sale_price':
				$rule->setSalePrice( '' !== $value ? floatval( $value ) : null );
				break;
			case 'discount':
				$discount = floatval( $value );
				
				$rule->setDiscount( $discount > 0 ? min( 100, $discount ) : null );
				break;
			case 'type':
				if ( in_array( $value, PriceManager::getPricingTypes(), true ) ) {
					$rule->setTieredPricingType( $value );
				}
				break;
			case 'minimum':
				$rule->setMinimumOrderQuantity( '' !== $value ? (int) $value : null );
				break;
			case 'fixed':
				$rule->setFixedTieredPricingRules( WoocommerceImportService::decodeExport( $value ) );
				break;
			case 'percentage':
				$rule->setPercentageTieredPricingRules( WoocommerceImportService::decodeExport( $value ) );
				break;
			case 'amount':
				$rule->setAmountTieredPricingRules( WoocommerceImportService::decodeExport( $value ) );
				break;
		}
	}
	
	public function addColumnsToImporter( $columns ): array {
		
		$disabled_roles = apply_filters( 'tiered_pricing_table/role_based_rules/import_export_disabled_roles', array( 'editor', 'author', 'contributor', 'shop_manager' ) );
		
		foreach ( RoleBasedPriceManager::getAvailableRoles() as $WPRole => $roleName ) {
			if ( in_array( $WPRole, $disabled_roles, true ) ) {
				continue;
			}
			
			$columns[ 'tpt_' . $WPRole ] = array(
				'name'    => __( 'Tiered Pricing', 'tier-pricing-table' ) . ': ' . $roleName,
				'options' => array(
					$WPRole . '_tiered_price_pricing_type'  => $roleName . ': ' . __( 'Regular pricing type',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_regular_price' => $roleName . ': ' . __( 'Regular price',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_sale_price'    => $roleName . ': ' . __( 'Sale price',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_discount'      => $roleName . ': ' . __( 'Percentage discount',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_type'          => $roleName . ': ' . __( 'Tiered pricing type',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_minimum'       => $roleName . ': ' . __( 'Minimum order quantity',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_fixed'         => $roleName . ': ' . __( 'Fixed pricing rules',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_percentage'    => $roleName . ': ' . __( 'Percentage pricing rules',
							'tier-pricing-table' ),
					$WPRole . '_tiered_price_amount'        => $roleName . ': ' . __( 'Amount pricing rules',
							'tier-pricing-table' ),
				),
			);
		}
		
		return $columns;
	}
	
	public function addColumnsToMapper( array $columns ): array {
		
		$disabled_roles = apply_filters( 'tiered_pricing_table/role_based_rules/import_export_disabled_roles', array( 'editor', 'author', 'contributor', 'shop_manager' ) );
		
		foreach ( RoleBasedPriceManager::getAvailableRoles() as $WPRole => $roleName ) {
			if ( in_array( $WPRole, $disabled_roles, true ) ) {
				continue;
			}
			
			$labels = array(
				'Regular pricing type'     => $WPRole . '_tiered_price_pricing_type',
				'Regular price'            => $WPRole . '_tiered_price_regular_price',
				'Sale price'               => $WPRole . '_tiered_price_sale_price',
				'Percentage discount'      => $WPRole . '_tiered_price_discount',
				'Fixed pricing rules'      => $WPRole . '_tiered_price_fixed',
				'Percentage pricing rules' => $WPRole . '_tiered_price_percentage',
				'Amount pricing rules'     => $WPRole . '_tiered_price_amount',
				'Tiered pricing type'      => $WPRole . '_tiered_price_type',
				'Minimum order quantity'   => $WPRole . '_tiered_price_minimum',
			);
			
			foreach ( $labels as $label => $column ) {
				// The exporter writes "Tiered Pricing — [Role] Label". Files exported before 8.0.3 / 10.0.3 carried
				// two (three for the pricing type) spaces before the bracket; they must keep mapping automatically.
				foreach ( array( ' ', '  ', '   ' ) as $spacing ) {
					$columns[ 'Tiered Pricing —' . $spacing . '[' . $roleName . '] ' . $label ] = $column;
				}
			}
		}
		
		return $columns;
	}
}
