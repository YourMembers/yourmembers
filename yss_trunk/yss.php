<?php
/*
 Plugin Name: Your Members Secure Stream
 Plugin URI: http://yourmembers.co.uk/your-secure-streams/support/
 Description: Provides a secure mechanism for delivering streaming video to your membership sites, Requires hash_hmac on your System to be enabled. Has CloudFront Support
 Version: 2.2.0
 Author: YourMembers.co.uk
 Author URI: http://www.YourMembers.co.uk
 Depends: yourmembers
 Provides: yourmembers-securestream
*/

/*
* $Id: yss.php 2482 2012-12-17 12:21:10Z tnash $
* $Revision: 2482 $
* $Date: 2012-12-17 12:21:10 +0000 (Mon, 17 Dec 2012) $
*/

$yss_file = trailingslashit(str_replace('\\', '/', __FILE__));
$yss_dir = trailingslashit(str_replace('\\', '/', dirname(__FILE__)));
$yss_home = trailingslashit(str_replace('\\', '/', get_bloginfo('wpurl')));

register_activation_hook($yss_file, 'yss_activate');
register_deactivation_hook($yss_file, 'yss_deactivate');

define('YSS_PLUGIN_VERSION', '2.0.1'); //change for each version
define('YSS_PLUGIN_VERSION_ID', '41'); //NMV Version ID for version checker
define('YSS_PLUGIN_PRODUCT', 'yss_single');
define('YSS_PLUGIN_SITE', 'http://www.yourmembers.co.uk/');

define('YSS_PLUGIN_LICENSING', YSS_PLUGIN_SITE . 'index.php?nm_action=activate&product=' . YSS_PLUGIN_PRODUCT . '&host=' . $yss_home);
define('YSS_VERSION_CHECK_URL', YSS_PLUGIN_SITE . 'index.php?nm_action=version_check&product=' . YSS_PLUGIN_PRODUCT . '&installed_versionid=' . YSS_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $yss_home . '&beta=' . get_option('ym_beta_notify', 0));

define('YSS_PLUGIN_DIR_PATH', $yss_dir);
define('YSS_PLUGIN_DIR_URL', trailingslashit(str_replace(str_replace('\\', '/', ABSPATH), $yss_home, $yss_dir)));
define('YSS_PLUGIN_DIRNAME', str_replace('/plugins/','',strstr(YSS_PLUGIN_DIR_URL, '/plugins/')));

define('YSS_CLASSES_DIR',YSS_PLUGIN_DIR_PATH . 'classes/');
define('YSS_INCLUDES_DIR',YSS_PLUGIN_DIR_PATH . 'includes/');
define('YSS_ADMIN_DIR', YSS_PLUGIN_DIR_PATH . 'admin/');

define('YSS_META_BASENAME', plugin_basename(__FILE__));
define('YSS_RESOURCES', YSS_PLUGIN_DIR_URL . 'resources');

define('YSS_EXPIRE_TIME_LIMIT', 5);

$yss_db = $wpdb->prefix."yss_videos";
$yss_post_assoc = $wpdb->prefix."yss_post_assoc";

require_once(YSS_INCLUDES_DIR . 'yss_player_plugins.include.php');
require_once(YSS_INCLUDES_DIR . 'yss_functions.include.php');
require_once(YSS_INCLUDES_DIR . 'yss_resource_functions.includes.php');
require_once(YSS_INCLUDES_DIR . 'yss_admin_functions.include.php');

require_once(YSS_INCLUDES_DIR . 'yss_shortcode.includes.php');

require_once(YSS_CLASSES_DIR . 'cloudfront.php');
require_once(YSS_CLASSES_DIR . 'S3.php');

// sanity check
if (!function_exists('ym_loaded')) {
	// ym gone self kill
	// deactivate using WP's plugin deactivation algorithm
	$current = get_option('active_plugins');
	$key = array_search(YSS_PLUGIN_DIRNAME . basename(__FILE__), $current);
	unset($current[$key]);
	update_option('active_plugins', $current);

	do_action('deactivate_' . YSS_PLUGIN_DIRNAME . 'yss.php');
	return;
}

// This plugin is dependent on ym so I can use its functions....
$yss_bucket = get_option('yss_bucket');
$yss_secret_key = get_option('yss_secret_key');
$yss_user_key = get_option('yss_user_key');

