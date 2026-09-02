<?php namespace TierPricingTable\Addons\PluginsRecommendations;

use TierPricingTable\Admin\Tips\Tip;

/**
 * A simple, dismissible tip for U2Code Product Addons in the product's Tiered Pricing tab
 * (Additional Options): one line of copy and two buttons — one-click install of the free
 * plugin and a link to the plugin site. Nothing renders once the plugin is active or the
 * tip was hidden (store-wide, via the standard Tips mechanism).
 */
class U2CodeProductAddonsPlugin extends Tip {

	const WPORG_SLUG = 'u2code-product-addons-for-woocommerce';

	public function getSlug(): string {
		return 'u2code_product_addons_tip';
	}

	public static function isAddonsPluginActive(): bool {
		// Free build (SimpleProductAddons) or premium build (U2Code\ProductAddons)
		return class_exists( '\\SimpleProductAddons\\Plugin' ) || class_exists( '\\U2Code\\ProductAddons\\Plugin' );
	}

	public function __construct() {
		parent::__construct();

		// Resolve this tip for the standard dismiss AJAX without touching TipsManager's list.
		add_filter( 'tiered_pricing_table/admin/tips/get_tip_by_slug', function ( $tip, $slug ) {
			return $slug === $this->getSlug() ? $this : $tip;
		}, 10, 2 );

		add_action( 'init', function () {

			// The plugin is already there (free or premium build) — nothing to recommend.
			if ( self::isAddonsPluginActive() ) {
				return;
			}

			add_action( 'tiered_pricing_table/admin/advance_product_options', array( $this, 'render' ) );
		} );
	}

	public function render() {

		if ( $this->isSeen() ) {
			return;
		}

		// Belt and braces: never show the tip while the plugin is active, regardless of hook timing.
		if ( self::isAddonsPluginActive() ) {
			return;
		}

		if ( current_user_can( 'install_plugins' ) ) {
			$installURL   = wp_nonce_url(
				self_admin_url( 'update.php?action=install-plugin&plugin=' . self::WPORG_SLUG ),
				'install-plugin_' . self::WPORG_SLUG
			);
			$installLabel = __( 'Install now — free', 'tier-pricing-table' );
		} else {
			$installURL   = 'https://wordpress.org/plugins/' . self::WPORG_SLUG . '/';
			$installLabel = __( 'Free on WordPress.org', 'tier-pricing-table' );
		}
		?>
		<div class="tiered-pricing-tip"
			 style="margin: 12px; padding: 10px; background: #fafafa; border: 1px solid #eeeeee; display: flex; gap: 10px; justify-content: space-between">
			<div style="display:flex; gap: 10px;">
				<div style="color: #2272b1; margin: 0 5px;">
					<span class="dashicons dashicons-plus-alt"></span>
				</div>
				<div>
					<strong>
						<?php esc_html_e( 'Tip', 'tier-pricing-table' ); ?>:
					</strong>

					<?php esc_html_e( 'Want to charge for product options — engraving, gift wrap, services?', 'tier-pricing-table' ); ?>

					<div style="margin-top: 5px; color: #50575e;">
						<?php esc_html_e( 'U2Code Product Addons pairs with tiered pricing: option prices follow the tier price and appear in the live totals. By the makers of this plugin.', 'tier-pricing-table' ); ?>
					</div>

					<div style="margin-top: 10px; display: flex; gap: 8px;">
						<a class="button button-primary" href="<?php echo esc_url( $installURL ); ?>">
							<?php echo esc_html( $installLabel ); ?>
						</a>
						<a class="button" target="_blank" rel="noopener"
						   href="https://product-addons.com/?utm_source=tiered-pricing-table&utm_medium=plugin&utm_campaign=product-tab-tip">
							<?php esc_html_e( 'Learn more', 'tier-pricing-table' ); ?>
						</a>
					</div>
				</div>
			</div>

			<div style="white-space: nowrap;">
				<a role="button" href="<?php echo esc_attr( $this->getMarkAsSeenURL() ); ?>"
				   class="tiered-pricing-tip-close-button">
					&times; <?php esc_html_e( 'Hide this tip', 'tier-pricing-table' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
