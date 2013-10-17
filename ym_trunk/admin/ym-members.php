<?php

/*
* $Id: ym-members.php 2602 2013-02-18 15:42:30Z tnash $
* $Revision: 2602 $
* $Date: 2013-02-18 15:42:30 +0000 (Mon, 18 Feb 2013) $
*/

wp_enqueue_script('ym_admin_js_members', YM_JS_DIR_URL . 'ym_admin_members.js', array('jquery'), YM_PLUGIN_VERSION);

global $wpdb, $ym_res;

get_currentuserinfo();
global $current_user;
$admin_user = $current_user;

$screen = get_current_screen();
$screen->add_help_tab(array(
	'id' => $_GET['ym_page'],
	'title' => __('Icon Key', 'ym'),
	'content' => '
<ul>
<li style="list-style: none;"><span class="ym_accept"></span> - ' . __('User has access, click to toggle', 'ym') . '</li>
<li style="list-style: none;"><span class="ym_cancel"></span> - ' . __('User is blocked, click to toggle', 'ym') . '</li>
<li style="list-style: none;"><span class="ym_node_tree"></span> - ' . __('Review User Logs', 'ym') . '</li>
<li style="list-style: none;"><span class="ym_user_go"></span> - ' . __('Orphan Child from parent account', 'ym') . '</li>
<li style="list-style: none;"><span class="ym_user_delete"></span> - ' . __('Completely delete account', 'ym') . '</li>
</ul>
'
));

global $current_screen;
set_current_screen();
$current_screen->render_screen_meta();

echo '<div id="message" class="error ym_ajax_error"><p>' . __('Ajax Failed, please reload the page', 'ym') . '</p></div>';
echo '<hr style="height: 5px; border: 0px;" />';
echo '<div class="wrap" id="poststuff">';

$page = ym_request('ym_current_page', 0);

global $ym_members_tasks;
$ym_members_tasks = array(
	'delete'			=> __('Delete Selected Users', 'ym'),
	'suspend'			=> __('Suspend Selected Users', 'ym'),
	'update'			=> __('Update Selected Users', 'ym'),
	'change_limit'		=> __('Change Page Limit', 'ym'),

	'change_filters'	=> __('Change Filters', 'ym'),
	'change_order'		=> __('Change Sort', 'ym'),

	'create_user'		=> __('Create User', 'ym'),

	'forward_a_page'	=> __('Forward a Page', 'ym'),
	'back_a_page'		=> __('Back a Page', 'ym'),
);

$task = ym_post('task');

