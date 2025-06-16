<?php use TierPricingTable\Core\ServiceContainer;
	
	defined( "ABSPATH" ) || die();
	
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
		content: url(<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/checkmark.svg' ) ) ?>);
		width: 1.3em;
		display: inline-block;
		padding: 0;
		height: 1.3em;
		vertical-align: middle;
		margin-right: 4px;
	}

	/**
	  * Button styles
	  */
	.tpt-welcome-page-button {
		display: inline-block;
		padding: 15px 30px;
		font-size: 15px;
		text-decoration: none;
		border-radius: 5px;
	}

	.tpt-welcome-page-button-primary {
		background: #96598a;
		color: #fff;
	}

	.tpt-welcome-page-button-primary--border {
		border: 2px solid #fff;
	}

	.tpt-welcome-page-button-primary:hover {
		color: #fff;
		background: #7b3f6f;
	}

	.tpt-welcome-page-button-secondary {
		background: #79ab3f;
		color: #fff;
	}

	.tpt-welcome-page-button-secondary:hover {
		color: #fff;
		background: #5f8a2f;
	}


	.tpt-welcome-page {
		margin-left: -20px;
	}

	.tpt-welcome-page-hero {
		background: #96598a;
		display: flex;
		justify-content: space-between;
		align-items: center;
		color: #fff;
		padding: 20px 40px;
	}

	.tpt-welcome-page-hero__content {
		width: 45%;
	}

	.tpt-welcome-page-hero__image img {
		width: 100%;
		max-height: 500px;
	}

	.tpt-welcome-page-hero__title {
		font-size: 3rem;
		line-height: 3.5rem;
	}

	.tpt-welcome-page-hero__actions {
		display: flex;
		gap: 10px;
	}

	.tpt-welcome-page-features {
		column-count: 2;
		column-gap: 30px;
		padding: 0 40px;
		margin: 40px 0;
	}

	.tpt-welcome-page-feature {
		margin-bottom: 30px; /* Gap between items vertically */
		break-inside: avoid;
	}

	.tpt-welcome-page-feature__image-description {
		text-align: center;
		margin-bottom: 20px;
		font-style: italic;
	}

	.tpt-welcome-page-features--templates {
		column-count: 4;
	}

	.tpt-welcome-page-feature__inner {
		background: #fff;
		padding: 30px;
		border-radius: 10px;
		border: 1px solid #e0e0e0;
	}

	.tpt-welcome-page-feature--template .tpt-welcome-page-feature__inner {
		padding: 10px;
	}

	.tpt-welcome-page-feature__title {
		font-size: 1.5rem;
		font-weight: 500;
		margin-bottom: 20px;
		line-height: 1.5rem;
	}

	.tpt-welcome-page-feature__description {
		font-size: 1.1em;
	}

	.tpt-welcome-page-feature img {
		width: 100%;
	}

	.tpt-welcome-page-feature-arrow img {
		width: 75px;
		display: flex;
		justify-content: space-between;
		padding: 20px 40px;
		transform: rotate(90deg);
	}

	.tpt-welcome-page-section-title {
		font-size: 3em;
		line-height: normal;
		padding: 0 40px;
		margin-top: 40px;
	}

	.tpt-welcome-page-section-title span {
		margin-right: 10px;
		background: #222;
		font-size: 2rem;
		color: #fff;
		display: inline-block;
		padding: 0px 10px;
		border-radius: 10px;
	}

	.tpt-welcome-page-install-notice {
		background: #ebfdeb;
		box-shadow: 0 2px 2px rgba(6, 113, 6, .3);
		color: green;
		padding: 10px 20px;
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
					<?php esc_html_e( 'Tiered Pricing Table is a powerful tool that allows you to create quantity-based pricing for your WooCommerce products.',
						'tier-pricing-table' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'With intuitive templates, flexible pricing rules, and advanced features, this plugin is a perfect fit for any type of store.',
						'tier-pricing-table' ); ?>
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
				   href="<?php echo esc_attr( \TierPricingTable\TierPricingTablePlugin::getContactUsURL() ) ?>"
				   target="_blank">Contact Us</a>
			</div>
		</div>

		<div class="tpt-welcome-page-hero__image">
			<img src="https://tiered-pricing.com/wp-content/uploads/2023/12/Hero-image2-1-1024x702.png"
				 alt="">
		</div>
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
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/product-level-rules.png' ) ) ?>">
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
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/product-page.png' ) ) ?>">
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
				'features' => array(
				),
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
		
		<?php foreach ( $templates as $template ): ?>

			<div class="tpt-welcome-page-feature tpt-welcome-page-feature--template">
				<div class="tpt-welcome-page-feature__title">
					<?php echo esc_html( $template['title'] ); ?>
				</div>

				<div class="tpt-welcome-page-feature__inner">
					<div class="tpt-welcome-page-feature__image">
						<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/templates/' . $template['image'] ) ) ?>">
					</div>
					<div class="tpt-welcome-page-feature__description">
						
						<?php foreach ( $template['features'] as $feature ): ?>
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
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/role-based.png' ) ) ?>">
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
				<?php esc_html_e( 'Apply custom prices in bulk for selected categories and users:',
					'tier-pricing-table' ); ?>
			</div>

			<div class="tpt-welcome-page-feature__inner">
				<div class="tpt-welcome-page-feature__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/global-rules	.png' ) ) ?>">
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
		
		<?php foreach ( $mainFeatures as $feature ): ?>
			<div class="tpt-welcome-page-feature">

				<div class="tpt-welcome-page-feature__title">
					<?php echo esc_html( $feature['title'] ); ?>
				</div>

				<div class="tpt-welcome-page-feature__inner">

					<div class="tpt-welcome-page-feature__image">
						<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/welcome-page/' . $feature['image'] ) ) ?>">
						<div class="tpt-welcome-page-feature__image-description">Product catalog</div>
					</div>

					<div class="tpt-welcome-page-feature__description">
						<?php foreach ( $feature['features'] as $featureItem ): ?>
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

	<style>
		.tpt-welcome-page-side-features {
			padding: 40px;
			display: flex;
			gap: 20px;
			flex-wrap: wrap;
		}

		.tpt-welcome-page-side-feature {
			padding: 20px;
			background: #fff;
			font-weight: 500;
			font-size: 1.5em;
			border: 1px solid #e0e0e0;
			border-radius: 10px;
		}
	</style>

	<section class="tpt-welcome-page-side-features">
		
		<?php foreach ( $otherFeatures as $feature ): ?>

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
			)
		
		?>
		<style>
			.tpt-welcome-page-integrations {
				padding: 40px 0;
				display: flex;
				flex-wrap: wrap;
				row-gap: 40px;
				align-items: center;
				justify-content: center;
			}

			.tpt-welcome-page-integration {
				width: 160px;
				text-align: center;
			}

			.tpt-welcome-page-integrations__image img {
				width: 60%;
				border-radius: 10px;
			}

			.tpt-welcome-page-integrations__name {
				text-align: center;
				margin-top: 10px;
			}

		</style>
		<?php foreach ( $integrations as $integration ): ?>
			<div class="tpt-welcome-page-integration">
				<div class="tpt-welcome-page-integrations__image">
					<img src="<?php echo esc_attr( $fileManager->locateAsset( 'admin/integrations/' . $integration['image'] ) ) ?>">
				</div>
				<div class="tpt-welcome-page-integrations__name">
					<b><?php echo esc_html( $integration['title'] ); ?></b>
				</div>
			</div>
		<?php endforeach; ?>

	</section>

	<style>
		.tpt-welcome-page-contact-us {
			background: #222;
			padding: 80px 40px;
			text-align: center;
		}

		.tpt-welcome-page-contact-us__title {
			font-size: 3em;
			color: #fff;
			line-height: normal;
			margin-bottom: 20px;
		}
	</style>

	<section class="tpt-welcome-page-contact-us">
		<div class="tpt-welcome-page-contact-us__title">Have a question?</div>
		<div class="tpt-welcome-page-contact-us__button">
			<a href="<?php echo esc_attr( \TierPricingTable\TierPricingTablePlugin::getContactUsURL() ) ?>"
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