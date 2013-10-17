<?php

/*
* $Id: yss_resource_functions.includes.php 1842 2012-02-01 14:26:14Z BarryCarlyon $
* $Revision: 1842 $
* $Date: 2012-02-01 14:26:14 +0000 (Wed, 01 Feb 2012) $
*/

function yss_s3_delete() {
	global $wpdb, $yss_db;

	$sql = 'DELETE FROM ' . $yss_db. '
		WHERE id = ' . $_REQUEST['id'];
	if ($wpdb->query($sql)) {
		ym_display_message(__('Video deleted Successfully','ym'));
	}
}

function yss_s3_save() {
	// saving
	get_currentuserinfo();
	global $wpdb, $current_user, $yss_db, $yss_post_assoc;

	$user = $current_user->ID;
	
	$file = ym_post('s3_file_select');
	$file = explode('/', $file);
	$bucket = array_shift($file);
	$resource = implode('/', $file);
	$account_types = ym_post('account_types');
	if (is_array($account_types)) {
		$account_types = implode('||', $account_types);
	} else {
		$account_types = '';
	}
	
	$members = (isset($_POST['memberonly']));

	if ($id = ym_post('s3s_id')) {
		$sql = "UPDATE " . $yss_db. " SET
					bucket = '" . $bucket . "'
					, resource_path = '" . $resource . "'
					, postDate = NOW()
					, user = '" . $user . "'
					, members = '" . $members . "'
					, account_types = '" . mysql_real_escape_string($account_types) . "'
				WHERE id = " . $id;
		$wpdb->query($sql);

		$sql = 'DELETE FROM ' . $yss_post_assoc . '
				WHERE s3_id = ' . $id;
		$wpdb->query($sql);
	} else {
		if ($bucket && $resource) {
			$sql = "INSERT INTO " . $yss_db. " (bucket, resource_path, postDate, user, members, account_types)
					VALUES (
						'" . $bucket . "'
						, '" . $resource . "'
						, NOW()
						, '" . $user . "'
						, '" . $members . "'
						, '" . mysql_real_escape_string($account_types) . "'
					)";
			$wpdb->query($sql);
			$id = $wpdb->insert_id;
			if ($id) {
				ym_display_message(__('New video created Successfully', 'yss'));
			} else {
				ym_display_message(__('Failed video Creation ', 'yss'));
			}
		} else {
			ym_display_message(__('No Resource and/or Bucket specified','yss'), 'error');
		}
	}

	if ($id) {
		if ($link_ids = ym_post('link_to_post_id')) {
			foreach ($link_ids as $post_id) {
				$sql = 'INSERT INTO ' . $yss_post_assoc . ' (s3_id, post_id)
					VALUES (' . $id . ', ' . $post_id . ')';
				$wpdb->query($sql);
			}
		}
	}
}

function yss_get_video_post_assoc($video_id) {
	global $yss_post_assoc, $wpdb;
	
	$sql = 'SELECT post_id
		FROM ' . $yss_post_assoc . '
		WHERE s3_id = ' . $video_id;
	$results = $wpdb->get_results($sql);
	
	return $results;
}

function yss_get($id=false) {
	global $wpdb, $yss_db;
	
	$row = false;
	
	if ($id) {
		$sql = 'SELECT id, bucket, resource_path, postDate, members, account_types, user, distribution
				FROM ' . $yss_db. '
				WHERE id = ' . $id;
		$row = $wpdb->get_row($sql);
	}

	return $row;
}

