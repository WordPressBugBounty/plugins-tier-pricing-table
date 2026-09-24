<?php
/**
 * Wholesale registration form.
 *
 * Override it by copying this file to yourtheme/tiered-pricing-table/registration-form.php.
 *
 * @var WP_User|null  $user            the logged-in customer, or null for a guest
 * @var array         $fields          the form's fields: [ key, label, type, required, options ] each
 * @var bool          $needsPassword   whether to ask for a password
 * @var int           $termsPageId     page id of the terms, 0 for none
 * @var WP_Error|null $errors
 * @var array         $values
 * @var string        $title
 * @var string        $intro
 * @var string        $submitText
 * @var string        $recaptchaKey    reCAPTCHA v3 site key, empty when off
 * @var string        $loginUrl
 * @var \TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Frontend\RegistrationForm $form
 */

use TierPricingTable\Addons\NonLoggedInUsers\Wholesale\Frontend\RegistrationForm;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$hasErrors = $errors instanceof WP_Error && $errors->has_errors();
$formId    = 'tpt-wholesale-form';
?>
<div class="tpt-wholesale">
	<?php if ( $title ) : ?>
		<h2 class="tpt-wholesale__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( $intro ) : ?>
		<p class="tpt-wholesale__intro"><?php echo wp_kses_post( $intro ); ?></p>
	<?php endif; ?>

	<?php if ( $hasErrors ) : ?>
		<ul class="woocommerce-error tpt-wholesale__errors" role="alert">
			<?php foreach ( $errors->get_error_messages() as $message ) : ?>
				<li><?php echo esc_html( $message ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<form method="post" class="tpt-wholesale__form woocommerce-form" id="<?php echo esc_attr( $formId ); ?>" novalidate>
		<?php wp_nonce_field( RegistrationForm::NONCE, RegistrationForm::NONCE ); ?>
		<input type="hidden" name="tpt_wholesale_action" value="register">

		<?php // honeypot: hidden from people, filled by bots ?>
		<div class="tpt-wholesale__hp" aria-hidden="true">
			<label for="tpt_wholesale_website_url">Website URL</label>
			<input type="text" name="tpt_wholesale_website_url" id="tpt_wholesale_website_url" value="" tabindex="-1" autocomplete="off">
		</div>

		<?php if ( ! $user ) : ?>
			<div class="tpt-wholesale__row tpt-wholesale__row--half">
				<p class="form-row <?php echo $form->error( 'first_name' ) ? 'woocommerce-invalid' : ''; ?>">
					<label for="tpt_wholesale_first_name"><?php esc_html_e( 'First name', 'tier-pricing-table' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="first_name" id="tpt_wholesale_first_name" autocomplete="given-name" value="<?php echo esc_attr( $form->value( 'first_name' ) ); ?>" required>
				</p>
				<p class="form-row <?php echo $form->error( 'last_name' ) ? 'woocommerce-invalid' : ''; ?>">
					<label for="tpt_wholesale_last_name"><?php esc_html_e( 'Last name', 'tier-pricing-table' ); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="last_name" id="tpt_wholesale_last_name" autocomplete="family-name" value="<?php echo esc_attr( $form->value( 'last_name' ) ); ?>" required>
				</p>
			</div>

			<p class="form-row <?php echo $form->error( 'email' ) ? 'woocommerce-invalid' : ''; ?>">
				<label for="tpt_wholesale_email"><?php esc_html_e( 'E-mail address', 'tier-pricing-table' ); ?> <span class="required">*</span></label>
				<input type="email" class="input-text" name="email" id="tpt_wholesale_email" autocomplete="email" value="<?php echo esc_attr( $form->value( 'email' ) ); ?>" required>
			</p>

			<?php if ( $needsPassword ) : ?>
				<p class="form-row <?php echo $form->error( 'password' ) ? 'woocommerce-invalid' : ''; ?>">
					<label for="tpt_wholesale_password"><?php esc_html_e( 'Password', 'tier-pricing-table' ); ?> <span class="required">*</span></label>
					<input type="password" class="input-text" name="password" id="tpt_wholesale_password" autocomplete="new-password" required>
				</p>
			<?php endif; ?>
		<?php else : ?>
			<p class="tpt-wholesale__account">
				<?php
				/* translators: 1: user display name, 2: e-mail */
				echo esc_html( sprintf( __( 'Applying as %1$s (%2$s).', 'tier-pricing-table' ), $user->display_name, $user->user_email ) );
				?>
			</p>
		<?php endif; ?>

		<?php foreach ( $fields as $field ) :
			$key        = (string) $field['key'];
			$type       = (string) $field['type'];
			$isRequired = ! empty( $field['required'] );
			$inputId    = 'tpt_wholesale_' . $key;
			$value      = $form->value( $key );
			$rowClass   = 'form-row tpt-wholesale__field tpt-wholesale__field--' . $type . ( $form->error( $key ) ? ' woocommerce-invalid' : '' );
			$autofill   = array( 'company' => 'organization', 'phone' => 'tel', 'website' => 'url' );
			?>
			<?php if ( 'checkbox' === $type ) : ?>
				<p class="<?php echo esc_attr( $rowClass ); ?>">
					<label for="<?php echo esc_attr( $inputId ); ?>" class="tpt-wholesale__checkbox">
						<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $inputId ); ?>" value="1" <?php checked( '1', $value ); ?>>
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( $isRequired ) : ?><span class="required">*</span><?php endif; ?>
					</label>
				</p>
				<?php continue; ?>
			<?php endif; ?>

			<p class="<?php echo esc_attr( $rowClass ); ?>">
				<label for="<?php echo esc_attr( $inputId ); ?>">
					<?php echo esc_html( $field['label'] ); ?>
					<?php if ( $isRequired ) : ?><span class="required">*</span><?php else : ?><span class="optional">(<?php esc_html_e( 'optional', 'tier-pricing-table' ); ?>)</span><?php endif; ?>
				</label>
				<?php if ( 'textarea' === $type ) : ?>
					<textarea class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $inputId ); ?>" rows="4" <?php echo $isRequired ? 'required' : ''; ?>><?php echo esc_textarea( $value ); ?></textarea>
				<?php elseif ( 'select' === $type ) : ?>
					<select class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $inputId ); ?>" <?php echo $isRequired ? 'required' : ''; ?>>
						<option value=""><?php esc_html_e( 'Choose an option…', 'tier-pricing-table' ); ?></option>
						<?php foreach ( (array) $field['options'] as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $option, $value ); ?>><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input type="<?php echo esc_attr( 'number' === $type ? 'text' : $type ); ?>" <?php echo 'number' === $type ? 'inputmode="decimal"' : ''; ?> class="input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $inputId ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo isset( $autofill[ $key ] ) ? 'autocomplete="' . esc_attr( $autofill[ $key ] ) . '"' : ''; ?> <?php echo $isRequired ? 'required' : ''; ?>>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>

		<?php if ( $termsPageId ) : ?>
			<p class="form-row tpt-wholesale__terms <?php echo $form->error( 'terms' ) ? 'woocommerce-invalid' : ''; ?>">
				<label for="tpt_wholesale_terms">
					<input type="checkbox" name="terms" id="tpt_wholesale_terms" value="1" <?php checked( '1', $form->value( 'terms' ) ); ?>>
					<?php
					echo wp_kses_post( sprintf(
					/* translators: %s: link to the terms page */
						__( 'I agree to the %s.', 'tier-pricing-table' ),
						'<a href="' . esc_url( get_permalink( $termsPageId ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $termsPageId ) ) . '</a>'
					) );
					?>
					<span class="required">*</span>
				</label>
			</p>
		<?php endif; ?>

		<?php if ( $recaptchaKey ) : ?>
			<input type="hidden" name="g-recaptcha-response" id="tpt_wholesale_recaptcha" value="">
		<?php endif; ?>

		<p class="form-row tpt-wholesale__submit">
			<button type="submit" class="button woocommerce-button"><?php echo esc_html( $submitText ); ?></button>
		</p>

		<?php if ( ! $user ) : ?>
			<p class="tpt-wholesale__login">
				<?php
				echo wp_kses_post( sprintf(
				/* translators: %s: login link */
					__( 'Already have an account? %s and apply from there.', 'tier-pricing-table' ),
					'<a href="' . esc_url( $loginUrl ) . '">' . esc_html__( 'Log in', 'tier-pricing-table' ) . '</a>'
				) );
				?>
			</p>
		<?php endif; ?>
	</form>
</div>

<?php if ( $recaptchaKey ) : ?>
	<script>
		( function () {
			var form = document.getElementById( <?php echo wp_json_encode( $formId ); ?> );
			if ( ! form ) { return; }
			var done = false;
			form.addEventListener( 'submit', function ( event ) {
				if ( done || typeof grecaptcha === 'undefined' ) { return; }
				event.preventDefault();
				grecaptcha.ready( function () {
					grecaptcha.execute( <?php echo wp_json_encode( $recaptchaKey ); ?>, { action: 'wholesale_registration' } ).then( function ( token ) {
						document.getElementById( 'tpt_wholesale_recaptcha' ).value = token;
						done = true;
						form.submit();
					} );
				} );
			} );
		} )();
	</script>
<?php endif; ?>
