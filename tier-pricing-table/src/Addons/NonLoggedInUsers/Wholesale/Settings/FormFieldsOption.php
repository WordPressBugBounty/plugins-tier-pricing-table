<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings;

/**
 * The WooCommerce settings field that edits the application form: one row per field with label, type,
 * required and, for a dropdown, its options. Rows can be added, removed and reordered. Saved as JSON.
 */
class FormFieldsOption {

	const FIELD_TYPE = 'tpt_wholesale_form_fields';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );

		add_filter( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {
			if ( self::FIELD_TYPE !== ( $option['type'] ?? '' ) ) {
				return $value;
			}

			$rows = is_array( $rawValue ) ? wp_unslash( $rawValue ) : array();

			return FormFields::toJson( FormFields::sanitizeList( $rows ) );
		}, 10, 3 );
	}

	public function render( $value ) {
		// the description is plugin-defined text (settings arrays), read once for output
		$description = (string) ( $value['desc'] ?? '' );
		$id     = (string) ( $value['id'] ?? '' );
		$fields = FormFields::fromJson( \WC_Admin_Settings::get_option( $id, '' ) );
		$types  = FormFields::getTypes();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo esc_html( $value['title'] ?? '' ); ?></label>
			</th>
			<td class="forminp">
				<div class="tpt-form-builder" data-name="<?php echo esc_attr( $id ); ?>">
					<input type="hidden" name="<?php echo esc_attr( $id ); ?>[__none__][label]" value="">
					<table class="widefat tpt-form-builder__table">
						<colgroup>
							<col class="tpt-form-builder__col-move">
							<col>
							<col class="tpt-form-builder__col-type">
							<col class="tpt-form-builder__col-required">
							<col class="tpt-form-builder__col-remove">
						</colgroup>
						<thead>
						<tr>
							<th class="tpt-form-builder__col-move"></th>
							<th><?php esc_html_e( 'Label', 'tier-pricing-table' ); ?></th>
							<th class="tpt-form-builder__col-type"><?php esc_html_e( 'Type', 'tier-pricing-table' ); ?></th>
							<th class="tpt-form-builder__col-required"><?php esc_html_e( 'Required', 'tier-pricing-table' ); ?></th>
							<th class="tpt-form-builder__col-remove"></th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ( $fields as $index => $field ) : ?>
							<?php $this->renderRow( $id, (string) $index, $field, $types ); ?>
						<?php endforeach; ?>
						</tbody>
					</table>

					<p class="tpt-form-builder__actions">
						<button type="button" class="button tpt-form-builder__add"><?php esc_html_e( '+ Add field', 'tier-pricing-table' ); ?></button>
						<select class="tpt-form-builder__preset">
							<option value=""><?php esc_html_e( 'Or add a common field…', 'tier-pricing-table' ); ?></option>
							<?php foreach ( FormFields::getPresets() as $key => $preset ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $preset['label'] ); ?>" data-type="<?php echo esc_attr( $preset['type'] ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<?php if ( ! empty( $value['desc'] ) ) : ?>
						<p class="description"><?php echo wp_kses_post( $description ); // nosemgrep ?></p>
					<?php endif; ?>

					<template class="tpt-form-builder__template">
						<?php $this->renderRow( $id, '__index__', FormFields::normalize( array( 'label' => ' ' ) ), $types ); ?>
					</template>
				</div>
				<?php $this->renderAssets(); ?>
			</td>
		</tr>
		<?php
	}

	protected function renderRow( string $name, string $index, array $field, array $types ) {
		$prefix = $name . '[' . $index . ']';
		?>
		<tr class="tpt-form-builder__row" data-known="<?php echo FormFields::isKnownKey( $field['key'] ) ? '1' : '0'; ?>">
			<td class="tpt-form-builder__col-move">
				<button type="button" class="tpt-form-builder__move" data-dir="up" title="<?php esc_attr_e( 'Move up', 'tier-pricing-table' ); ?>">▲</button>
				<button type="button" class="tpt-form-builder__move" data-dir="down" title="<?php esc_attr_e( 'Move down', 'tier-pricing-table' ); ?>">▼</button>
			</td>
			<td>
				<input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[key]" value="<?php echo esc_attr( $field['key'] ); ?>">
				<input type="text" class="tpt-form-builder__label" name="<?php echo esc_attr( $prefix ); ?>[label]" value="<?php echo esc_attr( trim( $field['label'] ) ); ?>" placeholder="<?php esc_attr_e( 'Field label', 'tier-pricing-table' ); ?>">
				<textarea class="tpt-form-builder__options" name="<?php echo esc_attr( $prefix ); ?>[options]" rows="3" placeholder="<?php esc_attr_e( 'One option per line', 'tier-pricing-table' ); ?>" <?php echo 'select' === $field['type'] ? '' : 'hidden'; ?>><?php echo esc_textarea( implode( "\n", $field['options'] ) ); ?></textarea>
				<?php if ( FormFields::isKnownKey( $field['key'] ) ) : ?>
					<span class="tpt-form-builder__hint"><?php echo esc_html( $this->knownKeyHint( $field['key'] ) ); ?></span>
				<?php endif; ?>
			</td>
			<td class="tpt-form-builder__col-type">
				<select name="<?php echo esc_attr( $prefix ); ?>[type]" class="tpt-form-builder__type">
					<?php foreach ( $types as $type => $label ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $field['type'], $type ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td class="tpt-form-builder__col-required">
				<input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[required]" value="1" <?php checked( $field['required'] ); ?>>
			</td>
			<td class="tpt-form-builder__col-remove">
				<button type="button" class="tpt-form-builder__remove" title="<?php esc_attr_e( 'Remove field', 'tier-pricing-table' ); ?>">&times;</button>
			</td>
		</tr>
		<?php
	}

	protected function knownKeyHint( string $key ): string {
		switch ( $key ) {
			case 'company':
				return __( 'Saved as the billing company.', 'tier-pricing-table' );
			case 'phone':
				return __( 'Saved as the billing phone.', 'tier-pricing-table' );
			case 'website':
				return __( 'Saved as the account website.', 'tier-pricing-table' );
			default:
				return '';
		}
	}

	protected function renderAssets() {
		static $printed = false;

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
		<style>
			/* the builder sits inside WooCommerce's settings table, whose th/td/select rules would otherwise size these cells */
			.woocommerce table.form-table .tpt-form-builder { max-width: 760px; }
			.woocommerce table.form-table .tpt-form-builder__table { table-layout: fixed; width: 100%; margin: 0 0 10px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: none; border-collapse: separate; border-spacing: 0; }
			.woocommerce table.form-table .tpt-form-builder__table th,
			.woocommerce table.form-table .tpt-form-builder__table td { width: auto; padding: 8px; vertical-align: top; line-height: 1.4; font-size: 13px; }
			.woocommerce table.form-table .tpt-form-builder__table thead th { padding: 7px 8px; font-weight: 600; color: #1d2327; border-bottom: 1px solid #c3c4c7; }
			.woocommerce table.form-table .tpt-form-builder__table tbody tr + tr td { border-top: 1px solid #f0f0f1; }
			.woocommerce table.form-table col.tpt-form-builder__col-move { width: 30px; }
			.woocommerce table.form-table col.tpt-form-builder__col-type { width: 150px; }
			.woocommerce table.form-table col.tpt-form-builder__col-required { width: 76px; }
			.woocommerce table.form-table col.tpt-form-builder__col-remove { width: 34px; }
			/* the controls are centred on the input's line (WooCommerce inputs are 40px tall); the hint hangs under the label input */
			.woocommerce table.form-table .tpt-form-builder__table td.tpt-form-builder__col-move { padding: 19px 0 8px 6px; text-align: center; }
			.woocommerce table.form-table .tpt-form-builder__table th.tpt-form-builder__col-required,
			.woocommerce table.form-table .tpt-form-builder__table td.tpt-form-builder__col-required { text-align: center; }
			.woocommerce table.form-table .tpt-form-builder__table td.tpt-form-builder__col-required { padding-top: 17px; }
			.woocommerce table.form-table .tpt-form-builder__table td.tpt-form-builder__col-remove { padding: 19px 6px 8px 0; text-align: center; }
			.woocommerce table.form-table .tpt-form-builder__label,
			.woocommerce table.form-table .tpt-form-builder__type,
			.woocommerce table.form-table .tpt-form-builder__options { width: 100% !important; max-width: none; min-width: 0; margin: 0; box-sizing: border-box; }
			.woocommerce table.form-table .tpt-form-builder__options { display: block; margin-top: 6px; }
			.woocommerce table.form-table .tpt-form-builder__options[hidden] { display: none; }
			.woocommerce table.form-table .tpt-form-builder__table input[type="checkbox"] { margin: 0; }
			.tpt-form-builder__move { display: block; border: 0; background: none; color: #a7aaad; cursor: pointer; padding: 0 4px; line-height: 1; font-size: 9px; }
			.tpt-form-builder__move:hover { color: #2271b1; }
			.tpt-form-builder__hint { display: block; margin-top: 3px; color: #646970; font-size: 12px; }
			.tpt-form-builder__remove { border: 0; background: none; color: #b32d2e; font-size: 18px; cursor: pointer; line-height: 1; padding: 0; }
			.tpt-form-builder__remove:hover { color: #8a1f28; }
			.tpt-form-builder__actions { display: flex; gap: 10px; align-items: center; margin: 0 0 8px; }
			.woocommerce table.form-table .tpt-form-builder__preset { width: 240px; }
			.woocommerce table.form-table .tpt-form-builder .description { margin: 0; }
			.tpt-form-builder__row--flash td { background: #fcf9e8; }
		</style>
		<script>
			( function () {
				document.querySelectorAll( '.tpt-form-builder' ).forEach( function ( builder ) {
					var tbody = builder.querySelector( 'tbody' );
					var template = builder.querySelector( '.tpt-form-builder__template' );
					var counter = 0;

					function addRow( label, type ) {
						var html = template.innerHTML.replace( /__index__/g, 'new_' + ( ++counter ) + '_' + Date.now() );
						var wrap = document.createElement( 'tbody' );
						wrap.innerHTML = html;
						var row = wrap.querySelector( 'tr' );
						row.querySelector( '.tpt-form-builder__label' ).value = label || '';
						row.querySelector( '.tpt-form-builder__type' ).value = type || 'text';
						tbody.appendChild( row );
						syncRow( row );
						row.classList.add( 'tpt-form-builder__row--flash' );
						setTimeout( function () { row.classList.remove( 'tpt-form-builder__row--flash' ); }, 900 );
						row.querySelector( '.tpt-form-builder__label' ).focus();
						markChanged( row );
					}

					function syncRow( row ) {
						var isSelect = row.querySelector( '.tpt-form-builder__type' ).value === 'select';
						row.querySelector( '.tpt-form-builder__options' ).hidden = ! isSelect;
					}

					function markChanged( el ) {
						// WooCommerce enables "Save changes" on input/change events
						el.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					}

					builder.querySelector( '.tpt-form-builder__add' ).addEventListener( 'click', function () {
						addRow( '', 'text' );
					} );

					builder.querySelector( '.tpt-form-builder__preset' ).addEventListener( 'change', function () {
						var option = this.options[ this.selectedIndex ];
						if ( ! option.value ) { return; }
						// the same common field can be added again (with another label); only the first copy keeps
						// the recognised key that maps to the customer record, the others get a key from their label
						var taken = Array.prototype.some.call( tbody.querySelectorAll( 'input[name$="[key]"]' ), function ( i ) { return i.value === option.value; } );
						addRow( option.dataset.label, option.dataset.type );
						if ( ! taken ) {
							tbody.lastElementChild.querySelector( 'input[name$="[key]"]' ).value = option.value;
						}
						this.value = '';
					} );

					tbody.addEventListener( 'change', function ( event ) {
						if ( event.target.classList.contains( 'tpt-form-builder__type' ) ) {
							syncRow( event.target.closest( 'tr' ) );
						}
					} );

					tbody.addEventListener( 'click', function ( event ) {
						var button = event.target.closest( 'button' );
						if ( ! button ) { return; }
						var row = button.closest( 'tr' );

						if ( button.classList.contains( 'tpt-form-builder__remove' ) ) {
							row.remove();
							markChanged( tbody );
						} else if ( button.classList.contains( 'tpt-form-builder__move' ) ) {
							if ( button.dataset.dir === 'up' && row.previousElementSibling ) {
								tbody.insertBefore( row, row.previousElementSibling );
							} else if ( button.dataset.dir === 'down' && row.nextElementSibling ) {
								tbody.insertBefore( row.nextElementSibling, row );
							}
							markChanged( tbody );
						}
					} );
				} );
			} )();
		</script>
		<?php
	}
}
