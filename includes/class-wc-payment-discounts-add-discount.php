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
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_discount' ), 10 );

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
	 * Calcule the discount amount.
	 *
	 * @param  string|int|float $amount Discount value.
	 * @param  float            $total Cart subtotal.
	 *
	 * @return float                   Discount amount.
	 */
	protected function calculate_discount( $amount, $subtotal ) {
		if ( strstr( $amount, '%' ) ) {
			$amount = ( $subtotal / 100 ) * str_replace( '%', '', $amount );
		}

		return apply_filters( 'wc_payment_discounts_amount', $amount );
	}

	/**
	 * Generate the discount name.
	 *
	 * @param  mixed  $amount  Discount amount
	 * @param  object $gateway Gateway data.
	 *
	 * @return string          Discount name.
	 */
	protected function discount_name( $amount, $gateway ) {
		if ( strstr( $amount, '%' ) ) {
			return sprintf( __( 'Discount for %s (%s off)', 'woocommerce-payment-discounts' ), esc_attr( $gateway->title ), $amount );
		}

		return sprintf( __( 'Discount for %s', 'woocommerce-payment-discounts' ), esc_attr( $gateway->title ) );
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
			$amount      = $settings[ WC()->session->chosen_payment_method ]['amount'];
			$include_tax = 'yes' === $settings[ WC()->session->chosen_payment_method ]['include_tax'];

			if ( apply_filters( 'wc_payment_discounts_apply_discount', 0 < $amount, $cart ) ) {

				// Gets the gateway data.
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				$gateway          = $payment_gateways[ WC()->session->chosen_payment_method ];

				// Generate the discount amount and title.
				$discount_name = $this->discount_name( $amount, $gateway );
				$cart_discount = $this->calculate_discount( $amount, $cart->cart_contents_total ) * -1;

				// Apply the discount.
				$cart->add_fee( $discount_name, $cart_discount, $include_tax );
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
