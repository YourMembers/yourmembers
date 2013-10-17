<?php

/*
* $Id: ym-advanced-messages.php 2257 2012-07-20 10:38:19Z bcarlyon $
* $Revision: 2257 $
* $Date: 2012-07-20 11:38:19 +0100 (Fri, 20 Jul 2012) $
* $Author: bcarlyon $
*/

if (ym_get('reload', FALSE)) {
	ym_cron::reschedule();
	echo '<div id="message" class="updated"><p>' . __('One Moment Rescheduling Tasks', 'ym') . '</p></div>';
	echo '<meta http-equiv="refresh" content="1;' . YM_ADMIN_URL . '&ym_page=ym-advanced-cron&reschedule=1" />';
	return;
}

global $ym_formgen, $ym_sys, $ym_crons_that_exist;
if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

$cron_task = ym_post('run_cron_job', FALSE);
if ($cron_task) {
	ob_end_flush();
	ob_implicit_flush(TRUE);
	echo '
<style type="text/css">
	html, body {
		min-width: 400px;
		width: 400px;
	}
</style>';

	echo 'Execute ' . $cron_task . '<br />';

	$this_job = FALSE;
	global $ym_crons_that_exist;
	foreach ($ym_crons_that_exist as $cron_job) {
		if ($cron_job['task'] == $cron_task) {
			$this_job = $cron_job;
			break;
		}
	}
	if (!$this_job) {
		echo '<div class="error" id="message"><p>';
		echo __('Could Not Find The Job in Order to Execute it', 'ym');
		echo '</p></div>';
	} else {
		if (class_exists($cron_task)) {
			echo '<pre>';
			$cron_task = new $cron_task();
			$cron_task->manual_run();
			echo '</pre>';
		} else if ($this_job['core'] == 2) {
			// try for do_action
			do_action($cron_task);
			echo '<div class="updated" id="message"><p>';
			echo __('Task Complete', 'ym');
			echo '</p></div>';
		} else {
			echo '<div class="error" id="message"><p>';
			echo __('Could Not Execute Class Not Found', 'ym');
			echo '</p></div>';
		}
	}
	return;
}

if (ym_get('reschedule', FALSE)) {
	echo '<div id="message" class="updated fade"><p>' . __('Rescheduling Tasks Complete', 'ym') . '</p></div>';
}

echo '<div class="wrap" id="poststuff">
<form action="" method="post">';

echo ym_box_top(__('Manual Cron Control', 'ym'));
echo '<table class="form-table">';

echo $ym_formgen->render_form_table_radio_row(__('Use Manual Cron (crontab) rather than WP Cron?', 'ym'), 'enable_manual_cron', $ym_sys->enable_manual_cron, __('For larger sites its recommended to set this to true and use crontab. The command is below. This will run YM Cron functions only not other WP Cron Tasks'));

echo '</table>';

	echo '<p>' . __('Add the following line to your crontab if enabled, the first argument (59), is the minute, the second argument (23) the hour, together make up the time to run the task. More Information <a href="http://en.wikipedia.org/wiki/Cron#Predefined_scheduling_definitions">Here</a>', 'ym');
	echo '<br /><pre>';
	echo '59 23 * * * wget -O cronresult.html ' . site_url('?ym_cron_do=1');
	echo '</pre><br /></p>';

	echo '<p>' . __('Rather than Running all tasks, you can instead call a specific task', 'ym') . '</p>';
	echo '<ul>';
	foreach ($ym_crons_that_exist as $cron_job) {
		if ($cron_job['core'] == 1) {
			echo '<li>' . site_url('?ym_cron_do=1&ym_cron_job=' . $cron_job['task']) . '</li>';
		}
	}
	echo '</ul>';

echo '
	<p>' . __('Unlike WP Cron calling these URLs will always run the task(s), whereas WP Cron will only run the task if scheduled', 'ym') . '</p>
';

	echo '<input id="ym_manual_cron_run_now" type="button" class="button-secondary" value="' . __('Manually run YM cron now', 'ym') . '" />
	<iframe id="ym_manual_cron_run_now_return" style="display:none; height: 400px; width: 600px;"></iframe>
	<script type="text/javascript">' . "
		jQuery('#ym_manual_cron_run_now').click(function() {
			jQuery(this).remove();
			jQuery('#ym_manual_cron_run_now_return').slideDown();
			jQuery('#ym_manual_cron_run_now_return').attr('src', '" . site_url('?ym_cron_do=1') . "');
		});
	</script>
	";

ym_box_bottom();

