<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Models;

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\CPT\ApplicationCPT;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings;
use WP_Post;
use WP_User;

/**
 * A wholesale account application, stored as a post of the tpt-wholesale-application type.
 * The post status is the application status; everything else is post meta.
 */
class WholesaleApplication {

	const STATUS_PENDING  = 'tpt-pending';
	const STATUS_APPROVED = 'tpt-approved';
	const STATUS_REJECTED = 'tpt-rejected';

	const USER_META_STATUS      = '_tpt_wholesale_status';
	const USER_META_APPLICATION = '_tpt_wholesale_application';

	/** Values of the fields the store owner added to the form: key => [ label, type, value ]. */
	const META_CUSTOM_FIELDS = '_custom_fields';

	/** Meta keys of the stored fields. */
	const FIELDS = array(
		'user_id'          => '_user_id',
		'email'            => '_email',
		'first_name'       => '_first_name',
		'last_name'        => '_last_name',
		'company'          => '_company',
		'phone'            => '_phone',
		'tax_id'           => '_tax_id',
		'website'          => '_website',
		'message'          => '_message',
		'requested_role'   => '_requested_role',
		'decided_by'       => '_decided_by',
		'decided_at'       => '_decided_at',
		'rejection_reason' => '_rejection_reason',
	);

	protected int $id = 0;

	protected string $status = self::STATUS_PENDING;

	protected string $date = '';

	protected array $data = array();

	/**
	 * @param  int|WP_Post  $post
	 */
	public function __construct( $post = 0 ) {
		$post = $post instanceof WP_Post ? $post : ( $post ? get_post( (int) $post ) : null );

		if ( $post && ApplicationCPT::POST_TYPE === $post->post_type ) {
			$this->id     = (int) $post->ID;
			$this->status = (string) $post->post_status;
			$this->date   = (string) $post->post_date;

			foreach ( self::FIELDS as $prop => $metaKey ) {
				$this->data[ $prop ] = (string) get_post_meta( $this->id, $metaKey, true );
			}
		}
	}

	public static function get( int $id ): ?self {
		$application = new self( $id );

		return $application->getId() ? $application : null;
	}

	/**
	 * The latest application of a user, whatever its status.
	 */
	public static function findForUser( int $userId ): ?self {
		if ( ! $userId ) {
			return null;
		}

		$applicationId = (int) get_user_meta( $userId, self::USER_META_APPLICATION, true );

		if ( $applicationId ) {
			$application = self::get( $applicationId );

			if ( $application ) {
				return $application;
			}
		}

		$posts = get_posts( array(
			'post_type'      => ApplicationCPT::POST_TYPE,
			'post_status'    => self::getStatuses(),
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_key'       => self::FIELDS['user_id'],
			'meta_value'     => $userId,
			'fields'         => 'ids',
		) );

		return $posts ? self::get( (int) $posts[0] ) : null;
	}

	public static function getStatuses(): array {
		return array( self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED );
	}

	public static function getStatusLabels(): array {
		return array(
			self::STATUS_PENDING  => __( 'Pending', 'tier-pricing-table' ),
			self::STATUS_APPROVED => __( 'Approved', 'tier-pricing-table' ),
			self::STATUS_REJECTED => __( 'Rejected', 'tier-pricing-table' ),
		);
	}

	public static function countPending(): int {
		$counts = wp_count_posts( ApplicationCPT::POST_TYPE );

		return (int) ( $counts->{self::STATUS_PENDING} ?? 0 );
	}

	/**
	 * Creates the post for a validated application.
	 *
	 * @param  array  $data  keys of self::FIELDS
	 *
	 * @return self|null
	 */
	public static function create( array $data ): ?self {
		$title = trim( ( $data['company'] ?? '' ) ?: trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) );

		$postId = wp_insert_post( array(
			'post_type'   => ApplicationCPT::POST_TYPE,
			'post_status' => self::STATUS_PENDING,
			'post_title'  => $title ?: (string) ( $data['email'] ?? '' ),
		), true );

		if ( is_wp_error( $postId ) || ! $postId ) {
			return null;
		}

		foreach ( self::FIELDS as $prop => $metaKey ) {
			if ( array_key_exists( $prop, $data ) ) {
				update_post_meta( $postId, $metaKey, $data[ $prop ] );
			}
		}

		if ( ! empty( $data['custom_fields'] ) && is_array( $data['custom_fields'] ) ) {
			update_post_meta( $postId, self::META_CUSTOM_FIELDS, $data['custom_fields'] );
		}

		if ( ! empty( $data['user_id'] ) ) {
			update_user_meta( (int) $data['user_id'], self::USER_META_APPLICATION, $postId );
			update_user_meta( (int) $data['user_id'], self::USER_META_STATUS, self::STATUS_PENDING );
		}

