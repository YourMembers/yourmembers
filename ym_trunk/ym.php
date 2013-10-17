<?php
/*
 Plugin Name:Your Members - Membership Management Plugin
 Plugin URI: http://YourMembers.co.uk/
 Description: Allows paid subscription and Membership services within WordPress
 Author: CodingFutures
 Author URI: http://www.YourMembers.co.uk
 Version: 12.0.6
 Provides: yourmembers
 License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/
/*  Copyright 2013  Coding Futures

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*
* $Id: ym.php 2617 2013-08-05 14:19:40Z tnash $
* $Revision: 2617 $
* $Date: 2013-08-05 15:19:40 +0100 (Mon, 05 Aug 2013) $
*/

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

$plugin_file = __FILE__;
$plugin_name = plugin_basename(__FILE__);
$plugin_folder = basename(dirname(__FILE__));
$plugin_dir = plugin_dir_path(__FILE__);
$plugin_url = plugin_dir_url(__FILE__);

// Version and Update
define('YM_PLUGIN_VERSION', '12.0.6');
define('YM_PLUGIN_VERSION_ID', '74');
define('YM_DATABASE_VERSION', 9);

// Some Names/white
define('YM_ADMIN_FUNCTION', 'ym');
define('YM_ADMIN_NAME', 'Your Members');
define('YM_PLUGIN_PRODUCT', 'ym_single');

define('YM_PLUGIN_SITE', 'http://yourmembers.co.uk/');

// VC
define('YM_NIGHTLY', '$Date: 2013-08-05 15:19:40 +0100 (Mon, 05 Aug 2013) $');
define('YM_META_BASENAME', $plugin_name);

// Comms
$ym_home = site_url('/');
define('YM_LICENCE_CHECK_URL', YM_PLUGIN_SITE . 'index.php?nm_action=activate&product=' . YM_PLUGIN_PRODUCT . '&host=' . $ym_home);
define('YM_TOS_INFORM_URL', YM_PLUGIN_SITE . 'index.php?nm_action=tosinform&product=' . YM_PLUGIN_PRODUCT . '&host=' . $ym_home);
define('YM_VERSION_CHECK_URL', YM_PLUGIN_SITE . 'index.php?nm_action=version_check&product=' . YM_PLUGIN_PRODUCT . '&installed_versionid=' . YM_PLUGIN_VERSION_ID . '&host=' . $ym_home . '&beta=' . get_option('ym_beta_notify', 0));
define('YM_ADVERT_URL', YM_PLUGIN_SITE . 'index.php?nm_action=getadvert&host=' . $ym_home);
define('YM_DEACTIVATE_URL', YM_PLUGIN_SITE . 'index.php?nm_action=deactivate&product=' . YM_PLUGIN_PRODUCT . '&host=' . $ym_home);
define('YM_SUPPORT_EMAIL', 'support@yourmembers.co.uk');
unset($ym_home);

// Useful Paths
define('YM_ADMIN_DIR', trailingslashit(basename($plugin_dir)));// the Folder YM is installed in
define('YM_PLUGIN_DIR_PATH', $plugin_dir);
define('YM_PLUGIN_DIR_URL', $plugin_url);

define('YM_PLUGIN_IMG_PATH',YM_PLUGIN_DIR_PATH . 'images/');
define('YM_CLASSES_DIR',YM_PLUGIN_DIR_PATH . 'classes/');
define('YM_INCLUDES_DIR',YM_PLUGIN_DIR_PATH . 'includes/');
define('YM_TEMPLATES_DIR',YM_PLUGIN_DIR_PATH . 'templates/');
define('YM_MODULES_DIR',YM_PLUGIN_DIR_PATH . 'paymentgateways/');
define('YM_IMAGES_DIR_URL',YM_PLUGIN_DIR_URL . 'images/');
define('YM_CSS_DIR_URL',YM_PLUGIN_DIR_URL . 'css/');
define('YM_JS_DIR_URL',YM_PLUGIN_DIR_URL . 'js/');

// URLS
define('YM_ADMIN_URL', site_url('wp-admin/admin.php?page=' . YM_ADMIN_FUNCTION));

// User Prefs
define('YM_DATE', get_option('date_format') . ' ' . get_option('time_format'));
define('YM_DATEFORMAT', get_option('date_format'));
define('YM_DATE_ONLY', 'd/m/y');
define('YM_DATEPICKER', 'dd/mm/yy');

// WP Version
define('YM_WP_VERSION', get_bloginfo('version'));

if (isset($_GET['ym_dev']) && $_GET['ym_dev'] == 1) {
	$option = get_option('ym_dev');
	if ($option == 1) {
		delete_option('ym_dev');
	} else {
		update_option('ym_dev', 1);
	}
	header('Location: ' . str_replace('&ym_dev=1', '', $_SERVER['REQUEST_URI']));
	exit;
}
if (get_option('ym_dev')) {
	define('ym_dev', 1);
}

require_once(YM_INCLUDES_DIR . 'ym_functions.include.php');
require_once(YM_INCLUDES_DIR . 'ym_log_functions.include.php');

require_once(YM_CLASSES_DIR . 'ym-auth.class.php');
require_once(YM_CLASSES_DIR . 'ym-system.class.php');
require_once(YM_CLASSES_DIR . 'ym-package_types.class.php');
require_once(YM_CLASSES_DIR . 'ym-packs.class.php');// TODO: a naff class which needs tweaks and expanding but its in the ini class so can't nerf it
require_once(YM_CLASSES_DIR . 'ym-resources.class.php');
require_once(YM_CLASSES_DIR . 'ym-user.class.php');
require_once(YM_CLASSES_DIR . 'ym-display_error.class.php');
require_once(YM_CLASSES_DIR . 'ym-manage_access.class.php');
require_once(YM_CLASSES_DIR . 'ym-file_upload.class.php');
require_once(YM_CLASSES_DIR . 'ym-form_generation.class.php');

require_once(YM_CLASSES_DIR . 'ym-payment-gateway.class.php');

require_once(YM_INCLUDES_DIR . 'update_checker.php');
require_once(YM_INCLUDES_DIR . 'ym-template_hooks.include.php');

//if (is_admin()) {
	require_once(YM_INCLUDES_DIR . 'ym-admin_functions.include.php');
if (is_admin()) {
	require_once(YM_INCLUDES_DIR . 'ym-admin_functions_posting.include.php');
	require_once(YM_INCLUDES_DIR . 'ym-admin_group_membership_functions.include.php');
	require_once(YM_INCLUDES_DIR . 'ym-admin_ajax_functions.include.php');
}

require_once(YM_INCLUDES_DIR . 'ym-download_functions.include.php');
require_once(YM_INCLUDES_DIR . 'ym-initialise.include.php');
require_once(YM_INCLUDES_DIR . 'ym-sidebar_widget.include.php');
require_once(YM_INCLUDES_DIR . 'ym-custom_fields.include.php');
require_once(YM_INCLUDES_DIR . 'ym_coupon_functions.include.php');
require_once(YM_INCLUDES_DIR . 'ym-cron.include.php');
require_once(YM_INCLUDES_DIR . 'ym-register.include.php');
require_once(YM_INCLUDES_DIR . 'ym-register-flows.include.php');

require_once(YM_INCLUDES_DIR . 'ym_individual_purchase_functions.include.php');
require_once(YM_INCLUDES_DIR . 'ym_bundle_functions.include.php');

require_once(YM_INCLUDES_DIR . 'ym-group_membership_functions.include.php');

require_once(YM_INCLUDES_DIR . 'ym_deprecated.php');

require_once(YM_INCLUDES_DIR . 'ym-data_output.include.php');
require_once(YM_INCLUDES_DIR . 'ym-data_import.include.php');

register_activation_hook($plugin_file, 'ym_activate');
register_deactivation_hook($plugin_file, 'ym_deactivate');

// load language l10n files, if appropriate
load_plugin_textdomain('ym', false, $plugin_folder . '/lang/');


$ym_auth = new YourMember_Authentication();
$ym_formgen = new ym_form_generation();

$max_upload_size = 10485760; //10mb

global $wpdb;
$ym_dl_db = $wpdb->prefix."ym_download";
$ym_dl_post_assoc = $wpdb->prefix."ym_download_post_assoc";
$ym_dl_root = YM_PLUGIN_DIR_URL;

$wp_upload = false;
$ym_upload_root = false;
$ym_upload_url = false;

$ym_version_resp = new stdClass();
$ym_update_checker = '';

$duration_str = array(
	'd' => __('Days', 'ym'),
	'm' => __('Months', 'ym'),
	'y' => __('Years', 'ym')
);

$status_str = array(
	'YM_STATUS_NULL'			=>__('Inactive','ym'),
	'YM_STATUS_ACTIVE'			=>__('Active','ym'),
	'YM_STATUS_EXPIRED'			=>__('Expired','ym'),
	'YM_STATUS_PENDING'			=>__('Pending','ym'),
	'YM_STATUS_ERROR'			=>__('Error','ym'),
	'YM_STATUS_TRIAL_EXPIRED'	=>__('Trial Expired','ym'),
	'YM_STATUS_GRACE'			=>__('Grace', 'ym'),
	'YM_STATUS_CANCEL'			=>__('Cancelled', 'ym'),

	// special classes to handle child expire
	'YM_STATUS_PARENT_CANCEL'	=>__('Parent Account: Blocked', 'ym'),
	'YM_STATUS_PARENT_EXPIRED'	=>__('Parent Account: Expired', 'ym'),
	'YM_STATUS_PARENT_CONFIG'	=>__('Child Account Needs Configuring', 'ym'),
);

foreach ($status_str as $constant=>$label) {
	define($constant, $label);
}

// global
$ym_sys = get_option('ym_sys');
$ym_res = new YourMember_Resources();
$ym_packs = get_option('ym_packs');
$ym_package_types = get_option('ym_package_types');
$ym_active_modules = get_option('ym_modules', array());

$ym_gateway_paths = apply_filters('ym_alternative_gateway_paths', array());
$ym_gateway_paths[] = YM_MODULES_DIR;

foreach ($ym_active_modules as $key => $module) {
	foreach ($ym_gateway_paths as $path) {
		if (is_file($path . $module . '.php')) {
			require_once(YM_MODULES_DIR . $module . '.php');
		}
	}
}

