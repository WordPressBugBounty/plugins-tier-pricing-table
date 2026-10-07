<?php namespace TierPricingTable\Addons\Tools\PricingTest;

use TierPricingTable\Addons\AbstractAddon;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRulesRepository;
use TierPricingTable\PriceManager;
use TierPricingTable\Settings\Settings;
use WC_Product;
use WP_User;

/**
 * The "Pricing test" utility: what a customer gets for a product, which global pricing rules apply
 * and why, and which one wins, without opening the storefront.
 */
class PricingTester {

	const TAB = 'pricing-test';

	const RULE_PARAM = 'tpt_rule';

	/**
	 * Link to the utility on the Advanced settings tab, optionally with a rule to highlight.
	 */
	public static function getUrl( int $ruleId = 0 ): string {
		$url = admin_url( 'admin.php?page=wc-settings&tab=' . Settings::SETTINGS_PAGE . '&section=advanced' );

		if ( $ruleId ) {
			$url = add_query_arg( self::RULE_PARAM, $ruleId, $url );
		}

		return $url . '#' . self::TAB;
	}

	public static function isAvailable(): bool {
		return AbstractAddon::isAddonEnabled( 'tools' );
	}

	public static function rulesEnabled(): bool {
		return AbstractAddon::isAddonEnabled( 'global-tier-pricing' );
	}

	/**
	 * Run the pricing for a product as the given customer (0 = visitor) would see it.
	 * Switches the current user for the rest of the request.
	 *
	 * @return array The result, or array{error: string}.
	 */
	public function run( int $productId, int $userId, int $highlightRuleId = 0 ): array {
		$user = $userId ? get_user_by( 'id', $userId ) : null;
		$user = $user ? $user : new WP_User( 0 );

		// the pricing providers read the current user through the plugin's accessor
		wp_set_current_user( $user->ID );
		$GLOBALS['tpt_current_user_id'] = $user->ID;
		add_filter( 'tiered_pricing_table/current_user', function () use ( $user ) {
			return $user;
		}, 999 );

		$product = wc_get_product( $productId );
		if ( ! $product ) {
			return array( 'error' => __( 'The product could not be loaded.', 'tier-pricing-table' ) );
		}

		$pricingRule = PriceManager::getPricingRule( $product->get_id() );
		$provider    = (string) ( $pricingRule->provider ?? '' );
		$tester      = new RuleTester();

		$winner = self::rulesEnabled() ? GlobalPricingRulesRepository::getInstance()->getMatchedPricingRule( $product, $user ) : null;
		// matched, but the rule's priority settings let product-level pricing win
		$overridden = $winner && 'global-rules' !== $provider;

		$tiers   = $pricingRule->getRules();
		$minimum = $pricingRule->getMinimum();
		$maximum = ! empty( $pricingRule->data['maximum_quantity'] ) ? (int) $pricingRule->data['maximum_quantity'] : null;
		$step    = ! empty( $pricingRule->data['group_of_quantity'] ) ? (int) $pricingRule->data['group_of_quantity'] : null;

		$basePrice  = (float) wc_get_price_to_display( $product );
		$quantities = array_unique( array_merge( array( 1 ), $minimum ? array( $minimum ) : array(), array_map( 'intval', array_keys( $tiers ) ) ) );
		sort( $quantities );

		$rows = array();
		foreach ( $quantities as $quantity ) {
			$tierPrice = $pricingRule->getTierPrice( $quantity );
			$price     = false !== $tierPrice ? (float) $tierPrice : $basePrice;
			$rows[]    = array(
				'quantity' => $quantity,
				'price'    => $this->formatPrice( $price ),
				'discount' => round( PriceManager::calculateDiscount( $basePrice, $price ), 2 ),
				'total'    => $this->formatPrice( $price * $quantity ),
				'tier'     => false !== $tierPrice,
			);
		}

		$limits = array();
		if ( $minimum ) {
			/* translators: %d: minimum quantity */
			$limits[] = sprintf( __( 'minimum %d', 'tier-pricing-table' ), $minimum );
		}
		if ( $maximum ) {
			/* translators: %d: maximum quantity */
			$limits[] = sprintf( __( 'maximum %d', 'tier-pricing-table' ), $maximum );
		}
		if ( $step ) {
			/* translators: %d: quantity step */
			$limits[] = sprintf( __( 'step %d', 'tier-pricing-table' ), $step );
		}

		return array(
			'product'      => array(
				'id'   => $product->get_id(),
				'name' => $product->get_name(),
				'url'  => self::editUrl( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ),
			),
			'customer'     => array(
				'id'    => $user->ID,
				'label' => $user->ID ? $user->display_name . ( $user->user_email ? ' (' . $user->user_email . ')' : '' ) : __( 'Visitor (not logged in)', 'tier-pricing-table' ),
				'roles' => $user->ID ? ( $user->roles ? $tester->roleNames( $user->roles ) : __( 'no role', 'tier-pricing-table' ) ) : '',
			),
			'rulesEnabled' => self::rulesEnabled(),
			'rules'        => self::rulesEnabled() ? $this->rules( $tester, $product, $user, $winner, $overridden, $highlightRuleId ) : array(),
			'winner'       => array(
				'id'         => $winner ? $winner->getId() : 0,
				'title'      => $winner ? self::ruleTitle( $winner->getId() ) : '',
				'url'        => $winner ? self::editUrl( $winner->getId() ) : '',
				'overridden' => (bool) $overridden,
			),
			'pricing'      => array(
				'provider' => $provider,
				'source'   => RuleTester::providerLabel( $provider ),
				'type'     => $pricingRule->getType(),
				'base'     => $this->formatPrice( $basePrice ),
				'limits'   => $limits,
				'rows'     => $rows,
				'log'      => array_values( (array) $pricingRule->getPricingLog() ),
			),
		);
	}

