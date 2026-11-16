<?php namespace TierPricingTable\Addons\RequestAQuote\API;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\GlobalTieredPricingCPT;
use TierPricingTable\Addons\NonLoggedInUsers\Roles;
use TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;

class FormsEndpoint extends WP_REST_Controller {

	protected $namespace = 'tier-pricing-table/v1';
	protected $restBase = 'quote-forms';

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

		// how many products, variations and pricing rules choose each form themselves
		register_rest_route( $this->namespace, '/' . $this->restBase . '/usage', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'usage' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );

		// the products, categories and customer roles a form can show on, for the pickers in the form builder
		register_rest_route( $this->namespace, '/' . $this->restBase . '/lookup', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'lookup' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'type'    => array(
						'type'     => 'string',
						'enum'     => array( 'products', 'categories', 'roles' ),
						'required' => true,
					),
					'search'  => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'include' => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		) );
	}

	/**
	 * For every form, the number of products, variations and global pricing rules that choose it themselves
	 * (for a role too). A form nothing chooses, and that shows on no products by itself, is shown nowhere.
	 *
	 * @return \WP_REST_Response Form ID => count.
	 */
	public function usage() {
		$usage = array();

		foreach ( RequestQuoteForm::getAll() as $form ) {
			$query = new \WP_Query( array(
				'post_type'        => array( 'product', 'product_variation', GlobalTieredPricingCPT::SLUG ),
				'post_status'      => array( 'publish', 'private', 'draft', 'pending', 'future' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				// the product's own key and the per-role keys end the same way
				'meta_query'       => array(
					array(
						'key'         => 'tier_pricing_table_quote_form_id',
						'compare_key' => 'LIKE',
						'value'       => $form->getId(),
					),
				),
			) );

			$usage[ $form->getId() ] = (int) $query->found_posts;
		}

		return rest_ensure_response( $usage );
	}

	/**
	 * Matching items as [{ id, label }]: found by a search, or the given ones (to show what a form has saved).
	 *
	 * @param  WP_REST_Request  $request
	 *
	 * @return \WP_REST_Response
	 */
	public function lookup( WP_REST_Request $request ) {
		$search  = trim( (string) $request->get_param( 'search' ) );
		$include = array_values( array_filter( array_map( 'sanitize_key', explode( ',', (string) $request->get_param( 'include' ) ) ) ) );

		switch ( $request->get_param( 'type' ) ) {
			case 'roles':
				$items = array();

				foreach ( Roles::getOptions() as $role => $label ) {
					if ( ( $include && ! in_array( $role, $include, true ) ) || ( '' !== $search && false === stripos( $label, $search ) ) ) {
						continue;
					}

					$items[] = array( 'id' => $role, 'label' => $label );
				}

				return rest_ensure_response( $items );

			case 'categories':
				$args = array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'orderby'    => 'name',
					'number'     => 30,
				);

				if ( $include ) {
					$args['include'] = array_map( 'absint', $include );
					$args['number']  = 0;
				} elseif ( '' !== $search ) {
					$args['search'] = $search;
				}

				$terms = get_terms( $args );

				$items = array();

				foreach ( is_array( $terms ) ? $terms : array() as $term ) {
					$path = array();

					foreach ( array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) as $ancestorId ) {
						$ancestor = get_term( $ancestorId, 'product_cat' );
						$path[]   = $ancestor instanceof \WP_Term ? $ancestor->name : '';
					}

					$path[]  = $term->name;
					$items[] = array( 'id' => (int) $term->term_id, 'label' => html_entity_decode( implode( ' › ', array_filter( $path ) ) ) );
				}

				return rest_ensure_response( $items );

			default:
				if ( $include ) {
					$ids = array_map( 'absint', $include );
				} elseif ( '' !== $search ) {
					$ids = \WC_Data_Store::load( 'product' )->search_products( $search, '', false, false, 20 );
				} else {
					$ids = wc_get_products( array( 'limit' => 20, 'orderby' => 'title', 'order' => 'ASC', 'return' => 'ids' ) );
				}

				$items = array();

				foreach ( array_filter( array_map( 'absint', (array) $ids ) ) as $id ) {
					$product = wc_get_product( $id );

					if ( $product && ! $product->get_parent_id() ) {
						$items[] = array( 'id' => (int) $id, 'label' => html_entity_decode( wp_strip_all_tags( $product->get_formatted_name() ) ) );
					}
				}

				return rest_ensure_response( $items );
		}
	}

	public function permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

	public function get_items( $request ) {
		$forms = get_option( RequestQuoteForm::OPTION, array() );
		return rest_ensure_response( $forms );
	}

	public function create_item( $request ) {
		$forms = $request->get_param( 'forms' );

		if ( ! is_array( $forms ) ) {
			return new \WP_Error( 'invalid_data', 'Forms must be an array', array( 'status' => 400 ) );
		}

		// Shop managers edit the forms; what they enter is shown to every visitor, so it is cleaned first
		$forms = array_values( array_map( array( RequestQuoteForm::class, 'sanitize' ), array_filter( $forms, 'is_array' ) ) );

		update_option( RequestQuoteForm::OPTION, $forms );

		return rest_ensure_response( $forms );
	}
}