if ($_POST) {
	if(ym_post('ym_start_import')) {
		// import
	} else if (ym_post('ym_start_xls_backup')) {
		// export
		if (ym_export_users_start(ym_post('bkformat', 'xls'))) {

			ym_box_top(__('Building Output', 'ym'));
			echo '<iframe src="" style="height: 100px; width: 100%" name="ym_exporting_users_frame"></iframe>';
			ym_export_users_operation_form(trailingslashit(ym_post('backup_temp_path')), 0);
			ym_box_bottom();

			return;
		} else {
			ym_display_message(__('Could not Start Export', 'ym'), 'error');
		}
	} else if (!wp_verify_nonce(ym_post('ym-members'), 'ymmembertasks')) {
		echo '<div id="message" class="error"><p>' . __('Nonce Failed Verification', 'ym') . '</p></div>';
	} else {
		if (!$task) {
			echo '<div id="message" class="error"><p>' . __('Task Not Sent', 'ym') . '</p></div>';
		} else if (!in_array($task, $ym_members_tasks)) {
			echo '<div id="message" class="error"><p>' . sprintf(__('Task (%s) Not Permitted', 'ym'), $task) . '</p></div>';
		} else {
			$users_to_do = ym_post('ps', array());

			$task = array_search($task, $ym_members_tasks);
			$results_count = 0;

			require_once(ABSPATH . 'wp-admin/includes/user.php');

			$package_apply = ym_post('apply_package_apply');
			$package_apply_skip_trial = ym_post('apply_package_skip_trial');

			$package_type = ym_post('package_type');
			$trial_on = ym_post('trial_on');
			$trial_taken = ym_post('trial_taken');
			$expire_date = ym_post('expire_date');
			$status = ym_post('status');
			$hide_old_content = ym_post('hide_old_content');

			$parent_id = ym_post('parent_id', false);
			$child_accounts_allowed = ym_post('child_accounts_allowed');
			$hide_admin_bar = ym_post('hide_admin_bar');

			$result_message = '';

			if (count($users_to_do)) {
				// a multi task
				foreach ($users_to_do as $user_id) {
					switch($task) {
						case 'delete':
							wp_delete_user($user_id);
							$results_count ++;
							$result_message = __('%s Users Deleted', 'ym');
							break;
						case 'suspend':
							$ym_updating_a_user = new YourMember_User($user_id);
							$data = array(
								'status'		=> YM_STATUS_NULL,
								'status_str'	=> __('Suspended', 'ym')
							);
							$ym_updating_a_user->update($data, TRUE);
							$results_count++;
							$result_message = __('%s Users Suspended', 'ym');
							break;
						case 'update':
							$ym_updating_a_user = new YourMember_User($user_id);
							if ($package_apply) {
								// gifting
								if ($package_apply_skip_trial) {
									$data['trial_taken'] = $package_apply;
									$ym_updating_a_user->update($data);
									$ym_updating_a_user->save();
								}

								if ($package_apply == '-') {
									// package Removal
									$data['pack_id'] = '';
									$ym_updating_a_user->update($data);
									$ym_updating_a_user->save();
								} else {
									$pay = new ym_payment_gateway();
									$pay->code = 'ym_gift';
									$pay->name = 'ym_gift';
									$nomore_email = ym_post('apply_package_gift_notify', FALSE);
									$nomore_email = $nomore_email ? FALSE : TRUE;//invert
									$pay->nomore_email = $nomore_email;

									$pay->do_buy_subscription($package_apply, $user_id, TRUE);
								}
							} else {
								$data = array();
								if ($package_type) {
									$data['account_type'] = ($package_type == '-') ? '' : $package_type;
									@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION, $data['account_type'], $user_id);
									if ($data['account_type'] != $ym_updating_a_user->account_type) {
										$data['account_type_join_date'] = time();
									}
								}
								if (strlen($trial_on)) {
									$data['trial_on'] = $trial_on;
								}
								if ($trial_taken) {
									$data['trial_taken'] = ($trial_taken == '-') ? '' : $trial_taken;
								}
								if ($expire_date) {
									$data['expire_date'] = strtotime(str_replace('/', '-', $expire_date));
									@ym_log_transaction(YM_ACCESS_EXTENSION, $data['expire_date'], $user_id);
								}
								if ($status) {
									$data['status'] = $status;
								}
								if ($hide_old_content) {
									$data['hide_old_content'] = $hide_old_content;
								}
								if ($parent_id) {
									if ($parent_id == 'Leave Alone') {
										$data['parent_id'] = '';
									} else {
										$user = get_user_by('login', $parent_id);
										$data['parent_id'] = $user->ID;
									}
								}
								if ($child_accounts_allowed) {
									$data['child_accounts_allowed'] = $child_accounts_allowed;
								}
								if (!empty($hide_admin_bar)) {
									$data['hide_admin_bar'] = $hide_admin_bar;
								}
								$ym_updating_a_user->update($data, TRUE);
							}
							$results_count++;
							break;
						default:
							echo '<div id="message" class="error"><p>' . __('Multi Task Error: No Tasks', 'ym') . '</p></div>';
					}
				}
			} else {
				// single tasks
				switch ($task) {
					case 'change_limit':
						update_option('ym_admin_ym_members_page_limit', ym_post('ym_admin_ym_members_page_limit', 50));
						$result_message = __('Updated Page Limit', 'ym');
						$page = 0;//reset the page counter
						break;

					case 'create_user':
						$username = ym_post('username');
						$email = ym_post('email');
						$password = ym_post('password', '');
						$password_confirm = ym_post('password_confirm', '');
						$smflag = ym_post('smflag', FALSE);

						if (!empty($password) && $password != $password_confirm) {
							// mismatch
							echo '<div id="message" class="error"><p>' . __('Password Do Not Match', 'ym') . '</p></div>';
						} else {
							$package = ym_post('package');
							$package_type = ym_post('package_type');
							$status = ym_post('status');

							if ($package) {
								$new_user = new YourMember_User();
								$result = $new_user->create($email, $package, $smflag, $username, $password);
								if (is_wp_error($result)) {
									// error
									ym_display_message($result->get_error_message(), 'error');
								} else {
									// reload
									$new_user = new YourMember_User($result);
									// ok
									$new_user->update(array('status_str' => __('User Create: Applied', 'ym')), TRUE);
									$result_message = sprintf(__('User Created, ID: %s', 'ym'), $result);
								}
							} else {
								$new_user = new YourMember_User();
								$result = $new_user->create($email, false, $smflag, $username, $password);
								if (is_wp_error($result)) {
									// error
									ym_display_message($result->get_error_message(), 'error');
								} else {
									// ok apply stuff
									$new_user->update(array(
										'account_type'		=> $package_type,
										'status'			=> $status,
										'status_str'		=> __('User Create: Applied', 'ym')
									), TRUE);
									$result_message = sprintf(__('User Created, ID: %s', 'ym'), $result);
								}
							}
						}
						if (!$result_message) {
							break;
						}
						$_POST['filter_by_option'] = '';

					case 'change_filters':
					case 'change_order':
						// load filters
						$filters = get_option('ym_admin_ym_members_filters', array());
						$filters['by_option'] = isset($filters['by_option']) ? $filters['by_option'] : '';
						$filters['by_text'] = isset($filters['by_text']) ? $filters['by_text'] : '';

						$filters['order_by'] = isset($filters['order_by']) ? $filters['order_by'] : 'login';
						$filters['order_by_direction'] = isset($filters['order_by_direction']) ? $filters['order_by_direction'] : 'ASC';

						$option = ym_post('filter_by_option', $filters['by_option']);

						if (!isset($_POST['filter_by_text_' . $option])) {
							$text = ym_post('filter_by_text', $filters['by_text']);
						} else {
							$text = ym_post('filter_by_text_' . $option, $filters['by_text']);
						}

						$filters = array(
							'by_option'		=> $option, 
							'by_text'		=> $text,
							'cf_field'		=> '',
						);

						if ($option == 'custom_field') {
							$filters['cf_field'] = ym_post('filter_by_text_custom_field', '');
							$filters['by_text'] = ym_post('filter_by_text', '');
						}

						$filters['order_by'] = ym_post('filter_by_order_by', 'login');
						$filters['order_by_direction'] = ym_post('filter_by_order_by_direction', 'ASC');

						update_option('ym_admin_ym_members_filters', $filters);
						$result_message = $result_message ? $result_message : __('Updated Filters', 'ym');

						$page = 0;//reset the page counter
						break;
					case 'forward_a_page':
					case 'back_a_page':
						break;
					default:
						echo '<div id="message" class="error"><p>' . __('Single Task Error: No Tasks', 'ym') . '</p></div>';
				}
			}

			if ($results_count > 0) {
				$result_message = $result_message ? $result_message : __('%s Users Updated', 'ym');
				$result_message = sprintf($result_message, $results_count);
			}
			if ($result_message) {
				echo ym_display_message($result_message);
			}
		}
	}
}

