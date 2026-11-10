<?php
/**
 * What is missing for the next order tier, as a row of the cart totals or the checkout order review.
 *
 * @var string $message Safe HTML.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<tr class="tpt-order-discount-nudge">
	<td colspan="2" class="tpt-order-discount-nudge__cell"><?php echo wp_kses_post( $message ); ?></td>
</tr>
