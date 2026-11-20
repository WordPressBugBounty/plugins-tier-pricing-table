<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\Tools\PricingTest\PricingTester;
use WP_Post;

/**
 * Links to the Pricing test utility (Tools add-on): on the pricing rules list a button next to "Add Pricing
 * Rule" and a "Test" row action; in the rule editor a "Test this rule" button next to "Add Pricing Rule".
 */
class PricingTestLinks {

	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'addRowAction' ), 20, 2 );
		add_action( 'admin_footer-edit.php', array( $this, 'addListButton' ) );
		add_action( 'admin_footer-post.php', array( $this, 'addEditorButton' ) );
	}

	public function addRowAction( $actions, WP_Post $post ) {
		if ( GlobalTieredPricingCPT::SLUG !== $post->post_type || ! PricingTester::isAvailable() ) {
			return $actions;
		}

		$rule = GlobalPricingRule::build( $post->ID );

		// a skipped rule changes nothing, so there is nothing to test
		if ( 'publish' === $post->post_status && 'skipped' === $rule->getEffectiveStatus() ) {
			return $actions;
		}

		// a new tab: the test is a side trip from the list or the editor, and the editor may hold unsaved changes
		$actions['tpt_pricing_test'] = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( PricingTester::getUrl( $post->ID, $rule->isOrderRule() ? 'cart' : 'product' ) ), esc_html__( 'Test', 'tier-pricing-table' ) );

		return $actions;
	}

	public function addListButton() {
		$screen = get_current_screen();

		// nothing to test on the blank state, before the first rule
		if ( ! $screen || GlobalTieredPricingCPT::SLUG !== $screen->post_type || ! PricingTester::isAvailable() || ! GlobalTieredPricingCPT::hasAnyRules() ) {
			return;
		}
		?>
		<script>
			jQuery( function ( $ ) {
				$( '.wrap .page-title-action' ).first().after( ' <a href="<?php echo esc_url( PricingTester::getUrl() ); ?>" class="page-title-action"><?php echo esc_js( __( 'Pricing test', 'tier-pricing-table' ) ); ?></a>' );
			} );
		</script>
		<?php
	}

	/**
	 * The rule editor: "Test this rule" next to "Add Pricing Rule", for a saved rule that changes something.
	 * It opens in a new tab and tests the saved rule, so changes not yet updated are not part of the test.
	 */
	public function addEditorButton() {
		$screen = get_current_screen();
		$post   = get_post();

		if ( ! $screen || GlobalTieredPricingCPT::SLUG !== $screen->post_type || ! $post || 'auto-draft' === $post->post_status || ! PricingTester::isAvailable() ) {
			return;
		}

		$rule = GlobalPricingRule::build( $post->ID );

		// a skipped rule changes nothing, so there is nothing to test
		if ( 'publish' === $post->post_status && 'skipped' === $rule->getEffectiveStatus() ) {
			return;
		}

		$button = sprintf(
			' <a href="%s" class="page-title-action" target="_blank" rel="noopener" title="%s">%s</a>',
			esc_url( PricingTester::getUrl( $post->ID, $rule->isOrderRule() ? 'cart' : 'product' ) ),
			esc_attr__( 'Tests the saved rule: update the rule first to test your latest changes.', 'tier-pricing-table' ),
			esc_html__( 'Test this rule', 'tier-pricing-table' )
		);
		?>
		<script>
			jQuery( function ( $ ) {
				$( '.wrap .page-title-action' ).first().after( <?php echo wp_json_encode( $button ); ?> );
			} );
		</script>
		<?php
	}
}
