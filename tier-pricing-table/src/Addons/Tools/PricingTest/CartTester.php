<?php namespace TierPricingTable\Addons\Tools\PricingTest;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\FreeShippingBar;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscount;
use TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts\OrderDiscountResolver;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSettings;
use TierPricingTable\PriceManager;
use WC_Product;
use WP_User;

/**
 * The "Pricing test" for a cart: what a set of lines gets from the order rules as a given customer,
 * which order rule is in play and why, the discount, the minimums and the next tier, without touching
 * the storefront cart. The lines are priced the way the cart prices them (tiered prices per product).
 */
class CartTester {

	/**
	 * Run the order rules for a cart as the given customer (0 = visitor) would see it.
	 *
	 * @param  array  $items  Rows of product_id and quantity.
	 * @param  int  $userId  0 = visitor.
	 * @param  int  $highlightRuleId  The rule the test was opened from.
	 *
	 * @return array The result, or array{error: string}.
	 */
	public function run( array $items, int $userId, int $highlightRuleId = 0 ): array {
		$user = $userId ? get_user_by( 'id', $userId ) : null;
		$user = $user ? $user : new WP_User( 0 );

		// the pricing providers read the current user through the plugin's accessor (the global and the
		// filter below), so the WordPress user is not switched
		$GLOBALS['tpt_current_user_id'] = $user->ID;
		add_filter( 'tiered_pricing_table/current_user', function () use ( $user ) {
			return $user;
		}, 999 );

		$includingTax = OrderDiscountResolver::countsTax();
		$lines        = array();
		$rows         = array();

		foreach ( $items as $index => $item ) {
			$productId = absint( $item['product_id'] ?? 0 );
			$quantity  = max( 1, absint( $item['quantity'] ?? 1 ) );
			$product   = $productId ? wc_get_product( $productId ) : null;

			if ( ! $product instanceof WC_Product ) {
				/* translators: %d: line number */
				return array( 'error' => sprintf( __( 'The product of line %d could not be loaded.', 'tier-pricing-table' ), (int) $index + 1 ) );
			}

			// the cart prices every line by its tiers before the order rules look at it
			$tierPrice = PriceManager::getPriceByRules( $quantity, $product->get_id() );
			$unitPrice = false !== $tierPrice ? (float) $tierPrice : (float) $product->get_price();
			$line      = OrderDiscountResolver::lineFor( $product, $quantity, $unitPrice, $includingTax, 'line-' . $index );
			$lines[]   = $line;

			$pricingRule = PriceManager::getPricingRule( $product->get_id() );
			$rows[]      = array(
				'id'       => $product->get_id(),
				'name'     => $product->get_name(),
				'url'      => self::editUrl( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ),
				'quantity' => $quantity,
				'price'    => $this->formatPrice( $unitPrice ),
				'tier'     => false !== $tierPrice,
				'amount'   => $this->formatPrice( $line['amount'] ),
				'source'   => RuleTester::sourceLabel( $pricingRule ),
			);
		}

		if ( ! $lines ) {
			return array( 'error' => __( 'Add at least one line to the cart.', 'tier-pricing-table' ) );
		}

		$resolver = new OrderDiscountResolver();
		$discount = PricingTester::rulesEnabled() ? $resolver->resolveLines( $lines, $user ) : null;
		$inPlay   = PricingTester::rulesEnabled() ? $resolver->findRule( $lines, $user ) : null;
		$next     = PricingTester::rulesEnabled() ? $resolver->nextTierForLines( $lines, $user ) : null;
		$missed   = PricingTester::rulesEnabled() ? $resolver->missedMinimumsForLines( $lines, $user ) : array();

		$total = 0.0;
		$count = 0;
		foreach ( $lines as $line ) {
			$total += (float) $line['amount'];
			$count += (int) $line['quantity'];
		}

		return array(
			'customer'     => array(
				'id'    => $user->ID,
				'label' => $user->ID ? $user->display_name . ( $user->user_email ? ' (' . $user->user_email . ')' : '' ) : __( 'Visitor (not logged in)', 'tier-pricing-table' ),
				'roles' => $user->ID ? ( $user->roles ? ( new RuleTester() )->roleNames( $user->roles ) : __( 'no role', 'tier-pricing-table' ) ) : '',
			),
			'rulesEnabled' => PricingTester::rulesEnabled(),
			'cart'         => array(
				'lines'        => $rows,
				'total'        => $this->formatPrice( round( $total, wc_get_price_decimals() ) ),
				'count'        => $count,
				'includingTax' => $includingTax,
			),
			'discount'     => $this->discountSummary( $discount, $inPlay, $lines ),
			'freeShipping' => $this->freeShippingSummary( $discount, $inPlay, $lines ),
			'minimums'     => $this->minimums( $missed, $lines, $user, $resolver ),
			'nextTier'     => $this->nextTierSummary( $next ),
			'rules'        => PricingTester::rulesEnabled() ? $this->rules( $lines, $user, $inPlay, $discount, $highlightRuleId, $resolver ) : array(),
		);
	}

