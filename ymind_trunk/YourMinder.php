<?php
/*
Plugin Name: Your Minder
Version: 2.2
Description: Stops multiple logins from occuring by logging out the user on page load if their ip differs from the one on file
Author: Cambrige New Media Services
Author URI: http://www.newmedias.co.uk
Plugin URI: http://www.newmedias.co.uk
*/

$ymind_file = trailingslashit(str_replace('\\', '/', __FILE__));
$ymind_dir = trailingslashit(str_replace('\\', '/', dirname(__FILE__)));
$ymind_site_home = trailingslashit(str_replace('\\', '/', get_bloginfo('wpurl')));

define('YMIND_PLUGIN_VERSION', '2.2'); //change for each version
define('YMIND_PLUGIN_PRODUCT', 'ymind_single');
define('YMIND_PLUGIN_SITE', 'http://www.yourmembers.co.uk/');

define('YMIND_PLUGIN_DIR_PATH', trailingslashit($ymind_dir));
define('YMIND_PLUGIN_DIR_URL', trailingslashit(str_replace(str_replace('\\', '/', ABSPATH), get_bloginfo('wpurl').'/', $ymind_dir)));
define('YMIND_PLUGIN_DIRNAME', str_replace('/plugins/','',strstr(YMIND_PLUGIN_DIR_URL, '/plugins/')));

define('YMIND_PLUGIN_VERSION_ID', '26'); //NMV Version ID for version checker
define('YMIND_VERSION_CHECK_URL', YMIND_PLUGIN_SITE . 'index.php?nmvc_action=check_version&version_id=' . YMIND_PLUGIN_VERSION_ID);
define('YMIND_MESSAGE_CHECK_URL', YMIND_PLUGIN_SITE . 'index.php?nmvc_action=get_message&product=' . YMIND_PLUGIN_PRODUCT . '&version_id=' . YMIND_PLUGIN_VERSION_ID);

define('YMIND_PLUGIN_LICENSING', YMIND_PLUGIN_SITE . 'index.php?nma_action=activate&');
define('YMIND_PLUGIN_LICENSING_OLD','http://www.newmedias.co.uk/license/ymcheckold/ym_license_checker.php?');

define('YMIND_LICENCE_CHECK_URL','product=' . YMIND_PLUGIN_PRODUCT . '&host=' . $ymind_site_home);

define('YMIND_ADMIN_DIR',YMIND_PLUGIN_DIR_PATH . 'admin/');
define('YMIND_CLASSES_DIR',YMIND_PLUGIN_DIR_PATH . 'classes/');
define('YMIND_INCLUDES_DIR',YMIND_PLUGIN_DIR_PATH . 'includes/');

define('YMIND_SQL_IMPORT_FILE', YMIND_INCLUDES_DIR . 'ymind_tables.sql' );

define('YMIND_ADMIN_DIR_URL', $ymind_site_home . 'wp-admin/');
define('YMIND_ADMIN_INDEX_URL', YMIND_ADMIN_DIR_URL . 'admin.php?page='.YMIND_ADMIN_DIR .'ymind_admin.php');

require_once(YMIND_CLASSES_DIR . 'ymind_auth.class.php');

require_once(YMIND_INCLUDES_DIR . 'ymind_admin.include.php');
require_once(YMIND_INCLUDES_DIR . 'ymind_admin_output.include.php');
require_once(YMIND_INCLUDES_DIR . 'ymind_common.include.php');
require_once(YMIND_INCLUDES_DIR . 'ymind_initialise.include.php');

$ymind_root = '';
$ymind_auth = new ymind_auth();

$ymind_pages = array(
__('Settings','ymind')=>YMIND_ADMIN_DIR.'ymind_settings.php'
, __('Blocked Members','ymind')=>YMIND_ADMIN_DIR.'ymind_members.php'
, __('Blocked IPs','ymind')=>YMIND_ADMIN_DIR.'ymind_blocklist.php'
);

add_action('admin_menu', 'ymind_admin_page_setup');

if ($ymind_auth->check_key()) {
    add_filter('wp_authenticate_user', 'ymind_log_ip');
    add_filter('the_content', 'ymind_filter_post', 50);
    add_action('init','ymind_ip_check');
} else {
	$dir = YMIND_PLUGIN_DIR_URL;
	$dir = explode('/', $dir);
	array_pop($dir);
	$dir = array_pop($dir);
	if ($_GET['page'] == $dir .'/admin/ymind_admin.php') {
		ymind_check_activate_account();
	} else {
		add_action('admin_notices', 'ymind_nag_box');
	}
}

function ymind_nag_box() {
	$dir = YMIND_PLUGIN_DIR_URL;
	$dir = explode('/', $dir);
	array_pop($dir);
	$dir = array_pop($dir);
	
	echo '<div class="update-nag"><a href="admin.php?page=' . $dir . '/admin/ymind_admin.php">You need to activate YourMinder</a></div>';
}

register_activation_hook(str_replace('\\', '/', $ymind_file), 'ymind_activate');
register_deactivation_hook(str_replace('\\', '/', $ymind_file), 'ymind_deactivate');

