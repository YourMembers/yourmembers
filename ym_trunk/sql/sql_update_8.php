<?php

/*
* $Id: sql_update_8.php 2596 2013-02-07 16:41:09Z bcarlyon $
* $Revision: 2596 $
* $Date: 2013-02-07 16:41:09 +0000 (Thu, 07 Feb 2013) $
*/

$queries = array(
	'ym_coupon_use_tos' => 'ALTER TABLE `' . $wpdb->prefix . 'ym_coupon_use` ADD `tos` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
	'ym_register_flows_button_text' => 'ALTER TABLE `' . $wpdb->prefix . 'ym_register_flows` ADD `complete_button` VARCHAR(255) NOT NULL DEFAULT "Complete"',
	'ym_register_pages_button_text' => 'ALTER TABLE `' . $wpdb->prefix . 'ym_register_pages` ADD `button_text` VARCHAR(255) NOT NULL DEFAULT "Next"',
);

global $ym_res;
$ym_old_res = get_option('ym_res');

$ym_res->payment_gateway_subject_post_success = $ym_old_res->payment_gateway_subject_post;
$ym_res->payment_gateway_subject_post_failed = $ym_old_res->payment_gateway_subject_post;
$ym_res->payment_gateway_subject_ppack_success = $ym_old_res->payment_gateway_subject_ppack;
$ym_res->payment_gateway_subject_ppack_failed = $ym_old_res->payment_gateway_subject_ppack;
$ym_res->payment_gateway_subject_subscription_success = $ym_old_res->payment_gateway_subject_subscription;
$ym_res->payment_gateway_subject_subscription_failed = $ym_old_res->payment_gateway_subject_subscription;

$ym_res->registration_flow_email_invalid = __('The Email Address is invalid', 'ym');
$ym_res->registration_flow_email_inuse = __('That Email Address is already in use', 'ym');
$ym_res->registration_flow_username_inuse = __('That Username is already in use', 'ym');
$ym_res->registration_flow_required_fields = __('Please fill in the required fields', 'ym');
$ym_res->registration_flow_invalid_coupon = __('The coupon is invalid, or has reached its usage limit', 'ym');

$ym_res->save();

// flip the packages

// temp class to avoid incomplete class error
class YourMember_Account_Types {}

// db load
$query = 'SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = \'ym_account_types\'';
$ym_account_types = unserialize($wpdb->get_var($query));

$ym_package_types = new YourMember_Package_Types();
$ym_package_types->initialise();
foreach ($ym_account_types->types as $type) {
	$ym_package_types->create($type);
}
delete_option('ym_account_types');
