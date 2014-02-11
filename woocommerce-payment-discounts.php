<?php
/**
 * WooCommerce Discounts Per Payment Method plugin.
 *
 * @package   WC_Payment_Discounts
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Discounts Per Payment Method
 * Plugin URI:        http://wordpress.org/plugins/woocommerce-payment-discounts/
 * Description:       Adds discounts on specific payment methods in WooCommerce.
 * Version:           2.0.2
 * Author:            claudiosanches
 * Author URI:        http://claudiosmweb.com/
 * Text Domain:       woocommerce-payment-discounts
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/claudiosmweb/woocommerce-payment-discounts
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin main class.
 */
require_once plugin_dir_path( __FILE__ ) . 'public/class-wc-payment-discounts.php';

/**
 * Activated and deactivated actions.
 */
register_activation_hook( __FILE__, array( 'WC_Payment_Discounts', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WC_Payment_Discounts', 'deactivate' ) );

/**
 * Initialize the plugin actions.
 */
add_action( 'plugins_loaded', array( 'WC_Payment_Discounts', 'get_instance' ) );

/**
 * Initialize the plugin admin.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-wc-payment-discounts-admin.php';

	add_action( 'plugins_loaded', array( 'WC_Payment_Discounts_Admin', 'get_instance' ) );
}
