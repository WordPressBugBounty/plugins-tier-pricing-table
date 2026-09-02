<?php namespace TierPricingTable\Settings\CustomOptions;

class TPTIntegrationOption {
	
	const FIELD_TYPE = 'tpt_integration_option';
	
	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
		
		add_action( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {
			
			if ( self::FIELD_TYPE === $option['type'] ) {
				$value = in_array( $value, array( 1, 'yes' ) ) ? 'yes' : 'no';
			}
			
			return $value;
		}, 10, 3 );
	}
	
	public function render( $value ) {
		if ( ! isset( $value['id'] ) ) {
			$value['id'] = '';
		}
		
		if ( ! isset( $value['title'] ) ) {
			$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
		}
		if ( ! isset( $value['default'] ) ) {
			$value['default'] = '';
		}
		
		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}
		
		if ( ! isset( $value['on_label'] ) ) {
			$value['on_label'] = __( 'On', 'role-and-customer-based-pricing-for-woocommerce' );
		}
		
		if ( ! isset( $value['off_label'] ) ) {
			$value['off_label'] = __( 'Off', 'role-and-customer-based-pricing-for-woocommerce' );
		}
		if ( ! isset( $value['desc'] ) ) {
			$value['desc'] = '';
		}
		
		$option_value = $value['value'];
		$is_official  = ! empty( $value['official'] );
		$action_links = isset( $value['action_links'] ) && is_array( $value['action_links'] ) ? $value['action_links'] : array();
		$meta_pills   = isset( $value['meta_pills'] ) && is_array( $value['meta_pills'] ) ? $value['meta_pills'] : array();
		?>
		<tr class="tpt-integration-item <?php echo $is_official ? 'tpt-integration-item--official' : ''; ?>">
			<td>
				<div class="tpt-integration-wrapper <?php echo $is_official ? 'tpt-integration-wrapper--official' : ''; ?>">
					<div class="tpt-integration-item__image">
						<img src="<?php echo esc_attr( $value['icon_url'] ); ?>"
							 alt="<?php echo esc_attr( $value['title'] ); ?>">
					</div>
					<div class="tpt-integration-item__description">
						<div class="tpt-integration-item__content">
						
						<?php $official_badge = $is_official ? ' <span class="tpt-integration-item__official-badge">' . esc_html__( 'By U2Code', 'tier-pricing-table' ) . '</span>' : ''; ?>
						<?php if ( $value['author_url'] ) : ?>
							<a target="_blank" href="<?php echo esc_attr( $value['author_url'] ); ?>">
								<h4><?php echo esc_html( $value['title'] ) . wp_kses_post( $official_badge ); ?></h4>
							</a>
						<?php else : ?>
							<h4><?php echo esc_html( $value['title'] ) . wp_kses_post( $official_badge ); ?></h4>
						<?php endif; ?>

						<?php if ( $meta_pills ) : ?>
							<div class="tpt-integration-item__pills">
								<?php foreach ( $meta_pills as $pill ) : ?>
									<span class="tpt-integration-item__pill"><?php echo esc_html( $pill ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<p class="description">
							<?php
								echo wp_kses_post( $value['desc'] ); // audit.php.wp.security.xss.shortcode-attr ignore
							?>
						</p>

						</div>

						<div class="tpt-integration-item__actions">
						<?php if ( $action_links ) : ?>
							<div class="tpt-integration-item__action-links">
								<?php foreach ( $action_links as $action_link ) : ?>
									<?php if ( empty( $action_link['url'] ) || empty( $action_link['label'] ) ) { continue; } ?>
									<a class="tpt-integration-item__action-link"
										target="<?php echo esc_attr( $action_link['target'] ?? '_blank' ); ?>"
										<?php echo ( $action_link['target'] ?? '_blank' ) === '_blank' ? 'rel="noopener"' : ''; ?>
										href="<?php echo esc_url( $action_link['url'] ); ?>">
										<?php echo esc_html( $action_link['label'] ); ?> &rarr;
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<div class="tpt-integration-item-checkbox">
							<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="checkbox"
									value="1"
								<?php checked( $option_value, 'yes' ); ?>
									class="tpt-toggle-switch"
							/>
							<label for="<?php echo esc_attr( $value['id'] ); ?>">
								<span data-tpt-toggle-switch-on><?php echo esc_attr( $value['on_label'] ); ?></span>
								<span data-tpt-toggle-switch-off><?php echo esc_attr( $value['off_label'] ); ?></span>
							</label>
						</div>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
}
