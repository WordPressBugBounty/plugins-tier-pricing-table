<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
	/**
	 * Available variables
	 *
	 * @var bool $is_product
	 */
	$is_product = isset( $is_product ) ? $is_product : false;
?>
<style>

</style>

<div class="tpt__free-version-used-premium-available">
    <div style=" <?php echo esc_attr( $is_product ? 'margin-bottom: 10px; border-bottom: 1px solid #eee;' : 'margin: 10px 0; border: 1px solid #eeeeee;' ); ?>  background: #fafafa; padding: 15px; display: flex; gap: 10px; justify-content: space-between">
        <div style="display:flex; gap: 10px; ">

            <div>
                <h3 style="margin: 0">
					<?php esc_html_e( 'The free version is active but you have a premium license',
						'tier-pricing-table' ); ?>
                </h3>

                <div style="margin-top: 10px;">
					<?php echo wp_kses_post( __( 'You can download the premium version either from your <a href="' . esc_url( tpt_fs()->get_account_url() ) . '">account</a> or the purchase confirmation email.',
						'tier-pricing-table' ) ); ?>
                </div>
            </div>
        </div>
    </div>
</div>


