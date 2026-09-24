<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT;

use Automattic\WooCommerce\Admin\PageController;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication;

class ApplicationCPT {

	public const POST_TYPE = 'tpt-wholesale-app';

	public function __construct() {
		add_action( 'init', array( $this, 'registerPostType' ) );
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ), 99 );
		add_action( 'deleted_user', array( $this, 'deleteApplicationsOfUser' ) );
	}

	/**
	 * A deleted customer leaves no application behind.
	 */
	public function deleteApplicationsOfUser( $userId ) {
		$applications = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => WholesaleApplication::FIELDS['user_id'],
			'meta_value'     => (int) $userId,
		) );

		foreach ( $applications as $applicationId ) {
			wp_delete_post( (int) $applicationId, true );
		}
	}

	public function registerPostType() {
		$labels = array(
			'name'               => _x( 'Wholesale Applications', 'post type general name', 'tier-pricing-table' ),
			'singular_name'      => _x( 'Wholesale Application', 'post type singular name', 'tier-pricing-table' ),
			'menu_name'          => _x( 'Wholesale Applications', 'admin menu', 'tier-pricing-table' ),
			'edit_item'          => __( 'Wholesale Application', 'tier-pricing-table' ),
			'view_item'          => __( 'View Application', 'tier-pricing-table' ),
			'all_items'          => __( 'All Applications', 'tier-pricing-table' ),
			'search_items'       => __( 'Search Applications', 'tier-pricing-table' ),
			'not_found'          => __( 'No wholesale applications yet.', 'tier-pricing-table' ),
			'not_found_in_trash' => __( 'No wholesale applications found in Trash.', 'tier-pricing-table' ),
		);

		register_post_type( self::POST_TYPE, array(
			'labels'              => $labels,
			'description'         => __( 'Wholesale account applications.', 'tier-pricing-table' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => false, // added as a WooCommerce submenu below
			'show_in_rest'        => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'supports'            => false,
		) );

		$this->registerPostStatuses();

		if ( class_exists( PageController::class ) ) {
			PageController::get_instance()->connect_page( array(
				'id'        => self::POST_TYPE,
				'title'     => __( 'Wholesale Applications', 'tier-pricing-table' ),
				'screen_id' => 'edit-' . self::POST_TYPE,
				'path'      => 'edit.php?post_type=' . self::POST_TYPE,
			) );
		}
	}

	protected function registerPostStatuses() {
		$statuses = array(
			WholesaleApplication::STATUS_PENDING  => array(
				_x( 'Pending', 'wholesale application status', 'tier-pricing-table' ),
				/* translators: %s: number of applications */
				_n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>', 'tier-pricing-table' ),
			),
			WholesaleApplication::STATUS_APPROVED => array(
				_x( 'Approved', 'wholesale application status', 'tier-pricing-table' ),
				/* translators: %s: number of applications */
				_n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'tier-pricing-table' ),
			),
			WholesaleApplication::STATUS_REJECTED => array(
				_x( 'Rejected', 'wholesale application status', 'tier-pricing-table' ),
				/* translators: %s: number of applications */
				_n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'tier-pricing-table' ),
			),
		);

		foreach ( $statuses as $status => $labels ) {
			register_post_status( $status, array(
				'label'                     => $labels[0],
				'public'                    => false,
				'internal'                  => false,
				// a protected status is listed in the admin "All" view; "any" queries skip statuses excluded from search
				'protected'                 => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => $labels[1],
			) );
		}
	}

	public function addAdminMenu() {
		$pending   = WholesaleApplication::countPending();
		$menuTitle = __( 'Wholesale Applications', 'tier-pricing-table' );

		if ( $pending > 0 ) {
			$menuTitle .= ' <span class="update-plugins count-' . esc_attr( $pending ) . '"><span class="plugin-count" aria-hidden="true">' . number_format_i18n( $pending ) . '</span><span class="screen-reader-text">' . sprintf(
				/* translators: %s: number of pending applications */
					_n( '%s pending application', '%s pending applications', $pending, 'tier-pricing-table' ),
					number_format_i18n( $pending )
				) . '</span></span>';
		}

		add_submenu_page(
			'woocommerce',
			__( 'Wholesale Applications', 'tier-pricing-table' ),
			$menuTitle,
			'manage_woocommerce',
			'edit.php?post_type=' . self::POST_TYPE
		);
	}
}
