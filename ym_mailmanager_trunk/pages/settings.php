<?php

global $ym_formgen;

$current_settings = get_option('ym_other_mm_settings');

if (ym_post('submit')) {
	// go for update
	$current_settings->from_email = ym_post('from_email');
	$current_settings->from_name = ym_post('from_name');
	
	$current_settings->series_hour = ym_post('series_hour');
	$current_settings->series_min = ym_post('series_min');
	
	ym_box_top(__('Mail Manager Settings', 'ym_mailmanager'));

	if (!is_email(ym_post('from_email'))) {
		$this->options->from_email = '';
		echo '<p>' . __('The Email you Supplied for the From Address is invalid', 'ym_mailmanager') . '</p>';
	}
	
	wp_clear_scheduled_hook('mm_queue_check');
	$now = time();
	if ($current_settings->series_hour < date('H', $now)) {
		// the has passed schedule for tomorrow
		$now = $now + 86400;
	}
	$next = mktime($current_settings->series_hour, $current_settings->series_min, 59, date('n', $now), date('j', $now), date('Y', $now));
	wp_schedule_event($next, 'daily', 'mm_queue_check');
	
	update_option('ym_other_mm_settings', $current_settings);
	echo '<p>' . __('Settings were updated', 'ym_mailmanager') . '</p>';
	ym_box_bottom();
	echo '<meta http-equiv="refresh" content="5" />';
	return;
}

echo '<form action="" method="post">';

ym_box_top(__('Send From Details', 'ym_mailmanager'));

echo '<table class="form-table">';

echo $ym_formgen->render_form_table_text_row(__('From Address', 'ym_mailmanager'), 'from_email', $current_settings->from_email, __('Address Emails appear to come from', 'ym_mailmanager'));
echo $ym_formgen->render_form_table_text_row(__('From Name', 'ym_mailmanager'), 'from_name', $current_settings->from_name, __('The Name emails appear to be from', 'ym_mailmanager'));

echo '</table>';
ym_box_bottom();

ym_box_top(__('Misc Settings', 'ym_mailmanager'));

echo '<p>' . __('When do you want the main email series sender to run', 'ym_mailmanager') . '</p>';

echo '<table class="form-table">';

$hours = array();
for($x=0;$x<24;$x++) {
	$hours[] = $x;
}
$mins = array();
for($x=0;$x<60;$x++) {
	$mins[] = $x;
}

echo $ym_formgen->render_combo_from_array_row(__('Hour', 'ym_mailmanager'), 'series_hour', $hours, $current_settings->series_hour, __('What hour to run the series checker at', 'ym_mailmanager'));
echo $ym_formgen->render_combo_from_array_row(__('Min', 'ym_mailmanager'), 'series_min', $mins, $current_settings->series_min, __('What min past the hour to run the series checker at', 'ym_mailmanager'));

echo '
</table>
<p class="submit" style="text-align: right;">
	<input type="submit" name="submit" value="' . __('Save Settings','ym_mailmanager') . ' &raquo;" />
</p>
';

ym_box_bottom();

echo '</form>';
