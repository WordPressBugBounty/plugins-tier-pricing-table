<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\StatusBox;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\StatusViews;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\RuleOverlaps;

/**
 * The primary column: title, status badge, and a meta line with the priority, the date window and
 * the rules that take precedence over it. Row actions are appended by WordPress.
 */
class Rule {

	/**
	 * @var RuleOverlaps
	 */
	protected $overlaps;

	public function getName(): string {
		return __( 'Rule', 'tier-pricing-table' );
	}

	public function render( GlobalPricingRule $rule ) {
		$id     = $rule->getId();
		$post   = get_post( $id );
		$status = $this->status( $post ? $post->post_status : 'publish', $rule );
		?>
		<span class="tpt-rules-list__badge tpt-rules-list__badge--<?php echo esc_attr( $status['key'] ); ?>"><?php echo esc_html( $status['label'] ); ?></span>
		<strong><a class="row-title" href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $rule->getTitle() ); ?></a></strong>
		<?php $this->renderLockInfo( $id ); ?>
		<div class="tpt-rules-list__meta">
			<?php echo wp_kses_post( implode( ' <span class="tpt-rules-list__sep">·</span> ', $this->metaParts( $rule ) ) ); ?>
		</div>
		<?php
	}

	/**
	 * @return array{key: string, label: string}
	 */
	protected function status( string $postStatus, GlobalPricingRule $rule ): array {
		$format = get_option( 'date_format' );

		if ( 'publish' !== $postStatus ) {
			$object = get_post_status_object( $postStatus );

			return array( 'key' => 'draft', 'label' => $object ? $object->label : $postStatus );
		}

		$status = $rule->getEffectiveStatus();

		switch ( $status ) {
			case 'scheduled':
				/* translators: %s: date */
				$label = sprintf( __( 'Starts %s', 'tier-pricing-table' ), wp_date( $format, $rule->getStartTimestamp() ) );
				break;
			case 'expired':
				/* translators: %s: date */
				$label = sprintf( __( 'Ended %s', 'tier-pricing-table' ), wp_date( $format, $rule->getEndTimestamp() ) );
				break;
			default:
				$label = StatusViews::labels()[ $status ];
		}

		return array( 'key' => $status, 'label' => $label );
	}

	/**
	 * @return string[] Escaped HTML fragments.
	 */
	protected function metaParts( GlobalPricingRule $rule ): array {
		/* translators: %d: priority number */
		$parts = array( esc_html( sprintf( __( 'Priority %d', 'tier-pricing-table' ), $rule->getPriority() ) ) );

		if ( $rule->hasSchedule() ) {
			$parts[] = esc_html( StatusBox::scheduleSummary( $rule ) );
		}

		// only a problem for a rule in force, and only rules in force can override it (skipped ones never apply)
		$overriding = 'publish' === get_post_status( $rule->getId() ) && $rule->canBeOverridden()
			? $this->getOverlaps()->findOverriding( $rule, GlobalTieredPricingCPT::getGlobalRules( true, false ) )
			: array();

		if ( $overriding ) {
			$names = array_map( function ( GlobalPricingRule $other ) {
				return $other->getTitle();
			}, $overriding );

			$parts[] = sprintf(
				'<span class="tpt-rules-list__warn" title="%s">%s</span>',
				esc_attr( implode( ', ', $names ) ),
				/* translators: %d: number of rules */
				esc_html( sprintf( _n( 'Overridden by %d rule', 'Overridden by %d rules', count( $overriding ), 'tier-pricing-table' ), count( $overriding ) ) )
			);
		}

		return $parts;
	}

	/**
	 * The "someone is editing" line WordPress's own title column prints; the heartbeat updates it.
	 */
	protected function renderLockInfo( int $id ) {
		if ( ! current_user_can( 'edit_post', $id ) || 'trash' === get_post_status( $id ) ) {
			return;
		}

		$lockHolder = wp_check_post_lock( $id );
		$lockHolder = $lockHolder ? get_userdata( $lockHolder ) : null;
		?>
		<div class="locked-info">
			<span class="locked-avatar"><?php echo $lockHolder ? get_avatar( $lockHolder->ID, 18 ) : ''; ?></span>
			<span class="locked-text">
				<?php
				if ( $lockHolder ) {
					/* translators: %s: user's display name */
					echo esc_html( sprintf( __( '%s is currently editing', 'tier-pricing-table' ), $lockHolder->display_name ) );
				}
				?>
			</span>
		</div>
		<?php
	}

	protected function getOverlaps(): RuleOverlaps {
		if ( ! $this->overlaps ) {
			$this->overlaps = new RuleOverlaps();
		}

		return $this->overlaps;
	}
}
