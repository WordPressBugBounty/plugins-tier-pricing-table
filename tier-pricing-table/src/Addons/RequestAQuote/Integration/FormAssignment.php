<?php namespace TierPricingTable\Addons\RequestAQuote\Integration;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\TierPricingTablePlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The form a product gets from the forms' own "Show this form on" settings, used when neither the product nor its
 * pricing rule chooses a form (or the chosen form was deleted).
 *
 * The most specific match wins: a form that names the product, then one that names one of its categories
 * (subcategories included), then one for all products. On a tie the form listed first wins.
 */
class FormAssignment {

	const MATCH_PRODUCT  = 3;
	const MATCH_CATEGORY = 2;
	const MATCH_ALL      = 1;

	/**
	 * @param  int  $productId  A product or a variation (a variation goes by its product).
	 * @param  string[]|null  $roles  The visitor's roles; null for the current visitor.
	 * @param  bool  $anyRole  Ignore the forms' customer roles (the admin screens describe what can apply).
	 *
	 * @return RequestQuoteForm|null
	 */
	public static function findForProduct( int $productId, ?array $roles = null, bool $anyRole = false ): ?RequestQuoteForm {
		$forms = array_filter( RequestQuoteForm::getAll(), function ( RequestQuoteForm $form ) {
			return RequestQuoteForm::SHOW_ON_CHOSEN !== $form->getShowOn();
		} );

		if ( empty( $forms ) ) {
			return null;
		}

		$product = $productId > 0 ? wc_get_product( $productId ) : null;

		if ( ! $product ) {
			return null;
		}

		$parentId   = $product->get_parent_id() ? (int) $product->get_parent_id() : (int) $product->get_id();
		$productIds = array_values( array_unique( array( (int) $product->get_id(), $parentId ) ) );
		$roles      = null === $roles ? TierPricingTablePlugin::getCurrentUserRoles() : $roles;
		$categories = null;

		$best      = null;
		$bestMatch = 0;

		foreach ( $forms as $form ) {
			$formRoles = $form->getShowOnRoles();

			if ( ! $anyRole && $formRoles && ! array_intersect( $formRoles, $roles ) ) {
				continue;
			}

			if ( array_intersect( $productIds, $form->getExcludedProducts() ) ) {
				continue;
			}

			$match = 0;

			if ( RequestQuoteForm::SHOW_ON_ALL === $form->getShowOn() ) {
				$match = self::MATCH_ALL;
			} elseif ( array_intersect( $productIds, $form->getShowOnProducts() ) ) {
				$match = self::MATCH_PRODUCT;
			} elseif ( $form->getShowOnCategories() ) {
				if ( null === $categories ) {
					$parent     = $parentId === (int) $product->get_id() ? $product : wc_get_product( $parentId );
					$categories = $parent ? array_map( 'intval', $parent->get_category_ids() ) : array();
				}

				if ( array_intersect( $categories, GlobalPricingRule::withSubcategories( $form->getShowOnCategories() ) ) ) {
					$match = self::MATCH_CATEGORY;
				}
			}

			if ( $match > $bestMatch ) {
				$best      = $form;
				$bestMatch = $match;
			}
		}

		return $best;
	}
}
