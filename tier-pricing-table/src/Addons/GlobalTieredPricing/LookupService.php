<?php namespace TierPricingTable\Addons\GlobalTieredPricing;

use Exception;
use TierPricingTable\Core\ServiceContainerTrait;
use WC_Customer;
use WP_Term;
use WP_User_Query;

class LookupService {

	use ServiceContainerTrait;

	const CATEGORIES_SEARCH_ACTION = 'woocommerce_json_search_tpt_categories';
	const TAGS_SEARCH_ACTION       = 'woocommerce_json_search_tpt_tags';
	const BRANDS_SEARCH_ACTION     = 'woocommerce_json_search_tpt_brands';
	const ATTRIBUTES_SEARCH_ACTION = 'woocommerce_json_search_tpt_attributes';
	const CUSTOMERS_SEARCH_ACTION  = 'woocommerce_json_search_tpt_customers';

	public function __construct() {
		add_action( 'wp_ajax_' . self::CATEGORIES_SEARCH_ACTION, array( $this, 'categoriesSearchHandler' ) );
		add_action( 'wp_ajax_' . self::TAGS_SEARCH_ACTION, array( $this, 'tagsSearchHandler' ) );
		add_action( 'wp_ajax_' . self::BRANDS_SEARCH_ACTION, array( $this, 'brandsSearchHandler' ) );
		add_action( 'wp_ajax_' . self::ATTRIBUTES_SEARCH_ACTION, array( $this, 'attributesSearchHandler' ) );
		add_action( 'wp_ajax_' . self::CUSTOMERS_SEARCH_ACTION, array( $this, 'customersSearchHandler' ) );
	}

	public function customersSearchHandler() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array() );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : false;

		if ( $term ) {
			$wp_user_query = new WP_User_Query(
				array(
					'search'         => '*' . $term . '*',
					'search_columns' => array(
						'user_login',
						'user_nicename',
						'user_email',
					),
					'fields'         => 'ID',
				) );

			$users = $wp_user_query->get_results();

			if ( $users ) {
				$_users = array();

				foreach ( $users as $userId ) {
					try {
						$customer = new WC_Customer( $userId );
					} catch ( Exception $e ) {
						continue;
					}

					if ( $customer instanceof WC_Customer ) {
						$_users[ $userId ] = Formatter::formatCustomerString( $customer );
					}

				}

				wp_send_json( $_users );
			}
		}

		wp_send_json( array() );
	}

	public function categoriesSearchHandler() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array() );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : false;

		if ( $term ) {
			$args = array(
				'taxonomy'   => array( 'product_cat' ),
				'orderby'    => 'id',
				'order'      => 'ASC',
				'limit'      => 5,
				'hide_empty' => false,
				'fields'     => 'all',
				'name__like' => $term
			);

			$terms = get_terms( $args );

			if ( $terms ) {
				$_terms = array();

				foreach ( $terms as $term ) {
					if ( $term instanceof WP_Term ) {
						$_terms[ $term->term_id ] = self::getCategoryLabel( $term );
					}
				}

				wp_send_json( $_terms );
			}
		}

		wp_send_json( array() );
	}

	public function tagsSearchHandler() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array() );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : false;

		if ( $term ) {
			$args = array(
				'taxonomy'   => array( 'product_tag' ),
				'orderby'    => 'id',
				'order'      => 'ASC',
				'limit'      => 5,
				'hide_empty' => false,
				'fields'     => 'all',
				'name__like' => $term
			);

			$terms = get_terms( $args );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$_terms = array();

				foreach ( $terms as $term ) {
					if ( $term instanceof WP_Term ) {
						$_terms[ $term->term_id ] = $term->name;
					}
				}

				wp_send_json( $_terms );
			}
		}

		wp_send_json( array() );
	}

	public function brandsSearchHandler() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array() );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : false;

		if ( $term ) {
			if ( taxonomy_exists( 'product_brand' ) ) {
				$args = array(
					'taxonomy'   => array( 'product_brand' ),
					'orderby'    => 'id',
					'order'      => 'ASC',
					'limit'      => 5,
					'hide_empty' => false,
					'fields'     => 'all',
					'name__like' => $term
				);

				$terms = get_terms( $args );

				if ( $terms && ! is_wp_error( $terms ) ) {
					$_terms = array();

					foreach ( $terms as $term ) {
						if ( $term instanceof WP_Term ) {
							$_terms[ $term->term_id ] = $term->name;
						}
					}

					wp_send_json( $_terms );
				}
			}
		}

		wp_send_json( array() );
	}

	/**
	 * Values of the global product attributes, labelled "Attribute: value". Searching for an attribute's
	 * name lists its values too, so "size" offers every size.
	 */
	public function attributesSearchHandler() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( array() );
		}

		$search     = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$taxonomies = array_values( array_filter( wc_get_attribute_taxonomy_names(), 'taxonomy_exists' ) );

		if ( '' === $search || empty( $taxonomies ) ) {
			wp_send_json( array() );
		}

		$found = get_terms( array(
			'taxonomy'   => $taxonomies,
			'hide_empty' => false,
			'name__like' => $search,
			'number'     => 30,
		) );
		$found = is_wp_error( $found ) ? array() : (array) $found;

		$byName = array_values( array_filter( $taxonomies, function ( $taxonomy ) use ( $search ) {
			return false !== stripos( wc_attribute_label( $taxonomy ), $search );
		} ) );

		if ( $byName ) {
			$values = get_terms( array(
				'taxonomy'   => $byName,
				'hide_empty' => false,
				'number'     => 50,
			) );
			$found  = array_merge( $found, is_wp_error( $values ) ? array() : (array) $values );
		}

		$options = array();

		foreach ( $found as $term ) {
			if ( $term instanceof WP_Term ) {
				$options[ $term->term_id ] = self::getAttributeTermLabel( $term );
			}
		}

		wp_send_json( $options );
	}

	public static function getAttributeTermLabel( WP_Term $term ): string {
		return wc_attribute_label( $term->taxonomy ) . ': ' . $term->name;
	}

	public static function getCategoryLabel( WP_Term $category ): string {
		$parentTermName = '';

		if ( $category->parent ) {
			$parentTerm = get_term( $category->parent );

			if ( $parentTerm ) {
				$parentTermName = ' (' . $parentTerm->name . ')';
			}
		}

		return $category->name . $parentTermName;
	}
}
