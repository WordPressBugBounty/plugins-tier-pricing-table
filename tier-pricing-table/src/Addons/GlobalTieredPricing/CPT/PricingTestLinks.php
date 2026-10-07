<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT;

use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\Tools\PricingTest\PricingTester;
use WP_Post;

/**
 * Links from the pricing rules list to the Pricing test utility (Tools add-on): a button next to
 * "Add Pricing Rule" and a "Test" row action. The rule editor links from its Rule status box.
 */
class PricingTestLinks {

	public function __construct() {
		add_filter( 'post_row_actions', array( $this, 'addRowAction' ), 20, 2 );
		add_action( 'admin_footer-edit.php', array( $this, 'addListButton' ) );
	}

	public function addRowAction( $actions, WP_Post $post ) {
		if ( GlobalTieredPricingCPT::SLUG !== $post->post_type || ! PricingTester::isAvailable() ) {
			return $actions;
		}

		// a skipped rule changes nothing, so there is nothing to test
		if ( 'publish' === $post->post_status && 'skipped' === GlobalPricingRule::build( $post->ID )->getEffectiveStatus() ) {
			return $actions;
		}

		// a new tab: the test is a side trip from the list or the editor, and the editor may hold unsaved changes
		$actions['tpt_pricing_test'] = sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( PricingTester::getUrl( $post->ID ) ), esc_html__( 'Test', 'tier-pricing-table' ) );

		return $actions;
	}

	public function addListButton() {
		$screen = get_current_screen();

		if ( ! $screen || GlobalTieredPricingCPT::SLUG !== $screen->post_type || ! PricingTester::isAvailable() ) {
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
}
