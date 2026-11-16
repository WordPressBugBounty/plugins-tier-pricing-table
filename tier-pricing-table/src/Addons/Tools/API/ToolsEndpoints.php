<?php namespace TierPricingTable\Addons\Tools\API;

use TierPricingTable\Addons\CustomColumns\CustomColumnsManager;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\RuleTypePointer;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use TierPricingTable\Addons\TaxSettings\API\TaxSettingsEndpoints;
use TierPricingTable\Admin\Notifications\Notifications;
use TierPricingTable\Admin\Tips\Tip;
use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\Settings;
use TierPricingTable\Addons\Tools\PricingTest\CartTester;
use TierPricingTable\Addons\Tools\PricingTest\PricingTester;
use WP_Error;
use WP_REST_Request;

class ToolsEndpoints {
	
	const NAMESPACE = 'tier-pricing-table/features/tools/v1';
	
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}
	
	public function registerRoutes() {
		
		register_rest_route( self::NAMESPACE, '/cleanup', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'cleanupData' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/reset_settings', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'resetSettings' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/delete_global_rules', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'deleteGlobalRules' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/pricing_test', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'pricingTest' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
				'args'                => array(
					'product_id' => array( 'type' => 'integer', 'required' => true ),
					'user_id'    => array( 'type' => 'integer', 'default' => 0 ),
					'rule_id'    => array( 'type' => 'integer', 'default' => 0 ),
				),
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/cart_test', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'cartTest' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_woocommerce' );
				},
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/roles', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRoles' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/roles_capabilities', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRolesAndCapabilities' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'createRole' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'updateRoleCapabilities' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'deleteRole' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
		
		// the "Cache" tab: the switch saves as it changes
		register_rest_route( self::NAMESPACE, '/cache', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'saveCache' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'enabled' => array( 'type' => 'boolean', 'required' => true ),
				),
			),
		) );
		
		register_rest_route( self::NAMESPACE, '/purge_cache', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'purgeCache' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			),
		) );
	}
	
	/**
	 * The cache switch as the "Cache" tab shows it.
	 *
	 * @return array{enabled: bool}
	 */
	public static function getCacheState(): array {
		return array(
			'enabled' => ServiceContainer::getInstance()->getCache()->isEnabled(),
		);
	}
	
	public function saveCache( WP_REST_Request $request ) {
		update_option( Settings::SETTINGS_PREFIX . 'cache_enabled', rest_sanitize_boolean( $request->get_param( 'enabled' ) ) ? 'yes' : 'no' );
		
		return rest_ensure_response( self::getCacheState() );
	}
	
	public function purgeCache() {
		ServiceContainer::getInstance()->getCache()->purge();
		
		return rest_ensure_response( array( 'purged' => true ) );
	}
	
	/**
	 * The Pricing test: what a customer (0 = visitor) gets for a product, and which global rules apply.
	 */
	public function pricingTest( WP_REST_Request $request ) {
		$productId = absint( $request->get_param( 'product_id' ) );
		$userId    = absint( $request->get_param( 'user_id' ) );
		$ruleId    = absint( $request->get_param( 'rule_id' ) );
		
		if ( ! $productId ) {
			return new WP_Error( 'tpt_pricing_test_product', __( 'Pick a product first.', 'tier-pricing-table' ), array( 'status' => 400 ) );
		}
		
		if ( $userId && ! get_user_by( 'id', $userId ) ) {
			return new WP_Error( 'tpt_pricing_test_customer', __( 'The customer could not be loaded.', 'tier-pricing-table' ), array( 'status' => 400 ) );
		}
		
		$result = ( new PricingTester() )->run( $productId, $userId, $ruleId );
		
		if ( isset( $result['error'] ) ) {
			return new WP_Error( 'tpt_pricing_test', $result['error'], array( 'status' => 400 ) );
		}
		
		return rest_ensure_response( $result );
	}
	
	/**
	 * The Pricing test for a cart: which order rule applies to a set of lines for a customer, the discount
	 * and the minimums.
	 */
	public function cartTest( WP_REST_Request $request ) {
		$items  = (array) $request->get_param( 'items' );
		$userId = absint( $request->get_param( 'user_id' ) );
		$ruleId = absint( $request->get_param( 'rule_id' ) );
		
		if ( $userId && ! get_user_by( 'id', $userId ) ) {
			return new WP_Error( 'tpt_cart_test_customer', __( 'The customer could not be loaded.', 'tier-pricing-table' ), array( 'status' => 400 ) );
		}
		
		$result = ( new CartTester() )->run( $items, $userId, $ruleId );
		
		if ( isset( $result['error'] ) ) {
			return new WP_Error( 'tpt_cart_test', $result['error'], array( 'status' => 400 ) );
		}
		
		return rest_ensure_response( $result );
	}
	
	public function getRoles() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}
		$roles = $wp_roles->get_names();
		
		$response = array();
		foreach ( $roles as $key => $name ) {
			$response[] = array(
				'value' => $key,
				'label' => $name,
			);
		}
		
		return rest_ensure_response( $response );
	}
	
	public function cleanupData( WP_REST_Request $request ) {
		$cleanStandard = filter_var( $request->get_param( 'clean_standard' ), FILTER_VALIDATE_BOOLEAN );
		$cleanUsers    = filter_var( $request->get_param( 'clean_users' ), FILTER_VALIDATE_BOOLEAN );
		$cleanAllRoles = filter_var( $request->get_param( 'clean_all_roles' ), FILTER_VALIDATE_BOOLEAN );
		
		if ( $cleanAllRoles ) {
			global $wp_roles;
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new \WP_Roles();
			}
			$roles = array_keys( $wp_roles->get_names() );
		} else {
			$roles = $request->get_param( 'roles' ); // array of role keys
		}
		
		$categories    = $request->get_param( 'categories' ); // array of term ids
		$products      = $request->get_param( 'products' ); // array of product ids
		
		$metaKeysToDelete = array();
		
		if ( $cleanStandard ) {
			$metaKeysToDelete = array_merge( $metaKeysToDelete, array(
				'_fixed_price_rules',
				'_percentage_price_rules',
				'_amount_price_rules',
				'_tiered_price_rules_type',
				'_tiered_price_minimum_qty',
				'_tiered_pricing_maximum_quantity',
				'_tiered_pricing_group_of_quantity',
				'_tiered_pricing_template',
				'_tiered_pricing_base_unit_name',
				'_tiered_pricing_default_variation_id',
				'_tiered_pricing_variable_product_same_prices',
			) );
		}
		
		if ( ! empty( $roles ) && is_array( $roles ) ) {
			foreach ( $roles as $roleKey ) {
				$roleKey          = sanitize_key( $roleKey );
				$metaKeysToDelete = array_merge( $metaKeysToDelete, array(
					"_{$roleKey}_tiered_price_pricing_type",
					"_{$roleKey}_tiered_price_regular_price",
					"_{$roleKey}_tiered_price_sale_price",
					"_{$roleKey}_tiered_price_discount",
					"_{$roleKey}_tiered_price_discount_type",
					"_{$roleKey}_tiered_price_rules_type",
					"_{$roleKey}_percentage_price_rules",
					"_{$roleKey}_amount_price_rules",
					"_{$roleKey}_fixed_price_rules",
					"_{$roleKey}_tiered_price_minimum_qty",
					"_{$roleKey}_tiered_pricing_maximum_quantity",
					"_{$roleKey}_tiered_pricing_group_of_quantity",
				) );
			}
		}
		
		$deletedCount = 0;
		
		if ( ! empty( $metaKeysToDelete ) ) {
			global $wpdb;
			$metaKeysIn = "'" . implode( "','", array_map( 'esc_sql', $metaKeysToDelete ) ) . "'";
			
			$postIds = array();
			
			if ( ! empty( $products ) && is_array( $products ) ) {
				$postIds = array_map( 'intval', $products );
			}
			
			if ( ! empty( $categories ) && is_array( $categories ) ) {
				$categoryIds = array_map( 'intval', $categories );
				$args        = array(
					'limit'    => - 1,
					'return'   => 'ids',
					'category' => array_map( function ( $id ) {
						$term = get_term( $id, 'product_cat' );
						
						return $term ? $term->slug : '';
					}, $categoryIds ),
				);
				
				$catProducts = wc_get_products( $args );
				$postIds     = array_unique( array_merge( $postIds, $catProducts ) );
			}
			
			if ( empty( $products ) && empty( $categories ) ) {
				$query        = "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($metaKeysIn)";
				$deletedCount = $wpdb->query( $query );
			} else {
				if ( ! empty( $postIds ) ) {
					$postIdsIn    = implode( ',', $postIds );
					$query        = "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($metaKeysIn) AND post_id IN ($postIdsIn)";
					$deletedCount = $wpdb->query( $query );
				}
			}
			
			if ( $deletedCount === false ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => 'Database error during cleanup.',
				) );
			}
		}
		
		if ( $cleanUsers ) {
			global $wpdb;
			
			$userMetaLikeQueries = array(
				"meta_key LIKE '\_user\_%\_tiered_price\_pricing\_type'",
				"meta_key LIKE '\_user\_%\_tiered_price\_regular\_price'",
				"meta_key LIKE '\_user\_%\_tiered_price\_sale\_price'",
				"meta_key LIKE '\_user\_%\_tiered_price\_discount'",
				"meta_key LIKE '\_user\_%\_tiered_price\_discount\_type'",
				"meta_key LIKE '\_user\_%\_tiered_price\_rules\_type'",
				"meta_key LIKE '\_user\_%\_percentage\_price\_rules'",
				"meta_key LIKE '\_user\_%\_fixed\_price\_rules'",
				"meta_key LIKE '\_user\_%\_tiered_price\_minimum\_qty'",
				"meta_key LIKE '\_user\_%\_tiered_price\_tax\_status'",
				"meta_key LIKE '\_user\_%\_tiered_price\_tax\_class'"
			);
			
			$likeQuery = implode( ' OR ', $userMetaLikeQueries );
			
			if ( empty( $products ) && empty( $categories ) ) {
				$query = "DELETE FROM {$wpdb->postmeta} WHERE ($likeQuery)";
				$userDeletedCount = $wpdb->query( $query );
			} else {
				if ( ! empty( $postIds ) ) {
					$postIdsIn = implode( ',', $postIds );
					$query = "DELETE FROM {$wpdb->postmeta} WHERE ($likeQuery) AND post_id IN ($postIdsIn)";
					$userDeletedCount = $wpdb->query( $query );
				}
			}
			
			if ( $userDeletedCount !== false ) {
				$deletedCount += $userDeletedCount;
			}
		}
		
		delete_option( \TierPricingTable\Admin\Tips\Tip::SEEN_TIPS_OPTION_KEY );
		
		$message = sprintf( 'Successfully cleaned up %d product records.', $deletedCount );
		
		return rest_ensure_response( array(
			'success'      => true,
			'deleted_rows' => $deletedCount,
			'message'      => $message,
		) );
	}
	
	/**
	 * Options the reset keeps although they carry the plugin's prefix: they hold the store's content, not
	 * settings. Products and pricing rules point to quote forms by ID, as quote requests do.
	 */
	public const RESET_KEEPS = array( RequestQuoteForm::OPTION );

	/**
	 * The options the reset deletes: every option with one of the plugin's prefixes, except RESET_KEEPS.
	 *
	 * @return string[]
	 */
	public static function getResetOptionNames(): array {
		global $wpdb;

		$names = array();

		foreach ( array( 'tier_pricing_table_', 'tiered_pricing_table/', CustomColumnsManager::CUSTOM_COLUMNS_OPTION_NAME, 'tpt_section_badge_seen_' ) as $prefix ) {
			$names = array_merge( $names, $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ) );
		}

		return array_values( array_diff( array_unique( $names ), self::RESET_KEEPS ) );
	}

	/**
	 * Back to a fresh install: every option of the plugin, the labels and the custom columns, the
	 * role tax overrides, the dismissed tips, badges and guides. Products, pricing rules, quote
	 * forms, quote requests and wholesale applications are kept.
	 */
	public function resetSettings( WP_REST_Request $request ) {
		foreach ( self::getResetOptionNames() as $option ) {
			delete_option( $option );
		}
		
		// dismissed tips, badges and notices come back; the activation date stays, it is history, not a setting
		foreach ( array( Tip::SEEN_TIPS_OPTION_KEY, 'tpt_integrations_badge_seen', TaxSettingsEndpoints::OPTION_KEY, Notifications::SEEN_NOTIFICATIONS_OPTION_KEY, Notifications::SNOOZED_NOTIFICATIONS_OPTION_KEY ) as $option ) {
			delete_option( $option );
		}
		
		// the rule type tip of the first rule is dismissed per user
		RuleTypePointer::resetDismissals();
		
		// cached prices and pricing tables were built with the old settings
		wp_cache_flush();
		
		// the first-run defaults again, the theme colour included; must come after the deletes
		if ( class_exists( '\TierPricingTable\Core\Install' ) ) {
			\TierPricingTable\Core\Install::reset();
		}
		
		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Plugin settings were successfully reset to defaults.',
		) );
	}
	
	public function deleteGlobalRules( WP_REST_Request $request ) {
		$globalRules = get_posts( array(
			'numberposts' => - 1,
			'post_type'   => GlobalTieredPricingCPT::SLUG,
			'post_status' => 'any',
			'fields'      => 'ids',
		) );
		
		$deletedCount = 0;
		foreach ( $globalRules as $ruleId ) {
			if ( wp_delete_post( $ruleId, true ) ) {
				$deletedCount ++;
			}
		}
		
		return rest_ensure_response( array(
			'success'      => true,
			'deleted_rows' => $deletedCount,
			'message'      => sprintf( 'Successfully deleted %d global rules.', $deletedCount ),
		) );
	}
	
	public function getRolesAndCapabilities() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}
		
		$allRoles = $wp_roles->roles;
		
		$allCapabilities = array();
		foreach ( $allRoles as $role ) {
			if ( ! empty( $role['capabilities'] ) ) {
				foreach ( $role['capabilities'] as $cap => $granted ) {
					$allCapabilities[ $cap ] = true;
				}
			}
		}
		
		$rolesResponse = array();
		foreach ( $allRoles as $key => $roleData ) {
			$rolesResponse[] = array(
				'key'          => $key,
				'name'         => $roleData['name'],
				'capabilities' => ! empty( $roleData['capabilities'] ) ? $roleData['capabilities'] : array(),
			);
		}
		
		$capabilitiesList = array_keys( $allCapabilities );
		sort( $capabilitiesList );
		
		return rest_ensure_response( array(
			'roles'            => $rolesResponse,
			'all_capabilities' => $capabilitiesList,
		) );
	}
	
	public function createRole( WP_REST_Request $request ) {
		$roleKey      = sanitize_key( $request->get_param( 'key' ) );
		$roleName     = sanitize_text_field( $request->get_param( 'name' ) );
		$capabilities = $request->get_param( 'capabilities' );
		
		if ( empty( $roleKey ) || empty( $roleName ) ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Role key and name are required.' ) );
		}
		
		if ( get_role( $roleKey ) ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Role already exists.' ) );
		}
		
		$capsToSave = array();
		if ( ! empty( $capabilities ) && is_array( $capabilities ) ) {
			foreach ( $capabilities as $cap ) {
				$capsToSave[ $cap ] = true;
			}
		}
		
		$result = add_role( $roleKey, $roleName, $capsToSave );
		
		if ( null !== $result ) {
			return rest_ensure_response( array( 'success' => true, 'message' => 'Role created successfully.' ) );
		}
		
		return rest_ensure_response( array( 'success' => false, 'message' => 'Failed to create role.' ) );
	}
	
	public function updateRoleCapabilities( WP_REST_Request $request ) {
		$roleKey      = sanitize_key( $request->get_param( 'key' ) );
		$capabilities = $request->get_param( 'capabilities' );
		
		$role = get_role( $roleKey );
		if ( ! $role ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Role not found.' ) );
		}
		
		// Administrator keeps every capability: taking one away can lock everyone out of the site
		if ( 'administrator' === $roleKey ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'The Administrator role cannot be changed here.', 'tier-pricing-table' ),
			) );
		}
		
		$lost = $this->getCapabilitiesTheEditorWouldLose( $roleKey, is_array( $capabilities ) ? $capabilities : array() );
		
		if ( $lost ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: capability names, for example "manage_options, edit_users" */
					__( 'Not saved: you have this role, and without %s you could no longer manage the store or this screen. Keep those capabilities, or change the role from another administrator account.', 'tier-pricing-table' ),
					implode( ', ', $lost )
				),
			) );
		}
		
		foreach ( $role->capabilities as $cap => $granted ) {
			$role->remove_cap( $cap );
		}
		
		if ( ! empty( $capabilities ) && is_array( $capabilities ) ) {
			foreach ( $capabilities as $cap ) {
				$role->add_cap( $cap );
			}
		}
		
		return rest_ensure_response( array( 'success' => true, 'message' => 'Role capabilities updated.' ) );
	}
	
	/**
	 * The capabilities the current user needs to run the store and this screen that they would no longer have if the
	 * role, which they hold, kept only the given capabilities (none of their other roles granting them).
	 *
	 * @param  string  $roleKey
	 * @param  array  $newCapabilities  The capability names the role would keep.
	 *
	 * @return string[]
	 */
	protected function getCapabilitiesTheEditorWouldLose( string $roleKey, array $newCapabilities ): array {
		$user = wp_get_current_user();
		
		if ( ! $user || ! in_array( $roleKey, (array) $user->roles, true ) ) {
			return array();
		}
		
		$needed = array( 'manage_options', 'manage_woocommerce', 'edit_users', 'promote_users' );
		$lost   = array();
		
		foreach ( $needed as $cap ) {
			if ( ! $user->has_cap( $cap ) || in_array( $cap, $newCapabilities, true ) ) {
				continue;
			}
			
			$elsewhere = false;
			
			foreach ( (array) $user->roles as $otherRole ) {
				$other = $otherRole !== $roleKey ? get_role( $otherRole ) : null;
				
				if ( $other && $other->has_cap( $cap ) ) {
					$elsewhere = true;
					break;
				}
			}
			
			if ( ! $elsewhere && empty( $user->caps[ $cap ] ) ) {
				$lost[] = $cap;
			}
		}
		
		return $lost;
	}
	
	public function deleteRole( WP_REST_Request $request ) {
		$roleKey = sanitize_key( $request->get_param( 'key' ) );
		
		if ( empty( $roleKey ) ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Role key is required.' ) );
		}
		
		$protected_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
		if ( in_array( $roleKey, $protected_roles ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Cannot delete a core WordPress role.',
			) );
		}
		
		$role = get_role( $roleKey );
		if ( ! $role ) {
			return rest_ensure_response( array( 'success' => false, 'message' => 'Role not found.' ) );
		}
		
		remove_role( $roleKey );
		
		return rest_ensure_response( array( 'success' => true, 'message' => 'Role deleted successfully.' ) );
	}
}
