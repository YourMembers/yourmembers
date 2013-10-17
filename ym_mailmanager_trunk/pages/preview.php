<?php

$passed_id = isset($passed_id) ? $passed_id : 0;
$email_id = ym_get('mm_eid');
$iframe_id = ym_get('iframe_preview');

global $mm, $wpdb;

if ($email_id || $passed_id) {
	ym_box_top(__('Message Preview', 'ym_mailmanager'));
	echo '<div style="width: 900px;"><iframe src="' . $mm->page_root . '&mm_action=preview&iframe_preview=' . ($passed_id ? $passed_id : $email_id) . '" style="width: 100%; height: 400px;" /></div>';
	ym_box_bottom();
} else if ($iframe_id) {
	
	do_action('mailmanager_email_preview', $iframe_id);
	if (defined('STOP_PREVIEW')) {
		return;
	}
	
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mm_email WHERE id = ' . $iframe_id;
	$r = $wpdb->get_row($sql);
	
	get_currentuserinfo();
	global $current_user;
	
	list($subject, $body) = mailmanager_process_hooks($iframe_id, false, $current_user->ID);
	
	echo '<pre>' . $body . '</pre>';
	exit;
}