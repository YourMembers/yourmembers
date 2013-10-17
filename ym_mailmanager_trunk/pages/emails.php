<?php

do_action('mailmanager_view_emails');
if (defined('STOP_VIEW_EMAILS')) {
	return;
}

ym_box_top(__('Email List', 'ym_mailmanager'));
echo '<table class="form-table">';
echo '<tr><th>' . __('Email Name', 'ym_mailmanager') . '</th><th>' . __('Email Subject', 'ym_mailmanager') . '</th><th>' . __('Preview', 'ym_mailmanager') . '</th><th>' . __('Edit', 'ym_mailmanager') . '</th></tr>';

$emails = array();

$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mm_email ORDER BY name';
foreach ($wpdb->get_results($sql) AS $email) {
	$emails[] = $email;
}
$emails = apply_filters('mailmanager_view_emails_filter', $emails);

foreach ($emails AS $email) {
	echo '<tr>';
	echo '<td>' . $email->name . '</td>';
	echo '<td>' . $email->subject . '</td>';
	echo '<td><a href="' . $mm->page_root . '&mm_action=preview&iframe_preview=' . $email->id . '" class="previewlink">' . __('Email Preview', 'ym_mailmanager') . '</a></td>';
	echo '<td><a href="' . $mm->page_root . '&mm_action=create&email_id=' . $email->id . '">' . __('Edit', 'ym_mailmanager') . '</a></td>';
	echo '</tr>';
}

echo '</table>';
ym_box_bottom();
