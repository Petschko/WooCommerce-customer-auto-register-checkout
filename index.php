<?php
/**
 * Plugin Name: Register after checkout
 * Plugin URI: -
 * Description: Creates an Wordpress-Account for the Customer after the Woo-Checkout
 * Version: 1.0.0
 * Author: Peter Dragicevic
 * Author URI: https://petschko.org/
 */

namespace Petschko\Wordpress\WooCommerce\AutoRegister;

use WC_Order;

defined('ABSPATH') or die('No script kiddies please!');

define('ACTION_PREFIX', 'auto_register_');
define('NAMESPACE_HOOK', 'Petschko\\Wordpress\\WooCommerce\\AutoRegister\\');

require_once('hooks.php');

/**
 * Register a Guest after a Order
 *
 * @param int $orderId - Order-Id
 */
function wcRegisterGuests($orderId) {
	if(! $orderId || is_user_logged_in())
		return;

	$order = new WC_Order($orderId);
	$orderEmail = $order->get_billing_email();

	if(username_exists($orderEmail) || email_exists($orderEmail))
		return;

	do_action(ACTION_PREFIX . 'before_account_creation', $order);

	// Create user, send mail and auto-login
	$userId = createUserFromOrder($orderEmail, $order);
	if(! $userId)
		return;

	do_action(ACTION_PREFIX . 'after_account_creation', $userId);
}

/**
 * Inits this Plugin
 */
function init() {
	add_action('woocommerce_checkout_order_processed', NAMESPACE_HOOK . 'wcRegisterGuests', 10, 1);
	add_action(ACTION_PREFIX . 'update_user_meta', NAMESPACE_HOOK . 'updateUserMeta', 10, 2);
	add_action(ACTION_PREFIX . 'change_display_name', NAMESPACE_HOOK . 'updateDisplayName', 10, 2);
	add_action(ACTION_PREFIX . 'after_account_creation', NAMESPACE_HOOK . 'sendCreationMail', 10, 1);
	add_action(ACTION_PREFIX . 'after_account_creation', NAMESPACE_HOOK . 'logUserIn', 20, 1);
}
add_action('wp_loaded', NAMESPACE_HOOK . 'init');
