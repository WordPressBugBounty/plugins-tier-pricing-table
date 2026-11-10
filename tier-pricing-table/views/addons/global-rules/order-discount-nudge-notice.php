<?php
/**
 * What is missing for the next order tier, as a line of the mini cart.
 *
 * @var string $message Safe HTML.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<p class="tpt-order-discount-nudge tpt-order-discount-nudge--notice"><?php echo wp_kses_post( $message ); ?></p>
