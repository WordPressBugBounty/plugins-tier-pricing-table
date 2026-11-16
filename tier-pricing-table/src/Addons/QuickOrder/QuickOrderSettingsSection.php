<?php namespace TierPricingTable\Addons\QuickOrder;

use TierPricingTable\Addons\NonLoggedInUsers\Roles;
use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Settings\Settings as WholesaleSettings;
use TierPricingTable\Settings\CustomOptions\TPTCheckboxListOption;
use TierPricingTable\Settings\CustomOptions\TPTDisplayType;
use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * The Quick Order settings tab. Listed only while the add-on is on, so stores that don't use the form never see it.
 */
class QuickOrderSettingsSection extends SectionAbstract {

	const AUDIENCE_EVERYONE  = 'everyone';
	const AUDIENCE_LOGGED_IN = 'logged-in';
	const AUDIENCE_ROLES     = 'roles';

	const START_SEARCH = 'search';
	const START_SHEET  = 'sheet';

	public static function getOptionID( string $option ): string {
		return Settings::SETTINGS_PREFIX . 'quick_order_' . $option;
	}

	public static function get( string $option, $default = null ) {
		return get_option( self::getOptionID( $option ), $default );
	}

	public function getName(): string {
		return __( 'Quick Order', 'tier-pricing-table' );
	}

	public function getSlug(): string {
		return 'quick-order';
	}

