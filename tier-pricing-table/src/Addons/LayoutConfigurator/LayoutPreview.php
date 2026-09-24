<?php namespace TierPricingTable\Addons\LayoutConfigurator;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\PriceManager;
use TierPricingTable\PricingRule;
use TierPricingTable\PricingTable;
use TierPricingTable\Settings\Settings;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;

/**
 * Live preview of the pricing layout for the settings page.
 *
 * Renders the real frontend templates with sample tiers on an in-memory preview product, using the values currently
 * chosen in the form (layout, design style, colour, title, quantity format, ...). The markup is shown
 * inside an iframe so the frontend stylesheet and the templates' own form controls stay isolated from
 * the admin page. The tooltip layout is not previewed.
 */
class LayoutPreview {

	use ServiceContainerTrait;

	const AJAX_ACTION = 'tpt_layout_preview';

	/**
	 * Preview kinds: what the panel demonstrates and which layout it forces.
	 */
	const KINDS = array(
		'display_type'  => null,
		'table_style'   => 'table',
		'blocks_style'  => 'blocks',
		'options_style' => 'options',
	);

	/**
	 * Form fields (option ids without prefix) the preview reacts to, mapped to renderer settings.
	 * "bool" fields arrive as yes/no.
	 */
	const FIELDS = array(
		'display_type'                        => array( 'display_type', 'text' ),
		'pricing_table_style'                 => array( 'table_style', 'text' ),
		'pricing_blocks_style'                => array( 'blocks_style', 'text' ),
		'pricing_options_style'               => array( 'options_style', 'text' ),
		'pricing_dropdown_style'              => array( 'dropdown_style', 'text' ),
		'pricing_plain_text_style'            => array( 'plain_text_style', 'text' ),
		'quantity_type'                       => array( 'quantity_type', 'text' ),
		'tiers_order'                         => array( 'tiers_order', 'text' ),
		'discount_format'                     => array( 'discount_format', 'text' ),
		'table_title'                         => array( 'title', 'text' ),
		'selected_quantity_color'             => array( 'active_tier_color', 'color' ),
		'compact_layout'                      => array( 'compact_layout', 'yesno' ),
		'layout_spacing'                      => array( 'layout_spacing', 'text' ),
		'cell_padding'                        => array( 'cell_padding', 'text' ),
		'discount_badge_color'                => array( 'discount_badge_color', 'optional-color' ),
		'table_active_border'                 => array( 'active_tier_border', 'yesno' ),
		'tiers_limit'                         => array( 'tiers_limit', 'text' ),
		'head_quantity_text'                  => array( 'quantity_column_title', 'text' ),
		'head_price_text'                     => array( 'price_column_title', 'text' ),
		'head_discount_text'                  => array( 'discount_column_title', 'text' ),
		'show_discount_column'                => array( 'show_discount_column', 'bool' ),
		'clickable_table_rows'                => array( 'clickable_rows', 'bool' ),
		'options_show_total'                  => array( 'options_show_total', 'bool' ),
		'options_show_original_product_price' => array( 'options_show_original_product_price', 'bool' ),
		'options_show_default_option'         => array( 'options_show_default_option', 'bool' ),
		'options_default_option_text'         => array( 'options_default_option_text', 'html' ),
		'options_option_text'                 => array( 'options_option_text', 'html' ),
		'tooltip_border'                      => array( 'tooltip_border', 'bool' ),
		'plain_text_template'                 => array( 'plain_text_option_text', 'html' ),
		'plain_text_show_first_tier'          => array( 'plain_text_show_default_option', 'bool' ),
		'plain_text_first_tier_template'      => array( 'plain_text_default_option_text', 'html' ),
	);

	const CONTEXTS = array( 'product-page', 'shop-loop' );

	/**
	 * Regular price of the preview product (store currency). The skeleton shows the same amount.
	 */
	const PRICE = 45;

	protected ?WC_Product $product = null;

