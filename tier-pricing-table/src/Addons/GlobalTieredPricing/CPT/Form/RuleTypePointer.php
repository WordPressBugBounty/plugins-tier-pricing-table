<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\GlobalTieredPricing\CPT\StatusBox;

/**
 * A guide tip on the first pricing rule: a WordPress pointer at the rule type picker that names the two
 * types and what each does. Shown on a new rule while the store has no rules yet, until the user closes
 * it; the dismissal is WordPress's own, stored per user.
 */
class RuleTypePointer {

	const ID = 'tpt_rule_type';

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * @param  string  $hook
	 */
	public function enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( 'post-new.php' !== $hook || ! $screen || GlobalTieredPricingCPT::SLUG !== $screen->post_type || ! $this->shouldShow() ) {
			return;
		}

		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wp_add_inline_script( 'wp-pointer', $this->script() );
	}

	/**
	 * Forgets every user's dismissal of the tip, so it shows again (the settings reset calls this). Only
	 * this plugin's pointer leaves the list; the users' other dismissed pointers stay.
	 */
	public static function resetDismissals() {
		$userIds = get_users( array(
			'meta_key'     => 'dismissed_wp_pointers', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'   => self::ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => 'LIKE',
			'fields'       => 'ID',
			'number'       => -1,
		) );

		foreach ( $userIds as $userId ) {
			$dismissed = array_filter( explode( ',', (string) get_user_meta( $userId, 'dismissed_wp_pointers', true ) ) );
			$kept      = array_values( array_diff( $dismissed, array( self::ID ) ) );

			if ( $kept ) {
				update_user_meta( $userId, 'dismissed_wp_pointers', implode( ',', $kept ) );
			} else {
				delete_user_meta( $userId, 'dismissed_wp_pointers' );
			}
		}
	}

	/**
	 * The first rule of the store, for a user who has not closed the tip.
	 */
	protected function shouldShow(): bool {
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$show      = ! in_array( self::ID, $dismissed, true ) && ! GlobalTieredPricingCPT::hasAnyRules();

		return (bool) apply_filters( 'tiered_pricing_table/global_pricing/show_rule_type_pointer', $show );
	}

	/**
	 * The pointer: the two types with their hints, opened at the rule type section of the status box.
	 */
	protected function script(): string {
		$content = '<h3>' . esc_html__( 'Start with the rule type', 'tier-pricing-table' ) . '</h3>';

		foreach ( StatusBox::kindOptions() as $option ) {
			$content .= '<p><strong>' . esc_html( $option['label'] ) . '</strong><br>' . esc_html( $option['hint'] ) . '</p>';
		}

		$content .= '<p>' . esc_html__( 'The tabs of the form follow the type you pick.', 'tier-pricing-table' ) . '</p>';

		return 'jQuery( function ( $ ) {'
			. 'var target = $( "#tpt-rule-status-box .tpt-rule-box__section--kind" );'
			. 'if ( ! target.length || ! $.fn.pointer ) { return; }'
			. 'target.pointer( {'
			. 'content: ' . wp_json_encode( $content ) . ','
			. 'position: { edge: "right", align: "top" },'
			. 'pointerClass: "wp-pointer tpt-rule-type-pointer",'
			. 'close: function () { $.post( window.ajaxurl, { action: "dismiss-wp-pointer", pointer: ' . wp_json_encode( self::ID ) . ' } ); }'
			. '} ).pointer( "open" );'
			. '} );';
	}
}
