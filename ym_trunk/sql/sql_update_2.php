<?php

/*
* $Id: sql_update_2.php 1933 2012-02-22 09:54:10Z bcarlyon $
* $Revision: 1933 $
* $Date: 2012-02-22 09:54:10 +0000 (Wed, 22 Feb 2012) $
*/

$queries = array(
	'ym_register_flows' => '
CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_register_flows` (
  `flow_id` int(11) NOT NULL AUTO_INCREMENT,
  `flow_name` varchar(255) NOT NULL,
  `flow_pages` text NOT NULL,
  `complete_url` varchar(255) NOT NULL,
  `complete_text` text NOT NULL,
  PRIMARY KEY (`flow_id`),
  UNIQUE KEY `flow_name` (`flow_name`)
) ENGINE=MYISAM;',
	'ym_register_pages' => '
CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_register_pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(255) NOT NULL,
  `page_fields` text NOT NULL,
  PRIMARY KEY (`page_id`)
) ENGINE=MYISAM;',

  'ym_post_packs_purchased' => '
CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_post_packs_purchased` (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id integer NULL,
  pack_id integer NOT NULL,
  payment_method varchar(255) NULL,
  unixtime integer
) ENGINE=MYISAM;',
  'ym_post_pack_expire' => '
ALTER TABLE ' . $wpdb->prefix . 'ym_post_pack ADD `expiretime` int(11) NOT NULL
',
  'ym_coupon' => '
ALTER TABLE ' . $wpdb->prefix . 'ym_coupon ADD `allowed` VARCHAR( 4 ) NOT NULL DEFAULT  "0000" AFTER  `description`
',
  'ym_coupon_b' => '
ALTER TABLE ' . $wpdb->prefix . 'ym_coupon ADD `usage_limit` INT( 11 ) NOT NULL DEFAULT  "0" AFTER  `allowed`
',
  'ym_coupon_use' => '
CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_coupon_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purchased` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;',
  '10_0_8' => '
ALTER TABLE ' . $wpdb->prefix . 'posts_purchased ADD COLUMN payment_method VARCHAR(255) NULL',
  'ym_post_pack' => '
ALTER TABLE  ' . $wpdb->prefix . 'ym_post_pack ADD `additional` VARCHAR( 255 ) NOT NULL ,
ADD `purchaseexpire` INT( 11 ) NOT NULL ,
ADD `purchaselimit` INT( 11 ) NOT NULL ,
ADD `saleend` INT( 11 ) NOT NULL',

  'ym_transaction' => '
ALTER TABLE ' . $wpdb->prefix . 'ym_transaction ADD `transaction_id` INT( 11 ) NOT NULL DEFAULT  "0" AFTER  `id`',
  'ym_new_trans' => '
INSERT INTO ' . $wpdb->prefix . 'ym_transaction_action (name, description) VALUES (\'Package Purchased\', \'The Package ID Purchased\')',
);

/**
YM Res
*/
global $ym_res;
if (!$ym_res->ppp_page_login) {
  $ym_res->ppp_page_login = '<div style="margin: 10px 0px;">You need to be logged in to buy posts</div>';
}
  
if (!$ym_res->ppp_page_start) {
  $ym_res->ppp_page_start = '<div>[submit_button]';
  $ym_res->ppp_page_row = '<div>[checkbox] [cost] <a href="[link]">[title]</a></div><div>[excerpt]</div>';
  $ym_res->ppp_page_end = '[submit_button]</div>';
}

$ym_res->purchasable_bundle_at_limit = __('Only a limited number of this bundle were available. They have now all been bought, keep an eye on this page in case the quota is lifted', 'ym');

$ym_res->checkemail_subscribed = __('Subscription complete. You may now Login.', 'ym');
$ym_res->checkemail_bundle = __('Bundle Purchase complete. Please Login to continue.', 'ym');
$ym_res->checkemail_post = __('Post Purchase complete. Please Login to continue.', 'ym');
$ym_res->checkemail_loginneeded = __('Please Login to Continue', 'ym');
$ym_res->checkemail_noaccess = __('You cannot access this content', 'ym');

update_option('ym_res', $ym_res);

/**
YM Sys
*/
global $ym_sys;
unset($ym_sys->subscription_options_label);
$ym_sys->required_custom_field_symbol = __('* Required', 'ym');

$ym_sys->protect_mode = $ym_sys->ym_hide_posts ? 1 : ($ym_sys->magic_mode ? 1 : 0);
unset($ym_sys->ym_hide_posts);
unset($ym_sys->magic_mode);

$ym_sys->no_access_redirect_lo = $ym_sys->no_access_redirect_lo ? $ym_sys->no_access_redirect_lo : '/wp-login.php?checkemail=loginneeded';
$ym_sys->no_access_redirect_li = $ym_sys->no_access_redirect_li ? $ym_sys->no_access_redirect_li : '/wp-login.php?checkemail=noaccess';
$ym_sys->hide_pages = 0;
$ym_sys->hide_posts = 0;

$ym_sys->wp_login_header_url = '/';

$ym_sys->email_reminder_subject_recur = 'Your account will renew soon';
$ym_sys->email_reminder_message_recur = 'This is just a reminder to say your subscription is soon to be renewed and payment taken.';
$ym_sys->wp_login_header_logo = '';

$ym_sys->logout_redirect_url = '';
$ym_sys->email_reminder_subject = '[[site_name]] ' . $ym_sys->email_reminder_subject;
$ym_sys->email_reminder_subject_recur = '[[site_name]] ' . $ym_sys->email_reminder_subject_recur;
$ym_sys->membership_details_redirect_url = '';

update_option('ym_sys', $ym_sys);

/**
YM Packs
*/
$packs = get_option('ym_packs');
foreach ($packs->packs as $id => $pack) {
  $pack['admin_name'] = isset($pack['admin_name']) ? $pack['admin_name'] : '';
  $pack['gateway_disable'] = isset($pack['gateway_disable']) ? $pack['gateway_disable'] : array();

  $packs->packs[$id] = $pack;
}
update_option('ym_packs', $packs);

/**
custom fields upgrade
*/
$source = get_option('ym_custom_fields');

$exchange = array(
  'Subscription Introduction',
  'Subscription Options',
  'Terms and Conditions',
  'Birthdate',
  'Country',
  'Coupon',
);

$to_add = array(
  'subscription_introduction' => array(
    'id' => '',
    'name' => 'subscription_introduction',
    'label' => 'Subscription Introduction',
    'available_values' => '',
    'caption' => '',
    'type' => 'textarea',
    'required' => false,
    'readonly' => true,
    'profile_only' => false,
    'no_profile' => false,
    'value' => '',
    'builtin' => 1
  ),
  'subscription_options' => array(
    'id' => '',
    'name' => 'subscription_options',
    'label' => 'Subscription Options',
    'available_values' => '',
    'caption' => '',
    'type' => 'textarea',
    'required' => true,
    'readonly' => true,
    'profile_only' => false,
    'no_profile' => false,
    'value' => '',
    'builtin' => 1
  ),
  'terms_and_conditions' => array(
    'id' => '',
    'name' => 'terms_and_conditions',
    'label' => 'Terms and Conditions',
    'available_values' => '',
    'caption' => '',
    'type' => 'textarea',
    'required' => true,
    'readonly' => true,
    'profile_only' => false,
    'no_profile' => false,
    'value' => '',
    'builtin' => 1
  ),
  'user_email' => array(
    'id' => '',
    'name' => 'user_email',
    'label' => 'Email',
    'available_values' => '',
    'caption' => '',
    'type' => 'text',
    'required' => true,
    'readonly' => false,
    'profile_only' => true,
    'no_profile' => false,
    'value' => '',
    'builtin' => 1
  ),
  'first_name' => array(
    'id' => '',
    'name' => 'first_name',
    'label' => 'First Name',
    'available_values' => '',
    'caption' => '',
    'type' => 'text',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 1
  ),
  'last_name' => array(
    'id' => '',
    'name' => 'last_name',
    'label' => 'Last Name',
    'available_values' => '',
    'caption' => '',
    'type' => 'text',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 1
  ),
  'ym_password' => array(
    'id' => '',
    'name' => 'ym_password',
    'label' => 'Password',
    'available_values' => '',
    'caption' => '',
    'type' => 'password',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 1
  ),
  'user_url' => array(
    'id' => '',
    'name' => 'user_url',
    'label' => 'Website',
    'available_values' => '',
    'caption' => '',
    'type' => 'text',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 1
  )
);
$copy = $to_add;

$max_id = 0;

foreach ($source->entries as $id => $data) {
  $ndata = array(
    'id' => '',
    'name' => '',
    'label' => '',
    'type' => '',
    'caption' => '',
    'available_values' => '',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 0
  );

  foreach ($data as $k => $i) {
    $ndata[$k] = $i;
  }

  if (in_array($data['name'], $exchange)) {
    $ndata['label'] = $data['name'];
    $ndata['name'] = strtolower(str_replace(' ', '_', $data['name']));
    $ndata['builtin'] = 1;
  }
  $max_id = $data['id'] > $max_id ? $data['id'] : $max_id;

  $source->entries[$id] = $ndata;

  unset($to_add[$data['label']]);
  unset($to_add[$data['name']]);
}

foreach ($to_add as $label => $data) {
  $max_id ++;
  $data['id'] = $max_id;
  $source->entries[] = $data;
}
$max_id++;
$source->next_id = $max_id;

// repair built ins
$exchange = array(
  'Terms and Conditions',
  'Subscription Introduction',
  'Subscription Options',
  'Birthdate',
  'Country',
);
foreach ($copy as $label => $data) {
  $exchange[] = $label;
}
foreach ($source->entries as $id => $data) {
  $ndata = array(
    'id' => '',
    'name' => '',
    'label' => '',
    'type' => '',
    'caption' => '',
    'available_values' => '',
    'required' => '',
    'readonly' => '',
    'profile_only' => '',
    'no_profile' => '',
    'value' => '',
    'builtin' => 0
  );

  foreach ($data as $k => $i) {
    $ndata[$k] = $i;
  }
  if (in_array($data['name'], $exchange)) {
    $ndata['builtin'] = 1;
  }
  $source->entries[$id] = $ndata;
}

update_option('ym_custom_fields', $source);
