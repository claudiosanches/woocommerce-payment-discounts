<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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
	 * @param  string|int|float $value Discount value.
	 * @param  float            $total Cart subtotal.
	 *
	 * @return float                   Discount amount.
	 */
	protected function calculate_discount( $value, $subtotal ) {
		if ( strstr( $value, '%' ) ) {
			$value = ( $subtotal / 100 ) * str_replace( '%', '', $value );
		}

		return $value;
	}

	/**
	 * Generate the discount name.
	 *
	 * @param  mixed  $value Discount amount
	 * @param  object $value Gateway data.
	 *
	 * @return string        Discount name.
	 */
	protected function discount_name( $value, $gateway ) {
		if ( strstr( $value, '%' ) ) {
			return sprintf( __( 'Discount for %s (%s off)', 'woocommerce-payment-discounts' ), esc_attr( $gateway->title ), $value );
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

		if ( isset( $settings[ $id ] ) && 0 < $settings[ $id ] ) {
			$discount = $settings[ $id ];

			if ( strstr( $discount, '%' ) ) {
				$value = $discount;
			} else {
				$value = wc_price( $discount );
			}

			$title .= ' <small>(' . sprintf( __( '%s off', 'woocommerce-payment-discounts' ), $value ) . ')</small>';
		}

		return $title;
	}

	/**
	 * Add discount.
	 *
	 * @param WC_Cart $cart
	 */
	public function add_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) || is_cart() ) {
			return;
		}

		// Gets the settings.
		$gateways = get_option( 'woocommerce_payment_discounts' );

		if ( isset( $gateways[ WC()->session->chosen_payment_method ] ) ) {
			// Gets the gateway discount.
			$value = $gateways[ WC()->session->chosen_payment_method ];

			if ( apply_filters( 'wc_payment_discounts_apply_discount', 0 < $value, $cart ) ) {

				// Gets the gateway data.
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				$gateway          = $payment_gateways[ WC()->session->chosen_payment_method ];

				// Generate the discount amount and title.
				$discount_name = $this->discount_name( $value, $gateway );
				$cart_discount = $this->calculate_discount( $value, $cart->cart_contents_total ) * -1;

				// Apply the discount.
				$cart->add_fee( $discount_name, $cart_discount, true );
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
