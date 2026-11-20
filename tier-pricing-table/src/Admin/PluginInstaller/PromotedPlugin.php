<?php namespace TierPricingTable\Admin\PluginInstaller;

/**
 * Another U2Code plugin offered from the getting-started page and the Integrations screen: its name, icon,
 * WordPress.org slug, how to tell it is active, and the action the current user may take (install it from
 * WordPress.org, activate the installed copy, or open its WordPress.org page).
 */
class PromotedPlugin {

	/**
	 * @var array{slug: string, name: string, header_names: string[], is_active: callable, icon: string, site_url: string, wporg_url: string, settings_url: string|callable}
	 */
	protected $args;

	/**
	 * @param  array  $args  slug (WordPress.org), name, header_names (names the installed copy may carry), is_active
	 *                       (callable), icon (asset path), site_url, wporg_url (optional), settings_url (optional,
	 *                       a string or a callable that returns it)
	 */
	public function __construct( array $args ) {
		$this->args = array_merge( array(
			'slug'         => '',
			'name'         => '',
			'header_names' => array(),
			'is_active'    => '__return_false',
			'icon'         => '',
			'site_url'     => '',
			'wporg_url'    => '',
			'settings_url' => '',
		), $args );

		if ( ! $this->args['wporg_url'] ) {
			$this->args['wporg_url'] = 'https://wordpress.org/plugins/' . $this->args['slug'] . '/';
		}

		if ( ! $this->args['site_url'] ) {
			$this->args['site_url'] = $this->args['wporg_url'];
		}

		if ( ! $this->args['header_names'] ) {
			$this->args['header_names'] = array( $this->args['name'] );
		}
	}

	public function getSlug(): string {
		return (string) $this->args['slug'];
	}

	public function getName(): string {
		return (string) $this->args['name'];
	}

	public function getIconAsset(): string {
		return (string) $this->args['icon'];
	}

	public function getSiteURL(): string {
		return (string) $this->args['site_url'];
	}

	public function getWporgURL(): string {
		return (string) $this->args['wporg_url'];
	}

	/**
	 * Where its settings are, once it is active ('' when it has no page of its own).
	 */
	public function getSettingsURL(): string {
		$url = $this->args['settings_url'];

		return (string) ( is_callable( $url ) ? call_user_func( $url ) : $url );
	}

	public function isActive(): bool {
		return (bool) call_user_func( $this->args['is_active'] );
	}

	/**
	 * The plugin file of an installed copy, or null when none is installed.
	 */
	public function findInstalledPluginFile(): ?string {
		if ( ! function_exists( 'get_plugins' ) ) {
			if ( ! defined( 'ABSPATH' ) ) {
				return null;
			}
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$slug  = $this->getSlug();
		$names = (array) $this->args['header_names'];

		foreach ( (array) get_plugins() as $file => $data ) {
			if ( 0 === strpos( $file, $slug . '/' ) || in_array( (string) ( $data['Name'] ?? '' ), $names, true ) ) {
				return (string) $file;
			}
		}

		return null;
	}

	/**
	 * Install it from WordPress.org, activate the installed copy, or open its WordPress.org page when the user may
	 * do neither.
	 *
	 * @return array{kind: string, url: string, label: string, external: bool}
	 */
	public function getInstallAction(): array {
		$installed = $this->findInstalledPluginFile();

		if ( $installed && current_user_can( 'activate_plugins' ) ) {
			return array(
				'kind'     => 'activate',
				'url'      => wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $installed ) ), 'activate-plugin_' . $installed ),
				'label'    => __( 'Activate now', 'tier-pricing-table' ),
				'external' => false,
			);
		}

		if ( ! $installed && current_user_can( 'install_plugins' ) ) {
			$slug = $this->getSlug();

			return array(
				'kind'     => 'install',
				'url'      => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $slug ), 'install-plugin_' . $slug ),
				'label'    => __( 'Install now, free', 'tier-pricing-table' ),
				'external' => false,
			);
		}

		return array(
			'kind'     => 'wporg',
			'url'      => $this->getWporgURL(),
			'label'    => __( 'Get it free on WordPress.org', 'tier-pricing-table' ),
			'external' => true,
		);
	}
}