// security alert
wp_set_current_user($admin_user->ID);

$ym_admin_ym_members_page_limit = ym_request('ym_page_limit', get_option('ym_admin_ym_members_page_limit', 50));

// load filters
$filters = get_option('ym_admin_ym_members_filters', array());
$filters['by_option'] = isset($filters['by_option']) ? $filters['by_option'] : '';
$filters['by_text'] = isset($filters['by_text']) ? $filters['by_text'] : '';
$filters['order_by'] = isset($filters['order_by']) ? $filters['order_by'] : 'login';
$filters['order_by_direction'] = isset($filters['order_by_direction']) ? $filters['order_by_direction'] : 'ASC';

if ($task == 'forward_a_page') {
	$page++;
} else if ($task == 'back_a_page' && $page > 0) {
	$page--;
}

$offset = $page * $ym_admin_ym_members_page_limit;

$args = array(
	'number'	=> $ym_admin_ym_members_page_limit,
	'offset'	=> $offset,
	'exclude'	=> array($admin_user->ID, 1),
	'orderby'	=> str_replace('exposed_', '', $filters['order_by']),
	'order'		=> $filters['order_by_direction'],
);

$query = '';
$query_sort = '';
$query_limit = ' LIMIT ' . $offset . ',' . $ym_admin_ym_members_page_limit;

if (FALSE === (strpos('exposed_', $filters['order_by']))) {
	$query_sort = ' ORDER BY user_' . $filters['order_by'] . ' ' . $filters['order_by_direction'];
} else {
	$query_sort = ' ORDER BY ' . str_replace('exposed_', '', $filters['order_by']) . ' ' . $filters['order_by_direction'];
}

