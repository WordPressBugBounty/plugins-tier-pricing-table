<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models\WholesaleApplication;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Services\ApplicationService;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;
use WP_Post;

/**
 * The applications list and the application screen: columns, approve/reject actions, bulk actions.
 */
class ApplicationAdmin {

	const ACTION = 'tpt_wholesale_application';
	const NONCE  = 'tpt_wholesale_application_nonce';

	public function __construct() {
		add_filter( 'manage_' . ApplicationCPT::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . ApplicationCPT::POST_TYPE . '_posts_custom_column', array( $this, 'columnData' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'rowActions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . ApplicationCPT::POST_TYPE, array( $this, 'bulkActions' ) );
		add_filter( 'handle_bulk_actions-edit-' . ApplicationCPT::POST_TYPE, array( $this, 'handleBulkActions' ), 10, 3 );
		add_filter( 'views_edit-' . ApplicationCPT::POST_TYPE, array( $this, 'removeUnusedViews' ) );
		add_filter( 'display_post_states', array( $this, 'postStates' ), 10, 2 );

		add_action( 'admin_post_' . self::ACTION, array( $this, 'handleAction' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );

		add_action( 'add_meta_boxes_' . ApplicationCPT::POST_TYPE, array( $this, 'metaBoxes' ) );
		add_action( 'save_post_' . ApplicationCPT::POST_TYPE, array( $this, 'saveDecision' ), 10, 2 );
		add_filter( 'enter_title_here', array( $this, 'titlePlaceholder' ), 10, 2 );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	/* --- list table ------------------------------------------------------------------------ */

	public function columns( array $columns ): array {
		return array(
			'cb'        => $columns['cb'] ?? '<input type="checkbox" />',
			'applicant' => __( 'Applicant', 'tier-pricing-table' ),
			'company'   => __( 'Company', 'tier-pricing-table' ),
			'contact'   => __( 'Contact', 'tier-pricing-table' ),
			'status'    => __( 'Status', 'tier-pricing-table' ),
			'submitted' => __( 'Submitted', 'tier-pricing-table' ),
			'decision'  => __( 'Decision', 'tier-pricing-table' ),
		);
	}

	public function columnData( string $column, int $postId ) {
		$application = WholesaleApplication::get( $postId );

		if ( ! $application ) {
			return;
		}

		switch ( $column ) {
			case 'applicant':
				$user = $application->getUser();
				?>
				<strong><a href="<?php echo esc_url( $application->getAdminUrl() ); ?>"><?php echo esc_html( $application->getName() ?: $application->getEmail() ); ?></a></strong>
				<div class="tpt-wholesale-muted">
					<?php if ( $user ) : ?>
						<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $application->getEmail() ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $application->getEmail() ); ?>
						<em><?php esc_html_e( '(account deleted)', 'tier-pricing-table' ); ?></em>
					<?php endif; ?>
				</div>
				<?php
				break;
			case 'company':
				echo esc_html( $application->getCompany() ?: '—' );

				if ( $application->getField( 'tax_id' ) ) {
					?><div class="tpt-wholesale-muted"><?php echo esc_html( $application->getField( 'tax_id' ) ); ?></div><?php
				}
				break;
			case 'contact':
				echo esc_html( $application->getField( 'phone' ) ?: '—' );

				if ( $application->getField( 'website' ) ) {
					?><div class="tpt-wholesale-muted"><a href="<?php echo esc_url( $application->getField( 'website' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', $application->getField( 'website' ) ) ); ?></a></div><?php
				}
				break;
			case 'status':
				$this->statusBadge( $application );
				break;
			case 'submitted':
				echo esc_html( $application->getDate() ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $application->getDate() ) ) : '—' );
				break;
			case 'decision':
				if ( $application->isPending() ) {
					?>
					<a class="button button-primary button-small" href="<?php echo esc_url( $this->actionUrl( 'approve', $postId ) ); ?>"><?php esc_html_e( 'Approve', 'tier-pricing-table' ); ?></a>
					<a class="button button-small" href="<?php echo esc_url( $this->actionUrl( 'reject', $postId ) ); ?>"><?php esc_html_e( 'Reject', 'tier-pricing-table' ); ?></a>
					<?php
				} else {
					$decidedAt = $application->getField( 'decided_at' );
					$decidedBy = (int) $application->getField( 'decided_by' );
					$by        = $decidedBy ? get_user_by( 'id', $decidedBy ) : null;

					echo esc_html( $decidedAt ? date_i18n( get_option( 'date_format' ), strtotime( $decidedAt ) ) : '—' );

					if ( $by ) {
						?><div class="tpt-wholesale-muted"><?php echo esc_html( $by->display_name ); ?></div><?php
					} elseif ( ! $decidedBy && $application->isApproved() ) {
						?><div class="tpt-wholesale-muted"><?php esc_html_e( 'automatic', 'tier-pricing-table' ); ?></div><?php
					}
				}
				break;
		}
	}

	protected function statusBadge( WholesaleApplication $application ) {
		$class = str_replace( 'tpt-', '', $application->getStatus() );
		?>
		<span class="tpt-wholesale-status tpt-wholesale-status--<?php echo esc_attr( $class ); ?>"><?php echo esc_html( $application->getStatusLabel() ); ?></span>
		<?php
	}

	public function rowActions( array $actions, WP_Post $post ): array {
		if ( ApplicationCPT::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		$application = WholesaleApplication::get( $post->ID );
		$new         = array();

		if ( isset( $actions['edit'] ) ) {
			$new['edit'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'View', 'tier-pricing-table' ) . '</a>';
		}

		if ( $application && ! $application->isApproved() ) {
			$new['approve'] = '<a href="' . esc_url( $this->actionUrl( 'approve', $post->ID ) ) . '">' . esc_html__( 'Approve', 'tier-pricing-table' ) . '</a>';
		}

		if ( $application && ! $application->isRejected() ) {
			$new['reject'] = '<a href="' . esc_url( $this->actionUrl( 'reject', $post->ID ) ) . '">' . esc_html__( 'Reject', 'tier-pricing-table' ) . '</a>';
		}

		if ( isset( $actions['trash'] ) ) {
			$new['trash'] = $actions['trash'];
		}

		return $new;
	}

	public function bulkActions( array $actions ): array {
		unset( $actions['edit'] );

		return array_merge( array(
			'tpt_approve' => __( 'Approve', 'tier-pricing-table' ),
			'tpt_reject'  => __( 'Reject', 'tier-pricing-table' ),
		), $actions );
	}

	public function handleBulkActions( string $redirect, string $action, array $postIds ): string {
		if ( ! in_array( $action, array( 'tpt_approve', 'tpt_reject' ), true ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return $redirect;
		}

		$service = new ApplicationService();
		$done    = 0;

		foreach ( $postIds as $postId ) {
			$application = WholesaleApplication::get( (int) $postId );

			if ( ! $application ) {
				continue;
			}

			$ok = 'tpt_approve' === $action
				? $service->approve( $application, get_current_user_id() )
				: $service->reject( $application, get_current_user_id() );

			$done += $ok ? 1 : 0;
		}

		return add_query_arg( array(
			'tpt-wholesale-notice' => 'tpt_approve' === $action ? 'approved' : 'rejected',
			'tpt-wholesale-count'  => $done,
		), $redirect );
	}

	public function removeUnusedViews( array $views ): array {
		unset( $views['publish'], $views['draft'], $views['future'], $views['private'] );

		return $views;
	}

	public function postStates( array $states, WP_Post $post ): array {
		if ( ApplicationCPT::POST_TYPE === $post->post_type ) {
			return array(); // the status column is enough
		}

		return $states;
	}

	/* --- approve / reject links -------------------------------------------------------------- */

	protected function actionUrl( string $do, int $postId ): string {
		return wp_nonce_url( add_query_arg( array(
			'action' => self::ACTION,
			'do'     => $do,
			'id'     => $postId,
		), admin_url( 'admin-post.php' ) ), self::ACTION . '_' . $postId, self::NONCE );
	}

	public function handleAction() {
		$postId = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$do     = isset( $_GET['do'] ) ? sanitize_key( $_GET['do'] ) : '';

		if ( ! $postId || ! current_user_can( 'manage_woocommerce' ) || ! isset( $_GET[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ self::NONCE ] ) ), self::ACTION . '_' . $postId ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'tier-pricing-table' ) );
		}

		$application = WholesaleApplication::get( $postId );
		$service     = new ApplicationService();
		$notice      = 'error';

		if ( $application && 'approve' === $do && $service->approve( $application, get_current_user_id() ) ) {
			$notice = 'approved';
		} elseif ( $application && 'reject' === $do && $service->reject( $application, get_current_user_id() ) ) {
			$notice = 'rejected';
		}

		$back = wp_get_referer() ?: admin_url( 'edit.php?post_type=' . ApplicationCPT::POST_TYPE );

		wp_safe_redirect( add_query_arg( array( 'tpt-wholesale-notice' => $notice, 'tpt-wholesale-count' => 1 ), remove_query_arg( array( 'tpt-wholesale-notice', 'tpt-wholesale-count' ), $back ) ) );
		exit;
	}

