<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Add discount.
 */
class WC_Payment_Discounts_Add_Discount {

	/**
	 * Initialize the actions.
	 */
	public function __construct() {
		// Load public-facing JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Apply the discounts.
		add_action( 'woocommerce_calculate_totals', array( $this, 'add_discount' ), 10 );

		// Display the discount in payment gateways titles.
		add_filter( 'woocommerce_gateway_title', array( $this, 'payment_method_title' ), 10, 2 );

		// Fix salved payment method title and update the cart discount total.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'update_order_data' ), 10 );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( 'woocommerce-payment-discounts', plugins_url( 'assets/js/update-checkout.min.js', plugin_dir_path( __FILE__ ) ), array( 'wc-checkout' ), WC_Payment_Discounts::VERSION );
		}
	}

	/**
	 * Display the discount in payment method title.
	 *
	 * @param  string $title Gateway title.
	 * @param  string $id    Gateway ID.
	 *
	 * @return string
	 */
	public function payment_method_title( $title, $id ) {
		if ( ! is_checkout() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return $title;
		}

		$settings = get_option( 'woocommerce_payment_discounts' );

		if ( isset( $settings[ $id ]['amount'] ) && 0 < $settings[ $id ]['amount'] ) {
			$amount = $settings[ $id ]['amount'];

			if ( strstr( $amount, '%' ) ) {
				$value = $amount;
			} else {
				$value = wc_price( $amount );
			}

			$title .= ' <small>(' . sprintf( __( '%s off', 'woocommerce-payment-discounts' ), $value ) . ')</small>';
		}

		return $title;
	}

	/**
	 * Remove payment coupons.
	 *
	 * @param  WC_Cart $cart Cart object.
	 * @param  string  $skip Payment method ID to skip during remotion.
	 * @return bool
	 */
	protected function remove_payment_coupons( $cart, $skip = '' ) {
		$removed = false;

		foreach ( $cart->get_applied_coupons() as $code ) {
			if ( 'wcpd_' === substr( $code, 0, 5 ) && $code !== $skip ) {
				$cart->remove_coupon( $code );
				$removed = true;
			}
		}

		return $removed;
	}

	/**
	 * Add discount.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function add_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) || is_cart() ) {
			return;
		}

		// Gets the settings.
		$settings = get_option( 'woocommerce_payment_discounts' );

		if ( isset( $settings[ WC()->session->chosen_payment_method ]['amount'] ) ) {
			// Gets the gateway discount.
			$amount = $settings[ WC()->session->chosen_payment_method ]['amount'];

			if ( apply_filters( 'wc_payment_discounts_apply_discount', 0 < $amount, $cart ) ) {
				// Gets the gateway data.
				$methods = WC()->payment_gateways->payment_gateways();
				$gateway = $methods[ WC()->session->chosen_payment_method ];
				$coupon  = WC_Payment_Discounts_Coupons::get_coupon( $gateway->id, array( 'amount' => $amount ) );

				// Remove other coupons and apply method coupon.
				$this->remove_payment_coupons( $cart, $coupon->get_code() );
				if ( ! $cart->has_discount( $coupon->get_code() ) ) {
					$cart->add_discount( $coupon->get_code() );
				}
			} else {
				$this->remove_payment_coupons( $cart );
			}
		}
	}

	/**
	 * Remove the discount in the payment method title.
	 *
	 * @param int $order_id Order ID.
	 */
	public function update_order_data( $order_id ) {
		$payment_method_title     = get_post_meta( $order_id, '_payment_method_title', true );
		$new_payment_method_title = preg_replace( '/<small>.*<\/small>/', '', $payment_method_title );

		// Save the new payment method title.
		update_post_meta( $order_id, '_payment_method_title', $new_payment_method_title );
	}
}

new WC_Payment_Discounts_Add_Discount();