	/**
	 * Every rule, newest first (the order the storefront checks them in), with its verdict for this
	 * product and customer. Suspended and unpublished rules are listed too, so "why does my rule not
	 * apply?" has an answer.
	 */
	protected function rules( RuleTester $tester, WC_Product $product, WP_User $user, ?GlobalPricingRule $winner, bool $overridden, int $highlightRuleId ): array {
		$ids = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => GlobalTieredPricingCPT::SLUG,
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'fields'      => 'ids',
		) );

		$rules = array_map( function ( $id ) {
			return GlobalPricingRule::build( $id );
		}, $ids );
		usort( $rules, array( GlobalPricingRule::class, 'compareOrder' ) ); // the order the storefront checks them in

		$format = get_option( 'date_format' );
		$rows   = array();
		foreach ( $rules as $rule ) {
			$id = $rule->getId();

			$explanation = $tester->explain( $rule, $user, $product );
			$matches     = $rule->matchRequirements( $user, $product );
			if ( $matches !== $explanation['matches'] ) {
				// the explanation mirrors the matcher; if they ever disagree, the matcher's verdict stands
				$explanation = array( 'matches' => $matches, 'reasons' => array( __( 'See the rule\'s products and customers tabs.', 'tier-pricing-table' ) ) );
			}

			$published = 'publish' === get_post_status( $id );
			// active | suspended | scheduled | expired | draft
			$status    = ! $published ? 'draft' : ( $rule->isSuspended() ? 'suspended' : $rule->getScheduleStatus() );
			$isWinner  = $winner && $winner->getId() === $id;
			$notes     = array();

			if ( ! $matches ) {
				$verdict = 'no';
			} elseif ( $isWinner ) {
				$verdict = $overridden ? 'overridden' : 'wins';
				if ( $overridden ) {
					$notes[] = __( 'Because of the rule\'s priority settings, the product\'s own pricing is applied instead. The pricing log shows the decision.', 'tier-pricing-table' );
				}
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
				$notes[] = __( 'The rule changes neither prices nor quantity limits, so the storefront skips it.', 'tier-pricing-table' );
			} else {
				$verdict = 'outranked';
				$notes[] = __( 'Another matching rule has a higher priority and is applied instead. The rule with the lowest priority number is applied first; if two rules have the same number, the newer rule is applied first.', 'tier-pricing-table' );
			}

			$schedule = '';
			if ( $rule->getStartDate() || $rule->getEndDate() ) {
				$schedule = ( $rule->getStartDate() ? wp_date( $format, $rule->getStartTimestamp() ) : '…' )
				            . ' – ' . ( $rule->getEndDate() ? wp_date( $format, $rule->getEndTimestamp() ) : '…' );
			}

			$rows[] = array(
				'id'          => $id,
				'title'       => self::ruleTitle( $id ),
				'url'         => self::editUrl( $id ),
				'status'      => $status,
				'priority'    => $rule->getPriority(),
				'schedule'    => $schedule,
				'matches'     => $matches,
				'verdict'     => $verdict,
				'reasons'     => $explanation['reasons'],
				'notes'       => $notes,
				'highlighted' => $id === $highlightRuleId,
			);
		}

		return $rows;
	}

	/**
	 * The rule's title, or "Rule #ID" for a rule saved without one.
	 */
	public static function ruleTitle( int $id ): string {
		return GlobalPricingRule::titleOf( $id );
	}

	/**
	 * The edit screen of a post. Not get_edit_post_link(): the request runs as the tested customer from
	 * the user switch on, and that customer cannot edit posts, so it would return nothing.
	 */
	protected static function editUrl( int $postId ): string {
		return admin_url( 'post.php?post=' . $postId . '&action=edit' );
	}

	protected function formatPrice( float $price ): string {
		return html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' );
	}
}
