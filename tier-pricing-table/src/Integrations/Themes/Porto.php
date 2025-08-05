<?php namespace TierPricingTable\Integrations\Themes;

class Porto {

	public function __construct() {

		add_action( 'wp_head', function () {
			?>
			<script>
				jQuery(document).ready(function ($) {
					if (document.tieredPriceTable) {
						setTimeout(function () {
							document.tieredPriceTable.init();
						}, 1000)
					}
				});
			</script>
			<?php
		} );
	}
}
