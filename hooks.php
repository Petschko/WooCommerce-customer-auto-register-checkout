<?php
/**
 * Author: Peter Dragicevic [peter@petschko.org]
 * Authors-Website: https://petschko.org/
 * Date: 16.08.2019
 *
 * Notes: -
 */

namespace Petschko\Wordpress\WooCommerce\AutoRegister;

use WC_Order;
use WP_User;

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Sends a Registration Mail to the current user
 *
 * @param int $userId - User-Id
 */
function sendCreationMail($userId) {
	if(! $userId)
		return;

	do_action(ACTION_PREFIX . 'before_creation_mail', $userId);

	WC()->mailer()->customer_new_account($userId);

	do_action(ACTION_PREFIX . 'after_creation_mail', $userId);
}

/**
 * Auto-Login the given User
 *
 * @param int $userId - User-Id
 */
function logUserIn($userId) {
	if(! $userId || is_user_logged_in())
		return;

	wp_set_auth_cookie($userId);
}

/**
 * Creates a Customer from Order
 *
 * @param string $email - E-Mail of the user
 * @param WC_Order $order - Order-Object
 * @return null|int - User-Id or null
 */
function createUserFromOrder($email, $order) {
	$username = $email;
	$password = wp_generate_password();

	// Add a Hook in case values should be changed
	$username = apply_filters(ACTION_PREFIX . 'username', $username);
	$password = apply_filters(ACTION_PREFIX . 'password', $password);

	$userId = wp_create_user($username, $password, $email);
	if(! is_int($userId))
		return null;

	// Add correct User-Role
	$wpUser = new WP_User($userId);
	$newRole = apply_filters(ACTION_PREFIX . 'user_role', 'customer');
	$wpUser->set_role($newRole);

	// Change display name
	do_action(
		ACTION_PREFIX . 'change_display_name',
		$userId,
		mb_strtolower($order->get_billing_first_name()) . '.' . mb_strtolower($order->get_billing_last_name())
	);

	// User Meta-Update
	do_action(ACTION_PREFIX . 'update_user_meta', $userId, $order);
	do_action(ACTION_PREFIX . 'after_update_user_meta', $userId);

	// Update past orders from this user
	wc_update_new_customer_past_orders($userId);

	return $userId;
}

/**
 * Update User Meta
 *
 * @param int $userId - User-Id
 * @param WC_Order $order - Order Object
 */
function updateUserMeta($userId, $order) {
	if(! $userId || ! $order)
		return;

	// Misc
	update_user_meta($userId, 'first_name', $order->get_billing_first_name());
	update_user_meta($userId, 'last_name', $order->get_billing_last_name());

	// Billing data
	if(get_post_meta($order->get_id(), '_billing_title', true))
		update_user_meta($userId, 'billing_title', get_post_meta($order->get_id(), '_billing_title', true));
	update_user_meta($userId, 'billing_address_1', $order->get_billing_address_1());
	update_user_meta($userId, 'billing_address_2', $order->get_billing_address_2());
	update_user_meta($userId, 'billing_city', $order->get_billing_city());
	update_user_meta($userId, 'billing_company', $order->get_billing_company());
	update_user_meta($userId, 'billing_country', $order->get_billing_country());
	update_user_meta($userId, 'billing_email', $order->get_billing_email());
	update_user_meta($userId, 'billing_first_name', $order->get_billing_first_name());
	update_user_meta($userId, 'billing_last_name', $order->get_billing_last_name());
	update_user_meta($userId, 'billing_phone', $order->get_billing_phone());
	update_user_meta($userId, 'billing_postcode', $order->get_billing_postcode());
	update_user_meta($userId, 'billing_state', $order->get_billing_state());

	// Shipping data
	if(get_post_meta($order->get_id(), '_shipping_title', true))
		update_user_meta($userId, 'shipping_title', get_post_meta($order->get_id(), '_shipping_title', true));
	update_user_meta($userId, 'shipping_address_1', $order->get_shipping_address_1());
	update_user_meta($userId, 'shipping_address_2', $order->get_shipping_address_2());
	update_user_meta($userId, 'shipping_city', $order->get_shipping_city());
	update_user_meta($userId, 'shipping_company', $order->get_shipping_company());
	update_user_meta($userId, 'shipping_country', $order->get_shipping_country());
	update_user_meta($userId, 'shipping_first_name', $order->get_shipping_first_name());
	update_user_meta($userId, 'shipping_last_name', $order->get_shipping_last_name());
	update_user_meta($userId, 'shipping_method', $order->get_shipping_method());
	update_user_meta($userId, 'shipping_postcode', $order->get_shipping_postcode());
	update_user_meta($userId, 'shipping_state', $order->get_shipping_state());
}

/**
 * Updates the Display-Name
 *
 * @param int $userId - User-Id
 * @param string $displayName - New Display-Name
 */
function updateDisplayName($userId, $displayName) {
	$displayName = apply_filters(ACTION_PREFIX . 'display_name', $displayName);

	if(! $userId || ! $displayName)
		return;

	wp_update_user(array(
		'ID' => $userId,
		'display_name' => $displayName
	));
}
