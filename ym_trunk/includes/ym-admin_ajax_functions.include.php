<?php

/*
* $Id: ym-admin_ajax_functions.include.php 2551 2013-01-21 16:02:54Z bcarlyon $
* $Revision: 2551 $
* $Date: 2013-01-21 16:02:54 +0000 (Mon, 21 Jan 2013) $
*/

/**
Functions
*/
function ym_ajax_superuser_check($user_id = FALSE) {
	if (!$user_id) {
		global $current_user;
		get_currentuserinfo();
		$user_id = $current_user->ID;
	}
	if (!ym_superuser($user_id)) {
		echo '-';
		die();
	}
}

/**
Logs
*/

add_action( 'wp_ajax_ym_logs_search_users', 'ym_logs_search_users' );
function ym_logs_search_users() {
	ym_ajax_superuser_check();
	$users = get_users('search=*' . like_escape(ym_get('q')) . '*');
	foreach ($users as $user) {
		echo $user->user_login . "\n";
	}
	die();
}

/**
GROUP MEMBERSHIP
*/

add_action('wp_ajax_ym_child_account_toggle', 'ym_group_membership_ym_child_account_toggle');
function ym_group_membership_ym_child_account_toggle() {
	global $current_user;
	get_currentuserinfo();
	$parent_id = $current_user->ID;
	$child_id = $_POST['child_id'];

	$user = new YourMember_User($child_id);
	// check parent can access child
	if (!ym_superuser($parent_id) && $user->parent_id != $parent_id) {
		echo '0';
		die();
	}

	if ($user->status == YM_STATUS_PARENT_CANCEL) {
		$user->update(array('status' => YM_STATUS_ACTIVE));
		$return = __('Active', 'ym');
	} else if ($user->status == YM_STATUS_ACTIVE) {
		$user->update(array('status' => YM_STATUS_PARENT_CANCEL));
		$return = __('Inactive', 'ym');
	} else {
		// no can touch really, but we will
		$user->update(array('status' => YM_STATUS_ACTIVE));
		$return = __('Active', 'ym');
	}
	$user->save();

	echo $return;
	die();
}

add_action('wp_ajax_ym_child_package_type_change', 'ym_group_membership_ym_child_package_type_change');
function ym_group_membership_ym_child_package_type_change() {
	global $current_user;
	get_currentuserinfo();
	$parent_id = $current_user->ID;
	$child_id = $_POST['child_id'];

	$user = new YourMember_User($child_id);
	// check parent can access child
	if (!ym_superuser($parent_id)) {// && $user->parent_id != $parent_id) {
		echo '0';
		die();
	}

	if ($_POST['package_type'] == 'inherit') {
		$_POST['package_type'] = '';
	}

	$user->update(array('account_type' => $_POST['package_type']));
	$user->save();

	$return = __('Updated', 'ym');

	echo $return;
	die();
}

add_action('wp_ajax_ym_child_package_pack_apply', 'ym_child_package_pack_apply');
function ym_child_package_pack_apply() {
	global $current_user;
	get_currentuserinfo();
	$parent_id = $current_user->ID;
	$child_id = $_POST['child_id'];

	$user = new YourMember_User($child_id);
	// check parent can access child
	if (!ym_superuser($parent_id)) {// && $user->parent_id != $parent_id) {
		echo '0';
		die();
	}

	if ($_POST['package_id'] == '-') {
		echo '0';
		die();
	}

	ym_group_apply_package($_POST['package_id'], $child_id);

	echo '1';
	die();
}

add_action('wp_ajax_ym_parent_child_accounts_package_types', 'ym_parent_child_accounts_package_types');
function ym_parent_child_accounts_package_types() {
	global $current_user;
	get_currentuserinfo();
	$user_id = $current_user->ID;

	if (!ym_superuser($user_id)) {
		echo '0';
		die();
	}

	$parent_id = $_POST['parent_id'];
	$package_type = $_POST['package_type'];
	$package_type_amount = ym_post('package_type_amount', '0');

	$parent = new YourMember_User($parent_id);
	$parent->child_accounts_package_types[$package_type] = $package_type_amount;
	$parent->save();

	echo '(' . $package_type_amount . ') ' . $package_type;
	die();
}

