<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\ProductAndCategories;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Quantity;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Pricing;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\Settings;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs\UsersAndRoles;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\RuleOverlaps;
use TierPricingTable\Core\ServiceContainerTrait;
use WP_Post;

class Form {

	use ServiceContainerTrait;

	/**
	 * Tabs
	 *
	 * @var FormTab[]
	 */
	protected $tabs;

	protected $defaultTab = 'pricing';

	protected $pricingRuleInstance = null;

	/**
	 * @var Guide
	 */
	protected $guide;

	public function __construct() {

		$this->guide = new Guide();

		add_action( 'init', function () {
			$tabs = array(
					new Pricing( $this ),
					new ProductAndCategories( $this ),
					new UsersAndRoles( $this ),
					new Quantity( $this ),
			);

			$tabs[] = new Settings( $this );

			$this->tabs = apply_filters( 'tiered_pricing_table/global_pricing/form_tabs', $tabs, $this );
		} );

		add_action( 'edit_form_after_title', function ( WP_Post $post ) {
			if ( GlobalTieredPricingCPT::SLUG !== $post->post_type ) {
				return;
			}

			$this->render( $post );
		} );

		new UpgradeTip();
	}

	protected function includeAssets() {
		?>
		<style>
			/**
			* Externals
			 */
			/* do not display any notices on rule creation */
			.wrap .notice:not(.notice-success, .tpt__admin__feedback-discount-banner) {
				display: none
			}

			.tpt-global-pricing-rule-form .woocommerce-help-tip {
				margin-left: 5px;
			}

			.tpt-global-pricing-rule-hint {
				display: flex;
				align-items: center;
				padding: 10px;
				border-left: 3px solid var(--wp-admin-theme-color, #2271b1);
				background: #f6f7f7;
				color: var(--wp-admin-theme-color, #2271b1) !important;
				margin-bottom: 20px;
			}

			.tpt-global-pricing-rule-hint--top-level {
				margin-top: 10px;
				border: 1px solid #888;
			}

			.tpt-global-pricing-rule-hint--warning {
				border-left-color: #dba617;
				background: #fcf9e8;
				color: #6d4f00 !important;
			}

			.tpt-global-pricing-rule-hint--top-level + .tpt-global-pricing-rule-hint--top-level {
				margin-top: -10px;
			}

			.tpt-global-pricing-rule-hint__icon {
				margin-right: 10px;
			}

			.tpt-global-pricing-rule-form {
				margin: 20px 0;
				display: flex;
				overflow: hidden;
				border-radius: 3px;
				flex-wrap: nowrap;
			}

			.tpt-global-pricing-rule-form__tabs {
				width: 30%;
				max-width: 300px;
				min-width: 250px;
			}

			.tpt-global-pricing-rule-form-tab {
				background: #fff;
				border-bottom: 1px solid #e8e8e8;
				border-left: 1px solid #e8e8e8;
				overflow: hidden;
				cursor: pointer;
				display: flex;
				align-items: center;
				padding: 15px 10px;
			}

			.tpt-global-pricing-rule-form-tab:first-child {
				border-top: 1px solid #e8e8e8;
			}

			.tpt-global-pricing-rule-form-tab:hover:not(.tpt-global-pricing-rule-form-tab--active) {
				background: #fbfbfb;
			}

			.tpt-global-pricing-rule-form-tab--active {
				cursor: default;
				background: #f6f7f7;
			}

			.tpt-global-pricing-rule-form-tab__icon {
				transition: all .1s;
				margin-right: 10px;
				height: 40px;
				aspect-ratio: 1/1;
				border-radius: 50%;
				background: #f6f7f7;
				text-align: center;
				color: var(--wp-admin-theme-color, #2271b1);
				font-size: 20px;
				font-weight: bold;
				display: flex;
				justify-content: center;
				align-items: center;
			}

			.tpt-global-pricing-rule-form-tab--active h3,
			.tpt-global-pricing-rule-form-tab--active div {
				color: var(--wp-admin-theme-color, #2271b1) !important;
			}

			.tpt-global-pricing-rule-form-tab--active .tpt-global-pricing-rule-form-tab__icon {
				background: #fff;
			}

			.tpt-global-pricing-rule-form-tab__title h3 {
				font-size: 1.1em;
				margin: 0;
			}

			.tpt-global-pricing-rule-form-tab__title div {
				margin-top: 5px;
				color: #777;
			}

			.tpt-global-pricing-rule-form-tab-content {
				display: none;
			}

			.tpt-global-pricing-rule-form-tab-content--active {
				display: block;
			}

			.tpt-global-pricing-rule-form__content {
				width: 70%;
				background: #fff;
				flex-grow: 1;
				padding: 10px;
				border: 1px solid #e8e8e8;
				box-shadow: 0 0 8px rgba(0, 0, 0, .1);
			}

			.tpt-global-pricing-rule-form input[type="text"],
			.tpt-global-pricing-rule-form input[type="number"],
			.tpt-global-pricing-rule-form .tiered-pricing-pricing-rules-form-row__inputs {
				width: 75% !important;
			}

			.tpt-global-pricing-rule-form #tiered_pricing_type {
				max-width: 75%;
				width: 75% !important;
			}

			@media screen and (max-width: 1248px) {

				.tpt-global-pricing-rule-form input[type="text"],
				.tpt-global-pricing-rule-form input[type="number"],
				.tpt-global-pricing-rule-form .tiered-pricing-pricing-rules-form-row__inputs {
					width: 100% !important;
				}

				.tpt-global-pricing-rule-form #tiered_pricing_type {
					max-width: 100%;
					width: 100% !important;
				}


				.tpt-global-pricing-rule-form {
					flex-wrap: wrap;
				}

				.tpt-global-pricing-rule-form__tabs {
					display: flex;
					max-width: 100%;
					width: 100%;
				}

				.tpt-global-pricing-rule-form-tab__icon {
					display: none;
				}

				.tpt-global-pricing-rule-form-tab--active {
					border-bottom: 3px solid var(--wp-admin-theme-color, #2271b1);
				}
			}

			@media screen and (max-width: 500px) {
				.tiered-pricing-form-block {
					padding: 5px 20px !important;
				}
			}

			/* Accordion styles */
			.tpt-exclusions-accordion {
				margin-top: 20px;
				background: #fff;
			}

			.tpt-exclusions-accordion summary {
				cursor: pointer;
				list-style: none;
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 12px 15px;
				background: #f6f7f7;
				border: 1px solid #e8e8e8;
				font-size: 14px;
				font-weight: 600;
				color: #1d2327;
				transition: background-color 0.2s ease;
			}

			.tpt-exclusions-accordion summary::-webkit-details-marker {
				display: none;
			}

			.tpt-exclusions-accordion summary:hover {
				background: #f0f0f1;
			}

			.tpt-exclusions-accordion[open] summary {
				border-bottom-left-radius: 0;
				border-bottom-right-radius: 0;
				border-bottom: 1px solid #e8e8e8;
			}

			.tpt-exclusions-accordion[open] summary .tpt-accordion-icon {
				transform: rotate(180deg);
			}

			.tpt-accordion-icon {
				transition: transform 0.3s ease;
				color: #787c82;
			}

			.tpt-exclusions-accordion-content {
				padding: 15px 0;
				border: 1px solid #e8e8e8;
				border-top: none;
				border-radius: 0 0 4px 4px;
			}
		</style>
		<script>
			jQuery(document).ready(function () {
				let tabs = jQuery('.tpt-global-pricing-rule-form-tab');
				let tabsContent = jQuery('.tpt-global-pricing-rule-form-tab-content');

				tabs.click(function (e) {
					e.preventDefault();

					tabsContent.removeClass('tpt-global-pricing-rule-form-tab-content--active');
					tabs.removeClass('tpt-global-pricing-rule-form-tab--active');

					jQuery(this).addClass('tpt-global-pricing-rule-form-tab--active');

					const target = jQuery(this).data('target');

					jQuery('#' + target).addClass('tpt-global-pricing-rule-form-tab-content--active');
				});
			});
		</script>
		<?php
	}

	protected function render( WP_Post $post ) {

		$this->includeAssets();

		$this->guide->render();

		// a rule that changes nothing is flagged as "Skipped" by the Rule status box; no second notice here
		if ( ! $this->isNewRule() ) {
			$this->renderStatusHints( $this->getPricingRuleInstance( $post ) );
		}

		?>
		<div class="tpt-global-pricing-rule-form">

			<nav class="tpt-global-pricing-rule-form__tabs">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="tpt-global-pricing-rule-form-tab <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'tpt-global-pricing-rule-form-tab--active' : '' ); ?>"
					     data-target="tpt-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">

						<div class="tpt-global-pricing-rule-form-tab__icon" style="">
							<?php if ( strpos( $tab->getIcon(), 'dashicons-' ) === 0 ) : ?>
								<span class="dashicons <?php echo esc_attr( $tab->getIcon() ); ?>"></span>
							<?php else : ?>
								<span><?php echo esc_html( $tab->getIcon() ); ?></span>
							<?php endif; ?>
						</div>

						<div class="tpt-global-pricing-rule-form-tab__title">
							<h3>
								<?php echo esc_html( $tab->getTitle() ); ?>
							</h3>
							<div><?php echo esc_html( $tab->getDescription() ); ?></div>
						</div>
					</div>
				<?php endforeach; ?>
			</nav>

			<section class="tpt-global-pricing-rule-form__content woocommerce_options_panel">
				<?php foreach ( $this->tabs as $tab ) : ?>
					<div class="tpt-global-pricing-rule-form-tab-content <?php echo esc_attr( $tab->getId() === $this->defaultTab ? 'tpt-global-pricing-rule-form-tab-content--active' : '' ); ?>"
					     id="tpt-global-pricing-rule-form-tab-<?php echo esc_attr( $tab->getId() ); ?>">
						<?php
							$tab->render( $this->getPricingRuleInstance( $post ) );

							do_action( 'tiered_pricing_table/global_pricing/form/tab_end', $tab,
									$this->getPricingRuleInstance( $post ) );
						?>
					</div>
				<?php endforeach; ?>
			</section>
		</div>
		<?php
	}

	/**
	 * What the admin should know before editing: other rules have a higher priority for products and
	 * customers they share, so this rule is skipped there. The Rule status box lists them.
	 */
	protected function renderStatusHints( GlobalPricingRule $rule ) {
		// a suspended, expired or skipped rule is not applied anyway, so nothing overrides it
		if ( 'publish' !== get_post_status( $rule->getId() ) || ! $rule->canBeOverridden() ) {
			return;
		}

		$overriding = ( new RuleOverlaps() )->findOverriding( $rule, GlobalTieredPricingCPT::getGlobalRules( true, false ) );

		if ( ! $overriding ) {
			return;
		}

		$this->tabs[0]->renderHint(
			sprintf(
				/* translators: %d: number of rules */
				_n(
					'%d other rule has a higher priority and is applied instead of this rule for the products and customers they both match. See "Overridden by" in the Rule status box.',
					'%d other rules have a higher priority and are applied instead of this rule for the products and customers they both match. See "Overridden by" in the Rule status box.',
					count( $overriding ),
					'tier-pricing-table'
				),
				count( $overriding )
			),
			array( 'custom_class' => 'tpt-global-pricing-rule-hint--top-level tpt-global-pricing-rule-hint--warning' )
		);
	}

	/**
	 * Get pricing rule instance
	 *
	 * @param  WP_Post  $post
	 *
	 * @return GlobalPricingRule
	 */
	public function getPricingRuleInstance( WP_Post $post ): GlobalPricingRule {
		if ( empty( $this->pricingRuleInstance ) ) {
			$this->pricingRuleInstance = GlobalPricingRule::build( $post->ID );
		}

		return $this->pricingRuleInstance;
	}

	public function isNewRule(): bool {
		global $pagenow;

		return 'post-new.php' == $pagenow;
	}
}