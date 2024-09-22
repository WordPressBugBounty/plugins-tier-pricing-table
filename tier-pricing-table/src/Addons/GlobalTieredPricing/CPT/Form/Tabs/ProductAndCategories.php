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
		return __( 'Select products or product categories the rule will work for.', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $pricingRule ) {

		if ( empty( $pricingRule->getIncludedProductCategories() ) && empty( $pricingRule->getIncludedProducts() ) ) {
			$this->form->renderHint( __( 'If you do not specify products or product categories, the rule will work for all products in your store. (excluding products selected in the exclusions section)',
				'tier-pricing-table' ) );
		}

		?>
		<p class="form-field">
			<label for="tpt_included_categories">
			<?php esc_html_e( 'Apply for categories', 'tier-pricing-table' ); ?>
			</label>

			<select class="wc-product-search" multiple="multiple" style="width: 95%;" id="tpt_included_categories"
					name="tpt_included_categories[]"
					data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'tier-pricing-table' ); ?>"
					data-action="woocommerce_json_search_tpt_categories">

				<?php foreach ( $pricingRule->getIncludedProductCategories() as $categoryId ) : ?>
					<?php $category = get_term_by( 'id', $categoryId, 'product_cat' ); ?>

					<?php if ( $category ) : ?>
						<option selected
								value="<?php echo esc_attr( $categoryId ); ?>"><?php echo esc_attr( LookupService::getCategoryLabel( $category ) ); ?></option>
					<?php endif; ?>

				<?php endforeach; ?>
			</select>

			<?php 
			echo wc_help_tip( __( 'Choose the categories for which this pricing rule will apply. The rule applies to all products in the category.',
				'tier-pricing-table' ) ); 
			?>
		</p>

		<p class="form-field">
			<label for="tpt_included_products">
			<?php 
			esc_html_e( 'Apply for specific products',
					'tier-pricing-table' ); 
			?>
					</label>

			<select class="wc-product-search" multiple="multiple" style="width: 95%;" id="tpt_included_products"
					name="tpt_included_products[]"
					data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'tier-pricing-table' ); ?>"
					data-action="woocommerce_json_search_products_and_variations">

				<?php foreach ( $pricingRule->getIncludedProducts() as $productId ) : ?>

					<?php $product = wc_get_product( $productId ); ?>

					<?php if ( $product ) : ?>
						<option selected
								value="<?php echo esc_attr( $productId ); ?>"><?php echo esc_attr( $product->get_name() ); ?></option>
					<?php endif; ?>

				<?php endforeach; ?>
			</select>

			<?php 
			echo wc_help_tip( __( 'Pick up products for which you want to apply the pricing rule.',
				'tier-pricing-table' ) ); 
			?>
		</p>
		
		<div class="tpt-global-pricing-title">
			<hr>
			<h4><?php esc_attr_e( 'Exclusions', 'tier-pricing-table' ); ?></h4>
		</div>
		
		<p class="form-field">
			<label for="tpt_included_categories">
				<?php esc_html_e( 'Exclude for categories', 'tier-pricing-table' ); ?>
			</label>
			
			<select class="wc-product-search" multiple="multiple" style="width: 95%;" id="tpt_excluded_categories"
					name="tpt_excluded_categories[]"
					data-placeholder="<?php esc_attr_e( 'Search for a category&hellip;', 'tier-pricing-table' ); ?>"
					data-action="woocommerce_json_search_tpt_categories">
				
				<?php foreach ( $pricingRule->getExcludedProductCategories() as $categoryId ) : ?>
					<?php $category = get_term_by( 'id', $categoryId, 'product_cat' ); ?>
					
					<?php if ( $category ) : ?>
						<option selected
								value="<?php echo esc_attr( $categoryId ); ?>"><?php echo esc_attr( LookupService::getCategoryLabel( $category ) ); ?></option>
					<?php endif; ?>
				
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Choose the categories for which exclude this pricing rule. The rule excludes to all products in the category.',
					'tier-pricing-table' ) );
			?>
		</p>
		
		<p class="form-field">
			<label for="tpt_excluded_products">
				<?php
					esc_html_e( 'Exclude for specific products',
						'tier-pricing-table' );
				?>
			</label>
			
			<select class="wc-product-search" multiple="multiple" style="width: 95%;" id="tpt_excluded_products"
					name="tpt_excluded_products[]"
					data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'tier-pricing-table' ); ?>"
					data-action="woocommerce_json_search_products">
				
				<?php foreach ( $pricingRule->getExcludedProducts() as $productId ) : ?>
					
					<?php $product = wc_get_product( $productId ); ?>
					
					<?php if ( $product ) : ?>
						<option selected
								value="<?php echo esc_attr( $productId ); ?>"><?php echo esc_attr( $product->get_name() ); ?></option>
					<?php endif; ?>
				
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Pick up products for which you want to exclude from pricing rule.',
					'tier-pricing-table' ) );
			?>
		</p>
		<?php
	}
}