foreach ($ym_active_modules as $key => $module) {
	if (!class_exists($module)) {
		// not loaded becuase not found
		unset($ym_active_modules[$key]);
		update_option('ym_modules', $ym_active_modules);
	}
}

$ym_user = FALSE;
// end globals

function ym_init() {
	global $errors, $ym_sys;

	if (ym_post('submit')) {
		if (ym_request('ym_page') || ym_request('another_page_needed')) {
			ym_update_custom_fields_by_page();
		}
	}

	// rewrite rules
	add_rewrite_tag('%mod%', '[a-zA-Z_0-9]');
	add_rewrite_tag('%ym_process%', '[0-9]');
	add_rewrite_tag('%ym_upgrade%', '[0-9]');
	add_rewrite_tag('%ym_subscribe%', '[0-9]');
	add_rewrite_tag('%ym_thank_you%', '[0-9]');

	if (!$data = get_option('ym_sys')) {
		ym_activate();
	} else {
		get_currentuserinfo();
		global $pagenow, $current_user;

		$array = array('wp-login.php', 'wp-register.php');

		// TODO: look at this old public access code
		if (!in_array($pagenow, $array) && !isset($_REQUEST['ym_thank_you_page']) && !isset($_REQUEST['ym_subscribe']) && !isset($_REQUEST['ym_process']) && $current_user->ID == 0 && !ym_is_spider()) {
			// do nothing user not logged in or is on a login page
		} else {
//			echo 'HERE';exit;
			// user is logged in confirm login
			if (ym_authenticate($current_user, true) === false && $ym_sys->modified_registration) {
				// user expired route to login
				wp_clear_auth_cookie();
				wp_redirect(get_option('siteurl') . '/wp-login.php');
				exit;
			}
		}
	}
} // end_of ym_init()

//@todo this function needs cleaning up it's about face.
// Function normally called up filter wp_authenticate_user
function ym_authenticate($user=null, $return_bool=false) {
	// CL. TN changed to empty() as a more reliable check and mimics WordPress method
	if (empty($user->ID)) {
		return ($return_bool ? true : $user);
	}
	global $ym_user;
	if (!isset($ym_user->ID)) {
		$ym_user = new YourMember_User($user->ID);
	}

	// returns true if not expired
	$not_expired = $ym_user->expire_check();
	if ($not_expired) {
		return ($return_bool ? true : $user);
	}

	//if the function hasnt returned by this point then theres an error
	if ($return_bool) {
		return false;
	}

	global $ym_res;
	$error_messages = array(
		YM_STATUS_NULL				=> $ym_res->login_errmsg_null,
		YM_STATUS_TRIAL_EXPIRED		=> $ym_res->login_errmsg_trial_expired,
		YM_STATUS_EXPIRED			=> $ym_res->login_errmsg_expired,
		YM_STATUS_PENDING			=> $ym_res->login_errmsg_pending,

		YM_STATUS_PARENT_EXPIRED	=> $ym_res->login_errmsg_parent_expired,
		YM_STATUS_PARENT_CANCEL		=> $ym_res->login_errmsg_parent_cancel,

		'*'							=> $ym_res->login_errmsg_default
	);

//	$block_access = add_filter('ym_authenticate', TRUE, )

	$user_status = $ym_user->status;
	$error = (isset($error_messages[$user_status]) ? $error_messages[$user_status] : $error_messages['*']);
	$error = str_replace('[[USERNAME]]', $user->user_login, $error);

	$err = new WP_Error();
	$err->add('ym_login_error',$error);
	return $err;
} // end of ym_authenticate()

/**
Stuff
*/
//require_once('includes/stuff.php');
function ym_register($user_id) {
	global $wpdb;

	if (!isset($_SESSION['error_on_page'])) {
		get_currentuserinfo();

		$ym_user = new YourMember_User($user_id);
		$ym_user->status = YM_STATUS_NULL;
		$ym_user->save();

		if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') === false) {
		   	// check if subscription option is in the registration form
			$subs_option = false;
			$user_pass = false;
						
			// save the custom fields if there are any
			$fld_obj = get_option('ym_custom_fields');
			$entries = $fld_obj->entries;
			$order = $fld_obj->order;
						
			if (!empty($order)) {
				if (strpos($order, ';') !== false) {
					$orders = explode(';', $order);
				} else {
					$orders = array($order);
				}

				$data = array();
				
				foreach ($orders as $order) {
					foreach ($entries as $entry) {
						if ($order == $entry['id']) {
							if ($entry['name'] == 'subscription_options') {
								$subs_option = true;
							} else if ($entry['name'] == 'subscription_introduction' || $entry['name'] == 'terms_and_conditions') {
								continue;
							} else if ($entry['name'] == 'birthdate') {
								if ((!empty($_POST['ym_birthdate_month'])) && (!empty($_POST['ym_birthdate_day'])) && (!empty($_POST['ym_birthdate_year']))) {
									$data[$entry['id']] = $_POST['ym_birthdate_month'] .'-'. $_POST['ym_birthdate_day'] .'-'. $_POST['ym_birthdate_year'];
								}
							} else if ($entry['name'] == 'country') {
								if (!empty($_POST['ym_country'])) {
									$data[$entry['id']] = $_POST['ym_country'];
								}
							} else if ($entry['type'] == 'file') {
								$name = 'ym_field-' . $entry['id'];
								if (isset($_FILES[$name])) {
									$ok = FALSE;
									global $ym_upload_root;
									if ($ym_upload_root) {
										$dir = trailingslashit(trailingslashit($ym_upload_root) . 'ym_custom_field_' . $entry['name']);
										if (!is_dir($dir)) {
											mkdir($dir);
										}
										if (is_dir($dir)) {
											// all good
											if ($_FILES[$name]['error'] == UPLOAD_ERR_OK) {
												$tmp = $_FILES[$name]['tmp_name'];
												$target = $dir . ym_get_user_id() . '_' . $_FILES[$name]['name'];
												if (move_uploaded_file($tmp, $target)) {
													global $ym_upload_url;
													$data[$entry['id']] = trailingslashit($ym_upload_url) . 'ym_custom_field_' . $entry['name'] . '/' . ym_get_user_id() . '_' . $_FILES[$name]['name'];
													$ok = TRUE;
												}
											}
										}
									}
									if (!$ok) {
										echo '<div id="message" class="error"><p>' . __('An Error Occured whilst Uploading', 'ym') . '</p></div>';
									}
								}
							} else if ($entry['type'] == 'callback') {
								$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_save';
								if (function_exists($callback)) {
									$data[$entry['id']] = $callback($entry['id']);
								}
							} else {
								$field_name = 'ym_field-'. $entry['id'];
															
								if (in_array($entry['name'], array('first_name', 'last_name'))) {
									update_user_meta($user_id, $entry['name'], $_POST[$field_name]);
								}

								$data[$entry['id']] = ym_post($field_name, '');
							}
						}
					}
				}

				update_user_option($user_id, 'ym_custom_fields', $data, true);
			}

			if (!$user_pass = ym_post('ym_password')) {
				$user_pass = substr(md5(uniqid(microtime())), 0, 7);
			}
						
			$user_pass_md5 = md5($user_pass);

			$wpdb->query("UPDATE $wpdb->users SET user_pass = '$user_pass_md5' WHERE ID = '$user_id'");

			wp_new_user_notification($user_id, $user_pass);
						
			// redirect to ym_subscribe
			$userdata = get_userdata($user_id);						
			$redirect = add_query_arg(array('username'=>$userdata->user_login, 'ym_subscribe'=>1), get_option('siteurl') );				
				
			if (ym_post('ym_autologin')) {
				$redirect = add_query_arg(array('ym_autologin'=>1), $redirect);
			}
						
			$redirector = ym_post('ym_redirector', ym_post('redirect_to'));
			if ($redirector) {
				$redirect = add_query_arg(array('redirector'=>$redirector), $redirect);
			}
						
			$another_page_needed = ym_request('another_page_needed');
			if ($page = ym_request('ym_page', 1)) {
				$redirect = add_query_arg(array('ym_page'=>$page), $redirect);
				if ($another_page_needed) {
					$redirect = add_query_arg(array('another_page_needed'=>$another_page_needed), $redirect);
				}
			}
						
			if ($subs_option) {
				$redirect = add_query_arg(array('pack_id'=>$_POST['ym_subscription']), $redirect);
			}

			if (!headers_sent()) {
				header('location: ' . $redirect);
			} else {
				echo '<script>document.location="' . $redirect . '";</script>';
			}

			exit;
		} else {
			return $user_id;
		}
	}
}
/**
end_of ym_register()
*/

//function argument variable names kept for consistency...
//another_page_needed = page to show
//page = page coming from
function ym_get_additional_registration_form_page($another_page_needed, $page=false) {
		$html = '';
		
		if (!$page) {
			$page = ym_request('ym_page');
		}
		
		if ($page > 1) {
			$wp_error = new WP_Error();
			ym_register_post(ym_request('username'), '', $wp_error, $page); //error checking
			
			if ($wp_error->get_error_code()) {
				$errors = '';
				$messages = '';
				foreach ( $wp_error->get_error_codes() as $code ) {
					$severity = $wp_error->get_error_data($code);
					foreach ( $wp_error->get_error_messages($code) as $error ) {
						if ( 'message' == $severity ) {
							$messages .= '	' . $error . "<br />\n";
						} else {
							$errors .= '	' . $error . "<br />\n";
						}
					}
				}
				if ( !empty($errors) ) {
					$html .= '<div id="login_error">' . apply_filters('login_errors', $errors) . "</div>\n";
				}
				if ( !empty($messages) ) {
					$html .= '<p class="message">' . apply_filters('login_messages', $messages) . "</p>\n";
				}
							
			$another_page_needed = $page;
			$page--;
		}
	}
	
	$action = trailingslashit(get_option('siteurl')) . '?ym_subscribe=1&ym_page=' . $page . '&username=' . ym_get('username') . (ym_get('subs') ? '&subs=' . ym_get('subs'):'') . (ym_get('pack_id') ? '&pack_id=' . ym_get('pack_id'):'');
	
	$html .= '<form action="' . $action . '" method="post" enctype="multipart/form-data" name="registerform" id="registerform">
		<div style="clear: both;">';
		
	$html .= ym_register_form(true, $another_page_needed);
	
	$html .= '</div>
		<div class="ym_clear">&nbsp;</div>
		<p class="submit">';
		
	$previous_page = ym_get_previous_custom_field_page($another_page_needed);
	
	if ($previous_page > 1) {
		$html .= '<input class="button-primary" type="button" value="' . __('&laquo Previous', 'ym') . '" onclick="document.location=\'' . $action . '&another_page_needed=' . $previous_page . '\';" />';
	}
	
	$html .= '	<input class="button-primary" type="submit" name="submit" value="Next &raquo;" />
		</p>';
				
	$html .= '</form>';
		
		return $html;
}