add_action('wp_ajax_ym_parent_child_accounts_packages', 'ym_parent_child_accounts_packages');
function ym_parent_child_accounts_packages() {
	global $current_user;
	get_currentuserinfo();
	$user_id = $current_user->ID;

	if (!ym_superuser($user_id)) {
		echo '0';
		die();
	}

	$parent_id = $_POST['parent_id'];
	$package_id = $_POST['package_id'];

	$parent = new YourMember_User($parent_id);
	if (in_array($package_id, $parent->child_accounts_packages)) {
		unset($parent->child_accounts_packages[array_search($package_id, $parent->child_accounts_packages)]);
	} else {
		$parent->child_accounts_packages[] = $package_id;
	}
	$parent->save();

	echo 1;
	die();
}

/**
Members Management
*/

add_action('wp_ajax_ym_quick_activate_toggle', 'wp_ajax_ym_quick_activate_toggle');
function wp_ajax_ym_quick_activate_toggle() {
	ym_ajax_superuser_check();

	$user_id = ym_post('ym_quick_activate_toggle_user_id');
	if ($user_id) {
		$user = new YourMember_User($user_id);
		$target_status = YM_STATUS_NULL;
		$str = __('Suspended', 'ym');
		if ($user->status == $target_status) {
			$target_status = YM_STATUS_ACTIVE;
			$str = __('Manual Update', 'ym');
		}
		$user->update(array(
			'status' => $target_status,
			'status_str' => $str,
		), TRUE);
		echo '
<script type="text/javascript">
jQuery(\'.ym_user_status_' . $user_id . '\').html(\'' . $target_status . '<br />' . $str . '\');
</script>
';
	} else {
		echo 0;
	}

	die();
}

add_action('wp_ajax_ym_quick_delete', 'wp_ajax_ym_quick_delete');
function wp_ajax_ym_quick_delete() {
	ym_ajax_superuser_check();
	$user_id = ym_post('ym_quick_delete_user_id');
	if ($user_id) {
		$ym_user = new YourMember_User($user_id);
		if ($ym_user->parent_id) {
			// is it a child, remove from parent
			ym_group_membership_delete_child_from_parent($user_id, $ym_user->parent_id);
		} else if ($ym_user->child_ids) {
			// is it a parent?
			foreach ($ym_user->child_ids as $child_id) {
				// orpanise all kids
				ym_group_membership_delete_child_from_parent($child_id, $user_id);
			}
		}
		wp_delete_user($user_id);

		echo '
<script type="text/javascript">
jQuery(\'.ym_user_status_' . $user_id . '\').parents(\'tr\').slideUp(function() {
	jQuery(this).remove();
});
</script>
';
	} else {
		echo 'N';
	}

	die();
}

add_action('wp_ajax_ym_quick_orphan', 'wp_ajax_ym_quick_orphan');
function wp_ajax_ym_quick_orphan() {
	ym_ajax_superuser_check();
	$user_id = ym_post('ym_quick_orphan_user_id');
	if ($user_id) {
		$ym_user = new YourMember_User($user_id);
		if ($ym_user->parent_id) {
			ym_group_membership_delete_child_from_parent($user_id, $ym_user->parent_id);
			echo '
<script type="text/javascript">
jQuery(\'.ym_user_orphan_' . $user_id . '\').parents(\'tr\').slideUp(function() {
	jQuery(this).remove();
});
</script>
';
			die();
		}
	}
	echo 'N';
	die();
}

/**
Crom Jobs
*/
add_action('wp_ajax_ym_cron_block_toggle', 'wp_ajax_ym_cron_block_toggle');
function wp_ajax_ym_cron_block_toggle() {
	ym_ajax_superuser_check();

	$blocked = get_option('ym_cron_block_' . $_POST['task']);
	if ($blocked) {
		delete_option('ym_cron_block_' . $_POST['task']);
	} else {
		update_option('ym_cron_block_' . $_POST['task'], 1);
	}
	echo '1';
	die();
}

add_action('wp_ajax_ym_cron_reschedule_job', 'wp_ajax_ym_cron_reschedule_job');
function wp_ajax_ym_cron_reschedule_job() {
	ym_ajax_superuser_check();

	$time = array(
		$_POST['new_hour'],
		$_POST['new_min'],
	);
	update_option('ym_cron_alttime_' . $_POST['task'], $time);
	update_option('ym_cron_altschedule_' . $_POST['task'], $_POST['new_schedule']);

	echo '
<meta http-equiv="refresh" content="0;' . YM_ADMIN_URL . '&ym_page=ym-advanced-cron&reload=1" />
';
	die();
}