if ($filters['by_option']) {
	switch ($filters['by_option']) {
		case 'username':
			$args = FALSE;
			$query = 'SELECT * FROM ' . $wpdb->users . ' WHERE ID != 1 AND user_login LIKE \'' . str_replace('*', '%', $filters['by_text']) . '\' ';
			break;
		case 'user_email':
			$args = FALSE;
			$query = 'SELECT * FROM ' . $wpdb->users . ' WHERE ID != 1 AND user_email LIKE \'' . str_replace('*', '%', $filters['by_text']) . '\' ';
			break;
		case 'package':
			$by_text = str_replace('*', '%', $filters['by_text']);
			if ($filters['by_text'] == 'none') {
				$by_text = '';
			}
			$args = FALSE;
			$query = 'SELECT * FROM ' . $wpdb->users . ' u '
				. 'LEFT JOIN ' . $wpdb->usermeta . ' um ON um.user_id = u.ID '
				. 'WHERE ID != 1 AND '
				. 'um.meta_key = \'ym_user\' AND um.meta_value LIKE "%'
				. mysql_real_escape_string('s:7:"pack_id";s:' . strlen($by_text) . ':"' . $by_text . '";')
				. '%" ';

			// tweak sort just in case
			$query_sort = ' ORDER BY u.' . $filters['order_by'] . ' ' . $filters['order_by_direction'];
			break;
		case 'package_type':
			$args['meta_key'] = 'ym_account_type';
			if ($filters['by_text'] == 'none') {
				$args['meta_value'] = '';
			} else {
				$args['meta_value'] = str_replace('*', '%', $filters['by_text']);
			}
			break;

		case 'custom_field':
			if (FALSE !== strpos($filters['by_text'], '*')) {
				$meta_value = 'i:' . $filters['cf_field'] . ';s:%:"' . str_replace('*', '%', $filters['by_text']) . '";';
			} else {
				$meta_value = 'i:' . $filters['cf_field'] . ';s:' . strlen($filters['by_text']) . ':"' . $filters['by_text'] . '";';
			}

			$by_text = $filters['by_text'];
			if ($filters['by_text'] == 'none') {
				$by_text = '';
			}
			$args = FALSE;
			$query = 'SELECT * FROM ' . $wpdb->users . ' u '
				. 'LEFT JOIN ' . $wpdb->usermeta . ' um ON um.user_id = u.ID '
				. 'WHERE ID != 1 AND '
				. 'um.meta_key = \'ym_custom_fields\' AND um.meta_value LIKE "%'
				. mysql_real_escape_string($meta_value)
				. '%" ';

			// tweak sort just in case
			$query_sort = ' ORDER BY u.' . $filters['order_by'] . ' ' . $filters['order_by_direction'];

			break;

		case 'status':
			$args['meta_key'] = 'ym_status';
			if ($filters['by_text'] == 'none') {
				$args['meta_value'] = '';
			} else {
				$args['meta_value'] = str_replace('*', '%', $filters['by_text']);
			}
			break;

		default:
			if (substr($args['meta_key'], 0, 8) == 'exposed_') {
				$args['meta_key'] = str_replace('exposed_', '', $filters['order_by']);
				$args['meta_value'] = str_replace('*', '%', $filters['by_text']);
			}
	}
}

if ($args) {
	$users = get_users($args);
} else {
	// query
	$users = $wpdb->get_results($query . $query_sort . $query_limit);
}
$count = count($users);

global $status_str, $ym_formgen, $ym_packs, $ym_package_types;

echo '<form action="" method="post">';
wp_nonce_field('ymmembertasks', 'ym-members');

//Adding Sort options
//ym_members_sort($filters);
//ym_members_filters($filters);

