<?php

global $ym_formgen, $ym_res, $ym_sys, $wpdb, $allowed_extensions;

global $current_user;
get_currentuserinfo();
$target = $current_user->user_email;

if (isset($_POST['send_test']) && !empty($_POST['send_test'])) {
	$ym_sys->email_reminder_subject = str_replace('[site_name]', get_bloginfo(), $ym_sys->email_reminder_subject);
	$message = ym_apply_filter_the_content($ym_sys->email_reminder_message);
	ym_email($target, $ym_sys->email_reminder_subject, $message);
	ym_display_message(__('Sent test Email Reminder test email to you','ym'));
}
if (isset($_POST['send_test_recur']) && !empty($_POST['send_test_recur'])) {
	$ym_sys->email_reminder_subject_recur = str_replace('[site_name]', get_bloginfo(), $ym_sys->email_reminder_subject_recur);
	$message = ym_apply_filter_the_content($ym_sys->email_reminder_message_recur);
	ym_email($target, $ym_sys->email_reminder_subject_recur, $message);
	ym_display_message(__('Sent test Email Reminder test email to you','ym'));
}

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

echo '<div class="wrap" id="poststuff">';
echo ym_box_top(__('Email Configuration', 'ym'));

echo '<p>' . sprintf(__('These from settings will also be used for all YM initiated emails, instead of the WordPress default, normally wordpress@%s', 'ym'), $_SERVER['HTTP_HOST']) . '</p>';

echo '<form action="" method="post">
<table class="form-table">';
$ym_formgen->render_form_table_text_row(__('From Name', 'ym'), 'from_name', $ym_sys->from_name, __('Name of the sender (you or your site&#39;s name)', 'ym'));
$ym_formgen->render_form_table_text_row(__('From Email', 'ym'), 'from_email', $ym_sys->from_email, __('The email address where site emails will come from', 'ym'));
$ym_formgen->render_form_table_radio_row(__('All Emails', 'ym'), 'filter_all_emails', $ym_sys->filter_all_emails, __('Apply these filters to <strong>ALL</strong> Emails WordPress Sends', 'ym'));
echo '</table>';

echo ym_end_box();

echo ym_box_top(__('Email Reminder Settings', 'ym'));

if ($next_run = wp_next_scheduled('ym_email_reminder')) {
	echo sprintf(__('<p>The next Email Reminders will be checked for sending at %s', 'ym'), date('r', $next_run));
} else if (get_option('enable_manual_cron')) {
	echo __('<p>Email Reminder Cron is set to manual run/crontab</p>', 'ym');
}

echo '<table class="form-table">';
$ym_formgen->render_form_table_radio_row(__('Enable Reminder Email', 'ym'), 'email_reminder_enable', $ym_sys->email_reminder_enable);
$ym_formgen->render_form_table_text_row(__('Number of days before', 'ym'), 'email_reminder_limit', $ym_sys->email_reminder_limit, __('The number of days before subscription expiry to send reminder notification', 'ym'));

echo '</table>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" class="button-primary" />
</p>';
echo ym_end_box();

echo ym_box_top(__('Email Reminder non Recurring Subscription', 'ym'));
echo '<table class="form-table">';

$ym_formgen->render_form_table_text_row(__('Reminder Subject (non Recurring Subscriptions', 'ym'), 'email_reminder_subject', $ym_sys->email_reminder_subject, __('Supports [site_name]', 'ym'));
$ym_formgen->render_form_table_wp_editor_row(__('Reminder Message (non Recurring Subscriptions)', 'ym'), 'email_reminder_message', $ym_sys->email_reminder_message);

echo '<tr><td></td><td>
<p class="submit" style="float: left;">
	' . __('Send Test Email to ', 'ym') . $target . '
	<input type="submit" name="send_test" value="' . __('Send','ym') . ' &raquo;" class="button-secondary" />
</p>
</td></tr>
';

echo '</table>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" class="button-primary" />
</p>';
echo ym_end_box();

echo ym_box_top(__('Email Reminder Recurring Subscription', 'ym'));
echo '<table class="form-table">';


$ym_formgen->render_form_table_text_row(__('Reminder Subject (Recurring Subscriptions)', 'ym'), 'email_reminder_subject_recur', $ym_sys->email_reminder_subject_recur, __('Supports [site_name]', 'ym'));
$ym_formgen->render_form_table_wp_editor_row(__('Reminder Message (Recurring Subscriptions)', 'ym'), 'email_reminder_message_recur', $ym_sys->email_reminder_message_recur);

echo '<tr><td></td><td>
<p class="submit" style="float: left;">
	' . __('Send Test Email to ', 'ym') . $target . '
	<input type="submit" name="send_test_recur" value="' . __('Send','ym') . ' &raquo;" class="button-secondary" />
</p>
</td></tr>
';

echo '</table>

<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" class="button-primary" />
</p>
';

echo ym_end_box();
echo ym_box_top(__('Email Reminder for Drip Fed Content', 'ym'));
echo '<table class="form-table">';

$ym_formgen->render_form_table_radio_row(__('Enable Drip Feeding Reminder Email', 'ym'), 'email_drip_reminder_enable', $ym_sys->email_drip_reminder_enable);
$ym_formgen->render_form_table_text_row(__('Reminder Subject (New Dripped Content)', 'ym'), 'email_drip_subject', $ym_sys->email_drip_subject, __('Supports [site_name]', 'ym'));
$ym_formgen->render_form_table_wp_editor_row(__('Reminder Message (New Dripped Content)', 'ym'), 'email_drip_message', $ym_sys->email_drip_message);

echo '</table>

<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="' . __('Save Settings','ym') . ' &raquo;" class="button-primary" />
</p>

</form>
';

echo ym_end_box();
echo '</div>';

