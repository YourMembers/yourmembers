<?php

/*
* $Id: ym-data_output.include.php 2603 2013-02-18 17:26:43Z tnash $
* $Revision: 2603 $
* $Date: 2013-02-18 17:26:43 +0000 (Mon, 18 Feb 2013) $
*/

function ym_export_users($offset = 0, $limit = 300, $package_type=null, $package = NULL, $active=null){
	global $wpdb, $duration_str;

	if(!$package_type){
		$package_type = 'all';
	}
	
	$user_list = array();

	$path = trailingslashit(ym_post('backup_temp_path'));

	global $ym_sys;
	$ym_sys->update(array(
		'export_last_tmp_path' => $path
	));

	$format = ym_post('bkformat', 'xls');

	$all_users = get_users(array(
		'exclude'	=> array(1),
		'offset'	=> $offset,
		'number'	=> $limit,
	));
	
	foreach ($all_users as $user) {
		$user = (array)$user;
		$ym_user = new YourMember_User($user['ID']);

		// build rest of object
		/*$user_data = (array)$user_data;

		unset($user_data['spawn']);
		unset($user_data['data']);
		unset($user_data['userId']);
		unset($user_data['custom_fields']);*/
		$user_data = array();
		$user_data['ID'] = $ym_user->ID;
		$user_data['username'] = $ym_user->data->user_login;
		$user_data['email'] = $ym_user->data->user_email;
		$user_data['registered'] = date(YM_DATE, strtotime($ym_user->data->user_registered) + (get_option('gmt_offset') * 3600));
		//date(YM_DATE, $ym_display_a_user->expire_date) 
		$user_data['last_pay_date'] = date(YM_DATE, $ym_user->last_pay_date + (get_option('gmt_offset') * 3600));
		$user_data['expire_date'] = date(YM_DATE, $ym_user->expire_date + (get_option('gmt_offset') * 3600));
		$user_data['package_join_date'] = date(YM_DATE, $ym_user->account_type_join_date + (get_option('gmt_offset') * 3600));
		$user_data['role'] = $ym_user->role;
		$user_data['status'] = $ym_user->status;

		//PAckages data
		$user_data['package_type'] = $ym_user->account_type;
		$user_data['package_id'] = $ym_user->pack_id;



		//$user = array_merge($user, $user_data);
		if ($customs = ym_get_custom_field_array($ym_user->ID)) {
//			$user = array_merge($user, $customs);
			// as apposed to merge the other way
			// I want field order....
			// saves doing merge($user, $custom, $merge);
			foreach ($customs as $i => $d) {
				// don't overwrite....
				$user_data[$i] = isset($user_data[$i]) ? $user_data[$i] : $d;
			}
		}

		if (
			($package_type == 'all' || strtolower($package_type) == strtolower($user_data['package_type'])) &&
			($package == 'all' || $package == $user_data['package_id'])
			) {
			$status = $user_data['status'];
			if ($status == YM_STATUS_ACTIVE || $active) {
				$user_list[] = $user_data;
			}
		}
	}

	$chunk = ym_export_users_do_chunk($path, $user_list, $format);
	if ($chunk) {
		// check for more data needed
		if (count($user_list) == $limit) {
			// more needed
			echo '<p>Loading Next Chunk ' . $offset . '</p>';
			ym_export_users_operation_form($path, $offset + $limit);
		} else {
			// lets send the file
			ym_export_users_operation_send($path, $format);
			// complete or did last batch
			echo '<p>Complete</p>';
		}
	} else {
		echo 'Could not Open Temproary File';
	}
}

function ym_export_users_start($format) {
	// write batch to temporary file
	$file = trailingslashit(sys_get_temp_dir()) . 'ym_export';
	if (is_file($file)) {
		unlink($file);
	}
	$fp = fopen($file, 'w');
	if (!$fp) {
		return false;
	}

	if ($format != 'csv') {
		require_once(YM_CLASSES_DIR . 'ym-xls.class.php');
		$xls = new YourMember_xls();
		fwrite($fp, $xls->start());
	}

	fclose($fp);

	return true;
}

