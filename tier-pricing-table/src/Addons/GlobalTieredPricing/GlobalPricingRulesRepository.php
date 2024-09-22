<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\TierPricingTablePlugin;
use WC_Product;
use WP_User;

class GlobalPricingRulesRepository {
	
	protected static $instance;
	
	/**
	 * Pricing rules
	 *
	 * @var GlobalPricingRule[]
	 */
	protected $rules = array();
	
	protected $matchedPricingRules = array();
	
	public static function getInstance(): self {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	private function __construct() {
		
		// Init and store the rules
		add_action( 'init', function () {
			$this->rules = GlobalTieredPricingCPT::getGlobalRules();
		} );
	}
	
	/**
	 * Get matched pricing rule for product and user
	 *
	 * @param  WC_Product  $product
	 * @param  WP_User|null  $user
	 *
	 * @return GlobalPricingRule|null
	 */
	public function getMatchedPricingRule( WC_Product $product, WP_User $user = null ): ?GlobalPricingRule {
		
		$user = $user ? $user : TierPricingTablePlugin::getCurrentUser();

		// Cache
		if ( ! isset( $this->matchedPricingRules[ $user->ID ][ $product->get_id() ] ) ) {
			
			$matchedRule = null;
			
			foreach ( $this->rules as $globalPricingRule ) {
				if ( $globalPricingRule->matchRequirements( $user, $product ) ) {
					$matchedRule = $globalPricingRule;
					break;
				}
			}

			$this->matchedPricingRules[ $user->ID ][ $product->get_id() ] = apply_filters( 'tiered_pricing_table/global_pricing/matched_pricing_rule',
				$matchedRule, $product, $user );
		}

		return $this->matchedPricingRules[ $user->ID ][ $product->get_id() ];
	}
	
}