	/**
	 * The discount the cart earns, as the cart shows it.
	 */
	protected function discountSummary( ?OrderDiscount $discount, ?GlobalPricingRule $inPlay, array $lines ): array {
		if ( ! $discount || ! $discount->hasTier() ) {
			return array(
				'applies' => false,
				'rule'    => $inPlay ? array( 'id' => $inPlay->getId(), 'title' => PricingTester::ruleTitle( $inPlay->getId() ), 'url' => self::editUrl( $inPlay->getId() ) ) : null,
			);
		}

		$rule   = $discount->getRule();
		$amount = $discount->isPercentage()
			? round( $discount->getSubtotal() * $discount->getDiscount() / 100, wc_get_price_decimals() )
			: min( $discount->getDiscount(), $discount->getSubtotal() );

		return array(
			'applies'   => true,
			'rule'      => array( 'id' => $rule->getId(), 'title' => PricingTester::ruleTitle( $rule->getId() ), 'url' => self::editUrl( $rule->getId() ) ),
			'label'     => $discount->getLabel(),
			'code'      => $discount->getCode(),
			'discount'  => OrderDiscount::formatDiscount( $discount->getDiscount(), $discount->isPercentage() ),
			'threshold' => OrderDiscount::formatThreshold( $discount->getThreshold(), $rule->isOrderBasisQuantity() ),
			'basis'     => $rule->getOrderBasis(),
			'counted'   => $rule->isOrderBasisQuantity()
				/* translators: %s: number of items */
				? sprintf( _n( '%s item', '%s items', $discount->getQuantity(), 'tier-pricing-table' ), number_format_i18n( $discount->getQuantity() ) )
				: $this->formatPrice( $discount->getSubtotal() ),
			'amount'    => $this->formatPrice( (float) $amount ),
			'scoped'    => $rule->isOrderScopeMatching(),
			'linesUsed' => count( OrderDiscountResolver::counted( $rule, $lines )['product_ids'] ),
		);
	}

	/**
	 * Whether the cart ships free through the rule in play, and from which counted value.
	 */
	protected function freeShippingSummary( ?OrderDiscount $discount, ?GlobalPricingRule $inPlay, array $lines = array() ): array {
		if ( ! $inPlay || ! $inPlay->hasOrderFreeShipping() ) {
			return array( 'offered' => false, 'applies' => false );
		}

		$mode = $inPlay->getOrderFreeShipping();
		$bar  = null;

		// the free shipping bar as the customer would see it for this cart
		if ( $inPlay->hasOrderShippingBar() ) {
			$progress = FreeShippingBar::progressFor( $inPlay, OrderDiscountResolver::counted( $inPlay, $lines ) );
			$bar      = array(
				'percent' => (int) $progress['percent'],
				'message' => html_entity_decode( wp_strip_all_tags( FreeShippingBar::messageFor( $progress ) ), ENT_QUOTES, 'UTF-8' ),
			);
		}

		return array(
			'offered'   => true,
			'applies'   => $discount ? $discount->hasFreeShipping() : false,
			'rule'      => array( 'id' => $inPlay->getId(), 'title' => PricingTester::ruleTitle( $inPlay->getId() ), 'url' => self::editUrl( $inPlay->getId() ) ),
			'mode'      => $mode,
			'threshold' => 'always' === $mode ? '' : OrderDiscount::formatThreshold( (float) $inPlay->getOrderFreeShippingFrom(), 'quantity' === $mode ),
			'bar'       => $bar,
		);
	}

