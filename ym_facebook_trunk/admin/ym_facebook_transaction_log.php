<?php

@session_start();
if ($_REQUEST['ym_facebook_trans_auth'] == 1) {
	$url = 'https://graph.facebook.com/oauth/access_token?client_id=' . $facebook_settings->app_id . '&client_secret=' . $facebook_settings->app_secret . '&grant_type=client_credentials';
	$data = ym_remote_request($url);
	list($nothing, $key) = explode('=', $data);
	$_SESSION['ym_facebook_trans_auth'] = $key;
} else if ($_REQUEST['ym_facebook_trans_auth'] == 2) {
	@session_destroy();
	@session_start();
}

if ($_SESSION['ym_facebook_trans_auth']) {
	
	$since = mktime(0,0,0);
	$until = mktime(23, 59, 59);//time();
	
	echo '<div id="ym_facebook_trans_log">';
//	echo '<ul>';
	$output = '';
	
	for ($x=0;$x<7;$x++) {
		$increase = 86400 * $x;
		
		$since = $since + $increase;
		$until = $until + $increase;
		
		$url = 'https://graph.facebook.com/' . $facebook_settings->app_id . '/payments?status=settled&since=' . $since . '&until=' . $until . '&access_token=' . $_SESSION['ym_facebook_trans_auth'];

		$data = ym_remote_request($url);
//		$data = json_decode($data);
		
		echo '<p>Owing to lack of test data we do not have this page working</p><p>If you would like to help please email us on sales@codingfutures.co.uk the content of the following box, if you have had some Facebook Credits Transactions:</p>';

		echo '<textarea style="width: 100%; height: 400px;">' . $data . '</textarea>';

//		print_r($data);
		
		/*
		if (count($data->data)) {
			echo '<li><a href="#tr' . $x . '">' . $x . '</a></li>';
			
			$ouput .= '<table id="tr' . $x . '">
	<tr>
		<th>From</th><th>To</th><th>Amount</th><th>Status</th><th>Created</th><th>Updated</th>
	</tr>';
			foreach ($data->data as $translog) {
				$output .= '<tr>';
				$output .= '<td>' . $translog->from->name . '(' . $translog->from->id . ')</td>';
				$output .= '<td>' . $translog->to->name . '(' . $translog->to->id . ')</td>';
				$output .= '<td>' . $translog->amount . '</td>';
				$output .= '<td>' . $translog->status . '</td>';
				$output .= '<td>' . $translog->created_time . '</td>';
				$output .= '<td>' . $translog->updated_time . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>';
		} else {
			echo '<li><a href="">' . $x . '</a></li>';
		}
		*/
	}
//	echo '</ul>';
	echo $output;
	echo '</div>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'#ym_facebook_trans_log\').tabs({
		fx: {opacity: \'toggle\'}
	});
});
</script>
';
	
	echo '<a href="' . YM_ADMIN_INDEX_URL .'&ym_page=other_ymfacebook&ym_facebook_trans_auth=2&ym_fb_tab_select=7">Logout</a>';
} else {
	// need to authenticate
	echo '<a href="' . YM_ADMIN_INDEX_URL .'&ym_page=other_ymfacebook&ym_facebook_trans_auth=1&ym_fb_tab_select=7">Login</a>';
}
