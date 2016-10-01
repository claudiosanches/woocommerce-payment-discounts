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

		$this->maybe_update();
	}

	/**
	 * Maybe update the plugin.
	 */
	protected function maybe_update() {
		$current_version = get_option( 'woocommerce_payment_discounts_version', '0' );

		if ( ! version_compare( $current_version, WC_Payment_Discounts::VERSION, '>=' ) ) {
			// Upgrade to 2.3.0.
			if ( version_compare( $current_version, '2.3.0', '<' ) ) {
				$this->upgrade_to_230();
			}

			update_option( 'woocommerce_payment_discounts_version', WC_Payment_Discounts::VERSION );
		}
	}

	/**
	 * Upgrade to 2.3.0.
	 */
	private function upgrade_to_230() {
		if ( $old_options = get_option( 'woocommerce_payment_discounts' ) ) {
			$new_options = array();

			foreach ( $old_options as $key => $value ) {
				$new_options[ $key ] = array(
					'amount'      => $value,
					'include_tax' => 'yes',
				);
			}

			update_option( 'woocommerce_payment_discounts', $new_options );
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
			// Validate amount.
			$output[ $key ]['amount'] = 0;
			if ( isset( $value['amount'] ) ) {
				if ( strstr( $value['amount'], '%' ) ) {
					$amount = str_replace( '%', '', floatval( $value['amount'] ) ) . '%';
				} else {
					$amount = floatval( $value['amount'] );
				}

				$output[ $key ]['amount'] = $amount;
			}

			// Validate include tax.
			$output[ $key ]['include_tax'] = 'no';
			if ( isset( $value['include_tax'] ) ) {
				$output[ $key ]['include_tax'] = 'yes';
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
		$settings         = get_option( 'woocommerce_payment_discounts' );
		$payment_gateways = WC()->payment_gateways->payment_gateways();

		include_once 'views/html-admin-settings.php';
	}
}

new WC_Payment_Discounts_Admin();
