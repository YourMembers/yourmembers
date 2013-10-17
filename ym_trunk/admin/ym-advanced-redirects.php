<?php

// gather
$fields = array();

//$login_redirect_url, $wpadmin_disable_redirect_url, $logout_redirect_url, $membership_details_redirect_url

global $ym_sys;
$fields['login_redirect_url'] = $ym_sys->login_redirect_url;
$fields['wpadmin_disable_redirect_url'] = $ym_sys->wpadmin_disable_redirect_url;
$fields['logout_redirect_url'] = $ym_sys->logout_redirect_url;

global $ym_packs;
foreach ($ym_packs->packs as $pack) {
	
	$fields['ym_pack_' . $pack['id'] . '-login_redirect_url'] = $pack['login_redirect_url'];
	$fields['ym_pack_' . $pack['id'] . '-wpadmin_disable_redirect_url'] = $pack['wpadmin_disable_redirect_url'];
	$fields['ym_pack_' . $pack['id'] . '-logout_redirect_url'] = $pack['logout_redirect_url'];
	$fields['ym_pack_' . $pack['id'] . '-first_login'] = $pack['first_login'];
	$fields['ym_pack_' . $pack['id'] . '-success_redirect'] = $pack['success_redirect'];

}

if ($_POST) {
	foreach ($fields as $key => $field) {
		$fields[$key] = $_POST[$key];

		if (substr($key, 0, 8) == 'ym_pack_') {
			list($pack_id, $item) = explode('-', substr($key, 8));
			$pack = ym_get_pack_by_id($pack_id);
			$pack[$item] = $_POST[$key];

			foreach ($ym_packs->packs as $ref_id => $old_pack) {
				if ($old_pack['id'] == $pack['id']) {
					$ym_packs->packs[$ref_id] = $pack;
				}
			}
		}
	}
	update_option('ym_packs', $ym_packs);
	ym_display_message(__('Packs Updated','ym'));

	// core
	$ym_sys->login_redirect_url = $fields['login_redirect_url'];
	$ym_sys->wpadmin_disable_redirect_url = $fields['wpadmin_disable_redirect_url'];
	$ym_sys->logout_redirect_url = $fields['logout_redirect_url'];

	update_option('ym_sys', $ym_sys);
	
	ym_display_message(__('System Updated','ym'));
}

echo '<div class="wrap" id="poststuff">
<form action="" method="post">
';

echo ym_box_top(__('Redirects Control', 'ym'));
echo '<p>' . __('This page Collects together all Redirect options, that are duplicated/refined by Pack', 'ym') . '</p>';

echo '<table>
<tr>
	<th style="width: 300px">' . __('Redirect For', 'ym') . '</th><td style="min-width: 700px;"></td>
</tr>
';

$last_pack_id = FALSE;
foreach ($fields as $key => $value) {
	echo '
<tr>
';

	echo ' ';
	if (substr($key, 0, 8) == 'ym_pack_') {
		list($pack_id, $item) = explode('-', substr($key, 8));
		if (!$last_pack_id || $last_pack_id != $pack_id) {
			echo '<td colspan="2"><input type="submit" class="button-secondary" value="' . __('Save', 'ym') . '" style="float: right;" /><hr /><br /></td></tr><tr>';
			$last_pack_id = $pack_id;
		}
		echo '<th>';
		echo 'Pack: ';
		echo ym_get_pack_label($pack_id);
		echo ':<br />';
		echo ucwords(str_replace('_', ' ', $item));
	} else {
		echo '<th>' . __('Base/Default', 'ym') . ': ';
		echo ucwords(str_replace('_', ' ', $key));
	}

	echo '</th>
	<td style="vertical-align: top;">' . site_url() . '
		<input class="ym_input" style="width: 400px;" name="' . $key . '" value="' . $value . '" />
	</td>
</tr>
	';
}

echo '</table>';
echo '<input type="submit" class="button-primary" value="' . __('Save', 'ym') . '" style="float: right;" />';

echo ym_box_bottom();
echo '</div></div>';
