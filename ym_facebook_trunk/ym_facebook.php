<?php

/*
 Plugin Name:Your Members - Facebook Integration
 Plugin URI: http://YourMembers.co.uk/
 Description: Integrate with Facebook
 Author: CodingFutures
 Author URI: http://www.YourMembers.co.uk
 Version: 0.6.1
 Depends: yourmembers
 Provides: yourmembers-facebook
 */

/*
* $Id: ym_facebook.php 2482 2012-12-17 12:21:10Z tnash $
* $Date: 2012-12-17 12:21:10 +0000 (Mon, 17 Dec 2012) $
* $Revision: 2482 $
* $Author: tnash $
*/

/*
* jQuery yMenu Used under MIT/GPL Compliant License
* http://www.mewsoft.com/jquery/ymenu/
*/

define('YM_FBOOK_BASE_DIR', dirname(__FILE__) . '/');
define('YM_FBOOK_BASE_URL', trailingslashit(plugins_url('', __FILE__)));

include(YM_FBOOK_BASE_DIR . 'classes/facebook.php');
include(YM_FBOOK_BASE_DIR . 'includes/ym_facebook_functions.php');
include(YM_FBOOK_BASE_DIR . 'includes/ym_facebook_auth.php');
include(YM_FBOOK_BASE_DIR . 'includes/ym_facebook_template.php');
include(YM_FBOOK_BASE_DIR . 'includes/ym_facebook_shortcode.php');

define('YM_FB_PLUGIN_VERSION', '0.6.1'); //change for each version
define('YM_FB_PLUGIN_VERSION_ID', '59'); //NMV Version ID for version checker
define('YM_FB_PLUGIN_PRODUCT', 'ym_facebook');
define('YM_FB_PLUGIN_SITE', 'http://www.yourmembers.co.uk/');
$ym_home = trailingslashit(str_replace('\\', '/', get_bloginfo('wpurl')));
define('YM_FB_VERSION_CHECK_URL', YM_PLUGIN_SITE . 'index.php?nm_action=version_check&product=' . YM_FB_PLUGIN_PRODUCT . '&installed_versionid=' . YM_FB_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $ym_home . '&beta='  . get_option('ym_beta_notify', 0));

define('YM_FB_META_BASENAME', plugin_basename(__FILE__));

load_plugin_textdomain(YM_FB_META_BASENAME, false, basename(YM_FBOOK_BASE_DIR) . '/lang' );

if (ym_get('fb_channel')) {
	ym_fb_channel();
	exit;
}

$facebook_settings = '';

add_action('init', 'ym_fbook_init');
add_action('init', 'ym_fbook_admin_init');
//add_action('plugins_loaded', 'ym_fbook_versioncheck');
add_action('plugins_loaded', 'ym_fbook_sanity_check');
register_activation_hook(YM_FB_META_BASENAME, 'ym_fbook_activate_check');

// shortcodes
add_shortcode('ym_fb_force_facebook', 'ym_enter_facebook');
add_shortcode('ym_fb_force_leave_facebook', 'ym_leave_facebook');
add_shortcode('ym_fb_profile', 'ym_fbook_profile');
add_shortcode('ym_fb_app_string', 'ym_fb_app_string');

add_shortcode('ym_fb_if_in_facebook', 'ym_if_in_facebook');
add_shortcode('ym_fb_if_not_in_facebook', 'ym_if_not_in_facebook');
add_shortcode('ym_fb_if_in_facebook_page', 'ym_if_in_facebook_page');
add_shortcode('ym_fb_if_not_in_facebook_page', 'ym_if_not_in_facebook_page');

add_shortcode('ym_fb_user_status', 'ym_fbook_user_status');
add_shortcode('ym_fb_leave_facebook', 'ym_fbook_leave_facebook');

add_shortcode('ym_fb_if_like', 'ym_fbook_if_like');
add_shortcode('ym_fb_if_not_like', 'ym_fbook_if_not_like');

add_shortcode('ym_fb_like_wall_like', 'ym_fbook_like_wall_like');
add_shortcode('ym_fb_like_wall_notlike', 'ym_fbook_like_wall_notlike');

add_shortcode('ym_fb_fan_gate_like', 'ym_fbook_fan_gate_like');
add_shortcode('ym_fb_fan_gate_notlike', 'ym_fbook_fan_gate_notlike');

add_shortcode('ym_fb_wp_logged_in', 'ym_fbook_wp_logged_in');
add_shortcode('ym_fb_wp_not_logged_in', 'ym_fbook_wp_not_logged_in');
add_shortcode('ym_fb_fb_logged_in', 'ym_fbook_fb_logged_in');
add_shortcode('ym_fb_fb_not_logged_in', 'ym_fbook_fb_not_logged_in');

add_shortcode('ym_fb_like', 'ym_fbook_render_like_button_shortcode');

add_shortcode('ym_fb_width', 'ym_fbook_width');
add_shortcode('ym_fb_hide_nav', 'ym_fbook_hide_nav');
add_shortcode('ym_fb_no_comments', 'ym_fbook_no_comments');

add_action('wp_head', 'ym_fbook_og');
// content
add_filter('the_content', 'ym_fbook_render_like_button_filter');
// comments
add_filter('comments_template', 'ym_fbook_fbook_wall');

add_action('ym_fbook_init', 'ym_fbook_fbook_init');
add_action('wp_footer', 'ym_fbook_analytics');

function ym_fbook_admin_init() {
	if (is_admin()) {
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('jquery-ui', 'https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.all.css');
		// we are in the admin system lets load the admin menu(s)
		include(YM_FBOOK_BASE_DIR . '/admin/ym_facebook.php');
	}
}

add_filter('ym_additional_context_help', 'ym_fbook_context_help', 10, 1);
function ym_fbook_context_help($content) {
	$content .= '<p>You are running Your Members Facebook Integration Version: ' . YM_FB_PLUGIN_VERSION . '</p>';
	return $content;
}
