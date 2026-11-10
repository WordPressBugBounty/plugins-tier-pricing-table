<?php namespace TierPricingTable\Addons\GlobalTieredPricing\OrderDiscounts;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\NonLoggedInUsers\CartRules\CartRulesSettings;
use TierPricingTable\Settings\Settings;

/**
 * Moves the "Minimum order" table of the cart rules by role (8.0.0 to 8.2.1) into order rules: one
 * published rule per role that had a minimum, suspended when the cart rules were switched off. Runs once;
 * the old option is left in place untouched.
 */
class MinimumsMigration {

	const OPTION = Settings::SETTINGS_PREFIX . 'order_minimums_migrated';

	public function __construct() {
		// after the rule post type is registered (init, 10)
		add_action( 'init', array( $this, 'maybeRun' ), 20 );
	}

	public function maybeRun() {
		if ( get_option( self::OPTION ) ) {
			return;
		}

		$created = $this->run( CartRulesSettings::getMinimums(), CartRulesSettings::isEnabled() );

		update_option( self::OPTION, array( 'at' => time(), 'rules' => $created ), false );
	}

	/**
	 * Create the rules for a minimums table.
	 *
	 * @param  array<string, array{amount: float, quantity: int}>  $minimums  By role.
	 * @param  bool  $enabled  Whether the cart rules were on: off makes the new rules suspended.
	 *
	 * @return int[] The ids of the rules created.
	 */
	public function run( array $minimums, bool $enabled ): array {
		$created = array();

		foreach ( self::rulesFromMinimums( $minimums, self::roleNames() ) as $data ) {
			$id = wp_insert_post( array(
				'post_type'   => GlobalTieredPricingCPT::SLUG,
				'post_status' => 'publish',
				'post_title'  => $data['title'],
			) );

			if ( ! $id || is_wp_error( $id ) ) {
				continue;
			}

			$rule = GlobalPricingRule::fromArray( $data['rule'] );
			$rule->setId( (int) $id );
			$rule->setIncludedUsersRole( $data['roles'] );
			$rule->setIsSuspended( ! $enabled );
			$rule->save();

			$created[] = (int) $id;
		}

		return $created;
	}

	/**
	 * The rules to create for a minimums table: title, rule data and roles. Pure, for the tests.
	 *
	 * @param  array<string, array{amount: float, quantity: int}>  $minimums  By role.
	 * @param  array<string, string>  $roleNames  Role slug => display name.
	 *
	 * @return array<int, array{title: string, roles: string[], rule: array}>
	 */
	public static function rulesFromMinimums( array $minimums, array $roleNames ): array {
		$rules = array();

		foreach ( $minimums as $role => $minimum ) {
			$amount   = (float) ( $minimum['amount'] ?? 0 );
			$quantity = (int) ( $minimum['quantity'] ?? 0 );

			if ( $amount <= 0 && $quantity <= 0 ) {
				continue;
			}

			$name = $roleNames[ $role ] ?? $role;

			$rules[] = array(
				/* translators: %s: role name */
				'title' => sprintf( __( 'Minimum order for %s', 'tier-pricing-table' ), $name ),
				'roles' => array( (string) $role ),
				'rule'  => array(
					'kind'                   => GlobalPricingRule::KIND_ORDER,
					'order_minimum_amount'   => $amount > 0 ? $amount : null,
					'order_minimum_quantity' => $quantity > 0 ? $quantity : null,
				),
			);
		}

		return $rules;
	}

	/**
	 * The role names for the rule titles.
	 *
	 * @return array<string, string> Role slug => translated name, guests included.
	 */
	public static function roleNames(): array {
		$names = array( GlobalPricingRule::GUEST_ROLE => __( 'Guests', 'tier-pricing-table' ) );

		foreach ( (array) wp_roles()->roles as $slug => $role ) {
			$names[ (string) $slug ] = translate_user_role( (string) ( $role['name'] ?? $slug ) );
		}

		return $names;
	}
}