function yss_s3_edit($id=false) {
	global $wpdb, $yss_post_assoc;

	$checked = array();
	$s3file = yss_get($id);

	$sql = 'SELECT ID, post_title
			FROM ' . $wpdb->posts . '
			WHERE post_status = "publish"
			AND post_type IN ("page","post")
			ORDER BY post_title';
	$posts = $wpdb->get_results($sql);

	if ($id) {
		$sql = 'SELECT post_id
				FROM ' . $yss_post_assoc . '
				WHERE s3_id = ' . $id;
		$results = $wpdb->get_results($sql);

		foreach ($results as $result) {
			$checked[] = $result->post_id;
		}
	}

	echo ym_start_box(($id ? 'Edit Video' : 'Add Video'));
	
	if (!$id) {
		require_once(YSS_CLASSES_DIR . 'S3.php');
		$s3 = new S3();
		$s3->setAuth(get_option('yss_user_key'), get_option('yss_secret_key'));
	}
	
	echo '
			<table class="widefat form-table" style="width: 100%;" cellspacing="10">
				<tr valign="top">
					<td>
						' . __('S3 Bucket/file',"ym") . '
					</td>
					<td>';
					
					if (!$id) {
						echo '
						<select name="s3_file_select">
						';
							foreach ($s3->listBuckets() as $bucket) {
								$thisbucket = $s3->getBucket($bucket);

								foreach ($thisbucket as $file) {
									echo '<option ';
									
									if ($s3file->bucket . '/' . $s3file->resource_path == $bucket . '/' . $file['name']) {
										echo 'selected="selected"';
									}
									
									echo '>' . $bucket . '/' . $file['name'] . '</option>';
								}
							}
echo '
	</select>
';
					} else {
						echo $s3file->bucket . '/' . $s3file->resource_path;
						echo '<input type="hidden" name="s3_file_select" value="' . $s3file->bucket . '/' . $s3file->resource_path . '" />';
					}
					echo '
					</td>
				</tr>
				<tr valign="top">
					<td>
						' . __('Your Members Package Types access',"ym") . '
						<div style="font-size: 10px; color: gray; margin-top: 10px;">Your videos can be protected by account type here. If none of the boxes are checked then it will fall back to the next section (post protection)</div>
					</td><td>';
	echo '	<div>';
	
	if ($data = get_option('ym_account_types')) {
		$types = $data->types;	
		
		$ac_checked = array();
		if ($selected = @$s3file->account_types) {
		    $ac_checked = explode('||', $selected);
		}
	
		foreach ((array)$types as $type) {
		    $checked_string = '';
		    
		    if (in_array($type, $ac_checked)) {
			$checked_string = 'checked="checked"';
		    }
	
		    echo '  <div class="ym_setting_list_item">
				<label>
				    <input type="checkbox" class="checkbox" name="account_types[]" value="' . $type . '" ' . $checked_string . ' /> ' . __($type) . '
				</label>
			    </div>';
		}
	} else {
		echo '<div>The system is unable to find any YM account types. Is there a problem with the install?</div>';
	}

	echo '</div>';

	echo '				</td>
				</tr>				
				<tr valign="top">
					<td>
						' . __('Restrict access by post/page?',"ym") . ' <input type="checkbox" name="memberonly" ' .(@$s3file->members ? "checked='checked'":'') . ' /> (Check to activate)
						<div style="font-size: 10px; color: gray; margin-top: 10px;">If the above account type check fails or you choose not to use it then you can optionally use this section. This will check access against a number of posts or pages and if at least one has access then the video will be shown.<br /><br />If the restrict access checkbox is unticked then YSS will assume that the video should remain unprotected (if you are not using the account type protection)</div>
					</td>
					<td>
						<br /><select name="link_to_post_id[]" multiple size=10 style="height: 250px; width: 450px;">';

	foreach ($posts as $row) {
		$selected = (in_array($row->ID, $checked) ? 'selected="selected"':'');
		echo '<option value="' . $row->ID . '" ' . $selected . ' >' . $row->post_title . '</option>';
	}

	echo '				</select>
					</td>
				</tr>';

	echo '	</table>
					
			<p class="submit">
				<div style="float: right;">
					<input type="submit"  class="button" name="submit_edit_s3" value="' . __('Save', 'yss') . '" />
				</div>
				<input type="submit" value="' . __('Back', 'yss') . '" />
				<div class="ym_clear">&nbsp;</div>
			</p>
			
			<input type="hidden" name="task" value="save" />
			<input type="hidden" name="s3s_id" value="' . @$s3file->id . '" /> 
';

	echo ym_end_box();
}
	
