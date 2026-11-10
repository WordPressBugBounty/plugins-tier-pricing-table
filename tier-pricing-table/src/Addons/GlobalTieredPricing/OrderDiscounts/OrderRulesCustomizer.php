<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\TierPricingTablePlugin;

/**
 * The Order rules settings customizer: one settings field that carries every store-wide option of the order
 * rules (the discount behaviour and the next tier message, the minimum order messages, the free shipping
 * bar's places and look) with a live preview of the cart, the checkout, the mini cart and the product page.
 * Rendered by a React app built with the layout configurator's package (js-source/order-rules-customizer.js
 * there, `npm run build` in src/Addons/LayoutConfigurator).
 *
 * Every option keeps its own id and is submitted through hidden inputs inside the field, so WooCommerce's
 * settings save path is unchanged; the values are sanitised per option kind here.
 */
class OrderRulesCustomizer {

	use ServiceContainerTrait;

	const FIELD_TYPE = 'tpt_order_rules_customizer';

	const SCRIPT_HANDLE = 'tiered-pricing/settings/order-rules-customizer';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		// the definitions carry translated defaults, so they are resolved when a save happens, not this early
		foreach ( OrderDiscountSettings::getOptionIds() as $key => $id ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_' . $id, function ( $value, $field, $rawValue ) use ( $key ) {
				return self::sanitize( OrderDiscountSettings::getOptionDefinitions()[ $key ], $rawValue );
			}, 10, 3 );
		}
	}

	/**
	 * A posted value as the option stores it: a known choice, yes/no, safe HTML, a list of known keys, a hex
	 * colour or a number within its range; the default when the value is unusable.
	 *
	 * @param  array  $option  A definition from OrderDiscountSettings::getOptionDefinitions().
	 * @param  mixed  $raw     The posted value (already unslashed by WooCommerce).
	 */
	public static function sanitize( array $option, $raw ) {
		$value = is_scalar( $raw ) ? (string) $raw : '';

		switch ( $option['kind'] ) {
			case 'choice':
				return in_array( $value, $option['choices'], true ) ? $value : $option['default'];
			case 'switch':
				return 'yes' === $value ? 'yes' : 'no';
			case 'template':
				return wp_kses_post( $value );
			case 'list':
				return implode( ',', array_values( array_intersect( TPTCheckboxListOption::toArray( is_array( $raw ) ? $raw : $value ), $option['choices'] ) ) );
			case 'color':
				$color = sanitize_hex_color( trim( $value ) );

				return $color ? $color : $option['default'];
			case 'number':
				$number = absint( $value );

				return (string) ( $number ? min( max( $number, (int) $option['min'] ), (int) $option['max'] ) : $option['default'] );
		}

		return $value;
	}

	/**
	 * The stored values by key, as strings; a missing option is its default (an emptied list stays empty).
	 */
	public static function getValues(): array {
		$values = array();

		foreach ( OrderDiscountSettings::getOptionDefinitions() as $key => $option ) {
			$stored = get_option( $option['id'], null );

			if ( is_null( $stored ) || false === $stored ) {
				$values[ $key ] = (string) $option['default'];
			} else {
				$values[ $key ] = is_array( $stored ) ? implode( ',', $stored ) : (string) $stored;
			}
		}

		return $values;
	}

	/**
	 * Configuration handed to the app.
	 */
	public function getConfig(): array {
		$definitions = OrderDiscountSettings::getOptionDefinitions();
		$defaults    = array();

		foreach ( $definitions as $key => $option ) {
			$defaults[ $key ] = (string) $option['default'];
		}

		return array(
			'title'        => __( 'Cart rules settings', 'tier-pricing-table' ),
			'values'       => self::getValues(),
			'defaults'     => $defaults,
			'premium'      => (bool) apply_filters( 'tiered_pricing_table/settings/is_premium', tpt_fs()->can_use_premium_code() ),
			'upgradeUrl'   => function_exists( 'tpt_fs_activation_url' ) ? tpt_fs_activation_url() : '',
			'rulesUrl'     => admin_url( 'edit.php?post_type=' . GlobalTieredPricingCPT::SLUG ),
			'placements'   => OrderDiscountSettings::getBarPlacementOptions(),
			'basis'        => array(
				OrderDiscountSettings::BASIS_DISPLAY       => __( 'Follow the cart tax display setting', 'tier-pricing-table' ),
				OrderDiscountSettings::BASIS_EXCLUDING_TAX => __( 'Excluding tax', 'tier-pricing-table' ),
				OrderDiscountSettings::BASIS_INCLUDING_TAX => __( 'Including tax', 'tier-pricing-table' ),
			),
			'placeholders' => array(
				'nudge_message'       => array( 'remaining', 'discount', 'threshold' ),
				'amount_message'      => array( 'minimum', 'subtotal' ),
				'quantity_message'    => array( 'minimum', 'count' ),
				'bar_message'         => array( 'remaining', 'threshold', 'progress' ),
				'bar_reached_message' => array( 'threshold' ),
			),
			'shortcode'    => FreeShippingBar::SHORTCODE,
			'currency'     => array(
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ),
				'position'          => get_option( 'woocommerce_currency_pos', 'left' ),
				'decimals'          => wc_get_price_decimals(),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
			),
			// the storefront stylesheet, so the bar in the preview is the storefront's bar
			'stylesheets'  => array( add_query_arg( 'ver', TierPricingTablePlugin::VERSION, $this->getContainer()->getFileManager()->locateCSSAsset( 'frontend/main.css' ) ) ),
		);
	}

	public function enqueueAssets() {
		$package   = dirname( __DIR__, 2 ) . '/LayoutConfigurator/';
		$assetFile = file_exists( $package . 'build/order-rules-customizer.asset.php' ) ? include $package . 'build/order-rules-customizer.asset.php' : array( 'dependencies' => array(), 'version' => TierPricingTablePlugin::VERSION );
		$anchor    = $package . 'LayoutConfigurator.php';

		wp_enqueue_script( self::SCRIPT_HANDLE, plugins_url( 'build/order-rules-customizer.js', $anchor ), $assetFile['dependencies'], $assetFile['version'], true );
		wp_set_script_translations( self::SCRIPT_HANDLE, 'tier-pricing-table', $this->getContainer()->getFileManager()->getPluginDirectory() . 'languages' );

		// the configurator's stylesheet (the shared cards, fields and preview frame) and the customizer's own
		foreach ( array( 'style-index.css', 'order-rules-customizer.css' ) as $index => $file ) {
			if ( file_exists( $package . 'build/' . $file ) ) {
				wp_enqueue_style( self::SCRIPT_HANDLE . '-' . $index, plugins_url( 'build/' . $file, $anchor ), array( 'wp-components' ), $assetFile['version'] );
			}
		}

		wp_enqueue_style( 'wp-components' );
		wp_add_inline_script( self::SCRIPT_HANDLE, 'window.tptOrderRulesCustomizer = ' . wp_json_encode( $this->getConfig() ) . ';', 'before' );
	}

	/**
	 * The field: the hidden inputs that carry the options and the root the app renders into.
	 *
	 * @param  mixed  $value  The field definition.
	 */
	public function render( $value ) {
		$this->enqueueAssets();

		$values      = self::getValues();
		$definitions = OrderDiscountSettings::getOptionDefinitions();
		?>
		<?php
		// WooCommerce renders fields inside <table class="form-table">, whose fixed layout keeps a spanning
		// cell inside the label column. The customizer needs the full width, so it closes the table, renders
		// as a block and reopens the table for what follows.
		?>
		</table>
		<div id="tpt-order-rules-customizer" class="tpt-order-rules-customizer" aria-label="<?php echo esc_attr( (string) ( $value['title'] ?? '' ) ); ?>">
			<?php foreach ( $definitions as $key => $option ) : ?>
				<input type="hidden"
					   name="<?php echo esc_attr( $option['id'] ); ?>"
					   id="<?php echo esc_attr( $option['id'] ); ?>"
					   value="<?php echo esc_attr( $values[ $key ] ); ?>"
					   data-tpt-orc-option="<?php echo esc_attr( $key ); ?>">
			<?php endforeach; ?>
			<div class="tpt-order-rules-customizer__app" data-tpt-orc-root>
				<p class="tpt-order-rules-customizer__loading"><?php esc_html_e( 'Loading the cart rules settings…', 'tier-pricing-table' ); ?></p>
			</div>
		</div>
		<table class="form-table">
		<?php
	}
}
