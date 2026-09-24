<?php namespace TierPricingTable\Settings\Sections\GeneralSection\Subsections;

use TierPricingTable\Settings\CustomOptions\TPTSwitchOption;
use TierPricingTable\Settings\Sections\SubsectionAbstract;
use TierPricingTable\Settings\Settings;

/**
 * How tiered prices are calculated. Formerly the "Calculations" tab; the option ids are unchanged.
 */
class CalculationSubsection extends SubsectionAbstract {
	
	public function getTitle(): string {
		return __( 'Price Calculation', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'How tiered prices are calculated for variable products, sale prices and global rules.', 'tier-pricing-table' );
	}
	
	public function getSlug(): string {
		return 'calculation';
	}
	
	public function getSettings(): array {
		$price = function ( $amount, ?int $decimals = null ) {
			$args = null === $decimals ? array() : array( 'decimals' => $decimals );

			return html_entity_decode( wp_strip_all_tags( wc_price( $amount, $args ) ), ENT_QUOTES, 'UTF-8' );
		};

		$settings = apply_filters( 'tiered_pricing_table/settings/calculation_logic', array(
			array(
				'title'                => __( 'Combine variations for quantity calculation', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'summarize_variations',
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
				'extended_description' => __( 'Treat all variations of a variable product as the same item when calculating the total cart quantity for tiered pricing rules.', 'tier-pricing-table' )
				                          . $this->example( 'tiers',
					                             array(
					                                'scene'  => __( 'Red × 3 and Blue × 4 in the cart count separately: 3 and 4.', 'tier-pricing-table' ),
					                                'result' => __( 'Below the 5+ tier, no discount', 'tier-pricing-table' ),
					                                'tone'   => 'bad',
					                             ),
					                             array(
					                                'scene'  => __( 'Red × 3 and Blue × 4 in the cart count together: 7.', 'tier-pricing-table' ),
					                                'result' => __( '5+ tier reached: 10% off', 'tier-pricing-table' ),
					                                'tone'   => 'good',
					                             )
					                          ),
				'desc_tip'             => true,
			),
			array(
				'title'                => __( 'Combine variations for minimum quantity', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'mix_and_match_minimum',
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
				'extended_description' => __( 'Determine whether the minimum quantity requirement applies to each variation separately, or to the combined total of all variations in the cart.', 'tier-pricing-table' )
				                          . $this->example( 'minimum',
					                             array(
					                                'scene'  => __( 'Minimum 10. Red × 6 and Blue × 4 each need 10 on their own.', 'tier-pricing-table' ),
					                                'result' => __( 'Minimum not met, cannot add to cart', 'tier-pricing-table' ),
					                                'tone'   => 'bad',
					                             ),
					                             array(
					                                'scene'  => __( 'Minimum 10. Red × 6 and Blue × 4 add up to 10.', 'tier-pricing-table' ),
					                                'result' => __( 'Minimum met', 'tier-pricing-table' ),
					                                'tone'   => 'good',
					                             )
					                          ),
				'desc_tip'             => true,
			),
			array(
				'title'                => __( 'Calculate percentage discounts from regular price', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'calculate_discount_based_on_regular_price',
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'extended_description' => __( 'Calculate percentage discounts using the product\'s regular price, ignoring any active sale prices.', 'tier-pricing-table' )
				                          . $this->example( 'flow',
					                             array(
					                                /* translators: 1: regular price, 2: sale price */
					                                'scene'  => sprintf( __( 'Regular price %1$s, on sale for %2$s. The 20%% tier starts from the sale price.', 'tier-pricing-table' ), $price( 100 ), $price( 90 ) ),
					                                /* translators: %s: tier price */
					                                'result' => sprintf( __( 'Tier price %s', 'tier-pricing-table' ), $price( 72 ) ),
					                             ),
					                             array(
					                                /* translators: 1: regular price, 2: sale price */
					                                'scene'  => sprintf( __( 'Regular price %1$s, on sale for %2$s. The 20%% tier starts from the regular price.', 'tier-pricing-table' ), $price( 100 ), $price( 90 ) ),
					                                /* translators: %s: tier price */
					                                'result' => sprintf( __( 'Tier price %s', 'tier-pricing-table' ), $price( 80 ) ),
					                             )
					                          ),
				'default'              => 'no',
			),
			array(
				'title'                => __( 'Round calculated prices', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'round_price',
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'yes',
				'extended_description' => __( 'Round calculated percentage discounts to prevent minor display discrepancies with standard WooCommerce pricing.', 'tier-pricing-table' )
				                          . $this->example( 'round',
					                             array(
					                                /* translators: %s: price */
					                                'scene'  => sprintf( __( '%s with a 15%% tier comes to 16.9915.', 'tier-pricing-table' ), $price( 19.99 ) ),
					                                'result' => __( 'The exact amount is used', 'tier-pricing-table' ),
					                             ),
					                             array(
					                                /* translators: %s: price */
					                                'scene'  => sprintf( __( '%s with a 15%% tier comes to 16.9915.', 'tier-pricing-table' ), $price( 19.99 ) ),
					                                /* translators: %s: rounded price */
					                                'result' => sprintf( __( 'Rounded to %s', 'tier-pricing-table' ), $price( 16.99 ) ),
					                             )
					                          ),
				'desc_tip'             => true,
			),
			array(
				'title'                => __( 'Prioritize global pricing rules', 'tier-pricing-table' ),
				'id'                   => Settings::SETTINGS_PREFIX . 'override_prices_by_global_rules',
				'extended_description' => __( 'Apply global pricing rules before product-level rules. If disabled, individual product rules take precedence over global rules.', 'tier-pricing-table' )
				                          . $this->example( 'rules',
					                             array(
					                                'scene'  => __( 'A product rule gives 10% off from 10 items, a global rule 15% off from 10 items.', 'tier-pricing-table' ),
					                                'result' => __( 'The product rule wins: 10% off', 'tier-pricing-table' ),
					                             ),
					                             array(
					                                'scene'  => __( 'A product rule gives 10% off from 10 items, a global rule 15% off from 10 items.', 'tier-pricing-table' ),
					                                'result' => __( 'The global rule wins: 15% off', 'tier-pricing-table' ),
					                             )
					                          ),
				'type'                 => TPTSwitchOption::FIELD_TYPE,
				'default'              => 'no',
			),
		) );

		// one group inside the General tab: no nested titles from the old tab's layout
		return array_values( array_filter( $settings, function ( $field ) {
			return ! in_array( $field['type'] ?? '', array( 'title', 'sectionend' ), true );
		} ) );
	}

	/**
	 * An "Off / On" comparison under an option: the same situation with the option off and on, one
	 * sentence each plus the outcome. The settings script highlights the case matching the switch.
	 *
	 * @param  string  $type  example type (adds a modifier class)
	 * @param  array   $off   scene (text), result (text), tone (good|bad|null)
	 * @param  array   $on    same shape
	 */
	protected function example( string $type, array $off, array $on ): string {
		$case = function ( array $case, string $label, bool $on ) {
			$tone = $case['tone'] ?? '';

			return '<div class="tpt-example__case ' . ( $on ? 'tpt-example__case--on' : 'tpt-example__case--off' ) . '">'
			       . '<span class="tpt-example__badge">' . esc_html( $label ) . '</span>'
			       . '<span class="tpt-example__text">' . esc_html( $case['scene'] ) . '</span>'
			       . '<span class="tpt-example__result' . ( $tone ? ' tpt-example__result--' . $tone : '' ) . '">' . esc_html( $case['result'] ?? '' ) . '</span>'
			       . '</div>';
		};

		return '<div class="tpt-example tpt-example--' . esc_attr( $type ) . '">'
		       . $case( $off, __( 'Off', 'tier-pricing-table' ), false )
		       . $case( $on, __( 'On', 'tier-pricing-table' ), true )
		       . '</div>';
	}

}
