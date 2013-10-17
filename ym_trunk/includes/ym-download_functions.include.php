<?php
//ym-download_functions.include.php

function ym_dl_ins($args, $content, $tag) {
	$return = '';
	
	if (!isset($args['format'])) {
		$args['format'] = 'link';
	}
	
	if (isset($args[0]) && !isset($args['id'])) {
		if (substr($args[0], 0, 1) == '#') {
			$args['id'] = substr($args[0], 1);
		}
	}	
	
	if (isset($args['id']) && $args['id'] != '') {
		if ($d = ym_get_download($args['id'])) {
			$download_url = trailingslashit(get_option('siteurl')) . '?ym_download_id=' . $d->id;
			
			switch ($args['format']) {
				case 'url':
					$return = $download_url;
					break;
				
				case 'image':
					$return = '<a href="' . $download_url . '" title="' . $d->title . '"><img src="'.YM_PLUGIN_DIR_URL . '/images/download.gif" alt="' . $d->title . '" /></a>';
					break;
		
				case 'full':
					$return = '<a href="' . $download_url . '" title="' . $d->title . '" >' . $d->title . ' - ' . ym_dl_get_size($d->filename) . '</a>';
					break;
					// Image link
				default:
					$return = '<a href=\'' . $download_url . '\' title=\'' . $d->title . '\' >' . $d->title . '</a>';
				break;
			}
			
		}
	}

	return $return;
}

// Formats file size
function ym_dl_get_size($path) {
	$path = str_replace(trailingslashit(get_option('siteurl')),"./",$path);

	if (file_exists($path)) {
		if ($size = filesize($path)) {

			$bytes = array('bytes','KB','MB','GB','TB');
			foreach($bytes as $val) {

				if($size > 1024){
					$size = $size / 1024;
				} else {
					break;
				}
			}
				
			return round($size, 2)." ".$val;
		}
	}
}


function ym_get_download_attributes($download_id=false) {
	global $wpdb;

	$value = '"" AS `value`';
	if ($download_id) {
		$value = '	(SELECT value
					FROM ' . $wpdb->prefix . 'ym_download_attribute
					WHERE
						attribute_id = t.id
						AND download_id = ' . $download_id . '
					) AS `value`';
	}

	$sql = 'SELECT
				' . $value . '
				, t.id
				, t.name
				, t.field_type_id, CONCAT("attributes[",t.id,"]") AS `field_name`
				, t.description AS `caption`
			FROM ' . $wpdb->prefix . 'ym_download_attribute_type t';
	$results = $wpdb->get_results($sql);
	return $results;
}

function ym_download_attributes_save($download_id) {
	if ($attributes = ym_post('attributes')) {
		foreach ($attributes as $type_id=>$value) {
			ym_download_attribute_add($download_id, $type_id, $value, true);
		}
	}
}

function ym_download_attribute_delete($download_id, $attribute_id=false) {
	global $wpdb;

	$sql = 'DELETE FROM ' . $wpdb->prefix . 'ym_download_attribute
			WHERE download_id = ' . $download_id;

	if ($attribute_id) {
		if (!is_array($attribute_id)) {
			$attribute_id = array($attribute_id);
		}

		$sql .= ' AND attribute_id IN (' . implode(',', $attribute_id) . ')';
	}

	$wpdb->query($sql);
}