	public function notices() {
		if ( empty( $_GET['tpt-wholesale-notice'] ) ) {
			return;
		}

		$notice = sanitize_key( $_GET['tpt-wholesale-notice'] );
		$count  = isset( $_GET['tpt-wholesale-count'] ) ? (int) $_GET['tpt-wholesale-count'] : 1;

		if ( 'approved' === $notice ) {
			/* translators: %s: number of applications */
			$message = sprintf( _n( '%s application approved. The customer now has the wholesale role.', '%s applications approved. The customers now have the wholesale role.', $count, 'tier-pricing-table' ), number_format_i18n( $count ) );
			$class   = 'notice-success';
		} elseif ( 'rejected' === $notice ) {
			/* translators: %s: number of applications */
			$message = sprintf( _n( '%s application rejected.', '%s applications rejected.', $count, 'tier-pricing-table' ), number_format_i18n( $count ) );
			$class   = 'notice-info';
		} else {
			$message = __( 'The application could not be updated. Check that the wholesale role exists and that the customer account still exists.', 'tier-pricing-table' );
			$class   = 'notice-error';
		}
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php
	}

	/* --- application screen --------------------------------------------------------------- */

	public function metaBoxes( WP_Post $post ) {
		remove_meta_box( 'submitdiv', ApplicationCPT::POST_TYPE, 'side' );
		remove_meta_box( 'slugdiv', ApplicationCPT::POST_TYPE, 'normal' );

		add_meta_box( 'tpt-wholesale-details', __( 'Application', 'tier-pricing-table' ), array( $this, 'renderDetails' ), ApplicationCPT::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'tpt-wholesale-decision', __( 'Decision', 'tier-pricing-table' ), array( $this, 'renderDecision' ), ApplicationCPT::POST_TYPE, 'side', 'high' );
	}

