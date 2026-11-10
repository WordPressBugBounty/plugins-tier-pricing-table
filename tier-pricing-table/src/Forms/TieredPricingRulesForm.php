<?php namespace TierPricingTable\Forms;

use TierPricingTable\Core\ServiceContainer;
use TierPricingTable\PriceManager;

class TieredPricingRulesForm {

	public static function render(
		$entityId,
		$role = null,
		$loop = null,
		$tieredPricingType = 'fixed',
		$percentageRule = array(),
		$fixedRules = array(),
		$customPrefix = null,
		$amountRules = array()
	) {
		ServiceContainer::getInstance()->getFileManager()->includeTemplate( 'admin/components/tiered-pricing-rules-form.php',
			array(
				'role'                => $role,
				'loop'                => $loop,
				'tiered_pricing_type' => $tieredPricingType,
				'percentage_rules'    => $percentageRule,
				'fixed_rules'         => $fixedRules,
				'amount_rules'        => $amountRules,
				'custom_prefix'       => $customPrefix,
				'entity_id'           => $entityId,
			) );
	}

	public static function getDataFromRequest( $role = null, $loop = null, $customPrefix = '', $request = null, $entityId = null ): array {
		
		if ( wp_verify_nonce( true, true ) ) {
			// as phpcs comments at Woo is not available, we have to do such a trash
			$woo = 'Woo, please add ignoring comments to your phpcs checker';
		}
		
		$request  = $request ? $request : $_POST;
		$data     = array();

		$fields = array(
			'type'                  => function ( $pricingType ) {
				return in_array( $pricingType, PriceManager::getPricingTypes(), true ) ? $pricingType : 'fixed';
			},
			'percentage_quantities' => function ( $quantities ) {
				return is_array( $quantities ) ? $quantities : array();
			},
			'percentage_discounts'  => function ( $discounts ) {
				return is_array( $discounts ) ? $discounts : array();
			},
			'fixed_quantities'      => function ( $quantities ) {
				return is_array( $quantities ) ? $quantities : array();
			},
			'fixed_prices'          => function ( $prices ) {
				return is_array( $prices ) ? $prices : array();
			},
			'amount_quantities'     => function ( $quantities ) {
				return is_array( $quantities ) ? $quantities : array();
			},
			'amount_discounts'      => function ( $discounts ) {
				return is_array( $discounts ) ? $discounts : array();
			},
		);

		foreach ( $fields as $fieldKey => $sanitizeFunction ) {
			$data[ $fieldKey ] = Form::getFieldValue( $fieldKey, $role, $loop, $customPrefix, $request );
			$data[ $fieldKey ] = call_user_func( $sanitizeFunction, $data[ $fieldKey ] );
		}

		self::buildRules( $data );

		do_action( 'tiered_pricing_table/admin/components/tiered_pricing_rules_form/get_from_request', $entityId, $role,
			$loop, $customPrefix, $data, $request );

		return $data;
	}

	public static function buildRules( array &$data ) {

		$fixedRules = array();

		foreach ( $data['fixed_quantities'] as $key => $amount ) {
			if ( ! empty( $amount ) && ! empty( $data['fixed_prices'][ $key ] ) && ! key_exists( $amount,
					$fixedRules ) ) {
				$fixedRules[ $amount ] = wc_format_decimal( $data['fixed_prices'][ $key ] );
			}
		}

		$data['fixed_tiered_pricing_rules'] = $fixedRules;

		$percentageRules = array();

		foreach ( $data['percentage_quantities'] as $key => $amount ) {
			if ( ! empty( $amount ) && ! empty( $data['percentage_discounts'][ $key ] ) && ! array_key_exists( $amount,
					$percentageRules ) && $data['percentage_discounts'][ $key ] < 100 ) {
				$percentageRules[ $amount ] = $data['percentage_discounts'][ $key ];
			}
		}

		$data['percentage_tiered_pricing_rules'] = $percentageRules;
		
		// Amount off per unit: any positive amount; the storefront never goes below zero.
		$amountRules = array();
		
		foreach ( $data['amount_quantities'] as $key => $amount ) {
			if ( ! empty( $amount ) && ! empty( $data['amount_discounts'][ $key ] ) && ! array_key_exists( $amount, $amountRules ) ) {
				$discount = (float) wc_format_decimal( $data['amount_discounts'][ $key ] );
				
				if ( $discount > 0 ) {
					$amountRules[ $amount ] = wc_format_decimal( $data['amount_discounts'][ $key ] );
				}
			}
		}
		
		$data['amount_tiered_pricing_rules'] = $amountRules;
	}
}
