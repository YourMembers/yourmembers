<?php

$action = ym_post('action') ? ym_post('action') : ym_get('action');
$date_format = get_option('date_format');

echo '<div class="wrap" id="poststuff" style="margin-bottom: 10px;">';

if (in_array($action, array('add', 'edit'))) {
	ym_download_edit(ym_get('id'));
} else {
	if ($action == 'delete') {
		ym_delete_download();
	}

	if (ym_post('submit_edit_download')) {
		ym_download_save();
	}

	ym_download_list();
}

ym_box_top(__('Download Manager Settings', 'ym'));

global $ym_formgen, $ym_sys;
$hook = ($ym_sys->download_hook ? $ym_sys->download_hook:'download');

if ((isset($_POST['settings_update'])) && (!empty($_POST['settings_update']))) {
	$ym_sys->update_from_post();

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

echo '<form action="" method="post">';
echo '<table class="form-table">';
$ym_formgen->render_form_table_text_row(__('Download Manager Hook', 'ym'), 'download_hook', $ym_sys->download_hook, __('The hook that the download manager looks for. Default is "download" which would form [download#1] within a post', 'ym'));
echo '</table>';
echo '<p>' . sprintf(__('Your Download Manager short code is %1$s which forms <strong>[%1$s#1]</strong> for example', 'ym'), $ym_sys->download_hook) . '</p>';
echo '<p class="submit" style="text-align: right;"><input type="submit" name="settings_update" value="' . __('Update Settings', 'ym') . '" />';
echo '</form>';

ym_box_bottom();

echo '</div>';


function ym_download_edit($id=false) {
	global $wpdb, $ym_dl_post_assoc;

	$checked = array();
	$download = ym_get_download($id);
	$attribs = ym_get_download_attributes($id);

	$posts = get_posts(array(
		'post_status' => 'publish',
		'post_type' => 'any',
		'numberposts' => -1
	));

	if ($id) {
		$sql = 'SELECT post_id
				FROM ' . $ym_dl_post_assoc . '
				WHERE download_id = ' . $id;
		$results = $wpdb->get_results($sql);

		foreach ($results as $result) {
			$checked[] = $result->post_id;
		}
	}

	echo ym_start_box(($id ? __('Edit Download: ', 'ym') . $download->title : __('Add Download', 'ym')));

	echo '	<form enctype="multipart/form-data" action="" method="post">
			<input type="hidden" name="action" value="goedit" />
			<table style="width: 100%;" cellspacing="10">
				<tr valign="middle">
					<td>' . __('Title (required)',"ym") . '</td>
					<td>
						<input type="text" style="width: 320px;" value="' . $download->title . '" name="title" />
					</td>
				</tr>
				<tr valign="top">
					<td>
						' . __('Upload a file',"ym") . '
					</td>
					<td>
						<div style="margin-bottom: 10px">' . __('Point to a file already on the server <small>(http:// etc required for this to work)' ,'ym') . '</small>
						<br /><input type="text" name="remote_file" style="width: 700px;" /></div>
						<div style="margin-bottom: 10px"><strong>' . __('OR', 'ym') . '</strong></div>
						<div style="margin-bottom: 10px">' . __('Upload the file directly', 'ym') . '
						<input type="file" name="upload" style="width: 320px;" /></div>
						<div>' . ($id ? '<br />' . __('Currently Using:', 'ym') . ' <em>' . $download->filename . '</em>.':'') . '</div>
					</td>
				</tr>
				<tr valign="top">
					<td>' . __('Restrict Access?',"ym") . '</td>
					<td>
						<input type="checkbox" name="memberonly" ' .($download->members ? "checked='checked'":'') . ' />
						<span style="color: gray; font-size: 10px; font-weight: normal;">' . __('If chosen, only users of the appropriate access level can access the file. User level is calculated by checking access to a certain post or posts.',"ym") . '</span>
						<br /><select name="link_to_post_id[]" multiple size=10 style="height: 250px; width: 450px;">';

	foreach ($posts as $row) {
		$selected = (in_array($row->ID, $checked) ? 'selected="selected"':'');
		echo '<option value="' . $row->ID . '" ' . $selected . ' >' . $row->post_title . '</option>';
	}

	echo '				</select>
					</td>
				</tr>';

	foreach ($attribs as $i=>$attrib) {
		$value = $attrib->value;

		echo '<tr>
				<td style="vertical-align: top;">'.ucfirst($attrib->name).'</td>
				<td style="vertical-align: top;">';

		ym_generate_field($attrib->field_name, $attrib->field_type_id, $value, false, 'attribute_' . $attrib->id);

		echo '<div style="font-size: 10px; color: gray;">' . $attrib->caption . '</div>';
		echo '</td>
		</tr>';
	}


	echo '	</table>
					
			<p class="submit">
				<div style="float: right;">
					<input type="submit"  class="button" name="submit_edit_download" value="' . __('Save Download',"ym") . '" />
				</div>
				<input type="button" class="button" onclick="document.location=\'' . YM_ADMIN_URL . '&ym_page=ym-content-downloads\';" value="' . __('Back to downloads', 'ym') . '" />
			</p>
			
			<input type="hidden" name="download_id" value="' . $download->id . '" /> 
			</form>';

	echo ym_end_box();
}

function ym_download_list() {
	get_currentuserinfo();
	global $ym_dl_db, $wpdb, $date_format, $current_user, $ym_upload_root;

	if (!is_dir($ym_upload_root)) {
		if (@mkdir($ym_upload_root, 0664)) {
			ym_display_message(__('The uploads directory did not exist so it was created and the permissions set to 664. Please make sure to update these permissions if you are not happy with them.'));
		} else {
			ym_display_message(__('The uploads directory does not exist and it could not be created. Please make sure that "' . $ym_upload_root . '" is present and writeable by PHP before adding any downloads.', 'ym'), 'error');
		}
	}
	
	$header_style = 'border-bottom: 1px solid silver; font-weight: bold;';
	$downloadurl = get_option('ym_dl_url');
	$downloadtype = get_option('ym_dl_type');
	$sort = ym_request('sort', "title");
	$sql = 'SELECT *
			FROM ' . $ym_dl_db . ' 
			ORDER BY ' . $sort;
	$download = $wpdb->get_results($sql);

	echo '<p>' . __('Downloads can be associated with pages and posts. When associated, they take on the page or post permissions including post purchased. Non associated downloads are accessible by all.','ym') . '</p>';

	echo ym_start_box('All Downloads');
	if (!is_writeable($ym_upload_root)) {
		ym_display_message(__('The uploads directory is not writeable by PHP and therefore anything uploaded using this tool will fail. Please set the permissions and then refresh this page to see if you have been successful.', 'ym'), 'error');
	}

	echo '			<table style="width: 100%;" cellspacing="0" class="ym_table">
						<tr>
							<th><a href="' . YM_ADMIN_URL . '&ym_page=ym-content-downloads&sort=id">' . __('ID',"ym") . '</a></td>
							<th><a href="' . YM_ADMIN_URL . '&ym_page=ym-content-downloads&sort=title">' . __('Title',"ym") . '</a></td>
							<th><a href="' . YM_ADMIN_URL. '&ym_page=ym-content-downloads&sort=filename">' . __('File',"ym") . '</a></td>
							<th style="width: 150px; text-align: center;">' . __('Limited Access',"ym") . '</td>
							<th style="width: 140px; text-align: center;">' . __('File Exists?',"ym") . '</td>
							<th style="width: 200px;"><a href="' . YM_ADMIN_URL . '&ym_page=ym-content-downloads&sort=postDate">' . __('Posted',"ym") . '</a></td>
							<th style="width: 130px;">' . __('Action',"ym") . '</td>
						</tr>';

	if ($download) {
		foreach ($download as $d) {
			$date = date($date_format, strtotime($d->postDate));
			$path = get_option('siteurl') . "/wp-content/uploads/";
			$file = str_replace($path, "", $d->filename);
			$links = explode("/",$file);
			$file = end($links);
			$user = get_userdata($d->user);
			$abs_file = ym_get_abs_file($d->filename);

			$edit_link = YM_ADMIN_URL.'&ym_page=ym-content-downloads&action=edit&id='.$d->id.'&sort='.$sort;
			$delete_link = YM_ADMIN_URL.'&ym_page=ym-content-downloads&action=delete&id='.$d->id.'&sort='.$sort;

			echo '	<tr>
						<td>'.$d->id.'</td>
						<td>'.$d->title.'</td>
						<td>'.$file.'</td>
						<td style="text-align:center; font-weight: bold;">
							' . ($d->members ? __('<span style="color: green;">Yes</span>', 'ym'):__('<span style="color: red;">No</span>','ym')) . '
						</td>
						<td style="text-align:center; font-weight: bold;">
							' . (file_exists($abs_file) ? __('<span style="color: green;">Yes</span>', 'ym'):__('<span style="color: red;">No</span>','ym')) . '
						</td>
						<td>'.$date.' by '.$current_user->user_login.'</td>
						<td style="line-height: 2em;">
							<a class="button" href="'.$edit_link.'">' . __('Edit', 'ym') . '</a>
							<a class="button" href="'.$delete_link.'">' . __('Delete', 'ym') . '</a>
						</td>
					</tr>';
		}

	} else {
		echo '	<tr>
					<td colspan="6">'.__('No downloads have been added yet.',"ym").'</td>
				</tr>';
	}

	echo '			</table>';

	echo '	<p class="submit">
				<form action=""	method="post" id="ym_dl_add" name="add_download">
					<input type="hidden" name="action" value="add" />
					<input type="submit" class="button" name="" value="' . __('Add New Download',"ym") . '" />
				</form>
			</p>';

	echo ym_end_box();
}
