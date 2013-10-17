<?php

/*
* $Id: sql_update_4.php 1769 2012-01-09 09:54:32Z BarryCarlyon $
* $Revision: 1769 $
* $Date: 2012-01-09 09:54:32 +0000 (Mon, 09 Jan 2012) $
*/

$queries = array();

// ym_res emails
global $ym_res;

$ym_res->payment_gateway_subject_post = '[blogname] - Your Purchase of Post: [post_title]';
$ym_res->payment_gateway_message_post_success = 'Hello [display_name],<br /><br />Thank you for your Purchase of Post: [post_title].<br />The transaction is complete and you can now view the post<br />[post_link]<br /><br />[blogname]';
$ym_res->payment_gateway_message_post_failed = 'Hello [display_name],<br /><br />Thank you for your Purchase of Post: [post_title].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]';

$ym_res->payment_gateway_subject_ppack = '[blogname] - Your Purchase of Post Pack: [pack_title]';
$ym_res->payment_gateway_message_ppack_success = 'Hello [display_name],<br /><br />Thank you for your Purchase of Post Pack: [pack_name].<br />The transaction is complete and you can now view the posts within the pack<br />[posts_in_pack]<br /><br />[blogname]';
$ym_res->payment_gateway_message_ppack_failed = 'Hello [display_name],<br /><br />Thank you for your Purchase of Post Pack: [pack_name].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]';

$ym_res->payment_gateway_subject_subscription = '[blogname] - Your Purchase of Subscription: [pack_label]';
$ym_res->payment_gateway_message_subscription_success = 'Hello [display_name],<br /><br />Thank you for your Purchase of Subscription: [pack_label].<br />Your subscription has started and is valid until: [pack_expire]<br /><br />[blogname]';
$ym_res->payment_gateway_message_subscription_failed = 'Hello [display_name],<br /><br />Thank you for your Purchase of Subscription: [pack_label].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]';
update_option('ym_res', $ym_res);

// gateway img fix
global $ym_active_modules;

foreach ((array)$ym_active_modules as $key => $module) {
	$gw = new $module();
	$logo = $gw->logo;
	$logo = explode('/', $logo);
	$logo = array_pop($logo);
	$logo = YM_IMAGES_DIR_URL . '/pg/' . $logo;
	$gw->logo = $logo;
	$gw->save();
}