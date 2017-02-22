<?php
/**
 * Administration page.
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
		<h3><?php esc_html_e( 'Payment Methods', 'woocommerce-payment-discounts' ); ?></h3>
		<p><?php esc_html_e( 'Enter an amount (e.g. 20.99, or a percentage, e.g. 5%) for each payment gateway.', 'woocommerce-payment-discounts' ); ?><br /><?php esc_html_e( 'Use zero (0) for not applying discounts.', 'woocommerce-payment-discounts' ); ?></p>
		<table class="wc_gateways widefat" cellspacing="0">
			<thead>
				<tr>
					<th style="width: 20%"><strong><?php esc_html_e( 'Payment method', 'woocommerce-payment-discounts' ); ?></strong></th>
					<th><strong><?php esc_html_e( 'Discount amount', 'woocommerce-payment-discounts' ); ?></strong></th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ( $payment_gateways as $gateway ) :
						$amount      = isset( $settings[ $gateway->id ]['amount'] ) ? $settings[ $gateway->id ]['amount'] : '0';
						$include_tax = isset( $settings[ $gateway->id ]['include_tax'] ) ? $settings[ $gateway->id ]['include_tax'] : 'yes';
				?>
					<tr>
						<td>
							<label for="woocommerce_payment_discounts_<?php echo esc_attr( $gateway->id ); ?>_amount"><strong><?php echo esc_attr( $gateway->title ); ?></strong></label>
						</td>
						<td>
							<input type="text" class="input-text regular-input" value="<?php echo esc_attr( $amount ); ?>" id="woocommerce_payment_discounts_<?php echo esc_attr( $gateway->id ); ?>_amount" name="woocommerce_payment_discounts[<?php echo esc_attr( $gateway->id ); ?>][amount]" />
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>
