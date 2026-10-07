<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

/**
 * A short banner above the rule form: the steps of making a rule, each linking to its tab, and how
 * rules resolve against each other. Every user sees it until they dismiss it; the dismissal is
 * stored per user.
 */
class Guide {

	const USER_META = 'tpt_rule_guide_dismissed';

	const AJAX_ACTION = 'tpt_dismiss_rule_guide';

	const DOCS_URL = 'https://tiered-pricing.com/documentation/user/#global-pricing-rules';

	public function __construct() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'dismiss' ) );
	}

	public static function isDismissed(): bool {
		return (bool) get_user_meta( get_current_user_id(), self::USER_META, true );
	}

	public function dismiss() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		update_user_meta( get_current_user_id(), self::USER_META, 1 );

		wp_send_json_success();
	}

	public function render() {
		if ( self::isDismissed() ) {
			return;
		}

		$tab = function ( string $id, string $text ): string {
			return '<a href="#" data-tab="' . esc_attr( $id ) . '">' . esc_html( $text ) . '</a>';
		};
		?>
		<div class="tpt-rule-guide" data-nonce="<?php echo esc_attr( wp_create_nonce( self::AJAX_ACTION ) ); ?>">
			<div class="tpt-rule-guide__head">
				<strong><?php esc_html_e( 'Making a pricing rule', 'tier-pricing-table' ); ?></strong>
				<button type="button" class="tpt-rule-guide__close tpt-rule-guide__dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'tier-pricing-table' ); ?>">&times;</button>
			</div>

			<ol class="tpt-rule-guide__steps">
				<li>
					<?php
					printf(
						/* translators: 1: link "Pricing", 2: link "Quantity limits" */
						esc_html__( 'Set the prices, tiers or quantity limits on the %1$s and %2$s tabs.', 'tier-pricing-table' ),
						wp_kses_post( $tab( 'pricing', __( 'Pricing', 'tier-pricing-table' ) ) ),
						wp_kses_post( $tab( 'quantity', __( 'Quantity limits', 'tier-pricing-table' ) ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: 1: link "products", 2: link "customers" */
						esc_html__( 'Choose the %1$s and %2$s it covers. An empty list covers all of them.', 'tier-pricing-table' ),
						wp_kses_post( $tab( 'products-and-categories', __( 'products', 'tier-pricing-table' ) ) ),
						wp_kses_post( $tab( 'user-and-roles', __( 'customers', 'tier-pricing-table' ) ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link "Priority Settings" */
						esc_html__( 'Decide how it meets product-level pricing on the %s tab.', 'tier-pricing-table' ),
						wp_kses_post( $tab( 'settings', __( 'Priority Settings', 'tier-pricing-table' ) ) )
					);
					?>
				</li>
				<li>
					<?php
					printf(
						/* translators: %s: link "Rule status box" */
						esc_html__( 'Publish, then use "Test this rule" in the %s to see what a customer pays.', 'tier-pricing-table' ),
						'<a href="#" data-scroll="#tpt-rule-status-box">' . esc_html__( 'Rule status box', 'tier-pricing-table' ) . '</a>'
					);
					?>
				</li>
			</ol>

			<p class="tpt-rule-guide__foot">
				<?php esc_html_e( 'When several rules match the same product and customer, the rule with the lowest priority number is applied. The Rule status box lists the rules that override the one you are editing.', 'tier-pricing-table' ); ?>
				<span class="tpt-rule-guide__links">
					<a href="<?php echo esc_url( self::DOCS_URL ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Read the guide', 'tier-pricing-table' ); ?></a>
					<span aria-hidden="true">·</span>
					<a href="#" class="tpt-rule-guide__dismiss"><?php esc_html_e( 'Don\'t show again', 'tier-pricing-table' ); ?></a>
				</span>
			</p>
		</div>
		<style>
			.tpt-rule-guide {
				position: relative;
				margin: 16px 0 0;
				padding: 12px 44px 12px 16px;
				border: 1px solid #c3c4c7;
				border-left: 4px solid var(--wp-admin-theme-color, #2271b1);
				border-radius: 3px;
				background: #fff;
				box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			}

			.tpt-rule-guide__head strong {
				font-size: 14px;
			}

			.tpt-rule-guide__close {
				position: absolute;
				top: 6px;
				right: 6px;
				width: 30px;
				height: 30px;
				padding: 0;
				border: 0;
				background: none;
				color: #787c82;
				font-size: 20px;
				line-height: 1;
				cursor: pointer;
			}

			.tpt-rule-guide__close:hover {
				color: #d63638;
			}

			.tpt-rule-guide__steps {
				display: grid;
				grid-template-columns: repeat(4, minmax(0, 1fr));
				gap: 6px 20px;
				margin: 8px 0 0;
				padding: 0;
				list-style: none;
				counter-reset: tpt-rule-guide;
			}

			.tpt-rule-guide__steps li {
				position: relative;
				margin: 0;
				padding-left: 26px;
				line-height: 1.5;
			}

			.tpt-rule-guide__steps li::before {
				content: counter(tpt-rule-guide);
				counter-increment: tpt-rule-guide;
				position: absolute;
				left: 0;
				top: 1px;
				width: 18px;
				height: 18px;
				border-radius: 50%;
				background: var(--wp-admin-theme-color, #2271b1);
				color: #fff;
				font-size: 11px;
				font-weight: 600;
				line-height: 18px;
				text-align: center;
			}

			.tpt-rule-guide__foot {
				margin: 10px 0 0;
				color: #50575e;
			}

			.tpt-rule-guide__links {
				margin-left: 6px;
				white-space: nowrap;
			}

			@media screen and (max-width: 1248px) {
				.tpt-rule-guide__steps {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}
			}

			@media screen and (max-width: 782px) {
				.tpt-rule-guide__steps {
					grid-template-columns: minmax(0, 1fr);
				}

				.tpt-rule-guide__links {
					display: block;
					margin: 6px 0 0;
				}
			}
		</style>
		<script>
			jQuery( function ( $ ) {
				var $guide = $( '.tpt-rule-guide' );

				// step links open the matching tab of the form below
				$guide.on( 'click', '[data-tab]', function ( e ) {
					e.preventDefault();
					$( '.tpt-global-pricing-rule-form-tab[data-target="tpt-global-pricing-rule-form-tab-' + $( this ).data( 'tab' ) + '"]' ).trigger( 'click' );
				} );

				$guide.on( 'click', '[data-scroll]', function ( e ) {
					e.preventDefault();
					var $target = $( $( this ).data( 'scroll' ) );
					if ( $target.length ) {
						$( 'html, body' ).animate( { scrollTop: $target.offset().top - 60 }, 200 );
					}
				} );

				// both the cross and "Don't show again" hide the banner for good, for this user
				$guide.on( 'click', '.tpt-rule-guide__dismiss', function ( e ) {
					e.preventDefault();
					$guide.slideUp( 150 );
					$.post( window.ajaxurl, { action: '<?php echo esc_js( self::AJAX_ACTION ); ?>', nonce: $guide.data( 'nonce' ) } );
				} );
			} );
		</script>
		<?php
	}
}
