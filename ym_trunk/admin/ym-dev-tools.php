<?php

/*
* $Id: ym-dev-tools.php 2283 2012-08-08 10:30:09Z bcarlyon $
* $Revision: 2283 $
* $Date: 2012-08-08 11:30:09 +0100 (Wed, 08 Aug 2012) $
*/

global $ym_sys;
if (!defined('ym_dev') || !$ym_sys->dev_tools) {
	return;
}
if (ym_get('do_munch')) {
	$var = ym_get('option_name');
	$var2 = ym_get('user_meta_name');
	$user_id = ym_get('user_id');
	if ($var) {
		$data = get_option($var);
		print_r(get_option($var));
	} else if ($var2) {
		$data = get_user_meta($user_id, $var2);
		print_r($data);
	} else {
		echo 'No Var';
	}
	exit;
}
global $wpdb;
$name = FALSE;

echo '<div class="wrap" id="poststuff">';
echo ym_box_top(__('Reset', 'ym'));

if (@$_POST['reset_sys']) {
	delete_option('ym_sys');
	$ym_sys = new YourMember_System();
	$ym_sys->initialise();
	echo 'ym_sys reset';
}
if (@$_POST['reset_res']) {
	delete_option('ym_res');
	$ym_res = new YourMember_Resources();
	$ym_res->initialise();
	echo 'ym_res reset';
}
if (@$_POST['reset_all']) {
	$_REQUEST['ym_uninstall'] = 1;
	$key = get_option('ym_license_key');
	$tos = get_option('ym_tos_version_accepted');
	$db = get_option('ym_db_version');
	ym_deactivate();
	unset($_REQUEST['ym_uninstall']);

	global $wpdb;
	$query = 'INSERT INTO ' . $wpdb->options . ' (option_name, option_value) VALUES 
(\'ym_license_key\', \'' . $key . '\'),
(\'ym_tos_version_accepted\', \'' . $tos . '\'),
(\'ym_db_version\', \'' . $db . '\'),
(\'ym_dev\', 1)';
	$wpdb->query($query);

	echo '<p>Step 1/2 Complete</p>';
	echo '<meta http-equiv="refresh" content="1;url=admin.php?page=ym/admin/ym-index.php&ym_page=ym-dev-tools&reset_all=1" />';
	return;
}
if (@$_GET['reset_all']) {
	ym_activate();
	echo '<p>Step 2/2 Complete</p>';
	echo '<meta http-equiv="refresh" content="1;url=admin.php?page=ym/admin/ym-index.php&ym_page=ym-dev-tools&reset_ok=1" />';
	return;
}
if (@$_GET['reset_ok']) {
	echo 'Reinitialised - Saved License, TOS and DB Version';
}

if (@$_POST['fidget_patch'] || @$_GET['fidget_patch'] || @$_POST['kopstad_patch'] || @$_GET['kopstad_patch']) {
	global $wpdb;

	$start = 0;
	$limit = 20;
	$did = 0;


	if (@$_POST['fidget_patch'] || @$_GET['fidget_patch']) {
		$patch = 'fidget_patch';
		$query = 'SELECT * FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'ym_user\' AND meta_value LIKE \'%stdClass%\' LIMIT ' . $start . ',' . $limit;

		foreach ($wpdb->get_results($query) as $row) {
			$userid = $row->user_id;
			$value = get_user_meta($userid, 'ym_user', TRUE);
			
			if ($value->scalar) {
				$user_data = $value->scalar;
			} else {
				$user_data = new YourMember_User();
				foreach ($user_data as $key => $item) {
					$user_data->$key = $value->$key;
				}
			}
			
			update_user_meta($userid, 'ym_user', $user_data);
			$did ++;
		}
	} else if (@$_POST['kopstad_patch'] || @$_GET['kopstad_patch']) {
		$patch = 'kopstad_patch';
		$query = 'SELECT * FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'ym_user\' AND meta_value LIKE \'s%\' LIMIT ' . $start . ',' . $limit;

		foreach ($wpdb->get_results($query) as $row) {
			$userid = $row->user_id;
			$value = get_user_meta($userid, 'ym_user', TRUE);
			
			$squery = 'UPDATE ' . $wpdb->usermeta . ' SET meta_value = \'' . $value . '\' WHERE umeta_id = ' . $row->umeta_id;
			$wpdb->query($squery);
			$did ++;
		}
	}

	$run = FALSE;
	if ($did == $limit) {
		$run = TRUE;
	}
	echo 'Completed ' . $limit . ' from ' . $start . ' this run ' . $did;
	if ($run) {
		echo '<br />More to Do, Sleeping';
		echo '<meta http-equiv="refresh" content="5;url=admin.php?page=ym/admin/ym-index.php&ym_page=ym-dev-tools&' . $patch . '=1&start=' . ($start + $limit) . '" />';
		return;
	}
	echo '<br />Completed ' . $patch . ' Patch';
}
if (@$_POST['de_dupe_package_types']) {
	$ym_p_t = new YourMember_Package_Types();
//	$ym_p_t->sanity();
	$ym_p_t->save();
	echo '<br />Completed De Dupe Package Types';

	$name = 'ym_package_types';
}
if (@$_POST['de_dupe_custom_fields']) {
	$fld_obj = get_option('ym_custom_fields');
	$entries = $fld_obj->entries;
	
	$new_data = array();
	$found = array();

	foreach ($entries as $entry) {
		if (!in_array($entry['name'], $found)) {
			$new_data[] = $entry;
			$found[] = $entry['name'];
		}
	}
	$fld_obj->entries = $new_data;
	update_option('ym_custom_fields', $fld_obj);
	echo '<br />Completed De Dupe Custom Fields';

	$name = 'ym_custom_fields';
}

if (@$_POST['meta_change']) {
	$name = $_POST['option_name'];
	$value = stripslashes($_POST['option_value']);

	if ($name && $value !== FALSE) {
		$test = substr($value, 0, 2);
		if ($test == 'O:' || $test == 'A:') {
			$query = 'UPDATE ' . $wpdb->options . ' SET option_value = \'' . $value . '\' WHERE option_name = \'' . $name . '\'';
			$wpdb->query($query);
		} else {
			update_option($name, $value);
		}
		echo '<p>Done *shudder*</p>';
	}
}
if (@$_POST['user_meta_change']) {
	$id = $_POST['user_id'];
	$user_meta = $_POST['user_meta_name'];
	$value = stripslashes($_POST['user_meta_value']);

	if ($id && $user_meta && $value) {
//		$test = substr($value, 0, 2);
//		if ($test == 'O:' || $test == 'A:') {
//			$query = 'UPDATE ' . $wpdb->options . ' SET option_value = \'' . $value . '\' WHERE option_name = \'' . $name . '\'';
//			$wpdb->query($query);
//		} else {
			update_user_meta($id, $user_meta, $value);
//		}
		echo '<p>Done usermeta *shudder*</p>';
	} else {
		echo '<p>Failed UserMeta</p>';
	}
}

if (@$_POST['remove_private_tags']) {
	global $wpdb;
	$query = 'SELECT ID, post_content FROM ' . $wpdb->posts . ' WHERE post_content LIKE \'[private%\'';

	echo '<p>Updated ';
	foreach ($wpdb->get_results($query) as $row) {
		$id = $row->ID;
		$content = $row->post_content;

		$content = str_replace('[/private]', '', $content);
		$start = strpos($content, '[private');
		while (FALSE !== $start) {
			$end = strpos($content, ']', $start);
			$end++;
			$remove = substr($content, $start, $end);

			$content = str_replace($remove, '', $content);
			$start = strpos($content, '[private');
		}

		$sql = 'UPDATE ' . $wpdb->posts . ' SET post_content = \'' . $content . '\' WHERE ID = ' . $id;
		$wpdb->query($sql);
		echo $id . ' ';
	}
	echo '</p>';
}
if (@$_POST['build_test_posts']) {
	global $wpdb;

	$query = 'TRUNCATE ' . $wpdb->posts;
//	$wpdb->query($query);

// a private post
// register/upgrade
// user has access/no access
// post shortcode with private
// bundle shortcode
// profile page
// register/upgrade page
//	$query = 'INSERT INTO ' . $wpdb->posts . '(post_title, post_content) VALUES (\'YM Reg/YM Upgrade\', \'[user_has_access]);
//	$wpdb->query($query);

	echo '<p>Done</p>';
}

if (@$_POST['run_db_step']) {
	function ym_dev_ym_beta_db_check_target($target) {
		return $_POST['db_step'];
	}

	if ($_POST['db_step']) {
		echo 'Here we go: ' . $_POST['db_step'] . '<br />';
		// run a db step
//		add_filter('ym_beta_db_check_target', 'ym_dev_ym_beta_db_check_target');
//		ym_database_updater();

		$file = YM_PLUGIN_DIR_PATH . 'sql/sql_update_' . $_POST['db_step'] . '.php';
		if (is_file($file)) {
			$queries = array();
			include($file);
			foreach ($queries as $query) {
				$wpdb->query($query);
			}
			echo '<div class="update-nag" style="display: block;">' . sprintf(__('Updated the Your Members database to Version %s', 'ym'), $_POST['db_step']) . '</div>';
		}
	}
}

if (@$_POST) {
	ym_box_bottom();
	ym_box_top('Reset');
}


echo '<p style="float: right;"><a href="' . YM_ADMIN_URL . '&ym_page=ym-dev-tools&ym_dev=1">Disable Tools</a></p>';

echo '
<form action="" method="post">
	<input type="submit" name="reset_sys" value="Reset YM SYS" />
</form>
<form action="" method="post">
	<input type="submit" name="reset_res" value="Reset YM RES" />
</form>
<form action="" method="post">
	<input type="submit" name="reset_all" value="Reset YM" />
</form>
<br /><br />
<form action="" method="post">
	<input type="submit" name="fidget_patch" value="Convert StdClass ym_user to YM Object with Scalar Uncode" />
</form>
<form action="" method="post">
	<input type="submit" name="kopstad_patch" value="Convert StdClass ym_user to YM Object with String Uncode" />
</form>
<form action="" method="post">
	<input type="submit" name="de_dupe_package_types" value="De-Duplicate Package Types" />
</form>
<form action="" method="post">
	<input type="submit" name="de_dupe_custom_fields" value="De-Duplicate Custom Fields" />
</form>
<br /><br />
<form action="" method="post">
	<label>Option Name <input type="text" name="option_name" id="option_name" ';

if ($name) {
	echo 'value="' . $name . '"';
}

echo '
/></label>
	<label>Option Value <input type="text" name="option_value" id="option_value" /></label>
	<input type="button" id="ym_get_current_value" value="Get Current Value" />
	<input type="submit" name="meta_change" value="Change a wp_option" />
	<div id="option_value_current" style="display: none;">
		<br />
		<div></div>
		<br />
		<textarea rows="10" cols="80"></textarea>
	</div>
</form>

<br /><br />
<form action="" method="post">
	<label for="user_id">ID: <input type="text" name="user_id" id="user_id" ';
	if (@$_POST['user_id']) {
		echo 'value="' . $_POST['user_id'] . '"';
	}
	echo ' /></label>
	<label for="user_meta_name">Name: <input type="text" name="user_meta_name" id="user_meta_name" ';
	if (@$_POST['user_meta_name']) {
		echo 'value="' . $_POST['user_meta_name'] . '"';
	}
	echo ' /></label>
	<label for="user_meta_value">Value: <input type="text" name="user_meta_value" id="user_meta_value" /></label>
	<input type="button" id="ym_get_current_user_value" value="Get Current Value" />
	<input type="submit" name="user_meta_change" value="Change User Meta" />
	<div id="user_option_value_current" style="display: none;">
		<br />
		<div></div>
		<br />
		<textarea rows="10" cols="80"></textarea>
	</div>
</form>

<br /><br />
<form action="" method="post">
	<input type="submit" name="remove_private_tags" value="Remove Private Tags from All Posts" />
</form>
<form action="" method="post">
	<input type="submit" name="build_test_posts" value="Built Test Suite" class="deletelink" />
</form>
<form action="" method="post">
	<input type="text" name="db_step" />
	<input type="submit" name="run_db_step" value="Run a DB Step" class="deletelink" />
</form>
';

echo ym_end_box();
echo '</div>';

echo '
<script type="text/javascript">' . "
	jQuery(document).ready(function() {
		jQuery('#ym_get_current_value').click(function() {
			ym_get_current_value();
		});
		jQuery('#ym_get_current_user_value').click(function() {
			ym_get_current_user_value();
		});
";

		if ($name) {
			echo "
ym_get_current_value();
";
		}
		if (@$_POST['user_meta_name']) {
			echo "
ym_get_current_user_value();
";
		}
		echo "
	});

	function ym_get_current_value() {
		jQuery.get('" . YM_ADMIN_URL . "&ym_page=ym-dev-tools&do_munch=1&option_name=' + jQuery('#option_name').val(), function(data) {
			jQuery('#option_value_current div').html(jQuery('#option_name').val());
			jQuery('#option_value_current textarea').html(data);
			jQuery('#option_value_current').slideDown();
		});
	}
	function ym_get_current_user_value() {
		jQuery.get('" . YM_ADMIN_URL . "&ym_page=ym-dev-tools&do_munch=1&user_meta_name=' + jQuery('#user_meta_name').val() + '&user_id=' + jQuery('#user_id').val(), function(data) {
			jQuery('#user_option_value_current div').html(jQuery('#user_meta_name').val());
			jQuery('#user_option_value_current textarea').html(data);
			jQuery('#user_option_value_current').slideDown();
		});
	}
</script>
";

