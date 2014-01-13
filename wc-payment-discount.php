<?php
/**
 * Plugin Name: WooCommerce Payment Discounts
 * Plugin URI: http://claudiosmweb.com/
 * Description: Adds discounts on specific payment methods in WooCommerce.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 1.2
 * License: GPLv2 or later
 * Text Domain: wcpaydisc
 * Domain Path: /languages/
 */

class WC_Payment_Discounts {

	public function __construct() {

		// Load textdomain.
		add_action( 'plugins_loaded', array( &$this, 'languages' ), 0 );

		// Add menu.
		add_action( 'admin_menu', array( &$this, 'menu' ) );

		// Register Settings.
		add_action( 'admin_init', array( &$this, 'register_settings' ) );

		// Add scripts in checkout review.
		add_action( 'wp_footer', array( &$this, 'script' ) );

		// Register ajax hooks.
		add_action( 'wp_ajax_nopriv_wc_custom_payment_discount', array( &$this, 'ajax' ) );
		add_action( 'wp_ajax_wc_custom_payment_discount', array( &$this, 'ajax' ) );
	}

	/**
	 * Load translations.
	 */
	public function languages() {
		load_plugin_textdomain( 'wcpaydisc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add plugin settings menu.
	 */
	public function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Payment Discounts', 'wcpaydisc' ),
			__( 'Payment Discounts', 'wcpaydisc' ),
			'manage_options',
			'wc-payment-discounts',
			array( &$this, 'settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		//register our settings
		register_setting( 'wc_payment_discounts_group', 'wcpaydisc_settings' );
		register_setting( 'wc_payment_discounts_group', 'wcpaydisc_gateways' );
	}

	/**
	 * Built the options page.
	 */
	public function settings_page() {
		?>
			<div class="wrap">
				<?php screen_icon( 'options-general' ); ?>
				<h2><?php _e( 'Payment Discounts', 'wcpaydisc' ); ?></h2>
				<?php settings_errors(); ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'wc_payment_discounts_group' ); ?>
					<h3><?php _e( 'General Settings', 'wcpaydisc' ); ?></h3>
					<?php
						$settings = get_option( 'wcpaydisc_settings' );
						$active = isset( $settings['active'] ) ? 1 : 0;

						$gateways = get_option( 'wcpaydisc_gateways' );
					?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row"><?php _e( 'Active Discounts', 'wcpaydisc' ); ?></th>
								<td>
									<?php  ?>
									<input type="checkbox" value="1" name="wcpaydisc_settings[active]" id="wcpaydisc-settings-active"<?php checked( $active, 1 ); ?>> <label for="wcpaydisc-settings-active"><?php _e( 'Activate', 'wcpaydisc' ); ?></label>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Coupon Name', 'wcpaydisc' ); ?></th>
								<td>
									<input type="text" id="wcpaydisc-settings-coupon" name="wcpaydisc_settings[coupon]" value="<?php echo $settings['coupon']; ?>" class="regular-text" />
									<br /><span class="description"><?php echo sprintf( __( 'You need to create a %scoupon%s and indicate his name in this field.', 'wcpaydisc' ), '<a href="' . get_admin_url() . 'edit.php?post_type=shop_coupon">', '</a>' ) ; ?></span>
								</td>
							</tr>
						</tbody>
					</table>
					<h3><?php _e( 'Payment Methods', 'wcpaydisc' ); ?></h3>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row"><?php _e( 'Add discount in', 'wcpaydisc' ); ?></th>
								<td>
									<?php
										$valid_gateways = get_option( 'woocommerce_gateway_order' );

										if ( $valid_gateways ) {
											foreach ( $valid_gateways as $key => $value ) {
												$default = isset( $gateways[$key] ) ? 1 : 0;
												$name = str_replace( '_', ' ', ucwords( $key ) );
												$checked = checked( 1, $default, false );

												echo sprintf( '<input type="checkbox" id="%1$s-%2$s" name="wcpaydisc_gateways[%2$s]" value="1"%3$s /> <label for="%1$s-%2$s"> %4$s</label><br />', 'wcpaydisc-settings-gateway', $key, $checked , $name );
											}
										} else {
											echo sprintf( __( 'You must configure the payment methods before. Click %shere%s to configure.', 'wcpaydisc' ), '<a href="'. get_admin_url() . 'admin.php?page=woocommerce_settings&amp;tab=payment_gateways">', '</a>' );
										}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Discounts ajax request.
	 */
	public function script() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Generate discount function.
				function generate_discount(value) {
					// Call the discount.
					$.ajax({
						type: "POST",
						url: woocommerce_params.ajax_url,
						data: {
							action: "wc_custom_payment_discount",
							get_payment_method: value.val(),
							security: "<?php echo wp_create_nonce( 'wc-custom-payment-discount-nonce' ); ?>"
						},
						dataType: "json",
						success: function() {
							// Update the checkout.
							$("body").trigger("update_checkout");
						}
					});
				}

				// Generate discount on click.
				$('form.checkout, #order_review').on('change', '.payment_methods input.input-radio', function() {
					generate_discount($(this));
				});

				$("body").load(function() {
				   generate_discount($("#order_review input[name=payment_method]"));
				});
			});
		</script>
		<?php
	}

	/**
	 * Ajax action.
	 */
	public function ajax() {
		global $woocommerce;

		// Checks referer.
		check_ajax_referer( 'wc-custom-payment-discount-nonce', 'security' );

		// Get the settings.
		$settings = get_option( 'wcpaydisc_settings' );
		$active = isset( $settings['active'] ) ? 1 : 0;

		$gateways = get_option( 'wcpaydisc_gateways' );

		if ( $active ) {
			// Tests method.
			if ( ! empty( $_POST['get_payment_method'] ) && in_array( $_POST['get_payment_method'] ,array_keys( $gateways ) ) ) {
				$woocommerce->cart->add_discount( sanitize_text_field( $settings['coupon'] ) );
			} else {
				$woocommerce->cart->remove_coupons( sanitize_text_field( $settings['coupon'] ) );
			}
		}

		die();
	}
}

new WC_Payment_Discounts;
