<?php

/*
* $Id: ym-membership-packages.php 2541 2013-01-18 17:03:00Z bcarlyon $
* $Revision: 2541 $
* $Date: 2013-01-18 17:03:00 +0000 (Fri, 18 Jan 2013) $
*/

echo '<div class="wrap" id="poststuff">';

global $wpdb, $ym_formgen, $duration_str, $ym_res, $ym_package_types;

$link = YM_ADMIN_URL. '&ym_page=ym-membership-packages&action=';

$action = ym_get('action');

$force_end_date = ym_post('force_end_date');
if ($force_end_date) {
	list($date, $month, $year) = explode('/', $force_end_date);
	$force_end_date = mktime(0, 0, 0, $month, $date, $year);
}


$data = array(
	'edit_id'						=> ym_post('id', ym_get('id')),
	'cost'							=> ym_post('cost', 0),

	'account_type'					=> ym_post('account_type', 0),

	'duration'						=> ym_post('duration', '3'),
	'duration_type'					=> ym_post('duration_type', 'm'),

	'trial_on'						=> ym_post('trial_on', 0),
	'trial_duration'				=> ym_post('trial_duration', 0),
	'trial_duration_type'			=> ym_post('trial_duration_type', 'd'),
	'trial_cost'					=> ym_post('trial_cost', 0),

	'num_cycles'					=> ym_post('num_cycles', 0),
	'role'							=> ym_post('role', 'subscriber'),
	'description'					=> stripslashes(ym_post('description', '')),

	'success_redirect'				=> ym_post('success_redirect', ''),
	'first_login'					=> ym_post('first_login', ''),
	'hide_old_content'				=> ym_post('hide_old_content', 0),
	'hide_subscription'				=> ym_post('hide_subscription', 0),
	
	'country'						=> ym_post('country', ''),
	'currency'						=> ym_post('currency', ''),
	
	'force_end_date'				=> $force_end_date,
	'on_expire_drop_to'				=> ym_post('on_expire_drop_to'),

	'admin_name'					=> ym_post('admin_name'),

	'gateway_disable'				=> ym_post('gateway_disable', array()),

	'login_redirect_url'			=> ym_post('login_redirect_url'),
	'wpadmin_disable_redirect_url'	=> ym_post('wpadmin_disable_redirect_url'),
	'logout_redirect_url'			=> ym_post('logout_redirect_url'),

	'child_accounts_allowed'		=> ym_post('child_accounts_allowed', 0),
	'child_accounts_package_types'	=> ym_post('child_accounts_package_types', array()),
	'child_accounts_packages'		=> ym_post('child_accounts_packages', array()),

	'hide_admin_bar'				=> ym_post('hide_admin_bar', FALSE),
);
$data = apply_filters('ym_packs_gateways_extra_fields_load', $data);

// invert gateway_disable
// current stores gateways to use
$gateways_disabled = array();
global $ym_active_modules;
foreach ((array)$ym_active_modules as $key => $module) {
	if (FALSE === array_search($module, $data['gateway_disable'])) {
		$gateways_disabled[] = $module;
	}
}
$data['gateway_disable'] = $gateways_disabled;

if ($_POST) {
	$data['cost'] = preg_replace('/[^\d\.]/', '', $data['cost']);
	$data['trial_cost'] = preg_replace('/[^\d\.]/', '', $data['trial_cost']);

	$data['cost'] = number_format($data['cost'], 2, '.', '');
	$data['trial_cost'] = number_format($data['trial_cost'], 2, '.', '');

	if ($data['duration'] == 0) {
		$data['duration'] = 1;
	}
	if ($data['trial_on'] == 1 && $data['trial_duration'] == 0) {
		$data['trial_duration'] = 1;
	}
}