// This adds custom fields into the registration form
function ym_register_form($return = false, $page=1, $pack_id=false, $hide_custom_fields=false, $hide_further_pages=false, $autologin=false) {
	global $duration_str, $ym_sys, $ym_res;
	
	$html = '';
	$fld_obj = get_option('ym_custom_fields');
	$hide = $ym_sys->hide_custom_fields;
	$user_id = ym_get_user_id();
		
	$hide_custom_fields = explode(',', $hide_custom_fields);
	if (!is_array($hide_custom_fields)) {
		$hide_custom_fields = array($hide_custom_fields);
	}

	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	if (empty($order)) {
		return;
	}

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}
		
	$html .= '<div style="clear:both; height: 1px;">&nbsp;</div>';
							   
	if ($redirect_to = ym_get('ym_redirector')) {
		$html .= '<input type="hidden" name="ym_redirector" value="' . urlencode($redirect_to) . '" />';
	}
	if ($autologin) {   
		$html .= '<input type="hidden" name="ym_autologin" value="1" />';
	}
		
	$another_page = false;
	$lowest_page = ym_get_last_custom_field_page()+1; //must be higher than the highest page
		
	$values = array();
	if ($username = ym_get('username')) {
		$values = ym_get_custom_fields_by_username($username);
	}
 	
	foreach ($orders as $order) {
		foreach ($entries as $entry) {
			if ($order == $entry['id']) {
				if (in_array($entry['id'], $hide_custom_fields)) {
					continue;
				}
							
				$entry['page'] = (!isset($entry['page']) ? 1:$entry['page']);
				if ($page == $entry['page']) {
					if (isset($_POST['hide_ym_field-'. $entry['id']])) {
						$entry['type'] = 'hidden';
						//will hide the field if the appropriate post data is present.
						//This is intended to go with hard coded signups where the register page will act as stage 2
					}

					$value = false;
					$row = '';
					$hide_label = false;

					if (isset($values[$entry['id']])) {
						if (trim($values[$entry['id']])) {
							$value = trim($values[$entry['id']]);
						}
					} else {
						$value = ym_post('ym_field-' . $entry['id']);
					}
								
					if ($value) {
						$entry['value'] = $value;
					}
								
					if ($value = $entry['value']) {
						if (strpos($value, ':') !== false) {
							$array = explode(':', $value);

							if (count($array)) {
								switch($array[0]) {
									case 'cookie':
										$entry['value'] = ym_cookie($array[1], '');
										break;
									case 'session':
										$entry['value'] = ym_session($array[1], '');
										break;
									case 'get':
										$entry['value'] = ym_get($array[1], '');
										break;
									case 'post':
										$entry['value'] = ym_post($array[1], '');
										break;
									case 'request':
									case 'qs':
										$entry['value'] = ym_request($array[1], '');
										break;
									default:
										$entry['value'] = '';
										break;
								}
							}
						}
					}

					if (($entry['name'] == 'terms_and_conditions') && (!empty($ym_res->tos))) {
						$row .= '<p>
								<textarea name="tos" cols="29" rows="5" readonly="readonly">' . $ym_res->tos . '</textarea>';
						$row .= '</p>';
						$row .= '<p>
								<label class="ym_label" for="ym_tos">
									<div><input type="checkbox" class="checkbox" name="ym_tos" id="ym_tos" value="1" />
									' . __('I agree to the Terms and Conditions.','ym') . '</div>
								</label>
							</p>' . "\n";
					} else if (($entry['name'] == 'subscription_introduction') && (!empty($ym_res->subs_intro))) {
						$row .= '<div class="ym_subs_intro">'. $ym_res->subs_intro .'</div>';
					} else if ($entry['name'] == 'subscription_options') {
//						$pack_restriction = false;
//						if (strpos(',', $pack_id)) {
//							$pack_restriction = explode(',', $pack_id);
//						}
						if (ym_request('ym_subscription', $pack_id)) {
							// pre selected!
							// could be from a ym_register and the reg is hidden so showing the selector here is bad
							$row .= '<input type="hidden" name="ym_subscription" value="' . ym_request('ym_subscription', $pack_id) . '" />';
							$hide_label = TRUE;
						} else {
							global $ym_packs;
							$packs = $ym_packs->packs;
							
							$active_modules = get_option('ym_modules');

							if (empty($active_modules)) {
								$row .= '<p>' . __('There are no payment gateways active. Please contact the administrator.','ym') . '</p>';
							} else {
								// RENDER
								$packs_shown = 0;

								if ($existing_data = ym_request('ym_subscription')) {
									$default = $existing_data;
								} else {
									$default = ym_get_default_pack();
								}

								$did_checked = FALSE;

								foreach ($packs as $pack) {
									/*
									if (count($pack_restriction)) {
										// has restiction
										if (in_array($pack['id'], $pack_restriction)) {
											// do not show aka hide
											$pack['hide_subscription'] = 1;
										}
									}
									*/
									if (!$pack['hide_subscription']) {
										$row .= '<div class="ym_register_form_subs_row">
													<div class="ym_reg_form_pack_radio">
														<input type="radio" ';
										if ($pack['id'] == $default && !$did_checked) {
											$row .= 'checked="checked"';
											$did_checked = TRUE;
										}
										$packs_shown++;
										$row .= ' class="checkbox" id="ym_subscription_' . $pack['id'] . '" name="ym_subscription" value="'. $pack['id'] .'" />
													</div>
													<label for="ym_subscription_' . $pack['id'] . '" class="ym_subs_opt_label ym_reg_form_pack_name">' . ym_get_pack_label($pack['id']) . '</label>
												</div>';
									}
								}

								if (!$packs_shown) {
									$hide_label = true;
								} else {
									if ($entry['caption']) {
										$row = '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>' . $row;
									}
								}
								// END RENDER
							}
						}
					} else if ($entry['name'] == 'birthdate' && !$hide) {
						$birthdate_fields = ym_birthdate_fields('ym_birthdate');
						$row .= '<p>'. $birthdate_fields .'</p>';

					} else if ($entry['name'] == 'country' && !$hide) {
						$countries_sel = ym_countries_list('ym_country');
						$row .= '<p>'. $countries_sel .'</p>';

					} else if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide) {
						$ro = ($entry['readonly'] ? 'readonly="readonly"':'');

						if ($entry['type'] == 'text') {
							$fld = '<input type="text" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
											} else if ($entry['type'] == 'hidden') {
												$fld = '<input type="hidden" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" />';
												$hide_label = true;
											} else if ($entry['type'] == 'yesno') {
							$fld = '<select class="ym_reg_select" name="ym_field-'. $entry['id'] .'" '. $ro .'>';

							$options = array('Yes', 'No');

							foreach ($options as $option) {
								$fld .= '<option value="' . $option . '" ' . (trim($option) == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
							}

							$fld .= '</select>';
						} else if ($entry['type'] == 'password') {
							// primary use is ym_password
							if ($entry['name'] == 'ym_password') {
								$fld = '<input type="password" name="ym_password" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
								ym_login_remove_password_string();
							} else {
								// allow other password fields
								$fld = '<input type="password" name="' . $entry['name'] . '" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
							}
						} else if ($entry['type'] == 'html') {
												$fld = '<div class="ym_reg_html">' . $entry['value'] . '</div>';
						} else if ($entry['type'] == 'textarea') {
							$fld = '<textarea class="ym_reg_textarea" name="ym_field-'. $entry['id'] .'" cols="29" rows="5" '. $ro .'>' . $entry['value'] . '</textarea>';
						} else if ($entry['type'] == 'select') {
							$fld = '<select class="ym_reg_select" name="ym_field-'. $entry['id'] .'" '. $ro .'>';

							$options = explode(';', $entry['available_values']);

							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $val . '</option>';
								} else {
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
								}
							}

							$fld .= '</select>';
						} else if ($entry['type'] == 'multiselect') {
							$fld = '<select class="ym_reg_multiselect" name="ym_field-' . $entry['id'] . '[]" ' . $ro . ' multiple="multiple">';
							
							$options = explode(';', $entry['available_values']);

							foreach ($options as $option) {
								if (strpos($option, ':')) {
									list($option, $val) = explode(':', $option);
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $val . '</option>';
								} else {
									$fld .= '<option value="' . $option . '" ' . ($option == $value ? 'selected="selected"':'') . '>' . $option . '</option>';
								}
							}

							$fld .= '</select>';
						} else if ($entry['type'] == 'file') {
							$fld = '<input type="file" name="ym_field-'. $entry['id'] .'" />';
							if ($entry['available_values'] == 'image') {
								$fld .= $entry['value'];
							}
						} else if ($entry['type'] == 'callback') {
							$callback = 'ym_callback_custom_fields_' . $entry['name'] . '_editor';
							if (function_exists($callback)) {
								$fld = $callback($entry['id']);
							}
						} else {
							if (!$fld = apply_filters('ym_generate_custom_field_type_' . $entry['type'], '', 'ym_field-'. $entry['id'], $entry, $value)) {
								$fld = '<input type="text" name="ym_field-'. $entry['id'] .'" value="'. $entry['value'] .'" '. $ro .' class="ym_reg_input" size="25" />';
							}
						}
						
						if ($entry['required']) {
							$fld .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_required">' . $ym_sys->required_custom_field_symbol . '</div>';
						}
						if ($entry['caption']) {
							$fld .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>';
						}
											
						$row .= '<p>' . $fld . '</p>';
					}


					////Adding of the row
					if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide && !$hide_label) {
						$html .= '<div class="ym_register_form_row" id="' . str_replace(' ', '_', $entry['name']) . '_row">';
						$label = $entry['label'];
						$html .= '<label class="ym_label">'. $label .'</label>';
					}
									
					$html .= $row;
									
					if ((!$entry['profile_only'] || $entry['profile_only'] == false) && !$hide && !$hide_label) {
						$html .= '<div class="ym_clear">&nbsp;</div>';
						$html .= '</div>';
					}
					////End adding of the row
								
				}
								
				if (!$hide_further_pages) {
					if ($entry['page'] > $page) {
						if ($entry['page'] < $lowest_page) {
							$lowest_page = $entry['page'];
						}
									
						$another_page = true;
					}
				}
			}
		}
	}
		
		$html .= '<input type="hidden" name="ym_page" value="' . $page . '" />'; //so that the update function knows which pages to validate
		
		if ($another_page) {
			$html .= '<input type="hidden" name="another_page_needed" value="' . $lowest_page . '" />'; //so that the rendering function knows to add another page before sending off to the gateway
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}		
}

