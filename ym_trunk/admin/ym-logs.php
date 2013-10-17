<?php

/*
* $Id: ym-logs.php 2514 2013-01-11 12:59:52Z bcarlyon $
* $Revision: 2514 $
* $Date: 2013-01-11 12:59:52 +0000 (Fri, 11 Jan 2013) $
*/

$search_user_name = ym_post('search_user_name');
$user_id = ym_request('user_id', FALSE);
$transaction_id = ym_post('group_log_id');

echo '<div class="wrap" id="poststuff">';

echo ym_start_box(__('Logs', 'ym'));
if (!$_POST && !$user_id) {
	echo '<p>' . __('You can select a user below to get their log, or one of the tabs above to get specific log information', 'ym') . '</p>';
} else if (!$user_id) {
	$user_id = get_user_by('login', $search_user_name);
	if ($user_id) {
		$user_id = $user_id->ID;
	} else {
		$user_id = FALSE;
		echo '<div class="error" id="message"><p>' . __('User Not Found', 'ym') . '</p></div>';
	}
}

if (!ym_request('user_id', false)) {
	// get user specific log
	echo '<form action="" method="post">';
	echo '<label for="user_id">' . __('Search for a User', 'ym');
	echo ' <input type="text" name="search_user_name" id="search_user_name" value="' . $search_user_name . '">';
	echo '</label>';
	echo '<input type="submit" value="' . __('Get User Log', 'ym') . '" />';
	echo '</form>';

	echo '<form action="" method="post">';

	global $wpdb;
	$query = 'SELECT DISTINCT(transaction_id) FROM ' . $wpdb->prefix . 'ym_transaction ORDER BY transaction_id DESC';

	echo '<label for="group_log_id">' . __('Select a Log Group ID', 'ym');
	echo ' <select name="group_log_id" id="group_log_id">';
	foreach ($wpdb->get_results($query) as $row) {
		echo '<option value="' . $row->transaction_id . '" ';
		if ($row->transaction_id == $transaction_id) {
			echo 'selected="selected" ';
		}
		echo '>' . $row->transaction_id . '</option>';
	}
	echo '</select></label>';
	echo '<input type="submit" value="' . __('Get Log Group', 'ym') . '" />';
	echo '</form>';
}

if ($_POST) {
	echo ym_end_box();
	echo ym_start_box(__('Logs', 'ym'));
}

if ($transaction_id) {
	echo '<p>' . sprintf(__('Transaction Group: %s', 'ym'), $transaction_id);
	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_transaction WHERE transaction_id = ' . $transaction_id;
	echo '<table class="form-table">
	<tr><th style="width: 75px;">ID</th><th>User</th><th>Action</th><th>Data</th><th>Date</th></tr>';
	foreach ($wpdb->get_results($query) as $transaction) {
		echo '<tr>';
		echo '<td>' . $transaction->id . '</td>';

		echo '<td nowrap="nowrap">(' . $transaction->user_id . ') ';
		$user = get_user_by('id', $transaction->user_id);
		echo $user->user_login;
		echo '</td>';

		$log_type = ym_get_transaction_action($transaction->action_id);
		echo '<td>' . $log_type->name . '</td>';

		$mod = maybe_unserialize($transaction->data);
			if ($transaction->action_id == YM_ACCESS_EXTENSION || $transaction->action_id == YM_ACCESS_EXPIRY) {
				// TODO: for YM 11 only
				if (strpos($transaction->data, ' ') || strpos($transaction->data, '-')) {
					$transaction->data = strtotime($transaction->data);
				}
				echo '<td>';
				if ($transaction->data) {
					echo date(YM_DATE, $transaction->data);
				} else {
					echo 'No Data';
				}
				echo '</td>';
			} else {
				echo '<td>'.(is_array($mod) ? '<pre>' . print_r($mod, true) . '</pre>':$mod).'</td>';
			}
		echo '<td>'.date(YM_DATE, $transaction->unixtime).'</td>';

		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
	return;
}

if ($user_id) {
	$user = get_userdata($user_id);
	echo '<p>' . sprintf(__('Showing Log Information for <strong>%s</strong>', 'ym'), $user->user_login) . '</p>';
}

echo ym_end_box();

$start = 0;
$limit = 50;

$cur = ym_post('start');
if (ym_post('next')) {
	$start = $cur + $limit;
}
if (ym_post('back')) {
	$start = $cur - $limit;
	if ($start < 0) {
		$start = 0;
	}
}

//$user_id = FALSE;
$order_by = 'id DESC';
$deleted = TRUE;

if ($user_id) {
	ym_show_timeline_log(false, $user_id, $limit, $start, $order_by, $deleted);
}

echo '</div>';
