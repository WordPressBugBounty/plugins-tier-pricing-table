<?php namespace TierPricingTable\Addons\RequestAQuote\CPT;

use TierPricingTable\Addons\RequestAQuote\Models\QuoteRequest;
use TierPricingTable\Addons\RequestAQuote\QuoteFiles;
use TierPricingTable\TierPricingTablePlugin;

class QuoteRequestAdmin {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'savePost' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'adminAssets' ) );

		add_filter( 'manage_' . QuoteRequestCPT::POST_TYPE . '_posts_columns', array( $this, 'setCustomColumns' ) );
		add_action( 'manage_' . QuoteRequestCPT::POST_TYPE . '_posts_custom_column', array( $this, 'customColumnData' ),
				10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'addProductsFilter' ) );
		add_action( 'pre_get_posts', array( $this, 'filterByProduct' ) );

		add_filter( 'bulk_actions-edit-' . QuoteRequestCPT::POST_TYPE, array( $this, 'registerBulkActions' ) );
		add_filter( 'handle_bulk_actions-edit-' . QuoteRequestCPT::POST_TYPE, array( $this, 'processBulkActions' ), 10,
				3 );
		add_action( 'admin_notices', array( $this, 'bulkActionNotices' ) );

		// the request screen: opening a request reads it, and it starts with the request's number
		add_action( 'load-post.php', array( $this, 'markReadOnOpen' ) );
		add_action( 'edit_form_top', array( $this, 'renderHeading' ) );

		// the list: no Quick Edit, the number first, sorting, search by number
		add_filter( 'post_row_actions', array( $this, 'removeQuickEdit' ), 10, 2 );
		add_filter( 'list_table_primary_column', array( $this, 'setPrimaryColumn' ), 10, 2 );
		add_filter( 'manage_edit-' . QuoteRequestCPT::POST_TYPE . '_sortable_columns', array( $this, 'setSortableColumns' ) );
		add_action( 'pre_get_posts', array( $this, 'searchByNumber' ) );
		add_action( 'admin_notices', array( $this, 'conversionNotices' ) );

		QuoteFiles::registerDownloadHandler();
	}

	public function adminAssets() {
		global $post_type;
		if ( QuoteRequestCPT::POST_TYPE === $post_type ) {
			wp_enqueue_style( 'tpt-quote-admin', plugins_url( '../assets/css/admin-quote-request.css', __FILE__ ),
					array(), TierPricingTablePlugin::VERSION );

			// live totals while prices are typed, and a question before an order is created
			wp_enqueue_script( 'tpt-quote-admin', plugins_url( '../assets/js/admin-quote-request.js', __FILE__ ),
					array(), TierPricingTablePlugin::VERSION, true );
			wp_localize_script( 'tpt-quote-admin', 'tptQuoteAdmin', array(
					'currency'       => array(
							'symbol'            => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
							'format'            => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
							'decimals'          => wc_get_price_decimals(),
							'decimalSeparator'  => wc_get_price_decimal_separator(),
							'thousandSeparator' => wc_get_price_thousand_separator(),
					),
					'confirmConvert' => __( 'Create a WooCommerce order from this request? Unsaved changes to quantities and prices are saved first.', 'tier-pricing-table' ),
			) );
		}
	}

	public function addMetaBoxes( $postType = '', $post = null ) {
		$isList = $post instanceof \WP_Post && QuoteRequestCPT::POST_TYPE === $post->post_type && ( new QuoteRequest( $post ) )->isList();

		add_meta_box( 'tier_pricing_table_quote_details', __( 'Customer Request', 'tier-pricing-table' ),
				array( $this, 'renderDetailsMetaBox' ), QuoteRequestCPT::POST_TYPE, 'normal', 'high' );

		add_meta_box( 'tier_pricing_table_quote_product', $isList ? __( 'Products', 'tier-pricing-table' ) : __( 'Product', 'tier-pricing-table' ),
				array( $this, 'renderProductMetaBox' ), QuoteRequestCPT::POST_TYPE, 'normal', 'high' );

		add_meta_box( 'tier_pricing_table_quote_customer', __( 'Customer', 'tier-pricing-table' ), array( $this, 'renderCustomerMetaBox' ),
				QuoteRequestCPT::POST_TYPE, 'side', 'high' );

		add_meta_box( 'tier_pricing_table_quote_notes', __( 'Notes', 'tier-pricing-table' ), array( $this, 'renderNotesMetaBox' ),
				QuoteRequestCPT::POST_TYPE, 'side', 'default' );

		add_meta_box( 'tier_pricing_table_quote_actions', __( 'Quote Actions', 'tier-pricing-table' ),
				array( $this, 'renderActionsMetaBox' ), QuoteRequestCPT::POST_TYPE, 'side', 'high' );
	}

	/**
	 * Opening a new request marks it as read, so the unread count goes down by itself. "Mark as Unread" in the
	 * list brings it back.
	 */
	public function markReadOnOpen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- opening a screen, not a form submission
		$postId = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $postId || QuoteRequestCPT::POST_TYPE !== get_post_type( $postId ) || 'unread' !== get_post_status( $postId ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$quote = new QuoteRequest( $postId );
		$quote->setStatus( 'read' );
		$quote->save();
	}

	/**
	 * The top of the request screen: the number the customer was given, what they asked for and when.
	 *
	 * @param  \WP_Post  $post
	 */
	public function renderHeading( $post ) {
		if ( ! $post instanceof \WP_Post || QuoteRequestCPT::POST_TYPE !== $post->post_type ) {
			return;
		}

		$quote   = new QuoteRequest( $post );
		$product = $quote->isList() ? null : wc_get_product( $quote->getProductId() );
		$subject = $quote->isList() ? $quote->getSummaryName() : ( $product ? $product->get_name() : '' );
		?>
		<div class="tpt-quote-heading">
			<h2>
				<?php
					/* translators: %s: request number */
					echo esc_html( sprintf( __( 'Request #%s', 'tier-pricing-table' ), $post->ID ) );
				?>
			</h2>
			<p>
				<?php if ( '' !== (string) $subject ) : ?>
					<?php echo esc_html( $subject ); ?>
					&middot;
				<?php endif; ?>
				<?php
					/* translators: 1: date, 2: time */
					echo esc_html( sprintf( __( 'received %1$s at %2$s', 'tier-pricing-table' ), get_the_date( '', $post ), get_the_time( '', $post ) ) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Notes only the store sees.
	 */
	public function renderNotesMetaBox( $post ) {
		$quote = new QuoteRequest( $post );
		?>
		<label class="screen-reader-text" for="tier_pricing_table_quote_note"><?php esc_html_e( 'Notes', 'tier-pricing-table' ); ?></label>
		<textarea name="tier_pricing_table_quote_note" id="tier_pricing_table_quote_note" rows="5" style="width: 100%;"
		          placeholder="<?php esc_attr_e( 'For you and your team: what was agreed, when to follow up.', 'tier-pricing-table' ); ?>"><?php echo esc_textarea( (string) $quote->getMeta( '_internal_note' ) ); ?></textarea>
		<p class="description" style="margin: 6px 0 0;"><?php esc_html_e( 'The customer never sees these notes.', 'tier-pricing-table' ); ?></p>
		<?php
	}

	public function removeQuickEdit( $actions, $post ) {
		if ( $post instanceof \WP_Post && QuoteRequestCPT::POST_TYPE === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );

			if ( isset( $actions['edit'] ) ) {
				$actions['edit'] = '<a href="' . esc_url( (string) get_edit_post_link( $post->ID ) ) . '">' . esc_html__( 'Open', 'tier-pricing-table' ) . '</a>';
			}
		}

		return $actions;
	}

	public function setPrimaryColumn( $default, $screenId ) {
		return 'edit-' . QuoteRequestCPT::POST_TYPE === $screenId ? 'request' : $default;
	}

	public function setSortableColumns( $columns ) {
		$columns['request']  = 'ID';
		$columns['received'] = 'date';

		return $columns;
	}

	/**
	 * Searching "#123" or "123" in the list finds that request.
	 *
	 * @param  \WP_Query  $query
	 */
	public function searchByNumber( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || QuoteRequestCPT::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$search = trim( (string) $query->get( 's' ) );

		if ( preg_match( '/^#?(\d+)$/', $search, $match ) && QuoteRequestCPT::POST_TYPE === get_post_type( (int) $match[1] ) ) {
			$query->set( 'p', (int) $match[1] );
			$query->set( 's', '' );
		}
	}

	public function renderDetailsMetaBox( $post ) {
		$quote        = new QuoteRequest( $post );
		$customFields = $quote->getCustomFields();

		?>
		<div class="tpt-quote-grid">
			<?php
				foreach ( $customFields as $key => $fieldData ) {
					if ( ! is_array( $fieldData ) ) {
						$fieldData = array(
								'label' => ucfirst( str_replace( '_', ' ', $key ) ),
								'value' => $fieldData,
						);
					}
					$label = isset( $fieldData['label'] ) && ! empty( $fieldData['label'] ) ? $fieldData['label'] : ucfirst( str_replace( '_',
							' ', $key ) );
					$value = isset( $fieldData['value'] ) ? $fieldData['value'] : '';

					?>
					<div class="tpt-quote-field">
						<label><?php echo esc_html( $label ); ?></label>
						<?php
							if ( isset( $fieldData['type'] ) && $fieldData['type'] === 'file' && ! empty( $value ) ) :
								// files are private: store managers download them through WordPress
								$fileName = QuoteFiles::getFileName( $fieldData );
								?>
								<span>
								<?php if ( QuoteFiles::getStoredPath( $fieldData ) ) : ?>
									<a href="<?php echo esc_url( QuoteFiles::getDownloadUrl( (int) $post->ID, (string) $key ) ); ?>"
									   style="color: #2271b1; font-weight: 500; text-decoration: underline;"><?php echo esc_html( $fileName ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $fileName ); ?>
								<?php endif; ?>
								</span>
							<?php else : ?>
								<span><?php echo esc_html( $value ); ?></span>
							<?php endif; ?>
					</div>
					<?php
				}
			?>
		</div>
		<?php
	}

	public function renderProductMetaBox( $post ) {
		$quote     = new QuoteRequest( $post );

		// a quote list request: every product with its own quantity and unit price
		if ( $quote->isList() ) {
			$this->renderItems( $quote );
			$this->renderConversion( $quote );

			return;
		}

		$productId = $quote->getProductId();
		$quantity  = $quote->getQuantity();
		$price     = $quote->getPrice();

		$product = wc_get_product( $productId );

		if ( ! $product ) {
			?>
			<p><?php esc_html_e( 'Product not found.', 'tier-pricing-table' ); ?></p>
			<?php
			return;
		}
		?>
		<table class="tpt-quote-table" data-tpt-quote-items>
			<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Quantity', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Unit Price', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr data-tpt-quote-line>
				<td style="display: flex; align-items: center; gap: 12px;">
					<div style="width: 48px; height: 48px; border-radius: 4px; overflow: hidden; border: 1px solid #e5e5e5; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #fff;">
						<?php echo wp_kses_post( $product->get_image( array( 48, 48 ) ) ); ?>
					</div>
					<div>
						<strong><a href="<?php echo esc_url( get_edit_post_link( $product->get_id() ) ); ?>"
						           target="_blank"><?php echo wp_kses_post( $product->get_name() ); ?></a></strong>
						<?php if ( $product->get_sku() ) : ?>
							<div style="font-size: 12px; color: #646970; margin-top: 4px;">
								<?php esc_html_e( 'SKU:',
										'tier-pricing-table' ); ?><?php echo esc_html( $product->get_sku() ); ?>
							</div>
						<?php endif; ?>
					</div>
				</td>
				<td>
					<input type="number" min="1" step="1" name="tier_pricing_table_quote_quantity" data-tpt-quote-qty
					       aria-label="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
					       value="<?php echo esc_attr( $quantity ? $quantity : 1 ); ?>"
					       style="width: 80px;">
				</td>
				<td>
					<span class="tpt-quote-price-field">
						<span class="tpt-quote-currency"><?php echo esc_html( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?></span>
						<input type="number" step="any" min="0" name="tier_pricing_table_quote_price" data-tpt-quote-price
						       aria-label="<?php esc_attr_e( 'Unit Price', 'tier-pricing-table' ); ?>"
						       value="<?php echo esc_attr( $price ); ?>"
						       style="width: 100px;">
					</span>
				</td>
				<td>
					<strong data-tpt-quote-line-total>
						<?php
							// a price is often still to be set: no total until then
							echo is_numeric( $price ) ? wp_kses_post( wc_price( (float) $price * max( 1, (int) $quantity ) ) ) : '&ndash;';
						?>
					</strong>
				</td>
			</tr>
			</tbody>
		</table>

		<?php
		$this->renderConversion( $quote );
	}

	/**
	 * The products of a quote list request: each with its quantity and unit price to edit, and the total.
	 */
	protected function renderItems( QuoteRequest $quote ) {
		$items = $quote->getItems();
		$total = $quote->getItemsTotal();
		?>
		<table class="tpt-quote-table tpt-quote-table--items" data-tpt-quote-items>
			<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Quantity', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Unit Price', 'tier-pricing-table' ); ?></th>
				<th><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $items as $index => $item ) : ?>
				<?php
				$product   = wc_get_product( $item['product_id'] );
				$editId    = $product ? ( $product->get_parent_id() ?: $product->get_id() ) : 0;
				$lineTotal = is_numeric( $item['price'] ) ? (float) $item['price'] * $item['quantity'] : null;
				?>
				<tr data-tpt-quote-line>
					<td style="display: flex; align-items: center; gap: 12px;">
						<div style="width: 48px; height: 48px; border-radius: 4px; overflow: hidden; border: 1px solid #e5e5e5; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: #fff;">
							<?php if ( $product ) : ?>
								<?php echo wp_kses_post( $product->get_image( array( 48, 48 ) ) ); ?>
							<?php endif; ?>
						</div>
						<div>
							<strong>
								<?php if ( $editId ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $editId ) ); ?>" target="_blank"><?php echo esc_html( $item['name'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $item['name'] ); ?>
								<?php endif; ?>
							</strong>
							<?php if ( '' !== $item['sku'] ) : ?>
								<div style="font-size: 12px; color: #646970; margin-top: 4px;">
									<?php esc_html_e( 'SKU:', 'tier-pricing-table' ); ?> <?php echo esc_html( $item['sku'] ); ?>
								</div>
							<?php endif; ?>
							<?php if ( ! $product ) : ?>
								<div style="font-size: 12px; color: #b32d2e; margin-top: 4px;"><?php esc_html_e( 'Product not found.', 'tier-pricing-table' ); ?></div>
							<?php endif; ?>
						</div>
					</td>
					<td>
						<input type="number" min="1" step="1" name="tier_pricing_table_quote_items[<?php echo esc_attr( $index ); ?>][quantity]" data-tpt-quote-qty
						       aria-label="<?php esc_attr_e( 'Quantity', 'tier-pricing-table' ); ?>"
						       value="<?php echo esc_attr( $item['quantity'] ); ?>" style="width: 80px;">
					</td>
					<td>
						<span class="tpt-quote-price-field">
							<span class="tpt-quote-currency"><?php echo esc_html( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?></span>
							<input type="number" step="any" min="0" name="tier_pricing_table_quote_items[<?php echo esc_attr( $index ); ?>][price]" data-tpt-quote-price
							       aria-label="<?php esc_attr_e( 'Unit Price', 'tier-pricing-table' ); ?>"
							       value="<?php echo esc_attr( $item['price'] ); ?>" style="width: 100px;">
						</span>
					</td>
					<td>
						<strong data-tpt-quote-line-total><?php echo null !== $lineTotal ? wp_kses_post( wc_price( $lineTotal ) ) : '&ndash;'; ?></strong>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th colspan="3" style="text-align: right;"><?php esc_html_e( 'Total', 'tier-pricing-table' ); ?></th>
				<td><strong data-tpt-quote-total><?php echo null !== $total ? wp_kses_post( wc_price( $total ) ) : '&ndash;'; ?></strong></td>
			</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * The Convert to Order button, or the order the request became.
	 */
	protected function renderConversion( QuoteRequest $quote ) {
		?>
		<div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e5e5e5; display: flex; justify-content: flex-end; align-items: center;">
			<?php if ( $quote->getConvertedOrderId() ) : ?>
				<?php
				// the order's own edit link: orders are not posts when WooCommerce stores them in its own tables (HPOS)
				$convertedOrder = wc_get_order( $quote->getConvertedOrderId() );
				?>
				<p style="margin:0;">
					<?php esc_html_e( 'Converted to Order:', 'tier-pricing-table' ); ?>
					<?php if ( $convertedOrder ) : ?>
						<a href="<?php echo esc_url( $convertedOrder->get_edit_order_url() ); ?>">
							<strong>#<?php echo esc_html( $convertedOrder->get_order_number() ); ?></strong>
						</a>
					<?php else : ?>
						<strong>#<?php echo esc_html( $quote->getConvertedOrderId() ); ?></strong>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<input type="submit" name="tpt_convert_to_order" class="button button-primary"
				       value="<?php esc_attr_e( 'Convert to Order', 'tier-pricing-table' ); ?>">
			<?php endif; ?>
		</div>
		<?php
	}

	public function renderActionsMetaBox( $post ) {
		$quote = new QuoteRequest( $post );
		wp_nonce_field( 'tier_pricing_table_quote_save_product', 'tier_pricing_table_quote_nonce' );
		?>
		<div class="submitbox" id="submitpost">
			<div id="minor-publishing">
				<div style="padding: 10px;">
					<label for="tier_pricing_table_quote_status"
					       style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Status:',
								'tier-pricing-table' ); ?></label>
					<select name="tier_pricing_table_quote_status" id="tier_pricing_table_quote_status" style="width: 100%;">
						<option value="unread" <?php selected( $quote->getStatus(),
								'unread' ); ?>><?php esc_html_e( 'Unread', 'tier-pricing-table' ); ?></option>
						<option value="read" <?php selected( $quote->getStatus(), 'read' ); ?>><?php esc_html_e( 'Read',
									'tier-pricing-table' ); ?></option>
						<?php // "Converted" comes with an order: Convert to Order sets it ?>
						<?php if ( $quote->getConvertedOrderId() || 'converted' === $quote->getStatus() ) : ?>
						<option value="converted" <?php selected( $quote->getStatus(),
								'converted' ); ?>><?php esc_html_e( 'Converted', 'tier-pricing-table' ); ?></option>
						<?php endif; ?>
						<option value="rejected" <?php selected( $quote->getStatus(),
								'rejected' ); ?>><?php esc_html_e( 'Rejected', 'tier-pricing-table' ); ?></option>
					</select>
				</div>
				<?php
					$formId = $quote->getMeta( '_form_id' );
					if ( $formId ) {
						$form = \TierPricingTable\Addons\RequestAQuote\Models\RequestQuoteForm::get( (string) $formId );
						if ( $form ) {
							$formUrl = admin_url( 'admin.php?page=wc-settings&tab=tiered_pricing_table_settings&section=request-a-quote&form_id=' . $formId );
							?>
							<div style="padding: 10px; border-top: 1px solid #dcdcde;">
								<label style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Submitted via Form:',
											'tier-pricing-table' ); ?></label>
								<a href="<?php echo esc_url( $formUrl ); ?>" target="_blank"
								   style="text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 4px;">
									<span class="dashicons dashicons-admin-page"
									      style="font-size: 16px; width: 16px; height: 16px;"></span>
									<?php echo esc_html( $form->getName() ?: __( 'Unnamed Form',
											'tier-pricing-table' ) ); ?>
								</a>
							</div>
							<?php
						}
					}
				?>
			</div>
			<div id="major-publishing-actions"
			     style="display: flex; justify-content: space-between; align-items: center;">
				<div id="delete-action">
					<a class="submitdelete deletion"
					   href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php esc_html_e( 'Move to Trash' ); ?></a>
				</div>
				<div id="publishing-action">
					<span class="spinner"></span>
					<input type="submit" name="save" id="publish" class="button button-large"
					       value="<?php esc_attr_e( 'Save Quote', 'tier-pricing-table' ); ?>">
				</div>
			</div>
		</div>
		<?php
	}

	public function renderCustomerMetaBox( $post ) {
		$quote        = new QuoteRequest( $post );
		$userId       = $quote->getUserId();
		$email        = $quote->getCustomerEmail();
		$customFields = $quote->getCustomFields();

		$name = '';
		$user = null;

		if ( $userId ) {
			$user = get_userdata( $userId );
			if ( $user ) {
				$name  = $user->display_name;
				$email = $user->user_email ?: $email;
			}
		}

		if ( ! $name ) {
			// Try to find name in custom fields
			foreach ( $customFields as $k => $field ) {
				$fieldKey = strtolower( $k );
				if ( strpos( $fieldKey, 'name' ) !== false || strpos( $fieldKey, 'first_name' ) !== false ) {
					$name = is_array( $field ) ? ( $field['value'] ?? '' ) : $field;
					break;
				}
			}
			if ( ! $name ) {
				$name = __( 'Guest', 'tier-pricing-table' );
			}
		}

		$avatarUrl = get_avatar_url( $userId ?: $email, array( 'size' => 160 ) );
		?>
		<div class="tpt-quote-customer-widget" style="text-align: center; padding: 10px 0;">
			<div class="tpt-avatar-wrapper" style="margin-bottom: 15px;">
				<?php if ( $userId && $user ) : ?>
					<img src="<?php echo esc_url( $avatarUrl ); ?>" alt="Avatar"
					     style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
				<?php else : ?>
					<div style="width: 80px; height: 80px; border-radius: 50%; background: #f0f0f1; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto; border: 1px solid #c3c4c7;">
						<span class="dashicons dashicons-admin-users"
						      style="font-size: 40px; width: 40px; height: 40px; color: #8c8f94;"></span>
					</div>
				<?php endif; ?>
			</div>

			<div style="font-size: 16px; font-weight: 600; color: #1d2327; margin-bottom: 5px;">
				<?php echo esc_html( $name ); ?>
			</div>

			<?php if ( $email ) : ?>
				<div style="font-size: 13px; color: #50575e; margin-bottom: 12px; word-break: break-all;">
					<?php echo esc_html( $email ); ?>
				</div>
				<?php
					/* translators: %s: request number */
					$replySubject = sprintf( __( 'Your quote request #%s', 'tier-pricing-table' ), $post->ID );
				?>
				<a href="<?php echo esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( $replySubject ) ); ?>" class="button"
				   style="width: 100%; text-align: center; display: block; margin-bottom: 8px;">
					<?php esc_html_e( 'Reply by e-mail', 'tier-pricing-table' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $userId && $user ) : ?>
				<a href="<?php echo esc_url( get_edit_user_link( $userId ) ); ?>" class="button"
				   style="width: 100%; text-align: center; display: block; padding: 4px 0;">
					<?php esc_html_e( 'View Profile', 'tier-pricing-table' ); ?>
				</a>
			<?php else : ?>
				<div style="background: #f0f0f1; color: #3c434a; padding: 8px; border-radius: 4px; font-weight: 500; text-align: center; border: 1px solid #c3c4c7; display: flex; justify-content: center; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-admin-users"
					      style="font-size: 18px; line-height: 1; width: 18px; height: 18px; color: #8c8f94;"></span>
					<span><?php esc_html_e( 'Guest User', 'tier-pricing-table' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function savePost( $post_id, $post ) {
		if ( get_post_type( $post_id ) !== QuoteRequestCPT::POST_TYPE ) {
			return;
		}

		if ( ! isset( $_POST['tier_pricing_table_quote_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tier_pricing_table_quote_nonce'] ) ),
						'tier_pricing_table_quote_save_product' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$quote = new QuoteRequest( $post_id );

		// a quote list request: each product's quantity and unit price (the products themselves stay)
		if ( $quote->isList() && isset( $_POST['tier_pricing_table_quote_items'] ) && is_array( $_POST['tier_pricing_table_quote_items'] ) ) {
			$posted = wp_unslash( $_POST['tier_pricing_table_quote_items'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is cleaned below
			$items  = $quote->getItems();

			foreach ( $items as $index => $item ) {
				if ( empty( $posted[ $index ] ) || ! is_array( $posted[ $index ] ) ) {
					continue;
				}

				if ( isset( $posted[ $index ]['quantity'] ) ) {
					$items[ $index ]['quantity'] = max( 1, (int) $posted[ $index ]['quantity'] );
				}

				if ( isset( $posted[ $index ]['price'] ) ) {
					$price = wc_format_decimal( sanitize_text_field( (string) $posted[ $index ]['price'] ) );

					// empty is allowed (not priced yet), a negative price is not
					if ( '' === $price || (float) $price >= 0 ) {
						$items[ $index ]['price'] = $price;
					}
				}
			}

			$quote->setItems( $items );
		}

		if ( ! $quote->isList() && isset( $_POST['tier_pricing_table_quote_price'] ) ) {
			$price = wc_format_decimal( wp_unslash( $_POST['tier_pricing_table_quote_price'] ) );

			// empty is allowed (not priced yet), a negative price is not
			if ( '' === $price || (float) $price >= 0 ) {
				$quote->setPrice( $price );
			}
		}

		if ( ! $quote->isList() && isset( $_POST['tier_pricing_table_quote_quantity'] ) ) {
			$quote->setQuantity( (int) $_POST['tier_pricing_table_quote_quantity'] );
		}

		if ( isset( $_POST['tier_pricing_table_quote_note'] ) ) {
			$quote->updateMetaData( '_internal_note', sanitize_textarea_field( wp_unslash( $_POST['tier_pricing_table_quote_note'] ) ) );
		}

		if ( isset( $_POST['tier_pricing_table_quote_status'] ) ) {
			$status  = sanitize_key( wp_unslash( $_POST['tier_pricing_table_quote_status'] ) );
			$allowed = array( 'unread', 'read', 'rejected' );

			// "Converted" comes with an order: Convert to Order sets it
			if ( $quote->getConvertedOrderId() ) {
				$allowed[] = 'converted';
			}

			if ( in_array( $status, $allowed, true ) ) {
				$quote->setStatus( $status );
			}
		}

		// Handle Conversion to Order
		if ( isset( $_POST['tpt_convert_to_order'] ) && ! $quote->getConvertedOrderId() ) {
			// every product of the request (one for a single product's form); products without a price
			// (typical for quote-only products) leave the unit price empty: the owner sets it
			$lines   = array();
			$problem = null;

			foreach ( $quote->getItems() as $item ) {
				$product = wc_get_product( $item['product_id'] );

				if ( ! $product ) {
					$problem = 'product_missing';
					break;
				}

				if ( ! is_numeric( $item['price'] ) || (float) $item['price'] < 0 ) {
					$problem = 'price_required';
				}

				$lines[] = array( $product, max( 1, $item['quantity'] ), (float) $item['price'] );
			}

			if ( ! $lines && ! $problem ) {
				$problem = 'product_missing';
			}

			if ( $problem ) {
				remove_action( 'save_post', array( $this, 'savePost' ), 10 );
				$quote->save();

				add_filter( 'redirect_post_location', function ( $location ) use ( $problem ) {
					return add_query_arg( 'tpt_quote_conversion_error', $problem, remove_query_arg( 'message', $location ) );
				} );

				return;
			}

			$order = wc_create_order( array(
					'customer_id' => $this->getOrderCustomerId( $quote ),
					'created_via' => 'tpt-quote-request',
			) );

			foreach ( $lines as list( $product, $quantity, $price ) ) {
				$order->add_product( $product, $quantity, array(
					'subtotal' => $price * $quantity,
					'total'    => $price * $quantity,
				) );
			}

			// the customer's details first: taxes are worked out for their address
			$this->fillOrderCustomer( $order, $quote );
			$order->calculate_totals();

			// The customer's answers, with download links for their files
			$notes = "Quote Request Notes:\n";

			foreach ( $quote->getSubmittedFields( true ) as $field ) {
				$notes .= $field['label'] . ': ' . $field['value'] . ( $field['url'] ? ' (' . $field['url'] . ')' : '' ) . "\n";
			}
			$order->update_meta_data( 'quote_request_notes', $notes );
			$order->update_meta_data( '_tpt_quote_request_id', (int) $quote->getId() );
			$order->save();

			$order->add_order_note( sprintf(
			/* translators: %s: link to the quote request */
					__( 'Created from quote request %s.', 'tier-pricing-table' ),
					'<a href="' . esc_url( (string) get_edit_post_link( $quote->getId(), 'raw' ) ) . '">#' . (int) $quote->getId() . '</a>'
			) . "\n\n" . trim( $notes ) );

			// Update quote
			remove_action( 'save_post', array( $this, 'savePost' ), 10, 2 );

			$quote->setConvertedOrderId( $order->get_id() );
			$quote->setStatus( 'converted' );
			$quote->save();

			add_filter( 'redirect_post_location', function ( $location ) use ( $order ) {
				return $order->get_edit_order_url();
			} );

			return;
		}

		$quote->save();
	}

	/**
	 * The customer account the request was sent from, if it still exists.
	 */
	protected function getOrderCustomerId( QuoteRequest $quote ): int {
		$userId = $quote->getUserId();

		return $userId && get_userdata( $userId ) ? $userId : 0;
	}

	/**
	 * The order gets the customer's details: their account's saved addresses when they were logged in, and the
	 * name and e-mail from the request. With a billing e-mail the store can send WooCommerce's payment link.
	 */
	protected function fillOrderCustomer( \WC_Order $order, QuoteRequest $quote ) {
		$userId = $this->getOrderCustomerId( $quote );

		if ( $userId ) {
			try {
				$customer = new \WC_Customer( $userId );

				$order->set_address( $customer->get_billing(), 'billing' );
				$order->set_address( $customer->get_shipping(), 'shipping' );
			} catch ( \Exception $e ) {
				// an account WooCommerce cannot load: the request's own details below still apply
				unset( $e );
			}
		}

		$name = trim( wp_strip_all_tags( (string) $quote->getCustomerName() ) );

		if ( '' !== $name && '' === $order->get_billing_first_name() && '' === $order->get_billing_last_name() ) {
			$parts = preg_split( '/\s+/', $name, 2 );

			$order->set_billing_first_name( $parts[0] );
			$order->set_billing_last_name( $parts[1] ?? '' );
		}

		// the address the customer asked to be answered at
		$email = sanitize_email( (string) $quote->getCustomerEmail() );

		if ( is_email( $email ) ) {
			$order->set_billing_email( $email );
		}
	}

	public function setCustomColumns( $columns ) {
		$columns = array(
				'cb'       => '<input type="checkbox" />',
				'request'  => __( 'Request', 'tier-pricing-table' ),
				'customer' => __( 'Customer', 'tier-pricing-table' ),
				'product'  => __( 'Products', 'tier-pricing-table' ),
				'value'    => __( 'Value', 'tier-pricing-table' ),
				'status'   => __( 'Status', 'tier-pricing-table' ),
				'received' => __( 'Received', 'tier-pricing-table' ),
		);

		return $columns;
	}

	public function customColumnData( $column, $post_id ) {
		$quote                = new QuoteRequest( $post_id );

		switch ( $column ) {
			case 'request':
				// the number the customer was given; bold while unread
				?>
				<a class="row-title" href="<?php echo esc_url( (string) get_edit_post_link( $post_id ) ); ?>"
				   style="font-weight: <?php echo 'unread' === $quote->getStatus() ? '700' : '400'; ?>;">#<?php echo esc_html( $post_id ); ?></a>
				<?php
				break;

			case 'customer':
				$name = $quote->getCustomerName();
				$email        = $quote->getCustomerEmail();
				?>
				<?php echo esc_html( $name ); ?><br>
				<?php if ( $email ) : ?>
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			<?php endif; ?>
				<?php
				break;

			case 'product':
				// every product with its quantity; a long list is cut after three
				$items = $quote->getItems();
				$shown = array_slice( $items, 0, 3 );
				$more  = count( $items ) - count( $shown );

				if ( empty( $items ) ) {
					echo '&mdash;';
					break;
				}

				foreach ( $shown as $item ) :
					?>
					<div><?php echo esc_html( $item['name'] ); ?> &times; <?php echo esc_html( $item['quantity'] ); ?></div>
					<?php
				endforeach;

				if ( $more > 0 ) :
					?>
					<small>
						<?php
						// translators: %d: number of other products.
						echo esc_html( sprintf( _n( '+ %d more product', '+ %d more products', $more, 'tier-pricing-table' ), $more ) );
						?>
					</small>
					<?php
				endif;
				break;

			case 'value':
				// quantity times unit price, once every line has a price
				$total = $quote->getItemsTotal();

				echo null !== $total ? wp_kses_post( wc_price( $total ) ) : '&mdash;';
				break;

			case 'received':
				echo esc_html( get_the_date( '', $post_id ) );
				?>
				<br><small><?php echo esc_html( get_the_time( '', $post_id ) ); ?></small>
				<?php
				break;

			case 'status':
				$status = $quote->getStatus();
				$statusLabels = array(
						'unread'    => __( 'Unread', 'tier-pricing-table' ),
						'read'      => __( 'Read', 'tier-pricing-table' ),
						'converted' => __( 'Converted', 'tier-pricing-table' ),
						'rejected'  => __( 'Rejected', 'tier-pricing-table' ),
				);
				$label        = isset( $statusLabels[ $status ] ) ? $statusLabels[ $status ] : $status;
				?>
				<mark class="tpt-quote-status status-<?php echo esc_attr( $status ); ?>">
					<span><?php echo esc_html( $label ); ?></span></mark>
				<?php
				break;
		}
	}

	public function addProductsFilter() {
		global $typenow;

		if ( QuoteRequestCPT::POST_TYPE === $typenow ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );

			$selected = isset( $_GET['tpt_product_filter'] ) ? absint( $_GET['tpt_product_filter'] ) : 0;
			?>
			<select name="tpt_product_filter" id="tpt_product_filter" class="wc-product-search" style="width: 250px;"
			        data-allow_clear="true"
			        data-placeholder="<?php esc_attr_e( 'Filter by product', 'tier-pricing-table' ); ?>"
			        data-action="woocommerce_json_search_products_and_variations">
				<option value=""><?php esc_html_e( 'Show all products', 'tier-pricing-table' ); ?></option>
				<?php if ( $selected > 0 ) : ?>
					<?php $product = wc_get_product( $selected ); ?>
					<?php if ( $product ) : ?>
						<option value="<?php echo esc_attr( $selected ); ?>"
						        selected="selected"><?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?></option>
					<?php endif; ?>
				<?php endif; ?>
			</select>
			<?php
		}
	}

	public function filterByProduct( $query ) {
		global $pagenow;

		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && QuoteRequestCPT::POST_TYPE === $_GET['post_type'] && isset( $_GET['tpt_product_filter'] ) && $_GET['tpt_product_filter'] > 0 ) {
			$meta_query = $query->get( 'meta_query' );
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array();
			}

			// the request's product, or any product of a quote list request
			$meta_query[] = array(
					'relation' => 'OR',
					array(
							'key'     => '_product_id',
							'value'   => absint( $_GET['tpt_product_filter'] ),
							'compare' => '=',
					),
					array(
							'key'     => QuoteRequest::ITEM_PRODUCT_META,
							'value'   => absint( $_GET['tpt_product_filter'] ),
							'compare' => '=',
					),
			);

			$query->set( 'meta_query', $meta_query );
		}
	}

	public function registerBulkActions( $bulk_actions ) {
		// "Edit" opens WordPress's bulk editor (status, author), which means nothing for requests
		unset( $bulk_actions['edit'] );

		$bulk_actions['tpt_mark_unread']   = __( 'Mark as Unread', 'tier-pricing-table' );
		$bulk_actions['tpt_mark_read']     = __( 'Mark as Read', 'tier-pricing-table' );
		$bulk_actions['tpt_mark_rejected'] = __( 'Mark as Rejected', 'tier-pricing-table' );

		return $bulk_actions;
	}

	public function processBulkActions( $redirect_to, $doaction, $post_ids ) {
		if ( ! in_array( $doaction, array( 'tpt_mark_unread', 'tpt_mark_read', 'tpt_mark_rejected' ) ) ) {
			return $redirect_to;
		}

		$changed = 0;
		foreach ( $post_ids as $post_id ) {
			$quote = new QuoteRequest( $post_id );
			if ( 'tpt_mark_unread' === $doaction ) {
				$quote->setStatus( 'unread' );
			} elseif ( 'tpt_mark_read' === $doaction ) {
				$quote->setStatus( 'read' );
			} elseif ( 'tpt_mark_rejected' === $doaction ) {
				$quote->setStatus( 'rejected' );
			}
			$quote->save();
			$changed ++;
		}

		$redirect_to = add_query_arg( 'tpt_bulk_changed', $changed, $redirect_to );
		$redirect_to = add_query_arg( 'tpt_bulk_action', $doaction, $redirect_to );

		return $redirect_to;
	}

	/**
	 * Why Convert to Order stopped, on the quote edit screen.
	 */
	public function conversionNotices() {
		if ( empty( $_GET['tpt_quote_conversion_error'] ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || QuoteRequestCPT::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$problem = sanitize_key( wp_unslash( $_GET['tpt_quote_conversion_error'] ) );

		if ( 'price_required' === $problem ) {
			$message = __( 'Enter a unit price, then click Convert to Order again. Your other changes are saved.', 'tier-pricing-table' );
		} elseif ( 'product_missing' === $problem ) {
			$message = __( 'The product in this quote no longer exists, so it cannot be converted to an order.', 'tier-pricing-table' );
		} else {
			return;
		}

		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	public function bulkActionNotices() {
		if ( ! empty( $_REQUEST['tpt_bulk_changed'] ) && ! empty( $_REQUEST['tpt_bulk_action'] ) ) {
			$changed = absint( $_REQUEST['tpt_bulk_changed'] );
			$action  = sanitize_text_field( $_REQUEST['tpt_bulk_action'] );

			if ( 'tpt_mark_unread' === $action ) {
				// translators: %s: number of quotes.
				$message = sprintf( _n( '%s quote marked as unread.', '%s quotes marked as unread.', $changed,
						'tier-pricing-table' ), $changed );
			} elseif ( 'tpt_mark_read' === $action ) {
				// translators: %s: number of quotes.
				$message = sprintf( _n( '%s quote marked as read.', '%s quotes marked as read.', $changed,
						'tier-pricing-table' ), $changed );
			} elseif ( 'tpt_mark_rejected' === $action ) {
				// translators: %s: number of quotes.
				$message = sprintf( _n( '%s quote marked as rejected.', '%s quotes marked as rejected.', $changed,
						'tier-pricing-table' ), $changed );
			} else {
				return;
			}

			echo '<div id="message" class="updated notice is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}
}