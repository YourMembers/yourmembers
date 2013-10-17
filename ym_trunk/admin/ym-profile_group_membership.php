<?php

/*
* $Id: ym-profile_group_membership.php 2547 2013-01-21 14:42:16Z bcarlyon $
* $Revision: 2547 $
* $Date: 2013-01-21 14:42:16 +0000 (Mon, 21 Jan 2013) $
*/

global $ym_user, $ym_formgen;

// get breakdown
$current_counts = ym_group_membership_get_counts();
$allowed_counts = array();

if ($_POST) {
	$action = ym_post('action', false);
	if ($action == 'deletechildaccount') {
		$id = ym_post('child_id', false);
		if (ym_group_membership_parent_delete_child($id, TRUE)) {
			// reload
			$ym_user = new YourMember_User($ym_user->ID);
		}
	} else if ($action == 'create_child_account') {
		$email_address = ym_post('email_address');
		$username = ym_post('username', $email_address);
		$password = ym_post('password');
		$c_password = ym_post('c_password');

		$sub_id = ym_post('package', false);
		$package_type = ym_post('package_type', false);

		if (ym_group_membership_create_child($email_address, $username, $password, $c_password, $sub_id, $package_type, TRUE)) {
			// reload
			$ym_user = new YourMember_User($ym_user->ID);
		}
	} else {
		ym_display_message(__('Unknown Action Requested', 'ym'), 'error');
	}
}

// get breakdown
$current_counts = ym_group_membership_get_counts();

echo '<div id="poststuff" class="wrap">
	<h2>' . __('Group Membership', 'ym') . '</h2>';

ym_box_top('&nbsp;');

echo '<p>' . sprintf(__('You are eligible to have %s account%s in your Group, you currently have %s Child Account%s', 'ym'), $ym_user->child_accounts_allowed, ($ym_user->child_accounts_allowed != 1 ? 's' : ''), count($ym_user->child_ids), (count($ym_user->child_ids) != 1 ? 's' : '')) . '</p>';

echo '
<table class="form-table widefat">';

if (count($ym_user->child_ids)) {
	echo '<tr>
		<th>' . __('User Email', 'ym') . '</th>
		<th>' . __('User Login', 'ym') . '</th>
		<th>' . __('Package Type', 'ym') . '</th>
		<th>' . __('Status, click to Toggle', 'ym') . '</th>
		<th>' . __('Delete', 'ym') . '</th>
	</tr>';
	foreach ($ym_user->child_ids as $child) {
		echo '<tr>';

		$child = get_user_by('id', $child);
		if ($child) {
			$ym_child = new YourMember_User($child->ID);

			echo '<td><a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=user-edit&amp;user_id=' . $child->ID . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">';
			echo $child->user_email;
			echo '</a></td>';
			echo '<td>' . $child->user_login . '</td>';
			echo '<td>' . $ym_child->account_type . '</td>';
			echo '<td><form action="" method="post" class="ym_ajax_call">
				<input type="hidden" name="action" value="ym_child_account_toggle" />
				<input type="hidden" name="child_id" value="' . $child->ID . '" />
				<a href="" class="ym_form_submit">';
				if ($ym_child->status == YM_STATUS_PARENT_CANCEL) {
					_e('Inactive', 'ym');
				} else if ($ym_child->status == YM_STATUS_ACTIVE) {
					_e('Active', 'ym');
				} else {
					_e('Active', 'ym');
				}
				echo '</a>
			</form></td>';

			echo '<td><form action="" method="post">
				<input type="hidden" name="action" value="deletechildaccount" />
				<input type="hidden" name="child_id" value="' . $child->ID . '" />
				<input type="submit" value="' . __('Delete', 'ym') . '" class="deletelink" />
			</form></td>';
		}

		echo '</tr>';
	}
} else {
	echo '<tr><td><p>' . __('You have no Child accounts', 'ym') . '</p></td>';
}

// build
$package_type_options = array();
// package type
foreach ($ym_user->child_accounts_package_types as $package_type => $count) {
	$allowed_counts[$package_type] = $count;
	if ($current_counts[$package_type] < $count) {
		$package_type_options[$package_type] = $package_type;
	}
}

if (count($ym_user->child_ids) < $ym_user->child_accounts_allowed) {
	echo '
<tr><td colspan="8"><h4>' . __('Add New Group Account', 'ym') . '</h4></td></tr><tr><td colspan="8">
<form action="" method="post">
	<input type="hidden" name="action" value="create_child_account" />
<table class="form-table">
';

	$ym_formgen->render_form_table_email_row(__('Email Address', 'ym'), 'email_address');
	$ym_formgen->render_form_table_text_row(__('Username', 'ym'), 'username', '', __('Leave blank to use the email address', 'ym'));
	$ym_formgen->render_form_table_password_row(__('Password', 'ym'), 'password', '', __('Leave blank to auto generate', 'ym'));
	$ym_formgen->render_form_table_password_row(__('Confirm Password', 'ym'), 'c_password');

	if (count($package_type_options)) {
		array_unshift($package_type_options, __('Select', 'ym'));
		$ym_formgen->render_combo_from_array_row(__('Package Type', 'ym'), 'package_type', $package_type_options);
	}

	$options = array();
	// package
	if (count($ym_user->child_accounts_packages)) {
		foreach ($ym_user->child_accounts_packages as $id) {
			$pack = ym_get_pack_by_id($id);
			if ($current_counts[$pack['account_type']] < $allowed_counts[$pack['account_type']]) {
				$options[$id] = ym_get_pack_label($id);
			}
		}
	}
	if (count($options)) {
		$options[0] = __('Select', 'ym');
		ksort($options);
		$label = __('Apply A Package', 'ym');
		if (count($ym_user->child_accounts_packages)) {
			$label = __('Or', 'ym') . ' ' . $label;
		}
		$ym_formgen->render_combo_from_array_row($label, 'package', $options);
	}

	echo '<tr><td colspan="2"><p class="submit"><input type="submit" class="button-primary alignright" value="' . __('Create', 'ym') . '" /></p></td></tr>';
	echo '</table></form></td></tr>';
}

echo '</table>';

ym_box_bottom();

echo '</div>';
