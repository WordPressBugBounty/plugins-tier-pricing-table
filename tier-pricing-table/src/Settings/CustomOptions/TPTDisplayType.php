<?php namespace TierPricingTable\Settings\CustomOptions;

/**
 * Single-choice setting rendered as a group of choice chips.
 *
 * Each option is a native radio input, so the group keeps the browser's keyboard behaviour
 * (Tab into the group, arrow keys to move the selection) and works with the settings
 * dependency script, which reads the checked input by name. The selected chip shows a
 * check mark so the current value is visible without relying on colour alone.
 *
 * Field definition keys (on top of the WooCommerce ones):
 *  - options: array of value => label.
 *  - descriptions (optional): array of value => short text shown under the label of that chip.
 */
class TPTDisplayType {

	const FIELD_TYPE = 'tpt_display_type';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
	}

	public function render( $value ) {
		$extendedDescription = (string) ( $value['extended_description'] ?? '' );
		$value = wp_parse_args( $value, array(
			'id'           => '',
			'title'        => isset( $value['name'] ) ? $value['name'] : '',
			'default'      => '',
			'desc'         => '',
			'options'      => array(),
			'descriptions' => array(),
			'class'        => '',
		) );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$optionValue  = $value['value'];
		$descriptions = is_array( $value['descriptions'] ) ? $value['descriptions'] : array();
		$hasDesc      = ! empty( array_filter( $descriptions ) );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] . '-' . array_key_first( $value['options'] ) ); ?>">
					<?php echo esc_html( $value['title'] ); ?>
				</label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<fieldset class="tpt-chips <?php echo esc_attr( $value['class'] ); ?><?php echo $hasDesc ? ' tpt-chips--described' : ''; ?>">
					<legend class="screen-reader-text"><?php echo esc_html( $value['title'] ); ?></legend>
					<?php foreach ( $value['options'] as $key => $label ) :
						$inputId = $value['id'] . '-' . $key;
						?>
						<div class="tpt-chip">
							<input type="radio"
								   class="tpt-chip__input"
								   name="<?php echo esc_attr( $value['id'] ); ?>"
								   id="<?php echo esc_attr( $inputId ); ?>"
								   value="<?php echo esc_attr( $key ); ?>"
								<?php checked( (string) $key, (string) $optionValue ); ?>
							>
							<label class="tpt-chip__surface" for="<?php echo esc_attr( $inputId ); ?>">
								<span class="tpt-chip__check" aria-hidden="true">
									<svg viewBox="0 0 20 20" width="16" height="16" focusable="false">
										<path d="M7.6 14.2 3.9 10.5l1.4-1.4 2.3 2.3 7-7 1.4 1.4z" fill="currentColor"/>
									</svg>
								</span>
								<span class="tpt-chip__text">
									<span class="tpt-chip__label"><?php echo esc_html( $label ); ?></span>
									<?php if ( ! empty( $descriptions[ $key ] ) ) : ?>
										<span class="tpt-chip__desc"><?php echo esc_html( $descriptions[ $key ] ); ?></span>
									<?php endif; ?>
								</span>
							</label>
						</div>
					<?php endforeach; ?>
				</fieldset>
				<?php if ( ! empty( $value['desc'] ) ) : ?>
					<p class="description"><?php echo esc_html( $value['desc'] ); ?></p>
				<?php endif; ?>
				<?php if ( isset( $value['extended_description'] ) ) : ?>
					<div class="tpt-toggle-extended-description">
						<?php
							echo wp_kses_post( $extendedDescription ); // nosemgrep
						?>
					</div>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
