<?php

/*
 Plugin Name:Your Members - Adaptive Pricing Plugin
 Plugin URI: http://yourmembers.co.uk/
 Description: This Plugin allows Your Members Subscriptions, Pay Per Post's and Post Bundles to be put on Adaptive Pricing Model. You must have YourMembers installed.
 Author: Coding Futures
 Author URI: http://www.yourmembers.co.uk
 Version: 1.3.0
 Depends: yourmembers
 Provides: yourmembers-adaptive
 */


/*
* $Id: ym.php 2140 2012-05-03 14:31:38Z bcarlyon $
* $Revision: 2140 $
* $Date: 2012-05-03 15:31:38 +0100 (Thu, 03 May 2012) $
*/

$plugin_file = __FILE__;
$plugin_name = plugin_basename(__FILE__);// folder/file.php
$plugin_folder = basename(dirname(__FILE__));
$plugin_dir = plugin_dir_path(__FILE__);
$plugin_url = plugin_dir_url(__FILE__);

// Version and Update
define('YM_APP_PLUGIN_VERSION', '1.3.0');
define('YM_APP_PLUGIN_VERSION_ID', '56');
define('YM_APP_PLUGIN_PRODUCT', 'ym_app_single');

// Useful Paths
define('YM_APP_PLUGIN_DIR_PATH', $plugin_dir);
define('YM_APP_PLUGIN_DIR_URL', $plugin_url);

// Comms
$home = site_url('/');
define('YM_APP_INSTALLED_URL', YM_PLUGIN_SITE . 'index.php?nm_action=plugin&product=' . YM_APP_PLUGIN_PRODUCT . '&installed_version=' . YM_APP_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $home);
define('YM_APP_VERSION_CHECK_URL', YM_PLUGIN_SITE . 'index.php?nm_action=version_check&product=' . YM_APP_PLUGIN_PRODUCT . '&installed_versionid=' . YM_APP_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $home . '&beta=' . get_option('ym_beta_notify', 0));

// Useful Paths
define('YM_APP_JS_DIR_URL',YM_APP_PLUGIN_DIR_URL . 'js/');
define('YM_APP_CLASSES_DIR',YM_APP_PLUGIN_DIR_PATH . 'classes/');
define('YM_APP_INCLUDES_DIR',YM_APP_PLUGIN_DIR_PATH . 'includes/');
define('YM_APP_PAGES_DIR',YM_APP_PLUGIN_DIR_PATH . 'pages/');

// URLS
define('YM_APP_ADMIN_INDEX_URL', site_url('wp-admin/admin.php?page=' . $plugin_folder . '/ym-index.php'));


define('YM_APP_META_BASENAME', $plugin_name);

require_once(YM_APP_CLASSES_DIR . 'ym-app-activate.class.php');

require_once(YM_APP_INCLUDES_DIR . 'ym-app.includes.php');
require_once(YM_APP_INCLUDES_DIR . 'ym-app-ppp.includes.php');
require_once(YM_APP_INCLUDES_DIR . 'ym-app-ppp-pack.includes.php');
require_once(YM_APP_INCLUDES_DIR . 'ym-app-subscription.includes.php');
require_once(YM_APP_INCLUDES_DIR . 'ym-app-shortcode.includes.php');

define('YM_APP_TYPE_POST', 0);
define('YM_APP_TYPE_SUB', 1);
define('YM_APP_TYPE_PACK', 2);
define('YM_APP_SALE_SALES', 0);
define('YM_APP_SALE_TIME', 1);

$firetypes = array(
	__('Individual Purchases'),
	__('Package'),
	__('Post Bundles'),
);
$saletypes = array(
	__('Sales'),
	__('Time'),
	__('Hours'),
);

$ym_app_version_resp = '';

global $wpdb;
$wpdb->ym_app_models = $wpdb->prefix . 'ym_app_models';
$wpdb->ym_app_models_tiers = $wpdb->prefix . 'ym_app_models_tiers';
$wpdb->ym_app_ppp_pack = $wpdb->prefix . 'ym_app_ppp_pack';

register_activation_hook(__FILE__, 'ym_app_activate');
register_deactivation_hook(__FILE__, 'ym_app_deactivate');

function ym_app_loaded() {
	ym_firesale_maintain_tiers();

	if (is_admin()) {
		ym_app_check_version();

		// download interrupt
		if (ym_get('app_download', FALSE)) {
			global $ym_app_version_resp;

			$ym_app_version_resp->checkForUpdates();
			$state = get_option($ym_app_version_resp->optionName);

			$download_url = $state->update->download_url;
			header('Location: ' . $download_url);
			exit;
		}

		add_filter('ym_navigation', 'ym_app_menu');
		add_action('ym_additional_context_help', 'ym_app_context_help');

		add_action('admin_head', 'ym_app_styles');
	}

	add_action('login_head', 'ym_firesale_subs');
	add_filter('ym_additional_code', 'ym_firesale_ppp', 10, 3);
	// ppp packs has widget toooo, so can't hook to additional
	add_action('wp_head', 'ym_firesale_ppp_packs');

	add_shortcode('app_counter', 'ym_fire_shortcode_parse');
}

add_action('ym_loaded_complete', 'ym_app_loaded');

function ym_app_menu($navigation) {
	$navigation['Adaptive Pricing'] = 'ym-hook-ym_app';
	add_action('ym-hook-ym_app', 'ym_app_admin_page');
	return $navigation;
}
function ym_app_context_help() {
	$string = '<h2>' . __('Your Members Support', 'ym') . '</h2>';
	$string .= '<p><a href="http://YourMembers.co.uk/forum/">' . __('Get Help and Support from the Forums', 'ym') . '</a></p>';
	$string .= '<p><a href="http://www.yourmembers.co.uk/the-support/guides-tutorials/">' . __('Get Help and Support from the Guides', 'ym') . '</a></p>';
	$string .= '<p>' . sprintf(__('You are running Your Members Adaptive Pricing: %s', 'ym_app'), YM_APP_PLUGIN_VERSION) . '</p>';
	get_current_screen()->add_help_tab(array('id' => 'ym_ypp', 'title' => __('YM Adaptive Pricing', 'ym_app'), 'content' => $string));
}

function ym_app_admin_page() {
	require_once(YM_APP_PAGES_DIR . 'ym_app.php');
	ymfire_admin_page();
}

function ym_app_styles() {
	wp_enqueue_script('ym_app_js', YM_APP_JS_DIR_URL . 'ym_app.js', array('jquery'));
}
