<?php

function ym_app_activate() {
	if (!function_exists('ym_loaded')) {
		// errror
		echo '<strong>Your Members - Adaptive Pricing Plugin</strong>';
		echo '<p>YourMembers does not appear to be installed. <a href="http://yourmembers.co.uk/">YourMembers</a> is required to use Your Members - Adapative Pricing Plugin, visit <a href="http://yourmembers.co.uk/">YourMembers</a> to purchase</p>';
		die();
	}
	// APP installed
	ym_remote_request(YM_APP_INSTALLED_URL);
	
	// create a log action to use
	global $wpdb;

	if (!defined('YM_APP')) {
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_transaction_action(name, description) VALUES (\'APP\', \'Pricing Models Updated YM Data\')';
		$wpdb->query($sql);
		$log_id = $wpdb->insert_id;
	}
	
	if (!defined('YM_APP_TIERCHANGE')) {
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_transaction_action(name, description) VALUES (\'APP TierChange\', \'Pricing Models Change Tiers\')';
		$wpdb->query($sql);
		$log_id = $wpdb->insert_id;
	}
	
	// sql
	$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->ym_app_models . ' (
	  fire_id int(11) NOT NULL AUTO_INCREMENT,
	  fire_name varchar(255) NOT NULL,
	  fire_type int(1) NOT NULL DEFAULT "0",
	  fire_type_id int(11) NOT NULL,
	  fire_enable int(1) NOT NULL DEFAULT "0",
	  fire_end_option int(1) NOT NULL DEFAULT "0",
	  PRIMARY KEY (fire_id)
	)';
	$wpdb->query($sql);

	$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->ym_app_models_tiers . ' (
	  fire_tier_id int(11) NOT NULL AUTO_INCREMENT,
	  fire_id int(11) NOT NULL,
	  fire_price double(6,2) NOT NULL DEFAULT "0.00",
	  fire_limit_by int(1) NOT NULL DEFAULT "0",
	  fire_limit_var int(11) NOT NULL,
	  fire_order int(11) NOT NULL,
	  fire_tier_started int(11) NOT NULL DEFAULT "0",
	  fire_tier_option int(1) NOT NULL DEFAULT "0",
	  PRIMARY KEY (fire_tier_id)
	);';
	$wpdb->query($sql);
	
	$sql = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->ym_app_ppp_pack . ' (
	  pack_id int(11) NOT NULL,
	  original_cost int(11) NOT NULL,
	  UNIQUE KEY pack_id (pack_id)
	)';
	$wpdb->query($sql);
}
function ym_app_deactivate() {}

function ym_app_check_version() {
	global $ym_app_version_resp, $ym_auth;
	
	$url = str_replace('version_check', 'metafile', YM_APP_VERSION_CHECK_URL);
	$url = $url . '&key=' . $ym_auth->ym_get_key();
	$ym_app_version_resp = new PluginUpdateChecker($url, YM_APP_META_BASENAME);
		
	add_action('after_plugin_row_' . YM_APP_META_BASENAME, 'ym_app_download_version', 10, 3);
}
function ym_app_download_version($plugin_file, $plugin_data, $plugin_status) {
	if ($plugin_file != YM_APP_META_BASENAME) {
		return;
	}

	global $ym_app_version_resp;
	$ym_app_version_resp->checkForUpdates();
	$state = get_option($ym_app_version_resp->optionName);
	if (isset($state->update) && version_compare($state->update->version, $ym_app_version_resp->getInstalledVersion(), '>')) {
		$download_url = YM_APP_ADMIN_INDEX_URL . '&app_download=1';
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">' . sprintf(__('An Update for Your Members APP is available, you can auto update below or <a href="%s">download the Update</a>', 'ym'), $download_url) . '</div></td></tr>';
	}
	return;
}
