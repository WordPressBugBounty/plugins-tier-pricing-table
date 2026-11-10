<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\LookupService;

class ProductAndCategories extends FormTab {
	
	public function getId(): string {
		return 'products-and-categories';
	}
	
	public function getTitle(): string {
		return __( 'Products', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Products, categories, tags or brands this rule covers', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		$this->renderSectionTitle( __( 'Applies to products', 'tier-pricing-table' ) );
		
		// one hint per rule type: the form shows the one matching the type picked in the status box
		$this->renderKindNote( GlobalPricingRule::KIND_ORDER, __( 'For a cart rule these lists decide which cart lines count towards the tiers and the minimum, and which lines get the discount. Leave them empty to count the whole cart, except the products in the Exclusions section.', 'tier-pricing-table' ) );
		
		if ( empty( $pricingRule->getIncludedProductCategories() ) && empty( $pricingRule->getIncludedProducts() ) ) {
			$this->renderKindNote( GlobalPricingRule::KIND_PRODUCT, __( 'If no products, categories, tags, or brands are selected, this rule will apply to all products in your store, except those listed in the Exclusions section.', 'tier-pricing-table' ) );
		}
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_categories',
			'label'         => __( 'Categories', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProductCategories() as $categoryId ) {
					$category = get_term_by( 'id', $categoryId, 'product_cat' );
					
					if ( $category ) {
						$options[ $categoryId ] = LookupService::getCategoryLabel( $category );
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search categories&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_categories',
		) );
		
		$this->renderCheckbox( array(
			'title'       => __( 'Subcategories', 'tier-pricing-table' ),
			'id'          => 'tpt_include_subcategories',
			'value'       => $pricingRule->isIncludeSubcategories(), // a bool: the helper's checked() compares with true
			'label'       => __( 'Include subcategories', 'tier-pricing-table' ),
			'description' => __( 'Products in subcategories of the selected categories match too. The excluded categories below follow the same switch.', 'tier-pricing-table' ),
		) );
		?>
		<script>
			jQuery( function ( $ ) {
				// "Include subcategories" matters only once a category is chosen, included or excluded
				var $box    = $( 'input[name="tpt_include_subcategories"]' ).closest( '.tiered-pricing-form-block' );
				var $fields = $( '#tpt_included_categories, #tpt_excluded_categories, [name="tpt_included_categories[]"], [name="tpt_excluded_categories[]"]' );
				var toggle  = function () {
					var chosen = false;
					$fields.each( function () {
						if ( ( $( this ).val() || [] ).length ) {
							chosen = true;
						}
					} );
					$box.toggle( chosen );
				};
				$( document ).on( 'change', '#tpt_included_categories, #tpt_excluded_categories, [name="tpt_included_categories[]"], [name="tpt_excluded_categories[]"]', toggle );
				toggle();
			} );
		</script>
		<?php
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_tags',
			'label'         => __( 'Tags', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProductTags() as $tagId ) {
					$tag = get_term_by( 'id', $tagId, 'product_tag' );
					
					if ( $tag ) {
						$options[ $tagId ] = $tag->name;
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search tags&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_tags',
		) );
		
		if ( taxonomy_exists( 'product_brand' ) ) {
			$this->renderSelect2( array(
				'id'            => 'tpt_included_brands',
				'label'         => __( 'Brands', 'tier-pricing-table' ),
				'value'         => ( function () use ( $pricingRule ) {
					$options = [];
					
					foreach ( $pricingRule->getIncludedProductBrands() as $brandId ) {
						$brand = get_term_by( 'id', $brandId, 'product_brand' );
						
						if ( $brand && ! is_wp_error( $brand ) ) {
							$options[ $brandId ] = $brand->name;
						}
					}
					
					return $options;
				} )(),
				'placeholder'   => __( 'Search brands&hellip;', 'tier-pricing-table' ),
				'search_action' => 'woocommerce_json_search_tpt_brands',
			) );
		}
		
		$this->renderSelect2( array(
			'id'            => 'tpt_included_products',
			'label'         => __( 'Products', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getIncludedProducts() as $productId ) {
					$product = wc_get_product( $productId );
					
					if ( $product ) {
						if ( ! $product->get_sku() ) {
							$options[ $productId ] = $product->get_name();
						} else {
							$options[ $productId ] = $product->get_name() . ' (' . $product->get_sku() . ')';
						}
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search products&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_products_and_variations',
		) );
		
		$hasExclusions = ! empty( $pricingRule->getExcludedProductCategories() ) ||
		                 ! empty( $pricingRule->getExcludedProductTags() ) ||
		                 ! empty( $pricingRule->getExcludedProductBrands() ) ||
		                 ! empty( $pricingRule->getExcludedProducts() );
		?>
		<details class="tpt-exclusions-accordion" <?php echo $hasExclusions ? 'open' : ''; ?>>
			<summary>
				<?php echo esc_html__( 'Exclusions', 'tier-pricing-table' ); ?>
				<span class="dashicons dashicons-arrow-down-alt2 tpt-accordion-icon"></span>
			</summary>
			<div class="tpt-exclusions-accordion-content">
		<?php
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_categories',
			'label'         => __( 'Categories', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getExcludedProductCategories() as $categoryId ) {
					$category = get_term_by( 'id', $categoryId, 'product_cat' );
					
					if ( $category ) {
						$options[ $categoryId ] = LookupService::getCategoryLabel( $category );
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search categories&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_categories',
		) );
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_tags',
			'label'         => __( 'Tags', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getExcludedProductTags() as $tagId ) {
					$tag = get_term_by( 'id', $tagId, 'product_tag' );
					
					if ( $tag ) {
						$options[ $tagId ] = $tag->name;
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search tags&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_tpt_tags',
		) );
		
		if ( taxonomy_exists( 'product_brand' ) ) {
			$this->renderSelect2( array(
				'id'            => 'tpt_excluded_brands',
				'label'         => __( 'Brands', 'tier-pricing-table' ),
				'value'         => ( function () use ( $pricingRule ) {
					$options = [];
					
					foreach ( $pricingRule->getExcludedProductBrands() as $brandId ) {
						$brand = get_term_by( 'id', $brandId, 'product_brand' );
						
						if ( $brand && ! is_wp_error( $brand ) ) {
							$options[ $brandId ] = $brand->name;
						}
					}
					
					return $options;
				} )(),
				'placeholder'   => __( 'Search brands&hellip;', 'tier-pricing-table' ),
				'search_action' => 'woocommerce_json_search_tpt_brands',
			) );
		}
		
		$this->renderSelect2( array(
			'id'            => 'tpt_excluded_products',
			'label'         => __( 'Products', 'tier-pricing-table' ),
			'value'         => ( function () use ( $pricingRule ) {
				$options = [];
				
				foreach ( $pricingRule->getExcludedProducts() as $productId ) {
					$product = wc_get_product( $productId );
					
					if ( ! $product ) {
						continue;
					}
					
					if ( ! $product->get_sku() ) {
						$options[ $productId ] = $product->get_name();
					} else {
						$options[ $productId ] = $product->get_name() . ' (' . $product->get_sku() . ')';
					}
				}
				
				return $options;
			} )(),
			'placeholder'   => __( 'Search products&hellip;', 'tier-pricing-table' ),
			'search_action' => 'woocommerce_json_search_products_and_variations',
		) );

		?>
			</div>
		</details>
		<?php
	}
	
	public function getIcon(): string {
		return 'dashicons-archive';
	}
}
