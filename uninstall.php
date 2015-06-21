<?php
// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
delete_option( 'woocommerce_payment_discounts' );
delete_option( 'woocommerce_payment_discounts_version' );
