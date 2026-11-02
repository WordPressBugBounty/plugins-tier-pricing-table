<?php namespace TierPricingTable\Admin\PluginInstaller;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\Settings\Sections\Multicurrency\MulticurrencySection;
use TierPricingTable\Settings\Sections\PluginPairingSectionAbstract;
use TierPricingTable\Settings\Sections\ProductAddons\ProductAddonsSection;
use TierPricingTable\TierPricingTablePlugin;

/**
 * One-click install and activation of the other U2Code plugins (Multicurrency, Product Addons)
 * from the getting-started page and the pairing tabs. WordPress core installs the plugin from
 * WordPress.org through its own AJAX action; this class activates it through its own action, so
 * the page stays where it is. Users who may not install or activate plugins get a link instead.
 */
class PluginInstaller {

	const ACTIVATE_ACTION = 'tpt_activate_plugin';

	const NONCE = 'tpt_plugin_installer';

	const SCRIPT_HANDLE = 'tiered-pricing-table-plugin-installer';

	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTIVATE_ACTION, array( $this, 'activate' ) );
	}

	/**
	 * The pairing sections describe the plugins: name, icon, WordPress.org slug, how to detect them.
	 *
	 * @return array<string, PluginPairingSectionAbstract> Keyed by WordPress.org slug.
	 */
	public static function sections(): array {
		static $sections = null;

		if ( null === $sections ) {
			$sections = array();

			foreach ( array( new MulticurrencySection(), new ProductAddonsSection() ) as $section ) {
				$sections[ $section->getOwnPluginWporgSlug() ] = $section;
			}
		}

		return $sections;
	}

	/**
	 * The plugins to offer, with their state and the action the current user may take.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function getPlugins(): array {
		$texts = array(
			'u2code-product-multicurrency-for-woocommerce' => __( 'Sell in several currencies. Tier and role prices follow the exchange rate, and the order is placed in the shopper\'s currency.', 'tier-pricing-table' ),
			'u2code-product-addons-for-woocommerce'        => __( 'Paid options such as engraving or gift wrap, priced on top of the tier price with live totals.', 'tier-pricing-table' ),
		);

		$plugins = array();

		foreach ( self::sections() as $slug => $section ) {
			$plugins[] = self::describe( $section, $texts[ $slug ] ?? '' );
		}

		return $plugins;
	}

	/**
	 * @return array{slug: string, name: string, text: string, icon: string, site_url: string, active: bool, action: string, fallback_url: string, fallback_label: string}
	 */
	public static function describe( PluginPairingSectionAbstract $section, string $text = '' ): array {
		$active = $section->isOwnPluginActive();
		$action = $active ? array( 'kind' => '', 'url' => '', 'label' => '' ) : $section->getInstallAction();

		return array(
			'slug'           => $section->getOwnPluginWporgSlug(),
			'name'           => $section->getOwnPluginName(),
			'text'           => $text,
			'icon'           => ServiceContainer::getInstance()->getFileManager()->locateAsset( $section->getOwnPluginIconAsset() ),
			'site_url'       => $section->getOwnPluginSiteURL(),
			'active'         => $active,
			'action'         => (string) $action['kind'], // install | activate | wporg | '' when active
			'fallback_url'   => (string) $action['url'],
			'fallback_label' => (string) $action['label'],
		);
	}

	/**
	 * The control for one plugin: an "Active" mark, a one-click button, or a link for users who
	 * may neither install nor activate.
	 *
	 * @param  array  $plugin  From describe().
	 * @param  array  $args    class: CSS class of the button or link (default "button");
	 *                         reload: reload the page once the plugin is active (for screens whose
	 *                         content depends on it).
	 */
	public static function renderButton( array $plugin, array $args = array() ) {
		$class = $args['class'] ?? 'button';

		if ( ! empty( $plugin['active'] ) ) {
			?>
			<span class="tpt-install__state">
				<svg viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="10" fill="currentColor" opacity=".14"/><path d="M6 10.4l2.6 2.6L14 7.6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<?php esc_html_e( 'Active', 'tier-pricing-table' ); ?>
			</span>
			<?php
			return;
		}

		if ( in_array( $plugin['action'], array( 'install', 'activate' ), true ) ) {
			self::enqueue();

			$label = 'install' === $plugin['action']
				? __( 'Install and activate', 'tier-pricing-table' )
				: __( 'Activate now', 'tier-pricing-table' );
			?>
			<button type="button" class="<?php echo esc_attr( $class ); ?> tpt-install"
					data-slug="<?php echo esc_attr( $plugin['slug'] ); ?>"
					data-action="<?php echo esc_attr( $plugin['action'] ); ?>"
					data-fallback="<?php echo esc_url( $plugin['fallback_url'] ); ?>"
					<?php echo empty( $args['reload'] ) ? '' : 'data-reload="1"'; ?>>
				<?php echo esc_html( $label ); ?>
			</button>
			<?php
			return;
		}
		?>
		<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $plugin['fallback_url'] ); ?>" target="_blank" rel="noopener">
			<?php echo esc_html( $plugin['fallback_label'] ); ?>
		</a>
		<?php
	}

	public static function enqueue() {
		if ( wp_script_is( self::SCRIPT_HANDLE, 'enqueued' ) ) {
			return;
		}

		wp_enqueue_script( self::SCRIPT_HANDLE,
			ServiceContainer::getInstance()->getFileManager()->locateJSAsset( 'admin/plugin-installer' ),
			array( 'jquery' ), TierPricingTablePlugin::VERSION, true );

		wp_localize_script( self::SCRIPT_HANDLE, 'tptPluginInstaller', array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'updatesNonce' => wp_create_nonce( 'updates' ),
			'nonce'        => wp_create_nonce( self::NONCE ),
			'i18n'         => array(
				'installing' => __( 'Installing…', 'tier-pricing-table' ),
				'activating' => __( 'Activating…', 'tier-pricing-table' ),
				'active'     => __( 'Active', 'tier-pricing-table' ),
				'retry'      => __( 'Try again', 'tier-pricing-table' ),
				'failed'     => __( 'The plugin could not be installed from here.', 'tier-pricing-table' ),
				'fallback'   => __( 'Open the WordPress installer', 'tier-pricing-table' ),
			),
		) );
	}

	/**
	 * AJAX: activate an installed copy of one of the U2Code plugins.
	 */
	public function activate() {
		check_ajax_referer( self::NONCE );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to activate plugins.', 'tier-pricing-table' ) ), 403 );
		}

		$slug    = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$section = self::sections()[ $slug ] ?? null;

		if ( ! $section ) {
			wp_send_json_error( array( 'message' => __( 'Unknown plugin.', 'tier-pricing-table' ) ), 400 );
		}

		$file = $section->findInstalledPluginFile();

		if ( ! $file ) {
			wp_send_json_error( array( 'message' => __( 'The plugin is not installed.', 'tier-pricing-table' ) ), 404 );
		}

		$result = activate_plugin( $file );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 500 );
		}

		wp_send_json_success( array( 'name' => $section->getOwnPluginName() ) );
	}
}
