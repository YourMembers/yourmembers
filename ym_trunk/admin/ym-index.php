<?php

/*
* $Id: ym-index.php 2198 2012-05-31 13:20:46Z bcarlyon $
* $Revision: 2198 $
* $Date: 2012-05-31 14:20:46 +0100 (Thu, 31 May 2012) $
*/

/**
Uninstall/deactivate handler
Normally fired from the links 
*/
if (ym_get('ym_uninstall') || ym_get('ym_deactivate')) {
	$current = get_option('active_plugins');
	array_splice($current, array_search(YM_META_BASENAME, $current), 1);
	update_option('active_plugins', $ym_pre_admin_loadercurrent);
	do_action('deactivate_' . YM_META_BASENAME);
	
	$connection_string = YM_DEACTIVATE_URL . '&email=' . get_option('ym_license_key');
	$response = ym_remote_request($connection_string);
	delete_option('ym_license_key');
	delete_option('ym_tos_version_accepted');
	
	if (ym_get('ym_uninstall')) {
		// nuke it all!
		ym_deactivate();
	}
	
	echo '<meta http-equiv="refresh" content="0;plugins.php?deactivate=true" />';
	exit;
}

// Database updates can be called on this hook
do_action('ym_pre_admin_loader');

get_currentuserinfo();
global $current_user, $ym_auth, $ym_res, $ym_sys;

$ym_page = ym_get('ym_page');

if ($ym_auth->ym_check_key()) {
	if (!ym_tos_checks()) {
		// no TOS stuff in progress
		echo '<div class="wrap"><h2>' . YM_ADMIN_NAME . '</h2>';

		// wizard
		ym_wizard_render();

		// loop
		ym_admin_menu();
		echo '<div style="clear: both; padding: 0px; margin: 0px;">';
		ym_admin_loader();
		echo '</div>';
		ym_admin_menu_end();

		// end
		echo '</div>';
	}
} else {
	global $ym_version_resp;
	
	ym_check_version(true);

	// ym conf hook
	if (!isset($_POST['activate_plugin'])) {
		ym_check_for_ymconf();
	}

	$auth_key_result = false;
	// no key
	if (ym_post('activate_plugin', false) && ym_post('registration_email', false)) {
		$auth_key_result = $ym_auth->ym_authorize_key(ym_post('registration_email'));
	}

	global $ym_version_resp;
	if (!is_wp_error($auth_key_result) && ym_post('registration_email')) {
		// key ok TOS check
		ym_tos_checks();
	} else {
		echo '
<div class="wrap" id="poststuff">
	<h2>' . YM_ADMIN_NAME . '</h2>
	<div id="message" class="error ym_auth">
	';

		if (is_wp_error($auth_key_result)) {
			echo '<div style="margin: 5px 0px; color:red; font-weight:bold;">';
			echo $auth_key_result->get_error_message();
			echo '</div>';
		}

		echo '<p><strong>' . YM_ADMIN_NAME . '</strong> ' . __('will not function until a valid Email has been entered.<br />Please enter the <strong>email address</strong> you used to purchase the plugin in the box below to activate it.','ym') . '</p>';
		if (YM_ADMIN_NAME == 'Your Members') {
			echo '<p>' . __('If you don\'t have a valid copy then please visit <a href=\'http://www.yourmembers.co.uk\'>http://www.YourMembers.co.uk</a> to purchase one.','ym') . '</p>';
		}
		echo '
	</div>';
		echo ym_start_box(YM_ADMIN_NAME . ' Activation', 'ym');

		echo '
		<form action="" method="post">
			<fieldset>
				<table class="form-table">
					<tr>
						<th><label for="registration_email">' . __('Email/Activation Code', 'ym') . '</label></th>
						<td><input type="text" name="registration_email" id="registration_email" style="width: 300px;" /></td>
					</tr><tr>
						<td></td><td>
							<p class="submit" style="text-align: right;">
								<input type="submit" name="activate_plugin" value="' . __('Activate', 'ym') . '" class="button-primary" />
							</p>
						</td>
					</tr>
				</table>
			</fieldset>
		</form>
		';

		echo ym_end_box();
	echo '
</div>
';
	}

do_action('ym_post_admin_loader');

}
