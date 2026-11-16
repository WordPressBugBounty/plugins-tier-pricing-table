<?php namespace TierPricingTable\Addons\RequestAQuote\API;

use WP_REST_Controller;
use WP_REST_Server;

class SettingsEndpoint extends WP_REST_Controller {

	protected $namespace = 'tier-pricing-table/v1';
	protected $restBase = 'quote-settings';

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->restBase, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );
	}

	public function permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	public function get_items( $request ) {
		$settings = get_option( 'tier_pricing_table_quote_global_settings', array() );
		return rest_ensure_response( $settings );
	}

	public function create_item( $request ) {
		$settings = $request->get_param( 'settings' );

		if ( ! is_array( $settings ) ) {
			return new \WP_Error( 'invalid_data', 'Settings must be an array', array( 'status' => 400 ) );
		}

		// text cleaned (keys pasted with spaces or line breaks would fail every check), other values keep their type
		$settings = \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm::sanitizeUnknown( $settings );

		// the reCAPTCHA score: a number from 0 to 1 with one decimal, or empty for the default
		if ( array_key_exists( 'recaptcha_score_threshold', $settings ) ) {
			$threshold = $settings['recaptcha_score_threshold'];

			$settings['recaptcha_score_threshold'] = is_numeric( $threshold ) ? (string) round( max( 0.0, min( 1.0, (float) $threshold ) ), 1 ) : '';
		}

		// Ensure we always have an array
		$current_settings = get_option( 'tier_pricing_table_quote_global_settings', array() );
		if ( ! is_array( $current_settings ) ) {
			$current_settings = array();
		}

		$new_settings = array_merge( $current_settings, $settings );
		update_option( 'tier_pricing_table_quote_global_settings', $new_settings );

		return rest_ensure_response( $new_settings );
	}
}
