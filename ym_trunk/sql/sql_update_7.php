<?php

/*
* $Id: sql_update_7.php 2085 2012-04-11 09:44:10Z bcarlyon $
* $Revision: 2085 $
* $Date: 2012-04-11 10:44:10 +0100 (Wed, 11 Apr 2012) $
*/

$queries = array();

global $ym_sys;
$ym_sys->upgrade_downgrade_string = __('Upgrade / Downgrade your account', 'ym');
$ym_sys->register_https_only = FALSE;
$ym_sys->register_https_pages = '';
$ym_sys->register_https_escape = FALSE;

$ym_sys->expire_time_hour = 0;
$ym_sys->expire_time_min = 0;
$ym_sys->expire_time_sec = 0;

$ym_sys->block_wp_login_action_register = '';

$ym_sys->filter_all_emails = TRUE;

update_option('ym_sys', $ym_sys);

global $ym_res;
$ym_res->payment_gateway_enable_post_success = TRUE;
$ym_res->payment_gateway_enable_post_failed = TRUE;
$ym_res->payment_gateway_enable_ppack_success = TRUE;
$ym_res->payment_gateway_enable_ppack_failed = TRUE;
$ym_res->payment_gateway_enable_subscription_success = TRUE;
$ym_res->payment_gateway_enable_subscription_failed = TRUE;

$ym_res->payment_gateway_email_post_success = '';
$ym_res->payment_gateway_email_post_failed = '';
$ym_res->payment_gateway_email_ppack_success = '';
$ym_res->payment_gateway_email_ppack_failed = '';
$ym_res->payment_gateway_email_subscription_success = '';
$ym_res->payment_gateway_email_subscription_failed = '';

$ym_res->save();
