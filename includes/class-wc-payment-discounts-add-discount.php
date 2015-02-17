<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add discount.
 */
class WC_Payment_Discounts_Add_Discount {

	/**
	 * Cart discount.
	 *
	 * @var int
	 */
	protected $cart_discount = 0;

	/**
	 * Discount name.
	 *
	 * @var string
	 */
	protected $discount_name = '';

	/**
	 * Initialize the actions.
	 */
	public function __construct() {
		// Load public-facing JavaScript.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Apply the discounts.
		add_action( 'woocommerce_calculate_totals', array( $this, 'add_discount' ), 10 );

		// Display the discount in review order.
		add_action( 'woocommerce_review_order_before_order_total', array( $this, 'discount_display' ) );

		// Display the discount in payment gateways titles.
		add_filter( 'woocommerce_gateway_title', array( $this, 'gateway_title' ), 10, 2 );

		// Fix salved payment method title.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'fix_payment_method_title' ), 1 );
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
	 * Display the discount in gateway title.
	 *
	 * @param  string $title Gateway title.
	 * @param  string $id    Gateway ID.
	 *
	 * @return string
	 */
	public function gateway_title( $title, $id ) {
		$settings = get_option( 'woocommerce_payment_discounts' );

		if ( isset( $settings[ $id ] ) && 0 < $settings[ $id ] ) {
			$discount = $settings[ $id ];

			if ( strstr( $discount, '%' ) ) {
				$value = $discount;
			} else {
				$value = woocommerce_price( $discount );
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

		global $woocommerce;

		// Gets the settings.
		$gateways = get_option( 'woocommerce_payment_discounts' );

		if ( isset( $gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
			// Gets the gateway discount.
			$value = $gateways[ $woocommerce->session->chosen_payment_method ];

			if ( 0 < $value ) {
				// Gets the gateway data.
				$payment_gateways = $woocommerce->payment_gateways->payment_gateways();
				$gateway          = $payment_gateways[ $woocommerce->session->chosen_payment_method ];

				// Generate the discount amount and title.
				$this->cart_discount = $this->calculate_discount( $value, $cart->cart_contents_total );
				$this->discount_name = $this->discount_name( $value, $gateway );

				// Apply the discount.
				$cart->cart_contents_total = $cart->cart_contents_total - $this->cart_discount;
			}
		}
	}

	/**
	 * Diplay the discount in checkout order view.
	 *
	 * @return string
	 */
	public function discount_display() {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
			if ( 0 < $this->cart_discount ) {
				$discount_name  = $this->discount_name;
				$discount_price = woocommerce_price( $this->cart_discount );

				$html = '<tr class="order-total">';
					$html .= '<th>' . $discount_name . '</th>';
					$html .= '<td>-' . $discount_price . '</td>';
				$html .= '</tr>';

				echo apply_filters( 'wc_payment_discounts_row', $html, $discount_name, $discount_price );
			}
		}
	}

	/**
	 * Fix payment method title.
	 * Remove the discount in the title.
	 *
	 * @param int  $order_id Order ID.
	 * @param array $posted  Posted order data.
	 */
	public function fix_payment_method_title( $order_id ) {
		$old_title = get_post_meta( $order_id, '_payment_method_title', true );
		$new_title = preg_replace( '/<small>.*<\/small>/', '', $old_title );

		// Save the fixed title.
		update_post_meta( $order_id, '_payment_method_title', $new_title );
	}
}

new WC_Payment_Discounts_Add_Discount();
