<?php namespace TierPricingTable\Addons\YouSave;

use TierPricingTable\Core\ServiceContainerTrait;
use TierPricingTable\Settings\Settings;
use WC_Product;

/**
 * Class YouSaveService
 *
 * Shows saving amount
 *
 * @package TierPricingTable\Addons\YouSave
 */
class YouSaveService {

	use ServiceContainerTrait;

	/**
	 * CatalogPriceManager constructor.
	 */
	public function __construct() {

		if ( 'price' === $this->getPosition() ) {
			add_action( 'woocommerce_get_price_html', array( $this, 'addYouSave' ), 150, 2 );
		} elseif ( 'before_add_to_cart' === $this->getPosition() ) {
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'renderBeforeAddToCart' ), 20 );
		} else {
			add_action( 'tiered_pricing_table/after_rendering_tiered_pricing', array( $this, 'renderAfterTable' ), 10, 3 );
		}

		add_shortcode( 'tiered_price_you_save', function ( $tag, $args ) {

			$args = wp_parse_args( $args, array(
					'product_id'          => null,
					'color'               => $this->getTextColor(),
					'template'            => $this->getTemplate(),
					'consider_sale_price' => $this->considerSalePrice(),
					'style'               => $this->getStyle(),
			) );

			$product = wc_get_product( $args['product_id'] );

			if ( ! $product || ! $this->isEnabledFor( $product ) ) {
				return '';
			}

			return $this->getYouSaveHTML( $args['color'], $args['template'], $args['consider_sale_price'], $product, $args['style'] );
		} );
	}

	/**
	 * Whether the badge is switched on for this product: the badge switch for products with tiers,
	 * the "products without tiered pricing" switch (with the sale discount counted) for the rest.
	 */
	public function isEnabledFor( WC_Product $product ): bool {
		$hasRules = \TierPricingTable\PricingTable::getInstance()->productHasPricingRules( $product );

		return $hasRules
			? 'yes' === $this->getContainer()->getSettings()->get( 'you_save_enabled', 'yes' )
			: 'yes' === $this->getContainer()->getSettings()->get( 'you_save_non_tiered', 'no' ) && 'yes' === $this->getContainer()->getSettings()->get( 'you_save_consider_sale_price', 'yes' );
	}

	/**
	 * Position "under the price": appended to the price line while that line follows the quantity.
	 */
	public function addYouSave( $priceHTML, WC_Product $product ) {

		if ( false === strpos( $priceHTML, 'tiered-pricing-dynamic-price-wrapper' ) ) {
			return $priceHTML;
		}

		if ( ! $this->isEnabledFor( $product ) ) {
			return $priceHTML;
		}

		$priceHTML .= '<br>' . $this->getYouSaveHTML( $this->getTextColor(), $this->getTemplate(),
						$this->considerSalePrice(), $product );

		return $priceHTML;
	}

	/**
	 * Position "above the add-to-cart button".
	 */
	public function renderBeforeAddToCart() {
		global $product;

		if ( ! is_product() || ! $product instanceof WC_Product ) {
			return;
		}

		$this->renderBlock( $product );
	}

	/**
	 * Position "after the pricing table": follows the table wherever it is placed.
	 *
	 * @param  WC_Product  $parentProduct
	 * @param  int|null  $variationID
	 * @param  array  $settings
	 */
	public function renderAfterTable( $parentProduct, $variationID, $settings ) {

		if ( ! $parentProduct instanceof WC_Product || 'product-page' !== ( $settings['display_context'] ?? 'product-page' ) || ! is_product() ) {
			return;
		}

		$this->renderBlock( $parentProduct );
	}

	protected function renderBlock( WC_Product $product ) {

		if ( ! $this->isEnabledFor( $product ) ) {
			return;
		}

		echo '<div class="tiered-pricing-you-save-wrapper tiered-pricing-you-save-wrapper--' . esc_attr( $this->getPosition() ) . '">'
		     . $this->getYouSaveHTML( $this->getTextColor(), $this->getTemplate(), $this->considerSalePrice(), $product ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		     . '</div>';
	}

	public function getYouSaveHTML( $color, $template, $considerSalePrice, $product = null, $style = null ): string {
		$template = $this->parseTemplate( $template );
		$style    = in_array( $style, self::getStyles(), true ) ? $style : $this->getStyle();
		$color    = $color ? $color : $this->getActiveTierColor();

		switch ( $style ) {
			case 'pill':
				$css = 'background-color: ' . $color . '; color: #ffffff;';
				break;
			case 'outline':
				$css = 'color: ' . $color . '; border-color: ' . $color . ';';
				break;
			default:
				$css = 'color: ' . $color . ';';
		}

		ob_start();
		?>
		<small data-consider-sale-price="<?php echo esc_attr( wc_bool_to_string( $considerSalePrice ) ); ?>"
		       data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
		       data-parent-id="<?php echo esc_attr( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ); ?>"
		       class="tiered-pricing-you-save tiered-pricing-you-save--hidden tiered-pricing-you-save--<?php echo esc_attr( $style ); ?>"
		       style="<?php echo esc_attr( $css ); ?>"><?php echo wp_kses_post( $template ); ?>
		</small>
		<?php
		return ob_get_clean();
	}

	/**
	 * Badge styles: coloured text, a filled pill, or an outlined label.
	 */
	public static function getStyles(): array {
		return array( 'text', 'pill', 'outline' );
	}

	/**
	 * Badge positions on the product page.
	 */
	public static function getPositions(): array {
		return array( 'price', 'before_add_to_cart', 'after_table' );
	}

	public function getStyle(): string {
		$style = (string) get_option( Settings::SETTINGS_PREFIX . 'you_save_style', 'text' );

		return in_array( $style, self::getStyles(), true ) ? $style : 'text';
	}

	public function getPosition(): string {
		$position = (string) get_option( Settings::SETTINGS_PREFIX . 'you_save_position', 'price' );

		return in_array( $position, self::getPositions(), true ) ? $position : 'price';
	}

	public function getTemplate() {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_template',
				'You save {tp_ys_total_price}' );
	}

	/**
	 * The badge colour; empty means "Auto": the active tier colour.
	 */
	public function getTextColor(): string {
		return (string) get_option( Settings::SETTINGS_PREFIX . 'you_save_text_color', '' );
	}

	/**
	 * The active tier colour of the pricing layout, which the badge follows unless it has its own colour.
	 */
	public function getActiveTierColor(): string {
		$color = (string) get_option( Settings::SETTINGS_PREFIX . 'selected_quantity_color', '#3858e9' );

		return '' !== $color ? $color : '#3858e9';
	}

	public function considerSalePrice(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_consider_sale_price', 'yes' ) === 'yes';
	}

	public function isEnabled(): bool {
		return get_option( Settings::SETTINGS_PREFIX . 'you_save_enabled', 'yes' ) === 'yes';
	}

	protected function parseTemplate( $template ): string {
		return strtr( $template, array(
				'{tp_ys_price}'               => '<span class="tiered-pricing-you-save__price"></span>',
				'{tp_ys_total_price}'         => '<span class="tiered-pricing-you-save__total"></span>',
				'{tp_ys_percentage_discount}' => '<span class="tiered-pricing-you-save__discount"></span>',
		) );
	}

}
