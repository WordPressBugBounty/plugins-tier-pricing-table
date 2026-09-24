<?php namespace TierPricingTable\Addons\NonLoggedInUsers\CartRules;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;

/**
 * The WooCommerce settings field with one row per role: minimum order amount and minimum quantity.
 * Saved as JSON.
 */
class RoleMinimumsOption {

	const FIELD_TYPE = 'tpt_role_minimums';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_filter( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {
			if ( self::FIELD_TYPE !== ( $option['type'] ?? '' ) ) {
				return $value;
			}

			return wp_json_encode( CartRules::normalizeMinimums( is_array( $rawValue ) ? wp_unslash( $rawValue ) : array() ) );
		}, 10, 3 );
	}

	public function render( $value ) {
		// the description is plugin-defined text (settings arrays), read once for output
		$description = (string) ( $value['desc'] ?? '' );
		$id       = (string) ( $value['id'] ?? '' );
		$minimums = CartRules::normalizeMinimums( \WC_Admin_Settings::get_option( $id, '' ) );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo esc_html( $value['title'] ?? '' ); ?></label>
			</th>
			<td class="forminp">
				<div class="tpt-role-minimums" id="<?php echo esc_attr( $id ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $id ); ?>[__none__][amount]" value="">
					<table class="widefat tpt-role-minimums__table">
						<thead>
						<tr>
							<th><?php esc_html_e( 'Role', 'tier-pricing-table' ); ?></th>
							<th>
								<?php
									/* translators: %s: currency symbol */
									echo esc_html( sprintf( __( 'Minimum order amount (%s)', 'tier-pricing-table' ), html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' ) ) );
								?>
							</th>
							<th><?php esc_html_e( 'Minimum quantity', 'tier-pricing-table' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( Roles::getOptions() as $role => $label ) : ?>
							<?php $row = $minimums[ $role ] ?? array( 'amount' => 0, 'quantity' => 0 ); ?>
							<tr>
								<td><?php echo esc_html( $label ); ?></td>
								<td>
									<input type="number" min="0" step="0.01" name="<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $role ); ?>][amount]"
										   value="<?php echo esc_attr( $row['amount'] > 0 ? wc_format_localized_price( $row['amount'] ) : '' ); ?>" placeholder="—">
								</td>
								<td>
									<input type="number" min="0" step="1" name="<?php echo esc_attr( $id ); ?>[<?php echo esc_attr( $role ); ?>][quantity]"
										   value="<?php echo esc_attr( $row['quantity'] > 0 ? $row['quantity'] : '' ); ?>" placeholder="—">
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( ! empty( $value['desc'] ) ) : ?>
						<p class="description"><?php echo wp_kses_post( $description ); // nosemgrep ?></p>
					<?php endif; ?>
				</div>
				<style>
					.woocommerce table.form-table .tpt-role-minimums { max-width: 640px; }
					.woocommerce table.form-table .tpt-role-minimums__table th,
					.woocommerce table.form-table .tpt-role-minimums__table td { padding: 8px 10px; vertical-align: middle; }
					.woocommerce table.form-table .tpt-role-minimums__table th { font-weight: 600; }
					.woocommerce table.form-table .tpt-role-minimums__table input[type=number] { width: 140px; }
				</style>
			</td>
		</tr>
		<?php
	}
}