switch($action) {
	case 'edit':
	case 'new':
		if ($_POST) {
			// then new or edit

			// post action

			// do view control
			$last = ym_post('ym_last_used_view', 0);
			update_option('ym_last_used_view', $last);

			// new account type?
			$new = ym_post('new_account_type', '');
			if ($new) {
				$new = strip_tags($new);

				if (empty($new)) {
					$item = $obj_types->types;
					$item = array_pop($item);
					$data['account_type'] = $item;
				} else {
					$ym_package_types->create($new);
					$data['account_type'] = $new;
				}
			} else {
				$data['account_type'] = $ym_package_types->types[$data['account_type']];
			}

			// temp
			$data = ym_packs_gateways_extra_fields_post($data);

			// burn baby burn
			$obj = get_option('ym_packs');

			$data['id'] = $data['edit_id'];
			unset($data['edit_id']);

			if ($data['id']) {
				// updating
				foreach ($obj->packs as $ref_id => $pack) {
					if ($pack['id'] == $data['id']) {
						$obj->packs[$ref_id] = $data;
					}
				}
			} else {
				$id = 0;
				foreach ($obj->packs as $pack) {
					$id = ($pack['id'] > $id) ? $pack['id'] : $id;
				}
				$id ++;
				$data['id'] = $id;
				// id
				$obj->packs[] = $data;
			}
			echo '<pre>';

			update_option('ym_packs', $obj);

			echo '<meta http-equiv="refresh" content="1;' . $link . '&message=ok" />';
			echo '</div>';
			return;
		}

		wp_enqueue_script('ym_admin_js_members', YM_JS_DIR_URL . 'ym_admin_membership_packages.js', array('jquery'), YM_PLUGIN_VERSION);

		if ($data['edit_id']) {
			$predata = ym_get_pack_by_id($data['edit_id']);
			$data['id'] = $data['edit_id'];
			unset($data['edit_id']);

			foreach ($data as $key => $current) {
				$data[$key] = isset($predata[$key]) ? $predata[$key] : $data[$key];
			}

			// invert gateway_disable
			// current stores gateways to hide
			$gateways_disabled = array();
			global $ym_active_modules;
			foreach ((array)$ym_active_modules as $key => $module) {
				if (FALSE === array_search($module, $data['gateway_disable'])) {
					$gateways_disabled[] = $module;
				}
			}
			$data['gateway_disable'] = $gateways_disabled;
		}
	
		if ($action == 'new') {
			ym_box_top(__('Creating a new Package', 'ym'));
		} else {
			ym_box_top(__('Editing an existing Package', 'ym'));
		}

		$selected = get_option('ym_last_used_view');
		if (!$selected) {
			$selected = 0;
		}
		if (!ym_packs_gateways_trial_on() && $selected == 1) {
			$selected = 0;
		}
		
		echo '
	<form action="" method="post">
		<div style="float: right;">' . __('Mode:', 'ym') . '
		<select id="ym_pack_views" name="ym_last_used_view">
			<option value="0" ' . ($selected == 0 ? 'selected="selected"' : '') . ' >' . __('Basic', 'ym') . '</option>';
			if (ym_packs_gateways_trial_on()) {
				echo '<option value="1" ' . ($selected == 1 ? 'selected="selected"' : '') . ' >' . __('Basic with trial', 'ym') . '</option>';
			}
			echo '
			<option value="2" ' . ($selected == 2 ? 'selected="selected"' : '') . ' >' . __('Advanced', 'ym') . '</option>
		</select>
		</div>
		';
		echo '<p>';
		if ($action == 'new') {
			echo '<p class="basic_text introtexts">' . __('You are creating a new Package using the Basic View', 'ym') . '</p>';
			echo '<p class="basic_with_trial_text introtexts">' . __('You are creating a new Package using the Basic with Trial View', 'ym') . '</p>';
			echo '<p class="advanced_text introtexts">' . __('You are creating a new Package using the Advanced View', 'ym') . '</p>';
		} else {
			echo '<p class="basic_text introtexts">' . __('You are Editing an existing Package using the Basic View', 'ym') . '</p>';
			echo '<p class="basic_with_trial_text introtexts">' . __('You are Editing an existing Package using the Basic with Trial View', 'ym') . '</p>';
			echo '<p class="advanced_text introtexts">' . __('You are Editing an existing Package using the Advanced View', 'ym') . '</p>';
		}
		echo '</p>';

		ym_render_subscription_form($data);
		
		echo '
		<p style="float: left;" class="submit">
			<input type="button" value="' . __('Back', 'ym') . '" onclick="location.href = \'' . YM_ADMIN_URL. '&ym_page=' . $_GET['ym_page'] . '\';" />
		</p>
		<p style="text-align: right;" class="submit">
			<input type="submit" class="button-primary" value="' . __('Save', 'ym') . '" />
		</p>
	</form>
		';

		ym_box_bottom();
		
		break;
	case 'delete':
		$id = ym_post('id', ym_get('id'));
		$new_pack_id = ym_post('pack_id');
		
		if ($id && $new_pack_id) {
			$obj = get_option('ym_packs');
			foreach ($obj->packs as $k => $pack) {
				if ($pack['id'] == $id) {
					unset($obj->packs[$k]);
				} else if ($pack['id'] == $new_pack_id) {
					$pack_data = $pack;
				}
			}
			update_option('ym_packs', $obj);
			
			// find all users on this pack and move to new pack
			// get all users
			$sql = 'SELECT u.id AS user_id FROM ' . $wpdb->users . ' u LEFT JOIN ' . $wpdb->usermeta . ' m ON m.user_id = u.id WHERE m.meta_key = \'ym_user\'';
			foreach ($wpdb->get_results($sql) as $row) {
				$user_id = $row->user_id;
				if (!$user_data = (object)get_user_option('ym_user', $user_id)) {
					// should never hit here
					$user_data = new YourMember_User($user_id);
					$user_data->save();
				}
				
				// only update is user is on the deleted pack
				if (isset($user_data->pack_id) && $user_data->pack_id == $id) {
					$user_data->pack_id = $new_pack_id;
					$user_data->account_type = $pack['account_type'];
					@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION, $user_data->account_type, $user_id);
					update_user_option($user_id, 'ym_user', $user_data, true);
					update_user_meta($user_id, 'ym_account_type', $user_data->account_type);
				}
			}
			
			echo '<meta http-equiv="refresh" content="3;' . $link . '&message=deleted" />';
		} else {
			ym_box_top(__('Deleting a Pack', 'ym'));
			echo '<p>' . __('Which pack would you like to put current pack members on', 'ym') . '</p>';
			
			echo '<form action="" method="post">';
			echo '<input type="hidden" name="id" value="' . $id. '" />';
			echo '<table class="form-table">';
			
			$obj = get_option('ym_packs');
			$packs = array();
			foreach ($obj->packs as $pack) {
				if ($pack['id'] != $id) {
					$packs[$pack['id']] = strip_tags(ym_get_pack_label($pack['id']));
				}
			}
			echo '<tr><th>' . __('Replacement Pack', 'ym') . '</th><td><select name="pack_id">';
			foreach ($packs as $k=>$v) {
				echo '<option value="' . $k . '">';
				echo $v;
				echo '</option>';
			}
			echo '</select></td></tr>';
			
			echo '</table>';
			echo '<p style="float: left;" class="submit"><input type="button" value="' . __('Back', 'ym') . '" onclick="location.href = \'' . YM_ADMIN_URL. '&ym_page=' . $_GET['ym_page'] . '\';" /></p>';
			echo '<p style="text-align: right;" class="submit"><input type="submit" class="deletelink" value="' . __('Delete', 'ym') . '" /></p>';
			echo '</form>';
			
			ym_box_bottom();
		}
		break;
	case 'order':
		$obj = get_option('ym_packs');
		$neworder = get_option('ym_packs');
		$neworder->packs = array();
		
		$order = ym_post('order');
		if ($order) {
			$order = str_replace('item[]=', '', $order);
			$order = explode('&', $order);
			foreach ($order as $id) {
				$pack = $obj->packs[$id];
				$neworder->packs[] = $pack;
			}
		}
		
		update_option('ym_packs', $neworder);

		echo '<meta http-equiv="refresh" content="3;' . $link . '&message=order" />';
		echo '</div>';
		return;
	default:
		if (ym_get('message')) {
			echo '<div id="message" class="updated fade"><p>';
		}
		if (ym_get('message') == 'ok') {
			echo __('Packages were updated', 'ym');
		}
		if (ym_get('message') == 'order') {
			echo __('Package order was updated', 'ym');
		}
		if (ym_get('message') == 'deleted') {
			echo __('The Pack was deleted', 'ym');
		}
		if (ym_get('message')) {
			echo '</p></div>';
		}

		ym_box_top('&nbsp');

		echo '<form action="' . $link . 'order" method="post" onsubmit="ym_process_sort();">';
		echo '<table class="form-table" id="sorttable">';
		$header = '<tr>
				<th style="width: 20px;">' . __('ID', 'ym') . '</th>
				<th>' . __('Label', 'ym') . '</th>
				<th>' . __('Cost', 'ym') . '</th>
				<th>' . __('Package Type', 'ym') . '</th>
				<th>' . __('WP Role', 'ym') . '</th>
				<th style="width: 40px;">' . __('Visible', 'ym') . '</th>
				<th style="width: 40px;">' . __('Group', 'ym') . '</th>
				<th style="width: 100px; text-align: center;">' . __('User Count', 'ym') . '</th>
				<th style="width: 20px;"></th><th style="width: 20px;"></th>
			</tr>';
		echo '<thead>' . $header . '</thead>';
		echo '<tfoot>' . $header . '</tfoot>';
		echo '<tbody>';
		foreach (ym_get_packs() as $k => $pack) {
			echo '<tr class="sorttablesort item" id="item_' . $k . '">';
			echo '<td>' . $pack['id'] . '</td>';
			echo '<td nowrap="nowrap">' . strip_tags(ym_get_pack_label($pack['id'])) . '</td>';
			echo '<td>' . $pack['cost'] . '</td>';
			echo '<td>' . $pack['account_type'] . '</td>';
			echo '<td>' . $pack['role'] . '</td>';
			echo '<td><span class="ym_' . ($pack['hide_subscription'] ? 'cross' : 'tick') . '">&nbsp;</span></td>';
			echo '<td><span class="ym_' . ($pack['child_accounts_allowed'] > 0 ? 'tick' : 'cross') . '">&nbsp;</span></td>';
			echo '<td style="text-align: center;">' . ym_users_on_pack_count($pack['id']) . '</td>';
			
			echo '<td style="width: 20px;"><a href="' . $link . 'edit&id=' . $pack['id'] . '" class="ym_edit" title="' . __('Edit', 'ym') . '">&nbsp;</a></td>';
			echo '<td style="width: 20px;"><a href="' . $link . 'delete&id=' . $pack['id'] . '" class="ym_delete" title="' . __('Delete', 'ym') . '">&nbsp;</a></td>';
			echo '</tr>';
		}
		echo '</tbody>';

		echo '</table>';
		
		echo '<p>' . __('To reorder fields drag and drop them to their new location. The Order affects the Subscription Options Custom Field', 'ym') . '</p>';
		
		echo '<input type="hidden" name="order" id="order" value="" />';
		
		echo '<p style="float: left;" class="submit"><input type="button" class="button-primary" value="' . __('Add A New Package', 'ym') . '" onclick="location.href = \'' . $link . 'new\'" /></p>';
		echo '<p style="text-align: right;" class="submit"><input type="submit" value="' . __('Update Order', 'ym') . '" /></p>';
		
		echo '</form>';

		ym_box_bottom();
}
echo '</div>';

