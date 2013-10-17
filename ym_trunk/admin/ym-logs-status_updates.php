<?php

/*
* $Id: ym-logs-status_updates.php 2166 2012-05-25 14:16:20Z bcarlyon $
* $Revision: 2166 $
* $Date: 2012-05-25 15:16:20 +0100 (Fri, 25 May 2012) $
*/

echo '<div class="wrap" id="poststuff">';

echo ym_start_box(__('Status Updates', 'ym'));
echo '<p>' . __('This simply means when a user status/account type has been updated. For instance an account activated or expired.', 'ym') . '</p>';

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

$user_id = FALSE;
$order_by = 'id DESC';
$deleted = TRUE;

if ($transactions = ym_get_all_logs(YM_USER_STATUS_UPDATE, $user_id, $limit, $start, $order_by, $deleted)) {
	$form = '<tr><td colspan="5" style="text-align: center;"><form action="" method="post">
<input type="hidden" name="start" value="' . $start . '" />';

	if ($start) {
		$form .= '<input type="submit" name="back" value="' . __('Back a Page', 'ym') . '" style="float: left;" />';
	}

	$count = count($transactions);
	if ($count == $limit) {
		$form .= '<input type="submit" name="next" value="' . __('Next Page', 'ym') . '" style="float: right;"  />';
	}

	$page = $start;
	if ($page == 0) {
		$page = 1;
	} else {
		$page = ($start / $limit) + 1;
	}
	$form .= sprintf(__('Page %s', 'ym'), $page);

	$form .= '</form></td>';

	$form .= '
		</tr>
	';

	echo '<table class="ym_table form-table">
	<thead>
		' . $form . '
		<tr>
			<th>' . __('User ID', 'ym') . '</th>
			<th>' . __('User', 'ym') . '</th>
			<th>' . __('User Email', 'ym') . '</th>			
			<th>' . __('New Status', 'ym') . '</th>
			<th>' . __('Date', 'ym') . '</th>
		</tr>
	</thead>
	<tbody>';
		
	foreach ($transactions as $transaction) {
		$mod = $transaction->data;
		if ($transaction->user_login) {
			$profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">' . $transaction->user_login . '</a>';
		} else {
			$profile = 'Deleted User';
		}
			
		echo '<tr>
			<td>' . $transaction->user_id . '</td>
			<td>' . $profile . '</td>
			<td>' . $transaction->user_email . '</td>			
			<td>' . $transaction->data . '</td>
			<td>' . date(YM_DATE, $transaction->unixtime) . '</td>
		</tr>';	
	}
	echo '</tbody>
	<tfoot>
		' . $form . '
	</tfoot>
	</table>';
} else {
	echo __('<em>There have been no status updates.</em>','ym');
}
	
echo ym_end_box();

echo '</div>';