		return self::get( (int) $postId );
	}

	public function getId(): int {
		return $this->id;
	}

	public function getStatus(): string {
		return $this->status;
	}

	public function getStatusLabel(): string {
		return self::getStatusLabels()[ $this->status ] ?? $this->status;
	}

	public function isPending(): bool {
		return self::STATUS_PENDING === $this->status;
	}

	public function isApproved(): bool {
		return self::STATUS_APPROVED === $this->status;
	}

	public function isRejected(): bool {
		return self::STATUS_REJECTED === $this->status;
	}

	public function getDate(): string {
		return $this->date;
	}

	public function getField( string $prop ): string {
		return (string) ( $this->data[ $prop ] ?? '' );
	}

	public function getUserId(): int {
		return (int) $this->getField( 'user_id' );
	}

	public function getUser(): ?WP_User {
		$user = $this->getUserId() ? get_user_by( 'id', $this->getUserId() ) : false;

		return $user instanceof WP_User ? $user : null;
	}

	public function getEmail(): string {
		return $this->getField( 'email' );
	}

	public function getName(): string {
		return trim( $this->getField( 'first_name' ) . ' ' . $this->getField( 'last_name' ) );
	}

	public function getCompany(): string {
		return $this->getField( 'company' );
	}

	public function getRequestedRole(): string {
		return $this->getField( 'requested_role' );
	}

	public function getRejectionReason(): string {
		return $this->getField( 'rejection_reason' );
	}

	/**
	 * @return array<string, array{label: string, type: string, value: string}>
	 */
	public function getCustomFields(): array {
		$fields = $this->id ? get_post_meta( $this->id, self::META_CUSTOM_FIELDS, true ) : array();

		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * Moves the application to a new status and records who decided and when.
	 */
	public function setStatus( string $status, int $decidedBy = 0, string $reason = '' ): bool {
		if ( ! in_array( $status, self::getStatuses(), true ) || ! $this->id ) {
			return false;
		}

		$updated = wp_update_post( array( 'ID' => $this->id, 'post_status' => $status ), true );

		if ( is_wp_error( $updated ) ) {
			return false;
		}

		$this->status = $status;

		if ( self::STATUS_PENDING !== $status ) {
			$this->setMeta( 'decided_by', (string) $decidedBy );
			$this->setMeta( 'decided_at', current_time( 'mysql' ) );
		}

		if ( self::STATUS_REJECTED === $status ) {
			$this->setMeta( 'rejection_reason', $reason );
		}

		if ( $this->getUserId() ) {
			update_user_meta( $this->getUserId(), self::USER_META_STATUS, $status );
			update_user_meta( $this->getUserId(), self::USER_META_APPLICATION, $this->id );
		}

		return true;
	}

	public function setMeta( string $prop, string $value ) {
		if ( isset( self::FIELDS[ $prop ] ) && $this->id ) {
			update_post_meta( $this->id, self::FIELDS[ $prop ], $value );
			$this->data[ $prop ] = $value;
		}
	}

	/**
	 * Label/value pairs of the business details the applicant filled in.
	 *
	 * @return array<string, string> label => value
	 */
	public function getDetails(): array {
		$details = array(
			__( 'Name', 'tier-pricing-table' )   => $this->getName(),
			__( 'E-mail', 'tier-pricing-table' ) => $this->getEmail(),
		);

		// the labels the store owner gave the recognised fields, with the stock labels as fallback
		$labels = array(
			'company' => __( 'Company', 'tier-pricing-table' ),
			'phone'   => __( 'Phone', 'tier-pricing-table' ),
			'tax_id'  => __( 'Tax / VAT ID', 'tier-pricing-table' ),
			'website' => __( 'Website', 'tier-pricing-table' ),
			'message' => __( 'Message', 'tier-pricing-table' ),
		);

		foreach ( Settings::getFormFields() as $field ) {
			if ( isset( $labels[ $field['key'] ] ) && '' !== $field['label'] ) {
				$labels[ $field['key'] ] = $field['label'];
			}
		}

		foreach ( $labels as $prop => $label ) {
			if ( '' !== $this->getField( $prop ) ) {
				$details[ $label ] = $this->getField( $prop );
			}
		}

		foreach ( $this->getCustomFields() as $custom ) {
			$value = (string) ( $custom['value'] ?? '' );

			if ( 'checkbox' === ( $custom['type'] ?? '' ) ) {
				$value = '1' === $value ? __( 'Yes', 'tier-pricing-table' ) : __( 'No', 'tier-pricing-table' );
			}

			if ( '' !== $value ) {
				$details[ (string) ( $custom['label'] ?? '' ) ] = $value;
			}
		}

		return $details;
	}

	public function getAdminUrl(): string {
		return $this->id ? (string) get_edit_post_link( $this->id, 'raw' ) : '';
	}
}