echo '
	<script type="text/javascript">
	jQuery(\'#sorttable\').sortable({
		items:			\'.sorttablesort\',
		placeholder:	\'ui-state-highlight\',
		start:			function(event, ui) {
			jQuery(\'.ui-state-highlight\').html(\'<td colspan="12"> </td>\');
		},
	});
	jQuery(\'#sorttable\').disableSelection();

	function ym_process_sort() {
		var order = jQuery(\'#sorttable\').sortable(\'serialize\');
		document.getElementById(\'order\').value = order;
		return;
	}
	</script>
	<style type="text/css">
		#sorttable tr {
			border: 1px solid #9F9F9F;
			cursor: move;
		}
		#sorttable tr.ui-state-highlight {
			border: 1px dashed #000000;
			margin: 5px;
		}
		#sorttable tr.ui-state-highlight td {
			height: 20px;
		}
	</style>
';

function ym_render_subscription_form($predata) {
	global $ym_formgen, $duration_str, $link, $ym_res, $ym_package_types;
	$data = $predata;

	$data['id'] = isset($data['id']) ? $data['id'] : 0;
	if ($data['id']) {
		echo '<input type="hidden" name="id" value="' . $data['id'] . '" />';
	}
	echo'
	<table class="form-table">';
	
	$ym_formgen->render_form_table_text_row(__('Admin Name', 'ym'), 'admin_name', $data['admin_name'], __('A Handy Name for the Admin Interface', 'ym'));
	$ym_formgen->render_form_table_text_row(__('Price', 'ym'), 'cost', $data['cost'], __('The Price in digits, no currency symbol needed', 'ym'));
	
	$types = array();
	if($ym_package_types) {
		foreach ($ym_package_types->types as $k=>$v) {
			$types[$k] = $v;
		}
	}
	$types['new'] = __('Create a New Type', 'ym');
	echo '<tr><th>' . __('Package Type', 'ym') . '</th><td><select name="account_type" class="account_type_selector">';
	foreach ($types as $k=>$v) {
		echo '<option value="' . $k . '" ';
		if ($data['account_type'] && strtolower($v) == strtolower($data['account_type'])) {
			echo ' selected="selected" ';
		}
		echo ' >';
		echo $v;
		echo '</option>';
	}
	echo '</select></td></tr>';
	
	echo '<tr class="new_account_type_entry"><th>' . __('New Package Type', 'ym');
	echo '<div style="color: gray; margin-top: 5px; font-size: 11px;">';
	echo __('Create a new Package Type', 'ym');
	echo '</th><td><input type="text" name="new_account_type" value="" /></td></tr>';
	
	echo '
<tr>
	<th>' . __('Duration', 'ym') . ':
	<div style="color: gray; margin-top: 5px; font-size: 11px;">' . __('The Length of the Subscription', 'ym') . '</div>
	</th>
	<td>
		<table><tr>
			<td>
				<input class="ym_input" style="width: 50px; font-family:\'Lucida Grande\',Verdana; font-size: 11px; text-align: right;" name="duration" value="' . $data['duration'] . '">
			</td>
			<td>
				<select name="duration_type">
				';
				
				foreach ($duration_str as $str => $val) {
					echo '<option value="' . $str . '"';
					if ($str == $data['duration_type']) {
						echo ' selected="selected"';
					}
					echo '>' . $val . '</option>';
				}
				echo '
				</select>
			</td>
		</tr></table>
	</td>
</tr>
';

	/**
	Basic with trial
	*/

	$trial = ym_packs_gateways_trial_on();

	if ($trial) {
		echo '<tr class="basic_with_trial table_divider"><td></td><th><h4>' . __('Trial Options', 'ym') . '</h4></th></tr>';
		echo '<tr class="basic_with_trial"><th>' . __('Enable Trial Period', 'ym');
		echo '</th><td><input type="checkbox" name="trial_on" value="1" ';
		if (isset($data['trial_on'])) {
			if ($data['trial_on'] == 1) {
				echo 'checked="checked"';
			}
		}
		echo ' /></td></tr>';

		echo '<tr class="basic_with_trial"><th>' . __('Trial Price', 'ym');
		echo '</th><td><input tpye="text" name="trial_cost" value="' . $data['trial_cost'] . '" /></td></tr>';
		echo '
<tr class="basic_with_trial">
	<th>' . __('Trial Duration', 'ym') . ':
	<div style="color: gray; margin-top: 5px; font-size: 11px;">' . __('The Length of the Trial Subscription', 'ym') . '</div>
	</th>
	
	<td>
		<table><tr>
			<td>
				<input class="ym_input" style="width: 50px; font-family:\'Lucida Grande\',Verdana; font-size: 11px; text-align: right;" name="trial_duration" value="' . $data['trial_duration'] . '">
			</td>
			<td>
				<select name="trial_duration_type">
				';
				
		foreach ($duration_str as $str => $val) {
			echo '<option value="' . $str . '"';
			if (isset($data['trial_duration_type'])) {
				if ($str == $data['trial_duration_type']) {
					echo ' selected="selected"';
				}
			}
			echo '>' . $val . '</option>';
		}
		echo '
				</select>
			</td>
		</tr></table>
	</td>
</tr>
';
	}

	/**
	Advanced
	*/

	echo '<tr class="advanced table_divider"><td></td><th><h4>' . __('Additional Package Options', 'ym') . '</h4></th></tr>';

	$cycles = array(0 => 'Ongoing');
	for ($x=1; $x<=59; $x++) {
		$cycles[$x] = $x;
	}

	$ym_formgen->tr_class = 'advanced';
	$ym_formgen->style = 'display: none;';

	$ym_formgen->render_combo_from_array_row(__('Package Reoccurance', 'ym'), 'num_cycles', $cycles, $data['num_cycles'], __('How many times the subscription repeats', 'ym'), $data['num_cycles']);
	
	$currencies = ym_get_currencies();
	if(!$data['currency']) $data['currency'] = ym_get_currency();

	$ym_formgen->render_combo_from_array_row(__('Payment Currency', 'ym'), 'currency', $currencies, $data['currency'], __('Currency for Package', 'ym'));
	$roles = new WP_Roles();
	$roles_array = array_reverse($roles->role_names);

	$ym_formgen->render_combo_from_array_row(__('WordPress Role', 'ym'), 'role', $roles_array, $data['role'], __('Grant these Role set to the User', 'ym'));
	
	$ym_formgen->render_form_table_textarea_row(__('Package Description', 'ym'), 'description', $data['description'], __('Only used with the [description] Package Template Argument. Check Advanced -> Messages -> Templates', 'ym'));

	$ym_formgen->render_form_table_checkbox_row(__('Hide Old Content', 'ym'), 'hide_old_content', $data['hide_old_content'], __('Hide content created and protected prior to the member&#39;s current subscription start date', 'ym'));
	$ym_formgen->render_form_table_checkbox_row(__('Hide from Standard Subscription page', 'ym'), 'hide_subscription', $data['hide_subscription'], __('When enabled, this package would only be available directly from ym_register shortcode or from a coupon', 'ym'));
	$ym_formgen->render_form_table_checkbox_row(__('Hide Admin Bar', 'ym'), 'hide_admin_bar', $data['hide_admin_bar']);

	echo '<tr class="advanced table_divider"><td></td><th><h4>' . __('Expire Options', 'ym') . '</h4></th></tr>';

	echo '<tr class="advanced"><th>' . __('Set Subscription Expiry Date', 'ym');
	echo '<div style="color: gray; margin-top: 5px; font-size: 11px;">' . __('Users will expire at the start of this date', 'ym') . '</div></th>';
				
	$value = $data['force_end_date'] ? $data['force_end_date'] : '';
	if ($value) {
		$value = date('d/m/Y', $value);
	}
	echo '<td>
					<input type="text" name="force_end_date" id="dateclear" value="' . $value . '" class="ym_yearpicker" /> <a href="#nowhere" onclick="ym_clear_target(\'dateclear\');">' . __('Clear Date', 'ym') . '
				</td></tr>';

	echo '<tr class="advanced"><th>' . __('On Expire Drop User to:', 'ym') . '</th>';
	echo '<td>';
	echo '<select name="on_expire_drop_to">';
	echo '<option value="">' . __('Inactive', 'ym') . '</option>';

	foreach (ym_get_packs() as $pack) {
		if ($data['id'] == $pack['id']) {
			continue;
		}
		echo '<option value="' . $pack['id'] . '" ';
					
		if ($data['on_expire_drop_to'] == $pack['id']) {
			echo 'selected="selected"';
		}
					
		echo '>';
				
		echo '(' . $pack['id'] . ') ' . ym_get_pack_label($pack['id']);

		echo '</option>';
	}
	
	echo '</select>';
				
	echo '</td></tr>';

	// gateway disable
	echo '<tr class="advanced table_divider"><td></td><th><h4>' . __('Payment Gateway Options', 'ym') . '</h4></th></tr>';

	echo '
	<tr class="advanced">
		<td colspan="2"><p>' . __('This filtering is also subject to Payment Gateway filters, for example, the Free Gateway can never be used to buy a non Free Package (so you do not have to deselect it here if this package is not free)', 'ym') . '</p></td>
	</tr>
	<tr class="advanced"><th>' . __('Allow Gateways', 'ym');
	echo '<div style="color: gray; margin-top: 5px; font-size: 11px;">'
		. __('You can select gateways to use with this Pack', 'ym')
		. '</div></th>';
	echo '<td>';

	echo '<ul>';
	global $ym_active_modules;
	foreach ((array)$ym_active_modules as $key => $module) {
		echo '<li>';
		echo '<label for="disable_module_' . $key . '">';
		echo '<input type="checkbox" id="disable_module_' . $key . '" name="gateway_disable[]" value="' . $module . '" ';

		// checked?
		if (FALSE !== array_search($module, $data['gateway_disable'])) {
			echo 'checked="checked"';
		}

		echo ' /> ';
		$way = new $module();
		echo $way->name;
		echo '</label>';
		echo '</li>';
	}
	echo '</ul>';

	echo '</td>';
	echo '</tr>';

	// URLS
	echo '<tr class="advanced table_divider"><td></td><th><h4>' . __('Package Speicifc Redirects', 'ym') . '</h4></th></tr>';

	$ym_formgen->render_form_table_url_row(__('Success URL', 'ym'), 'success_redirect', $data['success_redirect'], __('Where to redirect the user to on Successful Transaction, aka Thank You Page', 'ym'));

	echo '
	<tr class="advanced">
		<td colspan="2"><p>' . __('If you need Pack Specific redirects you can configure these here, you can leave these blank and the default redirects configured under Advanced->Security will be used', 'ym') . '</p></td>
	</tr>
	';

	$ym_formgen->render_form_table_url_row(__('Login Redirect URL', 'ym'), 'login_redirect_url', $data['login_redirect_url'], __('Where to redirect the user to on Login', 'ym'));
	$ym_formgen->render_form_table_url_row(__('WP Admin Block URL', 'ym'), 'wpadmin_disable_redirect_url', $data['wpadmin_disable_redirect_url'], __('Where to redirect the user to on WP Admin Access', 'ym'));
	$ym_formgen->render_form_table_url_row(__('Logout Redirect URL', 'ym'), 'logout_redirect_url', $data['logout_redirect_url'], __('Where to redirect the user to on Logout', 'ym'));
	$ym_formgen->render_form_table_url_row(__('First Login URL', 'ym'), 'first_login', $data['first_login'], __('Where to redirect on the user first login with this package', 'ym'));

	// Group Membership
	echo '<tr class="advanced table_divider"><td></td><th><h4>' . __('Group Membership', 'ym') . '</h4></th></tr>';

	$ym_formgen->render_form_table_text_row(__('Total Allowed Child Accounts', 'ym'), 'child_accounts_allowed', $data['child_accounts_allowed']);

	echo '<tr class="advanced"><td colspan="2"><p>' . __('A Group Leader can create child accounts, these child accounts normally inherit the parents package type, optionally you can allow the group admin to choose which Package Type the child account uses from a subset of the available Package Types (this will exclude the parent package type from being selectable unless you select it below). Further more you can allow Packages to be available, allowing you a Group Leader, to create a child account, which itself can have children. If packages are allowed they are applied to a child account at zero cost', 'ym') . '</p></td></tr>';
	echo '<tr class="advanced"><td>' . __('Use Inherit Mode', 'ym') . '</td><td><input type="checkbox" name="inherit_mode" id="ym_inherit_mode" ';

	$score = 0;
	foreach ($data['child_accounts_package_types'] as $v => $count) {
		$score += $count;
	}
	global $ym_packs;
	foreach ($ym_packs->packs as $pack) {
		if (in_array($pack['id'], $data['child_accounts_packages'])) {
			$score++;
		}
	}
	if (!$score) {
		echo 'checked="checked"';
	}

	echo ' /></td></tr>';
	echo '<tr class="ym_inherit_mode_off"><td>' . __('Available Package Types', 'ym') . '</td><td><table>';

	foreach ($ym_package_types->types as $v) {
		if (strtolower($v) == 'guest')
			continue;
		echo '<tr><td>';
		echo $v;
		echo '</td><td>';
		echo '<input type="text" name="child_accounts_package_types[' . $v . ']" value="' . (isset($data['child_accounts_package_types'][$v]) ? $data['child_accounts_package_types'][$v] : 0) . '" /> ';
		echo '</td></tr>';
	}
	echo '</table></td></tr>';

	echo '<tr class="ym_inherit_mode_off"><td>' . __('Available Packages', 'ym') . '</td><td><table>';
	foreach ($ym_packs->packs as $pack) {
		echo '<tr><td>';
		echo ym_get_pack_label($pack['id']);
		echo '</td><td>';
		echo '<input type="checkbox" name="child_accounts_packages[]" value="' . $pack['id'] . '" ' . (in_array($pack['id'], $data['child_accounts_packages']) ? 'checked="checked"' : '') . ' /> ';
		echo '</td></tr>';
	}
	echo '</table></td></tr>';
	
	/**
	ALL
	*/

	echo '<tr class="basic table_divider"><td></td><th><h4>' . __('Payment Gateway Specific Fields', 'ym') . '</h4></th></tr>';

	// gateway fields
	ym_packs_gateways_extra_fields_display($data);
	
	echo '</table>';
}
