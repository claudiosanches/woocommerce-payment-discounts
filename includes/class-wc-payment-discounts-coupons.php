<?php
/**
 * Handle coupons
 *
 * @package WC_Payment_Discounts/Classes
 * @since   3.0.0
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Payment_Discounts_Coupons class.
 */
class WC_Payment_Discounts_Coupons {

	/**
	 * Generate coupon code.
	 *
	 * @param  string $payment_method Payment method ID.
	 *
	 * @return string
	 */
	protected static function generate_code( $payment_method ) {
		$code = 'wcpd-' . $payment_method . '-' . wp_generate_password( 20, false );

		// Remove "_" and empty spaces.
		$payment_method = str_replace( ' ', '', $payment_method );

		return $code;
	}

	/**
	 * Query coupon ID.
	 *
	 * @param  string $payment_method Payment method ID.
	 *
	 * @return int|null
	 */
	protected static function get_coupon_id( $payment_method ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_wcpd_payment_method'
			AND meta_value = '%s'
		", $payment_method ) );
	}

	/**
	 * Get coupon by payment method ID.
	 *
	 * @param  string $payment_method Payment method ID.
	 * @param  array  $data           Coupon data.
	 *
	 * @return WC_Coupon
	 */
	public static function get_coupon( $payment_method, $data = array() ) {
		$id = self::get_coupon_id( $payment_method );

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

		$code   = wc_format_coupon_code( self::generate_code( $payment_method ) );
		$type   = false !== strstr( $data['amount'], '%' ) ? 'percent' : 'fixed_cart';
		$amount = (float) wc_format_decimal( trim( $data['amount'], '%' ) );

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
	 * @return bool
	 */
	public static function update_coupon( $payment_method, $data ) {
		$id     = (int) self::get_coupon_id( $payment_method );
		$amount = (float) wc_format_decimal( trim( $data['amount'], '%' ) );

		// Ignore if don't exists and amount is 0.
		if ( (float) 0 === $amount && 0 === $id ) {
			return false;
		}

		// Delete existing coupon if amount is 0.
		if ( (float) 0 === $amount && 0 !== $id ) {
			$coupon = new WC_Coupon( (int) $id );
			$coupon->delete( true );
			return true;
		}

		// Create a new coupon.
		if ( 0 === $id ) {
			self::create_coupon( $payment_method, $data );
			return true;
		}

		// Update existing coupon.
		$coupon = new WC_Coupon( (int) $id );
		$type   = false !== strstr( $data['amount'], '%' ) ? 'percent' : 'fixed_cart';

		$coupon->set_amount( $amount );
		$coupon->set_discount_type( $type );
		$coupon->save();

		return true;
	}
}