	/**
	 * Every minimum of the order rules that cover the customer: met or missed, with the storefront message.
	 */
	protected function minimums( array $missed, array $lines, WP_User $user, OrderDiscountResolver $resolver ): array {
		$missedKeys = array();
		foreach ( $missed as $miss ) {
			$missedKeys[ $miss['rule']->getId() . ':' . $miss['type'] ] = $miss;
		}

		$rows = array();
		foreach ( $this->orderRules( true ) as $rule ) {
			$minimum = $rule->getEffectiveOrderMinimum();
			if ( ( $minimum['amount'] <= 0 && $minimum['quantity'] <= 0 ) || ! $rule->matchesUser( $user ) ) {
				continue;
			}
			$counted = OrderDiscountResolver::counted( $rule, $lines );
			if ( empty( $counted['product_ids'] ) ) {
				continue;
			}
			foreach ( array( 'amount', 'quantity' ) as $type ) {
				if ( $minimum[ $type ] <= 0 ) {
					continue;
				}
				$key  = $rule->getId() . ':' . $type;
				$miss = $missedKeys[ $key ] ?? null;
				$rows[] = array(
					'rule'    => array( 'id' => $rule->getId(), 'title' => PricingTester::ruleTitle( $rule->getId() ), 'url' => self::editUrl( $rule->getId() ) ),
					'type'    => $type,
					'minimum' => 'amount' === $type ? $this->formatPrice( (float) $minimum['amount'] ) : number_format_i18n( (int) $minimum['quantity'] ),
					'actual'  => 'amount' === $type ? $this->formatPrice( (float) $counted['amount'] ) : number_format_i18n( (int) $counted['quantity'] ),
					'met'     => ! $miss,
					'message' => $miss ? $this->minimumMessage( $miss ) : '',
				);
			}
		}

		return $rows;
	}

	protected function minimumMessage( array $miss ): string {
		if ( 'amount' === $miss['type'] ) {
			return str_replace( array( '{minimum}', '{subtotal}' ), array( $this->formatPrice( (float) $miss['minimum'] ), $this->formatPrice( (float) $miss['actual'] ) ), CartRulesSettings::getAmountMessage() );
		}

		return str_replace( array( '{minimum}', '{count}' ), array( number_format_i18n( (int) $miss['minimum'] ), number_format_i18n( (int) $miss['actual'] ) ), CartRulesSettings::getQuantityMessage() );
	}

	protected function nextTierSummary( ?array $next ): ?array {
		if ( ! $next || $next['remaining'] <= 0 ) {
			return null;
		}

		$rule  = $next['rule'];
		$items = $rule->isOrderBasisQuantity();

		return array(
			'rule'      => array( 'id' => $rule->getId(), 'title' => PricingTester::ruleTitle( $rule->getId() ), 'url' => self::editUrl( $rule->getId() ) ),
			'remaining' => $items ? OrderDiscount::formatThreshold( (float) $next['remaining'], true ) : $this->formatPrice( (float) $next['remaining'] ),
			'threshold' => OrderDiscount::formatThreshold( (float) $next['tier']['from'], $items ),
			'discount'  => OrderDiscount::formatDiscount( (float) $next['tier']['discount'], 'percentage' === $rule->getOrderDiscountType() ),
		);
	}

