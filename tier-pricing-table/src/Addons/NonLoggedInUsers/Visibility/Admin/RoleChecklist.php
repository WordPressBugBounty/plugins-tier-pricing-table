<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;

/**
 * The roles checklist under a visibility mode select, shown only while the mode names roles.
 */
class RoleChecklist {

	/**
	 * @param  string  $name  input name (posted as an array)
	 * @param  string[]  $selected
	 * @param  string  $modeFieldId  id of the mode select that controls the checklist
	 * @param  bool  $termForm  true for the taxonomy screens (table-row markup), false for the product panel
	 */
	public static function render( string $name, array $selected, string $modeFieldId, bool $termForm = false ) {
		$options = VisibilityMeta::getRoleOptions();
		$id      = $name . '_list';
		?>
		<?php if ( $termForm ) : ?>
			<tr class="form-field tpt-visibility-roles" data-mode-field="<?php echo esc_attr( $modeFieldId ); ?>">
				<th scope="row"><?php esc_html_e( 'Roles', 'tier-pricing-table' ); ?></th>
				<td>
		<?php else : ?>
			<p class="form-field tpt-visibility-roles" data-mode-field="<?php echo esc_attr( $modeFieldId ); ?>">
				<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Roles', 'tier-pricing-table' ); ?></label>
		<?php endif; ?>
				<span id="<?php echo esc_attr( $id ); ?>" class="tpt-visibility-roles__list">
					<?php foreach ( $options as $role => $label ) : ?>
						<label class="tpt-visibility-roles__item">
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $role ); ?>" <?php checked( in_array( $role, $selected, true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</span>
		<?php if ( $termForm ) : ?>
					<p class="description"><?php esc_html_e( 'The rule also covers the child categories. A product can override it in its Tiered Pricing tab.', 'tier-pricing-table' ); ?></p>
				</td>
			</tr>
		<?php else : ?>
			</p>
		<?php endif; ?>
		<?php self::printAssets(); ?>
		<?php
	}

	/**
	 * The styles and the show/hide script, once per page.
	 */
	public static function printAssets() {
		static $printed = false;

		if ( $printed ) {
			return;
		}

		$printed = true;
		?>
		<style>
			.tpt-visibility-roles__list { display: inline-flex; flex-direction: column; gap: 4px; vertical-align: top; }
			.tpt-visibility-roles__item { display: block !important; margin: 0 !important; float: none !important; width: auto !important; font-weight: normal; }
			.tpt-visibility-roles__item input { margin: 0 6px 0 0 !important; }
		</style>
		<script>
			( function () {
				function sync( list ) {
					var select = document.getElementById( list.dataset.modeField );
					if ( ! select ) { return; }
					var namesRoles = select.value === <?php echo wp_json_encode( VisibilityRule::MODE_INCLUDE ); ?> || select.value === <?php echo wp_json_encode( VisibilityRule::MODE_EXCLUDE ); ?>;
					list.style.display = namesRoles ? '' : 'none';
				}
				document.querySelectorAll( '.tpt-visibility-roles' ).forEach( function ( list ) {
					var select = document.getElementById( list.dataset.modeField );
					if ( select ) { select.addEventListener( 'change', function () { sync( list ); } ); }
					sync( list );
				} );
			} )();
		</script>
		<?php
	}
}
