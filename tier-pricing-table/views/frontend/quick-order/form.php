<?php
	/**
	 * Quick order form. The rows are built by assets/frontend/quick-order.js from the form's JSON config.
	 *
	 * Override in a theme: tier-pricing-table/quick-order/form.php
	 *
	 * @var string $formId
	 * @var string $context shortcode, block or my-account
	 * @var string[] $columns product details to show: image, sku, stock
	 * @var bool $paste
	 * @var string $start search or sheet
	 * @var string $accent
	 * @var array $config
	 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$tptQoResults = $formId . '-results';
$tptQoPaste   = $formId . '-paste';
?>
<div class="tpt-quick-order tpt-quick-order--<?php echo esc_attr( $start ); ?> tpt-quick-order--<?php echo esc_attr( $context ); ?>"
	 id="<?php echo esc_attr( $formId ); ?>"
	 style="--tpt-qo-accent: <?php echo esc_attr( $accent ); ?>;"
	 data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">

	<div class="tpt-quick-order__toolbar">
		<div class="tpt-quick-order__search">
			<label class="screen-reader-text" for="<?php echo esc_attr( $formId ); ?>-search">
				<?php esc_html_e( 'Search products by name or SKU', 'tier-pricing-table' ); ?>
			</label>
			<svg class="tpt-quick-order__search-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10.5 4a6.5 6.5 0 0 1 5.17 10.44l4.45 4.44-1.06 1.06-4.44-4.45A6.5 6.5 0 1 1 10.5 4zm0 1.5a5 5 0 1 0 0 10 5 5 0 0 0 0-10z"/></svg>
			<input type="search"
				   id="<?php echo esc_attr( $formId ); ?>-search"
				   class="tpt-quick-order__search-input"
				   placeholder="<?php esc_attr_e( 'Search by product name or SKU', 'tier-pricing-table' ); ?>"
				   autocomplete="off"
				   role="combobox"
				   aria-autocomplete="list"
				   aria-expanded="false"
				   aria-controls="<?php echo esc_attr( $tptQoResults ); ?>">
			<ul class="tpt-quick-order__results" id="<?php echo esc_attr( $tptQoResults ); ?>" role="listbox" hidden></ul>
		</div>

		<?php if ( $paste ) : ?>
			<button type="button"
					class="tpt-quick-order__paste-toggle"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr( $tptQoPaste ); ?>">
				<?php esc_html_e( 'Paste a list', 'tier-pricing-table' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<?php if ( $paste ) : ?>
		<div class="tpt-quick-order__paste" id="<?php echo esc_attr( $tptQoPaste ); ?>" hidden>
			<label for="<?php echo esc_attr( $formId ); ?>-paste-input">
				<?php esc_html_e( 'One product per line: the SKU, then the quantity.', 'tier-pricing-table' ); ?>
			</label>
			<textarea id="<?php echo esc_attr( $formId ); ?>-paste-input"
					  class="tpt-quick-order__paste-input"
					  rows="5"
					  spellcheck="false"
					  placeholder="<?php echo esc_attr( "TS-BLU-L, 48\nBN-GRY, 24" ); ?>"></textarea>
			<div class="tpt-quick-order__paste-actions">
				<button type="button" class="button wp-element-button tpt-quick-order__paste-add">
					<?php esc_html_e( 'Add to the order', 'tier-pricing-table' ); ?>
				</button>
				<label class="tpt-quick-order__upload">
					<input type="file" class="tpt-quick-order__upload-input" accept=".csv,.txt,text/csv,text/plain">
					<span><?php esc_html_e( 'Upload a CSV file', 'tier-pricing-table' ); ?></span>
				</label>
			</div>
			<p class="tpt-quick-order__paste-result" role="status" aria-live="polite"></p>
		</div>
	<?php endif; ?>

	<div class="tpt-quick-order__notice" role="status" aria-live="polite" hidden></div>

	<table class="tpt-quick-order__table">
		<thead>
			<tr>
				<th scope="col" class="tpt-quick-order__col-product"><?php esc_html_e( 'Product', 'tier-pricing-table' ); ?></th>
				<th scope="col" class="tpt-quick-order__col-qty"><?php esc_html_e( 'Quantity', 'tier-pricing-table' ); ?></th>
				<th scope="col" class="tpt-quick-order__col-price"><?php esc_html_e( 'Price', 'tier-pricing-table' ); ?></th>
				<th scope="col" class="tpt-quick-order__col-total"><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
				<th scope="col" class="tpt-quick-order__col-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove', 'tier-pricing-table' ); ?></span></th>
			</tr>
		</thead>
		<tbody class="tpt-quick-order__rows"></tbody>
	</table>

	<p class="tpt-quick-order__empty">
		<?php
		if ( 'sheet' === $start ) {
			esc_html_e( 'Loading products…', 'tier-pricing-table' );
		} else {
			esc_html_e( 'Search for a product above, or paste a list, to start your order.', 'tier-pricing-table' );
		}
		?>
	</p>

	<button type="button" class="button wp-element-button tpt-quick-order__more" hidden>
		<?php esc_html_e( 'Load more products', 'tier-pricing-table' ); ?>
	</button>

	<div class="tpt-quick-order__footer">
		<div class="tpt-quick-order__summary" aria-live="polite">
			<span class="tpt-quick-order__count"></span>
			<span class="tpt-quick-order__sum"></span>
		</div>
		<?php if ( class_exists( '\TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings' ) && \TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListSettings::isAvailable() ) : ?>
			<?php // the quote list is on: the same lines can go to a quote request instead ?>
			<button type="button" class="button wp-element-button tpt-quick-order__to-quote" disabled>
				<?php esc_html_e( 'Add to quote list', 'tier-pricing-table' ); ?>
			</button>
		<?php endif; ?>
		<button type="button" class="button alt wp-element-button tpt-quick-order__submit" disabled>
			<?php esc_html_e( 'Add to cart', 'tier-pricing-table' ); ?>
		</button>
	</div>
</div>
