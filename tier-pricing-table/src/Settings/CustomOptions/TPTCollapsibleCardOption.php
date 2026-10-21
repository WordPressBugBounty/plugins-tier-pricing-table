<?php namespace TierPricingTable\Settings\CustomOptions;

/**
 * A collapsible card around a group of settings, in the layout configurator's visual language.
 *
 * Two field types wrap the group: `tpt_card_open` (title, desc, id, open) and `tpt_card_close`. The card
 * is a native <details> element, closed by default, so it needs no script and keeps the fields inside
 * it in the settings form.
 */
class TPTCollapsibleCardOption {

	const OPEN_FIELD_TYPE = 'tpt_card_open';

	const CLOSE_FIELD_TYPE = 'tpt_card_close';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::OPEN_FIELD_TYPE, array( $this, 'renderOpen' ) );
		add_action( 'woocommerce_admin_field_' . self::CLOSE_FIELD_TYPE, array( $this, 'renderClose' ) );
	}

	public function renderOpen( $value ) {
		// plugin-defined texts (settings arrays), read into locals for output
		$id    = (string) ( $value['id'] ?? '' );
		$title = (string) ( $value['title'] ?? '' );
		$desc  = (string) ( $value['desc'] ?? '' );
		$open  = ! empty( $value['open'] );
		?>
		<details class="tpt-settings-card" id="<?php echo esc_attr( $id ); ?>" <?php if ( $open ) : ?>open<?php endif; ?>>
			<summary class="tpt-settings-card__head">
				<span class="tpt-settings-card__heading">
					<span class="tpt-settings-card__title"><?php echo esc_html( $title ); ?></span>
					<?php if ( $desc ) : ?>
						<span class="tpt-settings-card__hint"><?php echo esc_html( $desc ); ?></span>
					<?php endif; ?>
				</span>
				<span class="tpt-settings-card__chevron" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="M7.4 9.2 12 13.7l4.6-4.5 1.4 1.4-6 6-6-6z" fill="currentColor"/></svg>
				</span>
			</summary>
			<div class="tpt-settings-card__body">
		<?php
	}

	public function renderClose( $value ) {
		?>
			</div>
		</details>
		<?php
	}
}
