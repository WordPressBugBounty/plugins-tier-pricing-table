<?php namespace TierPricingTable\Addons\NonLoggedInUsers\Visibility\Admin;

use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityMeta;
use TierPricingTable\Addons\NonLoggedInUsers\Visibility\VisibilityRule;
use WP_Term;

/**
 * "Who can see this category" on the product category screens.
 */
class CategoryVisibilityFields {

	const NONCE = 'tpt_visibility_category';

	public function __construct() {
		add_action( 'product_cat_add_form_fields', array( $this, 'renderAdd' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'renderEdit' ) );
		add_action( 'created_product_cat', array( $this, 'save' ) );
		add_action( 'edited_product_cat', array( $this, 'save' ) );
	}

	public function renderAdd() {
		$rule = VisibilityRule::normalize( VisibilityRule::MODE_EVERYONE, array() );

		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( VisibilityMeta::TERM_MODE ); ?>"><?php esc_html_e( 'Who can see this category', 'tier-pricing-table' ); ?></label>
			<?php $this->modeSelect( $rule['mode'] ); ?>
			<p><?php esc_html_e( 'Hidden categories and their products leave the shop, search and menus. Shop managers always see everything.', 'tier-pricing-table' ); ?></p>
		</div>
		<div class="form-field tpt-visibility-roles" data-mode-field="<?php echo esc_attr( VisibilityMeta::TERM_MODE ); ?>">
			<label><?php esc_html_e( 'Roles', 'tier-pricing-table' ); ?></label>
			<?php foreach ( VisibilityMeta::getRoleOptions() as $role => $label ) : ?>
				<label class="tpt-visibility-roles__item"><input type="checkbox" name="<?php echo esc_attr( VisibilityMeta::TERM_ROLES ); ?>[]" value="<?php echo esc_attr( $role ); ?>"> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</div>
		<?php
		RoleChecklist::printAssets();
	}

	public function renderEdit( WP_Term $term ) {
		$rule = VisibilityMeta::getCategoryRule( (int) $term->term_id );

		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( VisibilityMeta::TERM_MODE ); ?>"><?php esc_html_e( 'Who can see this category', 'tier-pricing-table' ); ?></label></th>
			<td>
				<?php $this->modeSelect( $rule['mode'] ); ?>
				<p class="description"><?php esc_html_e( 'Hidden categories and their products leave the shop, search and menus. Shop managers always see everything.', 'tier-pricing-table' ); ?></p>
			</td>
		</tr>
		<?php
		RoleChecklist::render( VisibilityMeta::TERM_ROLES, $rule['roles'], VisibilityMeta::TERM_MODE, true );
	}

	protected function modeSelect( string $current ) {
		?>
		<select name="<?php echo esc_attr( VisibilityMeta::TERM_MODE ); ?>" id="<?php echo esc_attr( VisibilityMeta::TERM_MODE ); ?>">
			<?php foreach ( VisibilityMeta::getModeOptions( false ) as $mode => $label ) : ?>
				<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $current, $mode ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function save( $termId ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		$mode  = isset( $_POST[ VisibilityMeta::TERM_MODE ] ) ? sanitize_key( wp_unslash( $_POST[ VisibilityMeta::TERM_MODE ] ) ) : VisibilityRule::MODE_EVERYONE;
		$roles = isset( $_POST[ VisibilityMeta::TERM_ROLES ] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST[ VisibilityMeta::TERM_ROLES ] ) ) : array();

		VisibilityMeta::saveCategoryRule( (int) $termId, $mode, $roles );
	}
}
