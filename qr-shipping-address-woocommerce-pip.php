<?php
/**
 * Plugin Name: Shipping Address QR code for Invoices & Packing lists
 * Plugin URI: https://www.webdados.pt/wordpress/plugins/shipping-address-qr-code-for-woocommerce-print-invoices-packing-lists-wordpress/
 * Description: Adds a QR Code with the Shipping Address link on Google Maps into several Invoices and Packing lists plugins.
 * Version: 1.5
 * Author: PT Woo Plugins (by Webdados)
 * Author URI: https://ptwooplugins.com
 * Requires at least: 5.4
 * Requires PHP: 7.0
 * WC requires at least: 5.0
 * WC tested up to: 8.4
 */

/* WooCommerce CRUD compatible */

require_once 'class-qr-code-wc-pip.php';

$qr_code_wc_pip = new QR_Code_WC_PIP();

/* HPOS Compatible */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */

