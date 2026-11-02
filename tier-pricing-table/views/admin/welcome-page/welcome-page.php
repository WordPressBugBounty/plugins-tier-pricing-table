<?php

	use TierPricingTable\Admin\PluginInstaller\PluginInstaller;

	/**
	 * Getting started page.
	 *
	 * @var bool $premium
	 * @var array[] $steps    id, title, text, button, url, done, optional?, disabled?, skipped?, skip_url?, undo_url?
	 * @var array[] $tips     title, text, url
	 * @var array[] $plugins  The other U2Code plugins, from PluginInstaller::getPlugins()
	 * @var string[] $links   docs, support, settings, changelog, review
	 */
	defined( 'ABSPATH' ) || die();

	$next = null;
	foreach ( $steps as $i => $step ) {
		if ( ! $step['done'] && empty( $step['disabled'] ) && null === $next ) {
			$next = $i;
		}
	}
	
	$total   = count( $steps );
	$done    = count( array_filter( $steps, function ( $step ) {
		return ! empty( $step['done'] );
	} ) );
	$left    = $total - $done;
	$percent = $total ? (int) round( $done / $total * 100 ) : 0;
?>
<style>
	.tpt-welcome { max-width: 1120px; margin: 24px auto 40px; color: #1d2327; font-size: 14px; line-height: 1.5; }
	.tpt-welcome a { text-decoration: none; }
	.tpt-welcome__head { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
	.tpt-welcome .wp-header-end { display: none; }
	.tpt-welcome .notice { margin: 0 0 16px; }
	.tpt-welcome__head-actions { margin-left: auto; flex: 0 0 auto; display: flex; gap: 8px; }
	.tpt-welcome__head h1 { margin: 0 0 4px; padding: 0; font-size: 24px; line-height: 1.25; font-weight: 600; }
	.tpt-welcome__head p { margin: 0; color: #50575e; font-size: 14px; }
	.tpt-welcome__ring { position: relative; width: 56px; height: 56px; flex: 0 0 56px; }
	.tpt-welcome__ring svg { width: 56px; height: 56px; transform: rotate(-90deg); }
	.tpt-welcome__ring circle { fill: none; stroke-width: 3.5; }
	.tpt-welcome__ring-track { stroke: #dcdcde; }
	.tpt-welcome__ring-bar { stroke: var(--wp-admin-theme-color, #2271b1); stroke-linecap: round; }
	.tpt-welcome__ring--complete .tpt-welcome__ring-bar { stroke: #00a32a; }
	.tpt-welcome__ring-value { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums; }
	.tpt-welcome__body { display: grid; grid-template-columns: minmax(0, 1fr) 320px; gap: 20px; align-items: start; }
	.tpt-welcome__card { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 6px 24px; margin-bottom: 16px; }
	.tpt-welcome__card h2 { font-size: 15px; font-weight: 600; margin: 0 -24px 16px; padding: 12px 24px; border-bottom: 1px solid #f0f0f1; }
	.tpt-welcome__steps { list-style: none; margin: 0; padding: 0; }
	.tpt-welcome__step { display: grid; grid-template-columns: 32px 1fr auto; gap: 14px; align-items: center; padding: 16px 0; border-top: 1px solid #f0f0f1; }
	.tpt-welcome__step:first-child { border-top: 0; }
	.tpt-welcome__mark { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; border: 2px solid #c3c4c7; color: #50575e; box-sizing: border-box; }
	.tpt-welcome__step--next .tpt-welcome__mark { border-color: var(--wp-admin-theme-color, #2271b1); color: var(--wp-admin-theme-color, #2271b1); }
	.tpt-welcome__step--done .tpt-welcome__mark { border-color: #00a32a; background: #00a32a; color: #fff; }
	.tpt-welcome__step--done .tpt-welcome__mark svg { width: 14px; height: 14px; }
	.tpt-welcome__step--skipped .tpt-welcome__mark { border-color: #a7aaad; background: #a7aaad; }
	.tpt-welcome__step h3 { margin: 0 0 2px; font-size: 14px; font-weight: 600; }
	.tpt-welcome__step h3 small { font-weight: 400; color: #787c82; margin-left: 6px; font-size: 12px; }
	.tpt-welcome__step p { margin: 0; color: #50575e; }
	.tpt-welcome__step--done h3 { color: #50575e; }
	.tpt-welcome__action { min-width: 150px; text-align: right; }
	.tpt-welcome__action .button { margin: 0; }
	.tpt-welcome__action span { color: #787c82; font-size: 12px; }
	.tpt-welcome__skip { margin-top: 6px; font-size: 12px; color: #787c82; }
	.tpt-welcome__skip a { color: #787c82; text-decoration: underline; }
	.tpt-welcome__skip a:hover { color: #1d2327; }
	.tpt-welcome__tips { list-style: none; margin: 0 0 16px; padding: 0; display: grid; grid-template-columns: 1fr; gap: 14px; }
	.tpt-welcome__tips li { padding: 0; }
	.tpt-welcome__tips a { font-weight: 600; display: block; margin-bottom: 2px; }
	.tpt-welcome__tips span { color: #50575e; font-size: 13px; }
	.tpt-welcome__plugins { list-style: none; margin: 0 0 18px; padding: 0; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 32px; align-items: stretch; }
	.tpt-welcome__plugins li { display: grid; grid-template-columns: 36px 1fr; grid-template-rows: auto 1fr auto; gap: 2px 12px; }
	.tpt-welcome__plugins img { grid-row: 1 / span 3; width: 36px; height: 36px; border-radius: 8px; }
	.tpt-welcome__plugins a.tpt-welcome__plugin-name { grid-column: 2; font-weight: 600; display: block; }
	.tpt-welcome__plugins p { grid-column: 2; margin: 0; color: #50575e; font-size: 13px; }
	.tpt-welcome__plugin-action { grid-column: 2; align-self: end; margin-top: 10px; min-height: 40px; display: flex; align-items: center; }
	.tpt-welcome__foot { display: flex; flex-wrap: wrap; gap: 8px 20px; padding: 4px 4px 0; color: #787c82; font-size: 13px; }
	@media (max-width: 1100px) { .tpt-welcome__body { grid-template-columns: 1fr; } .tpt-welcome__tips { grid-template-columns: repeat(2, 1fr); gap: 16px 32px; } }
	@media (max-width: 782px) { .tpt-welcome__step { grid-template-columns: 32px 1fr; } .tpt-welcome__action { grid-column: 2; text-align: left; } .tpt-welcome__tips { grid-template-columns: 1fr; } .tpt-welcome__plugins { grid-template-columns: 1fr; } }
</style>

<div class="wrap tpt-welcome">
	<div class="tpt-welcome__head">
		<div class="tpt-welcome__ring<?php echo $left ? '' : ' tpt-welcome__ring--complete'; ?>">
			<svg viewBox="0 0 36 36" aria-hidden="true" focusable="false">
				<circle class="tpt-welcome__ring-track" cx="18" cy="18" r="15.915"></circle>
				<?php if ( $percent > 0 ) : ?>
					<circle class="tpt-welcome__ring-bar" cx="18" cy="18" r="15.915" stroke-dasharray="<?php echo esc_attr( $percent ); ?> 100"></circle>
				<?php endif; ?>
			</svg>
			<span class="tpt-welcome__ring-value"><?php echo esc_html( $percent . '%' ); ?></span>
		</div>
		<div>
			<h1><?php esc_html_e( 'Getting started with Tiered Pricing Table', 'tier-pricing-table' ); ?></h1>
			<p>
				<?php if ( $left ) : ?>
					<?php
					echo esc_html( sprintf(
						/* translators: %1$d: steps done, %2$d: steps in total, %3$s: "N steps left" */
						__( '%1$d of %2$d steps done, %3$s', 'tier-pricing-table' ),
						$done,
						$total,
						/* translators: %d: steps not done yet */
						sprintf( _n( '%d step left', '%d steps left', $left, 'tier-pricing-table' ), $left )
					) );
					?>
				<?php else : ?>
					<?php /* translators: %d: steps in total */ echo esc_html( sprintf( __( 'All %d steps done', 'tier-pricing-table' ), $total ) ); ?>
				<?php endif; ?>
			</p>
		</div>
		<div class="tpt-welcome__head-actions">
			<a class="button" href="<?php echo esc_url( $links['docs'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'tier-pricing-table' ); ?></a>
			<a class="button" href="<?php echo esc_url( $links['support'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Contact us', 'tier-pricing-table' ); ?></a>
		</div>
	</div>
	<hr class="wp-header-end">

	<div class="tpt-welcome__body">
		<div class="tpt-welcome__main">

		<div class="tpt-welcome__card">
			<ol class="tpt-welcome__steps">
				<?php foreach ( $steps as $i => $step ) : ?>
					<?php $class = $step['done'] ? 'tpt-welcome__step--done' . ( empty( $step['skipped'] ) ? '' : ' tpt-welcome__step--skipped' ) : ( $i === $next ? 'tpt-welcome__step--next' : '' ); ?>
					<li class="tpt-welcome__step <?php echo esc_attr( $class ); ?>">
						<span class="tpt-welcome__mark" aria-hidden="true">
							<?php if ( $step['done'] ) : ?>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<?php else : ?>
								<?php echo esc_html( $i + 1 ); ?>
							<?php endif; ?>
						</span>
						<div>
							<h3>
								<?php echo esc_html( $step['title'] ); ?>
								<?php if ( ! empty( $step['skipped'] ) ) : ?><small><?php esc_html_e( 'skipped', 'tier-pricing-table' ); ?></small>
								<?php elseif ( ! empty( $step['optional'] ) ) : ?><small><?php esc_html_e( 'optional', 'tier-pricing-table' ); ?></small><?php endif; ?>
							</h3>
							<p>
								<?php echo esc_html( $step['text'] ); ?>
							</p>
						</div>
						<div class="tpt-welcome__action">
							<?php if ( ! empty( $step['disabled'] ) ) : ?>
								<span><?php echo esc_html( $step['disabled'] ); ?></span>
							<?php else : ?>
								<a class="button <?php echo $i === $next ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $step['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $step['button'] ); ?></a>
							<?php endif; ?>
							<?php if ( ! empty( $step['skipped'] ) ) : ?>
								<div class="tpt-welcome__skip"><a href="<?php echo esc_url( $step['undo_url'] ); ?>"><?php esc_html_e( 'Undo skip', 'tier-pricing-table' ); ?></a></div>
							<?php elseif ( ! empty( $step['optional'] ) && ! $step['done'] && ! empty( $step['skip_url'] ) ) : ?>
								<div class="tpt-welcome__skip"><a href="<?php echo esc_url( $step['skip_url'] ); ?>"><?php esc_html_e( 'Skip this step', 'tier-pricing-table' ); ?></a></div>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<?php if ( ! empty( $plugins ) ) : ?>
			<div class="tpt-welcome__card">
				<h2><?php esc_html_e( 'Pairs with Tiered Pricing', 'tier-pricing-table' ); ?></h2>
				<ul class="tpt-welcome__plugins">
					<?php foreach ( $plugins as $plugin ) : ?>
						<li>
							<img src="<?php echo esc_url( $plugin['icon'] ); ?>" width="36" height="36" alt="">
							<a class="tpt-welcome__plugin-name" href="<?php echo esc_url( $plugin['site_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $plugin['name'] ); ?></a>
							<p><?php echo esc_html( $plugin['text'] ); ?></p>
							<div class="tpt-welcome__plugin-action"><?php PluginInstaller::renderButton( $plugin ); ?></div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		</div>
		<div class="tpt-welcome__side">

		<div class="tpt-welcome__card">
			<h2><?php esc_html_e( 'Good to know', 'tier-pricing-table' ); ?></h2>
			<ul class="tpt-welcome__tips">
				<?php foreach ( $tips as $tip ) : ?>
					<li>
						<a href="<?php echo esc_url( $tip['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $tip['title'] ); ?></a>
						<span><?php echo esc_html( $tip['text'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		</div>
	</div>

	<div class="tpt-welcome__foot">
		<a href="<?php echo esc_url( $links['settings'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Settings', 'tier-pricing-table' ); ?></a>
		<a href="<?php echo esc_url( $links['changelog'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Changelog', 'tier-pricing-table' ); ?></a>
		<?php if ( ! $premium ) : ?>
			<a href="<?php echo esc_url( $links['review'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Leave a review', 'tier-pricing-table' ); ?></a>
		<?php endif; ?>
	</div>
</div>
