<?php

/*
* $Id: ym-logs-ipn.php 2166 2012-05-25 14:16:20Z bcarlyon $
* $Revision: 2166 $
* $Date: 2012-05-25 15:16:20 +0100 (Fri, 25 May 2012) $
*/

echo '<div class="wrap" id="poststuff">';
	
echo ym_start_box(__('IPN Information', 'ym'));
echo '<p>' . __('IPN stands for Instant Payment Notification. It means something of note has happened at a payment processor and they will make a "call" to your site to let you know. Things that happen are usually payments and refunds. YM can then deal with each request appropriately.', 'ym') . '</p>';

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

if ($transactions = ym_get_all_logs(YM_IPN, $user_id, $limit, $start, $order_by, $deleted)) {
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
			<th>' . __('Module', 'ym') . '</th>
			<th>' . __('Data', 'ym') . '</th>
			<th>' . __('Date', 'ym') . '</th>
		</tr>
	</thead>
	<tbody>';
		
	foreach ($transactions as $transaction) {
		$mod = unserialize($transaction->data);
		if ($transaction->user_login) {
			$profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">' . $transaction->user_login . '</a>';
		} else {
			$profile = 'Deleted User';
		}

		$mod['mod'] = isset($mod['mod']) ? $mod['mod'] : 
			(isset($mod['ym_process']) ? $mod['ym_process'] : 
				(isset($mod['gateway']) ? $mod['gateway'] : 'unknown'));
			
		if (isset($mod['gift_sub'])) {
			$mod['custom'] = __('Gifted Subscription', 'ym');
		}

		if (!isset($mod['custom']) && isset($mod['freebie_code'])) {
			$mod['mod'] = __('Free Coupon', 'ym');
			$mod['custom'] = $mod['freebie_code'];
		} else if ($mod['mod'] == 'ym_facebook_credits') {
			$mod['custom'] = $mod['fb_items'][0]->item_id;
		} else if (!isset($mod['custom'])) {
			// bust guest
			foreach ($mod as $key => $item) {
				if (!is_array($item) && substr($item, 0, 4) == 'buy_') {
					$mod['custom'] = $mod[$key];
					break;
				}
			}
		}

		$module = ucwords(str_replace('ym_', '', $mod['mod']));
		echo '<tr>
			<td>'.$transaction->user_id.'</td>
			<td>' . $profile . '</td>
			<td>'.$transaction->user_email.'</td>
			<td>'.$module.'</td>
			<td>'.($mod['custom'] ? $mod['custom'] : '').'</td>
			<td>'.date(YM_DATE, $transaction->unixtime).'</td>
		</tr>';	
	}
		
	echo '</tbody>
	<tfoot>' . $form . '</tfoot>
	</table>';
} else {
	echo __('<em>There are no Transactions logged.</em>','ym');
}
	
echo ym_end_box();

echo '</div>';
