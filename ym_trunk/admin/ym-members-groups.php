<?php

/*
* $Id: ym-about.php 2175 2012-05-28 15:26:07Z bcarlyon $
* $Revision: 2175 $
* $Date: 2012-05-28 16:26:07 +0100 (Mon, 28 May 2012) $
*/

if ($_POST) {
	$parent = ym_post('ym_add_child_to_parent_target');
	$child = ym_post('search_user_name');

	if ($parent && $child) {
		$child = get_user_by('login', $child);
		if ($child) {
			ym_group_membership_add_child_to_parent($child->ID, $parent);
			echo '<div id="message" class="updated"><p>' . __('Adoption Complete', 'ym') . '</div>';
		} else {
			echo '<div id="message" class="error"><p>' . __('Could not find the child details', 'ym') . '</div>';
		}
	}
}

global $wpdb;

echo '<div class="wrap" id="poststuff">';

echo ym_start_box(__('Group Membership', 'ym'));
echo '<p>' . __('Here you can control and review Groups of Members', 'ym') . '</p>';
echo '<p>' . __('Italic text on a child account, indicates that the data will be updated when the child account logs in', 'ym') . '</p>';

$offset = ym_get('offset', 0);
if ($offset < 0) {
	$offset = 0;
}
$limit = 20;

$search = ym_request('ym_search_for_a_user');

if ($search) {
	// find parents
	$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' um
		LEFT JOIN ' . $wpdb->users . ' u ON u.ID = um.user_id
		WHERE meta_key = \'ym_user\'
		AND meta_value LIKE \'%s:22:"child_accounts_allowed"%\'
		AND meta_value NOT LIKE \'%s:22:"child_accounts_allowed";s:1:"0"%\'
		AND (
			user_login LIKE \'%' . $search . '%\'
			OR
			user_email LIKE \'%' . $search . '%\'
		)
		ORDER BY user_id ASC
		LIMIT ' . $offset . ',' . $limit;
} else {
	// find parents
	$query = 'SELECT user_id FROM ' . $wpdb->usermeta . '
		WHERE meta_key = \'ym_user\'
		AND meta_value LIKE \'%s:22:"child_accounts_allowed"%\'
		AND meta_value NOT LIKE \'%s:22:"child_accounts_allowed";s:1:"0"%\'
		ORDER BY user_id ASC
		LIMIT ' . $offset . ',' . $limit;
}

echo '<table class="widefat">';

echo '
<tr><td colspan="11">
<form action="" method="post"><fieldset>
<label>' . __('Search for a user (matches User Login or User Email)', 'ym') . '</label>
<input type="text" name="ym_search_for_a_user" value="' . $search . '" />
<input type="submit" value="' . __('Search', 'ym') . '" class="alignright" />
</fieldset></form></td></tr>
';

echo '<tr>
<th>' . __('Group Leader', 'ym') . '</th>
<th>' . __('Package', 'ym') . '</th>
<th>' . __('Package Type', 'ym') . '</th>
<th>' . __('Limit', 'ym') . '</th>
<th>' . __('Package Types Available', 'ym') . '</th>
<th>' . __('Packages Available', 'ym') . '</th>
<th colspan="5"></th>
</tr>';

