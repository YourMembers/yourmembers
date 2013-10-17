<?php

/*
* $Id: sql_update_3.php 2166 2012-05-25 14:16:20Z bcarlyon $
* $Revision: 2166 $
* $Date: 2012-05-25 15:16:20 +0100 (Fri, 25 May 2012) $
*/

/**
Users
*/
if (!is_admin()) {
	return;
	// don't block if not admin so front end still runs
}

global $wpdb;

$user_start = ym_get('user_start', '0');

if ($user_start != 'done') {
	$limit = 300;
	$did = 0;

	$query = 'SELECT * FROM ' . $wpdb->users . ' ORDER BY ID ASC LIMIT ' . $user_start . ',' . $limit;
	echo '<pre>';
	foreach ($wpdb->get_results($query) as $user) {
		$data = new YourMember_User($user->ID);
		// process
		//last_pay_date - expire_date - 
		$last_pay_date = $data->last_pay_date;
		if (strpos($last_pay_date, '-')) {
			// invalid
			if (strpos($last_pay_date, ' ')) {
				list($last_pay_date, $rubbish) = explode(' ', $last_pay_date);
			}
			list($year, $month, $date) = explode('-', $last_pay_date);
			$tos = mktime(0, 0, 0, $month, $date, $year);
			$data->last_pay_date = $tos;
		}

		$expire_date = $data->expire_date;
		if (strpos($expire_date, '-')) {
			// invalid
			if (strpos($expire_date, ' ')) {
				list($expire_date, $rubbish) = explode(' ', $expire_date);
			}
			list($year, $month, $date) = explode('-', $expire_date);
			$tos = mktime(0, 0, 0, $month, $date, $year);
			$data->expire_date = $tos;
		}

		if (!ym_superuser($user->ID)) {
			$data->save();
			echo '.';
		} else {
			echo '|';
		}
		$did++;
		if (substr($did, -1, 1) == '0') {
			echo ' ' . ($user_start + $did) . '<br />';
		}
	}

	if ($did == $limit) {
		$next = $user_start + $limit;
		$url = YM_ADMIN_URL . '&user_start=' . $next;
		echo '<p>YourMembers Update Script: Sleeping for next Run</p>';
		echo '<meta http-equiv="refresh" content="5;' . $url . '" />';
	} else {
		$url = YM_ADMIN_URL . '&user_start=done';
		echo '<p>YourMembers Update Script: Completed User Update Run</p>';
		echo '<meta http-equiv="refresh" content="5;' . $url . '" />';
	}
	echo '</pre>';
	exit;
}