	protected bool $productResolved = false;

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax' ) );
	}

	/**
	 * Field names the JS collects from the form.
	 */
	public static function getFieldNames(): array {
		return array_map( function ( $id ) {
			return Settings::SETTINGS_PREFIX . $id;
		}, array_keys( self::FIELDS ) );
	}

	public function getFrontendStylesheetURL(): string {
		return $this->getContainer()->getFileManager()->locateCSSAsset( 'frontend/main.css' );
	}

	/**
	 * Stylesheets for the preview document: the active theme's style.css (parent first when a child
	 * theme is active), then the plugin's frontend stylesheet.
	 */
	/**
	 * The theme's global styles (theme.json: colours, fonts, element styles) and its font faces, as WordPress
	 * prints them inline on the storefront. Empty for a theme without them.
	 */
	public function getInlineStyles(): string {
		$css = '';

		if ( function_exists( 'wp_print_font_faces' ) ) {
			ob_start();
			wp_print_font_faces();
			$css .= preg_replace( '#</?style[^>]*>#i', '', (string) ob_get_clean() );
		}

		if ( function_exists( 'wp_get_global_stylesheet' ) ) {
			$css .= "\n" . wp_get_global_stylesheet();
		}

		// the CSS is inlined into the preview document
		return str_replace( '</style', '<\/style', trim( $css ) );
	}

	public function getStylesheetURLs(): array {
		$urls  = array();
		$theme = wp_get_theme();

		if ( $theme->exists() ) {
			if ( $theme->parent() ) {
				$urls[] = add_query_arg( 'ver', $theme->parent()->get( 'Version' ), get_template_directory_uri() . '/style.css' );
			}
			$urls[] = add_query_arg( 'ver', $theme->get( 'Version' ), get_stylesheet_uri() );
		}

		// WooCommerce's storefront stylesheets, in the storefront's order: the block theme sheet comes first,
		// the plugin's sheet next and WooCommerce's general sheets last (a theme that ships its own WooCommerce
		// styles drops them through WooCommerce's filter)
		$woocommerce = function_exists( 'WC' ) && class_exists( 'WC_Frontend_Scripts' );
		$version     = defined( 'WC_VERSION' ) ? WC_VERSION : '';

		if ( $woocommerce && function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
			$urls[] = add_query_arg( 'ver', $version, WC()->plugin_url() . '/assets/css/woocommerce-blocktheme.css' );
		}

		// the plugin's sheet is versioned with its modification time as well: the plugin version alone lets a
		// browser keep a cached copy while the templates move on (development, git deployments)
		$stylesheet = $this->getFrontendStylesheetURL();
		$file       = dirname( __DIR__, 3 ) . '/assets/frontend/' . basename( (string) wp_parse_url( $stylesheet, PHP_URL_PATH ) );
		$urls[]     = add_query_arg( 'ver', TierPricingTablePlugin::VERSION . ( file_exists( $file ) ? '.' . filemtime( $file ) : '' ), $stylesheet );

		if ( $woocommerce ) {
			foreach ( \WC_Frontend_Scripts::get_styles() as $handle => $style ) {
				// the small screen sheet is bound to a media query the preview frame does not honour
				if ( 'woocommerce-smallscreen' === $handle || empty( $style['src'] ) ) {
					continue;
				}
				$urls[] = add_query_arg( 'ver', $style['version'] ?? $version, $style['src'] );
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Full document for the preview iframe. The wrapper mirrors a product page (body classes, div.product
	 * and .summary) so theme rules written for WooCommerce product pages apply.
	 */
	public function getDocument( string $fragment ): string {
		$links = '';
		foreach ( $this->getStylesheetURLs() as $url ) {
			$links .= '<link rel="stylesheet" href="' . esc_url( $url ) . '">';
		}

		return '<!doctype html><html><head><meta charset="utf-8">' . $links
		       . '<style>html,body{margin:0;background:#fff}body{padding:16px}.tpt-preview-note{margin:0;color:#646970}</style></head>'
		       . '<body class="woocommerce woocommerce-page single-product"><div class="woocommerce"><div class="product"><div class="summary entry-summary">'
		       . $fragment . '</div></div></div></body></html>';
	}

	/**
	 * Values currently stored, in the form the AJAX request uses (id without prefix => value).
	 */
	public function getStoredValues(): array {
		$values = array();
		foreach ( self::FIELDS as $id => $map ) {
			$values[ $id ] = $this->getContainer()->getSettings()->get( $id );
		}

		return array_merge( $values, LayoutConfigurator::getUnitValues() );
	}

	/**
	 * Render the preview fragment for a panel.
	 *
	 * @param  string  $kind    One of self::KINDS.
	 * @param  array   $values  Form values (id without prefix => value).
	 */
	public function render( string $kind, array $values, string $context = 'product-page' ): string {
		if ( ! array_key_exists( $kind, self::KINDS ) ) {
			return '';
		}

		$settings = 'shop-loop' === $context ? $this->mapCatalogSettings( $values ) : $this->mapSettings( $values );

		if ( self::KINDS[ $kind ] ) {
			$settings['display_type'] = self::KINDS[ $kind ];
		}

		if ( 'tooltip' === $settings['display_type'] ) {
			$note = 'shop-loop' === $context
				? esc_html__( 'The tooltip layout is not available on shop and category pages. Choose "Custom" and pick another layout.', 'tier-pricing-table' )
				: esc_html__( 'The tooltip layout shows the pricing table next to the product price when the customer hovers over an icon. Open a product page to see it in action.', 'tier-pricing-table' );

			return '<p class="tpt-preview-note">' . $note . '</p>';
		}

		$product = $this->getProduct();

		$settings['display_context'] = $context;
		$settings['display']         = true;

		ob_start();
		try {
			PricingTable::getInstance()->renderPricingTableHTML( $product, $product, $settings );
		} catch ( \Throwable $e ) {
			ob_end_clean();

			return '<p class="tpt-preview-note">' . esc_html__( 'The preview could not be rendered.', 'tier-pricing-table' ) . '</p>';
		}

		$html = ob_get_clean();

		// the product page skeleton shows the first discounted tier selected; highlight that tier too
		if ( 'product-page' === $context ) {
			$html = $this->highlightTier( $html, (int) array_key_first( self::SAMPLE_TIERS ) );
		}

		return '<div class="tpt__tiered-pricing" data-display-type="' . esc_attr( $settings['display_type'] ) . '">' . $html . '</div>';
	}

	/**
	 * Move the "active tier" markers, which the frontend script moves on quantity change, to the tier
	 * element of the given quantity. Only class attributes are touched, so the templates' inline CSS
	 * for the active state keeps working.
	 */
	protected function highlightTier( string $html, int $quantity ): string {
		$markers = array( 'tiered-pricing--active', 'tiered-pricing-option-checkbox--active', 'tiered-pricing-dropdown-option--selected' );

		$html = preg_replace_callback( '/\bclass="([^"]*)"/', function ( $m ) use ( $markers ) {
			$classes = array_diff( preg_split( '/\s+/', trim( $m[1] ) ), $markers );

			return 'class="' . implode( ' ', $classes ) . '"';
		}, $html );
		$html = str_replace( 'aria-selected="true"', 'aria-selected="false"', $html );

		$activated = false;
		$html      = preg_replace_callback( '/<(tr|div|li)\b[^>]*\bdata-tiered-quantity="' . $quantity . '"[^>]*>/', function ( $m ) use ( &$activated ) {
			$tag = $m[0];
			if ( $activated ) {
				return $tag;
			}
			$activated = true;
			$add       = 'tiered-pricing--active' . ( false !== strpos( $tag, 'tiered-pricing-dropdown-option' ) ? ' tiered-pricing-dropdown-option--selected' : '' );
			$tag       = preg_match( '/\bclass="/', $tag )
				? preg_replace( '/\bclass="/', 'class="' . $add . ' ', $tag, 1 )
				: preg_replace( '/^<(\w+)/', '<$1 class="' . $add . '"', $tag, 1 );

			return str_replace( 'aria-selected="false"', 'aria-selected="true"', $tag );
		}, $html );

		// the options layouts mark the active option's checkbox as well
		$active = strpos( $html, 'class="tiered-pricing--active' );
		if ( false !== $active ) {
			$needle   = 'class="tiered-pricing-option-checkbox"';
			$checkbox = strpos( $html, $needle, $active );
			if ( false !== $checkbox ) {
				$html = substr_replace( $html, 'class="tiered-pricing-option-checkbox tiered-pricing-option-checkbox--active"', $checkbox, strlen( $needle ) );
			}
		}

		return $html;
	}

	public function getProductName(): string {
		return $this->getProduct()->get_name();
	}

	public function ajax() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; every value is sanitized by its field type below
		$rawValues = isset( $_POST['values'] ) && is_array( $_POST['values'] ) ? wp_unslash( $_POST['values'] ) : array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$kinds = isset( $_POST['kinds'] ) && is_array( $_POST['kinds'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['kinds'] ) ) : array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$context = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'product-page';
		$context = in_array( $context, self::CONTEXTS, true ) ? $context : 'product-page';

		$values = array();
		foreach ( $this->getScalarFieldTypes() as $id => $type ) {
			if ( array_key_exists( $id, $rawValues ) && is_scalar( $rawValues[ $id ] ) ) {
				$raw = (string) $rawValues[ $id ];
				switch ( $type ) {
					case 'color':
					case 'optional-color':
						$values[ $id ] = sanitize_hex_color( $raw );
						break;
					case 'html':
						$values[ $id ] = wp_kses_post( $raw );
						break;
					default:
						$values[ $id ] = sanitize_text_field( $raw );
				}
			}
		}
		// only known layouts, styles and formats reach the renderer; anything else falls back to the stored value
		foreach ( LayoutConfigurator::getChoiceLists() as $id => $allowed ) {
			if ( isset( $values[ $id ] ) && ! in_array( $values[ $id ], $allowed, true ) ) {
				unset( $values[ $id ] );
			}
		}

		$unitIds = array_merge( array_keys( LayoutConfigurator::UNIT_OPTIONS ), array_values( LayoutConfigurator::CATALOG_UNIT_OPTIONS ) );
		foreach ( $unitIds as $id ) {
			if ( isset( $rawValues[ $id ] ) && is_array( $rawValues[ $id ] ) ) {
				$values[ $id ] = array(
					'singular' => sanitize_text_field( (string) ( $rawValues[ $id ]['singular'] ?? '' ) ),
					'plural'   => sanitize_text_field( (string) ( $rawValues[ $id ]['plural'] ?? '' ) ),
				);
			}
		}

		$documents = array();
		$fragments = array();
		foreach ( $kinds as $kind ) {
			if ( array_key_exists( $kind, self::KINDS ) ) {
				$fragments[ $kind ] = $this->render( $kind, $values, $context );
				$documents[ $kind ] = $this->getDocument( $fragments[ $kind ] );
			}
		}

		wp_send_json_success( array(
			'documents'   => $documents,
			'fragments'   => $fragments,
			'extras'      => array(
				'catalogPrice' => $this->renderCatalogPrice( $values ),
				'summary'      => $this->renderSummary( $values ),
			),
			'stylesheets' => $this->getStylesheetURLs(),
			'product'     => $this->getProductName(),
		) );
	}

	/**
	 * Scalar fields the request may carry (option id => sanitiser type): the product page fields and their
	 * catalog counterparts, plus the catalog's "layout source" choice.
	 */
	protected function getScalarFieldTypes(): array {
		$types = array();
		foreach ( self::FIELDS as $id => $map ) {
			$types[ $id ] = $map[1];
		}
		foreach ( LayoutConfigurator::CATALOG_OPTIONS as $key => $id ) {
			if ( isset( self::FIELDS[ $key ] ) ) {
				$types[ $id ] = self::FIELDS[ $key ][1];
			}
		}
		$types[ LayoutConfigurator::CATALOG_OPTIONS['layout_settings'] ] = 'text';
		// price line options that decide which tier the product page preview highlights
		$types['product_page_price_format']    = 'text';
		$types['update_price_on_product_page'] = 'text';
		// pricing summary block and shop price format (rendered as extras)
		foreach ( array_keys( LayoutConfigurator::SUMMARY_OPTIONS ) as $id ) {
			$types[ $id ] = 'text';
		}
		foreach ( array_keys( LayoutConfigurator::CATALOG_PRICE_OPTIONS ) as $id ) {
			$types[ $id ] = 'tiered_price_at_catalog_custom_template' === $id ? 'html' : 'text';
		}

		return $types;
	}

	/**
	 * Run a callback while some options read as the given (unsaved) form values, so code that reads
	 * the settings directly renders the preview state.
	 */
	protected function withOptions( array $overrides, callable $callback ) {
		$filters = array();
		foreach ( $overrides as $id => $value ) {
			$hook             = 'pre_option_' . Settings::SETTINGS_PREFIX . $id;
			$filters[ $hook ] = function () use ( $value ) {
				return $value;
			};
			add_filter( $hook, $filters[ $hook ] );
		}
		try {
			return $callback();
		} finally {
			foreach ( $filters as $hook => $filter ) {
				remove_filter( $hook, $filter );
			}
		}
	}

	protected function plainText( $html ): string {
		$html = preg_replace( '#<span class="screen-reader-text">.*?</span>#s', '', (string) $html );

		return trim( html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' ) );
	}

	/**
	 * The preview product's price as the shop page formats it with the form's catalog price options:
	 * the lowest tier price with its prefix, the range, or the custom template. Computed from the sample
	 * amounts (the real formatter is premium code), so the free version previews it too. HTML.
	 */
	public function renderCatalogPrice( array $values ): string {
		$get = function ( $id, $default ) use ( $values ) {
			return array_key_exists( $id, $values ) ? $values[ $id ] : $this->getContainer()->getSettings()->get( $id, $default );
		};

		$lowest  = round( self::PRICE * ( 1 - max( self::SAMPLE_TIERS ) ), 2 );
		$lowest  = esc_html( $this->plainText( wc_price( $lowest ) ) );
		$regular = esc_html( $this->plainText( wc_price( self::PRICE ) ) );
		$range   = $lowest . ' - ' . $regular;

		switch ( (string) $get( 'tiered_price_at_catalog_type', 'lowest' ) ) {
			case 'range':
				return $range;
			case 'custom':
				$template = (string) $get( 'tiered_price_at_catalog_custom_template', __( 'From {tp_lowest_price} instead of {tp_original_price}', 'tier-pricing-table' ) );

				return strtr( wp_kses_post( $template ), array(
					'{tp_lowest_price}'   => $lowest,
					'{tp_prices_range}'   => $range,
					'{tp_original_price}' => $regular,
				) );
			default:
				return trim( esc_html( (string) $get( 'lowest_prefix', __( 'From', 'tier-pricing-table' ) ) ) . ' ' . $lowest );
		}
	}

	/**
	 * The pricing summary block for the preview product with the form's options, its figures filled in
	 * for the sample quantity (the frontend script fills them on the real page). Rendered whatever the
	 * licence, so the free version previews the premium block.
	 */
	public function renderSummary( array $values ): string {
		if ( ! LayoutConfigurator::isSummaryAvailable() ) {
			return '';
		}

		$get = function ( $id, $default ) use ( $values ) {
			return array_key_exists( $id, $values ) ? $values[ $id ] : $this->getContainer()->getSettings()->get( $id, $default );
		};

		$type = (string) $get( 'summary_type', 'table' );
		$type = in_array( $type, array( 'detailed', 'table', 'inline' ), true ) ? $type : 'table';

		ob_start();
		try {
			$this->getContainer()->getFileManager()->includeTemplate( 'frontend/summary-' . $type . '.php', array(
				'productId'     => $this->getProduct()->get_id(),
				'needHide'      => false,
				'totalLabel'    => (string) $get( 'summary_total_label', __( 'Total:', 'tier-pricing-table' ) ),
				'eachLabel'     => (string) $get( 'summary_each_label', __( 'Each: ', 'tier-pricing-table' ) ),
				'title'         => (string) $get( 'summary_title', '' ),
				'showNonTiered' => 'yes' === $get( 'display_summary_non_tiered', 'no' ) ? 'yes' : 'no',
			) );
		} catch ( \Throwable $e ) {
			ob_end_clean();

			return '';
		}
		$html = ob_get_clean();

		/** @var LayoutConfigurator $configurator */
		$configurator = $this->getContainer()->get( 'settings.layout_configurator' );
		$samples      = $configurator->getSamples();
		$figures      = array(
			'product-qty'       => $samples['quantity'],
			'product-name'      => $samples['productName'],
			'product-old-price' => $samples['price'],
			'product-price'     => $samples['tierPrice'],
			'total-with-tax'    => $samples['tierTotal'],
			'total'             => $samples['tierTotal'],
		);
		foreach ( $figures as $key => $text ) {
			$html = preg_replace_callback(
				'/(data-tier-pricing-table-summary-' . preg_quote( $key, '/' ) . '(?:="[^"]*")?(?:\s[^>]*)?>)\s*(<\/(?:span|div)>)/',
				function ( $m ) use ( $text ) {
					return $m[1] . esc_html( (string) $text ) . $m[2]; // a callback: prices contain "$"
				},
				$html
			);
		}

		return str_replace( ' tier-pricing-summary-table--hidden', '', $html );
	}

	/**
	 * Settings for the shop & category preview: the product page settings when the catalog follows the
	 * product page, otherwise the catalog's own values translated to the product page keys.
	 */
	protected function mapCatalogSettings( array $values ): array {
		$sourceKey = LayoutConfigurator::CATALOG_OPTIONS['layout_settings'];
		$custom    = isset( $values[ $sourceKey ] ) ? 'custom' === $values[ $sourceKey ] : \TierPricingTable\Addons\ProductCatalogLoop\Settings\ProductCatalogLoopSettingsSection::isCustomLayoutSettings();

		if ( ! $custom ) {
			// catalog-only options that apply whatever the layout source
			$translated = $values;
			foreach ( array( 'tiers_limit' ) as $key ) {
				$id = LayoutConfigurator::CATALOG_OPTIONS[ $key ];
				if ( array_key_exists( $id, $values ) ) {
					$translated[ $key ] = $values[ $id ];
				}
			}

			return $this->mapSettings( $translated );
		}

		$translated = array();
		foreach ( array_merge( LayoutConfigurator::CATALOG_OPTIONS, LayoutConfigurator::CATALOG_UNIT_OPTIONS ) as $key => $id ) {
			if ( array_key_exists( $id, $values ) ) {
				$translated[ $key ] = $values[ $id ];
			}
		}
		// a style withheld from shop pages renders as the default there
		foreach ( array( 'table' => 'pricing_table_style', 'blocks' => 'pricing_blocks_style', 'options' => 'pricing_options_style', 'dropdown' => 'pricing_dropdown_style', 'plain-text' => 'pricing_plain_text_style' ) as $layout => $key ) {
			if ( isset( $translated[ $key ] ) ) {
				$translated[ $key ] = LayoutConfigurator::catalogStyle( $layout, (string) $translated[ $key ] );
			}
		}

		return $this->mapSettings( $translated );
	}

	/**
	 * Map form values onto the renderer's settings keys. Missing values fall back to the stored ones
	 * through the renderer's defaults.
	 */
	protected function mapSettings( array $values ): array {
		$settings = array();

		foreach ( self::FIELDS as $id => $map ) {
			if ( ! array_key_exists( $id, $values ) || null === $values[ $id ] ) {
				continue;
			}
			list( $key, $type ) = $map;
			$value = $values[ $id ];

			switch ( $type ) {
				case 'bool':
					$settings[ $key ] = 'yes' === $value || true === $value;
					break;
				case 'color':
					$settings[ $key ] = $value ? $value : '#3858e9';
					break;
				case 'optional-color':
					$settings[ $key ] = (string) $value;
					break;
				default:
					$settings[ $key ] = (string) $value;
			}
		}

		if ( empty( $settings['display_type'] ) ) {
			$settings['display_type'] = $this->getContainer()->getSettings()->get( 'display_type', 'table' );
		}

		// quantity unit of the layout being rendered (the renderer derives it from the stored layout)
		foreach ( LayoutConfigurator::UNIT_OPTIONS as $id => $layouts ) {
			if ( in_array( $settings['display_type'], $layouts, true ) && isset( $values[ $id ] ) && is_array( $values[ $id ] ) ) {
				$settings['quantity_measurement_singular'] = (string) ( $values[ $id ]['singular'] ?? '' );
				$settings['quantity_measurement_plural']   = (string) ( $values[ $id ]['plural'] ?? '' );
			}
		}

		return $settings;
	}

	/**
	 * Sample tiers as quantity => discount share of the preview price.
	 */
	const SAMPLE_TIERS = array(
		10 => 0.10,
		25 => 0.20,
		50 => 0.30,
	);

	/**
	 * The preview product: an in-memory product with a fixed price and the sample tiers, so the preview
	 * never depends on the store's catalogue. WooCommerce's factory is taught to return it for its id,
	 * because the renderer loads products by id along the way.
	 */
	public function getProduct(): WC_Product {
		if ( $this->productResolved ) {
			return $this->product;
		}
		$this->productResolved = true;

		$productId = PreviewProduct::ID;

		add_filter( 'woocommerce_product_type_query', function ( $type, $id ) use ( $productId ) {
			return (int) $id === $productId ? 'simple' : $type;
		}, 10, 2 );

		add_filter( 'woocommerce_product_class', function ( $classname, $type, $postType, $id ) use ( $productId ) {
			return (int) $id === $productId ? PreviewProduct::class : $classname;
		}, 10, 4 );

		$sampleRules = array();
		foreach ( self::SAMPLE_TIERS as $quantity => $discount ) {
			$sampleRules[ $quantity ] = round( self::PRICE * ( 1 - $discount ), 2 );
		}

		add_filter( 'tiered_pricing_table/price/pricing_rule', function ( $pricingRule, $id ) use ( $productId, $sampleRules ) {
			if ( (int) $id === $productId && $pricingRule instanceof PricingRule ) {
				$pricingRule->setType( 'fixed' );
				$pricingRule->setRules( $sampleRules );
				$pricingRule->setMinimum( null );
			}

			return $pricingRule;
		}, 999, 2 );

		// Forget any rule cached for this id earlier in the request so the sample tiers apply.
		try {
			$cache = new \ReflectionProperty( PriceManager::class, 'pricingRules' );
			$cache->setAccessible( true );
			$rules = (array) $cache->getValue();
			unset( $rules[ $productId ] );
			$cache->setValue( null, $rules );
		} catch ( \ReflectionException $e ) {
			// Cache layout changed: the sample tiers still apply on first computation.
		}

		$this->product = new PreviewProduct();

		return $this->product;
	}
}
