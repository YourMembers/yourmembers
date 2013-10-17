<?php

/*
* $Id: ym-admin_group_membership_functions.include.php 2564 2013-01-24 16:58:41Z bcarlyon $
* $Revision: 2564 $
* $Date: 2013-01-24 16:58:41 +0000 (Thu, 24 Jan 2013) $
*/

function ym_group_membership_parent_delete_child($id = FALSE, $message = FALSE) {
	global $ym_user;

	if ($id) {
		$user = get_user_by('id', $id);
		if ($user) {
			$user = new YourMember_User($id);

			// only the parent can delete here
			if ($user->parent_id == $ym_user->ID) {
				$user->parent_id = FALSE;
				$user->account_type = 'guest';
				$user->status = YM_STATUS_PARENT_CANCEL;
				$user->status_str = __('Orpahned', 'ym');
				$user->save();

				unset($ym_user->child_ids[array_search($id, $ym_user->child_ids)]);
				$ym_user->save();

				@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Child Account Orphaned/Deleted from Parent', 'ym'), $id);

				if ($message) {
					ym_display_message(__('Child was Removed', 'ym'));
				}
				return TRUE;
			} else {
				if ($message) {
					ym_display_message(__('You do not have access to this Child', 'ym'), 'error');
				} else {
					return FALSE;
				}
			}
		} else {
			if ($message) {
				ym_display_message(__('Could Not find the Child Specified to Delete', 'ym'), 'error');
			}
			return FALSE;
		}
	} else {
		if ($message) {
			ym_display_message(__('No Child Specified to Delete', 'ym'), 'error');
		}
		return FALSE;
	}
	return FALSE;
}

function ym_group_membership_delete_child_from_parent($child_id, $parent_id) {
	// no auth
	$child = new YourMember_User($child_id);
	$child->parent_id = FALSE;
	$child->save();

	@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Child Account Orphaned/Deleted from Parent', 'ym'), $child_id);

	$parent = new YourMember_User($parent_id);
	unset($parent->child_ids[array_search($child_id, $parent->child_ids)]);
	$parent->save();

	return;
}

// TO THE ADOPTION AGENCY
function ym_group_membership_add_child_to_parent($child_id, $parent_id) {
	$child = new YourMember_User($child_id);
	if ($child->parent_id) {
		// adoption agency
		ym_group_membership_delete_child_from_parent($child_id, $child->parent_id);
	}
	$child->parent_id = $parent_id;
	$child->save();
	unset($child);
	$parent = new YourMember_User($parent_id);
	$parent->child_ids[] = $child_id;
	array_unique($parent->child_ids);
	$parent->save();
	unset($parent);

	@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Child Account Adopted by Parent', 'ym') . ' ' . $parent_id, $child_id);
	@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Parent Account Adopted a Child', 'ym') . ' ' . $child_id, $parent_id);

	return;
}

function ym_group_membership_create_child($email_address, $username, $password, $c_password, $sub_id, $package_type = false, $message = FALSE, $parent_id = FALSE) {
	if ($parent_id) {
		$ym_user = new YourMember_User($parent_id);
	} else {
		global $ym_user;
	}
	$current_counts = ym_group_membership_get_counts($ym_user);

	if (count($ym_user->child_ids) >= $ym_user->child_accounts_allowed) {
		if ($message) {
			ym_display_message(__('You are out of Child Accounts', 'ym'), 'error');
		}
		return FALSE;
	} else {
		if ($email_address && is_email($email_address)) {
			if (!empty($password) && $password != $c_password) {
				ym_display_message(__('Passwords do not match', 'ym'), 'error');
			}

			if ($sub_id) {
				if (!in_array($sub_id, $ym_user->child_accounts_packages)) {
					if ($message) {
						ym_display_message(__('You do not have access to this pacakge', 'ym'), 'error');
					}
					return FALSE;
				}
				$pack = ym_get_pack_by_id($sub_id);
			} else if ($package_type) {
				$pack = array();
				$pack['account_type'] = $package_type;
			} else {
				// inherit mode
				$pack = array();
				$pack['account_type'] = $ym_user->account_type;
			}

			$inherit = true;
			foreach ($ym_user->child_accounts_package_types as $type => $type_count) {
				if ($type_count) {
					$inherit = false;
				}
			}
//			if ($inherit) {
//				$pack['account_type'] = '';
//			}

			if (
(
	$pack['account_type'] && $ym_user->child_accounts_package_types[$pack['account_type']] > $current_counts[$pack['account_type']]
)
||
(
	$inherit && $ym_user->child_accounts_allowed > count($ym_user->child_ids)
)
			) {
				$new_user = new YourMember_User();

				$result = $new_user->create($email_address, $sub_id, FALSE, $username, $password);

				if (is_wp_error($result)) {
					ym_display_message($result->get_error_message(), 'error');
				} else {
					// apply child
					$data = array(
						'parent_id'		=> $ym_user->ID,
						'account_type'	=> $pack['account_type'],
						'status_str'	=> __('Child Account', 'ym'),
					);

					if (!$sub_id) {
						// the child has inherited they won't have a role!
						$new_user->updaterole('subscriber');
					}

					$new_user->update($data);
					$new_user->save();
					unset($new_user);//garbage collect

					$child_ids = $ym_user->child_ids;
					$child_ids[] = $result;
					$ym_user->update(array('child_ids' => $child_ids));
					$ym_user->save();

					@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION, __('Child', 'ym') . ' ' . $data['account_type'], $result);
					@ym_log_transaction(YM_USER_STATUS_UPDATE, YM_STATUS_ACTIVE . ' - ' . $data['status_str'], $result);

					// all done
					if ($message) {
						ym_display_message(__('Child User was created successfully', 'ym'));
					}
					return TRUE;
				}
			} else {
				if ($message) {
					ym_display_message(__('Total for this package type has been reached', 'ym'), 'error');
				}
				return FALSE;
			}
		} else {
			if ($message) {
				ym_display_message(__('The Email Address was Blank or Invalid', 'ym'), 'error');
			}
			return FALSE;
		}
	}
}

function ym_group_membership_get_counts($user = FALSE) {
	if ($user) {
		$ym_user = $user;
	} else {
		global $ym_user;
	}

	$current_counts = array();
	foreach ($ym_user->child_ids as $id) {
		// check orphaned
		$child_user = new YourMember_User($id);
		if (!$child_user->valid) {
			// missing user
			unset($ym_user->child_ids[array_search($id, $ym_user->child_ids)]);
			$ym_user->save();
		} else {
			$package_type = ym_get_user_package_type($id);
			if (isset($current_counts[$package_type])) {
				$current_counts[$package_type]++;
			} else {
				$current_counts[$package_type] = 1;
			}
		}
	}
	global $ym_package_types;
	foreach ($ym_package_types->types as $package_type) {
		if (!isset($current_counts[$package_type])) {
			$current_counts[$package_type] = 0;
		}
	}

	return $current_counts;
}

