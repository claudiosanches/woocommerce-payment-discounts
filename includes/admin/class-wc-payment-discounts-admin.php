<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Payment_Discounts_Admin class
 */
class WC_Payment_Discounts_Admin {

	/**
	 * Initialize the plugin admin.
	 */
	public function __construct() {
		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Need update message.
		if ( defined( 'WOOCOMMERCE_VERSION' ) && ! version_compare( WOOCOMMERCE_VERSION, '2.0', '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_need_update' ) );
		}

		// Plugin need update.
		if ( ! version_compare( get_option( 'woocommerce_payment_discounts_version' ), WC_Payment_Discounts::VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'needs_update' ) );

			$this->update();
		}
	}

	/**
	 * Update the plugin.
	 *
	 * @return void
	 */
	protected function update() {
		if ( isset( $_GET['wc-payment-discounts-update'] ) && '1' == $_GET['wc-payment-discounts-update'] ) {
			update_option( 'woocommerce_payment_discounts', array() );
			update_option( 'woocommerce_payment_discounts_version', WC_Payment_Discounts::VERSION );
			delete_option( 'wcpaydisc_settings' );
			delete_option( 'wcpaydisc_gateways' );
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Payment Discounts', 'woocommerce-payment-discounts' ),
			__( 'Payment Discounts', 'woocommerce-payment-discounts' ),
			'manage_woocommerce',
			'woocommerce-payment-discounts',
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'woocommerce_payment_discounts_group', 'woocommerce_payment_discounts', array( $this, 'validate_settings' ) );
	}

	/**
	 * Validate the plugin settings.
	 *
	 * @param  array $options Submitted values.
	 *
	 * @return array          Fixed values.
	 */
	public function validate_settings( $options ) {
		$output = array();

		foreach ( $options as $key => $value ) {
			if ( isset( $options[ $key ] ) ) {
				if ( strstr( $value, '%' ) ) {
					$value = str_replace( '%', '', floatval( $value ) ) . '%';
				} else {
					$value = floatval( $value );
				}

				$output[ $key ] = $value;
			}
		}

		return $output;
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @return string Settings page content.
	 */
	public function display_plugin_admin_page() {
		global $woocommerce;

		$settings         = get_option( 'woocommerce_payment_discounts' );
		$payment_gateways = $woocommerce->payment_gateways->payment_gateways();

		include_once( 'views/html-admin-settings.php' );
	}

	/**
	 * WooCommerce need update.
	 *
	 * @return string Admin notice.
	 */
	public function woocommerce_need_update() {
		echo '<div class="error"><p>' . __( '<strong>WooCommerce Discounts Per Payment Method</strong> works only with WooCommerce 2.0 or higher, please, upgrade you WooCommerce!', 'woocommerce-payment-discounts' ) . '</p></div>';
	}

	/**
	 * This plugin needs update.
	 *
	 * @return string Admin notice.
	 */
	public function needs_update() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Discounts Per Payment Method</strong> has been updated! Much has changed, you can now configure discount each payment method. %s.', 'woocommerce-payment-discounts' ), '<a href="' . admin_url( 'admin.php?page=woocommerce-payment-discounts&wc-payment-discounts-update=1' ) . '">' . __( 'Configure now the discounts', 'woocommerce-payment-discounts' ) . '</a>' ) . '</p></div>';
	}
}

new WC_Payment_Discounts_Admin();
