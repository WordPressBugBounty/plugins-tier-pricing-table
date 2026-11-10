<?php
/**
 * The free shipping bar as a row of the cart totals or the checkout order review.
 *
 * @var string $bar The bar's HTML, built by free-shipping-bar.php.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<tr class="tpt-free-shipping-bar-row">
	<td colspan="2" class="tpt-free-shipping-bar-row__cell"><?php echo $bar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in free-shipping-bar.php ?></td>
</tr>