	/**
	 * Every order rule in the order the storefront checks them, with its verdict for this cart and customer.
	 */
	protected function rules( array $lines, WP_User $user, ?GlobalPricingRule $inPlay, ?OrderDiscount $discount, int $highlightRuleId, OrderDiscountResolver $resolver ): array {
		$tester = new RuleTester();
		$format = get_option( 'date_format' );
		$rows   = array();

		foreach ( $this->orderRules( false ) as $rule ) {
			$id        = $rule->getId();
			$published = 'publish' === get_post_status( $id );
			$status    = ! $published ? 'draft' : ( $rule->isSuspended() ? 'suspended' : $rule->getScheduleStatus() );
			$customer  = $tester->explainCustomer( $rule, $user );
			$counted   = OrderDiscountResolver::counted( $rule, $lines );
			$reasons   = $customer['reasons'];
			$notes     = array();

			if ( $rule->isOrderScopeMatching() ) {
				$reasons[] = $counted['product_ids']
					/* translators: 1: number of lines, 2: number of lines in the cart */
					? sprintf( __( '%1$d of %2$d cart lines match the rule\'s products and are counted.', 'tier-pricing-table' ), count( $counted['product_ids'] ), count( $lines ) )
					: __( 'None of the cart lines matches the rule\'s products.', 'tier-pricing-table' );
			} else {
				$reasons[] = __( 'The rule counts the whole cart (no products, categories, tags or brands selected).', 'tier-pricing-table' );
			}

			if ( ! $customer['matches'] ) {
				$verdict = 'no';
			} elseif ( empty( $counted['product_ids'] ) ) {
				$verdict = 'no-lines';
			} elseif ( 'active' !== $status ) {
				$verdict = 'inactive';
				switch ( $status ) {
					case 'draft':
						$notes[] = __( 'The rule is not published, so the storefront ignores it.', 'tier-pricing-table' );
						break;
					case 'suspended':
						$notes[] = __( 'The rule is suspended, so the storefront ignores it.', 'tier-pricing-table' );
						break;
					case 'scheduled':
						/* translators: %s: date */
						$notes[] = sprintf( __( 'The rule starts on %s, so the storefront ignores it until then.', 'tier-pricing-table' ), wp_date( $format, $rule->getStartTimestamp() ) );
						break;
					case 'expired':
						/* translators: %s: date */
						$notes[] = sprintf( __( 'The rule ended on %s, so the storefront ignores it.', 'tier-pricing-table' ), wp_date( $format, $rule->getEndTimestamp() ) );
						break;
				}
			} elseif ( ! $rule->isValidPricing() ) {
				$verdict = 'skipped';
				try {
					$rule->validatePricing();
				} catch ( \Exception $e ) {
					$notes[] = $e->getMessage();
				}
			} elseif ( $inPlay && $inPlay->getId() === $id ) {
				if ( $discount ) {
					$verdict = 'wins';
					/* translators: 1: tier threshold, 2: discount */
					$notes[] = sprintf( __( 'The cart reaches the tier from %1$s: %2$s off.', 'tier-pricing-table' ), OrderDiscount::formatThreshold( $discount->getThreshold(), $rule->isOrderBasisQuantity() ), OrderDiscount::formatDiscount( $discount->getDiscount(), $discount->isPercentage() ) );
				} elseif ( $discount && $discount->hasFreeShipping() ) {
					$verdict = 'wins';
					$notes[] = __( 'The cart reaches the free shipping threshold.', 'tier-pricing-table' );
				} elseif ( empty( $rule->getOrderTiers() ) ) {
					$verdict = 'minimum-only';
					$notes[] = $rule->hasOrderFreeShipping()
						? __( 'The rule has no discount tiers; the cart is below its free shipping threshold.', 'tier-pricing-table' )
						: __( 'The rule has no discount tiers; it only sets a minimum order.', 'tier-pricing-table' );
				} else {
					$verdict = 'below';
					$notes[] = __( 'The cart is below the rule\'s first tier. As the matching rule with the highest priority it holds the cart: rules below it are not applied.', 'tier-pricing-table' );
				}
			} else {
				$verdict = 'outranked';
				$notes[] = __( 'Another matching cart rule has a higher priority and is the one in play. The rule with the lowest priority number is applied first; if two rules have the same number, the newer rule is applied first.', 'tier-pricing-table' );
			}

			// the minimum of every matching rule counts, whoever gives the discount
			$minimum = $rule->getEffectiveOrderMinimum();
			if ( $customer['matches'] && $counted['product_ids'] && 'active' === $status && ( $minimum['amount'] > 0 || $minimum['quantity'] > 0 ) ) {
				$notes[] = __( 'Its minimum order is checked for this cart: see the minimums above.', 'tier-pricing-table' );
			}

			$schedule = '';
			if ( $rule->getStartDate() || $rule->getEndDate() ) {
				$schedule = ( $rule->getStartDate() ? wp_date( $format, $rule->getStartTimestamp() ) : '…' )
							. ' – ' . ( $rule->getEndDate() ? wp_date( $format, $rule->getEndTimestamp() ) : '…' );
			}

			$rows[] = array(
				'id'          => $id,
				'title'       => PricingTester::ruleTitle( $id ),
				'url'         => self::editUrl( $id ),
				'status'      => $status,
				'priority'    => $rule->getPriority(),
				'schedule'    => $schedule,
				'verdict'     => $verdict,
				'reasons'     => $reasons,
				'notes'       => $notes,
				'highlighted' => $id === $highlightRuleId,
			);
		}

		return $rows;
	}

	/**
	 * The order rules, in the order the storefront checks them.
	 *
	 * @param  bool  $inForce  Only the published, active, valid ones.
	 *
	 * @return GlobalPricingRule[]
	 */
	protected function orderRules( bool $inForce ): array {
		if ( $inForce ) {
			return array_values( array_filter( GlobalTieredPricingCPT::getGlobalRules(), function ( GlobalPricingRule $rule ) {
				return $rule->isOrderRule();
			} ) );
		}

		$ids = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => GlobalTieredPricingCPT::SLUG,
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'fields'      => 'ids',
		) );

		$rules = array_filter( array_map( function ( $id ) {
			return GlobalPricingRule::build( $id );
		}, $ids ), function ( GlobalPricingRule $rule ) {
			return $rule->isOrderRule();
		} );

		usort( $rules, array( GlobalPricingRule::class, 'compareOrder' ) );

		return array_values( $rules );
	}

	protected static function editUrl( int $postId ): string {
		return admin_url( 'post.php?post=' . $postId . '&action=edit' );
	}

	protected function formatPrice( float $price ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' );
	}
}
