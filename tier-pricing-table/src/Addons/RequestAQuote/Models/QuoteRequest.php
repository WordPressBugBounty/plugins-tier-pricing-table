<?php namespace TierPricingTable\Addons\RequestAQuote\Models;

use TierPricingTable\Addons\RequestAQuote\CPT\QuoteRequestCPT;
use TierPricingTable\Addons\RequestAQuote\QuoteFiles;

class QuoteRequest {
	
	protected int $id = 0;
	
	protected array $data = array(
		'status'           => 'unread',
		'title'            => '',
		'content'          => '',
		'date'             => '',
		'productId'        => 0,
		'quantity'         => 1,
		'price'            => '',
		'userId'           => 0,
		'customerEmail'    => '',
		'customFields'     => array(),
		'convertedOrderId' => 0,
		'items'            => array(),
	);
	
	protected array $metaData = array();
	
	/**
	 * Meta keys set through addMetaData() or updateMetaData() since the last save.
	 *
	 * @var array<string, true>
	 */
	protected array $metaChanges = array();
	
	protected array $changes = array();
	
	public function __construct( $quote = 0 ) {
		if ( is_numeric( $quote ) && $quote > 0 ) {
			$this->id = absint( $quote );
			$this->read();
		} elseif ( $quote instanceof \WP_Post ) {
			$this->id = absint( $quote->ID );
			$this->read();
		} elseif ( is_array( $quote ) ) {
			$this->setProps( $quote );
		}
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId( $id ) {
		$this->id = absint( $id );
	}
	
	public function read() {
		if ( ! $this->getId() ) {
			return;
		}
		
		$post = get_post( $this->getId() );
		if ( ! $post || $post->post_type !== QuoteRequestCPT::POST_TYPE ) {
			$this->setId( 0 );
			
			return;
		}
		
		$this->setProps( array(
			'status'  => $post->post_status,
			'title'   => $post->post_title,
			'content' => $post->post_content,
			'date'    => $post->post_date,
		) );
		
		$meta = get_post_meta( $this->getId() );
		if ( $meta && is_array( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$val = $value[0];
				if ( is_string( $val ) && is_serialized( $val ) ) {
					$val = maybe_unserialize( $val );
				}
				$this->metaData[ $key ] = $val;
			}
		}
		
		$customFields = $this->metaData['_custom_fields'] ?? array();
		if ( ! is_array( $customFields ) ) {
			$customFields = array();
		}
		
		// Hydrate known properties
		$this->setProps( array(
			'productId'        => $this->metaData['_product_id'] ?? 0,
			'quantity'         => $this->metaData['_quantity'] ?? 1,
			'price'            => $this->metaData['_price'] ?? '',
			'userId'           => $this->metaData['_user_id'] ?? 0,
			'customerEmail'    => $this->metaData['_email'] ?? '',
			'customFields'     => $customFields,
			'convertedOrderId' => $this->metaData['_converted_order_id'] ?? 0,
			'items'            => is_array( $this->metaData['_items'] ?? null ) ? $this->metaData['_items'] : array(),
		) );
		
		$this->applyChanges();
	}
	
	public function save() {
		$post_data = array();
		
		if ( array_key_exists( 'status', $this->changes ) ) {
			$post_data['post_status'] = $this->changes['status'];
		}
		if ( array_key_exists( 'title', $this->changes ) ) {
			$post_data['post_title'] = $this->changes['title'];
		}
		if ( array_key_exists( 'content', $this->changes ) ) {
			$post_data['post_content'] = $this->changes['content'];
		}
		if ( array_key_exists( 'date', $this->changes ) ) {
			$post_data['post_date'] = $this->changes['date'];
		}
		
		if ( ! $this->getId() ) {
			$post_data['post_type'] = QuoteRequestCPT::POST_TYPE;
			if ( empty( $post_data['post_title'] ) ) {
				$post_data['post_title'] = __( 'Quote Request', 'tier-pricing-table' );
			}
			if ( empty( $post_data['post_status'] ) ) {
				$post_data['post_status'] = 'unread';
			}
			
			$post_id = wp_insert_post( $post_data );
			if ( ! is_wp_error( $post_id ) ) {
				$this->setId( $post_id );
			} else {
				return false;
			}
		} elseif ( ! empty( $post_data ) ) {
			$post_data['ID'] = $this->getId();
			wp_update_post( $post_data );
		}
		
		// Save mapped properties to meta
		$meta_mapping = array(
			'productId'        => '_product_id',
			'quantity'         => '_quantity',
			'price'            => '_price',
			'userId'           => '_user_id',
			'customerEmail'    => '_email',
			'customFields'     => '_custom_fields',
			'convertedOrderId' => '_converted_order_id',
			'items'            => '_items',
		);
		
		foreach ( $meta_mapping as $prop => $meta_key ) {
			if ( array_key_exists( $prop, $this->changes ) ) {
				update_post_meta( $this->getId(), $meta_key, $this->changes[ $prop ] );
				
				$this->metaData[ $meta_key ] = $this->changes[ $prop ];
				unset( $this->metaChanges[ $meta_key ] );
			}
		}
		
		// Save direct meta changes. Only the keys changed since loading: writing back every value read earlier
		// would undo the property changes saved above and WordPress's own meta (edit lock).
		foreach ( array_keys( $this->metaChanges ) as $key ) {
			update_post_meta( $this->getId(), $key, $this->metaData[ $key ] );
		}
		
		$this->metaChanges = array();
		
		$this->applyChanges();
		
		return $this->getId();
	}
	
	public function getProp( $prop, $context = 'view' ) {
		return array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : ( $this->data[ $prop ] ?? null );
	}
	
	public function setProp( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data ) && $this->data[ $prop ] !== $value ) {
			$this->changes[ $prop ] = $value;
		}
	}
	
	public function setProps( $props ) {
		foreach ( $props as $prop => $value ) {
			$this->setProp( $prop, $value );
		}
	}
	
	public function getStatus() {
		return $this->getProp( 'status' );
	}
	
	public function setStatus( $value ) {
		$this->setProp( 'status', $value );
	}
	
	public function getTitle() {
		return $this->getProp( 'title' );
	}
	
	public function setTitle( $value ) {
		$this->setProp( 'title', $value );
	}
	
	public function getContent() {
		return $this->getProp( 'content' );
	}
	
	public function setContent( $value ) {
		$this->setProp( 'content', $value );
	}
	
	public function getDate() {
		return $this->getProp( 'date' );
	}
	
	public function setDate( $value ) {
		$this->setProp( 'date', $value );
	}
	
	public function getProductId(): int {
		return (int) $this->getProp( 'productId' );
	}
	
	public function setProductId( $value ) {
		$this->setProp( 'productId', (int) $value );
	}
	
	public function getQuantity(): int {
		return (int) $this->getProp( 'quantity' );
	}
	
	public function setQuantity( $value ) {
		$this->setProp( 'quantity', (int) $value );
	}
	
	public function getPrice() {
		return $this->getProp( 'price' );
	}
	
	public function setPrice( $value ) {
		$this->setProp( 'price', $value );
	}
	
	public function getUserId(): int {
		return (int) $this->getProp( 'userId' );
	}
	
	public function setUserId( $value ) {
		$this->setProp( 'userId', (int) $value );
	}
	
	public function applyChanges() {
		if ( ! empty( $this->changes ) ) {
			$this->data = array_merge( $this->data, $this->changes );
			$this->changes = array();
		}
	}
	
	public function getCustomerEmail() {
		return $this->getProp( 'customerEmail' );
	}
	
	public function setCustomerEmail( $value ) {
		$this->setProp( 'customerEmail', $value );
	}
	
	public function getCustomFields(): array {
		return (array) $this->getProp( 'customFields' );
	}
	
	public function setCustomFields( $value ) {
		$this->setProp( 'customFields', (array) $value );
	}
	
	public function getConvertedOrderId(): int {
		return (int) $this->getProp( 'convertedOrderId' );
	}
	
	public function setConvertedOrderId( $value ) {
		$this->setProp( 'convertedOrderId', (int) $value );
	}
	
	/**
	 * Whether the request came from the quote list (several products) rather than one product's form.
	 */
	public function isList(): bool {
		return ! empty( $this->getProp( 'items' ) );
	}

	/**
	 * The requested products, one line each: product_id (a product or a variation), name, sku, quantity and
	 * price (the unit price, '' when there is none). A request for one product is one line made from its
	 * product, quantity and price.
	 *
	 * @return array<int, array{product_id: int, name: string, sku: string, quantity: int, price: string}>
	 */
	public function getItems(): array {
		$items = $this->getProp( 'items' );

		if ( ! empty( $items ) && is_array( $items ) ) {
			$lines = array();

			foreach ( $items as $item ) {
				if ( is_array( $item ) && ! empty( $item['product_id'] ) ) {
					$lines[] = self::normalizeItem( $item );
				}
			}

			return $lines;
		}

		if ( ! $this->getProductId() ) {
			return array();
		}

		$product = wc_get_product( $this->getProductId() );

		return array(
			self::normalizeItem( array(
				'product_id' => $this->getProductId(),
				// a variation with its options: "Hoodie (Color: Blue, Logo: Yes)"
				'name'       => $product ? \TierPricingTable\Addons\RequestAQuote\QuoteList\QuoteListService::getDisplayName( $product ) : '',
				'sku'        => $product ? $product->get_sku() : '',
				'quantity'   => $this->getQuantity(),
				'price'      => $this->getPrice(),
			) ),
		);
	}

	/**
	 * Set the requested products (a quote list request). The first one is also kept as the request's product,
	 * so lists, filters and integrations that read one product keep working.
	 *
	 * @param  array  $items  Lines as getItems() returns them.
	 */
	public function setItems( array $items ) {
		$lines = array();

		foreach ( $items as $item ) {
			if ( is_array( $item ) && ! empty( $item['product_id'] ) ) {
				$lines[] = self::normalizeItem( $item );
			}
		}

		$this->setProp( 'items', $lines );

		if ( $lines ) {
			$this->setProductId( $lines[0]['product_id'] );
			$this->setQuantity( array_sum( wp_list_pluck( $lines, 'quantity' ) ) );
			$this->setPrice( '' );
		}
	}

	/**
	 * What the request is for, in a few words: the product's name, or the first product's name and how many more.
	 */
	public function getSummaryName(): string {
		$items = $this->getItems();

		if ( ! $items ) {
			return '';
		}

		$first = '' !== $items[0]['name'] ? $items[0]['name'] : (string) get_the_title( $items[0]['product_id'] );
		$more  = count( $items ) - 1;

		// translators: 1: the first product's name, 2: how many other products.
		return $more ? sprintf( _n( '%1$s and %2$d more product', '%1$s and %2$d more products', $more, 'tier-pricing-table' ), $first, $more ) : $first;
	}

	/**
	 * The sum of every priced line, or null when a line has no price.
	 */
	public function getItemsTotal(): ?float {
		$total = 0.0;

		foreach ( $this->getItems() as $item ) {
			if ( ! is_numeric( $item['price'] ) ) {
				return null;
			}

			$total += (float) $item['price'] * $item['quantity'];
		}

		return $total;
	}

	/**
	 * Meta key with one row per product (and parent product) of a list request, for filtering by product.
	 */
	public const ITEM_PRODUCT_META = '_item_product_id';

	/**
	 * The products and parent products of the lines, each once.
	 *
	 * @param  array  $items
	 *
	 * @return int[]
	 */
	public static function getItemProductIds( array $items ): array {
		$ids = array();

		foreach ( $items as $item ) {
			$product = ! empty( $item['product_id'] ) ? wc_get_product( (int) $item['product_id'] ) : null;

			if ( $product ) {
				$ids[] = $product->get_id();

				if ( $product->get_parent_id() ) {
					$ids[] = $product->get_parent_id();
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	protected static function normalizeItem( array $item ): array {
		$price = $item['price'] ?? '';

		return array(
			'product_id' => (int) $item['product_id'],
			'name'       => sanitize_text_field( (string) ( $item['name'] ?? '' ) ),
			'sku'        => sanitize_text_field( (string) ( $item['sku'] ?? '' ) ),
			'quantity'   => max( 1, (int) ( $item['quantity'] ?? 1 ) ),
			'price'      => is_numeric( $price ) && (float) $price >= 0 ? (string) $price : '',
		);
	}

	public function getMeta( $key ) {
		return $this->metaData[ $key ] ?? '';
	}
	
	public function addMetaData( $key, $value, $unique = false ) {
		if ( $unique && isset( $this->metaData[ $key ] ) ) {
			return;
		}
		$this->metaData[ $key ]    = $value;
		$this->metaChanges[ $key ] = true;
	}
	
	public function updateMetaData( $key, $value ) {
		$this->metaData[ $key ]    = $value;
		$this->metaChanges[ $key ] = true;
	}
	
	public function deleteMetaData( $key ) {
		unset( $this->metaData[ $key ], $this->metaChanges[ $key ] );
		if ( $this->getId() ) {
			delete_post_meta( $this->getId(), $key );
		}
	}
	
	public function getMetaData(): array {
		return $this->metaData;
	}

	/**
	 * The customer's answers as label and value pairs, for e-mails and order notes. A file shows its name;
	 * with $fileLinks it also has 'url', the store manager's download link (it needs a login).
	 *
	 * @param  bool  $fileLinks
	 *
	 * @return array<int, array{label: string, value: string, url: string}>
	 */
	public function getSubmittedFields( bool $fileLinks = false ): array {
		$fields = array();

		foreach ( $this->getCustomFields() as $key => $fieldData ) {
			if ( ! is_array( $fieldData ) ) {
				$fieldData = array( 'value' => $fieldData );
			}

			$label = ! empty( $fieldData['label'] ) ? (string) $fieldData['label'] : ucfirst( str_replace( '_', ' ', (string) $key ) );
			$value = isset( $fieldData['value'] ) && is_scalar( $fieldData['value'] ) ? (string) $fieldData['value'] : '';
			$url   = '';

			if ( 'file' === ( $fieldData['type'] ?? '' ) && '' !== $value ) {
				$value = QuoteFiles::getFileName( $fieldData );

				if ( $fileLinks && $this->getId() ) {
					$url = QuoteFiles::getDownloadUrl( $this->getId(), (string) $key );
				}
			}

			$fields[] = array(
				'label' => $label,
				'value' => $value,
				'url'   => $url,
			);
		}

		return $fields;
	}
	
		public function getCustomerName() {
		$fields = $this->getCustomFields();
		
		// Helper to extract value if it's an array
		$getValue = function($field) {
			if ( is_array( $field ) && isset( $field['value'] ) ) {
				return $field['value'];
			}
			return $field;
		};

		if ( isset( $fields['name'] ) ) {
			return $getValue( $fields['name'] );
		}
		if ( isset( $fields['first_name'] ) ) {
			return $getValue( $fields['first_name'] );
		}
		return $this->getMeta( '_name' ) ?: $this->getMeta( '_first_name' );
	}
}