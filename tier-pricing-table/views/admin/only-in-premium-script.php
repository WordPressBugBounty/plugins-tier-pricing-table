<?php if ( ! defined( 'ABSPATH' ) ) {
exit;} ?>
<style>
	.tpt-premium-wrapper {
		top: 40%;
		left: 26%;
		position: absolute;
	}

	.tpt-premium-wrapper__text {
		transform: translateX(-50%);
		font-size: 32px;
		color: #ec3d3d;
		font-weight: bold;
	}

	@media (max-width: 820px) {

		.tpt-premium-wrapper__text {
			transform: translateX(0);
		}
	}
</style>
<script>
	jQuery(document).ready(function () {
		jQuery('[data-tiered-pricing-premium-setting]').closest('table')
			.css('pointer-events', 'none')
			.css('position', 'relative')
			.append('<div class="tpt-premium-wrapper" ><p class="tpt-premium-wrapper__text">
			<?php 
			esc_html_e( 'Only in premium version',
				'tier-pricing-table' ); 
			?>
				</p></div>')
			.find('tbody')
			.css('opacity', '0.7');
	});
</script>