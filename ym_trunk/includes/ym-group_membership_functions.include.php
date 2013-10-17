<?php

/*
* $Id: ym-group_membership_functions.include.php 2612 2013-03-13 09:30:27Z tnash $
* $Revision: 2612 $
* $Date: 2013-03-13 09:30:27 +0000 (Wed, 13 Mar 2013) $
*/

/**
Group Membership Admin Permissions
*/
add_filter('user_has_cap', 'ym_group_membership_permissions', 10, 3);
function ym_group_membership_permissions($allcaps, $caps, $args) {
	if ($args[0] == 'edit_user') {
		global $ym_user;
		// check if the id of the user beung editing is in the child ids of the logged in user
		if (in_array($args[2], $ym_user->child_ids)) {
			$allcaps['edit_users'] = $args[2];		
		}
	}
	return $allcaps;
}

/**
Functions
*/
function ym_group_apply_package($pack_id, $user_id = FALSE) {
	$vars_to_apply = array(
		'role',
		'account_type',
		'hide_old_content',
		'child_accounts_allowed',
		'child_accounts_package_types',
		'child_accounts_packages',
		'hide_admin_bar',
	);

	$pack = ym_get_pack_by_id($pack_id);

	foreach ($vars_to_apply as $value) {
		$vars_to_apply[$value] = $pack[$value];
	}

	if ($user_id) {
		$ym_user = new YourMember_User($user_id);
	} else {
		global $ym_user;
	}
	$ym_user->update($vars_to_apply);
	$ym_user->save();

	@ym_log_transaction(YM_PACKAGE_PURCHASED,  $pack_id, $user_id);

	return;
}

/**
Shortcodes
Front End Control
*/
if (!is_admin()) {
	add_shortcode('ym_group_membership_control', 'ym_shortcode_ym_group_membership_control');
}
function ym_shortcode_ym_group_membership_control() {
	// @TODO: Finish
	global $ym_user, $ym_formgen;

	
	if ($ym_user->child_ids || $ym_user->child_accounts_allowed) {
		// has children
		$total_kids = count($ym_user->child_ids);

		$action = ym_post('action', false);
		if ($action == 'ym_add_child_user') {
			if ($ym_user->child_accounts_allowed > $total_kids) {
				$email_address = ym_post('email_address');
				$username = ym_post('username', $email_address);
				$password = ym_post('password');
				$c_password = ym_post('c_password');

				if ($email_address && is_email($email_address)) {
					if (!empty($password) && $password != $c_password) {
						ym_display_message(__('Passwords do not match', 'ym'), 'error');
					}
					$new_user = new YourMember_User();
					$result = $new_user->create($email_address, false, false, $username, $password);

					if (is_wp_error($result)) {
						ym_display_message($result->get_error_message(), 'error');
					} else {
						// apply child

						$data = array(
							'parent_id'	=> $ym_user->ID
						);
						// package type
						if (count($ym_user->child_accounts_package_types) > 1) {
							$data['account_type'] = $ym_user->child_accounts_package_types[0];
						} else {
							$data['account_type'] = $ym_user->account_type;
						}

						$new_user->update($data);
						$new_user->save();
						unset($new_user);//garbage collect

						$child_ids = $ym_user->child_ids;
						$child_ids[] = $result;
						$ym_user->update(array('child_ids' => $child_ids));
						$ym_user->save();

						// all done
						ym_display_message(__('Child User was created successfully', 'ym'));
					}
				} else {
					ym_display_message(__('The Email Address was Blank or Invalid', 'ym'), 'error');
				}
			} else {
				ym_display_message(__('You have reached the maximum number of accounts', 'ym'), 'error');
			}
		} else if ($action == 'ym_child_package_type_change') {
			$child_id = ym_post('child_id', false);

			if ($child_id) {
				$ym_child = new YourMember_User($child_id);
				if ($ym_child->parent_id = $ym_user->ID) {
					$ym_child->update(array('account_type' => $_POST['package_type']));
					$ym_child->save();
					ym_display_message(__('Child account was updated successfully', 'ym'));
				} else {
					ym_display_message(__('You are trying to update someone elses child', 'ym'), 'error');
				}
			}
		}

		$return .= '<table class="form-table">';
		foreach ($ym_user->child_ids as $child) {
			// loop thru kids
			$ym_child = new YourMember_User($child);

			$return .= '<tr>';
			$return .= '<td>' . $ym_child->data->user_login . '</td>';
			$return .= '<td>';

			$return .= $ym_child->account_type;

			$return .= '</td>';
			$return .= '</tr>';
		}
		$return .= '</table>';

		if ($ym_user->child_accounts_allowed > $total_kids) {
			// can add child
			$return .= '<h4>' . __('Create new Group Account', 'ym') . '</h4>';
			$return .= '<form action="" method="post">
	<input type="hidden" name="action" value="ym_add_child_user" />
<table class="form-table">
';

			$ym_formgen->return = true;
			$return .= $ym_formgen->render_form_table_email_row(__('Email Address', 'ym'), 'email_address');
			$return .= $ym_formgen->render_form_table_text_row(__('Username', 'ym'), 'username', '', __('Leave blank to use the email address', 'ym'));
			$return .= $ym_formgen->render_form_table_password_row(__('Password', 'ym'), 'password', '', __('Leave blank to auto generate', 'ym'));
			$return .= $ym_formgen->render_form_table_password_row(__('Confirm Password', 'ym'), 'c_password');
			$ym_formgen->return = false;

			$return .= '<tr><td colspan="2"><p class="submit"><input type="submit" class="button-primary alignright" value="' . __('Create', 'ym') . '" /></p></td></tr>';
			$return .= '</table></form>';
		}
		return $return;
	} else {
		return '<p>' . __('You do not have access to Group Management', 'ym') . '</p>';
	}
}