// Check required custom fields
function ym_register_post($user_login, $user_email, $errors, $page=1) {
	global $ym_res;

	if (isset($_SESSION)) {
		unset($_SESSION['error_on_page']);
	}

	$fld_obj = get_option('ym_custom_fields');

	$entries = $fld_obj->entries;
	$order = $fld_obj->order;

	if (empty($order)) {
		return;
	}

	if (strpos($order, ';') !== false) {
		$orders = explode(';', $order);
	} else {
		$orders = array($order);
	}
		
		//$page = ym_request('ym_page', 1);
		
		$values = array();
		if ($username = ym_get('username')) {
			$values = ym_get_custom_fields_by_username($username);
		}		

	foreach ($orders as $order) {
		foreach ($entries as $entry) {
						$entry['page'] = (isset($entry['page']) ? $entry['page']:1);
						if ($entry['page'] == $page) {
							
							if ($order == $entry['id']) {
									if (($entry['name'] == 'terms_and_conditions') && (!empty($ym_res->tos))) {
											if (!isset($_POST['ym_tos'])) {
													$errors->add('ym_tos', '<strong>ERROR</strong>: ' . __('You must accept the Terms and Conditions.', 'ym'));
											}
									} else if ($entry['name'] == 'subscription_introduction') {
											continue;
									} else if ($entry['name'] == 'subscription_options') {
											if ( (!isset($_POST['ym_subscription'])) || (empty($_POST['ym_subscription'])) ) {
													$errors->add('ym_subscription', __('<strong>ERROR</strong>: You must select a Subscription Type.','ym'));
											}
									} else if ($entry['name'] == 'birthdate') {
											if ($entry['required'] && (empty($_POST['ym_birthdate_month']) || empty($_POST['ym_birthdate_day']) || empty($_POST['ym_birthdate_year']))) {
													$errors->add('ym_birthdate', __('<strong>ERROR</strong>: Birthdate is required','ym'));
											}
									} else if (strtolower($entry['name']) == __('password','ym') ) {
											if ($entry['required'] && empty($_POST['ym_password'])) {
													$errors->add('ym_password', __('<strong>ERROR</strong>: Password is required','ym'));
											}
									} else if ($entry['name'] == 'country') {
											if ($entry['required'] && empty($_POST['ym_country'])) {
													$errors->add('ym_country', __('<strong>ERROR</strong>: Country is required','ym'));
											}
									} else {
	
											$required = $entry['required'];
											$field_name = 'ym_field-'. $entry['id'];
											$name = $entry['name'];
											$value = ym_post($field_name);
											
											if (isset($entry['label']) && $entry['label']) {
												$label = $entry['label'];
											} else {
												$label = $entry['name'];
											}											
											
											if (isset($values[$entry['id']])) {
												if (trim($values[$entry['id']])) {
													$value = trim($values[$entry['id']]);
												}
											}
											
											if ($required && empty($value) && (ym_get('action') == 'register' && !$entry['profile_only'])) {
													$errors->add($field_name, sprintf(__('<strong>ERROR</strong>: %s is a required field','ym'),$label));
											} else if ($entry['available_values'] != '' && $entry['type'] != 'select' && $entry['type'] != 'multiselect') {
													if (strpos($entry['available_values'], ':')) {
														$values = array();
														$options = explode(';', $entry['available_values']);
														foreach ($options as $option) {
															list($v, $option) = explode(':', $option);
															$values[] = $v;
														}
													} else {
														$values = explode(';', $entry['available_values']);
													}
													if (!ym_validate_custom_field_data($value, $values)) {
														$errors->add($field_name, sprintf(__('<strong>ERROR</strong>: %s is not valid','ym'),$label));
													}
											}
											
											$errors = apply_filters('ym_custom_field_single_validation', $errors, $entry);
											
											$pack_id = ym_post('ym_subscription', FALSE);
											
											$errors = apply_filters('ym_post_additional', $errors, $user_login, $user_email, $pack_id);
									}
							}
						}
		}
	}
		
		$errors = apply_filters('ym_custom_field_group_validation', $errors, $_POST);

	if (count($errors->errors)) {
		$_SESSION['error_on_page'] = true;
	}

	return $errors;
}

// show buttons of modules available for upgrade/downgrade
function ym_upgrade_buttons($return=false, $pack_id=false, $user_id=false) {
	global $wpdb, $duration_str, $current_user, $ym_res, $ym_sys, $ym_packs;
	get_currentuserinfo();

	if (!$user_id) {
		$user_id = $current_user->ID;
	}

	if ($pack_id == 'all') {
		global $ym_packs;

		$html = '';
		foreach ($ym_packs->packs as $pack) {
			if (!$pack['hide_subscription']) {
				$html .= ym_upgrade_buttons(TRUE, $pack['id']);
			}
		}

		if ($return) {
			return $html;
		} else {
			echo $html;
			return;
		}
	}
		
	$html = '';
	$ym_home = get_option('siteurl');
		
	if (!$user_id) {
		$html = $ym_res->msg_header . __('Sorry but you must be logged in to upgrade your account', 'ym') . $ym_res->msg_footer;
	} else {
		$user_data = new YourMember_User($user_id);

		$account_type = $user_data->account_type;
		$packs = $ym_packs->packs;
//		$trial_taken = get_user_meta($user_id, 'ym_trial_taken', TRUE);
		// UP TO HERE

		global $ym_active_modules;
		$base = add_query_arg(array('ym_subscribe'=>1, 'ud'=>1, 'username'=>$current_user->user_login), $ym_home);

		if ((!isset($_POST['submit']) || !isset($_POST['subs_opt'])) && !$pack_id) {
			// TODO: Does this code even run?
			$html = '<p class="message register">' . __('Choose an Account Type', 'ym') . '</p>';
			$html .= '<form action="" method="post" class="ym"><div style="clear: both; overflow: auto; padding-bottom: 10px;">';
	
			// RENDER2
			$packs_shown = 0;

			if ($existing_data = ym_request('ym_subscription')) {
				$default = $existing_data;
			} else {
//				$default = ym_get_default_pack();
				$default = $user_data->pack_id;
			}

			$did_checked = FALSE;

			foreach ($packs as $pack) {
				if (!$pack['hide_subscription']) {
					$html .= '<div class="ym_register_form_subs_row">
								<div class="ym_reg_form_pack_radio">
									<input type="radio" ';
					if ($pack['id'] == $default && !$did_checked) {
						$html .= 'checked="checked"';
						$did_checked = TRUE;
					}
					$packs_shown++;
					$html .= ' class="checkbox" id="ym_subscription_' . $pack['id'] . '" name="ym_subscription" value="'. $pack['id'] .'" />
							</div>
							<label for="ym_subscription_' . $pack['id'] . '" class="ym_subs_opt_label ym_reg_form_pack_name">' . ym_get_pack_label($pack['id']) . '</label>
						</div>';
				}
			}

			if (!$packs_shown) {
				$hide_label = true;
			} else {
				if (isset($entry['caption']) && $entry['caption']) {
					$html .= '<div class="ym_clear">&nbsp;</div><div class="ym_register_form_caption">' . $entry['caption'] . '</div>' . $row;
				}
			}

			// END RENDER2
	
			if ($packs_shown) {
//				$html .= '</div><input type="hidden" name="ref" value="'. md5($user_data->amount .'_'. $user_data->duration .'_'. $user_data->duration_type .'_'. $user_data->account_type) .'" />';
				$html .= '<p class="submit"><input type="submit" name="submit" value="' . __('Next &raquo;', 'ym') . '" /></p>';
			} else {
				$html .= '<p>' . __('Sorry there are currently no upgrade/downgrade options available to you.', 'ym') . '</p>';
			}
					
			$html .= '</form>';
		} else if (!ym_post('subs_opt') && $pack_id != ym_post('ym_subscription')) {
				global $ym_res;
				
				$html = '<form action="" method="post" class="ym_upgrade_shortcode">';
				$html .= '<input type="hidden" name="ym_subscription" value="' . $pack_id . '" />';
//				$html .= '<input type="hidden" name="ref" value="'. md5($user_data->amount .'_'. $user_data->duration .'_'. $user_data->duration_type .'_'. $user_data->account_type) .'" />';
				$html .= ym_get_pack_label($pack_id);
				$html .= '&nbsp;<a href="#nowhere" onClick="jQuery(this).parents(\'form\').submit();">Upgrade</a>';
				$html .= '</form>';
				
				return $html;
			} else {
				$pack = ym_get_pack_by_id($pack_id);
					$cost = $pack['cost'];
	
					if (!$pack_id) {
						$html .= '<br /><table width="100%" cellpadding="3" cellspacing="0" border="0" align="center" class="form-table">';
	
						if ($cost == 0 || $account_type == 'free') {
								$html .= '<tr><th>' . __('Create a free account: ','ym') . ucwords($account_type) . '</th></tr>';
						} else {
								$html .= '<tr><th>' . __('Select Payment Gateway','ym') . '</th></tr>';
								$html .= '<tr><th>' . ym_get_pack_label($pack['id']) . '</th></tr>';
						}
					}

					if (count($ym_active_modules)) {
							$buttons_shown = array();
							foreach ($ym_active_modules as $module) {
									if ($module == 'ym_free' && $pack['cost'] > 0) {
											continue;
									}
									
									$obj = new $module();
	
									$string = $obj->getButton($pack['id']);
									if ($string) {
										$buttons_shown[] = $module;
										$html .= $string;
									}
									$string = false;
									$obj = null;
							}
							$html .= '</table>';
							
							if (count($buttons_shown) == 1) {
									$module = array_pop($buttons_shown);

									// check that I'm allowed to auto fire
									$check = new $module();
									if (method_exists($check, 'abort_auto'))
										continue;
									
									$form_code = '<div style="display:none;">' . $html . '</div>';
											
									$js = 'document.forms["' . $module . '_form"].submit();';
									$html = '  <html>
													<head>
														<title>Redirecting...</title>
														<script type="text/javascript">
															function load() {
																' . $js . '
															}
														</script>
													</head>
													<body onload="load();">';
													
									$html .= '  <div style="color: #333333; font-size: 14px; margin: 30px 10px; font-family: tahoma; text-align: center; padding: 50px; border: 1px solid silver;">';
									
									$html .= '  <div>' . __('You are being redirected. If this page does not refresh in 5 seconds then click', 'ym') . ' <a onclick="document.forms[\'' . $module . '_form\'].submit();">here</a>.</div>
											   <div style="margin-top: 10px;"><img alt="" src="' . YM_IMAGES_DIR_URL . 'loading.gif" /></div>';
									$html .= '  </div>';
									
									$html .= $form_code;
												
									$html .= '	  </body>
												</html>';
									
									echo $html;
									die;
							}
					} else {
							$html .= '</table>';
							$html .= __('There are no gateways available at this time.','ym');
					}
			}
		}
		
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
}
// HEREHEREHERE

