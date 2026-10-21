<?php namespace TierPricingTable\Settings\CustomOptions;

/**
 * A switch as a compact row (switch, label, one-line hint) for use inside a collapsible card, instead
 * of a row of the WooCommerce settings table. Same input and sanitising as the switch option, so the
 * stored value is "yes" or "no". An optional `example` (HTML) folds away under "Show an example".
 */
class TPTSwitchRowOption extends TPTSwitchOption {

	const FIELD_TYPE = 'tpt_switch_row';

	public function render( $value ) {
		// plugin-defined texts (settings arrays), read into locals for output
		$id          = (string) ( $value['id'] ?? '' );
		$title       = (string) ( $value['title'] ?? ( $value['name'] ?? '' ) );
		$description = (string) ( $value['desc'] ?? '' );
		$example     = (string) ( $value['example'] ?? '' );
		$onLabel     = (string) ( $value['on_label'] ?? __( 'On', 'tier-pricing-table' ) );
		$offLabel    = (string) ( $value['off_label'] ?? __( 'Off', 'tier-pricing-table' ) );
		$attributes  = isset( $value['custom_attributes'] ) ? implode( ' ', array_keys( (array) $value['custom_attributes'] ) ) : '';

		$optionValue = $value['value'] ?? \WC_Admin_Settings::get_option( $id, $value['default'] ?? '' );
		?>
		<div class="tpt-settings-card__row">
			<div class="tpt-settings-card__switch">
				<input
					<?php echo esc_attr( $attributes ); ?>
					name="<?php echo esc_attr( $id ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					type="checkbox"
					value="1"
					<?php checked( $optionValue, 'yes' ); ?>
					class="tpt-toggle-switch"
				/>
				<label for="<?php echo esc_attr( $id ); ?>">
					<span data-tpt-toggle-switch-on><?php echo esc_html( $onLabel ); ?></span>
					<span data-tpt-toggle-switch-off><?php echo esc_html( $offLabel ); ?></span>
				</label>
			</div>
			<div class="tpt-settings-card__text">
				<label class="tpt-settings-card__label" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
				<?php if ( $description ) : ?>
					<p class="tpt-settings-card__desc"><?php echo wp_kses_post( $description ); // nosemgrep ?></p>
				<?php endif; ?>
				<?php if ( $example ) : ?>
					<details class="tpt-settings-card__example">
						<summary><?php esc_html_e( 'Show an example', 'tier-pricing-table' ); ?></summary>
						<?php echo wp_kses_post( $example ); // nosemgrep ?>
					</details>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
