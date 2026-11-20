<?php namespace TierPricingTable\Integrations\Plugins\Feeds;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilitySettings;
use TierPricingTable\Services\Feeds\FeedEligibility;
use TierPricingTable\Services\Feeds\MinimumQuantityDisplay;
use TierPricingTable\Services\Feeds\MinimumQuantityPrice;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\GeneralSection\Subsections\ProductPagePriceSubsection;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * The "Product feeds" settings section, shown while a supported feed plugin is active and its integration is on:
 * what feeds advertise for products with a minimum quantity (only feeds that can send a total), which products are
 * left out, and a list of store settings that get feed prices rejected.
 */
class FeedsSettings extends SectionAbstract {

	/**
	 * The integrations whose feed plugin is active, by slug.
	 *
	 * @var FeedIntegrationAbstract[]
	 */
	protected static $integrations = array();

	public static function register( FeedIntegrationAbstract $integration ): void {
		$first = empty( self::$integrations );

		self::$integrations[ $integration->getSlug() ] = $integration;

		// the product page and its structured data show the total feeds advertise
		if ( $integration->supportsMinimumQuantityTotal() ) {
			MinimumQuantityDisplay::init();
		}

		if ( ! $first ) {
			return;
		}

		add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
			$sections[] = new self();

			return $sections;
		} );
	}

	/**
	 * The active feed integrations that can or cannot advertise the total of a minimum quantity.
	 *
	 * @return FeedIntegrationAbstract[]
	 */
	protected static function getIntegrations( bool $supportingTotals ): array {
		return array_values( array_filter( self::$integrations, function ( FeedIntegrationAbstract $integration ) use ( $supportingTotals ) {
			return $integration->supportsMinimumQuantityTotal() === $supportingTotals;
		} ) );
	}

	/**
	 * Whether the minimum quantity setting applies to any active feed.
	 */
	protected static function isMinimumQuantityTotalAvailable(): bool {
		return MinimumQuantityPrice::isAvailable() && self::getIntegrations( true );
	}

	/**
	 * "A, B and C" for integrations.
	 *
	 * @param  FeedIntegrationAbstract[]  $integrations
	 */
	protected static function listNames( array $integrations, bool $bold = false ): string {
		return wp_sprintf( '%l', array_map( function ( FeedIntegrationAbstract $integration ) use ( $bold ) {
			return $bold ? '<strong>' . esc_html( $integration->getTitle() ) . '</strong>' : $integration->getTitle();
		}, $integrations ) );
	}

	public function getName(): string {
		return __( 'Product feeds', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'product-feeds';
	}

	public function getSettings(): array {
		$settings = array(
			array(
				'title' => __( 'Product feeds', 'tier-pricing-table' ),
				'id'    => Settings::SETTINGS_PREFIX . '_subsection_' . $this->getSlug(),
				'desc'  => $this->getIntro(),
				'type'  => 'title',
			),
		);

		if ( MinimumQuantityPrice::isAvailable() ) {
			// the total only where a feed can send it; leaving products out works for every feed
			$withTotals = (bool) self::getIntegrations( true );
			$unitOnly   = self::getIntegrations( false );

			$options      = array( MinimumQuantityPrice::MODE_UNIT => __( 'Price of one item', 'tier-pricing-table' ) );
			$descriptions = array(
				/* translators: %s: example price */
				MinimumQuantityPrice::MODE_UNIT    => sprintf( __( 'e.g. %s for a cap sold in 50s', 'tier-pricing-table' ), wp_strip_all_tags( wc_price( 2 ) ) ),
				/* translators: %s: example price */
				MinimumQuantityPrice::MODE_TOTAL   => sprintf( __( 'e.g. %s for 50 caps, Google\'s rule', 'tier-pricing-table' ), wp_strip_all_tags( wc_price( 100 ) ) ),
				MinimumQuantityPrice::MODE_EXCLUDE => __( 'Only products sold one at a time', 'tier-pricing-table' ),
			);

			if ( $withTotals ) {
				$options[ MinimumQuantityPrice::MODE_TOTAL ] = __( 'Total of the minimum quantity', 'tier-pricing-table' );
			} else {
				unset( $descriptions[ MinimumQuantityPrice::MODE_TOTAL ] );
			}

			$options[ MinimumQuantityPrice::MODE_EXCLUDE ] = __( 'Leave out of feeds', 'tier-pricing-table' );

			$description = $withTotals
				? __( 'Google asks for the total price of the smallest quantity a shopper can buy: the minimum quantity, rounded up to the "Group of" step. With the total, the product page shows a "Minimum order" line with the same total, and its structured data carries it too.', 'tier-pricing-table' )
				: __( 'The smallest quantity a shopper can buy is the minimum quantity, rounded up to the "Group of" step.', 'tier-pricing-table' );

			if ( $withTotals && $unitOnly ) {
				$description .= ' ' . sprintf(
					/* translators: %s: names of feed plugins */
					__( 'With the total, %s still send the price of one item.', 'tier-pricing-table' ),
					self::listNames( $unitOnly )
				);
			}

			$settings[] = array(
				'title'                => __( 'Products with a minimum quantity', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . MinimumQuantityPrice::OPTION,
				'type'                 => TPTDisplayType::FIELD_TYPE,
				'default'              => MinimumQuantityPrice::MODE_UNIT,
				'options'              => $options,
				'descriptions'         => $descriptions,
				'extended_description' => $description,
			);
		}


		$excludeDescription = __( 'Products a logged-out visitor cannot see or buy are left out of product feeds and catalogs: hidden prices, quote-only products, products hidden from visitors and a closed store. Shoppers who click them cannot buy, and Google rejects them.', 'tier-pricing-table' );

		// Meta only stops updating a product it already has
		if ( isset( self::$integrations['meta-for-woocommerce'] ) ) {
			$excludeDescription .= ' ' . __( 'Meta keeps products it already has: remove them in Meta Commerce Manager.', 'tier-pricing-table' );
		}

		$settings[] = array(
			'title'                => __( 'Leave out products visitors cannot buy', 'tier-pricing-table' ),
			'id'                   => Settings::SETTINGS_PREFIX . FeedEligibility::OPTION,
			'type'                 => TPTSwitchOption::FIELD_TYPE,
			'default'              => 'no',
			'extended_description' => $excludeDescription,
		);

		$settings[] = array(
			'type' => 'sectionend',
		);

		$settings[] = array(
			'title' => __( 'Checks', 'tier-pricing-table' ),
			'id'    => Settings::SETTINGS_PREFIX . '_subsection_' . $this->getSlug() . '_checks',
			'desc'  => $this->renderChecks(),
			'type'  => 'title',
		);

		$settings[] = array(
			'type' => 'sectionend',
		);

		return $settings;
	}

	public function isIntegration(): bool {
		return true;
	}

	protected function getIntro(): string {
		return sprintf(
			/* translators: %s: the names of the active feed plugins */
			__( 'Feeds and catalogs from %s get the price a logged-out visitor pays, whoever builds them: role prices, customer prices and rules for some roles stay out of them.', 'tier-pricing-table' ),
			self::listNames( array_values( self::$integrations ), true )
		);
	}

	/**
	 * Store settings that make Google reject prices, each with what to do about it.
	 *
	 * @return array<int, array{type: string, text: string}>
	 */
	public function getChecks(): array {
		$checks   = array();
		$settings = admin_url( 'admin.php?page=wc-settings&tab=' . Settings::SETTINGS_PAGE );
		$link     = function ( string $url, string $text ): string {
			return '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		};

		if ( self::isMinimumQuantityTotalAvailable() && MinimumQuantityPrice::MODE_UNIT === MinimumQuantityPrice::getMode() && $this->hasProductsWithMinimumQuantity() ) {
			$checks[] = array(
				'type' => 'warning',
				'text' => __( 'Some products have a minimum quantity or a "Group of" step, and feeds send the price of one item, which shoppers cannot buy. Choose the total of the minimum quantity above, or leave these products out.', 'tier-pricing-table' ),
			);
		}

		if ( 'same_as_catalog' === ProductPagePriceSubsection::getFormatPriceType() ) {
			$checks[] = array(
				'type' => 'warning',
				'text' => sprintf(
					/* translators: %s: link to the setting */
					__( 'The product page shows the lowest tier price ("From …") as its main price, which differs from the price in feeds. Set %s to "Actual price for selected quantity".', 'tier-pricing-table' ),
					$link( $settings . '&section=general', __( 'Price display format', 'tier-pricing-table' ) )
				),
			);
		}

		$enhanced = array();
		foreach ( array( 'yoast' => 'Yoast SEO', 'rank_math' => 'Rank Math', 'seopress' => 'SEOPress' ) as $prefix => $name ) {
			if ( 'yes' === get_option( Settings::SETTINGS_PREFIX . $prefix . '_enhance_schema', 'no' ) ) {
				$enhanced[] = $name;
			}
		}

		if ( $enhanced ) {
			$checks[] = array(
				'type' => 'warning',
				'text' => sprintf(
					/* translators: %s: names of SEO plugins */
					__( '"Enhance product schema" is on for %s: it gives Google the lowest tier price and a price range, which Google Shopping does not accept. Turn it off in the settings of that integration.', 'tier-pricing-table' ),
					esc_html( wp_sprintf( '%l', $enhanced ) )
				),
			);
		}

		if ( ! FeedEligibility::isExclusionEnabled() && AbstractAddon::isAddonEnabled( 'non-logged-in-users' ) ) {
			$guestLimits = VisibilitySettings::isClosedStore()
				|| 'yes' === get_option( Settings::SETTINGS_PREFIX . 'non_logged_in_users_hide_prices', 'no' )
				|| 'yes' === get_option( Settings::SETTINGS_PREFIX . 'non_logged_in_users_prevent_purchase', 'no' );

			if ( $guestLimits ) {
				$checks[] = array(
					'type' => 'warning',
					'text' => __( 'Logged-out visitors cannot see prices or buy (hidden prices, no purchasing or a closed store), so Google rejects every product in the feed and catalog shoppers cannot buy. Turn on "Leave out products visitors cannot buy".', 'tier-pricing-table' ),
				);
			}
		}

		foreach ( $this->getUnsupportedFeedPlugins() as $name => $text ) {
			$checks[] = array(
				'type' => 'info',
				'text' => '<strong>' . esc_html( $name ) . '</strong>: ' . esc_html( $text ),
			);
		}

		if ( ! $checks ) {
			$checks[] = array(
				'type' => 'ok',
				'text' => __( 'Nothing found that gets your feed prices rejected.', 'tier-pricing-table' ),
			);
		}

		return apply_filters( 'tiered_pricing_table/feeds/checks', $checks );
	}

	protected function renderChecks(): string {
		$icons = array(
			'warning' => '<span class="dashicons dashicons-warning" style="color:#b26200"></span>',
			'info'    => '<span class="dashicons dashicons-info-outline" style="color:#2271b1"></span>',
			'ok'      => '<span class="dashicons dashicons-yes-alt" style="color:#00a32a"></span>',
		);

		$items = '';
		foreach ( $this->getChecks() as $check ) {
			$items .= '<li style="display:flex;gap:8px;align-items:flex-start;margin:0 0 10px">' . ( $icons[ $check['type'] ] ?? '' ) . '<span>' . $check['text'] . '</span></li>';
		}

		return __( 'Store settings that can get feed prices rejected.', 'tier-pricing-table' ) . '<ul style="margin:12px 0 0;max-width:760px">' . $items . '</ul>';
	}

	/**
	 * Whether any product (or a role's price on it) has a minimum quantity or a "Group of" step above one.
	 */
	protected function hasProductsWithMinimumQuantity(): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- one cheap lookup, only on this settings screen
		return (bool) $wpdb->get_var(
			"SELECT 1 FROM {$wpdb->postmeta} WHERE ( meta_key LIKE '%tiered_price_minimum_qty' OR meta_key LIKE '%tiered_pricing_group_of_quantity' ) AND CAST( meta_value AS UNSIGNED ) > 1 LIMIT 1"
		);
	}

	/**
	 * Active feed plugins this plugin cannot price, by name.
	 *
	 * @return array<string, string>
	 */
	protected function getUnsupportedFeedPlugins(): array {
		$active = (array) get_option( 'active_plugins', array() );
		$is     = function ( string $folder ) use ( $active ): bool {
			foreach ( $active as $plugin ) {
				if ( 0 === strpos( (string) $plugin, $folder . '/' ) ) {
					return true;
				}
			}

			return false;
		};

		$found = array();

		if ( $is( 'wp-product-feed-manager' ) ) {
			$found['WP Product Feed Manager'] = __( 'reads WooCommerce\'s own prices by default, so role prices and pricing rules do not reach its feeds yet.', 'tier-pricing-table' );
		}

		if ( $is( 'enhanced-e-commerce-for-woocommerce-store' ) ) {
			$found['Conversios'] = __( 'reads prices straight from the database, so role prices and pricing rules cannot reach its product sync.', 'tier-pricing-table' );
		}

		return $found;
	}
}
