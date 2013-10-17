<?php

/*
* $Id: ym-about.php 2452 2012-12-03 11:12:24Z bcarlyon $
* $Revision: 2452 $
* $Date: 2012-12-03 11:12:24 +0000 (Mon, 03 Dec 2012) $
*/

if (ym_request('do_munch') && ym_request('download')) {
	ym_check_version();
	
	global $ym_update_checker;

	$ym_update_checker->checkForUpdates();
	$state = get_option($ym_update_checker->optionName);

	$download_url = $state->update->download_url;

	header('Location: ' . $download_url);
	exit;
}
if (ym_request('do_munch') && ym_request('download_beta')) {
	ym_check_version();
	
	global $ym_update_checker, $ym_version_resp;

	$ym_update_checker->checkForUpdates();

	if ($ym_version_resp->version->beta_download_url) {
		header('Location: ' . $ym_version_resp->version->beta_download_url);
		exit;
	}
}

global $wp_version, $ym_version_resp, $wpdb, $ym_update_checker;

$do_check = ym_post('ym_do_version_check');
$check_step = ym_post('ym_do_version_check_step');

$do_beta_toggle = ym_post('ym_do_toggle_beta');
if ($do_beta_toggle) {
	$beta_enable = get_option('ym_beta_notify');
	if ($beta_enable) {
		delete_option('ym_beta_notify');
		$message = __('You will not be notified of Betas', 'ym');
	} else {
		update_option('ym_beta_notify', TRUE);
		$message = __('You will be notified of Betas', 'ym');
	}
	echo '<div id="message" class="updated"><p>' . $message . '</p></div>';
	$do_check = TRUE;
	$check_step = 1;
}


if ($do_check) {
	switch ($check_step) {
		case '2';
			break;
		case '1':
		default:
			delete_option('external_updates-ym');
			delete_option('ym_vc');
			// step 2
			echo '
<form action="" method="post" id="ym_manual_version_check">
	<input type="hidden" name="ym_do_version_check" value="true" />
	<input type="hidden" name="ym_do_version_check_step" value="2" />
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery(\'#ym_manual_version_check\').submit();
		});
	</script>
</form>
';
			return;
	}
}

if (is_object($ym_update_checker)) {
	$ym_update_checker->checkForUpdates();
	$state = get_option($ym_update_checker->optionName);

	$download_url = YM_ADMIN_URL . '_about&do_munch=1&download=1';
	$reinstall = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&reinstall=1&plugin=' . YM_META_BASENAME), 'upgrade-plugin_' . YM_META_BASENAME);

	if ($ym_version_resp->version->beta_upgrade_available) {
		$download_beta_url = YM_ADMIN_URL . '_about&do_munch=1&download_beta=1';
		echo '<div id="message" class="updated"><p>' . sprintf(__('A Beta Version is available, <strong>V%s</strong> - <a href="%s">Download</a>', 'ym'), $ym_version_resp->version->beta_version_id, $download_beta_url) . '</p></div>';
	}


	if (isset($state->update) && version_compare($state->update->version, $ym_update_checker->getInstalledVersion(), '=')) {
		echo '<div id="message" class="updated"><p>You are running the latest version</a></p></div>';
		
		echo '<div id="message" class="updated"><p>' . sprintf(__('You can Reinstall the lastest version <strong>V%s</strong> - <a href="%s">Reinstall</a> or <a href="%s">Download</a>', 'ym'), $state->update->version, $reinstall, $download_url) . '</p></div>';
	} else if (isset($state->update) && version_compare($state->update->version, $ym_update_checker->getInstalledVersion(), '>')) {
		// then a update is available
		$url = wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . YM_META_BASENAME), 'upgrade-plugin_' . YM_META_BASENAME);
		echo '<div id="message" class="updated"><p>' . sprintf(__('An Update is available, the latest version is <strong>V%s</strong> - <a href="%s">Update Now</a> or <a href="%s">Download</a>', 'ym'), $state->update->version, $url, $download_url) . '</p></div>';
	} else if (isset($state->update) && version_compare($state->update->version, $ym_update_checker->getInstalledVersion(), '<')) {
		echo '<div id="message" class="updated"><p>' . sprintf(__('You are running a newer version, <a href="%s">Reinstall Latest (V%s)</a> or <a href="%s">Download</a>', 'ym'), $reinstall, $state->update->version, $download_url) . '</p></div>'; 
	}
} else {
	if (!defined('DISABLE_VERSIONING')) {
		define('DISABLE_VERSIONING', TRUE);
	}
}

echo '
<div class="wrap" id="poststuff">
<h2>' . YM_ADMIN_NAME . '</h2>
';

ym_box_top(__('About', 'ym') . ' ' . YM_ADMIN_NAME);

echo '
<p style="text-align: center;">
<img src="' . YM_IMAGES_DIR_URL . 'logo.png" alt="' . YM_ADMIN_NAME . ' Logo" />
</p>
';

echo '
<p>
' . YM_ADMIN_NAME . ' Version ' . YM_PLUGIN_VERSION . '
</p><p>
' . YM_ADMIN_NAME . ' is Copyright <a href="http://CodingFutures.co.uk/">Coding Futures Ltd</a> all rights reserved (' . date('Y', time()) . ')
</p><p>
Support Information: PHP Version ' . phpversion() . ', WordPress version: ' . $wp_version . '
</p><p>
A copy of the End User License Agreement should be included in the download zip file, one can be obtained from ';

if (defined('DISABLE_VERSIONING')) {
	echo 'sales@codingfutures.co.uk';
} else {
	echo '<a href="' . $ym_version_resp->tos->tos_text_url . '">here</a>';
}

echo '
</p><p>
Support is available <a href="http://www.yourmembers.co.uk/">Here</a>
</p>
';

if (!defined('DISABLE_VERSIONING')) {
	// manual version check
	echo '
<form action="" method="post" id="ym_manual_version_check">
<input type="hidden" name="ym_do_version_check" value="true" />
<p>You can perform a <a href="#nowhere" onclick="jQuery(\'#ym_manual_version_check\').submit()">manual version check</a></p>
</form>
<form action="" method="post" id="ym_beta_enable">
<input type="hidden" name="ym_do_toggle_beta" value="true" />
<p><a href="#nowhere" onclick="jQuery(\'#ym_beta_enable\').submit()">
';

	$beta_enable = get_option('ym_beta_notify');
	if ($beta_enable) {
		echo 'You will be notified of Betas, Click to Disable';
	} else {
		echo 'You will not be notified of Betas, Click to Enable';
	}

	echo '
</a></p>
</form>
';
}

echo '
<p>You can <a href="' . YM_ADMIN_URL . '&wizard=0">reset the Set Up Wizard</a></p>
';

ym_box_bottom();

echo '</div>';
