<?php namespace TierPricingTable\Settings\CustomOptions;

/**
 * A list of checkboxes, or a multi-select, saved as a comma-separated string so the layout configurator's
 * hidden inputs can carry the same value. Unticking everything saves an empty string.
 */
class TPTCheckboxListOption {

	const FIELD_TYPE = 'tpt_checkbox_list';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_filter( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {
			if ( self::FIELD_TYPE !== ( $option['type'] ?? '' ) ) {
				return $value;
			}

			$chosen = array_map( 'sanitize_text_field', array_filter( (array) $rawValue, 'is_scalar' ) );

			// the category search has no fixed option list: keep term ids
			if ( 'category-search' === ( $option['display'] ?? '' ) ) {
				return implode( ',', array_filter( array_map( 'intval', $chosen ) ) );
			}

			$known = array_map( 'strval', array_keys( (array) ( $option['options'] ?? array() ) ) );

			return implode( ',', array_values( array_intersect( $chosen, $known ) ) );
		}, 10, 3 );
	}

	/**
	 * The stored value as a list.
	 */
	public static function toArray( $value ): array {
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}

		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
	}

	public function render( $value ) {
		// the description is plugin-defined text (settings arrays), read once for output
		$description = (string) ( $value['desc'] ?? '' );
		$value  = wp_parse_args( $value, array(
			'id'          => '',
			'title'       => '',
			'desc'        => '',
			'default'     => '',
			'options'     => array(),
			'display'     => 'checkboxes',
			'placeholder' => '',
			'type'        => self::FIELD_TYPE,
		) );
		$chosen = self::toArray( \WC_Admin_Settings::get_option( $value['id'], $value['default'] ) );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $value['id'] ); ?>[]" value="">
				<?php if ( 'category-search' === $value['display'] ) : ?>
					<select id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>[]" multiple="multiple" class="wc-category-search" style="width: 400px;" data-placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>" data-action="woocommerce_json_search_categories" data-allow_clear="true">
						<?php foreach ( $chosen as $termId ) : ?>
							<?php $term = get_term( (int) $termId, 'product_cat' ); ?>
							<?php if ( $term && ! is_wp_error( $term ) ) : ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>" selected="selected"><?php echo esc_html( $term->name ); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				<?php elseif ( 'select' === $value['display'] ) : ?>
					<select id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>[]" multiple="multiple" class="wc-enhanced-select" style="width: 400px;" data-placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>">
						<?php foreach ( $value['options'] as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( (string) $key, $chosen, true ) ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<fieldset id="<?php echo esc_attr( $value['id'] ); ?>" class="tpt-checkbox-list">
						<?php foreach ( $value['options'] as $key => $label ) : ?>
							<label style="display: block; margin-bottom: 4px;">
								<input type="checkbox" name="<?php echo esc_attr( $value['id'] ); ?>[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( (string) $key, $chosen, true ) ); ?>>
								<?php echo esc_html( $label ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				<?php endif; ?>
				<?php if ( $value['desc'] ) : ?>
					<p class="description"><?php echo wp_kses_post( $description ); // nosemgrep ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}
}
