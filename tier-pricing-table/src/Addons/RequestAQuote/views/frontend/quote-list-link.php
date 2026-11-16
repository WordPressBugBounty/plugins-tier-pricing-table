<?php
	/**
	 * The "Quote list (3)" link of the [tiered_pricing_quote_list_link] shortcode.
	 *
	 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-list-link.php.
	 *
	 * @var string $pageUrl
	 * @var string $text
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<a class="tpt-quote-list-link" href="<?php echo esc_url( $pageUrl ); ?>">
	<?php echo esc_html( $text ); ?>
	<span class="tpt-quote-list-link__count" data-tpt-quote-list-count>0</span>
</a>