$row_index = 0;
while ($user_id = $wpdb->get_var($query, 0, $row_index)) {
	$user = new YourMember_User($user_id);
	echo '<tr>';
	echo '<td rowspan="2">[' . $user_id . '] ';
	echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=user-edit&amp;user_id=' . $user_id . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">';
	echo $user->data->user_login;
	echo '</a>';
	if ($user->data->user_login != $user->data->user_email)
		echo ' [' . $user->data->user_email . ']';
	echo '</td>';
	echo '<td rowspan="2">';
	if ($user->pack_id)
		echo '[' . $user->pack_id . '] ' . ym_get_pack_label($user->pack_id);
	else 
		echo __('N/A', 'ym');
	echo '</td>';
	echo '<td rowspan="2">' . $user->account_type . '</td>';
	echo '<td rowspan="2" style="text-align: center;">' . $user->child_accounts_allowed . '</td>';
	echo '<td rowspan="2">';

	global $ym_package_types;
	echo '<ul style="margin: 0px;">';
	foreach ($ym_package_types->types as $type) {
		if (strtolower($type) != 'guest') {
			echo '<li>';
			echo '<form action="" method="post" class="ym_ajax_call">
				<input type="hidden" name="action" value="ym_parent_child_accounts_package_types" />
				<input type="hidden" name="parent_id" value="' . $user->ID . '" />
				<input type="hidden" name="package_type" value="' . $type . '" />
				<input type="hidden" name="package_type_amount" class="ym_ajax_prompt_value" value="" />
				<a href="" class="ym_form_submit_prompt" data-html="1">';
			echo '(' . (isset($user->child_accounts_package_types[$type]) ? $user->child_accounts_package_types[$type] : '0') . ') ';
			echo $type;
			echo '</a></form></li>';
		}
	}
	echo '</ul>';

	echo '</td><td rowspan="2">';

	global $ym_packs;
	echo '<ul style="margin: 0px;">';
	foreach ($ym_packs->packs as $pack) {
		echo '<li>';
		echo '<form action="" method="post" class="ym_ajax_call">
			<input type="hidden" name="action" value="ym_parent_child_accounts_packages" />
			<input type="hidden" name="parent_id" value="' . $user->ID . '" />
			<input type="hidden" name="package_id" value="' . $pack['id'] . '" />
			<a href="" class="ym_form_submit">';
		if (in_array($pack['id'], $user->child_accounts_packages)) {
			echo '<span class="ym_tick"></span>';
		} else {
			echo '<span class="ym_cross"></span>';
		}
		echo ym_get_pack_label($pack['id']);
		echo '</a></form></li>';
	}
	echo '</ul>';
	echo '</td>';

	echo '<td>&nbsp;</td>';
	echo '<td>&nbsp;</td>';
	echo '<td class="alignright">
		<a href="#TB_inline?height=100&amp;width=400&amp;inlineId=ym_add_child_to_parent" class="thickbox ym_user_add" data-parent="' . $user_id . '">&nbsp;</a>
	</td>';

	echo '</tr><tr>';

	if ($user->child_ids) {
		echo '<th style="vertical-align: bottom;">' . __('Package Type', 'ym') . '</th>';
		echo '<th style="vertical-align: bottom;">' . __('Apply Pack', 'ym') . '</th>';
		echo '<th style="vertical-align: bottom;" colspan="3">' . __('Status', 'ym') . '</th>';
	} else {
		echo '<td colspan="5"</td>';
	}
	echo '</tr>';

	// got kids?
	if ($user->child_ids) {
		foreach ($user->child_ids as $child_id) {
			$child = new YourMember_User($child_id);
			echo '<tr>';
			echo '<td class="alignright"><strong>' . __('Child Account:', 'ym') . '</strong></td>';

			echo '<td colspan="4">[' . $child_id . '] ';
			echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=user-edit&amp;user_id=' . $child_id . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">';
			echo $child->data->user_login;
			echo '</a>';
			if ($child->data->user_login != $child->data->user_email)
				echo ' [' . $child->data->user_email . ']';
			echo '</td>';
			echo '<td>' . $child->child_accounts_allowed . '</td>';
			echo '<td>';

			$type = $child->status ? $child->account_type : '';

			echo '<form action="" method="post" class="ym_ajax_call">
				<input type="hidden" name="action" value="ym_child_package_type_change" />
				<input type="hidden" name="child_id" value="' . $child->ID . '" />
				<select class="ym_form_submit_select" name="package_type">
					<option value="inherit">' . __('Inherit', 'ym') . '</option>
				';
				global $ym_package_types;
				foreach ($ym_package_types->types as $type) {
					if (strtolower($type) != 'guest') {
						echo '<option value="' . $type . '" ';
						if ($type == $child->account_type) {
							echo 'selected="selected"';
						}
						echo '>' . $type . '</option>';
					}
				}
				echo '
			</select>
			';
			echo '</form></td>';
			echo '<td>';

			echo '<form action="" method="post" class="ym_ajax_call">
				<input type="hidden" name="action" value="ym_child_package_pack_apply" />
				<input type="hidden" name="child_id" value="' . $child->ID . '" />
				<select class="ym_form_submit_select" name="package_id">
					<option value="-">' . __('Apply Pack', 'ym') . '</option>
				';
				global $ym_packs;
				foreach ($ym_packs->packs as $pack) {
					echo '<option value="' . $pack['id'] . '">' . ym_get_pack_label($pack['id']) . '</option>';
				}
				echo '
			</select>
			';
			echo '</form></td>';

			// quick orpahnise
			echo '<td>';
			echo '<form action="" method="post" class="ym_ajax_call">
				<input type="hidden" name="action" value="ym_child_account_toggle" />
				<input type="hidden" name="child_id" value="' . $child->ID . '" />
				<a href="" class="ym_form_submit" data-html="1">';
			echo ($child->status ? $child->status : '<i>' . __('Not Logged In Yet', 'ym') . '</i>');
			echo '</a></form></td>';

			echo '<td class="ym_user_orphan_' . $child->ID . '">';
			echo '
<div class="ym_ajax_call">
	<input type="hidden" name="action" value="ym_quick_orphan">
	<input type="hidden" name="ym_quick_orphan_user_id" value="' . $child->ID . '">
	<a href="" class="ym_form_submit_clone ym_user_go deletelink" data-html="1" title="' . __('Orphaise the User', 'ym') . '"></a>
</div>
';
			echo '</td>';

			// quick delete
			echo '<td class="ym_user_status_' . $child->ID . '">';
			echo '
<div class="ym_ajax_call">
	<input type="hidden" name="action" value="ym_quick_delete">
	<input type="hidden" name="ym_quick_delete_user_id" value="' . $child->ID . '">
	<a href="" class="ym_form_submit_clone ym_user_delete deletelink" data-html="1" title="' . __('Delete the User', 'ym') . '"></a>
</div>
';
			echo '</td>';

			echo '</tr>';
		}
	}
	$row_index ++;
}
echo '<tr><td colspan="2">';
if ($offset > 0) {
	echo '<a href="?page=' . $_GET['page'] . '&ym_page=' . $_GET['ym_page'] . '&offset=' . ($offset - $limit) . '&ym_search_for_a_user=' . ym_request('ym_search_for_a_user') . '">' . __('Previous Page', 'ym') . '</a>';
}
echo '</td><td colspan="6">';
echo '</td><td colspan="2">';
if ($row_index == $limit) {
	echo '<a href="?page=' . $_GET['page'] . '&ym_page=' . $_GET['ym_page'] . '&offset=' . ($offset + $limit) . '&ym_search_for_a_user=' . ym_request('ym_search_for_a_user') . '" class="alignright">' . __('Next Page', 'ym') . '</a>';
}
echo '</td></tr>';
echo '</table>';

echo ym_end_box();
echo '</div>';

?>

<div id="ym_add_child_to_parent" style="display: none;">
	<form action="" method="post">
		<input type="hidden" id="ym_add_child_to_parent_target" name="ym_add_child_to_parent_target" />
		<?php
	_e('If the Child Account already has a Parent, it will be adopted', 'ym');
	echo '<br />';
	echo '<label for="user_id">' . __('Search for a Child', 'ym');
	echo ' <input type="text" name="search_user_name" id="search_user_name" value="">';
	echo '</label>';
	?>
		<input type="submit" value="<?php _e('Add child to parent', 'ym') ?>" />
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery('.ym_user_add').click(function() {
		jQuery('#ym_add_child_to_parent_target').val(jQuery(this).attr('data-parent'));
		jQuery('#search_user_name').val('');
	});
});
</script>