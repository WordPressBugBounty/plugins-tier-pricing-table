<?php
	
	use TierPricingTable\Core\ServiceContainer;
	use TierPricingTable\Settings\Sections\GeneralSection\Subsections\CartOptionsSubsection;
	
if ( ! defined( 'WPINC' ) ) {
	die;
}
	
	$calculationLogicSectionLink = add_query_arg( array(
		'section' => 'calculation_logic',
	), ServiceContainer::getInstance()->getSettings()->getLink() );

	?>
<?php if ( CartOptionsSubsection::globalRulesOverrideProductLevelRules() ) : ?>
	<h4>
		<?php
			esc_html_e( 'Pricing you set here will override any rules set directly in the products.',
				'tier-pricing-table' );
		?>
	</h4>
<?php else : ?>
	<h4>
		<?php
			esc_html_e( 'Please note that rules in products have higher priority overriding the pricing rules you set here.',
				'tier-pricing-table' );
		?>
	</h4>
	
	<h4>
		<?php esc_html_e( 'Priorities are the following:', 'tier-pricing-table' ); ?>
	</h4>
	
	<ul class="tpt-global-pricing-notice-list">
		<li><?php esc_html_e( 'Single product rules (or single variation)', 'tier-pricing-table' ); ?></li>
		<li><?php esc_html_e( 'Variable product rules (if a product is a variable)', 'tier-pricing-table' ); ?></li>
		<li><?php esc_html_e( 'Global rules', 'tier-pricing-table' ); ?></li>
	</ul>
<?php endif; ?>


<h4>
	<?php esc_html_e( 'You can adjust priorities in the', 'tier-pricing-table' ); ?>
	<a target="_blank" href="<?php echo esc_attr( $calculationLogicSectionLink ); ?>">
		<?php esc_html_e( 'Settings', 'tier-pricing-table' ); ?>
	</a>
</h4>