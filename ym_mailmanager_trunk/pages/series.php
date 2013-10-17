<?php

global $mm, $wpdb, $ym_formgen;

do_action('mailmanager_series_precontent');

do_action('mailmanager_series_replace');
if (defined('MM_SERIES_REPLACED')) {
	return;
}

ym_box_top(__('Email Series', 'ym_mailmanager'));

$action = ym_get('series_action');

switch ($action) {
	case 'new':
		$show = TRUE;
		$name = ym_post('series_name');
		$desc = ym_post('series_description');
		$recipient_list = ym_post('recipient_list');

		if ($_POST) {
			$bypass = FALSE;
			do_action('mailmanager_series_create', $name, $recipient_list, $show, $bypass);
			
			if ($name && $desc) {
				$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_series (name, description, recipient_list) VALUES (\'' . $name . '\', \'' . $desc . '\', \'' . $recipient_list . '\')';
				$wpdb->query($sql);
				
				if ($wpdb->insert_id) {
					$show = FALSE;
					echo '<p>' . __('Your Series was created', 'ym_mailmanager') . '</p>';
					ym_box_bottom();
					ym_box_top(__('Email Series', 'ym_mailmanager'));
				} else {
					echo '<p>' . __('There was a SQL error creating your Series', 'ym_mailmanager') . '</p>';
					ym_box_bottom();
					ym_box_top(__('Email Series', 'ym_mailmanager'));					
				}
			} else if (!$bypass) {
				echo '<p>' . __('Please Supply both a name and a description', 'ym_mailmanager') . '</p>';
				ym_box_bottom();
				ym_box_top(__('Email Series', 'ym_mailmanager'));
			}
		}
		if ($show) {
			echo '<p>' . __('Once a series has been created you can then add emails to it', 'ym_mailmanager') . '</p>';
			echo '<form action="" method="post">';
			echo '<table class="form-table">';
			echo $ym_formgen->render_form_table_text_row(__('Series Name', 'ym_mailmanager'), 'series_name', $name, __('A Name for the Series', 'ym_mailmanager'));
			echo $ym_formgen->render_form_table_text_row(__('Series Description', 'ym_mailmanager'), 'series_description', $desc, __('A Description for the Series', 'ym_mailmanager'));
			echo $ym_formgen->render_combo_from_array_row(__('Recipients', 'ym_mailmanager'), 'recipient_list', mailmanager_get_recipients(), $recipient_list, __('Select a List to send to', 'ym_mailmanager'));
			echo '</table>';
			echo '<p><input type="submit" value="' . __('Add Series', 'ym_mailmanager') . '" style="float: right;" /></p>';
			echo '</form>';
			break;
		}
	case 'delete':
		if ($id = ym_get('deleteid')) {
			$sql = 'DELETE FROM ' . $wpdb->prefix . 'mm_series WHERE id = ' . $id;
			$wpdb->query($sql);
		}
	case 'enable':
		if ($id = ym_get('tseries')) {
			$sql = 'UPDATE ' . $wpdb->prefix . 'mm_series SET enabled = 1 WHERE id = ' . $id;
			$wpdb->query($sql);
			if (!$wpdb->rows_affected) {
				$sql = 'UPDATE ' . $wpdb->prefix . 'mm_series SET enabled = 0 WHERE id = ' . $id;
				$wpdb->query($sql);
			}
		}
	case 'assoc':
		if ($add_id = ym_post('email_id')) {
			$series_id = ym_post('series');
			if ($add_id && $series_id) {
				$delay = ym_post('delay');
				$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_email_in_series(series_id, email_id, delay_days) VALUES (' . $series_id . ', ' . $add_id . ', ' . $delay . ')';
				$wpdb->query($sql);
				
				if ($wpdb->insert_id) {
					echo '<p>' . __('Email Associated', 'ym_mailmanager') . '</p>';
				} else {
					echo '<p>' . __('Email Failed to be Associated', 'ym_mailmanager') . '</p>';
				}
				ym_box_bottom();
				ym_box_top(__('Email Series', 'ym_mailmanager'));
			}
		} else if ($del_id = ym_get('deleteid')) {
			$sql = 'DELETE FROM ' . $wpdb->prefix . 'mm_email_in_series WHERE id = ' . $del_id;
			$wpdb->query($sql);
		} 
		if ($series = ym_get('series')) {
			echo '<form action="" method="post">
			<input type="hidden" name="series" value="' . $series . '" />
			<table class="form-table">';
			$emails = mailmanager_get_emails(TRUE);

			if ($emails) {
				echo $ym_formgen->render_combo_from_array_row(__('Select Prior Email', 'ym_mailmanager'), 'email_id', $emails, '', __('Select a previously created email', 'ym_mailmanager'));
			}
			
			$days = array();
			for ($x=0;$x<=365;$x++) {
				$days[$x] = $x . ' Days';
			}
			echo $ym_formgen->render_combo_from_array_row(__('Select Delay', 'ym_mailmanager'), 'delay', $days, '', __('Select number of days between emails', 'ym_mailmanager'));
			
			echo '
			</table>
			<p style="text-align: right;">
			<input type="submit" value="' . __('Add Email to Series', 'ym_mailmanager') . '" />
			</p>
			</form>';
			ym_box_bottom();
			ym_box_top(__('Current Series Content', 'ym_mailmanager'));
			echo '<p>' . __('Currently in this series', 'ym_mailmanager') . '</p>';
			echo '<table class="form-table">
			<tr><th>' . __('ID', 'ym_mailmanager') . '</th><th>' . __('Email', 'ym_mailmanager') . '</th><th>' . __('Delay', 'ym_mailmanager') . '</th><th>' . __('Preview', 'ym_mailmanager') . '</th><th>' . __('Action', 'ym_mailmanager') . '</th></tr>
			';
			
			$sql = 'SELECT eis.id AS id, e.id as series, name, delay_days, email_id FROM ' . $wpdb->prefix . 'mm_email_in_series eis LEFT JOIN ' . $wpdb->prefix . 'mm_email e ON e.id = eis.email_id WHERE series_id = ' . $series;
			foreach ($wpdb->get_results($sql) as $row) {
				echo '<tr>';
				echo '<td>' . $row->id . '</td>';
				echo '<td>' . $row->name . '</td>';
				echo '<td>' . $row->delay_days . ' ' . __('Days') . '</td>';
				echo '<td><a href="' . $mm->page_root . '&mm_action=preview&iframe_preview=' . $row->email_id . '" class="previewlink">' . __('Email Preview', 'ym_mailmanager') . '</a></td>';
				echo '<td><a href="' . $mm->page_root . '&mm_action=series&series_action=assoc&series=' . $row->series . '&deleteid=' . $row->id . '" class="deletelink">' . __('Delete', 'ym_mailmanager') . '</a></td>';
				echo '</tr>';
			}
			
			echo '
			</table>';
			break;
		}
	default:
		echo '<p>' . __('This is a list of email series that exists. Emails are sent out to subscribers the specified number of days after the previous.', 'ym_mailmanager') . '</p>';

//		$sql = 'SELECT s.id as id, s.name, s.description, s.recipient_list, s.enabled, COUNT(eis.series_id) AS emails, SUM(eis.delay_days) AS dayslength FROM ' . $wpdb->prefix . 'mm_series s LEFT JOIN ' . $wpdb->prefix . 'mm_email_in_series eis ON eis.series_id = s.id ORDER BY s.name ASC';
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mm_series ORDER BY id ASC';
		echo '
		<form action="' . $mm->page_root . '&mm_action=series&series_action=new" method="post">
		<table class="form-table">
		<thead>
		<tr><th>' . __('ID', 'ym_mailmanager') . '</th><th>' . __('Name', 'ym_mailmanager') . '</th><th>' . __('Description', 'ym_mailmanager') . '</th><th>' . __('Sending to', 'ym_mailmanager') . '</th><th>' . __('Size/Duration', 'ym_mailmanager') . '</th><th>' . __('Enabled', 'ym_mailmanager') . '</th><th>' . __('Actions', 'ym_mailmanager') . '</th></tr>
		</thead>
		<tbody>';
		
		foreach ($wpdb->get_results($sql) as $row) {
			$subs_sql = 'SELECT COUNT(eis.series_id) AS emails, SUM(eis.delay_days) AS dayslength FROM ' . $wpdb->prefix . 'mm_series s LEFT JOIN ' . $wpdb->prefix . 'mm_email_in_series eis ON eis.series_id = s.id WHERE s.id = ' . $row->id;
			$emails = $wpdb->get_var($subs_sql);
			$days = $wpdb->get_var($subs_sql, 1);
			$days = $days ? $days : 0;
			
			echo '<tr>';
			echo '<td>' . $row->id . '</td><td>' . $row->name . '</td><td>' . $row->description . '</td><td>';
			echo $row->recipient_list;
			echo '</td>';
			echo '<td style="text-align: center;">' . $emails . '/' . $days . ' ' . __('days', 'ym_mailmanager') . '</td>';
			echo '<td>' . ($row->enabled ? 'Yes' : 'No') . '</td>';
			echo '<td>';

			echo '<a href="' . $mm->page_root . '&mm_action=series&series_action=assoc&series=' . $row->id . '">' . __('Content Edit', 'ym_mailmanager') . '</a>';
			echo ' / ';
			if ($emails) {
				echo '<a href="' . $mm->page_root . '&mm_action=series&series_action=enable&tseries=' . $row->id . '">' .  ($row->enabled ? __('Disable', 'ym_mailmanager') : __('Enable', 'ym_mailmanager')) . '</a>';
				echo ' / ';
			}
			echo '<a href="' . $mm->page_root . '&mm_action=series&series_action=delete&deleteid=' . $row->id . '" class="deletelink">' . __('Delete', 'ym_mailmanager') . '</a>';

			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody>
		<tfoot>
			<tr id="sourcerow"><td></td><td><input type="text" name="series_name" /></td><td><input type="text" name="series_description" /></td><td>';
			
			echo '<select name="recipient_list">';

			foreach (mailmanager_get_recipients() AS $value => $text) {
				echo '<option value="' . $value . '">' . $text . '</option>';
			}
			echo '</select>';
			
			echo '</td><td><input type="submit" value="' . __('Add', 'ym_mailmanager') . '" /></td></tr>
		</tfoot>';
		echo '</table></form>';

		echo '<p><a href="' . $mm->page_root . '&mm_action=series&series_action=new" id="addlink">' . __('Create new series', 'ym_mailmanager') . '</a></p>';
}

ym_box_bottom();
