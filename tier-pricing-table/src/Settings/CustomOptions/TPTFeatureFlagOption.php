<?php namespace TierPricingTable\Settings\CustomOptions;

/**
 * The Modules screen (Settings → Advanced): one card per module with its icon, name, description and switch,
 * and group headings. settings.js adds the count of the modules that are on next to the "Modules" title.
 */
class TPTFeatureFlagOption {

	const FIELD_TYPE = 'tpt_feature_flag_option';
	const GROUP_TYPE = 'tpt_feature_flag_group';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
		add_action( 'woocommerce_admin_field_' . self::GROUP_TYPE, array( $this, 'renderGroup' ) );

		add_action( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {

			if ( self::FIELD_TYPE === $option['type'] ) {
				$value = in_array( $value, array( 1, 'yes' ) ) ? 'yes' : 'no';
			}

			return $value;
		}, 10, 3 );
	}

	public function render( $value ) {
		$value = wp_parse_args( $value, array(
			'id'      => '',
			'title'   => $value['name'] ?? '',
			'desc'    => '',
			'default' => '',
			'icon'    => '',
		) );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		// read once for output (QIT flags output that reads $value[...] directly inside render())
		$isOn        = 'yes' === $value['value'];
		$fieldId     = (string) $value['id'];
		$title       = (string) $value['title'];
		$description = (string) $value['desc'];
		$icon        = (string) $value['icon'];
		?>
		<tr class="tpt-feature-flag-item <?php echo $isOn ? '' : 'is-off'; ?>" data-tpt-module>
			<td>
				<div class="tpt-feature-flag-wrapper">
					<span class="tpt-feature-flag-item__icon" aria-hidden="true">
						<?php echo wp_kses( $icon, self::getAllowedIconTags() ); ?>
					</span>

					<div class="tpt-feature-flag-item__description">
						<h4 id="<?php echo esc_attr( $fieldId ); ?>-title"><?php echo esc_html( $title ); ?></h4>

						<p class="description" id="<?php echo esc_attr( $fieldId ); ?>-description">
							<?php echo wp_kses_post( $description ); // nosemgrep ?>
						</p>

						<span class="tpt-feature-flag-item__pending" hidden>
							<?php esc_html_e( 'Save changes to apply', 'tier-pricing-table' ); ?>
						</span>
					</div>

					<div class="tpt-feature-flag-item-checkbox">
						<input name="<?php echo esc_attr( $fieldId ); ?>"
							   id="<?php echo esc_attr( $fieldId ); ?>"
							   type="checkbox"
							   value="1"
							   class="tpt-toggle-switch"
							   data-tpt-module-switch
							   data-tpt-module-initial="<?php echo $isOn ? '1' : '0'; ?>"
							   aria-describedby="<?php echo esc_attr( $fieldId ); ?>-description"
							<?php checked( $isOn ); ?>>
						<label for="<?php echo esc_attr( $fieldId ); ?>">
							<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
						</label>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * A group heading: the name and how many of its modules are on (kept current by settings.js).
	 */
	public function renderGroup( $value ) {
		$title = (string) ( $value['title'] ?? '' );
		$group = (string) ( $value['group'] ?? '' );
		?>
		<tr class="tpt-feature-flag-group" data-tpt-module-group="<?php echo esc_attr( $group ); ?>">
			<td>
				<h3>
					<?php echo esc_html( $title ); ?>
					<span class="tpt-feature-flag-group__count" data-tpt-module-group-count></span>
				</h3>
			</td>
		</tr>
		<?php
	}

	/**
	 * The add-on icons are one-path SVGs.
	 */
	public static function getAllowedIconTags(): array {
		$shape = array(
			'd'               => true,
			'fill'            => true,
			'fill-rule'       => true,
			'clip-rule'       => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'cx'              => true,
			'cy'              => true,
			'r'               => true,
			'x'               => true,
			'y'               => true,
			'width'           => true,
			'height'          => true,
			'rx'              => true,
			'points'          => true,
			'transform'       => true,
		);

		return array(
			'svg'      => array(
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'aria-hidden' => true,
				'focusable'   => true,
			),
			'path'     => $shape,
			'circle'   => $shape,
			'rect'     => $shape,
			'g'        => $shape,
			'polygon'  => $shape,
			'polyline' => $shape,
			'line'     => $shape,
		);
	}
}
