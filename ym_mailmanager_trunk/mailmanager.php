<?php

/*
 Plugin Name:Your Members - MailManager
 Plugin URI: http://YourMembers.co.uk/
 Description: Plugin managed emails and email subscriptions for YourMembers
 Author: CodingFutures
 Author URI: http://www.codingfutures.co.uk
 Version: 2.2.8
 Provides: yourmembers-mailmanager
 Depends: yourmembers
 */

$ym_mm_file = trailingslashit(str_replace('\\', '/', __FILE__));
$ym_mm_dir = trailingslashit(str_replace('\\', '/', dirname(__FILE__)));
$ym_mm_home = trailingslashit(str_replace('\\', '/', get_bloginfo('wpurl')));

$plugin_name = 'MailManager';
define('YM_MM_PLUGIN_VERSION', '2.2.8'); //change for each version
define('YM_MM_PLUGIN_VERSION_ID', '67'); //NMV Version ID for version checker
define('YM_MM_PLUGIN_PRODUCT', 'ym_mailmanager');

define('YM_MM_INSTALLED_URL', YM_PLUGIN_SITE . 'index.php?nm_action=plugin&product=' . YM_MM_PLUGIN_PRODUCT . '&installed_version=' . YM_MM_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $ym_mm_home . '&key=');
define('YM_MM_VERSION_CHECK_URL', YM_PLUGIN_SITE . 'index.php?nm_action=version_check&product=' . YM_MM_PLUGIN_PRODUCT . '&installed_versionid=' . YM_MM_PLUGIN_VERSION_ID . '&ym_version=' . YM_PLUGIN_VERSION_ID . '&host=' . $ym_mm_home . '&beta=' . get_option('ym_beta_notify', 0));

define('YM_MM_PLUGIN_DIR_PATH', $ym_mm_dir);
define('YM_MM_PLUGIN_DIR_URL', trailingslashit(str_replace(str_replace('\\', '/', ABSPATH), $ym_mm_home, $ym_mm_dir)));
define('YM_MM_PLUGIN_DIRNAME', str_replace('/plugins/','',strstr(YM_MM_PLUGIN_DIR_URL, '/plugins/')));

define('YM_MM_CLASSES_DIR',YM_MM_PLUGIN_DIR_PATH . 'classes/');
define('YM_MM_INCLUDES_DIR',YM_MM_PLUGIN_DIR_PATH . 'includes/');
define('YM_MM_PAGES_DIR',YM_MM_PLUGIN_DIR_PATH . 'pages/');

define('YM_MM_GATEWAY_DIR', YM_MM_PLUGIN_DIR_PATH . 'mailgateway/');
define('YM_MM_GATEWAY_URL', YM_MM_PLUGIN_DIR_URL . 'mailgateway/');

define('YM_MM_JS_DIR_URL',YM_MM_PLUGIN_DIR_URL . 'js/');

include(YM_MM_CLASSES_DIR . 'ym_appstore_plugin.php');
include(YM_MM_INCLUDES_DIR . 'mm_invoicing.php');

load_plugin_textdomain(plugin_basename(__FILE__), false, basename($ym_mm_dir) . '/lang' );

register_activation_hook($ym_mm_file, YM_MM_PLUGIN_PRODUCT . '::activate');
register_deactivation_hook($ym_mm_file, YM_MM_PLUGIN_PRODUCT . '::deactivate');

$mm = '';
$mailgateway = '';

function mailmanager_is_loaded() {
	global $ym_mm_file, $plugin_name, $mailmanager_gateways;

	$nav_pages = array(
		__('Broadcast', 'ym_mailmanager')			=> 'mailmanager&mm_action=broadcast',
		__('Create', 'ym_mailmanager')				=> 'mailmanager&mm_action=create',
		__('View Emails', 'ym_mailmanager')			=> 'mailmanager&mm_action=emails',
		__('Series', 'ym_mailmanager')				=> 'mailmanager&mm_action=series',
		__('Welcome Message', 'ym_mailmanager')		=> 'mailmanager&mm_action=welcome',
		__('Invoicing', 'ym_mailmanager')			=> 'mailmanager&mm_action=invoice',
		__('Settings', 'ym_mailmanager')			=> 'mailmanager&mm_action=settings',
		__('Gateways', 'ym_mailmanager')			=> 'mailmanager&mm_action=gateways',
	);
	mailmanager_load_active_gateway($nav_pages);
	
	$plugin = YM_MM_PLUGIN_PRODUCT;
	$plugin = new $plugin(__FILE__, YM_MM_INSTALLED_URL, YM_MM_VERSION_CHECK_URL, $plugin_name, $nav_pages, YM_MM_PLUGIN_DIRNAME);
	if (!$plugin->sane) {
		return;
	}
	$plugin->version_check(TRUE);
	$other_action = '';
	add_action('ym_admin_other', 'mailmanager_admin', $other_action);
	global $mm;
	$mm = $plugin;
	
	//add_action('init', 'mailmanager_unsub_check', 9);
	mailmanager_unsub_check();
	
	ym_mm_invoice_init();
	
	if (ym_get('iframe_preview')) {
		include(YM_MM_PAGES_DIR . 'preview.php');
		exit;
	}
}
add_action('init', 'mailmanager_is_loaded');// on init for sanity check priority

