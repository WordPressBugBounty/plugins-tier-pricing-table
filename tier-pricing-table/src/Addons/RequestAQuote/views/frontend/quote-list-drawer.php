<?php
	/**
	 * The quote list drawer (opens when a product is added) and the floating "Quote list" button.
	 *
	 * This template can be overridden by copying it to yourtheme/tiered-pricing-table/quote-list-drawer.php.
	 *
	 * @var string $pageUrl
	 * @var bool $isListPage
	 * @var bool $showFloating
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
?>
<div class="tpt-quote-drawer" data-tpt-quote-drawer hidden>
	<div class="tpt-quote-drawer__overlay" data-tpt-quote-drawer-close></div>
	<div class="tpt-quote-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="tpt-quote-drawer-title" tabindex="-1">
		<div class="tpt-quote-drawer__header">
			<h2 class="tpt-quote-drawer__title" id="tpt-quote-drawer-title">
				<?php esc_html_e( 'Quote list', 'tier-pricing-table' ); ?>
				<span class="tpt-quote-drawer__count" data-tpt-quote-list-count-label></span>
			</h2>
			<button type="button" class="tpt-quote-drawer__close" data-tpt-quote-drawer-close aria-label="<?php esc_attr_e( 'Close', 'tier-pricing-table' ); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
			</button>
		</div>
		<p class="tpt-quote-drawer__status" data-tpt-quote-drawer-status></p>
		<div class="tpt-quote-drawer__lines" data-tpt-quote-drawer-lines></div>
		<div class="tpt-quote-drawer__footer">
			<div class="tpt-quote-drawer__total" data-tpt-quote-drawer-total></div>
			<a class="button alt wp-element-button tpt-quote-drawer__cta" href="<?php echo esc_url( $pageUrl ); ?>"><?php esc_html_e( 'Review and send request', 'tier-pricing-table' ); ?></a>
			<button type="button" class="tpt-quote-drawer__continue" data-tpt-quote-drawer-close><?php esc_html_e( 'Continue shopping', 'tier-pricing-table' ); ?></button>
		</div>
	</div>
</div>

<?php if ( ! $isListPage && $showFloating ) : ?>
	<button type="button" class="tpt-quote-fab" data-tpt-quote-fab hidden>
		<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>
		<span><?php esc_html_e( 'Quote list', 'tier-pricing-table' ); ?></span>
		<span class="tpt-quote-fab__count" data-tpt-quote-list-count>0</span>
	</button>
<?php endif; ?>

<div class="tpt-quote-list-announcer screen-reader-text" data-tpt-quote-list-announcer aria-live="polite"></div>
<div class="tpt-quote-toast" data-tpt-quote-toast role="status" hidden></div>
