<?php
/**
 * Administration page.
 *
 * @package   WC_Payment_Discounts_Admin
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php settings_fields( 'woocommerce_payment_discounts_group' ); ?>
		<h3><?php _e( 'Payment Methods', $this->plugin_slug ); ?></h3>
		<p><?php _e( 'Enter an amount (e.g. 20.99, or a percentage, e.g. 5%) for each payment gateway.', $this->plugin_slug ); ?></p>
		<table class="form-table">
			<tbody>
				<?php
					foreach ( $payment_gateways as $gateway ) :
						$current = isset( $settings[ $gateway->id ] ) ? $settings[ $gateway->id ] : '0';
				?>
					<tr valign="top">
						<th scope="row"><label for="woocommerce_payment_discounts_<?php echo esc_attr( $gateway->id ); ?>"><?php echo esc_attr( $gateway->title ); ?></label></th>
						<td><input type="text" class="input-text regular-input" value="<?php echo esc_attr( $current ); ?>" id="woocommerce_payment_discounts_<?php echo esc_attr( $gateway->id ); ?>" name="woocommerce_payment_discounts[<?php echo esc_attr( $gateway->id ); ?>]" /></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