	public function getSettings(): array {
		return array(
			array(
				'title' => __( 'Quick order form', 'tier-pricing-table' ),
				'desc'  => __( 'A one-page order form for bulk and repeat buyers. Every line shows the price the customer gets at that quantity, with their role and customer prices, quantity steps and limits applied.',
					'tier-pricing-table' ),
				'type'  => 'title',
				'id'    => self::getOptionID( 'section_form' ),
			),
			array(
				'title'   => __( 'Enable the quick order form', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'enabled' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => self::getDefaultEnabled() ? 'yes' : 'no',
				'desc'    => __( 'Shows the form where you place it (a block, the shortcode or a page) and runs its product search and add to cart. Switch it on to see and set up the options below; nothing goes live until you save.',
					'tier-pricing-table' ),
			),
			array(
				'title'        => __( 'Who can use it', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'audience' ),
				'type'         => TPTDisplayType::FIELD_TYPE,
				'options'      => array(
					self::AUDIENCE_EVERYONE  => __( 'Everyone', 'tier-pricing-table' ),
					self::AUDIENCE_LOGGED_IN => __( 'Logged-in customers', 'tier-pricing-table' ),
					self::AUDIENCE_ROLES     => __( 'Chosen roles', 'tier-pricing-table' ),
				),
				'descriptions' => array(
					self::AUDIENCE_EVERYONE  => __( 'Guests too', 'tier-pricing-table' ),
					self::AUDIENCE_LOGGED_IN => __( 'Any account', 'tier-pricing-table' ),
					self::AUDIENCE_ROLES     => __( 'Wholesale accounts, for example', 'tier-pricing-table' ),
				),
				'default'      => self::getDefaultAudience(),
			),
			self::getRolesField(),
			array(
				'title'       => __( 'Message for everyone else', 'tier-pricing-table' ),
				'id'          => self::getOptionID( 'denied_message' ),
				'type'        => 'textarea',
				'default'     => '',
				'placeholder' => self::getDefaultDeniedMessage(),
				'css'         => 'width: 400px; height: 60px;',
				'desc'        => __( 'Shown in place of the form. Guests also get a link to log in. Leave empty for the message shown in the box.', 'tier-pricing-table' ),
				'desc_tip'    => true,
			),
			array(
				'title'        => __( 'The form starts with', 'tier-pricing-table' ),
				'id'           => self::getOptionID( 'start' ),
				'type'         => TPTDisplayType::FIELD_TYPE,
				'options'      => array(
					self::START_SEARCH => __( 'A search box', 'tier-pricing-table' ),
					self::START_SHEET  => __( 'An order sheet', 'tier-pricing-table' ),
				),
				'descriptions' => array(
					self::START_SEARCH => __( 'Buyers add the products they need', 'tier-pricing-table' ),
					self::START_SHEET  => __( 'Every product listed, buyers fill in quantities', 'tier-pricing-table' ),
				),
				'default'      => self::START_SEARCH,
			),
			array(
				'title'       => __( 'Products', 'tier-pricing-table' ),
				'id'          => self::getOptionID( 'categories' ),
				'type'        => TPTCheckboxListOption::FIELD_TYPE,
				'display'     => 'category-search',
				'placeholder' => __( 'All categories', 'tier-pricing-table' ),
				'default'     => '',
				'desc'        => __( 'Limit the form to these categories, for example your trade range. Leave empty for every product. A category covers its child categories.',
					'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Product details', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'columns' ),
				'type'    => TPTCheckboxListOption::FIELD_TYPE,
				'options' => array(
					'image' => __( 'Image', 'tier-pricing-table' ),
					'sku'   => __( 'SKU', 'tier-pricing-table' ),
					'stock' => __( 'Stock', 'tier-pricing-table' ),
				),
				'default' => 'image,sku',
			),
			array(
				'title'   => __( 'Paste a list or upload a CSV', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'paste' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Buyers paste "SKU, quantity" lines or upload a CSV file to fill in the form at once.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Next tier hint', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'next_tier' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'yes',
				'desc'    => __( 'Under a line: how many more units reach the next tier, and its price.', 'tier-pricing-table' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => self::getOptionID( 'section_form' ),
			),

			array(
				'title' => __( 'Where it shows', 'tier-pricing-table' ),
				'desc'  => self::getPlacementDescription(),
				'type'  => 'title',
				'id'    => self::getOptionID( 'section_placement' ),
			),
			self::getPageField(),
			array(
				'title'   => __( 'My Account tab', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'my_account' ),
				'type'    => TPTSwitchOption::FIELD_TYPE,
				'default' => 'no',
				'desc'    => __( 'A tab with the form in My Account, for the customers who can use it.', 'tier-pricing-table' ),
			),
			array(
				'title'   => __( 'Tab title', 'tier-pricing-table' ),
				'id'      => self::getOptionID( 'my_account_title' ),
				'type'    => 'text',
				'default' => __( 'Quick order', 'tier-pricing-table' ),
				'css'     => 'width: 240px;',
			),
			array(
				'type' => 'sectionend',
				'id'   => self::getOptionID( 'section_placement' ),
			),
		);
	}

	/**
	 * The roles list, or in the free version a note that "Chosen roles" is premium (the choice is not saved).
	 */
	protected static function getRolesField(): array {
		if ( ! self::canChooseRoles() ) {
			return array(
				'title'     => __( 'Roles', 'tier-pricing-table' ),
				'id'        => self::getOptionID( 'roles_premium' ),
				'type'      => QuickOrderSettings::PREMIUM_NOTE_FIELD_TYPE,
				'is_option' => false,
			);
		}

		return array(
			'title'   => __( 'Roles', 'tier-pricing-table' ),
			'id'      => self::getOptionID( 'roles' ),
			'type'    => TPTCheckboxListOption::FIELD_TYPE,
			'options' => self::getRoleOptions(),
			'default' => self::getDefaultRoles(),
			'desc'    => __( 'Customers with any of these roles can use the form.', 'tier-pricing-table' ),
		);
	}

	protected static function getPageField(): array {
		return array(
			'title'     => __( 'Quick order page', 'tier-pricing-table' ),
			'id'        => self::getOptionID( 'page_link' ),
			'type'      => QuickOrderSettings::PAGE_FIELD_TYPE,
			'is_option' => false,
		);
	}

	protected static function getPlacementDescription(): string {
		return sprintf(
			/* translators: 1: block name, 2: shortcode */
			__( 'Add the form to any page with the %1$s block or the %2$s shortcode, or create a page for it below.', 'tier-pricing-table' ),
			'"' . __( 'Quick Order Form', 'tier-pricing-table' ) . '"',
			'[' . QuickOrderDisplay::SHORTCODE . ']'
		);
	}

	/* --- values the form reads ------------------------------------------------------------------- */

	/**
	 * The form is off until the store switches it on, even with the module on: no page, no My Account tab, no
	 * live search or add to cart.
	 */
	public static function isEnabled(): bool {
		return 'yes' === self::get( 'enabled', self::getDefaultEnabled() ? 'yes' : 'no' );
	}

	/**
	 * Before the switch is saved, it is on for a store that already set the form up (a form page or the My
	 * Account tab), so such a store keeps working.
	 */
	protected static function getDefaultEnabled(): bool {
		return 'yes' === get_option( self::getOptionID( 'my_account' ) ) || (int) get_option( self::getOptionID( 'page' ), 0 ) > 0;
	}

	public static function getAudience(): string {
		$audience = self::getStoredAudience();

		// "Chosen roles" saved with a premium licence that is gone: logged-in customers, never everyone
		if ( self::AUDIENCE_ROLES === $audience && ! self::canChooseRoles() ) {
			return self::AUDIENCE_LOGGED_IN;
		}

		return $audience;
	}

	/**
	 * The saved choice, as it is (it may be "Chosen roles" without the premium version).
	 */
	public static function getStoredAudience(): string {
		$audience = (string) self::get( 'audience', self::getDefaultAudience() );

		return in_array( $audience, array( self::AUDIENCE_EVERYONE, self::AUDIENCE_LOGGED_IN, self::AUDIENCE_ROLES ), true ) ? $audience : self::AUDIENCE_EVERYONE;
	}

	/**
	 * Limiting the form to chosen roles needs the premium version.
	 */
	public static function canChooseRoles(): bool {
		return tpt_fs()->can_use_premium_code();
	}

	/**
	 * The roles that may use the form, when it is limited to chosen roles.
	 *
	 * @return string[]
	 */
	public static function getRoles(): array {
		return TPTCheckboxListOption::toArray( self::get( 'roles', self::getDefaultRoles() ) );
	}

	public static function getDeniedMessage(): string {
		$message = (string) self::get( 'denied_message', self::getDefaultDeniedMessage() );

		return '' !== trim( $message ) ? $message : self::getDefaultDeniedMessage();
	}

	public static function getStart(): string {
		return self::START_SHEET === self::get( 'start', self::START_SEARCH ) ? self::START_SHEET : self::START_SEARCH;
	}

	/**
	 * The categories the form is limited to (none: every product).
	 *
	 * @return int[]
	 */
	public static function getCategories(): array {
		return array_values( array_filter( array_map( 'intval', TPTCheckboxListOption::toArray( self::get( 'categories', '' ) ) ) ) );
	}

	/**
	 * The product details the lines show: image, sku, stock.
	 *
	 * @return string[]
	 */
	public static function getColumns(): array {
		return array_values( array_intersect( TPTCheckboxListOption::toArray( self::get( 'columns', 'image,sku' ) ), array( 'image', 'sku', 'stock' ) ) );
	}

	public static function isPasteEnabled(): bool {
		return 'yes' === self::get( 'paste', 'yes' );
	}

	public static function isNextTierEnabled(): bool {
		return 'yes' === self::get( 'next_tier', 'yes' );
	}

	public static function isMyAccountEnabled(): bool {
		return self::isEnabled() && 'yes' === self::get( 'my_account', 'no' );
	}

	public static function getMyAccountTitle(): string {
		$title = trim( (string) self::get( 'my_account_title', '' ) );

		return '' !== $title ? $title : __( 'Quick order', 'tier-pricing-table' );
	}

	/* --- defaults ---------------------------------------------------------------------------------- */

	/**
	 * A store that runs wholesale registration starts with the form for its wholesale accounts (in the free
	 * version, for logged-in customers: "Chosen roles" is premium).
	 */
	public static function getDefaultAudience(): string {
		if ( ! self::hasWholesaleRole() ) {
			return self::AUDIENCE_EVERYONE;
		}

		return self::canChooseRoles() ? self::AUDIENCE_ROLES : self::AUDIENCE_LOGGED_IN;
	}

	protected static function getDefaultRoles(): string {
		return self::hasWholesaleRole() ? WholesaleSettings::getRole() : '';
	}

	protected static function hasWholesaleRole(): bool {
		return class_exists( WholesaleSettings::class ) && WholesaleSettings::isEnabled() && '' !== WholesaleSettings::getRole();
	}

	/**
	 * The message that fits who can use the form.
	 */
	protected static function getDefaultDeniedMessage(): string {
		if ( self::AUDIENCE_LOGGED_IN === self::getAudience() ) {
			return __( 'Log in to use the quick order form.', 'tier-pricing-table' );
		}

		return __( 'The quick order form is available to business accounts.', 'tier-pricing-table' );
	}

	/**
	 * The roles a store can choose, without administrators.
	 *
	 * @return array<string, string>
	 */
	protected static function getRoleOptions(): array {
		$options = Roles::getOptions();

		unset( $options['administrator'] );

		return $options;
	}
}