if ($users) {
	echo '<table class="widefat">
	<thead>
';

// pagination buttons
	echo '<tr>
		<td colspan="11" style="text-align: center;">';
		if ($page != 0) {
			echo '<input type="submit" class="button-secondary" name="task" value="' . __('Back a Page', 'ym') . '" style="float: left;" />';
		}
	echo '
		<input type="hidden" name="ym_current_page" value="' . $page . '" />
		' . sprintf(__('Page: %s', 'ym'), ($page + 1));

		$count_users = $wpdb->get_var('SELECT count(ID) FROM ' . $wpdb->users);
		$pages = ceil($count_users / $ym_admin_ym_members_page_limit);
		echo '/' . $pages;

		if ($count == $ym_admin_ym_members_page_limit) {
			echo '<input type="submit" class="button-secondary" name="task" value="' . __('Forward a Page', 'ym') . '" style="float: right;" />';
		}

		echo '
		</td>
	</tr>
	';
// end pagination

echo '
	<tr>
		<th style="width: 20px;" style="text-align:center"><input type="checkbox" class="ym_select_all" data-target="ym_member_select" /></th>
		<th style="text-align:center">' . __('Member [ID]', 'ym') . '</th>
		<th style="text-align:center"></th>
		<th style="text-align:center">' . __('Package Type', 'ym') . '</th>
		<th style="text-align:center">' . __('Package', 'ym') . '</th>
		<th colspan="2" style="text-align:center">' . __('Dates', 'ym') . '</th>
		<th style="text-align:center">' . __('Status', 'ym') . '</th>
		<th></th>
		<th></th>
		<th style="text-align:center">' . __('Delete', 'ym') . '</th>
	</tr></thead>
	<tfoot><tr>
		<th style="width: 20px;" style="text-align:center"><input type="checkbox" class="ym_select_all" data-target="ym_member_select" /></th>
		<th style="text-align:center">' . __('Member [ID]', 'ym') . '</th>
		<th style="text-align:center"></th>
		<th style="text-align:center">' . __('Package Type', 'ym') . '</th>
		<th style="text-align:center">' . __('Package', 'ym') . '</th>
		<th colspan="2" style="text-align:center">' . __('Dates', 'ym') . '</th>
		<th style="text-align:center">' . __('Status', 'ym') . '</th>
		<th></th>
		<th></th>
		<th style="text-align:center">' . __('Delete', 'ym') . '</th>
	</tr>
';

// pagination buttons
	echo '<tr>
		<td colspan="11" style="text-align: center;">';
		if ($page != 0) {
			echo '<input type="submit" class="button-secondary" name="task" value="' . __('Back a Page', 'ym') . '" style="float: left;" />';
		}
	echo '
		<input type="hidden" name="ym_current_page" value="' . $page . '" />
		' . sprintf(__('Page: %s', 'ym'), ($page + 1));
		echo '/' . $pages;

		if ($count == $ym_admin_ym_members_page_limit) {
			echo '<input type="submit" class="button-secondary" name="task" value="' . __('Forward a Page', 'ym') . '" style="float: right;" />';
		}

		echo '
		</td>
	</tr>
	';
// end pagination

	echo '
	</tfoot>
	<tbody>';

	foreach ($users as $user) {
		$ym_display_a_user = new YourMember_User($user->ID);
		/**
		ym_members_render_member_row($user, $ym_user);
		split to separate function
		@TODO: remove_old_content_restriction
		*/
		echo '<tr>';

		echo '<td><input type="checkbox" style="width: 20px;" class="checkbox ym_member_select" name="ps[]" id="user_' . $user->ID . '" value="' . $user->ID . '" /></td>';
		echo '<td>';

		echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=user-edit&amp;user_id=' . $user->ID . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">';
		echo $user->user_login;
		echo '</a>';

		echo ' [' . $user->ID . '] ';
//		echo ' <a href="' . admin_url('admin-ajax.php') . '?action=ym_raw_packet&amp;user_id=' . $user->ID . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">' . __('Package', 'ym') . '</a>';

		if ($ym_display_a_user->parent_id) {
			echo '<br />' . __('Group Leader:', 'ym') . ' ' . $ym_display_a_user->parent_id;
			$parent = get_user_by('id', $ym_display_a_user->parent_id);
			echo ' ' . $parent->user_login;
		}

		echo '<br />' . $user->user_email . '</td>';

		// log
		echo '<td>';
		echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=ym-logs&amp;user_id=' . $user->ID . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox ym_node_tree"></a></td>';

		$acc = $ym_display_a_user->account_type ? $ym_display_a_user->account_type : __('No Purchase', 'ym');
		echo '<td>' . $acc;
		if ($ym_display_a_user->trial_on) {
			echo '<br />' . __('Trial Active', 'ym');
		}
		echo '</td>';

		$pack = $ym_display_a_user->pack_id ? '<span title="This user is currently on Pack ID: ' . $ym_display_a_user->pack_id . '">' . '(' . $ym_display_a_user->pack_id . ') ' . ym_get_pack_label($ym_display_a_user->pack_id) . '</span>' : 'N/A';
		echo '<td>' . $pack;
		if (
			$ym_display_a_user->hide_old_content && 
			$ym_display_a_user->account_type_join_date
			) {
			echo '<br />';
			echo '<span style="color: gray;">' . __('Limited PRE:', 'ym') . '</span> ' . date(YM_DATE, $ym_display_a_user->account_type_join_date + (get_option('gmt_offset') * 3600));
			echo '<a href="' . $this_page . '&task=remove_old_content_restriction&user_id=' . $user->ID . '">
				<img style="height: 10px;" title="Remove Restriction on Account" src="' . YM_IMAGES_DIR_URL . 'cross.png" />
			</a>';
		}
		echo '</td>';

		echo '<td>';
		echo '<span style="color: gray;">' . __('Register:', 'ym') . '</span> ' . date(YM_DATE, strtotime($user->user_registered) + (get_option('gmt_offset') * 3600)) . '<br />';
		echo '<span style="color: gray;">' . __('Last Pay:', 'ym') . '</span> ' . ($ym_display_a_user->last_pay_date ? date(YM_DATE, $ym_display_a_user->last_pay_date + (get_option('gmt_offset') * 3600)) : __('No Payment', 'ym'));
		echo '</td>';

		echo '<td>';
		echo '<span style="color: gray;">' . __('Expiry:', 'ym') . '</span> ' . ($ym_display_a_user->expire_date ? date(YM_DATE, $ym_display_a_user->expire_date) : 'N/A') . '<br />';
		echo '<span style="color: gray;">' . __('PT Join:', 'ym') . '</span> ' . ($ym_display_a_user->account_type_join_date ? date(YM_DATE, $ym_display_a_user->account_type_join_date + (get_option('gmt_offset') * 3600)) : __('No Package Type', 'ym'));
		echo '</td>';

		echo '<td class="ym_user_status_' . $ym_display_a_user->ID . '">';
		echo $ym_display_a_user->status ? $ym_display_a_user->status : __('No Status', 'ym');
		if ($ym_display_a_user->status_str) {
			echo '<br />' . $ym_display_a_user->status_str;
		}
		echo '</td>';

		/**
		hover functions
		*/

		echo '<td class="ym_user_orphan_' . $ym_display_a_user->ID . '">';
		if ($ym_display_a_user->parent_id) {
			echo '
<div class="ym_ajax_call">
	<input type="hidden" name="action" value="ym_quick_orphan">
	<input type="hidden" name="ym_quick_orphan_user_id" value="' . $ym_display_a_user->ID . '">
	<a href="" class="ym_form_submit_clone ym_user_go deletelink" data-html="1" title="' . __('Orphaise the User', 'ym') . '"></a>
</div>
';
		}
		echo '</td>';

		echo '<td>
<div class="ym_hover_functions">
	<div class="ym_ajax_call">
		<input type="hidden" name="action" value="ym_quick_activate_toggle" />
		<input type="hidden" name="ym_quick_activate_toggle_user_id" value="' . $user->ID . '" />
		<a href="" class="ym_form_submit_clone ';

		if ($ym_display_a_user->status == YM_STATUS_ACTIVE)
			echo 'ym_accept';
		else
			echo 'ym_cancel';

		echo '" data-html="1" title="' . __('Toggle the User', 'ym') . '"></a>
	</div>
</div>
</td><td>
	<div class="ym_ajax_call">
		<input type="hidden" name="action" value="ym_quick_delete" />
		<input type="hidden" name="ym_quick_delete_user_id" value="' . $user->ID . '" />
		<a href="" class="ym_form_submit_clone ym_user_delete deletelink" data-html="1" title="' . __('Delete the User', 'ym') . '"></a>
	</div>
</td>';

		echo '</tr>';
		// end split
	}

	echo '
	</tbody>
	</table>

	';
	ym_members_filters($filters);

	ym_box_top(__('Tasks', 'ym'));
	echo '<label for="delete_users"><input type="submit" id="delete_users" name="task" class="button-secondary deletelink" value="' . $ym_members_tasks['delete'] . '" /></label>';
	echo ' | ';
	echo '<label for="suspend_users"><input type="submit" id="suspend_users" name="task" class="button-secondary deletelink" value="' . $ym_members_tasks['suspend'] . '" /></label>';
	echo ' | ';
	echo '<label for="create_user"><input type="submit" id="ym_members_create_user" name="task" class="button-secondary" value="' . $ym_members_tasks['create_user'] . '" /></label>';
	echo ' | ';
	echo '<label for="ym_admin_ym_members_page_limit">' . __('Change page limit', 'ym') . ' <input type="text" name="ym_admin_ym_members_page_limit" value="' . $ym_admin_ym_members_page_limit . '" size="4" /></label>';
	echo ' | ';
	echo '<input type="submit" id="change_limit" name="task" class="button-secondary" value="' . $ym_members_tasks['change_limit'] . '" />';
	ym_box_bottom();

	ym_box_top(__('Update User', 'ym'));

	echo '<table class="form-table">';
	echo '<tr><th colspan="10"><p>' . __('You can change the following User Package Fields') . '</p></th></tr>';

	echo '<tr><th>' . __('Package Type', 'ym') . '</th><td><select name="package_type">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
	<option value="-">' . __('Remove', 'ym') . '</option>';

	foreach ($ym_package_types->types as $type) {
		echo '<option value="' . $type . '">' . $type . '</option>';
	}

	echo '</select></td></tr>

<tr><th>' . __('Trial On', 'ym') . '</th><td><select name="trial_on">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
	<option value="1">' . __('Yes', 'ym') . '</option>
	<option value="0">' . __('No', 'ym') . '</option>
</select></td><th>' . __('Trial Taken (Package)', 'ym') . '</th><td><select name="trial_taken">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
	<option value="-">' . __('Remove', 'ym') . '</option>
	';

	foreach ($ym_packs->packs as $pack) {
		echo '<option value="' . $pack['id'] . '">' . ym_get_pack_label($pack['id']) . '</option>';
	}

	echo '
</select></td></tr>

<tr><th>' . __('Expire Date', 'ym') . '</th><td>
	<input type="text" name="expire_date" id="expire_date" class="ym_datepicker" />
</td><th>' . __('Status', 'ym') . '</th><td><select name="status">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
';
	foreach ($status_str as $str) {
		echo '<option value="' . $str . '">' . $str . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th>' . __('Hide Old Content', 'ym') . '</th><td>
		<select name="hide_old_content">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
	<option value="1">' . __('Yes', 'ym') . '</option>
	<option value="0">' . __('No', 'ym') . '</option>
		</select></td>
	';

	echo '<td>' . __('Hide Admin Bar', 'ym') . '</td><td><select name="hide_admin_bar">
	<option value="">' . __('Leave Alone', 'ym') . '</option>
	<option value="1">' . __('Yes', 'ym') . '</option>
	<option value="0">' . __('No', 'ym') . '</option>
		</select></td>';

	echo '</tr>';

	echo '<tr><th colspan="10"><h4>' . __('Group Membership', 'ym') . '</h4></th></tr>';
	echo '<tr><th>' . __('Change Child Accounts Limit', 'ym') . '</th><td>';
	echo '<input type="text" name="child_accounts_allowed" id="child_accounts_allowed" />';
//	echo '</td><td>';
//	echo 'Change Parent';
//	echo '</td><td>';
//	echo '<input type="text" name="parent_id" id="search_user_name" value="Leave Alone">';
	echo '</td></tr>';

	/**

	*/
	echo '<tr><th colspan="10"><h4>' . __('Or you can Apply an entire Package', 'ym') . '</h4></th></tr>';

	echo '<tr><th>Package</th>';
	echo '<td><select name="apply_package_apply">
		<option value="">' . __('Leave Alone/Do not Apply Package', 'ym') . '</option>
		<option value="-">' . __('Remove', 'ym') . '</option>
	';

	foreach ($ym_packs->packs as $pack) {
		echo '<option value="' . $pack['id'] . '">' . ym_get_pack_label($pack['id']) . '</option>';
	}
	echo '</select>';
	echo '</td><th>';
	echo __('Skip Trial (if enabled on package)', 'ym') . '</th><td>';
	echo '<select name="apply_package_skip_trial">
	<option value="1">' . __('Yes', 'ym') . '</option>
	<option value="0">' . __('No', 'ym') . '</option>
	</select>
	';
	echo '</td><th>';
	echo __('Notify the user about their Gift', 'ym') . '</th><td>';
	echo '<select name="apply_package_gift_notify">
	<option value="1">' . __('Yes', 'ym') . '</option>
	<option value="0" selected="selected">' . __('No', 'ym') . '</option>
	</select>
	';
	echo '</td></tr>';

	echo '</table>';

	echo '<p class="submit" style="text-align: right;">
		<input type="submit" name="task" class="button-primary" value="' . $ym_members_tasks['update'] . '" />
	</p>';

	ym_box_bottom();
} else {
	if (!$page && empty($filters['by_option'])) {
		// case: No users found
		echo '
	<div id="message" class="error"><p><strong><center>' . __('There are no subscribed users.','ym') . '</center></strong></p></div>
';

		ym_box_top(__('Tasks', 'ym'));
		echo '<label for="create_user"><input type="submit" id="ym_members_create_user" name="task" class="button-secondary" value="' . $ym_members_tasks['create_user'] . '" /></label>';
		ym_box_bottom();
	} else {
		echo '
	<div id="message" class="error"><p><strong><center>' . __('There are no users on this page.','ym') . '</center></strong></p></div>
';

		ym_box_top(__('Tasks', 'ym'));
		echo '<label for="create_user"><input type="submit" id="ym_members_create_user" name="task" class="button-secondary" value="' . $ym_members_tasks['create_user'] . '" /></label>';
		ym_box_bottom();

		ym_members_filters($filters);
	}
}
echo '</form>';

echo '
<form id="ym_members_create_user_form" method="post">
';
wp_nonce_field('ymmembertasks', 'ym-members');
echo '
	<fieldset>
		<legend>' . __('Create a User', 'ym') . '</legend>
		<table>
';

$status = array();
foreach ($status_str as $state) {
	$status[$state] = $state;
}
$package_types = array();
foreach ($ym_package_types->types as $type) {
	$package_types[$type] = $type;
}

$ym_formgen->render_form_table_text_row(__('Username') . ' ' . __('(required)'), 'username');
$ym_formgen->render_form_table_email_row(__('E-mail') . ' ' . __('(required)'), 'email');
$ym_formgen->render_form_table_password_row(__('Password'), 'password');
$ym_formgen->render_form_table_password_row(__('Password Confirm', 'ym'), 'password_confirm');

$ym_formgen->render_form_table_divider(__('Apply', 'ym'));

$new_user_packages = array(0 => __('No Package', 'ym'));
foreach ($ym_packs->packs as $pack) {
	$new_user_packages[$pack['id']] = ym_get_pack_label($pack['id']);
}

$ym_formgen->render_combo_from_array_row(__('Package', 'ym'), 'package', $new_user_packages);

$ym_formgen->render_form_table_divider(__('Or Apply', 'ym'));
$ym_formgen->render_combo_from_array_row(__('Package Type', 'ym'), 'package_type', $package_types);
$ym_formgen->render_combo_from_array_row(__('Status', 'ym'), 'status', $status);

$ym_formgen->render_form_table_divider();
$ym_formgen->render_form_table_radio_row(__('Send Welcome Email', 'ym'), 'smflag', '0');

echo '
		</table>
		<p class="submit"> <input type="submit" name="task" class="button-primary alignright" value="' . $ym_members_tasks['create_user'] . '" /></p>
	</fieldset>
</form>
';

// stuff to redo
?>
	<div class="postbox" style="margin:5px 0px;">
		<h3><?php _e('Import Member data',"ym"); ?></h3>
		<div class="inside">
			<?php _e('<em><strong>Import members data from a CSV generated using Your Members Export Members data tool only</strong></em>','ym') ?>
			<form method="post" enctype="multipart/form-data">
				<div style="clear: both; padding-top: 5px;">
					<label>
						<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Upload File:','ym') ?></div>
						<div style="float: left; width: 150px;">
							<input type="file" name="upload">
						</div>
					</label>
				</div>

				<div style="clear: both; padding-top: 10px;">
					<div style="float: left; width: 150px; padding-left: 150px;">
						<input class="button" type="submit" name="ym_start_import" value="Import" />
					</div>
				</div>
				
				<div style="clear: both; height: 1px; width: 1px;">&nbsp;</div>
			</form>
		</div>
	</div>

	<div id="poststuff">
		 <div class="postbox close-me" style="margin:5px 0px;">
			<h3><?php _e('Export Member Data',"ym"); ?></h3>
		 <div class="inside">
		 <?php _e('<em>Generates an export file containing members details</em>','ym') ?>
		 <form method="POST">

		<div style="clear: both; padding-top: 10px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Path to save file to as we build it:','ym') ?></div>
				<div style="float: left; width: 150px;">
					<input type="text" name="backup_temp_path" value="<?php
global $ym_sys;
$last_used = $ym_sys->export_last_tmp_path;
if (!$last_used || !is_dir($last_used)) {
	$last_used = trailingslashit(sys_get_temp_dir());
}
echo $last_used;
?>" />
				</div>
			</label>
		</div>

		<div style="clear: both; padding-top: 10px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Include Expired Members:','ym') ?></div>
				<div style="float: left; width: 150px;">
					<select name="bkinactive">
						<option value="0">No</option>
						<option value="1">Yes</option>
					</select>
				</div>
			</label>
		</div>
		
		<div style="clear: both; padding-top: 5px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Package Type:','ym') ?></div>
				<div style="float: left; width: 150px;">
					<select name="bkpackagetype">
					<option value="all">All</option>

				<?php
				$obj_types = get_option('ym_account_types');

				foreach ($obj_types->types as $type) {
					echo '<option value="'. $type .'">'. $type .'</option>';
				}
				?>
					</select>
				</div>		
			</label>
		</div>

		<div style="clear: both; padding-top: 5px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Package:','ym') ?></div>
				<div style="float: left; width: 150px;">
					<select name="bkpackage">
					<option value="all">All</option>

				<?php
				global $ym_packs;
				foreach ($ym_packs->packs as $pack) {
					echo '<option value="'. $pack['id'] .'">('. $pack['id'] .') '. ($pack['admin_name'] ? $pack['admin_name'] : ym_get_pack_label($pack['id'])) .'</option>';
				}
				?>
					</select>
				</div>		
			</label>
		</div>

		<div style="clear: both; padding-top: 5px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Output Format:','ym') ?></div>
				<div style="float: left; width: 150px;">
					<select name="bkformat">
						<option value="xls">XLS</option>
						<option value="csv">CSV</option>
					</select>
				</div>		
			</label>
		</div>
		
		<div style="clear: both; padding-top: 5px;">
			<label>
				<div style="float: left; width: 150px; padding-top: 5px;"><?php _e('Include headers?','ym') ?></div>
				<div style="float: left; width: 150px;">
					<input type="checkbox" name="bkheaders" />
				</div>		
			</label>
		</div>
		
		<div style="clear: both; padding-top: 10px;">
			<div style="float: left; width: 150px; padding-left: 150px;">
				<input class="button" type="submit" name="ym_start_xls_backup" value="Export" />
			</div>
		</div>
		
		<div style="clear: both; height: 1px; width: 1px;">&nbsp;</div>
		 </form>
		 </div>
	</div>
</div>