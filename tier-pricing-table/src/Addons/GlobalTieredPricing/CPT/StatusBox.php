<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\RuleOverlaps;
use TierPricingTable\Addons\Tools\PricingTest\PricingTester;
use WP_Post;

/**
 * The plugin's own sidebar box in place of WordPress's Publish box: the rule's status (active, suspended,
 * scheduled, expired, not published), the suspend switch, the priority, the date window, the rules it
 * overlaps with, the Pricing test link, and the Update / Move to Trash actions.
 *
 * The classic editor's script and the save pipeline look for the hidden status inputs, the "#submitpost"
 * wrapper, the "#publish" button and the "#major-publishing-actions" bar; they keep their ids and names.
 */
class StatusBox {

	/**
	 * Posted with the box, so other save paths (WP-CLI, bulk edits) keep the stored values.
	 */
	const MARKER = 'tpt_status_box';

	public function __construct() {
		add_action( 'add_meta_boxes_' . GlobalTieredPricingCPT::SLUG, array( $this, 'register' ) );
	}

	public function register() {
		remove_meta_box( 'submitdiv', GlobalTieredPricingCPT::SLUG, 'side' );

		add_meta_box( 'tpt-rule-status-box', __( 'Rule status', 'tier-pricing-table' ), array( $this, 'render' ), GlobalTieredPricingCPT::SLUG, 'side', 'high' );
	}

