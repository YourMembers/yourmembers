<?php

/*
* $Id: ym-membership-package_types.php 2252 2012-07-18 15:56:20Z bcarlyon $
* $Revision: 2252 $
* $Date: 2012-07-18 16:56:20 +0100 (Wed, 18 Jul 2012) $
*/

echo '<div class="wrap" id="poststuff">';

global $wpdb, $ym_package_types;

if (ym_post('del_btn')) {
	$delete = ym_post('delete_package_type');
	$ym_package_types->delete($delete);

	$to = ym_post('moveto');

	// account type is exposed by default
	$query = 'SELECT user_id
		FROM ' . $wpdb->usermeta . '
		WHERE meta_key = \'ym_account_type\'
		AND meta_value = \'' . $delete . '\'';
	$users = $wpdb->get_results($query);
	foreach ($users as $user) {
		$user = new YourMember_User($user->user_id);
		$user->account_type = $to;
		$user->save();
	}
	
	// find and remove account type from the subscriptions
	// FAILS!!!
	global $ym_packs;
	foreach ($ym_packs->packs as $k => $pack) {
		if ($pack['account_type'] == $delete) {
			$ym_packs->packs[$k]['account_type'] = $to;
		}
	}
	update_option('ym_packs', $ym_packs);
	
	ym_display_message(__('Package Type Deleted', 'ym'));
}

if (ym_post('new_package_type')) {
	$new = strip_tags(ym_post('new_package_type'));

	if (empty($new)) {
		ym_display_message(__('Please specify a package type to create', 'ym'), 'error');
	} else {
		if ($ym_package_types->create($new)) {
			ym_display_message(__('The new Package Type has been created successfully', 'ym'));
		} else {
			ym_display_message(__('The new Package Type already exists', 'ym'), 'error');
		}
	}
}

ym_box_top(__('Package Types', 'ym'));

echo '<form action="" method="post">';
echo '
<table class="form-table">
	<tr class="alternate"> 
		<td style="font-weight: bold;">' . __('Package Type', 'ym') . '</td> 
		<td style="font-weight: bold;">' . __('Delete', 'ym') . '</td> 
	</tr>
';
foreach ($ym_package_types->types as $type) {
	echo '
	<tr class="alternate">
		<td align="center">' . esc_html($type) . '</t>
		<td>';
		if ((strtolower($type) != 'guest') && (strtolower($type) != 'free')) {
			echo '
			<form action="" method="post">
				<input type="hidden" name="delete_package_type" value="' . $type . '" /> ' . __('Delete and move users of this type to', 'ym') . '
				<select name="moveto">
				';
				foreach ($ym_package_types->types as $t) {
					if (strtolower($t) == 'guest') {
						continue;
					}
					if ($t != $type) {
						echo '<option value="'.$t.'">'. esc_html($t) .'</option>';
					}
				}
				echo '
				</select>
				<input type="submit" class="button-secondary" name="del_btn" value="Go" style="font-size: 13px; width: auto;" />
			</form>
			';
		} else {
			echo '-';
		}
		echo '
		</td>
	</tr>
	';
}
echo '</table>';
echo '</form>';

ym_box_bottom();
ym_box_top(__('Create a new Package Type', 'ym'));

echo '
<form action="" method="post">
	<fieldset>
		<table class="form-table">
		<tr><td>
			<label for="new_package_type">' . __('Create a new Package Type', 'ym') . '</label>
		</td><td>
			<input type="text" name="new_package_type" id="new_package_type" value="" />
		</td><td>
			<input type="submit" class="button-primary" value="' . __('Add New Package Type', 'ym') . '" />
		</td></tr>
		</table>
	</fieldset>
</form>
';

ym_box_bottom();
echo '</div>';
