# WooCommerce Discounts Per Payment Method #
**Contributors:** claudiosanches  
**Donate link:** http://claudiosmweb.com/doacoes/  
**Tags:** woocommerce, discount, coupon  
**Requires at least:** 4.0  
**Tested up to:** 4.6  
**Stable tag:** 2.3.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Adds discounts on specific payment methods in WooCommerce.

## Description ##

This plugin allows you to add discounts per payment methods.  
Just set a amount (fixed  or %) to the payment methods you want to give discount and is ready!  
At the moment of payment the customer will be notified of discounts for each payment method.

### Contribute ###

You can contribute to the source code in our [GitHub](https://github.com/claudiosmweb/woocommerce-payment-discounts) page.

## Installation ##

* Upload plugin files to your plugins folder, or install using WordPress built-in Add New Plugin installer;
* Activate the plugin;
* Navigate to WooCommerce -> Payment Discounts and fill the options for each payment method.

## Frequently Asked Questions ##

### What is the plugin license? ###

This plugin is released under a GPL license.

### What is needed to use this plugin? ###

[WooCommerce](http://wordpress.org/plugins/woocommerce/) 2.1 or later.

### The discounts not being applied. How I fix this? ###

This error may be caused by some malfunctioning javascript in your theme. Just fix it!

### Need more help or want to make a suggestion? ###

Create an issue in our [GitHub](https://github.com/claudiosmweb/woocommerce-payment-discounts) page.

### Works with "WooCommerce German Market"? ###

Nop, maybe because this plugin uses the "fees" system from WooCommerce to apply discounts and unfortunately the "WooCommerce German Market" plugin does not work very well with it.

If you want to help us work around this problem, please send an [Pull Request on Github](https://github.com/claudiosmweb/woocommerce-payment-discounts).

## Screenshots ##

### 1. Settings page. ###
![1.Settings page.](https://ps.w.org/woocommerce-payment-discounts/assets/screenshot-1.png)

### 2. Plugin in action on checkout page. ###
![2. Plugin in action on checkout page.](https://ps.w.org/woocommerce-payment-discounts/assets/screenshot-2.png)


## Changelog ##

### 2.3.0 - 2016/10/01 ###

* Added option to include or not taxes in the discount amount.

### 2.2.2 - 2016/09/20 ###

* Fixed discount when taxes are applied.

### 2.2.1 - 2015/06/21 ###

* Fixed the WooCommerce version check.

### 2.2.0 - 2015/06/21 ###

* Added new method to apply discount with the WooCommerce "fee" system (Thanks to [Patrick Meister](https://github.com/extrapixel)).
* Fixed issues with discounts and wrong taxes calculation (see FAQ note about "WooCommerce German Market").
* Fixed the payment method title on admin (removed the discount value HTML).
* Removed support for WooCommerce 2.0.x (works great with WooCommerce 2.1.x or later).

### 2.1.4 - 2015/02/17 ###

* Added method to save the discount in the order data.

### 2.1.3 - 2015/02/17 ###

* Fixed the plugin update script.

### 2.1.2 - 2015/02/16 ###

* Added the `wc_payment_discounts_apply_discount` filter.

### 2.1.1 - 2015/02/16 ###

* Added support for WooCommerce 2.3.
* Discounts are now applied in the cart contents total, before shipping and taxes.

### 2.0.3 - 2014/02/24 ###

* Added the `wc_payment_discounts_row` filter.

### 2.0.2 - 2014/02/10 ###

* Added de_DE translation by [@chrwald](https://github.com/chrwald).

### 2.0.1 - 2014/01/28 ###

* Fixed a bug that allowed to add the discount in the cart before the customer arrive at the checkout page.

### 2.0.0 - 2014/01/27 ###

* Improved all plugin code.
* Added option to add the discounts without coupons.

## Upgrade Notice ##

### 2.3.0 ###

* Added option to include or not taxes in the discount amount.
