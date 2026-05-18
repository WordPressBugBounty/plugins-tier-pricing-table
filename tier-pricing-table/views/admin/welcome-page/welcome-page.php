<?php use TierPricingTable\Core\ServiceContainer;

	defined( 'ABSPATH' ) || die();

	$fileManager = ServiceContainer::getInstance()->getFileManager();
?>
<style>
	/**
	  * General styles
	 */
	.notice, .error {
		display: none;
	}

	.tpt-checkmark {
		display: block;
		margin: 10px 0;
		font-weight: 500;
	}

	.tpt-checkmark::before {
		content: url(<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/checkmark.svg' ) ); ?>);
		width: 1.3em;
		display: inline-block;
		padding: 0;
		height: 1.3em;
		vertical-align: middle;
		margin-right: 6px;
	}

	/**
	  * Button styles
	  */
	.tpt-welcome-page-button {
		display: inline-block;
		padding: 14px 28px;
		font-size: 15px;
		font-weight: 600;
		text-decoration: none;
		border-radius: 8px;
		transition: all 0.2s ease;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}

	.tpt-welcome-page-button-primary {
		background: #96598a;
		color: #fff;
	}

	.tpt-welcome-page-button-primary--border {
		border: 2px solid rgba(255, 255, 255, 0.3);
		box-shadow: none;
	}

	.tpt-welcome-page-button-primary:hover {
		color: #fff;
		background: #7b3f6f;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
	}

	.tpt-welcome-page-button-primary--border:hover {
		border-color: #fff;
	}

	.tpt-welcome-page-button-secondary {
		background: #79ab3f;
		color: #fff;
	}

	.tpt-welcome-page-button-secondary:hover {
		color: #fff;
		background: #5f8a2f;
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
	}

	.tpt-welcome-page {
		margin-left: -20px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	}

	.tpt-welcome-page-hero {
		background: linear-gradient(135deg, #7b3f6f 0%, #96598a 100%);
		display: flex;
		justify-content: space-between;
		align-items: center;
		color: #fff;
		padding: 40px 50px;
		box-shadow: 0 4px 12px rgba(150, 89, 138, 0.2);
	}

	.tpt-welcome-page-hero__content {
		width: 45%;
	}

	.tpt-browser-mockup {
		background: #fff;
		border-radius: 12px;
		box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
		width: 100%;
		max-width: 650px;
		transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
		transition: transform 0.5s ease;
		margin-left: auto;
		overflow: hidden;
	}

	.tpt-browser-mockup:hover {
		transform: perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1.02);
	}

	.tpt-browser-mockup-header {
		background: #f1f5f9;
		padding: 12px 16px;
		display: flex;
		align-items: center;
		border-bottom: 1px solid #e2e8f0;
	}

	.tpt-browser-mockup-header .dot {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 50%;
		margin-right: 8px;
	}

	.tpt-browser-mockup-header .dot-red {
		background: #ef4444;
	}

	.tpt-browser-mockup-header .dot-yellow {
		background: #eab308;
	}

	.tpt-browser-mockup-header .dot-green {
		background: #22c55e;
	}

	.tpt-browser-mockup-address {
		background: #fff;
		border: 1px solid #cbd5e1;
		border-radius: 4px;
		padding: 4px 12px;
		font-size: 0.8rem;
		color: #94a3b8;
		margin-left: 20px;
		flex: 1;
		text-align: center;
	}

	.tpt-interactive-preview {
		display: flex;
		background: #fff;
		color: #333;
		padding: 24px;
		gap: 30px;
		box-sizing: border-box;
	}

	.tpt-interactive-preview * {
		box-sizing: border-box;
	}

	.tpt-ip-left {
		flex: 0 0 30%;
		background: #eef2f6;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		min-width: 0;
	}

	.tpt-ip-left img {
		width: 100%;
		max-height: 250px;
		object-fit: contain;
	}

	.tpt-ip-right {
		flex: 1;
		display: flex;
		flex-direction: column;
		text-align: left;
		min-width: 0;
	}

	.tpt-ip-title {
		font-size: 2rem;
		font-weight: 300;
		margin: 0 0 10px 0;
		color: #1e293b;
		line-height: 1;
	}

	.tpt-ip-main-price {
		font-size: 1.5rem;
		margin-bottom: 15px;
		color: #475569;
	}

	.tpt-ip-main-price del {
		color: #9ca3af;
		margin-right: 8px;
	}

	.tpt-ip-desc {
		color: #64748b;
		margin-top: 0;
		margin-bottom: 20px;
		font-size: 0.95em;
	}

	.tpt-ip-blocks {
		display: flex;
		flex-direction: column;
		gap: 12px;
		margin-bottom: 20px;
	}

	.tpt-hero-preview-row {
		display: flex;
		gap: 12px;
		justify-content: flex-start;
		flex-wrap: wrap;
	}

	.tpt-hero-preview-block {
		background: #f8fafc;
		border: 1px solid #cbd5e1;
		border-radius: 6px;
		padding: 16px 15px 12px 15px;
		position: relative;
		flex: 1;
		min-width: 120px;
		text-align: left;
		color: #475569;
		box-sizing: border-box;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.tpt-hero-preview-block:hover {
		border-color: #94a3b8;
	}

	.tpt-hero-preview-block.is-selected {
		border-color: #96598a;
		box-shadow: 0 0 0 1px #96598a;
	}

	.tpt-hero-preview-qty {
		font-size: 0.95em;
		margin-bottom: 6px;
		color: #64748b;
		white-space: nowrap;
	}

	.tpt-hero-preview-price {
		font-size: 1.15em;
		color: #334155;
		white-space: nowrap;
	}

	.tpt-hero-preview-price strong {
		font-weight: 700;
	}

	.tpt-hero-preview-discount {
		font-size: 0.75em;
		font-weight: 600;
		color: #64748b;
		margin-left: 4px;
	}

	.tpt-ip-add-to-cart {
		display: flex;
		gap: 12px;
		margin-bottom: 25px;
	}

	.tpt-ip-qty-input {
		width: 70px;
		padding: 8px;
		border: 1px solid #cbd5e1;
		border-radius: 4px;
		font-size: 1.05rem;
		text-align: center;
		background: #f1f5f9;
		color: #334155;
	}

	.tpt-ip-btn {
		background: #334155;
		color: #fff;
		border: none;
		padding: 8px 20px;
		border-radius: 4px;
		font-size: 1rem;
		font-weight: 600;
		cursor: pointer;
		transition: background 0.2s ease;
	}

	.tpt-ip-btn:hover {
		background: #1e293b;
	}

	.tpt-ip-summary {
		border-top: 1px solid #e2e8f0;
		padding-top: 15px;
	}

	.tpt-ip-summary-row {
		display: flex;
		justify-content: space-between;
		margin-bottom: 8px;
		font-size: 1.05rem;
		color: #475569;
		font-weight: 600;
	}

	.tpt-ip-summary-row--total {
		font-size: 1.3rem;
		color: #64748b;
		margin-bottom: 0;
	}

	.tpt-ip-summary-row--total strong {
		color: #334155;
	}

	.tpt-welcome-page-hero__title {
		font-size: 3.5rem;
		line-height: 1.1;
		margin-bottom: 20px;
		font-weight: 700;
	}

	.tpt-welcome-page-hero__description {
		font-size: 1.1rem;
		line-height: 1.6;
		margin-bottom: 30px;
		opacity: 0.9;
	}

	.tpt-welcome-page-hero__actions {
		display: flex;
		gap: 15px;
	}

	.tpt-welcome-page-features {
		column-count: 2;
		column-gap: 40px;
		padding: 0 50px;
		margin: 50px 0;
	}

	.tpt-welcome-page-feature {
		margin-bottom: 40px;
		break-inside: avoid;
	}

	.tpt-welcome-page-feature__image-description {
		text-align: center;
		margin-bottom: 20px;
		font-style: italic;
		color: #64748b;
		font-size: 0.9em;
	}

	.tpt-welcome-page-features--templates {
		column-count: 4;
		column-gap: 30px;
	}

	.tpt-welcome-page-feature__inner {
		background: #fff;
		padding: 30px;
		border-radius: 12px;
		border: 1px solid #f1f5f9;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.tpt-welcome-page-feature__inner:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -5px rgba(0, 0, 0, 0.04);
	}

	.tpt-welcome-page-feature--template .tpt-welcome-page-feature__inner {
		padding: 15px;
	}

	.tpt-welcome-page-feature__title {
		font-size: 1.4rem;
		font-weight: 600;
		margin-bottom: 20px;
		line-height: 1.4;
		color: #1e293b;
	}

	.tpt-welcome-page-feature__description {
		font-size: 1.05em;
		color: #334155;
	}

	.tpt-welcome-page-feature img {
		width: 100%;
		border-radius: 6px;
	}

	.tpt-welcome-page-section-title {
		font-size: 2.5em;
		font-weight: 700;
		line-height: 1.2;
		padding: 0 50px;
		margin-top: 60px;
		color: #0f172a;
		display: flex;
		align-items: center;
	}

	.tpt-welcome-page-section-title span {
		margin-right: 15px;
		background: linear-gradient(135deg, #7b3f6f 0%, #96598a 100%);
		font-size: 1.5rem;
		color: #fff;
		display: inline-block;
		padding: 6px 16px;
		border-radius: 8px;
		box-shadow: 0 4px 6px rgba(150, 89, 138, 0.3);
	}

	.tpt-welcome-page-install-notice {
		background: #dcfce7;
		color: #166534;
		padding: 12px 24px;
		border-radius: 8px;
		border: 1px solid #bbf7d0;
		display: flex;
		align-items: center;
		font-weight: 500;
		font-size: 1.05em;
	}

	.tpt-welcome-page-install-notice .dashicons {
		margin-right: 8px;
		color: #15803d;
	}

	.tpt-welcome-page-side-features {
		padding: 0 50px;
		margin: 40px 0;
		display: flex;
		gap: 24px;
		flex-wrap: wrap;
	}

	.tpt-welcome-page-side-feature {
		padding: 20px 25px;
		background: #fff;
		font-weight: 500;
		font-size: 1.25em;
		color: #334155;
		border: 1px solid #f1f5f9;
		border-radius: 12px;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
		transition: transform 0.2s ease, box-shadow 0.2s ease;
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.tpt-welcome-page-side-feature:hover {
		transform: translateY(-4px);
		box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
		border-color: #e2e8f0;
	}

</style>
<main class="tpt-welcome-page">

	<div class="tpt-welcome-page-install-notice">
		<span class="dashicons dashicons-plugins-checked"></span> Thanks for installing the plugin! Below you will find
		a quick overview of the main features.
	</div>

	<header class="tpt-welcome-page-hero">

		<div class="tpt-welcome-page-hero__content">
			<div class="tpt-welcome-page-hero__title">
				<div>Welcome to</div>
				<div><b>Tiered Pricing Table</b></div>
			</div>

			<div class="tpt-welcome-page-hero__description">
				<p>
					<?php
						esc_html_e( 'Tiered Pricing Table is a powerful tool that allows you to create quantity-based pricing for your WooCommerce products.',
								'tier-pricing-table' );
					?>
				</p>
				<p>
					<?php
						esc_html_e( 'With intuitive templates, flexible pricing rules, and advanced features, this plugin is a perfect fit for any type of store.',
								'tier-pricing-table' );
					?>
				</p>
			</div>
			<div class="tpt-welcome-page-hero__actions">
				<a href="<?php echo esc_attr( ServiceContainer::getInstance()->getSettings()->getLink() ); ?>"
				   class="tpt-welcome-page-button tpt-welcome-page-button-secondary">
					<?php esc_html_e( 'Settings', 'tier-pricing-table' ); ?>
				</a>

				<a href="<?php echo esc_attr( \TierPricingTable\TierPricingTablePlugin::getDocumentationURL() ); ?>"
				   target="_blank"
				   class="tpt-welcome-page-button tpt-welcome-page-button-primary tpt-welcome-page-button-primary--border">
					<?php esc_html_e( 'Documentation', 'tier-pricing-table' ); ?>
				</a>
			</div>

			<div class="tpt-welcome-page-hero__additional" style="font-size: 1.2em; margin-top: 20px;">
				Questions? We're here to help.
				<a style="color: #fff"
				   href="<?php echo esc_attr( \TierPricingTable\TierPricingTablePlugin::getContactUsURL() ); ?>"
				   target="_blank">Contact Us</a>
			</div>
		</div>

		<div class="tpt-welcome-page-hero__image">
			<div class="tpt-browser-mockup">
				<div class="tpt-browser-mockup-header">
					<span class="dot dot-red"></span>
					<span class="dot dot-yellow"></span>
					<span class="dot dot-green"></span>
					<div class="tpt-browser-mockup-address">yoursite.com/product/cap</div>
				</div>
				<div class="tpt-interactive-preview" id="tpt-interactive-preview">
					<div class="tpt-ip-left">
						<svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"
						     stroke-linejoin="round" style="width: 80%; height: auto; max-height: 200px;">
							<path d="M20.38 3.46L16 2a4 4 0 01-8 0L3.62 3.46a2 2 0 00-1.34 2.23l.58 3.47a1 1 0 00.99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 002-2V10h2.15a1 1 0 00.99-.84l.58-3.47a2 2 0 00-1.34-2.23z"/>
						</svg>
					</div>
					<div class="tpt-ip-right">
						<h2 class="tpt-ip-title">Cap</h2>
						<div class="tpt-ip-main-price" id="tpt-ip-main-price">
							<del>$100.00</del>
							<span>$75.00</span></div>
						<p class="tpt-ip-desc">This is a simple product.</p>

						<div class="tpt-ip-blocks">
							<div class="tpt-hero-preview-row">
								<div class="tpt-hero-preview-block tpt-ip-block" data-qty="1" data-price="100.00">
									<div class="tpt-hero-preview-qty">1 - 9 pieces</div>
									<div class="tpt-hero-preview-price"><strong>$100.00</strong></div>
								</div>
								<div class="tpt-hero-preview-block tpt-ip-block" data-qty="10" data-price="90.00">
									<div class="tpt-hero-preview-qty">10 - 19 pieces</div>
									<div class="tpt-hero-preview-price"><strong>$90.00</strong> <span
												class="tpt-hero-preview-discount">(10% off)</span></div>
								</div>
							</div>
							<div class="tpt-hero-preview-row">
								<div class="tpt-hero-preview-block tpt-ip-block" data-qty="20" data-price="85.00">
									<div class="tpt-hero-preview-qty">20 - 49 pieces</div>
									<div class="tpt-hero-preview-price"><strong>$85.00</strong> <span
												class="tpt-hero-preview-discount">(15% off)</span></div>
								</div>
								<div class="tpt-hero-preview-block tpt-ip-block" data-qty="50" data-price="80.00">
									<div class="tpt-hero-preview-qty">50 - 99 pieces</div>
									<div class="tpt-hero-preview-price"><strong>$80.00</strong> <span
												class="tpt-hero-preview-discount">(20% off)</span></div>
								</div>
							</div>
							<div class="tpt-hero-preview-row">
								<div class="tpt-hero-preview-block tpt-ip-block is-selected" data-qty="100"
								     data-price="75.00" style="max-width: 200px;">
									<div class="tpt-hero-preview-qty">100+ pieces</div>
									<div class="tpt-hero-preview-price"><strong>$75.00</strong> <span
												class="tpt-hero-preview-discount">(25% off)</span></div>
								</div>
							</div>
						</div>

						<div class="tpt-ip-add-to-cart">
							<input type="number" id="tpt-ip-qty" class="tpt-ip-qty-input" value="100" min="1">
							<button class="tpt-ip-btn">Add to cart</button>
						</div>

						<div class="tpt-ip-summary">
							<div class="tpt-ip-summary-row">
								<span id="tpt-ip-summary-qty">100x</span>
								<span id="tpt-ip-summary-each">$75.00</span>
							</div>
							<div class="tpt-ip-summary-row tpt-ip-summary-row--total">
								<span>Cap</span>
								<strong id="tpt-ip-summary-total">$7,500.00</strong>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			document.addEventListener('DOMContentLoaded', function () {
				const blocks = document.querySelectorAll('.tpt-ip-block');
				const qtyInput = document.getElementById('tpt-ip-qty');
				const mainPrice = document.getElementById('tpt-ip-main-price');
				const summaryQty = document.getElementById('tpt-ip-summary-qty');
				const summaryEach = document.getElementById('tpt-ip-summary-each');
				const summaryTotal = document.getElementById('tpt-ip-summary-total');

				function getPriceForQty(qty) {
					if (qty >= 100) return 75.00;
					if (qty >= 50) return 80.00;
					if (qty >= 20) return 85.00;
					if (qty >= 10) return 90.00;
					return 100.00;
				}

				function updateUI(qty) {
					qty = parseInt(qty) || 1;
					if (qty < 1) qty = 1;
					if (qtyInput.value != qty) qtyInput.value = qty;

					const price = getPriceForQty(qty);

					blocks.forEach(b => b.classList.remove('is-selected'));

					let selectedIndex = 0;
					if (qty >= 100) selectedIndex = 4;
					else if (qty >= 50) selectedIndex = 3;
					else if (qty >= 20) selectedIndex = 2;
					else if (qty >= 10) selectedIndex = 1;

					if (blocks[selectedIndex]) blocks[selectedIndex].classList.add('is-selected');

					if (price < 100) {
						mainPrice.innerHTML = `<del>$100.00</del> <span>$${price.toFixed(2)}</span>`;
					} else {
						mainPrice.innerHTML = `<span>$100.00</span>`;
					}

					summaryQty.innerText = `${qty}x`;
					summaryEach.innerText = `$${price.toFixed(2)}`;
					summaryTotal.innerText = `$${(price * qty).toLocaleString('en-US', {
						minimumFractionDigits: 2,
						maximumFractionDigits: 2
					})}`;
				}

				blocks.forEach(block => {
					block.addEventListener('click', function () {
						const qty = parseInt(this.getAttribute('data-qty'));
						updateUI(qty);
					});
				});

				if (qtyInput) {
					qtyInput.addEventListener('input', function () {
						updateUI(this.value);
					});
				}

				updateUI(100);
			});
		</script>
	</header>

	<div class="tpt-welcome-page-section-title">
		<span>#1</span>
		Easy Setup
	</div>

	<section class="tpt-welcome-page-features">

		<div class="tpt-welcome-page-feature">

			<div class="tpt-welcome-page-feature__title">
				<?php esc_html_e( 'Apply tiered pricing to products', 'tier-pricing-table' ); ?>:
			</div>

			<div class="tpt-welcome-page-feature__inner">
				<div class="tpt-welcome-page-feature__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/product-level-rules.png' ) ); ?>">
					<div class="tpt-welcome-page-feature__image-description">Product edit page</div>
				</div>
				<div class="tpt-welcome-page-feature__description">
					<span class="tpt-checkmark"> Add unlimited quantity-based prices.</span>
					<span class="tpt-checkmark"> Fixed prices or percentage discounts.</span>
					<span class="tpt-checkmark"> Works great with variable products.</span>
				</div>
			</div>

		</div>

		<div class="tpt-welcome-page-feature">

			<div class="tpt-welcome-page-feature__title">
				<?php esc_html_e( 'Prices automatically displayed on the product page:', 'tier-pricing-table' ); ?>
			</div>

			<div class="tpt-welcome-page-feature__inner">
				<div class="tpt-welcome-page-feature__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/product-page.png' ) ); ?>">
					<div class="tpt-welcome-page-feature__image-description">Product page</div>
				</div>

			</div>
		</div>

	</section>

	<div class="tpt-welcome-page-section-title">
		<span>#2</span>
		Various Pricing Templates
	</div>

	<?php
		$templates = array(
				array(
						'title'    => __( 'Pricing Table', 'tier-pricing-table' ),
						'image'    => 'table.png',
						'features' => array(
								__( 'Ability to add custom columns.', 'tier-pricing-table' ),
								__( 'Customizable columns titles', 'tier-pricing-table' ),
								__( 'Customize accent color.', 'tier-pricing-table' ),
						),
				),
				array(
						'title'    => __( 'Pricing Blocks', 'tier-pricing-table' ),
						'image'    => 'blocks-2.png',
						'features' => array(
								__( 'Show/hide percentage discount.', 'tier-pricing-table' ),
						),
				),
				array(
						'title'    => __( 'Pricing Blocks #2', 'tier-pricing-table' ),
						'image'    => 'blocks-3.png',
						'features' => array(),
				),
				array(
						'title'    => __( 'Pricing Options', 'tier-pricing-table' ),
						'image'    => 'options.png',
						'features' => array(
								__( 'Customize template with various available variables.', 'tier-pricing-table' ),
								__( 'Show/hide total in a selected option.', 'tier-pricing-table' ),
						),
				),

				array(
						'title'    => __( 'Tooltip', 'tier-pricing-table' ),
						'image'    => 'tooltip.png',
						'features' => array(
								__( 'Customizable color and size.', 'tier-pricing-table' ),
						),
				),

				array(
						'title'    => __( 'Horizontal table', 'tier-pricing-table' ),
						'image'    => 'horizontal-table.png',
						'features' => array(),
				),
				array(
						'title'    => __( 'Plain text', 'tier-pricing-table' ),
						'image'    => 'plain-text.png',
						'features' => array(
								__( 'Customize template with various available variables.', 'tier-pricing-table' ),
						),
				),
				array(
						'title'    => __( 'Dropdown', 'tier-pricing-table' ),
						'image'    => 'dropdown.png',
						'features' => array(
								__( 'Customizable template.', 'tier-pricing-table' ),
						),
				),
		);
	?>
	<section class="tpt-welcome-page-features tpt-welcome-page-features--templates">

		<?php foreach ( $templates as $template ) : ?>

			<div class="tpt-welcome-page-feature tpt-welcome-page-feature--template">
				<div class="tpt-welcome-page-feature__title">
					<?php echo esc_html( $template['title'] ); ?>
				</div>

				<div class="tpt-welcome-page-feature__inner">
					<div class="tpt-welcome-page-feature__image">
						<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/templates/' . $template['image'] ) ); ?>">
					</div>
					<div class="tpt-welcome-page-feature__description">

						<?php foreach ( $template['features'] as $feature ) : ?>
							<span class="tpt-checkmark"><?php echo esc_html( $feature ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>

			</div>

		<?php endforeach; ?>
	</section>

	<div class="tpt-welcome-page-section-title">
		<span>#3</span>
		Flexible Pricing
	</div>

	<section class="tpt-welcome-page-features tpt-welcome-page-features--flexible-pricing">
		<div class="tpt-welcome-page-feature">

			<div class="tpt-welcome-page-feature__title">
				<?php esc_html_e( 'Apply custom prices to any user role', 'tier-pricing-table' ); ?>:
			</div>

			<div class="tpt-welcome-page-feature__inner">
				<div class="tpt-welcome-page-feature__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/role-based.png' ) ); ?>">
				</div>
				<div class="tpt-welcome-page-feature__description">
					<span class="tpt-checkmark"> Add unlimited role-based pricing.</span>
					<span class="tpt-checkmark"> Control regular & sale price or provide a percentage discount.</span>
					<span class="tpt-checkmark"> Control minimum, maximum and quantity step.</span>
					<span class="tpt-checkmark"> Works great with variable products.</span>
				</div>
			</div>

		</div>

		<div class="tpt-welcome-page-feature">

			<div class="tpt-welcome-page-feature__title">
				<?php
					esc_html_e( 'Apply custom prices in bulk for selected categories and users:',
							'tier-pricing-table' );
				?>
			</div>

			<div class="tpt-welcome-page-feature__inner">
				<div class="tpt-welcome-page-feature__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/global-rules	.png' ) ); ?>">
				</div>
				<div class="tpt-welcome-page-feature__description">
					<span class="tpt-checkmark"> Control regular prices, tiered pricing and quantity limits in one place.</span>
					<span class="tpt-checkmark"> Apply tiered pricing across multiple products.</span>
					<span class="tpt-checkmark"> Select products or product categories the rule works for.</span>
					<span class="tpt-checkmark"> Select users or user roles the rule works for.</span>
				</div>
			</div>
		</div>
	</section>

	<div class="tpt-welcome-page-section-title">
		<span>#4</span>
		Advanced Features
	</div>

	<section class="tpt-welcome-page-features tpt-welcome-page-features--plugin-features">
		<?php
			$mainFeatures = array(
					array(
							'title'    => __( 'Instant price updating', 'tier-pricing-table' ),
							'image'    => 'totals.png',
							'features' => array(
									'Price updates instantly when customers change the quantity.',
									'Instant totals with three different available templates.',
									'Instant “You save” label which shows to your customers difference between original and sale price.',
							),
					),
					array(
							'title'    => __( 'Cart', 'tier-pricing-table' ),
							'image'    => 'cart.png',
							'features' => array(
									'Cart upsell to motivate customers to purchase more.',
									'Customize cart upsells template.',
									'Tiered price in the cart appears as a discount.',
							),
					),
					array(
							'title'    => __( 'Catalog prices', 'tier-pricing-table' ),
							'image'    => 'catalog.png',
							'features' => array(
									'Show the lowest price.',
									'Customize the lowest price prefix: “from $10.00”, “as low as $10.00” or whatever you want.',
									'Show the price range based on tiered pricing.',
							),
					),
					array(
							'title'    => __( 'Product catalog (Category page)', 'tier-pricing-table' ),
							'image'    => 'catalog-render.png',
							'features' => array(
									'Customize template (can be different from product page).',
									'Show quantity field.',
							),
					),
			);
		?>

		<?php foreach ( $mainFeatures as $feature ) : ?>
			<div class="tpt-welcome-page-feature">

				<div class="tpt-welcome-page-feature__title">
					<?php echo esc_html( $feature['title'] ); ?>
				</div>

				<div class="tpt-welcome-page-feature__inner">

					<div class="tpt-welcome-page-feature__image">
						<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/' . $feature['image'] ) ); ?>">
						<div class="tpt-welcome-page-feature__image-description">Product catalog</div>
					</div>

					<div class="tpt-welcome-page-feature__description">
						<?php foreach ( $feature['features'] as $featureItem ) : ?>
							<span class="tpt-checkmark"><?php echo esc_html( $featureItem ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>

			</div>
		<?php endforeach; ?>
	</section>

	<div class="tpt-welcome-page-section-title">
		<span>#5</span>
		Other Features That Make The Plugin Unique
	</div>

	<?php
		$otherFeatures = array(
				array(
						'title' => __( 'Tier Labels & Badges', 'tier-pricing-table' ),
						'icon'  => '🏷️',
				),
				array(
						'title' => __( 'Data Cleanup & Role Management Tools', 'tier-pricing-table' ),
						'icon'  => '🛠️',
				),
				array(
						'title' => __( 'Import \ Export', 'tier-pricing-table' ),
						'icon'  => '🔁',
				),
				array(
						'title' => __( 'REST API', 'tier-pricing-table' ),
						'icon'  => '⚙️',
				),
				array(
						'title' => __( 'Admin-made orders supported', 'tier-pricing-table' ),
						'icon'  => '✅',
				),
				array(
						'title' => __( 'Built-in cache', 'tier-pricing-table' ),
						'icon'  => '🚀',
				),
				array(
						'title' => __( 'Coupons management', 'tier-pricing-table' ),
						'icon'  => '🎫',
				),
				array(
						'title' => __( 'Shortcode \ Gutenberg \ Elementor', 'tier-pricing-table' ),
						'icon'  => '🧱',
				),
				array(
						'title' => __( 'Hide prices for logged-out users', 'tier-pricing-table' ),
						'icon'  => '🔑',
				),
				array(
						'title' => __( 'Works with any theme', 'tier-pricing-table' ),
						'icon'  => '✨',
				),
				array(
						'title' => __( 'Debug mode', 'tier-pricing-table' ),
						'icon'  => '⚙️',
				),
		)
	?>

	<section class="tpt-welcome-page-side-features">
		<?php foreach ( $otherFeatures as $feature ) : ?>
			<div class="tpt-welcome-page-side-feature">
				<?php echo esc_html( $feature['icon'] ); ?><?php echo esc_html( ' ' . $feature['title'] ); ?>
			</div>
		<?php endforeach; ?>
	</section>


	<div class="tpt-welcome-page-section-title">
		<span>#6</span>
		Integrations with 3rd party plugins
	</div>

	<section class="tpt-welcome-page-integrations">
		<?php
			$integrations = array(
					array(
							'title' => 'WP All Import',
							'image' => 'wpallimport-icon.png',
					),
					array(
							'title' => 'WPML',
							'image' => 'wpml-multicurrency-icon.png',
					),
					array(
							'title' => 'Elementor',
							'image' => 'elementor-icon.svg',
					),
					array(
							'title' => 'WooCommerce Product Add-ons',
							'image' => 'woocommerce-develop.jpeg',
					),
					array(
							'title' => 'Yith Request a Quote',
							'image' => 'yith-raq-icon.jpeg',
					),
					array(
							'title' => 'Addify Request a Quote',
							'image' => 'addify-raq-icon.png',
					),
					array(
							'title' => 'Aelia Multicurrency',
							'image' => 'aelia-icon.svg',
					),
					array(
							'title' => 'WooCommerce Bundles',
							'image' => 'woocommerce-develop.jpeg',
					),
					array(
							'title' => 'Fox Multicurrency',
							'image' => 'fox-icon.png',
					),
					array(
							'title' => 'Mix & Match Products',
							'image' => 'mix-match-icon.png',
					),
					array(
							'title' => 'Currency Switcher by "WP Experts"',
							'image' => 'wccs-icon.png',
					),
					array(
							'title' => 'WooCommerce Deposits',
							'image' => 'woocommerce-develop.jpeg',
					),
					array(
							'title' => 'WPML Multicurrency',
							'image' => 'wpml-multicurrency-icon.png',
					),
					array(
							'title' => 'WooCommerce Custom Product Addons',
							'image' => 'wcpa-icon.png',
					),
			);
		?>
		<style>
			.tpt-welcome-page-integrations {
				padding: 40px 50px;
				display: flex;
				flex-wrap: wrap;
				gap: 30px;
				align-items: center;
				justify-content: flex-start;
			}

			.tpt-welcome-page-integration {
				width: 140px;
				text-align: center;
				transition: transform 0.2s ease;
			}

			.tpt-welcome-page-integration:hover {
				transform: translateY(-6px) scale(1.05);
			}

			.tpt-welcome-page-integrations__image img {
				width: 65%;
				border-radius: 16px;
				box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
			}

			.tpt-welcome-page-integrations__name {
				text-align: center;
				margin-top: 12px;
				font-size: 0.95em;
				color: #475569;
			}

		</style>
		<?php foreach ( $integrations as $integration ) : ?>
			<div class="tpt-welcome-page-integration">
				<div class="tpt-welcome-page-integrations__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/integrations/' . $integration['image'] ) ); ?>">
				</div>
				<div class="tpt-welcome-page-integrations__name">
					<b><?php echo esc_html( $integration['title'] ); ?></b>
				</div>
			</div>
		<?php endforeach; ?>
	</section>

	<style>
		.tpt-welcome-page-contact-us {
			background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
			padding: 80px 40px;
			text-align: center;
			border-radius: 16px;
			margin: 20px 50px 60px;
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		}

		.tpt-welcome-page-contact-us__title {
			font-size: 2.5em;
			font-weight: 700;
			color: #fff;
			line-height: 1.2;
			margin-bottom: 30px;
		}
	</style>

	<section class="tpt-welcome-page-contact-us">
		<div class="tpt-welcome-page-contact-us__title">Have a question?</div>
		<div class="tpt-welcome-page-contact-us__button">
			<a href="<?php echo esc_attr( \TierPricingTable\TierPricingTablePlugin::getContactUsURL() ); ?>"
			   target="_blank"
			   class="tpt-welcome-page-button tpt-welcome-page-button-primary">Contact Us</a>
		</div>
	</section>
</main>

<style>
	@media screen and (max-width: 900px) {

		.tpt-welcome-page-hero__content {
			width: 100%;
		}

		.tpt-welcome-page-hero__image {
			display: none
		}

		.tpt-welcome-page-hero__additional {
			font-size: 1em;
		}

		.tpt-welcome-page-features {
			column-count: 1;
		}

		.tpt-welcome-page-features--templates {
			column-count: 2;
		}

		.tpt-welcome-page-feature__image-description {
			font-size: 0.8em;
		}

		.tpt-welcome-page-feature__title {
			font-size: 1.4em;
		}

		.tpt-welcome-page-feature__description {
			font-size: 1em;
		}

		.tpt-welcome-page-section-title {
			font-size: 2em;
		}

		.tpt-welcome-page-side-features {
			gap: 10px;
		}

		.tpt-welcome-page-side-feature {
			font-size: 1.2em;
			padding: 10px;
		}

		.tpt-welcome-page-integrations {
			padding: 40px 20px;
		}

		.tpt-welcome-page-integration {
			width: 120px;
		}

		.tpt-welcome-page-contact-us {
			padding: 40px 20px;
		}

		.tpt-welcome-page-contact-us__title {
			font-size: 2em;
		}
	}
</style>