<?php namespace TierPricingTable\Addons\QuickOrder;

use TierPricingTable\Settings\Settings;
use WC_Admin_Settings;

/**
 * Registers the Quick Order settings tab and its admin actions.
 */
class QuickOrderSettings {

	const CREATE_PAGE_ACTION = 'tpt_quick_order_create_page';

	const PAGE_FIELD_TYPE = 'tpt_quick_order_page';

	const PREMIUM_NOTE_FIELD_TYPE = 'tpt_quick_order_roles_premium';

	public function __construct() {
		add_filter( 'tiered_pricing_table/settings/sections', function ( $sections ) {
			$sections[] = new QuickOrderSettingsSection();

			return $sections;
		}, 15 );

		// Right after Request a Quote: both are ordering tools.
		add_filter( 'tiered_pricing_table/settings/sections_order', function ( $order ) {
			$order['quick-order'] = 55;

			return $order;
		} );

		// "Create a page with the form" submits the settings form: the changes are saved first, then the page is made
		add_action( 'woocommerce_admin_field_' . self::PAGE_FIELD_TYPE, array( $this, 'renderPageField' ) );
		add_action( 'woocommerce_update_options_' . Settings::SETTINGS_PAGE, array( $this, 'createPageAfterSave' ), 20 );

		// "Chosen roles" is premium: in the free version it can be picked (a note explains it) but is not saved
		add_action( 'woocommerce_admin_field_' . self::PREMIUM_NOTE_FIELD_TYPE, array( $this, 'renderRolesPremiumNote' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . QuickOrderSettingsSection::getOptionID( 'audience' ), function ( $value ) {
			if ( QuickOrderSettingsSection::AUDIENCE_ROLES !== $value || QuickOrderSettingsSection::canChooseRoles() ) {
				return $value;
			}

			$stored = get_option( QuickOrderSettingsSection::getOptionID( 'audience' ), false );

			return false !== $stored ? $stored : QuickOrderSettingsSection::getDefaultAudience();
		} );

		// The My Account tab is a rewrite endpoint: WooCommerce rebuilds the rules on its next request.
		$flush = function () {
			update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		};

		foreach ( array( 'my_account', 'enabled' ) as $option ) {
			add_action( 'update_option_' . QuickOrderSettingsSection::getOptionID( $option ), $flush );
			add_action( 'add_option_' . QuickOrderSettingsSection::getOptionID( $option ), $flush );
		}
		add_action( 'update_option_' . Settings::SETTINGS_PREFIX . '_addon_' . QuickOrderAddon::SLUG, $flush );
	}

	/**
	 * The "Quick order page" row, drawn when the tab is shown (after a save in the same request, so a page made by
	 * that save already shows as "View"): a link to the page, or a button that saves the form and creates it.
	 */
	public function renderPageField( $field ) {
		$pageId = QuickOrderDisplay::getFormPage();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html( $field['title'] ?? '' ); ?></th>
			<td class="forminp forminp-<?php echo esc_attr( self::PAGE_FIELD_TYPE ); ?>">
				<?php if ( $pageId ) : ?>
					<a class="button" href="<?php echo esc_url( get_permalink( $pageId ) ); ?>">
						<?php
						/* translators: %s: page title */
						echo esc_html( sprintf( __( 'View "%s"', 'tier-pricing-table' ), get_the_title( $pageId ) ) );
						?>
					</a>
				<?php else : ?>
					<button type="submit" name="save" value="<?php echo esc_attr( self::CREATE_PAGE_ACTION ); ?>" class="button" data-tpt-save-and-create>
						<?php esc_html_e( 'Create a page with the form', 'tier-pricing-table' ); ?>
					</button>
					<p class="description"><?php esc_html_e( 'Saves your changes, then publishes a page with the form.', 'tier-pricing-table' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Free version: in place of the roles list, a note that "Chosen roles" is premium. The disabled input only lets
	 * the settings script show the row while "Chosen roles" is picked; it is never submitted.
	 */
	public function renderRolesPremiumNote( $field ) {
		$savedAsRoles = QuickOrderSettingsSection::AUDIENCE_ROLES === QuickOrderSettingsSection::getStoredAudience();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html( $field['title'] ?? '' ); ?></th>
			<td class="forminp forminp-<?php echo esc_attr( self::PREMIUM_NOTE_FIELD_TYPE ); ?>">
				<input type="hidden" name="<?php echo esc_attr( $field['id'] ?? '' ); ?>" value="" disabled>
				<span style="display: block; max-width: 520px; padding: 10px 12px; border-left: 4px solid #3858e9; border-radius: 2px; background: #f0f6fc; line-height: 1.5;">
					<strong><?php esc_html_e( 'Chosen roles is a premium feature.', 'tier-pricing-table' ); ?></strong>
					<?php
					if ( $savedAsRoles ) {
						esc_html_e( 'Upgrade to limit the form to wholesale accounts or other roles you choose. Until then, every logged-in customer can use it.', 'tier-pricing-table' );
					} else {
						esc_html_e( 'Upgrade to limit the form to wholesale accounts or other roles you choose. Without it, this choice is not saved.', 'tier-pricing-table' );
					}
					?>
					<a href="<?php echo esc_url( tpt_fs_activation_url() ); ?>" target="_blank" style="font-weight: 600;">
						<?php esc_html_e( 'Upgrade to premium', 'tier-pricing-table' ); ?>
					</a>
				</span>
			</td>
		</tr>
		<?php
	}

	/**
	 * After the settings are saved by the "Create a page with the form" button.
	 */
	public function createPageAfterSave() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC_Admin_Settings::save() checked the woocommerce-settings nonce
		$button = isset( $_POST['save'] ) ? sanitize_text_field( wp_unslash( $_POST['save'] ) ) : '';

		if ( self::CREATE_PAGE_ACTION !== $button ) {
			return;
		}

		if ( ! current_user_can( 'publish_pages' ) ) {
			WC_Admin_Settings::add_error( __( 'Your settings were saved, but you are not allowed to create pages.', 'tier-pricing-table' ) );

			return;
		}

		$pageId = self::createFormPage();

		if ( $pageId ) {
			/* translators: %s: page title */
			WC_Admin_Settings::add_message( sprintf( __( 'Page "%s" created.', 'tier-pricing-table' ), get_the_title( $pageId ) ) );
		} else {
			WC_Admin_Settings::add_error( __( 'The page could not be created.', 'tier-pricing-table' ) );
		}
	}

	/**
	 * A published page holding the Quick Order Form block, remembered as the form's page. An existing one is kept.
	 */
	public static function createFormPage(): int {
		$pageId = QuickOrderDisplay::getFormPage();

		if ( $pageId ) {
			return $pageId;
		}

		$pageId = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => __( 'Quick order', 'tier-pricing-table' ),
			'post_content' => '<!-- wp:' . QuickOrderDisplay::BLOCK . ' {"align":"wide"} /-->',
		) );

		if ( ! $pageId || is_wp_error( $pageId ) ) {
			return 0;
		}

		update_option( QuickOrderSettingsSection::getOptionID( 'page' ), (int) $pageId, false );

		return (int) $pageId;
	}
}
