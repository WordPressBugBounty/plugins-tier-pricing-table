<?php namespace TierPricingTable\Integrations\Themes;

class Electro {

	public function __construct() {
		add_action( 'wp_head', function () {
			?>
			<script>
				function tieredPriceTableGetProductPriceContainer() {
					return jQuery('form.cart').closest('.product-actions').find('.tiered-pricing-dynamic-price-wrapper');
				}
			</script>
			<?php
		} );
	}
}