// cloudfront
$yss_cloudfront = new CloudFront();

function yss_loaded() {
	add_shortcode('yss_player', 'yss_shortcode');
	add_shortcode('yss_link', 'yss_hack_link');//VERY BAD VERY VERY BAD! // DEPREICATED
	if (is_admin()) {
		yss_check_version();
	}
	
}
add_action('plugins_loaded', 'yss_loaded');

function yss_plugin_setup() {
	if (is_admin()) {
		wp_enqueue_style( 'plugin-install' );
		wp_enqueue_script( 'plugin-install' );

		add_thickbox();
	}
}
add_action('init', 'yss_plugin_setup');

/*
YM HOOKS
*/
function ym_yss_menu($navigation) {
	if (!current_user_can('administrator')) {//needs to be ym user right level
		return $navigation;
	}
	define('YM_YSS_NAVIGATION_ID', count($navigation));
	$navigation[__('Secure Stream', 'yss')] = array(
		__('Guide', 'yss')			=> 'ym-hook-yss_home',
		__('Content', 'yss')		=> 'ym-hook-yss_content',
//		'S3'			=> 'other_ymyss_s3',
		__('Cloudfront', 'yss')		=> 'ym-hook-yss_cloudfront',
		__('Settings', 'yss')		=> 'ym-hook-yss_settings'
	);

	foreach ($navigation[__('Secure Stream', 'yss')] as $lang => $item) {
		add_action($item, str_replace('ym-hook-', '', $item));
	}
	return $navigation;
}
add_filter('ym_navigation', 'ym_yss_menu');

function yss_home() {
	global $ym_formgen;
	echo '<div class="wrap" id="poststuff"><form action="" method="post">';
	include(YSS_ADMIN_DIR . 'yss_home.php');
	echo '</form></div>';
}
function yss_content() {
	global $ym_formgen;
	echo '<div class="wrap" id="poststuff"><form action="" method="post">';
	include(YSS_ADMIN_DIR . 'yss_content.php');
	echo '</form></div>';
}
function yss_cloudfront() {
	global $ym_formgen;
	echo '<div class="wrap" id="poststuff"><form action="" method="post">';
	include(YSS_ADMIN_DIR . 'yss_cloudfront.php');
	echo '</form></div>';
}
function yss_settings() {
	global $ym_formgen;
	echo '<div class="wrap" id="poststuff"><form action="" method="post">';
	include(YSS_ADMIN_DIR . 'yss_settings.php');
	echo '</form></div>';
}

if (ym_get('buckettask')) {
	include(YSS_ADMIN_DIR . 'yss_s3.php');
	exit;
}
if (ym_get('cloudtask')) {
	include(YSS_ADMIN_DIR . 'yss_cloudfront.php');
	exit;
}

function yss_context_help() {
	$string = '<h2>' . __('Your Members Support', 'yss') . '</h2>';
	$string .= '<p><a href="http://YourMembers.co.uk/forum/">' . __('Get Help and Support from the Forums', 'yss') . '</a></p>';
	$string .= '<p><a href="http://www.yourmembers.co.uk/the-support/guides-tutorials/">' . __('Get Help and Support from the Guides', 'yss') . '</a></p>';

	$string .= '<p>' . sprintf(__('You are running Your Members Your Secure Stream %s on WordPress %s on PHP %s', 'yss'), YSS_PLUGIN_VERSION, YM_WP_VERSION, phpversion()) . '</p>';
	get_current_screen()->add_help_tab(array('id' => 'ym_yss', 'title' => __('Your Secure Stream', 'yss'), 'content' => $string));
}
add_action('ym_additional_context_help', 'yss_context_help');

function yss_wizard_steps($steps) {
	$steps[] = __('Add your Amazon S3 Access Keys', 'yss');
	return $steps;
}
function yss_wizard_links($links) {
	$links[] = 'jQuery(\'#yourmembers\').tabs({selected: \'#ym-top-hook-yss_home\'});jQuery(\'#ym-top-hook-yss_home\').tabs({selected: 3});';
	return $links;
}
add_filter('ym_wizard_steps', 'yss_wizard_steps');
add_filter('ym_wizard_links', 'yss_wizard_links');
