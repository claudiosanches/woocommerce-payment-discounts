<?php
/**
 * WooCommerce Discounts Per Payment Method plugin.
 *
 * @package   WC_Payment_Discounts
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 */

/**
 * WC_Payment_Discounts class.
 *
 * @package WC_Payment_Discounts
 * @author  Claudio Sanches <contato@claudiosmweb.com>
 */
class WC_Payment_Discounts {

	/**
	 * Plugin version.
	 *
	 * @since 2.0.0
	 *
	 * @var   string
	 */
	const VERSION = '2.0.0';

	/**
	 * Plugin slug.
	 *
	 * @since 2.0.0
	 *
	 * @var   string
	 */
	protected static $plugin_slug = 'woocommerce-payment-discounts';

	/**
	 * Instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @var   object
	 */
	protected static $instance = null;

	/**
	 * Cart discount.
	 *
	 * @since 2.0.0
	 *
	 * @var   int
	 */
	protected $cart_discount = 0;

	/**
	 * Discount name.
	 *
	 * @since 2.0.0
	 *
	 * @var   string
	 */
	protected $discount_name = '';

	/**
	 * Initialize the plugin.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added.
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Initialize the public actions.
		$this->init();
	}

	/**
	 * Return the plugin slug.
	 *
	 * @since  2.0.0
	 *
	 * @return Plugin slug variable.
	 */
	public static function get_plugin_slug() {
		return self::$plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  2.0.0
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @since  2.0.0
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	public static function woocommerce_instance() {
		if ( function_exists( 'WC' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

	/**
	 * Test with has WooCommerce activated.
	 *
	 * @since  2.0.0
	 *
	 * @return bool
	 */
	public static function has_woocommerce() {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Initialize the plugin public actions only has WooCommerce activated.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	protected function init() {
		if ( self::has_woocommerce() ) {
			// Load public-facing JavaScript.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Apply the discounts.
			add_action( 'woocommerce_calculate_totals', array( $this, 'add_discount' ), 1, 3 );

			// Display the discount in review order.
			add_action( 'woocommerce_review_order_before_order_total', array( $this, 'discount_display' ) );

			// Display the discount in payment gateways titles.
			add_filter( 'woocommerce_gateway_title', array( $this, 'gateway_title' ), 10, 2 );

			// Fix salved payment method title.
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'fix_payment_method_title' ), 1 );
		}
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since  2.0.0
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses
	 *                               "Network Activate" action, false if
	 *                               WPMU is disabled or plugin is
	 *                               activated on an individual blog.
	 *
	 * @return void
	 */
	public static function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since  2.0.0
	 *
	 * @param  boolean $network_wide True if WPMU superadmin uses
	 *                               "Network Deactivate" action, false if
	 *                               WPMU is disabled or plugin is
	 *                               deactivated on an individual blog.
	 *
	 * @return void
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}

				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since  2.0.0
	 *
	 * @param  int  $blog_id ID of the new blog.
	 *
	 * @return void
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since  2.0.0
	 *
	 * @return array|false The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	private static function single_activate() {
		add_option( 'woocommerce_payment_discounts', array() );
		add_option( 'woocommerce_payment_discounts_version', self::VERSION );
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	private static function single_deactivate() {
		delete_option( 'woocommerce_payment_discounts' );
		delete_option( 'woocommerce_payment_discounts_version' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since  2.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = self::$plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( self::$plugin_slug, plugins_url( 'assets/js/update-checkout.min.js', __FILE__ ), array( 'wc-checkout' ), self::VERSION );
		}
	}

	/**
	 * Calcule the discount amount.
	 *
	 * @since  2.0.0
	 *
	 * @param  mixed $value Discount value.
	 * @param  float $total Cart subtotal.
	 *
	 * @return mixed        Discount amount.
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
	 * @since  2.0.0
	 *
	 * @param  mixed  $value Discount amount
	 * @param  object $value Gateway data.
	 *
	 * @return string        Discount name.
	 */
	protected function discount_name( $value, $gateway ) {
		if ( strstr( $value, '%' ) ) {
			return sprintf( __( 'Discount for %s (%s off)', self::$plugin_slug ), esc_attr( $gateway->title ), $value );
		}

		return sprintf( __( 'Discount for %s', self::$plugin_slug ), esc_attr( $gateway->title ) );
	}

	/**
	 * Display the discount in gateway title.
	 *
	 * @since  2.0.0
	 *
	 * @param  string $title Gateway title.
	 * @param  string $id    Gateway ID.
	 *
	 * @return string        [description]
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

			$title .= ' <small>(' . sprintf( __( '%s off', self::$plugin_slug ), $value ) . ')</small>';
		}

		return $title;
	}

	/**
	 * Add discount.
	 *
	 * @since  2.0.0
	 *
	 * @param  object $cart WC_Cart object.
	 *
	 * @return void
	 */
	public function add_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$woocommerce = self::woocommerce_instance();

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
				$this->cart_discount = $this->calculate_discount( $value, $cart->subtotal );
				$this->discount_name = $this->discount_name( $value, $gateway );

				// Apply the discount.
				$cart->discount_total = ( $this->cart_discount + $cart->discount_total );
			}
		}
	}

	/**
	 * Diplay the discount in checkout order view.
	 *
	 * @return string
	 */
	public function discount_display() {
		$woocommerce = self::woocommerce_instance();

		if ( version_compare( $woocommerce->version, '2.1', '>=' ) ) {
			if ( 0 < $this->cart_discount ) {
				$html = '<tr class="order-total">';
					$html .= '<th>' . $this->discount_name . '</th>';
					$html .= '<td>-' . woocommerce_price( $this->cart_discount ) . '</td>';
				$html .= '</tr>';

				echo $html;
			}
		}
	}

	/**
	 * Fix payment method title.
	 * Remove the discount in the title.
	 *
	 * @param  int  $order_id Order ID.
	 * @param  array $posted  Posted order data.
	 *
	 * @return void
	 */
	public function fix_payment_method_title( $order_id ) {
		$old_title = get_post_meta( $order_id, '_payment_method_title', true );
		$new_title = preg_replace( '/<small>.*<\/small>/', '', $old_title );

		// Save the fixed title.
		update_post_meta( $order_id, '_payment_method_title', $new_title );
	}
}