function ym_download_attribute_add($download_id, $type_id, $value, $delete_first=false) {
	global $wpdb;

	if ($delete_first) {
		ym_download_attribute_delete($download_id, $type_id);
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_download_attribute (
				download_id
				, attribute_id
				, value
			)
			VALUES (
				' . $download_id . '
				, "' . $type_id . '"
				, "' . $value . '"
			)';
	$wpdb->query($sql);

}

function ym_get_download_posts($download_id) {
	global $wpdb, $ym_dl_post_assoc;

	$sql = 'SELECT post_id
			FROM ' . $ym_dl_post_assoc . '
			WHERE download_id = ' . $download_id;
	return $wpdb->get_results($sql);
}

function ym_download_file($download_id) {
	get_currentuserinfo();
	global $wpdb, $current_user, $ym_upload_root;

	$allow_download = true;

	if ($download = ym_get_download($download_id)) {
		if ($download->members) {
			$allow_download = false;

			if ($current_user->ID) {
				if (!isset($current_user->caps['administrator'])) {
					$posts = ym_get_download_posts($download_id);

					foreach ($posts as $post) {
						if (ym_user_has_access($post->post_id)) {
							$allow_download = true;
							break;
						}
					}
				} else {
					$allow_download = true;
				}
			}
		}

		if ($allow_download) {
			$abs_file = ym_get_abs_file($download->filename);

			if (file_exists($abs_file)) {
				
				set_time_limit(0);
				ini_set('memory_limit', -1);
					
				$file_name = strrpos($download->filename,'/');
				$loc = substr($download->filename, 0, $file_name);
				$file_name = substr($download->filename,$file_name+1);
				
				@ym_log_transaction(YM_DOWNLOAD_STARTED, $download->filename, $current_user->ID);

				header("Pragma: public"); // required
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private",false); // required for certain browsers
				header("Content-type: application/force-download");
				header("Content-Transfer-Encoding: Binary");
				header("Content-length: ".@filesize($abs_file));
				header("Content-disposition: attachment; filename=\"" . $file_name . "\"");
				//readfile($abs_file);
				
				flush();
				$file = fopen($abs_file, "rb");
				while(!feof($file))
				{
				    // send the current file part to the browser
				    print fread($file, 8192);
				    flush();
				}
				fclose($file);				
				
				@ym_log_transaction(YM_DOWNLOAD_COMPLETED, $download->filename, $current_user->ID);
				exit();
			} else {
				echo __('You can not download this file because it does not exist. Please notify the Administrator.', 'ym');
				exit();
			}
		} else {
			echo __('You can not download this file because you do not have access','ym');
			exit();
		}
	} else {
		die;
	}
}

function ym_get_abs_file($filename) {
	return str_replace(trailingslashit(get_option('siteurl')), str_replace('\\', '/', ABSPATH), $filename);
}


function ym_download_save() {
	get_currentuserinfo();
	global $wpdb, $current_user, $ym_dl_db, $ym_dl_post_assoc, $ym_upload_root, $ym_upload_url;

	$title = ym_post('title');
	$remote_file = ym_post('remote_file');
	$user = $current_user->ID;
	$members = (isset($_POST['memberonly']));
	$filename = false;


	if ($remote_file) {
		$filename = $remote_file;
	} else if ($_FILES['upload']['error'] !=4) {
		$my_upload = new ym_dl_file_upload;
		$my_upload->upload_dir = trailingslashit($ym_upload_root);
		$my_upload->max_length_filename = 255;
		$my_upload->rename_file = false;
		$my_upload->the_temp_file = $_FILES['upload']['tmp_name'];
		$my_upload->the_file = $_FILES['upload']['name'];
		$my_upload->http_error = $_FILES['upload']['error'];
		$my_upload->replace = 1;
		$my_upload->do_filename_check = "n";

		if ($my_upload->upload()) {
			$filename = trailingslashit($ym_upload_url) . $my_upload->file_copy;
		} else {
			ym_display_message($my_upload->show_error_string(), 'error');
		}
	}

	if ($id = ym_post('download_id')) {
		$sql = "UPDATE " . $ym_dl_db . " SET
					title = '" . $title . "'
					" . ($filename ? ", filename = '" . $filename . "'":"") . "
					, postDate = NOW()
					, user = '" . $user . "'
					, members = '" . $members . "'
				WHERE id = " . $id;
		$wpdb->query($sql);

		$sql = 'DELETE FROM ' . $ym_dl_post_assoc . '
				WHERE download_id = ' . $id;
		$wpdb->query($sql);

		ym_display_message(__('Download updated Successfully: ',"ym") . ($filename ? __('File Replaced', 'ym'):__('File NOT Replaced', 'ym')));
	} else {
		if ($filename) {
			$sql = "INSERT INTO " . $ym_dl_db . " (title, filename, postDate, user, members)
					VALUES (
						'" . $title . "'
						, '" . $filename . "'
						, NOW()
						, '" . $user . "'
						, '" . $members . "'
					)";
			$wpdb->query($sql);
			$id = $wpdb->insert_id;

			ym_display_message(__('New Download created Successfully','ym'));
		} else {
			ym_display_message(__('Problem uploading file to ','ym') . $ym_upload_root, 'error');
		}
	}

	if ($id) {
		ym_download_attributes_save($id);

		if ($link_ids = ym_post('link_to_post_id')) {
			foreach ($link_ids as $post_id) {
				$sql = 'INSERT INTO ' . $ym_dl_post_assoc . ' (download_id, post_id)
						VALUES (' . $id . ', ' . $post_id . ')';
				$wpdb->query($sql);
			}
		}
	}
}

function ym_delete_download() {
	global $wpdb, $ym_dl_db;

	$sql = 'DELETE FROM ' . $ym_dl_db . '
			WHERE id = ' . $_GET['id'];
	if ($wpdb->query($sql)) {
		ym_display_message(__('Download deleted Successfully','ym'));
	}
}

function ym_get_download($id=false) {
	global $wpdb, $ym_dl_db;

	$row = new stdClass();
	$row->id = $row->title = $row->filename = $row->postDate = $row->members = $row->user = false;

	if ($id) {
		$sql = 'SELECT id, title, filename, postDate, members, user
				FROM ' . $ym_dl_db . '
				WHERE id = ' . $id;
		$row = $wpdb->get_row($sql);
	}

	return $row;
}

function ym_get_downloads() {
	global $wpdb, $ym_dl_db;

	$sql = 'SELECT id, title, filename, postDate, members, user
			FROM ' . $ym_dl_db;
	return $wpdb->get_results($sql);
}