ym_box_top(__('Disable WP Cron to improve performance', 'ym'));
echo "
	<p>
	To disable WP Cron and convert it to CronTab, add<br /><pre>define('DISABLE_WP_CRON', true);</pre><br /> to your wp-config.php and<br /><pre>5/* * * * * wget -O cronresult.html " . site_url('wp-cron.php?doing_wp_cron=1') . "</pre><br />to your crontab.
	</p>
";
echo '<input id="ym_manual_wpcron_run_now" type="button" class="button-secondary" value="' . __('Manually run WP cron now', 'ym') . '" />
<iframe id="ym_manual_wpcron_run_now_return" style="display:none; height: 400px; width: 600px;"></iframe>
<script type="text/javascript">' . "
	jQuery('#ym_manual_wpcron_run_now').click(function() {
		jQuery(this).remove();
		jQuery('#ym_manual_wpcron_run_now_return').slideDown();
		jQuery('#ym_manual_wpcron_run_now_return').attr('src', '" . site_url('wp-cron.php?doing_wp_cron=1') . "');
	});
</script>
";

echo ym_end_box();

ym_box_top(__('Notifications', 'ym'));
echo '<table class="form-table">';
echo $ym_formgen->render_form_table_email_row(__('Email Address', 'ym'), 'cron_notify_email', $ym_sys->cron_notify_email, __('Output from YM Cron Tasks can be Forwarded to a Email Address for Logging Needs', 'ym'));
echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym'), 'cron_notify_subject', $ym_sys->cron_notify_subject, __('Choose the Subject, useful for Mail Sorting', 'ym'));
echo '</table>';
echo ym_end_box();

?>
<p class="submit" style="text-align: right;">
	<input type="submit" name="settings_update" value="<?php _e('Save Settings','ym') ?> &raquo;" class="button-primary" style="float: right;" />
</p>
</form>
<?php

$schedules = wp_get_schedules();

echo ym_box_top(__('Cron Jobs', 'ym'));
echo '<table class="form-table">';
echo '<thead><tr>
	<th>' . __('Task', 'ym') . '</th>
	<th style="width: 60px;">' . __('Exists', 'ym') . '</th>
	<th style="width: 60px;">' . __('Enabled', 'ym') . '</th>
	<th style="width: 75px;">' . __('Schedule', 'ym') . '</th>
	<th style="width: 75px;">' . __('Next Scheduled Run', 'ym') . '</th>
	<th style="width: 75px;">' . __('Change Schedule', 'ym') . '</th>
	<th style="width: 90px;">' . __('Run Now', 'ym') . '</th>
</tr></thead>';
echo '<tbody>';

$string = __('Never', 'ym');
if ($ym_sys->enable_manual_cron) {
	$string = __('Manual', 'ym');
}

foreach ($ym_crons_that_exist as $cron_job) {
	$cron_name = ucwords(str_replace(array('_', '-'), ' ', $cron_job['task']));

	echo '<tr>';
	echo '<td><span>' . $cron_name . '</span><br />';
	if (class_exists($cron_job['task'])) {
		$task = new $cron_job['task'];
		echo $task->description();
	}
	echo '</td>';
	echo '<td>';
	if ($cron_job['core'] != 2) {
		$exists = class_exists($cron_job['task']);
		$exists_class = $exists ? 'ym_tick' : 'ym_cross';
		echo '<span class="' . ($exists_class) . '">&nbsp;</span>';
	} else {
		echo __('External', 'ym');
	}
	echo '</td>';
	echo '<td>';
	if ($cron_job['core'] != 2 && $exists) {
		echo '
<form action="" method="post" class="ym_ajax_call">
	<input type="hidden" name="action" value="ym_cron_block_toggle" />
	<input type="hidden" name="task" value="' . $cron_job['task'] . '" />
';
		echo '<a href="" class="ym_form_submit ';
		$blocked = get_option('ym_cron_block_' . $cron_job['task']);
		if ($blocked) {
			echo 'ym_cross';
		} else {
			echo 'ym_tick';
		}
		echo '" data-html="0"></a>';
		echo '
	<div class="clear"></div>
</form>
';
	} else {
		echo '-';
	}
	echo '</td>';
	echo '<td>';

	$task_schedule = (isset($cron_job['schedule']) ? $cron_job['schedule'] : 'daily');
	echo ucwords($task_schedule);
	echo '<br />';
	echo $schedules[$task_schedule]['display'];

	echo '</td>';
	$next = wp_next_scheduled($cron_job['task']);
	echo '<td>' . ($next ? date(YM_DATE, $next) : $string) . '</td>';

	echo '<td>';
	if ($cron_job['core'] != 2 && $exists) {
		echo '
<form action="" method="post" class="ym_ajax_call">
	<input type="hidden" name="action" value="ym_cron_reschedule_job" />
	<input type="hidden" name="task" value="' . $cron_job['task'] . '" />
	<table class="ym_table_collapse"><tr><td colspan="2">
		<select name="new_schedule">
		';

		foreach ($schedules as $index => $schedule) {
			echo '<option value="' . $index . '"';
			if ($index == $task_schedule)
				echo ' selected="selected"';
			echo '>' . $schedule['display'] . '</option>';
		}

		echo '
		</select>
		</td><td>
			<a href="" class="ym_form_submit button-secondary" data-html="1">' . __('Update', 'ym') . '</a>
	</td></tr>
	<tr><td>
		<select name="new_hour">
		';

		for ($x=0;$x<24;$x++) {
			echo '<option value="' . $x . '"';
			if ($x == $cron_job['time'][0])
				echo ' selected="selected"';
			echo '>' . $x . '</option>';
		}

		echo '
		</select>
	</td><td>
		<select name="new_min">
		';

		for ($x=0;$x<60;$x++) {
			echo '<option value="' . $x . '"';
			if ($x == $cron_job['time'][1])
				echo ' selected="selected"';
			echo '>' . $x . '</option>';
		}

		echo '
		</select>
	</td></tr></table>
	<div class="clear"></div>
</form>
';
	}
	echo '</td>';

	echo '<td>';
	if ($exists) {
		echo '<form action="" method="post"><input type="hidden" name="run_cron_job" value="' . $cron_job['task'] . '" /><input type="submit" value="' . __('Run Task', 'ym') . '" class="button-secondary ym_dialog_form_submit" /></form>';
	}
	echo '</td>';
	echo '</tr>';
}
echo '</tbody></table>';
echo ym_end_box();

?>
</div>
