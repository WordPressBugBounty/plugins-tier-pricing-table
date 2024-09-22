<?php namespace TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\Tabs;

use TierPricingTable\Addons\GlobalTieredPricing\CPT\Form\FormTab;
use TierPricingTable\Addons\GlobalTieredPricing\GlobalPricingRule;
use TierPricingTable\Addons\GlobalTieredPricing\LookupService;

class UsersAndRoles extends FormTab {
	
	public function getId(): string {
		return 'user-and-roles';
	}
	
	public function getTitle(): string {
		return __( 'Users & roles', 'tier-pricing-table' );
	}
	
	public function getDescription(): string {
		return __( 'Select users or user roles the rule will work for.', 'tier-pricing-table' );
	}
	
	public function render( GlobalPricingRule $pricingRule ) {
		
		$selectForACustomerLabel = __( 'Select for a customer', 'tier-pricing-table' );
		$selectForARoleLabel     = __( 'Select for a user role', 'tier-pricing-table' );
		
		if ( empty( $pricingRule->getIncludedUserRoles() ) && empty( $pricingRule->getIncludedUsers() ) ) {
			$this->form->renderHint( __( 'The rule will work for all users if you do not specify user roles or specific customers. (excluding users selected in the exclusions section)',
				'tier-pricing-table' ) );
		}
		
		?>
		<p class="form-field">
			<label for="tpt_included_user_roles">
				<?php
					esc_html_e( 'Include user roles', 'tier-pricing-table' );
				?>
			</label>
			
			<select class="tpt-select-woo" multiple="multiple" style="width: 95%;" id="tpt_included_user_roles"
					name="tpt_included_user_roles[]"
					data-placeholder="<?php echo esc_attr( $selectForARoleLabel ); ?>">
				
				<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
					<?php if ( ! in_array( $key, array() ) ) : ?>
						<option
							<?php selected( in_array( $key, $pricingRule->getIncludedUserRoles() ) ); ?>
							value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_attr( $WPRole['name'] ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Choose to what user roles this rule will be relevant. Applies to all users with those roles.',
					'tier-pricing-table' ) );
			?>
		</p>
		
		<p class="form-field">
			<label for="tpt_included_users">
				<?php
					esc_html_e( 'Include specific customers', 'tier-pricing-table' );
				?>
			</label>
			
			<select class="rbp-select-woo wc-product-search" multiple="multiple" style="width: 95%;"
					id="tpt_included_users"
					name="tpt_included_users[]"
					data-action="woocommerce_json_search_tpt_customers"
					data-placeholder="<?php echo esc_attr( $selectForACustomerLabel ); ?>">
				
				<?php foreach ( $pricingRule->getIncludedUsers() as $userId ) : ?>
					<?php $user = get_user_by( 'id', $userId ); ?>
					<?php if ( $user ) : ?>
						<option selected
								value="<?php echo esc_attr( $userId ); ?>"><?php echo esc_attr( $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')' ); ?></option>
					<?php endif; ?>
				
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Pick up separate user accounts, which will be affected by this rule. ',
					'tier-pricing-table' ) );
			?>
		</p>
		
		<div class="tpt-global-pricing-title">
			<hr>
			<h4><?php esc_attr_e( 'Exclusions', 'tier-pricing-table' ); ?></h4>
		</div>
		
		<p class="form-field">
			<label for="tpt_excluded_user_roles">
				<?php
					esc_html_e( 'Exclude user roles', 'tier-pricing-table' );
				?>
			</label>
			
			<select class="tpt-select-woo" multiple="multiple" style="width: 95%;" id="tpt_excluded_user_roles"
					name="tpt_excluded_user_roles[]"
					data-placeholder="<?php echo esc_attr( $selectForARoleLabel ); ?>">
				
				<?php foreach ( wp_roles()->roles as $key => $WPRole ) : ?>
					<?php if ( ! in_array( $key, array() ) ) : ?>
						<option
							<?php selected( in_array( $key, $pricingRule->getExcludedUserRoles() ) ); ?>
							value="<?php echo esc_attr( $key ); ?>">
							<?php echo esc_attr( $WPRole['name'] ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Choose to what user roles will exclude this rule. Excludes all users with those roles.',
					'tier-pricing-table' ) );
			?>
		</p>
		
		<p class="form-field">
			<label for="tpt_excluded_users">
				<?php
					esc_html_e( 'Exclude specific customers', 'tier-pricing-table' );
				?>
			</label>
			
			<select class="rbp-select-woo wc-product-search" multiple="multiple" style="width: 95%;"
					id="tpt_excluded_users"
					name="tpt_excluded_users[]"
					data-action="woocommerce_json_search_tpt_customers"
					data-placeholder="<?php echo esc_attr( $selectForACustomerLabel ); ?>">
				
				<?php foreach ( $pricingRule->getExcludedUsers() as $userId ) : ?>
					<?php $user = get_user_by( 'id', $userId ); ?>
					<?php if ( $user ) : ?>
						<option selected
								value="<?php echo esc_attr( $userId ); ?>"><?php echo esc_attr( $user->first_name . ' ' . $user->last_name . ' (' . $user->user_email . ')' ); ?></option>
					<?php endif; ?>
				
				<?php endforeach; ?>
			</select>
			
			<?php
				echo wc_help_tip( __( 'Pick up separate user accounts, which will be excluded for this rule.',
					'tier-pricing-table' ) );
			?>
		</p>
		
		<?php
	}
}