function ym_export_users_operation_form($path, $offset) {
	echo '<form action="" method="post" target="ym_exporting_users_frame" style="display: none;" id="ym_exporting_users_form" name="ym_exporting_users_form">';
	echo '<input type="hidden" name="backup_temp_path" value="' . $path . '" />';
	echo '<input type="hidden" name="ym_exporting_users" value="1" />';
	echo '<input type="hidden" name="offset" value="' . $offset . '" />';
	foreach ($_POST as $key => $item) {
		if ($key != 'offset') {
			echo '<input type="hidden" name="' . $key . '" value="' . $item . '" /> ' . $key . ' - ' . $item;
			echo '<br />';
		}
	}
	echo '<input type="submit" value="reload" />';
	echo '</form>';
	echo '
<script type="text/javascript">
document.getElementById(\'ym_exporting_users_form\').submit();
</script>
';
}

global $ym_export_did_headers, $xls_row_counter;
$ym_export_did_headers = FALSE;
$xls_row_counter = 0;
function ym_export_users_do_headers($data, $format) {
	global $ym_export_did_headers, $xls_row_counter;
	$path = trailingslashit(ym_post('backup_temp_path'));
	$offset = ym_post('offset', 0);
	$headers = ym_post('bkheaders') ? true : false;
	if (!$ym_export_did_headers && !$offset) {
		$ym_export_did_headers = TRUE;
		if ($headers) {
			$row = array();
			foreach ($data as $key => $trash) {
				$row[] = $key;
			}
			// write this row out
			ym_export_users_do_chunk($path, array($row), $format);
			$xls_row_counter = 1;
		}
	} else if ($offset) {
		$xls_row_counter = $offset;
		if ($headers) {
			$xls_row_counter++;
		}
	}
}

function ym_export_users_do_chunk($path, $chunk, $format) {
	global $xls_row_counter;
	ym_export_users_do_headers($chunk[0], $format);
	// write batch to temporary file
	$file = $path . 'ym_export';
	$fp = fopen($file, 'a');
	if (!$fp) {
		return false;
	}
	// write batch to temporary
	if ($format == 'csv') {
		// write csv rows to temporary
		foreach ($chunk as $row) {
			$row = (array)$row;
			foreach ($row as $key => &$item) {
				if (is_array($item) || is_object($item)) {
					$item = json_encode($item);
				}
			}
			fputcsv($fp, $row);
		}
	} else {
		// write some excel....
		require_once(YM_CLASSES_DIR . 'ym-xls.class.php');
		$xls = new YourMember_xls();
		$xls->row = $xls_row_counter;
		$data = $xls->generate_data($chunk);
		fwrite($fp, $data);
	}
	fclose($fp);

	return true;
}

function ym_export_users_operation_send($path, $format) {
	if ($format != 'csv') {
		$file = $path . 'ym_export';
		$fp = fopen($file, 'a');
		if (!$fp) {
			return false;
		}
		require_once(YM_CLASSES_DIR . 'ym-xls.class.php');
		$xls = new YourMember_xls();
		fwrite($fp, $xls->end());
		fclose($fp);
	}

	global $ym_upload_root, $ym_upload_url;

	$source = $path . 'ym_export';
	$filename = strtoupper(YM_ADMIN_FUNCTION) . '_EXPORT_' . strtoupper($format) . '_' . date('dmY') . '.' . $format;
	$file = trailingslashit($ym_upload_root) . $filename;
	$url = trailingslashit($ym_upload_url) . $filename;

	if ($ym_upload_root && rename($source, $file)) {
		echo '<p>' . sprintf(__('You can right click download the <a href="%s">File</a>, make sure to remove it from %s when you have done so', 'ym'), $url, $file) . '</p>'; 
	} else {
		rename($source, $path . $filename);
		echo '<p>' . sprintf(__('Could not move the file to Web Accessable, Please use FTP/SFTP to Download the Export from %s', 'ym'), $path . $filename) . '</p>';
	}
	exit;
}

function ym_export_coupon_xls($coupon_id){
	require_once(YM_CLASSES_DIR . 'ym-xls.class.php');
	$xls = new YourMember_xls();
	$all_users = ym_coupon_get_uses($coupon_id);
	$xls->download_from_array($all_users);
}
