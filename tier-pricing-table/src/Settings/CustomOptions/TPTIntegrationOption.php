<?php namespace TierPricingTable\Settings\CustomOptions;

use TierPricingTable\Admin\PluginInstaller\PluginInstaller;

class TPTIntegrationOption {
	
	const FIELD_TYPE = 'tpt_integration_option';

	/**
	 * The U2Code plugins panel: one field that shows every U2Code plugin as a tile.
	 */
	const PANEL_TYPE = 'tpt_integration_u2code_panel';

	/**
	 * The switch of an integration shown inside the panel: saved like FIELD_TYPE, drawn by the panel.
	 */
	const PANEL_SWITCH_TYPE = 'tpt_integration_option_in_panel';
	
	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
		add_action( 'woocommerce_admin_field_' . self::PANEL_TYPE, array( $this, 'renderPanel' ) );
		
		add_action( 'woocommerce_admin_settings_sanitize_option', function ( $value, $option, $rawValue ) {
			
			if ( in_array( $option['type'], array( self::FIELD_TYPE, self::PANEL_SWITCH_TYPE ), true ) ) {
				$value = in_array( $value, array( 1, 'yes' ) ) ? 'yes' : 'no';
			}
			
			return $value;
		}, 10, 3 );
	}
	
	/**
	 * One compact card: the plugin's logo, its name (linked to the plugin), what the integration does and a switch
	 * on the right, as on the Modules screen. A U2Code plugin (install_slug) also gets a one-click install button,
	 * or "Active" and a link to its settings; a card without an id is a recommendation with no switch.
	 */
	public function render( $value ) {
		$value = wp_parse_args( $value, array(
			'id'           => '',
			'title'        => $value['name'] ?? '',
			'desc'         => '',
			'default'      => '',
			'icon_url'     => '',
			'author_url'   => '',
			'official'     => false,
			'action_links' => array(),
			'meta_pills'   => array(),
			'install_slug' => '',
		) );

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		// read once for output (plugin-defined settings arrays)
		$isOn        = 'yes' === $value['value'];
		$fieldId     = (string) $value['id'];
		$title       = (string) $value['title'];
		$description = (string) $value['desc'];
		$iconUrl     = (string) $value['icon_url'];
		$authorUrl   = (string) $value['author_url'];
		$isOfficial  = ! empty( $value['official'] );
		$actionLinks = is_array( $value['action_links'] ) ? $value['action_links'] : array();
		$metaPills   = is_array( $value['meta_pills'] ) ? $value['meta_pills'] : array();
		$hasSwitch   = '' !== $fieldId;
		$install     = null;

		$promoted = PluginInstaller::plugins()[ (string) $value['install_slug'] ] ?? null;

		if ( $promoted ) {
			$install     = PluginInstaller::describe( $promoted );
			$actionLinks = self::getPromotedLinks( $install );
		}
		?>
		<tr class="tpt-integration-item <?php echo $isOfficial ? 'tpt-integration-item--official' : ''; ?>">
			<td>
				<div class="tpt-integration-wrapper <?php echo $isOfficial ? 'tpt-integration-wrapper--official' : ''; ?> <?php echo $hasSwitch ? '' : 'tpt-integration-wrapper--promo'; ?>">
					<span class="tpt-integration-item__image">
						<img src="<?php echo esc_url( $iconUrl ); ?>" alt="">
					</span>

					<div class="tpt-integration-item__description">
						<h4 id="<?php echo esc_attr( $fieldId ); ?>-title">
							<?php if ( $authorUrl ) : ?>
								<a target="_blank" rel="noopener" href="<?php echo esc_url( $authorUrl ); ?>"><?php echo esc_html( $title ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $title ); ?>
							<?php endif; ?>
						</h4>

						<?php if ( $metaPills ) : ?>
							<div class="tpt-integration-item__pills">
								<?php foreach ( $metaPills as $pill ) : ?>
									<span class="tpt-integration-item__pill"><?php echo esc_html( $pill ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<p class="description" id="<?php echo esc_attr( $fieldId ); ?>-description">
							<?php echo wp_kses_post( $description ); // nosemgrep ?>
						</p>

						<?php if ( $actionLinks || $install ) : ?>
							<div class="tpt-integration-item__action-links">
								<?php
								if ( $install ) {
									PluginInstaller::renderButton( $install, array( 'class' => 'button' ) );
								}
								?>
								<?php foreach ( $actionLinks as $actionLink ) : ?>
									<?php
									if ( empty( $actionLink['url'] ) || empty( $actionLink['label'] ) ) {
										continue;
									}

									$target = $actionLink['target'] ?? '_blank';
									?>
									<a class="tpt-integration-item__action-link"
									   target="<?php echo esc_attr( $target ); ?>"
										<?php echo '_blank' === $target ? 'rel="noopener"' : ''; ?>
									   href="<?php echo esc_url( $actionLink['url'] ); ?>">
										<?php echo esc_html( $actionLink['label'] ); ?> &rarr;
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $hasSwitch ) : ?>
					<div class="tpt-integration-item-checkbox">
						<input name="<?php echo esc_attr( $fieldId ); ?>"
							   id="<?php echo esc_attr( $fieldId ); ?>"
							   type="checkbox"
							   value="1"
							   class="tpt-toggle-switch"
							   aria-describedby="<?php echo esc_attr( $fieldId ); ?>-description"
							<?php checked( $isOn ); ?>>
						<label for="<?php echo esc_attr( $fieldId ); ?>">
							<span class="screen-reader-text"><?php echo esc_html( $title ); ?></span>
						</label>
					</div>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * After the install button: "Settings" once the plugin is active and has a settings page, "Learn more" before.
	 *
	 * @param  array  $install  From PluginInstaller::describe().
	 *
	 * @return array<int, array{url: string, label: string, target?: string}>
	 */
	protected static function getPromotedLinks( array $install ): array {
		if ( $install['active'] && $install['settings_url'] ) {
			return array( array( 'url' => $install['settings_url'], 'label' => __( 'Settings', 'tier-pricing-table' ), 'target' => '_self' ) );
		}

		return array( array( 'url' => self::withUtm( $install['site_url'] ), 'label' => __( 'Learn more', 'tier-pricing-table' ) ) );
	}

	/**
	 * The U2Code plugins in a panel of their own: a heading, then one tile per plugin with its logo, the switch of
	 * its integration (when it has one), its name, one sentence and the install button. The switches keep the
	 * names of their settings, which are saved through PANEL_SWITCH_TYPE fields.
	 *
	 * @param  array  $value  title, desc and plugins (integration settings arrays).
	 */
	public function renderPanel( $value ) {
		$title   = (string) ( $value['title'] ?? '' );
		$intro   = (string) ( $value['desc'] ?? '' );
		$plugins = isset( $value['plugins'] ) && is_array( $value['plugins'] ) ? $value['plugins'] : array();

		PluginInstaller::enqueueSlider();
		?>
		<tr class="tpt-u2code-row">
			<td colspan="2">
				<section class="tpt-u2code" aria-labelledby="tpt-u2code-title" data-tpt-slider data-tpt-slider-step="page">
					<div class="tpt-u2code__head">
						<div>
							<h2 id="tpt-u2code-title"><?php echo esc_html( $title ); ?></h2>
							<?php if ( $intro ) : ?>
								<p><?php echo esc_html( $intro ); ?></p>
							<?php endif; ?>
						</div>

						<div class="tpt-u2code__nav">
							<button type="button" class="tpt-u2code__arrow" data-tpt-slider-prev aria-label="<?php esc_attr_e( 'Previous plugins', 'tier-pricing-table' ); ?>">
								<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M10 3L5 8l5 5"/></svg>
							</button>
							<button type="button" class="tpt-u2code__arrow" data-tpt-slider-next aria-label="<?php esc_attr_e( 'Next plugins', 'tier-pricing-table' ); ?>">
								<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M6 3l5 5-5 5"/></svg>
							</button>
						</div>
					</div>

					<ul class="tpt-u2code__grid" data-tpt-slider-track tabindex="0" aria-labelledby="tpt-u2code-title">
						<?php foreach ( $plugins as $plugin ) : ?>
							<?php
							$fieldId   = (string) ( $plugin['id'] ?? '' );
							$name      = (string) ( $plugin['title'] ?? '' );
							$authorUrl = (string) ( $plugin['author_url'] ?? '' );
							$promoted  = PluginInstaller::plugins()[ (string) ( $plugin['install_slug'] ?? '' ) ] ?? null;
							$install   = $promoted ? PluginInstaller::describe( $promoted ) : null;
							$links     = $install ? self::getPromotedLinks( $install ) : (array) ( $plugin['action_links'] ?? array() );
							$isOn      = $fieldId && 'yes' === \WC_Admin_Settings::get_option( $fieldId, $plugin['default'] ?? 'yes' );
							?>
							<li class="tpt-u2code__tile">
								<div class="tpt-u2code__top">
									<img class="tpt-u2code__icon" src="<?php echo esc_url( (string) ( $plugin['icon_url'] ?? '' ) ); ?>" alt="">

									<?php if ( $fieldId ) : ?>
										<span class="tpt-u2code__switch tpt-integration-item-checkbox">
											<input name="<?php echo esc_attr( $fieldId ); ?>"
												   id="<?php echo esc_attr( $fieldId ); ?>"
												   type="checkbox"
												   value="1"
												   class="tpt-toggle-switch"
												<?php checked( $isOn ); ?>>
											<label for="<?php echo esc_attr( $fieldId ); ?>">
												<span class="screen-reader-text">
													<?php
													/* translators: %s: plugin name */
													echo esc_html( sprintf( __( 'Integration with %s', 'tier-pricing-table' ), $name ) );
													?>
												</span>
											</label>
										</span>
									<?php endif; ?>
								</div>

								<h3>
									<?php if ( $authorUrl ) : ?>
										<a href="<?php echo esc_url( $authorUrl ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $name ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $name ); ?>
									<?php endif; ?>
								</h3>

								<p><?php echo wp_kses_post( (string) ( $plugin['desc'] ?? '' ) ); // nosemgrep ?></p>

								<div class="tpt-u2code__actions">
									<?php
									if ( $install ) {
										PluginInstaller::renderButton( $install, array( 'class' => 'button' ) );
									}
									?>
									<?php foreach ( $links as $link ) : ?>
										<?php
										if ( empty( $link['url'] ) || empty( $link['label'] ) ) {
											continue;
										}

										$target = $link['target'] ?? '_blank';
										?>
										<a class="tpt-u2code__link" href="<?php echo esc_url( $link['url'] ); ?>"
										   target="<?php echo esc_attr( $target ); ?>"
											<?php echo '_blank' === $target ? 'rel="noopener"' : ''; ?>>
											<?php echo esc_html( $link['label'] ); ?> &rarr;
										</a>
									<?php endforeach; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</td>
		</tr>
		<?php
	}

	/**
	 * A plugin's own site, tagged as a visit from this screen (WordPress.org links stay as they are).
	 */
	protected static function withUtm( string $url ): string {
		if ( ! $url || false !== strpos( $url, 'wordpress.org' ) ) {
			return $url;
		}

		return add_query_arg( array(
			'utm_source'   => 'tiered-pricing-table',
			'utm_medium'   => 'plugin',
			'utm_campaign' => 'integrations-page',
		), $url );
	}
}
