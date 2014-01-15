<?php
/**
 * WooCommerce Discounts Per Payment Method plugin.
 *
 * @package   WC_Payment_Discounts_Admin
 * @author    Claudio Sanches <contato@claudiosmweb.com>
 * @license   GPL-2.0+
 */

/**
 * WC_Payment_Discounts_Admin class.
 *
 * @package WC_Payment_Discounts_Admin
 * @author  Claudio Sanches <contato@claudiosmweb.com>
 */
class WC_Payment_Discounts_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @var   object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin admin.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->plugin_slug = WC_Payment_Discounts::get_plugin_slug();
		$this->init();
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
	 * Initialize the plugin admin actions only has WooCommerce activated.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	protected function init() {
		if ( WC_Payment_Discounts::has_woocommerce() ) {
			// Add the options page and menu item.
			add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

			// Register plugin settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Need update message.
			if ( ! version_compare( WC_Payment_Discounts::woocommerce_instance()->version, '2.0', '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'woocommerce_need_update' ) );
			}

			// Plugin need update.
			if ( ! version_compare( get_option( 'woocommerce_payment_discounts_version' ), WC_Payment_Discounts::VERSION, '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'needs_update' ) );
				$this->update();
			}
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_is_missing_notice' ) );
		}
	}

	/**
	 * Update the plugin.
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Payment Discounts', $this->plugin_slug ),
			__( 'Payment Discounts', $this->plugin_slug ),
			'manage_woocommerce',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since  2.0.0
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'woocommerce_payment_discounts_group', 'woocommerce_payment_discounts', array( $this, 'validate_settings' ) );
	}

	/**
	 * Validate the plugin settings.
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
	 *
	 * @return string Settings page content.
	 */
	public function display_plugin_admin_page() {
		$settings         = get_option( 'woocommerce_payment_discounts' );
		$payment_gateways = WC_Payment_Discounts::woocommerce_instance()->payment_gateways->payment_gateways();

		include_once plugin_dir_path( __FILE__ ) . 'views/admin.php';
	}

	/**
	 * WooCommerce missing notice.
	 *
	 * @since  2.0.0
	 *
	 * @return string Admin notice.
	 */
	public function woocommerce_is_missing_notice() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Discounts Per Payment Method</strong> depends on the last version of %s to work!', $this->plugin_slug ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">' . __( 'WooCommerce', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}

	/**
	 * WooCommerce need update.
	 *
	 * @since  2.0.0
	 *
	 * @return string Admin notice.
	 */
	public function woocommerce_need_update() {
		echo '<div class="error"><p>' . __( '<strong>WooCommerce Discounts Per Payment Method</strong> works only with WooCommerce 2.0 or higher, please, upgrade you WooCommerce!', $this->plugin_slug ) . '</p></div>';
	}

	/**
	 * This plugin needs update.
	 *
	 * @since  2.0.0
	 *
	 * @return string Admin notice.
	 */
	public function needs_update() {
		echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Discounts Per Payment Method</strong> has been updated! Much has changed, you can now configure discount each payment method. %s.', $this->plugin_slug ), '<a href="' . admin_url( 'admin.php?page=woocommerce-payment-discounts&wc-payment-discounts-update=1' ) . '">' . __( 'Configure now the discounts', $this->plugin_slug ) . '</a>' ) . '</p></div>';
	}
}