function ym_shortcode_user($args, $content = '', $tag) {
	$return = '';

	$name = isset($args['name']) ? $args['name'] : '';
	$name = strtolower($name);
	$alt = isset($args['alt']) ? $args['alt'] : '';
	$user_id = isset($args['user_id']) ? $args['user_id'] : '';
	$exempt = isset($args['exempt']) ? $args['exempt'] : ''; 

	if($user_id){
		if(is_numeric($user_id)) $user = get_userdata($user_id);
		else{
			$user_id = isset($_REQUEST[$user_id]) ? $_REQUEST[$user_id] : '';
			if(is_numeric($user_id)) $user = get_userdata($user_id);
			else return false;
		}
		if(isset($exempt)){
			$user_ids = explode( ',', $exempt);

			foreach($user_ids as $userID)
			{
				if($userID == $user->ID) return false;
			}
		}
	}
	else{
		get_currentuserinfo();
		global $current_user;
		$user = $current_user;
	}
	

	if ($tag == 'ym_user_url' || $name == 'user_url') {
		$return = ($user->user_url ? $user->user_url:'-');
	} else if ($tag == 'ym_user_register_date') {
		$return = $user->user_registered;
	} else if ($tag == 'ym_user_email' || $name == 'user_email') {
		$return = $user->user_email;
	} else if ($tag == 'ym_user_first_name' || $name == 'first_name') {
		$return = $user->user_firstname;
	} else if ($tag == 'ym_user_last_name' || $name == 'last_name') {
		$return = $user->user_lastname;
	} else if ($tag == 'ym_user_username') {
		$return = $user->user_login;
	} else if ($tag == 'ym_user_nickname') {
		$return = $user->nickname;
	} else if ($tag == 'ym_user_displayname') {
		$return = $user->display_name;
	} else if ($tag == 'ym_user_description' || $name == 'user_description') {
		$return = get_user_meta($user->ID, 'description', TRUE);
	} else if ($tag == 'ym_user_custom' && $name) {
		$custom_field = ym_get_custom_field_by_name($name);
		$id = $custom_field['id'];
		$user_fields = ym_get_custom_fields($user->ID);

		// convert key to value?
		$keys = array();
		if ($custom_field['type'] == 'multiselect' || $custom_field['type'] == 'select') {
			$options = explode(';', $custom_field['available_values']);
			foreach ($options as $option) {
				if (strpos($option, ':')) {
					list($key, $value) = explode(':', $option);
				} else {
					$key = $value = $option;
				}
				$keys[$key] = $value;
			}
		}

		if (is_array($user_fields[$id])) {
			foreach ($user_fields[$id] as $x => $entry) {
				$user_fields[$id][$x] = isset($keys[$entry]) ? $keys[$entry] : $entry;
			}
			$user_fields[$id] = implode($user_fields[$id], ', ');
		} else {
			$user_fields[$id] = isset($keys[$user_fields[$id]]) ? $keys[$user_fields[$id]] : $user_fields[$id];
		}

		$return = $user_fields[$id] ? $user_fields[$id] : $alt;
	}

	return $return;
}

function ym_shortcode_parse($args, $content, $tag) {
	global $ym_sys;
	get_currentuserinfo();
	global $current_user;
	
	$arg_zero = (isset($args[0]) ? $args[0]:false);

	if ($tag == 'private') {
		if (!$ym_sys->protect_mode || ym_post_is_purchasable()) {
			$return = ym_replace_tag($tag, $content, $args);
		} else {
			//just removes the private tags as the query joins hide the posts
			$return = $content;
			$return = '';
		}
	} else if ($tag == 'ym_login') {
		$return = ym_replace_tag($tag, $content, $args);
	} else if ($tag == 'ym_register') {
		$return = ym_replace_tag($tag, $content, $args);
	} else if ($tag == 'private_or') { 
		$account = str_replace('#', '', $arg_zero);
		$return = ym_replace_tag($tag, $content, $account);
	} else if ($tag == 'private_and') { 
		$account = str_replace('#', '', $arg_zero);
		$return = ym_replace_tag($tag, $content, $account);
	} else if ($tag == 'ym_packs') {
		$return = ym_upgrade_links('page', $args['id'], $args['hide_pack_string'], true);
	} else if ($tag == 'ym_upgrade') {
		if (is_user_logged_in()) {
			$args['id'] = isset($args['id']) ? $args['id'] : '';
			if ($args['id'] == 'get') {
				$args['id'] = ym_request('id', FALSE);
			}

			if ($args['id']) {
				$return = ym_upgrade_buttons(true, $args['id']);
			} else {
				$return = ym_available_modules($current_user->user_login, true, 1);//pass a 1 for coupon type 1 upgrade
			}
		} else {
			$return = '';
		}
	} else if ($tag == 'ym_drip_date') {
		$return = ym_drip_date($tag, $content, $args);
	} else {
		$args = str_replace('#', '', $arg_zero);
		
		$return = ym_replace_tag($tag, $content, $args);
	}

	return do_shortcode(stripslashes($return));
}

//Filter Tags with Multiple Values
function ym_replace_tag($function, $matches, $argument = false ) {
	get_currentuserinfo();
	global $current_user, $user_data, $ym_user;
	$return = '';
	

	switch ($function) {
		case 'ym_register':
			$flow = isset($argument['flow']) ? $argument['flow'] : FALSE;
			$id = isset($argument['id']) ? $argument['id'] : FALSE;

			if ($id == 'get') {
				$id = ym_request('id');
			}

			if (function_exists('ym_register_flow') && $flow) {
				$return = ym_register_flow($flow, $id);
				break;
			}

			$hcf = isset($argument['hide_custom_fields']) ? $argument['hide_custom_fields'] : FALSE;
			$hfp = isset($argument['hide_further_pages']) ? $argument['hide_further_pages'] : FALSE;
			$al = isset($argument['autologin']) ? $argument['autologin'] : FALSE;
			$return = ym_register_sidebar_widget(false, $id, $hcf, $hfp, $al);
			break;

		case 'ym_login':
			$register_text = isset($argument['register_text']) ? $argument['register_text'] : '';
			$lostpassword_text = isset($argument['lostpassword_text']) ? $argument['lostpassword_text'] : '';
			$redirect = isset($argument['redirect']) ? $argument['redirect'] : '';
			$return = ym_login_form($register_text, $lostpassword_text, $redirect);
			break;
		
		case 'private':
			$override_message = isset($argument['override_message']) ? $argument['override_message'] : '';
			$return = ym_post_replace($matches, $override_message);
			break;
			
		//Private Tag with Or statement	Account Type
		case 'private_or':
			if (ym_user_has_access() || strtolower($argument) == strtolower($ym_user->account_type)) {
				$return = $matches;
			}

			break;
		
		//Private Tag with AND statement Account Type
		case 'private_and':
			if (ym_user_has_access() && strtolower($argument) == strtolower($ym_user->account_type)){
				$return = $matches;
			}
			break;
			
		//Checks User has access to referenced post usage: [user_has_access#123] where post_id = 123 [user_has_access post_id123]
		case 'user_has_access':
			// as long as arg zero is a post id....
//			$post_id = is_array($argument) ? (isset($argument['post_id']) ? $argument['post_id'] : $argument['post_id']) : $argument;
			if (ym_user_has_access($argument)) {
				$return = $matches;
			}
			break;

		// user name check [private_username_is#username]
		case 'private_username_is':
			get_currentuserinfo();
			global $current_user;
			
			$username = $current_user->user_login;
			if (strtolower($username) == strtolower($argument)) {
				$return = $matches;
			}
			break;

		//Checks if user has no access to current post usage [no_access]
		case 'no_access':
			// as long as arg zero is a post id....
//			$post_id = is_array($argument) ? (isset($argument['post_id']) ? $argument['post_id'] : $argument['post_id']) : $argument;
			if (!ym_user_has_access($argument)) {
				$return = $matches;
			}
			break;
	}

	return do_shortcode(stripslashes($return));
}
function ym_drip_date($tag, $content, $args) {
	// tag is ym_drip_date
	// content is blank
	// args id or msg
	$id = ($args['id']) ? $args['id'] : get_the_ID();
	if (!$id) {
		return __('Post ID Could not be determined', 'ym');
	}
	// days left for this user until post available
	$data = get_post_meta($id, '_ym_account_min_duration', TRUE);
	if (!$data) {
		return __('Post has no Drip Data Set', 'ym');
	}

	if (!is_user_logged_in()) {
		return __('You are not logged in', 'ym');
	}
	global $current_user, $ym_sys, $ym_user;
	get_current_user();

	$user_id = $ym_user->ID;

	$data = explode(';', $data);
	$drip = array();
	foreach ($data as $entry) {
		$entry = explode('=', $entry);
		$drip[$entry[0]] = $entry[1];
	}
	// user account type
	$account_type = $ym_user->account_type;
	if ($days = $drip[$account_type]) {
		if ($days != 0) {
			$reg = $current_user->user_registered;
			if ($ym_sys->post_delay_start == 'pack_join') {
				//$reg = date('Y-m-d', $ym_user->account_type_join_date);
				$reg = $ym_user->account_type_join_date;
			} else {
				$reg = mktime(0,0,0,substr($reg, 5, 2), substr($reg, 8, 2), substr($reg, 0, 4));
			}

			$user_at = $reg + (86400*$days);
			if ($user_at >= time()) {
				$diff = $user_at - time();
				$days_left = number_format($diff / 86400);
				
				$r = __('Post is available in', 'ym') . ' ';
				if ($days_left == 0) {
					$r .= date('g:i', $diff) . ' ' . __('Hours', 'ym');
				} else {
					$r .= $days_left . ' ' . __('Days', 'ym');
				}
				return $r;
			}
		}
	}
	return $args['msg'];
}