	public function renderDetails( WP_Post $post ) {
		$application = new WholesaleApplication( $post );
		$user        = $application->getUser();
		?>
		<table class="widefat striped tpt-wholesale-details">
			<tbody>
			<?php foreach ( $application->getDetails() as $label => $value ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td>
						<?php if ( $value === $application->getField( 'website' ) && '' !== $value ) : ?>
							<a href="<?php echo esc_url( $value ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $value ); ?></a>
						<?php else : ?>
							<?php echo nl2br( esc_html( $value ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Customer account', 'tier-pricing-table' ); ?></th>
				<td>
					<?php if ( $user ) : ?>
						<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
						<span class="tpt-wholesale-muted">(<?php echo esc_html( implode( ', ', array_map( 'translate_user_role', array_map( function ( $r ) { return wp_roles()->roles[ $r ]['name'] ?? $r; }, (array) $user->roles ) ) ) ); ?>)</span>
					<?php else : ?>
						<em><?php esc_html_e( 'The account no longer exists.', 'tier-pricing-table' ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Submitted', 'tier-pricing-table' ); ?></th>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $application->getDate() ) ) ); ?></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	public function renderDecision( WP_Post $post ) {
		$application = new WholesaleApplication( $post );
		$role        = $application->getRequestedRole() ?: Settings::getRole();
		$roleName    = $role && isset( wp_roles()->roles[ $role ] ) ? translate_user_role( wp_roles()->roles[ $role ]['name'] ) : $role;

		wp_nonce_field( self::ACTION . '_decision', self::NONCE );
		?>
		<p><?php $this->statusBadge( $application ); ?></p>

		<?php if ( $application->isRejected() && $application->getRejectionReason() ) : ?>
			<p class="tpt-wholesale-muted"><?php echo esc_html( $application->getRejectionReason() ); ?></p>
		<?php endif; ?>

		<?php if ( ! $application->isApproved() ) : ?>
			<p><?php
				/* translators: %s: role name */
				echo esc_html( sprintf( __( 'Approving gives the customer the "%s" role.', 'tier-pricing-table' ), $roleName ) ); ?></p>
			<p><button type="submit" name="tpt_wholesale_decision" value="approve" class="button button-primary button-large tpt-wholesale-decision__button"><?php esc_html_e( 'Approve', 'tier-pricing-table' ); ?></button></p>
		<?php endif; ?>

		<?php if ( ! $application->isRejected() ) : ?>
			<p>
				<label for="tpt_wholesale_reason" class="tpt-wholesale-muted"><?php esc_html_e( 'Reason (sent to the applicant, optional)', 'tier-pricing-table' ); ?></label>
				<textarea name="tpt_wholesale_reason" id="tpt_wholesale_reason" rows="3" class="widefat"></textarea>
			</p>
			<p><button type="submit" name="tpt_wholesale_decision" value="reject" class="button button-large tpt-wholesale-decision__button"><?php esc_html_e( 'Reject', 'tier-pricing-table' ); ?></button></p>
		<?php endif; ?>

		<p class="tpt-wholesale-muted"><a class="submitdelete" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php esc_html_e( 'Move to Trash', 'tier-pricing-table' ); ?></a></p>
		<?php
	}

	public function saveDecision( int $postId, WP_Post $post ) {
		if ( empty( $_POST['tpt_wholesale_decision'] ) || ! isset( $_POST[ self::NONCE ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::ACTION . '_decision' ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$decision    = sanitize_key( $_POST['tpt_wholesale_decision'] );
		$reason      = isset( $_POST['tpt_wholesale_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tpt_wholesale_reason'] ) ) : '';
		$application = WholesaleApplication::get( $postId );
		$service     = new ApplicationService();

		if ( ! $application ) {
			return;
		}

		// the post form has already saved the post; the status change below is the decision
		remove_action( 'save_post_' . ApplicationCPT::POST_TYPE, array( $this, 'saveDecision' ), 10 );

		if ( 'approve' === $decision ) {
			$ok = $service->approve( $application, get_current_user_id() );
		} else {
			$ok = $service->reject( $application, get_current_user_id(), $reason );
		}

		add_filter( 'redirect_post_location', function ( $location ) use ( $decision, $ok ) {
			return add_query_arg( array(
				'tpt-wholesale-notice' => $ok ? ( 'approve' === $decision ? 'approved' : 'rejected' ) : 'error',
				'tpt-wholesale-count'  => 1,
			), remove_query_arg( 'message', $location ) );
		} );
	}

	public function titlePlaceholder( string $placeholder, WP_Post $post ): string {
		return ApplicationCPT::POST_TYPE === $post->post_type ? __( 'Application', 'tier-pricing-table' ) : $placeholder;
	}

	public function styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ApplicationCPT::POST_TYPE !== $screen->post_type ) {
			return;
		}
		?>
		<style>
			.tpt-wholesale-muted { color: #646970; font-size: 12px; }
			.tpt-wholesale-status { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.6; }
			.tpt-wholesale-status--pending { background: #fcf9e8; color: #8a6d0b; border: 1px solid #f0e2a6; }
			.tpt-wholesale-status--approved { background: #edfaef; color: #1e6b32; border: 1px solid #b7e2c1; }
			.tpt-wholesale-status--rejected { background: #fcf0f1; color: #8a1f28; border: 1px solid #f1b8bd; }
			.column-decision .button { margin-right: 4px; }
			.tpt-wholesale-details th { width: 180px; }
			.tpt-wholesale-decision__button { width: 100%; text-align: center; }
			#titlediv { display: none; }
		</style>
		<?php
	}
}