include(YM_MM_INCLUDES_DIR . 'mm_includes.php');

$current_welcome = get_option('ym_other_mm_welcome');
if ($current_welcome->enable == 1) {
	include(YM_MM_INCLUDES_DIR . 'mm_welcome.php');
}

if (@$_GET['mm_action']) {
	add_action('init', 'mailmanager_script');
}

function mailmanager_script() {
	wp_enqueue_script('ym_mm_js', YM_MM_JS_DIR_URL . 'mm.js', 'jquery');
}

function mailmanager_admin($other_action) {
	global $mm, $wpdb;
	$current_settings = get_option('ym_other_mm_settings');
	
	if ($other_action == 'mailmanager') {
		$mm_action = ym_get('mm_action');
		
		echo '<div class="wrap" id="poststuff" style="width: 98%;">';
		
		if (!$current_settings->first_run_done) {
			$mm_action = 'gateways';
		}
		
		do_action('mailmanager_precontent');
		
		switch ($mm_action) {
			case 'gateways':
			case 'settings':
			case 'broadcast':
			case 'create':
			case 'emails':
			case 'preview':
			case 'series':
			case 'welcome':
			case 'invoice':
				include(YM_MM_PAGES_DIR . $mm_action . '.php');
				wp_tiny_mce(false,
					array(
						'editor_selector'	=> 'editorContent'
					)
				);
				break;
			case 'gateway':
				$file = $current_settings->mail_gateway;
				$break = FALSE;
				// just in case it should be already loaded from the generation of the settings menu
				require_once(YM_MM_GATEWAY_DIR . $file . '/' . $file . '.php');
				// invoke settings page
				global $mailgateway;
				if ($mailgateway->settings) {
					$mailgateway->settings($break);
				}
				wp_tiny_mce(false,
					array(
						'editor_selector'	=> 'editorContent'
					)
				);
				if ($break) {
					break;
				}
			case 'runnow':
				if ($mm_action == 'runnow') {
					ym_box_top(__('MailManger: Cron', 'ym_mailmanager'));
					echo '<p>';
					do_action('mailmanager_cron_check');
					echo '</p>';
					echo '<p>' . __('The run of the cron is complete', 'ym_mailmanager') . '</p>';
					ym_box_bottom();
				}
			default:
			
				echo '<div style="width: 49%; float: left;">';
				ym_box_top(__('MailManger', 'ym_mailmanager'));
				
				mailmanager_email_stats();
				
				$sch_using = wp_get_schedule('mailmanager_cron_check');
				$next = wp_next_scheduled('mailmanager_cron_check');
				if (!$sch_using) {
					$now = time();
					if ($current_settings->series_hour < date('H', $now)) {
						// the hour has passed schedule for tomorrow
						$now = $now + 86400;
					}
					$next = mktime($current_settings->series_hour, $current_settings->series_min, 59, date('n', $now), date('j', $now), date('Y', $now));
					wp_schedule_event($next, 'daily', 'mailmanager_cron_check');
				} else {
					echo '<p>' . sprintf(__('The Cron is set to check %s and will next run at %s', 'ym_mailmanager'), $sch_using, date('r', $next)) . '</p>';
				}
				echo '<p>' . sprintf(__('Run the <a href="%s&mm_action=runnow">cron now</a>', 'ym_mailmanager'), $mm->page_root) . '</p>';
				
				do_action('mailmanager_homepage');
				
				ym_box_bottom();
				echo '</div>';
				echo '<div style="width: 49%; float: right">';
				mailmanager_list_stats();
				echo '</div>';
		}
		echo '</div>';
	}
}

add_filter('ym_additional_context_help', 'ym_mm_context_help', 10, 1);
function ym_mm_context_help($content) {
	$content .= '<p>' . sprintf(__('You are running Your Members Mailmanager Version: %s', 'ym_mailmanager'), YM_MM_PLUGIN_VERSION) . '</p>';
	return $content;
}