function ym_get_pack_dropdown($name='pack_id', $value=false, $show_all_option=true, $paid_only=true, $return=true) {
	global $ym_packs;
	$html = '<select class="ym_pack_dropdown" id="' . $name . '" name="' . $name . '">';
	
	if ($show_all_option) {
		$html .= '<option value="0">' . __('All Packs', 'ym') . '</option>';
	}
	
	if ($packs = $ym_packs->packs) {
		foreach ($packs as $i=>$pack) {
			if ($paid_only) {
				if ($pack['cost'] <= 0) {
					continue;
				}
			}
			
			$pack_string = ym_get_pack_label($pack);
			$selected = ym_selected($value, $pack['id']);
			$html .= '<option ' . $selected . ' value="' . $pack['id'] . '">' . $pack_string . '</option>';
		}
	}
	
	$html .= '</select>';
	
	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

// show buttons of modules available for upgrade/downgrade
function ym_upgrade_links($template='sidebar', $pack_id=false, $hide_pack_string=false, $return=false) {
	get_currentuserinfo();
	global $user, $current_user, $duration_str, $ym_packs;
		
	$html = '';
	$account_type = ym_get_user_account_type(false, true);
	$username = $current_user->user_login;
	
	if (!$packs = $ym_packs->packs) {
		$packs = array();
	}
	
	$active_modules = get_option('ym_modules');
	$mod_count = count($active_modules);
	$base = add_query_arg(array('ym_subscribe'=>1, 'ud'=>1, 'username'=>$username), get_option('siteurl'));
		$html .= '<div class="ym_upgrade_packs">';

	foreach ($packs as $pack) {
		if ($pack_id) {
			if ($pack['id'] != $pack_id) {
				continue;
			}
		}
		if ($pack['hide_subscription']) {
			continue;
		}
		
		
		$dur_type = $duration_str[$pack['duration_type']];
		$dur_str = ($pack['duration'] == 1 ? rtrim($dur_type, 's'):$dur_type);
		$ac_type = strtolower($pack['account_type']);

		if (in_array($ac_type, array($account_type, 'trial', 'free'))) {
			continue;
		}

		$cost = $pack['cost'];
		$pack_str = false;

		if (!$hide_pack_string) {
						$pack_str = ym_get_pack_label($pack);
		}

		$html .= '<div class="ym_upgrade_pack">';

		if ($pack_str) {
			if ($template != 'sidebar') {
				$html .= '<div class="ym_page_upgrade_pack_string">' . $pack_str . '</div>';
	
				if ($pack['description'] != '') {
					$html .= '<div class="ym_page_upgrade_pack_description">' . $pack['description'] . '</div>';
				}
			} else {
				$html .= '<div class="ym_sidebar_upgrade_pack_description">' . $pack_str . '</div>';
			}
		}

		if ($mod_count) {
			if ($active_modules) {
				foreach ($active_modules as $module) {
					if ($module == 'ym_trial') {
						continue;
					} else if ($module == 'ym_free' && $cost) {
											continue;
										}
	
					$obj = new $module();
//					$button = $obj->getButton($cost, $pack['duration'], $pack['duration_type'], $pack['account_type'], $pack['product_id'], $pack['num_cycles'], $pack['trial_on'], $pack['trial_cost'], $pack['trial_duration'], $pack['trial_duration_type'], $pack['role'], $pack['hide_old_content'], $pack['zombaio_price_id'], $pack['vat_applicable'], $pack['id']);
					$button = $obj->getButton($pack['id']);
	
					if ($button) {
						$html .= '<div class="ym_upgrade_button ym_' . $obj->name . '_upgrade">';
						$html .= $button;
						$html .= '</div>';
					}
				}
			}
		} else {
			$html .= __('There are no gateways available at this time.','ym');
		}

		$html .= '<div style="padding: 0; margin: 0; clear: both;"></div>';
		$html .= '</div>';

	}
		
		$html .= '</div>';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ym_user_has_access($post_id = false, $user_id=false, $allow_on_purchasable = false) {
	get_currentuserinfo();
	global $current_user, $user_data, $wpdb, $ym_sys;

	if (isset($_GET['username']) && isset($_GET['password'])) {
		$user = wp_authenticate($_GET['username'], $_GET['password']);
	} else if ($user_id) {
				$user = get_userdata($user_id);
	} else if (ym_get('token') && ym_use_rss_token()) {
		$user = ym_get_user_by_token(ym_get('token'));
	} else {
		$user = $current_user;
	}

	$return = false;
	if (!$post_id) {
		$post_id = get_the_id();
	}
	if ($post_id) {
		$post = get_post($post_id);
			
		$purchasable = ym_post_is_purchasable($post_id);

		$is_published = ($post->post_status == 'publish');

		if ($allow_on_purchasable && $purchasable) {
			$return = true;
		} else if (isset($user->caps['administrator'])) {
			$return = true;
		} else if (!$is_published) {
					$return = false;
// logged out purchase
//		} else if (ym_check_ppp_cookie($post_id)) {
//			$return = true;
		} else {
			if ($user->ID > 0 && $purchasable && (ym_has_purchased_post($post_id, $user->ID) || ym_has_purchased_bundle_post_in($post_id, $user->ID) )) {
				$return = true;
			} else {
				$types = strtolower(get_post_meta($post_id, '_ym_account_type', true));
				//Logic check if no YM meta has been applied to the post return TRUE
				if(!$types){
					$return = true;
				}
				if(!is_user_logged_in() && $ym_sys->enable_metered){
					//metered access check
					$cookie = stripslashes($_COOKIE['ymmeter']);
					$cookie = unserialize($cookie);
					$posts = $cookie['posts'];
					if(is_array($posts)){
						if(in_array($post_id, $posts)){
							$return = true;

						}
					}
					//Check if FCF is enabled (this is a bad thing normally)
					if($ym_sys->enable_fcf_metered){
						if(stripos($_SERVER[‘HTTP_USER_AGENT’], ‘Googlebot’) !== false) {
					        $host = gethostbyaddr($_SERVER['REMOTE_ADDR']); 
					        if(stripos($host, 'googlebot') !== false){
					        	//ok we think this is Google
					        	$metered_act = strtolower($ym_sys->metered_account_types);
								$metered_act =explode(';', $metered_act);
								$metered_types = explode(';', $types);
								foreach ($metered_types as $metered_type) {
									if($metered_type = 'guest') $return = true;
									elseif(in_array($metered_type, $meterered_act)) $return = true;
								}
					        }
					    }
					}
				}
				$uat = ym_get_user_package_type($user->ID, true); //status is implied through the type.

				if (!$uat) {
					$uat = 'Guest';
				}

				if (in_array($uat, explode(';',$types))) {
									$return = true;
									
									 if ($pack_join = get_user_meta($user->ID, 'ym_account_type_join_date', TRUE)) {
										if ($hide_old_content = get_user_meta($user->ID, 'ym_hide_old_content', TRUE)) {
										   $post_date = strtotime($post->post_date);
										   $return = false;
										   
										   if ($pack_join < $post_date) {
											   $return = true;	
										   }
										}
									 }
				}
				if ($return == true) {
					// @TODO re-evaluate what is going on here
					$new_array = array();
					$min_dur = get_post_meta($post_id, '_ym_account_min_duration', true);
					if ($min_dur) {
						$min_dur = explode(';', $min_dur);

						foreach ($min_dur as $keyvalues) {
							$array = explode('=', $keyvalues);
							$new_array[$array[0]] = $array[1];
						}

						$min_dur = $new_array;
						$uat_min_dur = (int)$min_dur[strtolower(str_replace(' ','_',$uat))];

						if ($uat_min_dur > 0) {

													$reg = $user->user_registered;
													if ($sys->post_delay_start == 'pack_join') {
														if ($pack_join = get_user_meta($user->ID, 'ym_account_type_join_date', TRUE)) {
															$reg = date('Y-m-d', $pack_join);
														}
													}

							$reg = mktime(0,0,0,substr($reg, 5, 2), substr($reg, 8, 2), substr($reg, 0, 4));
							$user_at = $reg + (86400*$uat_min_dur);
							if ($user_at >= time()) {
								$return = false;
							}
						}
					}
				}
			}
		}
	}
		$return = apply_filters('ym_user_has_access_additional', $return, $post_id, $user, $allow_on_purchasable);

	return $return;
}

function ym_post_replace($matches, $override_message=false) {
	get_currentuserinfo();
	global $wpdb, $current_user, $ym_res, $ym_sys;

	//returns nothing in the event of an empty string
	if ($matches == '') {
		return '';
	}

	//user has access copes with validation against user level and ppp
	if (ym_user_has_access()) {
		$return = '<span class="ym_private_access">' . $matches . '</span>';
	} else {
		//by this time the user does not have access

		if (!ym_post_is_purchasable()) {
			if ($current_user->ID == 0) {
				$return = $ym_res->msg_header . $ym_res->private_text . $ym_res->msg_footer;
			} else {
				$return = $ym_res->msg_header . ($override_message ? $override_message:$ym_res->no_access) . $ym_res->msg_footer;
			}
		} else {
			$post_id = get_the_ID();
			if ($current_user->ID > 0) {
				$purchase_limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
				$purchased = ym_post_purchased_count($post_id);
				if ($purchase_limit && (($purchase_limit - $purchased) <= 0)) {
					$return = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->purchasable_at_limit . $ym_res->msg_footer . '</div>';
				} else {
					// TODO: refactor/split out
					$cost = get_post_meta($post_id, '_ym_post_purchasable_cost',1);

					if ($cost) {
						$product_id = get_post_meta($post_id, '_ym_post_purchasable_product_id',1);
						$title = get_the_title();
						$modules = get_option('ym_modules');

						$return = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->private_text_purchasable . $ym_res->msg_footer . '</div>'; //'<div style="margin-bottom:5px;width:100%;">' . $res->private_text_purchasable . '</div>';

						foreach ($modules as $module) {
							if (in_array($module, array('ym_free'))) {
								continue;
							}

							$obj = new $module();
							$button = $obj->gen_buy_now_button($cost, $title, true, $product_id);
							$return .= "<div style='width:100%;'>" . $button . "</div>";
						}
					} else {
						$return = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->purchasable_pack_only . $ym_res->msg_footer . '</div>';
					}
				}
			} else {
				// not logged in
				$return = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->login_first_text . $ym_res->msg_footer . '</div>';
			}
		}
				
		$return = '<div class="ym_private_no_access">' . $return . '</div>';
	}
	
	$return = ym_filter_message($return);

	return $return;
}

function ym_filter_message($message) {
	global $current_user, $ym_res;

	$logged_in = false;
	if ($current_user->ID > 0) {
		$logged_in = true;
	}

	/*
	 *  [[purchase_cost]] = Cost and currency of a purchasable post
	 * [[login_register]] = Login or register form
	 * [[login_register_links]] = Links for login and register
	 * [[login_link]] = Login link only
	 * [[register_link]] = Register link only
	 * [[account_types]] = A list of membership levels that can see this post/page
	 * [[duration]] = number of days that the user will have access for
	 * [[buy_now]] = buy now button for single posts
	 * [[paypal_button]] = Paypal image button
	 * [[this_page]] = This page URL
	 * [[p_id]] = This page/post ID
	 * [[auto_buy_post]] = When used as a redirect will forward the user to paypal to buy the post they wanted
	 */

	$post_id = get_the_ID();

	$currency = $ym_res->currency;
	$duration = get_post_meta($post_id, '_ym_post_purchasable_duration',1);
	if (!$duration) {
		$duration = __('unlimited', 'ym');
	}

	$cost = get_post_meta($post_id, '_ym_post_purchasable_cost',1) . ' ' . $currency;
	$login_register_links = (!$logged_in ? ym_get_login_register_links():'');
	$login_link = (!$logged_in ? ym_get_login_link():'');
	$register_link = (!$logged_in ? ym_get_register_link():'');
	$login_form = (!$logged_in ? ym_login_form():'');
	if (!$account_types = str_replace(';', ', ', get_post_meta($post_id, '_ym_account_type', true))) {
		if (ym_post_is_purchasable($post_id)) {
			$account_types = 'Purchasable Only';
		} else {
			$account_types = 'No access';
		}
	}
		
	$message = str_replace('[[purchase_cost]]', $cost, $message);
	$message = str_replace('[[login_register]]', $login_form, $message);
	$message = str_replace('[[login_register_links]]', $login_register_links, $message);
	$message = str_replace('[[login_link]]', $login_link, $message);
	$message = str_replace('[[register_link]]', $register_link, $message);
	$message = str_replace('[[account_types]]', $account_types, $message);
	$message = str_replace('[[duration]]', $duration, $message);
	$message = str_replace('[[this_page]]', get_permalink(), $message);
	$message = str_replace('[[p_id]]', get_the_ID(), $message);

	return $message;
}


/**
 * Handle responses from a Payment Gateway
 */
function ym_process_response() {
	// if a ym process request
		
	$ym_process = ym_request('ym_process');
	if (empty($ym_process)) {
		// nothing to do here....
		return;
	}

	$mod = ym_request('mod', ym_request('ym_process'));

	if (!$mod || $mod == 1) {
		die(__('FATAL ERROR: Missing parameter', 'ym'));
	}
	global $ym_active_modules;

	if (in_array($mod, $ym_active_modules)) {
			$obj = new $mod();
			$obj->process();
			exit;
	}
	die(__('Unknown/Inactive Gateway', 'ym'));
}

function ym_upgrade_response() {
	// if a ym subscribe request
	$ym_subscribe = ym_get('ym_upgrade');
	if (empty($ym_subscribe)) {
		return;
	}

	require_once(YM_PLUGIN_DIR_PATH . 'ym-subscribe.php');
	exit;
}

function ym_subscribe_response() {
	// if a ym subscribe request
	$ym_subscribe = ym_get('ym_subscribe');
	if (empty($ym_subscribe)) {
		return;
	}

	require_once(YM_PLUGIN_DIR_PATH . 'ym-subscribe.php');
	exit;
}

function ym_download_response() {
	if ($download_id = ym_get('ym_download_id')) {
		ym_download_file($download_id);
	}
}

function ym_thank_you_response() {
	// if a ym tos page request
	$ym_page = get_query_var('ym_thank_you');
	if (empty($ym_page)) {
		return;
	}

	require_once(YM_TEMPLATES_DIR . 'ym_thank_you.php');
	exit;
}

//Load language Files
function ym_load_lang() {
	$locale = get_locale();
	$ym_domain = 'ym';

	if (empty($locale)) {
		$locale = 'en_UK';
	}

	$mofile = YM_PLUGIN_DIR_PATH .'lang/'.$locale.'.mo';
	load_textdomain ($ym_domain, $mofile);
}

function ym_member_count() {
	global $wpdb, $ym_package_types;
	
	$sql = 'SELECT ID
			FROM ' . $wpdb->users;
	$users = $wpdb->get_results($sql);
	
	$counts = array();
	
	foreach ($ym_package_types->types as $account_type) {
		if ($account_type != 'Guest') {
			$counts[$account_type] = 0;
		}
	}
	
	foreach ($users as $user) {
		$ac = ym_get_user_account_type($user->ID);
		if ($ac == 'Guest') {
			$ac = 'Free';
		}
		
		$counts[$ac]++;
	}
	
	$html = '<ul>';
	foreach ($counts as $ac=>$num) {
		$html .= '<li>' . $ac . ': ' . $num . '</li>';
	}
	$html .= '</ul>';
	
	return $html;
}

function ym_suppress_enclosure_check() {
	add_filter('get_enclosed', 'ym_delete_enclosure');
	add_filter('rss_enclosure', 'ym_delete_enclosure');
	add_filter('atom_enclosure', 'ym_delete_enclosure');
}

function ym_delete_enclosure($data){
	global $post;
	
	if (ym_user_has_access($post->ID)) {
		return $data;
	} else {
		return '';
	}
}



if (isset($_GET['reinstall']) && $_GET['reinstall']) {
	add_filter('site_transient_update_plugins', 'ym_reinstall_transient_adjust', 10, 1);
}
function ym_reinstall_transient_adjust($trans) {
	global $ym_version_resp, $plugin_name;
	ym_check_version(TRUE);

	$data = new stdClass();
	$data->package = $ym_version_resp->version->current_download_url;
	$trans->response[$plugin_name] = $data;
	
	return $trans;
}

function ym_loaded() {
	// last globals
	global $current_user, $ym_user;
	get_currentuserinfo();

	if (is_user_logged_in()) {
		// as the user is logged in....
		$ym_user = new YourMember_User($current_user->ID);
	} else {
		// blank one
		$ym_user = new YourMember_User();
	}

	global $wpdb, $ym_auth, $ym_dl_db, $ym_dl_post_assoc, $ym_sys, $plugin_file;
	global $wp_upload, $ym_upload_root, $ym_upload_url;

	if (ym_get('ym_go') == 'support') {
		header('Location: ' . YM_SUPPORT_LINK);
		exit;
	}

	// TODO: tidy all these calls up again

	//Localization
	add_action ('init', 'ym_load_lang');
	// admin bar
	add_action('init', 'ym_admin_nav');
	add_action('admin_bar_menu', 'ym_admin_bar', 90);

	// context help (help in the top right)
	if (is_admin()) {
		add_action('ym_pre_admin_loader', 'ym_database_updater', 10, 1);

		// interrupt?
		if (ym_request('do_munch')) {
			ym_admin_loader();
			exit;
		}

		// main drag
		add_action('admin_menu', 'ym_admin_page');
		//Plugin Panel Hooks
		add_filter('plugin_action_links' , 'ym_action_link', 10, 2);

		// user edit
		add_action('user_edit_form_tag', 'ym_form_enctype');

		// conf bypasses
		ym_conf_bypass();

//		add_action('load-toplevel_page_ym/admin/ym-index', 'ym_context_help');
		add_action('load-toplevel_page_' . YM_ADMIN_FUNCTION, 'ym_context_help');
		add_action('load-your-members_page_' . YM_ADMIN_DIR . 'ym-about', 'ym_context_help');
	} else {
		// SSL
		add_action('init', 'ym_go_ssl');
		add_action('posts_selection', 'ym_go_ssl_pages');

		if($ym_sys->enable_metered) add_action('init','ym_check_metered_access');

		add_action('get_footer', 'ym_affiliate_link');
	}
	
	// call version check
	ym_check_version();

	if ($ym_auth->ym_check_key()) {
		$wp_upload = wp_upload_dir();
		if ($wp_upload['error']) {
			if (is_admin() && ym_get('page') == YM_ADMIN_FUNCTION && !ym_request('ym_page')) {
				echo '<div id="message" class="error"><p>' . $wp_upload['error'] . '</p></div>';
			}
		} else {
			$ym_upload_root = $wp_upload['path'];
			$ym_upload_url = $wp_upload['url'];
		}

	ym_create_log_constants(); //Must be first for any logging that occurs from hereonin 

	ym_suppress_enclosure_check();

	add_action('mod_rewrite_rules', 'ym_block_wp_login_action_register');
	add_action('admin_init', 'ym_block_wp_login_action_register_flush');

	if (!is_admin()) {
		ym_download_response(); //checks for a download id in the url

		global $ym_manage_access;
		$ym_manage_access = new YourMember_Manage_Access();
		if ($ym_sys->protect_mode) {
			add_action('template_redirect', array($ym_manage_access, 'exit_check'));
		}

		add_shortcode('private', 'ym_shortcode_parse');
		add_shortcode('no_access', 'ym_shortcode_parse');
		add_shortcode('user_has_access', 'ym_shortcode_parse');

		add_shortcode('ym_user_profile', 'ym_edit_custom_field_standalone');
		add_shortcode('ym_rss_token', 'ym_get_rss_token');

		add_shortcode('ym_upgrade', 'ym_shortcode_parse');
		add_shortcode('ym_packs', 'ym_shortcode_parse');
		add_shortcode('private_or', 'ym_shortcode_parse');
		add_shortcode('private_and', 'ym_shortcode_parse');
		add_shortcode('ym_membership_content', 'ym_membership_content_shortcode');

		// TODO: Deprecate 11.0.6
		add_shortcode('user_account_is', 'ym_shortcode_parse');
		add_shortcode('private_username_is', 'ym_shortcode_parse');
		// Replace with
		add_shortcode('ym_user_is', 'ym_user_is');
		add_shortcode('ym_user_is_not', 'ym_user_is_not');

		add_shortcode('ym_user_custom_is', 'ym_user_custom_is');
		add_shortcode('ym_user_custom_is_not', 'ym_user_custom_is_not');

		add_shortcode('ym_profile', 'ym_get_user_profile');
		add_shortcode('ym_purchase_history', 'ym_get_user_purchase_history_shortcode');
		add_shortcode('ym_gateway_cancel', 'ym_get_user_unsub_button_gateway');

		add_shortcode('ym_gravatar', 'ym_gravatar_render');

		// start content

			// buy now
			add_shortcode('ym_buy_content', 'ym_buy_button_content');
			add_filter('the_content', 'ym_buy_button_content_filter', 1, 1);
			add_shortcode('ym_buy_bundle', 'ym_buy_button_bundle');
			add_filter('the_content', 'ym_buy_button_bundle_filter', 1, 1);
			// end buy now

			// indexy
			add_shortcode('ym_all_content', 'ym_get_all_content_buttons');
			add_shortcode('ym_all_bundles', 'ym_get_all_bundle_buttons');

			add_shortcode('ym_featured_content', 'ym_get_featured_content_buttons');
			// end indexy

		add_shortcode('ym_content_units_left', 'ym_content_units_left');
		add_shortcode('ym_bundle_units_left', 'ym_bundle_units_left');//pass ID
		add_shortcode('ym_content_units_sold', 'ym_content_units_sold');
		add_shortcode('ym_bundle_units_sold', 'ym_bundle_units_sold');//pass ID
		add_shortcode('ym_content_units_limit', 'ym_content_units_limit');
		add_shortcode('ym_bundle_units_limit', 'ym_bundle_units_limit');//pass ID

		add_shortcode('ym_content_expiry_date', 'ym_post_purchased_expiry');// so that an expiry date can be shown once a post has been purchased
		add_shortcode('ym_content_purchase_date', 'ym_post_last_purchased_date'); //so that a purchase date can be shown once a post has been purchased
		add_shortcode('ym_bundle_expiry_date', 'ym_bundle_purchased_expiry');
		add_shortcode('ym_bundle_purchase_date', 'ym_bundle_last_purchased_date');

		// end content

		add_shortcode('ym_register', 'ym_shortcode_parse');
		add_shortcode('ym_login', 'ym_shortcode_parse');
		
		add_shortcode('ym_drip_date', 'ym_shortcode_parse');
		
		add_shortcode('ym_user_password_form', 'ym_user_password_form');
		add_shortcode('ym_user_profile_form', 'ym_user_profile_form');
		add_shortcode('ym_user_unsubscribe', 'ym_user_unsubscribe');
		
		add_shortcode('ym_promote', 'ym_shortcode_aff_link');
	
		$hook = ($ym_sys->download_hook ? $ym_sys->download_hook : 'download');
		add_shortcode($hook, 'ym_dl_ins');
	
		//Profile Data
		add_shortcode('ym_user_register_date', 'ym_shortcode_user');
		add_shortcode('ym_user_email', 'ym_shortcode_user');
		add_shortcode('ym_user_first_name', 'ym_shortcode_user');
		add_shortcode('ym_user_last_name', 'ym_shortcode_user');
		add_shortcode('ym_user_username', 'ym_shortcode_user');
		add_shortcode('ym_user_description', 'ym_shortcode_user');
		add_shortcode('ym_user_custom', 'ym_shortcode_user');
	}
		
	//CSS
	add_action( 'wp_enqueue_scripts', 'ym_styles' );
	add_action( 'login_enqueue_scripts', 'ym_login_styles');

//	if ($ym_auth->ym_check_key()) {
		add_action('init', array('ym_cron', 'init'), 20);// run manual cron if needed, check schedules if not

		add_filter('wp_authenticate_user', 'ym_authenticate');

		if ($ym_sys->modified_registration) {
			add_action('user_register', 'ym_register', 10, 1);
			add_action('register_form', 'ym_register_form', 10, 6);
			add_action('register_post', 'ym_register_post',10, 3);
		} else {
			add_action('user_register', 'ym_register_default', 10, 1);
		}

		/**
		WP Admin block/login redirect
		Logout redirect
		*/
		add_action('login_head', 'ym_login_redirect');
		add_action('wp_login', 'ym_wp_login', 1, 2);
		add_action('admin_head', 'ym_stop_wp_admin', 1);
		add_action('wp_logout', 'ym_wp_logout', 1);

		/**
		Loginism
		*/

		add_action('login_head', 'ym_login_js');
		// fire on non login page
		add_action('wp_head', 'ym_login_js');

		/**
		Login Register
		*/
		// custom messages for login form
		add_filter('login_message', 'ym_login_message');
		// remove password string?
//		add_action('login_head', 'ym_login_remove_password_string');


		/**
		Login themeing
		*/
		// login page overrides
		if ($ym_sys->wp_login_header_url) {
			add_filter('login_headerurl', 'ym_login_headerurl');
			add_filter('login_headertitle', 'ym_login_headertitle');
		}
		if ($ym_sys->wp_login_header_logo) {
			add_action('login_head', 'ym_login_header_logo');
		}

		add_action('init', 'ym_subscribe_response');
		add_action('init', 'ym_upgrade_response');
		
		add_action('init', 'ym_register_catch_gateway');

		add_action('admin_enqueue_scripts', 'ym_admin_script_init');
		add_action('wp_head', 'ym_js_varibles');

		add_action('init', 'ym_process_response');
		add_action('parse_query', 'ym_thank_you_response');
		add_action('init', 'ym_init');
		
		/**
		custom fields
		**/
		if (is_admin()) {
			// hook for catching core fields and updating out own (wp-admin)
			add_action('profile_update', 'ym_update_custom_fields');
		}
		add_action('show_user_profile', 'ym_edit_custom_fields');
		add_action('edit_user_profile', 'ym_edit_custom_fields');

//		add_filter('print_scripts_array', 'ym_fix_tinymce_conflict');
		add_filter('rewrite_rules_array','ym_rewrite_rule');

		/**
		widgets
		*/
		add_action('init', 'ym_widget_init');
		add_action('init', 'ym_sidebar_init');
		add_action('init', 'ym_register_sidebar_init');
		add_filter('widget_text', 'do_shortcode');

		/**
		Email
		*/
		// Replaces the From Name and Address with custom info
		if ($ym_sys->filter_all_emails) {
			add_filter('wp_mail_from', 'ym_mail_from');
			add_filter('wp_mail_from_name', 'ym_mail_from_name');
		}

		/**
		RSS Repair
		*/
		add_action('atom_head', 'ym_rss_stop_payments');
		add_action('rdf_head', 'ym_rss_stop_payments');
		add_action('rss_head', 'ym_rss_stop_payments');
		add_action('rss2_head', 'ym_rss_stop_payments');

		if (is_admin()) {
			// new data export/import
			if (ym_post('ym_exporting_users')) {
				ym_export_users(
					ym_post('offset', 0),
					ym_post('limit', 300),
					ym_post('bkpackagetype', 'all'),
					ym_post('bkpackage', 'all'),
					ym_post('bkinactive', 0)
				);
				exit;
			}
			ym_import_users_from_csv(); //check for CSV import request in post

//			add_action('after_plugin_row','ym_info_note', 10, 3);

			// only add TinyMCE buttons to Post/Page/Custom Post Type new Content/Edit Content WP Editor Field
			// if user has access to admin
			// and if the request_uri matches a known post editor location
			if (ym_admin_user_has_access(true) && strpos($_SERVER['REQUEST_URI'], 'wp-admin/post')) {
				add_action('add_meta_boxes', 'ym_meta_box_setup');
				add_action('save_post', 'ym_account_save');
				add_action('init', 'ym_tinymce_addbuttons');
			}

			// tos check
//			ym_tos_check();

			// hooks that can result in a dialog/iframe
			add_action('admin_notices', 'ym_get_advert');
			$ym_upgrade_action = ym_check_upgrade();

			// lightbox and message hook
			if (ym_get(YM_ADMIN_FUNCTION . '_activated')) {
				add_action('admin_notices', 'ym_do_welcome_box');
				add_action('admin_notices', 'ym_activated_thanks_box');
			} else if ($ym_upgrade_action) {
				add_action('admin_notices', 'ym_do_welcome_box');
				add_action('admin_notices', 'ym_upgrade_nag_box');
			}
		}
	}

	do_action('ym_loaded_complete');
}

add_action('plugins_loaded', 'ym_loaded');
