<?php

global $ym_formgen;

$email_id = ym_post('email_id');

$defaults = array(
    'email_subject' => '',
    'email_content' => '',
);
$postarr = wp_parse_args($_POST, $defaults);
$postarr = sanitize_post($postarr, 'db');

// export array as variables
extract($postarr, EXTR_SKIP);

$ym_month_email_date = ym_post('ym_month_email_date');
$ym_date_email_date = ym_post('ym_date_email_date');
$ym_year_email_date = ym_post('ym_year_email_date');
$ym_hour_email_date = ym_post('ym_hour_email_date');
$ym_min_email_date = ym_post('ym_min_email_date');

$recipient_list = ym_post('recipient_list');

do_action('mailmanager_broadcast_precontent');

if ($ym_month_email_date) {
	$time = array($ym_month_email_date, $ym_date_email_date, $ym_year_email_date, $ym_hour_email_date, $ym_min_email_date);
} else {
	$time = time();
}

if (!$email_id && (!$email_content || !$email_subject) && $_POST) {
	ym_box_top(__('Broadcast Error', 'ym_mailmanager'));
	echo '<p>' . __('You must provide a Email to send or fill in the a email content and subject', 'ym_mailmanager') . '</p>';
	ym_box_bottom();
} else if (ym_post('submit')) {
	// swotch the time back to unix time
	if (is_array($time)) {
		$value = array();
		$value['month'] = array_shift($time);
		$value['date'] = array_shift($time);
		$value['year'] = array_shift($time);
		$value['hour'] = array_shift($time);
		$value['min'] = array_shift($time);
		$time = mktime($value['hour'], $value['min'], 0, $value['month'], $value['date'], $value['year']);
	}
	
	global $wpdb;
	
	do_action('mailmanager_broadcast_create', $email_id, $email_subject, $email_content, $recipient_list, $time);
	if (defined('STOP_BROADCAST')) {
		return;
	}
	
	if (!$email_id) {
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_email(name, subject, body) VALUES (\'broadcast_' . $email_subject . '\', \'' . $email_subject . '\', \'' . $email_content . '\')';
		$wpdb->query($sql);
		$email_id = $wpdb->insert_id;
	}
	$args = array($email_id, $recipient_list);

	wp_schedule_single_event($time, 'mm_scheduled_email', $args);
	
	ym_box_top(__('Broadcast', 'ym_mailamager'));
	echo '<p>' . sprintf(__('Scheduled for %s it is currently %s', 'ym_mailmanager'), date('r', $time), date('r', time())) . '</p>';
	ym_box_bottom();
	$passed_id = $email_id;
	include('preview.php');
	global $mm;
	echo '<meta http-equiv="refresh" content="10;' . $mm->page_root . '" />';
	return;
}

ym_box_top(__('Broadcast', 'ym_mailmanager'));

echo '<form action="" method="post">';
echo '<p>' . __('Schedule or send immediately a message to selected Users', 'ym_mailmanager') . '</p>';

echo '<table class="form-table">';

define('BROADCAST_FORM_OPEN', TRUE);
do_action('mailmanager_broadcast_form');

if (!defined('MAILMANAGER_FORM_REPLACED')) {
	echo $ym_formgen->render_combo_from_array_row(__('Recipients', 'ym_mailmanager'), 'recipient_list', mailmanager_get_recipients(), $recipient_list, __('Select a List to send to', 'ym_mailmanager'));

	$emails = mailmanager_get_emails(TRUE);

	if ($emails) {
		echo $ym_formgen->render_combo_from_array_row(__('Select Prior Email', 'ym_mailmanager'), 'email_id', $emails, $email_id, __('Select a previously created email', 'ym_mailmanager'));
	}

	echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym_mailmanager'), 'email_subject', $email_subject, __('Subject of Message', 'ym_mailmanager'));
	$ym_formgen->render_form_table_wp_editor_row(__('Email Message', 'ym_mailmanager'), 'email_message', $email_message, __('Message to Send, you can use HTML. You can use [ym_mm_custom_field field=""] [ym_mm_if_custom_field field=""]content[/ym_mm_if_custom_field] where the "" is a Custom Profile Field', 'ym_mailmanager'));
	echo $ym_formgen->render_form_table_datetime_picker_row(__('Send At', 'ym_mailmanager'), 'email_date', $time, __('Date/Time to send Message', 'ym_mailmanager'));
}


if (!defined('STOP_VIEW_EMAILS')) {
	echo '</table>';
	echo '<p class="submit" style="text-align: right;">
	<input type="submit" name="submit" value="' . __('Schedule', 'ym_mailmanager') . ' &raquo;" />
</p>';
	echo '</form>';

	ym_box_bottom();
}
