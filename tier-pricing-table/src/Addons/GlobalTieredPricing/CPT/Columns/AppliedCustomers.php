<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Columns;

use Exception;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\Formatter;
use WC_Customer;

class AppliedCustomers {
	
	public function getName(): string {
		return __( 'Customers', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $rule ) {
		
		$hasCustomers = $this->showCustomers( $rule->getIncludedUsers() );
		$hasRoles     = $this->showUserRoles( $rule->getIncludedUserRoles() );
		
		if ( ! $hasRoles && ! $hasCustomers ) {
			?>
			<b style="color:#d63638">
				<?php esc_html_e( 'Applied to every user', 'tier-pricing-table' ); ?>
            </b>
			<br>
			<br>
			<?php
		}
	
		$this->showCustomers( $rule->getExcludedUsers(), false );
		$this->showUserRoles( $rule->getExcludedUserRoles(), false );
	}
	
	public function showCustomers( array $customersIds, $included = true ): bool {
		$customersMoreThanCanBeShown = count( $customersIds ) > 10;
		
		$customersIds = array_slice( $customersIds, 0, 5 );
		
		$customers = array_filter( array_map( function ( $customerId ) {
			try {
				return new WC_Customer( $customerId );
			} catch ( Exception $e ) {
				return false;
			}
		}, $customersIds ) );
		
		if ( ! empty( $customers ) ) {
			
			if ( $included ) {
				esc_html_e( 'Customers: ', 'tier-pricing-table' );
			} else {
				esc_html_e( 'Excluded customers: ', 'tier-pricing-table' );
			}
			
			$customersString = array_map( function ( WC_Customer $customer ) {
				return Formatter::formatCustomerString( $customer, true );
			}, $customers );
			
			echo wp_kses_post( implode( ', ',
					$customersString ) . ( $customersMoreThanCanBeShown ? '<span> ...</span>' : '' ) );
			
			echo '<br><br>';
			
			return true;
		}
		
		return false;
	}
	
	public function showUserRoles( array $roles, $included = true ): bool {
		
		if ( ! empty( $roles ) ) {
			if ( $included ) {
				esc_html_e( 'Roles: ', 'tier-pricing-table' );
			} else {
				esc_html_e( 'Excluded roles: ', 'tier-pricing-table' );
			}
			
			$rolesString = array_map( function ( $role ) {
				return sprintf( '<span>%s</span>', Formatter::formatRoleString( $role ) );
			}, $roles );
			
			echo wp_kses_post( implode( ', ', $rolesString ) );
			
			echo '<br><br>';
			
			return true;
		}
		
		return false;
	}
}
