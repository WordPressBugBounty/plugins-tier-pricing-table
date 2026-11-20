<?php namespace TierPricingTable\Services\Feeds;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\ProductVisibilityService;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilitySettings;
use TierPricingTable\Addons\RequestAQuote\Frontend\Adapters\AddToCartAdapter;
use TierPricingTable\Core\PricingContext;
use TierPricingTable\Settings\Settings;
use WC_Product;

/**
 * Products a logged-out visitor cannot see or buy, which Google disapproves when they are in a product feed:
 * Google checks every product as a logged-out visitor, and the price must be on the page and payable by anyone.
 *
 * Leaving them out of feeds is a setting (off by default): it removes listings from the store's Merchant Center.
 * Products that cannot be bought one at a time can be left out too (MinimumQuantityPrice).
 *
 * @package TierPricingTable\Services\Feeds
 */
class FeedEligibility {

	const OPTION = 'feeds_exclude_unbuyable';

	public static function isExclusionEnabled(): bool {
		return 'yes' === get_option( Settings::SETTINGS_PREFIX . self::OPTION, 'no' );
	}

	/**
	 * Whether a product is to be left out of feeds: a visitor cannot buy it (while that setting is on), or it cannot
	 * be bought one at a time and the store leaves such products out.
	 */
	public static function shouldExclude( WC_Product $product ): bool {
		if ( self::isExclusionEnabled() && null !== self::getExclusionReason( $product ) ) {
			return true;
		}

		if ( MinimumQuantityPrice::MODE_EXCLUDE !== MinimumQuantityPrice::getMode() ) {
			return false;
		}

		return PricingContext::runAsGuest( function () use ( $product ) {
			return MinimumQuantityPrice::getQuantity( $product ) > 1;
		} );
	}

	/**
	 * Why a logged-out visitor cannot see or buy the product, or null when they can.
	 *
	 * @return string|null closed_store, hidden_price, guests_cannot_buy, hidden_from_guests or quote_only
	 */
	public static function getExclusionReason( WC_Product $product ): ?string {
		$reason = PricingContext::runAsGuest( function () use ( $product ) {
			return self::findReason( $product );
		} );

		return apply_filters( 'tiered_pricing_table/feeds/exclusion_reason', $reason, $product );
	}

	protected static function findReason( WC_Product $product ): ?string {
		$productId = $product->get_id();
		// visibility rules and quote forms are set on the product a variation belongs to
		$mainId = $product->get_parent_id() ? $product->get_parent_id() : $productId;

		if ( AbstractAddon::isAddonEnabled( 'non-logged-in-users' ) ) {
			if ( VisibilitySettings::isClosedStore() ) {
				return 'closed_store';
			}

			if ( 'yes' === get_option( Settings::SETTINGS_PREFIX . 'non_logged_in_users_hide_prices', 'no' ) ) {
				return 'hidden_price';
			}

			if ( 'yes' === get_option( Settings::SETTINGS_PREFIX . 'non_logged_in_users_prevent_purchase', 'no' ) ) {
				return 'guests_cannot_buy';
			}

			if ( VisibilitySettings::isEnabled() && ! ProductVisibilityService::isProductVisibleFor( $mainId, array(), true ) ) {
				return 'hidden_from_guests';
			}
		}

		if ( AbstractAddon::isAddonEnabled( 'request-a-quote' ) && ( AddToCartAdapter::isQuoteOnly( $productId ) || ( $mainId !== $productId && AddToCartAdapter::isQuoteOnly( $mainId ) ) ) ) {
			return 'quote_only';
		}

		return null;
	}
}