function yss_s3_list() {
	get_currentuserinfo();
	global $yss_db, $wpdb, $date_format, $current_user;

	$header_style = 'border-bottom: 1px solid silver; font-weight: bold;';
	$sql = 'SELECT *
			FROM ' . $yss_db. ' 
			ORDER BY id';
	$s3s = $wpdb->get_results($sql);

	echo ym_start_box('Videos');
	echo '<p>' . __('Videos can be associated with content or Package types. When associated, they take on the page or post permissions including post purchased. Non associated videos are accessible by all.', 'yss') . '</p>';
	echo '<p>' . __('YSS will attempt to use Streaming, then Download and finally S3', 'yss') . '</p>';
	echo '<p>' . __('Note: YSS does not support non FLV files in a streaming distribution under FlowPlayer. It just does not work!', 'yss') . '</p>';

	echo '	<table class="widefat form-table" style="width: 100%;" cellspacing="0">
			<thead>
			<tr>
				<td style="' . $header_style . '">' . __('ID', 'yss') . '</td>
				<td style="' . $header_style . '">' . __('Bucket', 'yss') . '</td>
				<td style="' . $header_style . '">' . __('Resource Path', 'yss') . '</td>
				<td style="' . $header_style . '">' . __('Distribution', 'yss') . '</td>
				<td style="' . $header_style . ' width: 150px; text-align: center;">' . __('Limited Access (<span title="Video protected by Your Members Package Types">ACs</span>)', 'yss') . '</td>
				<td style="' . $header_style . ' width: 150px; text-align: center;">' . __('Limited Access (<span title="Video protected via access to Posts/Pages protected by YM">Posts</span>)', 'yss') . '</td>
				<td style="' . $header_style . '">' . __('Posted', 'yss') . '</td>
				<td style="' . $header_style . '">' . __('Action', 'yss') . '</td>
			</tr>
			</thead>
			<tbody>';

	if ($s3s) {
		foreach ($s3s as $s3) {
			$date = date($date_format, strtotime($s3->postDate));

//			$user = get_userdata($s3->user);
			
			$stream_distribute = YM_ADMIN_INDEX_URL . '&ym_page=ym-hook-yss_content&task=stream&id=' . $s3->id;
			$download_distribute = YM_ADMIN_INDEX_URL . '&ym_page=ym-hook-yss_content&task=dload&id=' . $s3->id;
			
			$dl = $s = 'No';
			if ($s3->distribution) {
				$distribution = json_decode($s3->distribution);
				if (isset($distribution->download) && $distribution->download) {
					$dl = 'Yes';
				}
				if (isset($distribution->streaming) && $distribution->streaming) {
					$s = 'Yes';
				}
			}

			echo '<tr>
					<td>'.$s3->id.'</td>
					<td>'.$s3->bucket.'</td>
					<td>'.$s3->resource_path.'</td>
					<td>
					';
				if (!get_option('yss_cloudfront_id') || !get_option('yss_cloudfront_private')) {	
					echo '
						N/A
					';
				} else {
					echo '
						DL: <a href="' . $download_distribute . '">' . $dl . '</a>
						S: <a href="' . $stream_distribute . '">' . $s . '</a>
					';
				}
					echo '
					</td>
					<td style="text-align:center; font-weight: bold;">
						' . ($s3->account_types ? __('<span style="color: green;">' . implode(', ', explode('||', $s3->account_types)) . '</span>', 'yss'):__('<span style="color: red;">No</span>', 'yss')) . '
					</td>					
					<td style="text-align:center; font-weight: bold;">
						' . ($s3->members ? __('<span style="color: green;">Yes</span>', 'yss'):__('<span style="color: red;">No</span>', 'yss')) . '
					</td>
					<td>'.$date.' by '.$current_user->user_login.'</td>
					<td style="line-height: 2em;">
						<a href="' . YM_ADMIN_INDEX_URL . '&ym_page=ym-hook-yss_content&task=edit&id=' . $s3->id . '">Edit</a> | <a href="' . YM_ADMIN_INDEX_URL . '&ym_page=ym-hook-yss_content&task=delete&id=' . $s3->id . '" class="deletelink">Delete</a>
					</td>
				</tr>
				';
		}
	} else {
		echo '	<tr>
				<td colspan="6">'.__('You have yet to add any videos.', 'yss').'</td>
			</tr>';
	}

	echo '</tbody>';
	echo '			</table>';

	echo '<input type="hidden" name="id" id="yss_edit_id" value="" />';

	echo '
<p class="submit" style="text-align: right;">
	<input type="hidden" name="task" value="add" />
	<input type="submit" value="Add New Video" />
</p>';

	echo ym_end_box();
}