	public function render( WP_Post $post ) {
		$rule      = GlobalPricingRule::build( $post->ID );
		$isNew     = 'auto-draft' === $post->post_status;
		$published = 'publish' === $post->post_status;
		$status    = $this->status( $post, $rule );
		// only a rule in force can be overridden, and only by rules in force (skipped ones never apply)
		$overriding = ( $isNew || ! $published || ! $rule->canBeOverridden() )
			? array()
			: ( new RuleOverlaps() )->findOverriding( $rule, GlobalTieredPricingCPT::getGlobalRules( true, false ) );
		$format    = get_option( 'date_format' );
		?>
		<div class="submitbox" id="submitpost">
			<div style="display: none;"><?php submit_button( __( 'Save', 'tier-pricing-table' ), '', 'save' ); ?></div>
			<input type="hidden" name="hidden_post_status" id="hidden_post_status" value="<?php echo esc_attr( $isNew ? 'draft' : $post->post_status ); ?>">
			<input type="hidden" name="post_status" id="post_status" value="<?php echo esc_attr( $isNew ? 'draft' : $post->post_status ); ?>">
			<input type="hidden" name="<?php echo esc_attr( self::MARKER ); ?>" value="1">

			<div class="tpt-rule-box">
				<div class="tpt-rule-box__state tpt-rule-box__state--<?php echo esc_attr( $status['key'] ); ?>">
					<span class="dashicons <?php echo esc_attr( $status['icon'] ); ?>"></span>
					<div class="tpt-rule-box__text">
						<strong><?php echo esc_html( $status['label'] ); ?></strong>
						<div class="tpt-rule-box__detail"><?php echo esc_html( $status['detail'] ); ?></div>
					</div>
					<?php if ( $published ) : ?>
						<?php // a link-styled submit: saves the form and flips the state in one click ?>
						<?php if ( $rule->isSuspended() ) : ?>
							<button type="submit" name="tpt_suspend_action" value="reactivate" class="button-link tpt-rule-box__toggle"><?php esc_html_e( 'Reactivate', 'tier-pricing-table' ); ?></button>
						<?php else : ?>
							<button type="submit" name="tpt_suspend_action" value="suspend" class="button-link tpt-rule-box__toggle"><?php esc_html_e( 'Suspend', 'tier-pricing-table' ); ?></button>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<?php // the rule type: product prices (the default) or the order total; the tabs on the left follow it ?>
				<details class="tpt-rule-box__section tpt-rule-box__section--kind" <?php echo $isNew ? 'open' : ''; ?>>
					<summary>
						<span><?php esc_html_e( 'Rule type', 'tier-pricing-table' ); ?></span>
						<span class="tpt-rule-box__meta tpt-rule-box__kind-meta"><?php echo esc_html( $rule->isOrderRule() ? __( 'Cart rule', 'tier-pricing-table' ) : __( 'Product prices', 'tier-pricing-table' ) ); ?></span>
					</summary>
					<div class="tpt-rule-box__body">
						<?php foreach ( self::kindOptions() as $kind => $option ) : ?>
							<label class="tpt-rule-box__kind">
								<input type="radio" name="tpt_rule_kind" value="<?php echo esc_attr( $kind ); ?>" data-label="<?php echo esc_attr( $option['label'] ); ?>" <?php checked( $kind, $rule->getKind() ); ?>>
								<span class="tpt-rule-box__kind-text">
									<strong><?php echo esc_html( $option['label'] ); ?></strong>
									<span class="description"><?php echo esc_html( $option['hint'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</details>

				<details class="tpt-rule-box__section">
					<summary>
						<span><?php esc_html_e( 'Priority', 'tier-pricing-table' ); ?></span>
						<span class="tpt-rule-box__meta"><?php echo esc_html( number_format_i18n( $rule->getPriority() ) ); ?></span>
					</summary>
					<div class="tpt-rule-box__body">
						<input type="number" id="tpt_priority" name="tpt_priority" min="0" step="1" value="<?php echo esc_attr( $rule->getPriority() ); ?>" aria-label="<?php esc_attr_e( 'Priority', 'tier-pricing-table' ); ?>">
						<p class="description"><?php esc_html_e( 'The rule with the lowest number is applied first. If two rules have the same number, the newer rule is applied first.', 'tier-pricing-table' ); ?></p>
					</div>
				</details>

				<details class="tpt-rule-box__section" <?php echo $rule->hasSchedule() ? 'open' : ''; ?>>
					<summary>
						<span><?php esc_html_e( 'Schedule', 'tier-pricing-table' ); ?></span>
						<span class="tpt-rule-box__meta"><?php echo esc_html( self::scheduleSummary( $rule ) ); ?></span>
					</summary>
					<div class="tpt-rule-box__body">
						<label for="tpt_start_date"><?php esc_html_e( 'Start date', 'tier-pricing-table' ); ?></label>
						<input type="date" id="tpt_start_date" name="tpt_start_date" value="<?php echo esc_attr( (string) $rule->getStartDate() ); ?>">
						<label for="tpt_end_date"><?php esc_html_e( 'End date', 'tier-pricing-table' ); ?></label>
						<input type="date" id="tpt_end_date" name="tpt_end_date" value="<?php echo esc_attr( (string) $rule->getEndDate() ); ?>">
						<p class="description">
							<?php
							printf(
								/* translators: %s: timezone, e.g. "Europe/Kyiv" */
								esc_html__( 'Whole days in the site timezone (%s). Leave a date empty for no limit.', 'tier-pricing-table' ),
								esc_html( wp_timezone_string() )
							);
							?>
						</p>
					</div>
				</details>

				<?php if ( $overriding ) : // only when another rule takes precedence somewhere ?>
				<details class="tpt-rule-box__section" open>
					<summary>
						<span><?php esc_html_e( 'Overridden by', 'tier-pricing-table' ); ?></span>
						<span class="tpt-rule-box__meta tpt-rule-box__meta--warn"><?php echo esc_html( number_format_i18n( count( $overriding ) ) ); ?></span>
					</summary>
					<div class="tpt-rule-box__body">
							<ul class="tpt-rule-box__overlaps">
								<?php foreach ( $overriding as $other ) : ?>
									<li>
										<a href="<?php echo esc_url( get_edit_post_link( $other->getId(), 'raw' ) ); ?>" title="<?php echo esc_attr( $other->getTitle() ); ?>"><?php echo esc_html( $other->getTitle() ); ?></a>
										<span class="tpt-rule-box__meta">
											<?php
											/* translators: %d: priority number */
											echo esc_html( sprintf( __( 'priority %d', 'tier-pricing-table' ), $other->getPriority() ) );
											if ( $other->hasSchedule() ) {
												echo ', ' . esc_html( self::scheduleSummary( $other ) );
											}
											?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
							<p class="description"><?php esc_html_e( 'These rules have a higher priority. When one of them matches the same product and customer, that rule is applied and the rule you are editing is skipped.', 'tier-pricing-table' ); ?></p>
					</div>
				</details>
				<?php endif; ?>

				<?php // no test link for a skipped rule: the test could only say it changes nothing ?>
				<?php // an order rule is tested with a cart, a product rule with a product ?>
				<?php if ( ! $isNew && PricingTester::isAvailable() && ! ( $published && 'skipped' === $rule->getEffectiveStatus() ) ) : ?>
					<p class="tpt-rule-box__test">
						<span class="dashicons dashicons-search"></span>
						<a href="<?php echo esc_url( PricingTester::getUrl( $post->ID, $rule->isOrderRule() ? 'cart' : 'product' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Test this rule', 'tier-pricing-table' ); ?></a>
						<span class="description"><?php echo esc_html( $rule->isOrderRule() ? __( 'What a cart gets, and which cart rule wins.', 'tier-pricing-table' ) : __( 'What a customer pays for a product, and which rule wins.', 'tier-pricing-table' ) ); ?></span>
					</p>
				<?php endif; ?>
			</div>

			<div id="major-publishing-actions">
				<div id="delete-action">
					<?php if ( ! $isNew && current_user_can( 'delete_post', $post->ID ) ) : ?>
						<a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">
							<?php echo EMPTY_TRASH_DAYS ? esc_html__( 'Move to Trash', 'tier-pricing-table' ) : esc_html__( 'Delete permanently', 'tier-pricing-table' ); ?>
						</a>
					<?php endif; ?>
				</div>
				<div id="publishing-action">
					<span class="spinner"></span>
					<?php if ( $published ) : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update', 'tier-pricing-table' ); ?>">
						<?php submit_button( __( 'Update', 'tier-pricing-table' ), 'primary large', 'save', false, array( 'id' => 'publish' ) ); ?>
					<?php else : ?>
						<input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Publish', 'tier-pricing-table' ); ?>">
						<?php submit_button( __( 'Publish', 'tier-pricing-table' ), 'primary large', 'publish', false ); ?>
					<?php endif; ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<style>
			#tpt-rule-status-box .inside {
				margin: 0;
				padding: 0;
			}

			.tpt-rule-box {
				padding: 12px;
			}

			.tpt-rule-box__state {
				display: flex;
				gap: 8px;
				align-items: flex-start;
				margin-bottom: 12px;
				padding: 10px 12px;
				border-radius: 4px;
				line-height: 1.4;
			}

			.tpt-rule-box__state .dashicons {
				flex: 0 0 auto;
				font-size: 20px;
				width: 20px;
				height: 20px;
			}

			.tpt-rule-box__state--active {
				background: #edfaef;
				color: #007017;
			}

			.tpt-rule-box__state--scheduled {
				background: #f0f6fc;
				color: #2271b1;
			}

			.tpt-rule-box__state--suspended,
			.tpt-rule-box__state--skipped {
				background: #fcf9e8;
				color: #996800;
			}

			.tpt-rule-box__state--expired,
			.tpt-rule-box__state--draft {
				background: #f0f0f1;
				color: #50575e;
			}

			.tpt-rule-box__detail {
				margin-top: 2px;
				font-size: 12px;
			}

			.tpt-rule-box__text {
				flex: 1 1 auto;
				min-width: 0;
			}

			.tpt-rule-box__toggle {
				flex: 0 0 auto;
				align-self: flex-start;
				margin-top: 1px;
				font-size: 12px;
				line-height: 1.4;
				text-decoration: underline;
			}

			.tpt-rule-box__body label {
				display: block;
				margin: 0 0 4px;
				font-weight: 600;
			}

			.tpt-rule-box__body label + label,
			.tpt-rule-box__body input + label {
				margin-top: 8px;
			}

			.tpt-rule-box__body input[type="date"],
			.tpt-rule-box__body input[type="number"] {
				width: 100%;
			}

			.tpt-rule-box .description {
				margin: 6px 0 0;
				font-size: 12px;
				color: #646970;
			}

			.tpt-rule-box__section {
				margin-top: 12px;
				padding-top: 10px;
				border-top: 1px solid #dcdcde;
			}

			.tpt-rule-box__section summary {
				display: flex;
				align-items: center;
				gap: 8px;
				cursor: pointer;
				font-weight: 600;
				list-style: none;
			}

			.tpt-rule-box__section summary::-webkit-details-marker {
				display: none;
			}

			.tpt-rule-box__section summary > span:first-child {
				flex: 1 1 auto;
			}

			.tpt-rule-box__section summary::after {
				content: "\f140";
				font-family: dashicons;
				font-size: 18px;
				color: #787c82;
				transition: transform .15s;
			}

			.tpt-rule-box__section[open] summary::after {
				transform: rotate(180deg);
			}

			.tpt-rule-box__meta {
				font-weight: 400;
				font-size: 12px;
				color: #646970;
			}

			.tpt-rule-box__body {
				padding: 10px 0 2px;
			}

			.tpt-rule-box__overlaps {
				margin: 0;
			}

			.tpt-rule-box__overlaps li {
				margin: 0 0 8px;
				padding-left: 10px;
				border-left: 3px solid #dba617;
				line-height: 1.5;
			}

			.tpt-rule-box__overlaps a {
				display: block;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				font-weight: 600;
			}

			.tpt-rule-box__meta--warn {
				color: #996800;
				font-weight: 600;
			}

			/* the Rule type options: the box's label rule makes labels blocks, so these are more specific */
			.tpt-rule-box__body .tpt-rule-box__kind {
				display: flex;
				gap: 8px;
				align-items: flex-start;
				margin: 0 0 10px;
				font-weight: normal;
				cursor: pointer;
			}

			.tpt-rule-box__body .tpt-rule-box__kind:last-child {
				margin-bottom: 0;
			}

			.tpt-rule-box__body .tpt-rule-box__kind input[type="radio"] {
				flex: 0 0 auto;
				width: 16px;
				height: 16px;
				margin: 2px 0 0;
			}

			.tpt-rule-box__body .tpt-rule-box__kind-text {
				display: flex;
				flex-direction: column;
				line-height: 1.4;
			}

			.tpt-rule-box__body .tpt-rule-box__kind-text .description {
				margin: 0;
			}

			.tpt-rule-box__test {
				display: flex;
				flex-wrap: wrap;
				gap: 2px 6px;
				align-items: center;
				margin: 12px 0 0;
				padding-top: 12px;
				border-top: 1px solid #dcdcde;
			}

			.tpt-rule-box__test .dashicons {
				color: #8c8f94;
			}

			.tpt-rule-box__test .description {
				flex: 1 1 100%;
				margin: 0;
			}
		</style>
		<?php
	}

	/**
	 * @return array{key: string, label: string, detail: string, icon: string}
	 */
	protected function status( WP_Post $post, GlobalPricingRule $rule ): array {
		$format = get_option( 'date_format' );

		if ( 'publish' !== $post->post_status ) {
			return array(
				'key'    => 'draft',
				'label'  => __( 'Not published', 'tier-pricing-table' ),
				'detail' => __( 'Publish the rule to put it in force.', 'tier-pricing-table' ),
				'icon'   => 'dashicons-edit',
			);
		}

		switch ( $rule->getEffectiveStatus() ) {
			case 'suspended':
				return array(
					'key'    => 'suspended',
					'label'  => __( 'Suspended', 'tier-pricing-table' ),
					'detail' => __( 'The rule is saved but does not apply until you reactivate it.', 'tier-pricing-table' ),
					'icon'   => 'dashicons-controls-pause',
				);
			case 'expired':
				return array(
					'key'    => 'expired',
					'label'  => __( 'Expired', 'tier-pricing-table' ),
					/* translators: %s: date */
					'detail' => sprintf( __( 'Ended %s. Clear or move the end date to use the rule again.', 'tier-pricing-table' ), wp_date( $format, $rule->getEndTimestamp() ) ),
					'icon'   => 'dashicons-dismiss',
				);
			case 'skipped':
				return array(
					'key'    => 'skipped',
					'label'  => __( 'Skipped', 'tier-pricing-table' ),
					'detail' => self::skippedReason( $rule ),
					'icon'   => 'dashicons-warning',
				);
			case 'scheduled':
				return array(
					'key'    => 'scheduled',
					'label'  => __( 'Scheduled', 'tier-pricing-table' ),
					/* translators: %s: date */
					'detail' => sprintf( __( 'Starts %s.', 'tier-pricing-table' ), wp_date( $format, $rule->getStartTimestamp() ) ),
					'icon'   => 'dashicons-clock',
				);
		}

		return array(
			'key'    => 'active',
			'label'  => __( 'Active', 'tier-pricing-table' ),
			'detail' => $rule->getEndDate()
				/* translators: %s: date */
				? sprintf( __( 'Applies now, until %s.', 'tier-pricing-table' ), wp_date( $format, $rule->getEndTimestamp() ) )
				: __( 'Applies now.', 'tier-pricing-table' ),
			'icon'   => 'dashicons-yes-alt',
		);
	}

	/**
	 * Why the storefront skips a rule: the validation's own message, which names what is missing.
	 */
	public static function skippedReason( GlobalPricingRule $rule ): string {
		try {
			$rule->validatePricing();
		} catch ( \Exception $e ) {
			return $e->getMessage();
		}
		
		return __( 'The rule changes neither prices nor quantity limits, so the storefront skips it. Set a price, tiers or a limit on the tabs on the left.', 'tier-pricing-table' );
	}
	
	/**
	 * The rule types with their labels and one-line hints.
	 *
	 * @return array<string, array{label: string, hint: string}>
	 */
	public static function kindOptions(): array {
		return array(
			GlobalPricingRule::KIND_PRODUCT => array(
				'label' => __( 'Product prices', 'tier-pricing-table' ),
				'hint'  => __( 'Tiers and base prices per product.', 'tier-pricing-table' ),
			),
			GlobalPricingRule::KIND_ORDER   => array(
				'label' => __( 'Cart rule', 'tier-pricing-table' ),
				'hint'  => __( 'A discount, free shipping or a minimum order, based on the cart subtotal or item count.', 'tier-pricing-table' ),
			),
		);
	}

	/**
	 * "Always", "From …", "Until …" or "… to …".
	 */
	public static function scheduleSummary( GlobalPricingRule $rule ): string {
		if ( ! $rule->hasSchedule() ) {
			return __( 'Always', 'tier-pricing-table' );
		}

		$format = get_option( 'date_format' );
		$from   = $rule->getStartDate() ? wp_date( $format, $rule->getStartTimestamp() ) : null;
		$to     = $rule->getEndDate() ? wp_date( $format, $rule->getEndTimestamp() ) : null;

		if ( $from && $to ) {
			/* translators: 1: start date, 2: end date */
			return sprintf( __( '%1$s to %2$s', 'tier-pricing-table' ), $from, $to );
		}

		if ( $from ) {
			/* translators: %s: start date */
			return sprintf( __( 'From %s', 'tier-pricing-table' ), $from );
		}

		/* translators: %s: end date */
		return sprintf( __( 'Until %s', 'tier-pricing-table' ), $to );
	}
}
