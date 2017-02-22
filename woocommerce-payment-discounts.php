<?php
/**
 * Plugin Name: WooCommerce Discounts Per Payment Method
 * Plugin URI: https://github.com/claudiosmweb/woocommerce-payment-discounts
 * Description: Adds discounts on specific payment methods in WooCommerce.
 * Author: Claudio Sanches
 * Author URI: https://claudiosmweb.com/
 * Version: 2.4.0
 * License: GPLv2 or later
 * Text Domain: woocommerce-payment-discounts
 * Domain Path: /languages/
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Payment_Discounts' ) ) :

/**
 * WooCommerce Discounts Per Payment Method plugin.
 */
class WC_Payment_Discounts {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.4.0';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.7', '>=' ) ) {
			if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				$this->admin_includes();
			}

			$this->includes();
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_is_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
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
	 * Includes.
	 */
	protected function includes() {
		include_once dirname( __FILE__ ) . '/includes/class-wc-payment-discounts-coupons.php';
		include_once dirname( __FILE__ ) . '/includes/class-wc-payment-discounts-apply-discount.php';
	}

	/**
	 * Admin includes.
	 */
	protected function admin_includes() {
		include_once dirname( __FILE__ ) . '/includes/admin/class-wc-payment-discounts-admin.php';
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 */
	public static function activate() {
		add_option( 'woocommerce_payment_discounts', array() );
		add_option( 'woocommerce_payment_discounts_version', self::VERSION );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-payment-discounts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @return string Admin notice.
	 */
	public function woocommerce_is_missing_notice() {
		echo '<div class="error"><p><strong>' . __( 'WooCommerce Discounts Per Payment Method', 'woocommerce-payment-discounts' ) . '</strong> ' . sprintf( __( 'works only with %s 2.7 or later, please install or upgrade your installation!', 'woocommerce-payment-discounts' ), '<a href="http://wordpress.org/plugins/woocommerce/">' . __( 'WooCommerce', 'woocommerce-payment-discounts' ) . '</a>' ) . '</p></div>';
	}
}

/**
 * Install plugin default options.
 */
register_activation_hook( __FILE__, array( 'WC_Payment_Discounts', 'activate' ) );

/**
 * Initialize the plugin actions.
 */
add_action( 'plugins_loaded', array( 'WC_Payment_Discounts', 'get_instance' ) );

endif;
