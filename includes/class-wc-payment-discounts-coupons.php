<?php
/**
 * Handle coupons
 *
 * @package WC_Payment_Discounts/Classes
 * @since   2.4.0
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Payment_Discounts_Coupons class.
 */
class WC_Payment_Discounts_Coupons {

	/**
	 * Get coupon by payment method ID.
	 *
	 * @param  string $payment_method Payment method ID.
	 * @param  array  $data           Coupon data.
	 *
	 * @return WC_Coupon
	 */
	public static function get_coupon( $payment_method, $data = array() ) {
		global $wpdb;

		$id = $wpdb->get_var( $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_wcpd_payment_method'
			AND meta_value = '%s'
		", $payment_method ) );

		if ( is_null( $id ) ) {
			return self::create_coupon( $payment_method, $data );
		}

		return new WC_Coupon( intval( $id ) );
	}

	/**
	 * Create coupon for a payment method.
	 *
	 * @param  string $payment_method Payment method ID.
	 * @param  array  $data           Coupon data.
	 *
	 * @return WC_Coupon
	 */
	public static function create_coupon( $payment_method, $data ) {
		$coupon = new WC_Coupon();

		$code   = wc_format_coupon_code( $payment_method . '_' . wp_generate_password( 30, true, true ) );
		$type   = false !== strstr( $data['amount'], '%' ) ? 'percent' : 'fixed_cart';
		$amount = (float) trim( $data['amount'], '%' );

		$coupon->set_code( $code );
		$coupon->set_description( esc_html( sprintf( __( 'Discount coupon for payments with %s.', 'woocommerce-payment-discounts' ), $payment_method ) ) );
		$coupon->set_amount( $amount );
		$coupon->set_discount_type( $type );
		$coupon->add_meta_data( '_wcpd_payment_method', $payment_method, true );
		$coupon->save();

		return $coupon;
	}

	/**
	 * Update payment method coupon.
	 *
	 * @param  string $payment_method Payment method ID.
	 * @param  array  $data           Coupon data.
	 *
	 * @return WC_Coupon
	 */
	public static function update_coupon( $payment_method, $data ) {
		$coupon = get_coupon( $payment_method, $data );

		$type   = false !== strstr( $data['amount'], '%' ) ? 'percent' : 'fixed_cart';
		$amount = (float) trim( $data['amount'], '%' );

		$coupon->set_amount( $amount );
		$coupon->set_discount_type( $type );
		$coupon->save();

		return $coupon;
	}
}
