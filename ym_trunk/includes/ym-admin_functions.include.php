<?php

/*
* $Id: ym-admin_functions.include.php 2617 2013-08-05 14:19:40Z tnash $
* $Revision: 2617 $
* $Date: 2013-08-05 15:19:40 +0100 (Mon, 05 Aug 2013) $
*/

$ym_nav = array();

//not as it sounds. checks whether the current user can access the admin pages at all
function ym_admin_user_has_access($account_interface=false) {
	get_currentuserinfo();
	global $ym_sys, $current_user;
	
	$return = false;

	$cap = 'activate_plugins';

	if (!$ym_sys) {
		// activation mode
		return current_user_can($cap);
	}
	
	$check_against = ($account_interface ? $ym_sys->account_interface_admin_role : $ym_sys->admin_role);
	
	if ($current_user->ID) {
		switch ($check_against) {
			case 'administrator':
				$cap = 'activate_plugins';
				break;
			case 'editor':
				$cap = 'moderate_comments';
				break;
			case 'author':
				$cap = 'publish_posts';
				break;
			case 'contributor':
				$cap = 'edit_posts';
				break;
			case 'subscriber':
				$cap = 'read';
				break;
		}
	
		$return = current_user_can($cap);
	}
	
	return $return;
}

function ym_superuser($user_id = FALSE) {
	if (!$user_id) {
		return current_user_can('administrator');
	} else {
		return user_can($user_id, 'administrator');
	}
}

/**
Box widgets
*/
function ym_box_top($title='', $collapse=false, $is_collapsed=false) {
	echo '<div class="postbox ym_postbox ';
	
	if ($collapse && $is_collapsed) {
		echo ' closed';
	}
	
	echo '" style="margin-top: 10px;">';
	if ($collapse) {
		echo '<div class="ymhandlediv handlediv" title="' . __('Click to Toggle', 'ym') . '"><br /></div>';
	}

	echo '<h3 class="hndle';
	if ($collapse) {
		echo ' ymcancollapse';
	}
	echo '">' . $title . '</h3>';

	echo '<div class="inside" style="overflow: auto;">';
}

function ym_start_box($title , $return=true){

	$html = '	<div class="postbox" style="margin: 5px 0px;">
					<h3>' . $title . '</h3>
					<div class="inside">';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ym_end_box($return=true) {
	$html = '</div>
		</div>';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ym_box_bottom() {
	echo '</div></div>';
}
/**
End Widgets
*/


function ym_draw_hidden($key, $value) {
	return '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
}

function ym_convert_to_currency($num) {
	if (strpos($num, '.') == false) {
		$num = $num . '.00';
	} else {
		$num = sprintf("%01.2f", (float)$num);
	}
	return $num;
}

function ym_remote_request($url, $error_string=true) {
	if (!class_exists('WP_Http')) {
		include_once(ABSPATH . WPINC . '/class-http.php');
	}
	$request = new WP_Http;
	$result = $request->request($url);
	if (is_wp_error($result)) {
		return FALSE;
	}
	if ($result['response']['code'] == 200) {
		// success
		return $result['body'];
	} else {
		return FALSE;
	}
}

/**
Wizard
*/
function ym_wizard() {
	$steps = array(
		__('Enable registration of users on the site', 'ym'),
		__('Setup and configure packages (subscriptions)', 'ym'),
		__('Configure registration fields shown on default registration', 'ym'),
		__('Configure payment details and set currency', 'ym'),
		__('Configure content protection and default security settings', 'ym')
	);

	$links = array(
		'jQuery(\'#' . YM_ADMIN_FUNCTION . '\').tabs({selected: 4});jQuery(\'#ym-top-advanced-security\').tabs({selected: 1});',
		'jQuery(\'#' . YM_ADMIN_FUNCTION . '\').tabs({selected: 2});jQuery(\'#ym-top-members\').tabs({selected: 0});',
		'jQuery(\'#' . YM_ADMIN_FUNCTION . '\').tabs({selected: 1});jQuery(\'#ym-top-members\').tabs({selected: 1});',
		'jQuery(\'#' . YM_ADMIN_FUNCTION . '\').tabs({selected: 2});jQuery(\'#ym-top-membership-packages\').tabs({selected: 4});',
		'jQuery(\'#' . YM_ADMIN_FUNCTION . '\').tabs({selected: 4});jQuery(\'#ym-top-advanced-security\').tabs({selected: 0});',
	);

	return array($steps, $links);
}
function ym_wizard_render() {
	global $ym_auth;
	if ($ym_auth->ym_check_key()) {
		if (ym_get('wizard')) {
			update_option('ym_wizard_bar_step', ym_get('wizard'));
		}

		$step = get_option('ym_wizard_bar_step');
		$step = $step ? $step : 0;

		list($steps, $links) = ym_wizard();

		$steps = apply_filters('ym_wizard_steps', $steps);
		$links = apply_filters('ym_wizard_links', $links);

		$step_count = sizeof($steps);

		if ($step < $step_count) {
			// Its a wizard Harry
			echo '<div id="ym_wizard">';
			echo '<a href="' . YM_ADMIN_URL . '&wizard=' . ($step_count + 1) . '" id="ym_wizard_close"><img src="' . YM_IMAGES_DIR_URL . 'cross.png" /></a>';
			echo '<h3>' . __('Get Started with', 'ym') . ' ' . YM_ADMIN_NAME . '</h3>';

			echo '<div>';

			$percent = (100 / ($step_count + 1)) * ($step + 1);
			echo '<div id="ym_wizard_progress"></div>
	<script type="text/javascript">
	' . "
		jQuery(document).ready(function() {
			jQuery('#ym_wizard_progress').progressbar({
				value: " . $percent . "
			})
		});
	</script>";

			echo '<span id="ym_wizard_step">';
			echo 'Step ' . ($step + 1) . '/' . $step_count . ' ';
			echo '<a href="#nowhere" onclick="' . $links[$step] . '">';
			echo $steps[$step];
			echo '</a>';
			echo '</span>';

			echo '<a href="' . YM_ADMIN_URL . '&wizard=' . ($step + 1) . '" id="ym_wizard_next">' . __('I have done this step', 'ym') . ' <img src="' . YM_IMAGES_DIR_URL . 'tick.png" /></a>';
			echo '</div>';

			echo '</div>';

			if (ym_get('wizard')) {
				echo '
<script type="text/javascript">
	jQuery(document).ready(function() {
		setTimeout(\'ym_wizard_stepper()\', 500);
	});
	function ym_wizard_stepper() {
		' . $links[$step] . '
	}
</script>
';
			}
		} else if ($step == $step_count && ym_get('wizard')) {
			echo '<div id="ym_wizard" class="ym_wizard_completed">';
			echo '<a href="' . YM_ADMIN_URL . '&wizard=' . ($step_count + 1) . '" id="ym_wizard_close"><img src="' . YM_IMAGES_DIR_URL . 'cross.png" /></a>';
			echo '<h3>' . __('Get Started with Your Members', 'ym') . '</h3>';
			echo '<div>';
			echo '<div id="ym_wizard_progress"></div>
	<script type="text/javascript">
	' . "
		jQuery(document).ready(function() {
			jQuery('#ym_wizard_progress').progressbar({
				value: 100
			})
		});
	</script>";
			echo '<span id="ym_wizard_step">';
			echo __('Congratulations, you have completed the basic setup', 'ym');
			echo '</span>';
			echo '</div>';
			echo '</div>';
		}
	}
}
/**
End Wizard
*/

function ym_admin_script_init() {
	$page = ym_get('page', '');
	$test = explode('/', $_SERVER['REQUEST_URI']);
	$test = array_pop($test);
	$test = explode('?', $test);
	$test = $test[0];

	// commons
	wp_enqueue_script('jquery');
	if (YM_WP_VERSION >= '3.6') {
		//http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.js
		wp_deregister_script( 'jquery-ui-core' );
		wp_deregister_script( 'jquery-ui-dialog' );
		wp_deregister_script( 'jquery-ui-tabs' );

		wp_enqueue_script('jquery-ui-core','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js');

	}

	add_action('admin_head', 'ym_js_varibles');

	wp_enqueue_script('ym_admin_js_common', YM_JS_DIR_URL . 'ym_admin_common.js', array('jquery'), YM_PLUGIN_VERSION);

	// make ui css common

	wp_enqueue_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/themes/base/jquery-ui.css');
	wp_enqueue_style('jquery-ui-override', YM_CSS_DIR_URL . 'ym-jquery_ui_override.css');

	if ($page == YM_ADMIN_FUNCTION || defined('YM_ADMIN_IFRAME')) {
		add_thickbox();

		wp_enqueue_style('ym_admin_css', YM_CSS_DIR_URL . 'ym_admin.css' , false, YM_PLUGIN_VERSION, 'all');
		wp_enqueue_style('ym_icons_css', YM_CSS_DIR_URL . 'ym_icons.css' , false, YM_PLUGIN_VERSION, 'all');
		if (YM_WP_VERSION >= '3.6') {
			wp_enqueue_script('jquery-ui-dialog','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js');
			wp_enqueue_script('jquery-ui-tabs','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js');
		}
		else{
			wp_enqueue_script('jquery-ui-dialog');
			wp_enqueue_script('jquery-ui-tabs');
		}
		wp_enqueue_script('suggest');

		wp_enqueue_script('ym_jq_table', YM_JS_DIR_URL . 'jquery.fixedtable.js', array('jquery'), YM_PLUGIN_VERSION);
		wp_enqueue_script('ym_admin_js', YM_JS_DIR_URL . 'ym_admin.js', array('jquery', 'jquery-ui-sortable', 'ym_jq_table'), YM_PLUGIN_VERSION);
		wp_enqueue_script('ym-admin-ajax', YM_JS_DIR_URL . 'ym_admin_ajax.js', array('jquery-color', 'jquery'), YM_PLUGIN_VERSION);
		wp_enqueue_script('ym_formbuilder_js', YM_JS_DIR_URL . 'ym_form_builder.js', array('jquery', 'jquery-ui-sortable', 'ym_jq_table', 'ym_admin_js'), YM_PLUGIN_VERSION); 
		wp_enqueue_script('ym-admin-post', YM_JS_DIR_URL . 'ym_admin_post.js', array('jquery-ui-datepicker', 'jquery-ui-core'), YM_PLUGIN_VERSION);

		// wp editor anyway....
		wp_enqueue_script('editor');

		$step = get_option('ym_wizard_bar_step');

		list($steps, $links) = ym_wizard();
		$steps = apply_filters('ym_wizard_steps', $steps);
		$step_count = sizeof($steps);

		if ($step < $step_count) {
			if (YM_WP_VERSION >= '3.3') {
				wp_enqueue_script('jquery-ui-widget');
				wp_enqueue_script('jquery-ui-progressbar');
			} else {
				wp_enqueue_script('jquery-ui-widget', 'https://jquery-ui.googlecode.com/svn/tags/latest/ui/jquery.ui.widget.js', 'jquery-ui-core', YM_PLUGIN_VERSION, TRUE);
				wp_enqueue_script('jquery-ui-progressbar', 'https://jquery-ui.googlecode.com/svn/tags/latest/ui/jquery.ui.progressbar.js', 'jquery-ui-core', YM_PLUGIN_VERSION, TRUE);
				wp_enqueue_style('jquery-ui-progressbar-css', 'https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.progressbar.css');
			}
		}

		if (YM_WP_VERSION >= '3.3') {
			wp_enqueue_script('jquery-ui-datepicker');
		} else {
			wp_enqueue_script('jquery-ui-datepicker', 'https://jquery-ui.googlecode.com/svn/tags/latest/ui/jquery.ui.datepicker.js', 'jquery-ui-core', YM_PLUGIN_VERSION, TRUE);
			wp_enqueue_style('jquery-ui-datepicker-css', 'https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.datepicker.css');
		}
	}
	if ($test == 'profile.php') {
		add_thickbox();

		wp_enqueue_style('ym_admin_css', YM_CSS_DIR_URL . 'ym_admin.css' , false, YM_PLUGIN_VERSION, 'all');
		wp_enqueue_script('ym-admin-ajax', YM_JS_DIR_URL . 'ym_admin_ajax.js', array('jquery-color', 'jquery'), YM_PLUGIN_VERSION);
	}
	if ($test == 'post.php' || $test == 'post-new.php') {
		wp_enqueue_script('ym-admin-post', YM_JS_DIR_URL . 'ym_admin_post.js', array('jquery-ui-datepicker', 'jquery-ui-core'), YM_PLUGIN_VERSION);
	}
}
function ym_js_varibles() {
	echo '
<script type="text/javascript">' . "
	var ajaxurl = '" . admin_url('admin-ajax.php') . "';
	var ymdateFormat = '" . YM_DATEPICKER . "';
	var ymadminfunction = '" . YM_ADMIN_FUNCTION .  "';
</script>
";
}

function is_ym_admin() {
	get_currentuserinfo();
	global $current_user;

	$return = false;

	if (isset($current_user->caps['administrator'])) {
		$return = true;
	}

	return $return;

}

// conf bypass
function ym_conf_bypass() {
	if (get_option('disable_plugin_store') == 1) {
		define('DISABLE_PLUGIN_STORE', TRUE);
	}
	if (get_option('disable_versioning') == 1) {
		// freeze and disable the version checker
		define('DISABLE_VERSIONING', TRUE);
	}
}
/**
Navigation
*/
function ym_admin_nav() {
	if (!is_user_logged_in()) {
		return;
	}

	global $ym_auth, $ym_version_resp, $ym_sys;

	$root_url = YM_ADMIN_URL . '&ym_page=';

	if ($ym_auth->ym_check_key()) {
		// define menu base
		$pages = array(
			__('Dashboard', 'ym')		=> 'ym-dashboard',
			__('Members', 'ym')			=> array(
				__('Management', 'ym')							=> 'ym-members',
				__('Custom Registration Fields', 'ym')			=> 'ym-members-customfields',
				__('Group Membership', 'ym')					=> 'ym-members-groups',
			),
			__('Memberships', 'ym')		=> array(
				__('Packages', 'ym')							=> 'ym-membership-packages',
				__('Package Types', 'ym')						=> 'ym-membership-package_types',
				__('Registration Flows', 'ym')					=> 'ym-membership-registration_flows', 
				__('Coupons', 'ym')								=> 'ym-membership-coupons',
				__('Payment Gateways', 'ym')					=> 'ym-payment-gateways',
			),
			__('Content', 'ym')			=> array(
				__('Global Content Options', 'ym')				=> 'ym-content-options',
				__('Download Manager', 'ym')					=> 'ym-content-downloads',
				__('Mass Content Setup', 'ym')					=> 'ym-content-mass_content_setup',
				__('Individual Purchase', 'ym')					=> 'ym-content-individual_purchase',
				__('Content Bundles', 'ym')						=> 'ym-content-bundles',
			),
			__('Advanced', 'ym')		=> array(
				__('Security', 'ym')							=> 'ym-advanced-security',
				__('Registration', 'ym')						=> 'ym-advanced-registration',
				__('Cron', 'ym')								=> 'ym-advanced-cron',
				__('Redirects', 'ym')							=> 'ym-advanced-redirects',
				__('Messages', 'ym')							=> 'ym-advanced-messages',
				__('Email', 'ym')								=> 'ym-advanced-email',
				__('Payment Gateway Emails', 'ym')				=> 'ym-advanced-payment_gateway_email',
				__('Import/Export Config', 'ym')				=> 'ym-advanced-config',
			),
			__('Logs', 'ym')			=> array(
				__('Logs', 'ym')								=> 'ym-logs',
				__('IPN', 'ym')									=> 'ym-logs-ipn',
				__('Status Updates', 'ym')						=> 'ym-logs-status_updates',
				__('Content Purchase', 'ym')					=> 'ym-logs-content_purchase',
			),
		);

		if (defined('ym_dev') && $ym_sys->dev_tools) {
			$pages[__('Advanced', 'ym')][__('Dev Tools', 'ym')] = 'ym-dev-tools';
		}
		
		$pages = apply_filters('ym_plugin_preappstore', $pages);//legacy
		$pages = apply_filters('ym_navigation', $pages);

		// mailmanager patch
		foreach ($pages as $top => $sub) {
			if (is_array($sub)) {
				foreach ($sub as $item => $menu) {
					$pages[$top][$item] = str_replace('=', '---', str_replace('&', '--', $menu));
				}
			}
		}
		
		global $ym_nav;
		$ym_nav = $pages;

		do_action('ym_navigation_loaded');
	}
}

/**
API Functions
Does not allow overrides!
parent_menu is top level tab
child_menu is the tab name
admin_page_file the admin/file to load (or task to call)
*/
function ym_admin_add_menu_page($parent_menu, $child_menu, $admin_page_file) {
	global $ym_nav;
	if (!isset($ym_nav[$parent_menu]))
		$ym_nav[$parent_menu] = array();
	if (!isset($ym_nav[$parent_menu][$child_menu]))
		$ym_nav[$parent_menu][$child_menu] = $admin_page_file;
	return;
}
function ym_admin_remove_menu_page($parent_menu, $child_menu) {
	global $ym_nav;
	if (isset($ym_nav[$parent_menu][$child_menu]))
		unset($ym_nav[$parent_menu][$child_menu]);
	return;
}
/**
End Api Functions
*/

$target_index = 0;
function ym_admin_menu() {
	global $ym_auth, $ym_nav, $ym_version_resp, $target_index;
	$pages = $ym_nav;

	$root_url = YM_ADMIN_URL . '&ym_page=';
		echo '
<div id="' . YM_ADMIN_FUNCTION . '">
';
	if ($ym_auth->ym_check_key()) {
		$target = isset($_GET['ym_nav']) ? $_GET['ym_nav'] : '';
		if ($target) {
			$target = str_replace('ym', 'ym-top', $target);
		}
		echo '
<ul>
';
		$index = 0;
		foreach ($pages as $page => $subpages) {
			if (is_array($subpages)) {
				$first = array_shift($subpages);
				$first = str_replace('ym', 'ym-top', $first);
				if (FALSE === strpos($first, 'ym-top')) {
					$first = 'ym-top' . $first;
				}
				
				echo '<li><a href="#' . $first . '">' . $page . '</a></li>' . "\n";
				if ($first == $target) {
					$target_index = $index;
				}
			} else {
				$first = '';
				echo '<li><a href="#' . $subpages . '">' . $page . '</a></li>' . "\n";
			}
			$index++;
		}
		echo '
</ul>
';

		foreach ($pages as $top => $subpages) {
			if (is_array($subpages)) {
				$first = $subpages;
				$first = array_shift($first);
				$first = str_replace('ym', 'ym-top', $first);
				if (FALSE === strpos($first, 'ym-top')) {
					$first = 'ym-top' . $first;
				}
				
				echo '<div id="' . $first . '" class="ym_subtabs">';
				echo '<ul>';

				foreach ($subpages as $page => $url) {
					echo '<li><a href="#' . $url . '">' . $page . '</a></li>' . "\n";
				}

				echo '</ul>';
				foreach ($subpages as $page => $url) {
					echo '<div id="' . $url . '" class="ym_admin_iframe"></div>' . "\n";
				}
				echo '</div>';
			} else {
				if (!$subpages) { 
				} else if ($subpages != 'ym-dashboard') {
					echo '<div id="' . $subpages . '" class="ym_admin_iframe">'; 
					echo '<iframe src="' . $root_url . $subpages . '" style="width: 100%; height: 800px;"></iframe>'; 
					echo '</div>'; 
				} else if ($subpages == 'ym-dashboard') {
echo '
<div id="ym-dashboard">';
include(YM_PLUGIN_DIR_PATH . '/admin/ym-dashboard.php');
echo '
</div>
';
				}
			}
		}
	}
}
function ym_admin_menu_end() {
	global $ym_nav, $target_index;
	$pages = $ym_nav;
	
	$target = ym_get('ym_tab', $target_index);
	
	echo '
</div>

<script type="text/javascript">
/*<![CDATA[*/
';

echo "
	jQuery(document).ready(function() {
		if (jQuery('#' + ymadminfunction).tabs) {
			jQuery('#' + ymadminfunction).tabs({
				select: function(event, ui) {
					var url = ui.tab.href;
					url = url.split('#');
					url = url[1];

					jQuery('.ym_its_a_tab').remove();

					if (jQuery('#' + url).hasClass('ym_subtabs')) {
						var targettab = jQuery('#' + url).tabs('option', 'selected');
						jQuery('#' + url).tabs('option', 'selected', 1);
						jQuery('#' + url).tabs('option', 'selected', 0);
						jQuery('#' + url).tabs('option', 'selected', targettab);
					}
					
					ym_tab_change(ui.index);
				}
			});
		}
";
		
$this_index = 0;
foreach ($pages as $top => $subpages) {
	if (is_array($subpages)) {
		$first = $subpages;
		$first = array_shift($first);
		$first = str_replace('ym', 'ym-top', $first);
		if (FALSE === strpos($first, 'ym-top')) {
			$first = 'ym-top' . $first;
		}

		$this_index++;
		$index = ($target == $this_index) ? ym_get('ym_subtab', 0) : -1;

		echo "
		jQuery('#" . $first . "').tabs({
			select: function(event, ui) {
				var url = ui.tab.href;
				url = url.split('#');
				url = url[1];
				var idurl = url;
				
				test = url.split('_');
				if (test[0] == 'other') {
					url = url.replace('other_', 'ym-other&amp;action=');
					
					url = url.replace('--', '&amp;');
					url = url.replace('---', '=');
				}
				
				jQuery('#' + idurl).html('<iframe class=\"ym_its_a_tab\" style=\"width: 100%; height: 800px;\" src=\"" . YM_ADMIN_URL . "&ym_page=' + url + '\"></iframe>');
			},
			selected: " . $index . "
		});
		";
	}
}

if ($target) {
	echo "
		jQuery('#' + ymadminfunction).tabs({
			selected: " . $target . "
		});
";
}

echo "
	});
";

echo '
/*]]>*/
</script>
';
}

function ym_admin_index() {
	require_once(YM_PLUGIN_DIR_PATH . 'admin/ym-index.php');
}

/**
Navigation Driver and YM iFrame
*/
function ym_admin_page() {
	global $ym_auth, $ym_nav, $ym_sys, $ym_user;
	
	$func = 'ym_admin_loader';
	$read_func = 'ym_admin_read_func';
	$access = 'manage_options';

	if (ym_admin_user_has_access()) {
		add_menu_page(YM_ADMIN_NAME, YM_ADMIN_NAME, 'read', YM_ADMIN_FUNCTION, 'ym_admin_index', YM_IMAGES_DIR_URL . 'logo_thumb.jpg');

		if ($ym_auth->ym_check_key()) {
			$x=0;
			if (count($ym_nav) > 1) {
				foreach ($ym_nav as $page => $subpages) {
					if (!is_array($subpages) && $subpages == 'ym-dashboard') {
						continue;
					}
					$x++;
					add_submenu_page(YM_ADMIN_FUNCTION, __($page, 'ym'), __($page, 'ym'), 'read', 'admin.php?page=' . YM_ADMIN_FUNCTION . '&amp;ym_tab=' . $x);
				}
			}
		}
		add_submenu_page(YM_ADMIN_FUNCTION, __('About', 'ym'), __('About', 'ym'), 'read', YM_ADMIN_FUNCTION . '_about', $func);
	}

	// Profile Tab
	add_submenu_page('profile.php', __('Membership Details', 'ym'), __('Membership Details', 'ym'), 'read', 'ym-profile', $read_func);
	if (!$ym_sys->hide_membership_content) {
		add_submenu_page('profile.php', __('Members Content', 'ym'), __('Members Content', 'ym'), 'read', 'ym-profile_content', $read_func);
	}
	if ($ym_user->child_accounts_allowed) {
		add_submenu_page('profile.php', __('Group Membership', 'ym'), __('Group Membership', 'ym'), 'read', 'ym-profile_group_membership', $read_func);
	}


	// Others/Plugins
	$ym_other_hook = FALSE;
	$ym_page = ym_request('ym_page', FALSE);
	if (FALSE !== (strpos($ym_page, 'other'))) {
		$other_action = ym_get('action');
		if (!$other_action) {
			$r = explode('_', $ym_page);
			$other_action = $r[1];
		}
		$ym_other_hook = 'action';
	}

	if (substr($ym_page, 0, 8) == 'ym-hook-') {
		$ym_other_hook = $ym_page;
	}

	if ($ym_page && $ym_auth->ym_check_key()) {
		if ($ym_other_hook) {
			if ($ym_other_hook != 'action') {
				ym_admin_iframe(false, $ym_other_hook);
			} else {
				_doing_it_wrong(__FUNCTION__, 'Use ym-hook-' . $other_action . ' as the Page Hook instead', '11.2');
				// should be deprecated....
				ym_admin_iframe(false, 'ym_admin_other', $other_action);
			}
			exit;
		} else if (file_exists(YM_PLUGIN_DIR_PATH . 'admin/' . $ym_page . '.php')) {
			return ym_admin_iframe($ym_page);
		} else {
			header('HTTP/1.0 404 Not Found');
			exit;
		}
	}

} // end_of ym_admin_page()
/**
admin bar
*/
function ym_admin_bar() {
	global $wp_admin_bar, $ym_sys;

	if (ym_admin_user_has_access(TRUE)) {
		$wp_admin_bar->add_menu(
			array(
				'id'		=> YM_ADMIN_FUNCTION . '_adb',
				'title'		=> YM_ADMIN_NAME,
				'href'		=> YM_ADMIN_URL
			)
		);
		global $ym_nav;
		$count = 0;
		foreach ($ym_nav as $page => $subpages) {
			$id = 'ym_adb_' . strtolower($count);
			
			$wp_admin_bar->add_menu(
				array(
					'parent'	=> YM_ADMIN_FUNCTION . '_adb',
					'id'		=> $id,
					'title'		=> $page,
					'href'		=> YM_ADMIN_URL . '&ym_tab=' . $count
				)
			);
			$count ++;
		}
	}
	// about
	$wp_admin_bar->add_menu(
		array(
			'parent'	=> YM_ADMIN_FUNCTION . '_adb',
			'id'		=> YM_ADMIN_FUNCTION . '_adb_about',
			'title'		=> __('About', 'ym'),
			'href'		=> site_url('/wp-admin/admin.php?page=' . YM_ADMIN_FUNCTION . '_about')
		)
	);
	// non admin user
	$wp_admin_bar->add_menu(
		array(
			'parent'	=> 'my-account',
			'id'		=> 'ym-profile',
			'title'		=> __('Membership Details', 'ym'),
			'href'		=> site_url('/wp-admin/users.php?page=ym-profile')
		)
	);
	if (!$ym_sys->hide_membership_content) {
		$wp_admin_bar->add_menu(
			array(
				'parent'	=> 'my-account',
				'id'		=> 'ym-membership_content',
				'title'		=> __('Membership Content', 'ym'),
				'href'		=> site_url('/wp-admin/users.php?page=ym-profile_content')
			)
		);
	}
	return;
}
/**
End Nav
*/

/**
YourMember admin page loading methods
*/
function ym_admin_loader() {
	global $ym_auth;

	$page = ym_request('page');
	$ym_page = ym_request('ym_page');

	$auth_exclude = array(
		YM_ADMIN_FUNCTION . '_about' => 'ym-about.php'
	);

	$ym_target = str_replace(YM_ADMIN_DIR, '', $page);
	if (array_key_exists($ym_target, $auth_exclude)) {
		$page = YM_PLUGIN_DIR_PATH . 'admin/' . $auth_exclude[$ym_target];
		require_once($page);
	} else if (ym_request('do_munch') && $ym_auth->ym_check_key() && $ym_page) {
		$page = YM_PLUGIN_DIR_PATH . 'admin/' . $ym_page . '.php';
		require_once($page);
	}
}
// display function for non priviledged pages/non iframe powered
function ym_admin_read_func() {
	require_once(YM_PLUGIN_DIR_PATH . 'admin/' . ym_get('page') . '.php');
}

/**
Iframe template
*/
function ym_admin_iframe($page = FALSE, $action = FALSE, $arg = FALSE) {
	define( 'YM_ADMIN_IFRAME' , true );

	ym_admin_header($page);

	// Database updates can be called on this hook
	do_action('ym_pre_admin_loader');

	if ($action) {
		do_action($action, $arg);
	} else {
		require_once(YM_PLUGIN_DIR_PATH . 'admin/' . $page . '.php');
	}
	
	do_action('ym_post_admin_loader');

	ym_admin_footer($page);

	exit;
}

function ym_admin_header($page) {
	_wp_admin_html_begin();

	do_action('admin_init');

	// recall for scripts
	ym_admin_script_init();

	// In case admin-header.php is included in a function.
	global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version, $is_iphone,
	$current_site, $update_title, $total_update_count, $parent_file;

	// Catch plugins that include admin-header.php before admin.php completes.
	if ( empty( $current_screen ) )
		set_current_screen();

	wp_user_settings();

	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'ie' );

	do_action('admin_print_styles');
	do_action('admin_print_scripts');
	do_action('admin_head');

	echo '<style type="text/css">
html.wp-toolbar {
	padding-top: 0px;
}
#poststuff {
	width: 100%;
	margin: 0px;
}
</style>
';

	echo '</head>';
?>
<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?> class="no-js">
<script type="text/javascript">
document.body.className = document.body.className.replace('no-js', 'js');
</script>
	<?php
}
function ym_admin_footer($page) {
	do_action('admin_print_footer_scripts');
	?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
<?php
	return;
}
/**
Iframe Template End
*/

/**
 * Creates a rewrite rule for the deprecated ym links such as
 * /ym-process.php?mod=ym_paypal
 */
function ym_rewrite_rule($rules = array()) {
	$rule['ym-process.php/?(.*)$'] = 'index.php?ym_process=1';
	$rule['paypal.php/?(.*)$'] = 'index.php?ym_process=1&mod=ym_paypal';
	$rule['ym-subscribe.php/?(.*)$'] = 'index.php?ym_subscribe=1';
	$rule['ym-tos.php/?(.*)$'] = 'index.php?ym_tos_page=1';
	return array_merge($rule, $rules);
}

function ym_count_users_by_type($type) {
	global $wpdb;
	
	$sql = 'SELECT COUNT(u.ID) 
		FROM
			' . $wpdb->users . ' u
			JOIN ' . $wpdb->usermeta . ' um ON (u.ID = um.user_id)
		WHERE 
			um.meta_key = "ym_account_type"
			AND LOWER(um.meta_value) = "' . mysql_real_escape_string(strtolower($type)) . '"';
	return $wpdb->get_var($sql);
}

function ym_members_to_date() {
	global $wpdb, $ym_package_types;
	
	$totals = array();
	foreach ($ym_package_types->types as $id=>$type) {
		$type = ucwords(strtolower($type));
		if (!is_array($type)) {
		$totals[$type] = 0;
		}
	}
	
	if (!get_option('ym_user_counts_updated')) {
		$sql = 'SELECT ID FROM ' . $wpdb->users;
		if ($users = $wpdb->get_results($sql)) {
		foreach ($users as $i=>$user) {
			$ac = ucwords(ym_get_user_account_type($user->ID, true));
			update_user_meta($user->ID, 'ym_account_type', $ac);
			$totals[$ac]++;
		}
		update_option('ym_user_counts_updated', time());
		}
	} else {
		foreach ($totals as $type=>$count) {
		$totals[$type] = ym_count_users_by_type($type);
		}
	}

	echo '<table style="width:100%;">';
	echo '<tr>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Package Type', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold; width:20%;">' . __('Users', 'ym') . '</td>
			</tr>';

	foreach ($totals as $ac=>$count) {
		echo '<tr>
				<td style="border-bottom: 1px solid #EFEFEF;">' . $ac . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; text-align:right;">' . $count . '</td>
			</tr>';
	}

	echo '</table>';
}

/**
Message engine
*/
function ym_display_message($msg, $type='feedback') {
	switch ($type) {
		case 'error':
			$class = 'error';
		case 'updated':
			$class = isset($class) ? $class : 'updated';
		default:
			$class = isset($class) ? $class : 'updated fade';
	}
	echo '	<div id="message" class="' . $class . '" style="clear: both; margin-top: 5px; padding: 7px;">
				' . $msg . '
			</div>';
}

// nag nag nag nag
function ym_check_version($force_ping = FALSE) {
	global $ym_version_resp, $ym_auth, $ym_update_checker;

	if (is_ym_admin()) {
		$url = YM_VERSION_CHECK_URL;
		if ($ym_auth->ym_get_key()) {
			$key = $ym_auth->ym_get_key();
			$url .= '&key=' . $key;
		} else {
			if (!defined('DISABLE_VERSIONING')) {
				define('DISABLE_VERSIONING', TRUE);
				return;
			}
		}

		if ($data = get_option('ym_vc')) {
			$data = json_decode($data);
		} else {
			$data = false;
			$force_ping = TRUE;
		}

		if ($force_ping || (isset($data) && $data->time < (time() - 86400))) {
			$ym_version_resp = ym_remote_request($url);
			$packet = array(
				'time' => time(),
				'json' => $ym_version_resp
			);
			update_option('ym_vc', json_encode($packet));
		} else {
			$ym_version_resp = $data->json;
		}
		$ym_version_resp = json_decode($ym_version_resp);
		
		if (defined('DISABLE_VERSIONING')) {
			return;
		}

		$url = str_replace('version_check', 'metafile', YM_VERSION_CHECK_URL);
		$url = $url . '&key=' . $key;
		$ym_update_checker = new PluginUpdateChecker($url, YM_META_BASENAME);//, 'ym');
		
		add_action('admin_notices', 'ym_nag_nag_box');
		add_action('after_plugin_row_' . YM_META_BASENAME, 'ym_new_version_download', 10, 3);
	}
}

/**
Nag engine
*/
function ym_nag_nag_box() {
	global $ym_version_resp;
	if ($ym_version_resp->messages->nag) {
		echo '<div class="update-nag">';
		echo 'Here follows a message from the ' . YM_ADMIN_NAME . ' Dev Team: ';
		echo $ym_version_resp->messages->nag;
		echo '</div>';			
	}
}

function ym_tos_nag_box() {
	echo '<div class="update-nag">' . YM_ADMIN_NAME . ' ' . __('has updated its Terms of Service', 'ym') . ' <a href="' . YM_ADMIN_URL . '">' . __('Click here to view', 'ym') . '</a></div>';
}
function ym_activated_thanks_box() {
	echo '<div class="update-nag">' . __('Thank you for installing and activating your copy of', 'ym') . ' ' . YM_ADMIN_NAME . '</div>';
}
function ym_upgrade_nag_box() {
	echo '<div class="update-nag">' . __('Thank you for Upgrading your copy of', 'ym') . ' ' . YM_ADMIN_NAME . '</div>';
	// PING!
	ym_check_version(TRUE);
}
/**
end nagging
*/

/**
Plugins page
*/
function ym_new_version_download($plugin_file, $plugin_data, $plugin_status) {
	if ($plugin_file != YM_META_BASENAME) {
		return;
	}

	global $ym_update_checker;
	$ym_update_checker->checkForUpdates();
	$state = get_option($ym_update_checker->optionName);
	if (isset($state->update) && version_compare($state->update->version, $ym_update_checker->getInstalledVersion(), '>')) {
		$download_url = 'admin.php?page=' . YM_ADMIN_FUNCTION . '_about&do_munch=1&download=1';
		echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">' . sprintf(__('An Update for Your Members is available, you can auto update below or <a href="%s">download the Update</a>', 'ym'), $download_url) . '</div></td></tr>';
	}
	return;
}

//Filter for Links on Plugin screen
function ym_action_link($links, $file){
	if ($file == YM_META_BASENAME){
		global $ym_auth;
		if ($ym_auth->ym_check_key()) {
			$links = array();
			$links[] = '<a id="ym_settings" href="'.YM_ADMIN_URL.'" title="Configure this plugin">' . __('Settings', 'ym') . '</a>';
			$links[] = '<a id="ym_about" href="admin.php?page=' . YM_ADMIN_FUNCTION . '_about" title="About this plugin">' . __('About', 'ym') . '</a>';
			$links[] = '<a id="ym_license" href="http://www.yourmembers.co.uk" title="About this plugins License">' . __('License', 'ym') . '</a>';
			$links[] = '<a id="ym_deactivate" href="'.YM_ADMIN_URL.'&ym_deactivate=1" title="Plugin Deactivate">' . __('Deactivate', 'ym') . '</a>';
			$links[] = '<a id="ym_uninstall" href="'.YM_ADMIN_URL.'&ym_uninstall=1" title="Full Uninstall" class="deletelink">' . __('Full Uninstall', 'ym') . '</a>';
		} else {
			$links[] = '<a id="ym_about" href="admin.php?page=' . YM_ADMIN_FUNCTION . '_about" title="About this plugin">' . __('About', 'ym') . '</a>';
			$links[] = '<a id="ym_license" href="http://www.yourmembers.co.uk" title="About this plugins License">' . __('License', 'ym') . '</a>';
		}
	}
	
	return $links;
}
/**
End Plugins page
*/

// ADVERTS
function ym_get_advert() {
	$page = isset($_GET['page']) ? $_GET['page'] : '';
	$test = substr($_SERVER['REQUEST_URI'], 11);
	if ($page == YM_ADMIN_DIR .'ym-index.php' || $test == 'plugins.php') {
		global $ym_auth, $ym_version_resp;

		if (!$ym_version_resp) {
			// offline
			return;
		}

		$advert = $ym_version_resp->advert;

		if (!$advert->advert_url) {
			return;
		}

		$previous = get_option('ym_last_shown_advert');
		if ($previous != $advert->advert_url) {
			if (!$advert->persist_show_advert) {
				update_option('ym_last_shown_advert', $advert->advert_url);
			}
			$width = isset($advert->width) ? $advert->width : 450;
			$height = isset($advert->height) ? $advert->height : 450;

			echo '<div id="ym_advert" style="background: #FFFFFF; display: none;">';
			echo '<iframe src="' . $advert->advert_url . '" style="width: 100%; height: 100%;"></iframe>';
			echo '</div>';
			echo '
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery(\'#ym_advert\').dialog({
						width: ' . $width . ',
						height: ' . $height . ',
						modal: true,
						resizable: false
					});
				});
			</script>
			';
		}
	}
}
function ym_do_welcome_box() {
	$test = substr($_SERVER['REQUEST_URI'], -11, 11);
	if (ym_get('page') == YM_ADMIN_FUNCTION || $test == 'plugins.php') {
		global $ym_auth, $ym_version_resp;

		if (ym_get(YM_ADMIN_FUNCTION . '_activated')) {
			$url = $ym_version_resp->messages->welcome_new_user;
		} else {
			$url = $ym_version_resp->messages->changelog;
		}
		if (!$url) {
			return;
		}

		$width = 600;
		$height = 450;

		echo '<div id="ym_log" style="background: #FFFFFF; display: none;">';
		echo '<iframe src="' . $url . '" style="width: 100%; height: 100%;"></iframe>';
		echo '</div>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery(\'#ym_log\').dialog({
					width: ' . $width . ',
					height: ' . $height . ',
					modal: true,
					resizable: false,
					stack: true
				});
			});
		</script>
		';
	}
}

// Terms of service
function ym_tos_check() {
	global $ym_version_resp;

	if (!is_ym_admin()) {
		return;
	}

	$last_tos_version = get_option('ym_tos_version_accepted');
	$tos = $ym_version_resp->tos;

	if (!$ym_version_resp) {
		// offline
		return;
	}
	
	if ($tos->tos_version_id != $last_tos_version) {
		// new terms
		// goto terms page
		if ((ym_get('ym_page') != 'ym-terms' && ym_get('ym_page')) || (ym_get('page') == YM_ADMIN_DIR . 'ym-index.php') && !ym_get('ym_page')) {
			if (is_admin()) {
				header('Location: admin.php?page=' . YM_ADMIN_DIR . 'ym-index.php&ym_page=ym-terms');
				exit;
			}
		} else {
			// update nag
			add_action('admin_notices', 'ym_tos_nag_box');
		}
	}
}

function ym_context_help() {
	global $wp_version, $ym_auth;
	
	$string = '<h2>' . YM_ADMIN_NAME . ' ' . __('Support', 'ym') . '</h2>';
	$string .= '<p><a href="http://YourMembers.co.uk/forum/">' . __('Get Help and Support from the Forums', 'ym') . '</a></p>';
	$string .= '<p><a href="http://www.yourmembers.co.uk/the-support/guides-tutorials/">' . __('Get Help and Support from the Guides', 'ym') . '</a></p>';

	$string .= '<p>' . sprintf(__('You are running %s %s on WordPress %s on PHP %s with YM Database Version %s/%s', 'ym'), YM_ADMIN_NAME, YM_PLUGIN_VERSION, $wp_version, phpversion(), YM_DATABASE_VERSION, get_option('ym_db_version', 0)) . '</p>';
	
	get_current_screen()->add_help_tab(array('id' => 'ym_core', 'title' => YM_ADMIN_NAME, 'content' => $string));

	do_action('ym_additional_context_help');
}

// affiliate
function ym_affiliate_link() {
	global $ym_sys;
	if ($ym_sys->advertise_ym) {
		echo '<a href="http://YourMembers.co.uk/';
		if ($ym_sys->advertise_ym_affid && !is_ym_admin()) {
			echo $ym_sys->advertise_ym_affid;
		}
		echo '" style="clear: both; display: block; text-align: center;">' . $ym_sys->advertise_ym_text . '</a><br />';
	}
}

function ym_shortcode_aff_link() {
	$r = '<a href="http://YourMembers.co.uk/';
	if ($ym_sys->advertise_ym_affid && !is_ym_admin()) {
		$r .= $ym_sys->advertise_ym_affid;
	}
	$r .= '" style="clear: both; display: block; text-align: center;">' . $ym_sys->advertise_ym_text . '</a><br />';
	return $r;
}



// third part function
// from somewhere on the php.net website
// convert xml to an array

if (!function_exists('xml2array')) {
function xml2array($contents, $get_attributes = 1, $priority = 'tag') {
	if (!function_exists('xml_parser_create'))
	{
		return array ();
	}
	$parser = xml_parser_create('');

	xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parse_into_struct($parser, trim($contents), $xml_values);
	xml_parser_free($parser);

	if (!$xml_values)
		return;//Hmm...

	$xml_array = array ();
	$parents = array ();
	$opened_tags = array ();
	$arr = array ();
	$current = & $xml_array;
	$repeated_tag_index = array ();

	foreach ($xml_values as $data) {
		unset ($attributes, $value);
		extract($data);
		$result = array ();
		$attributes_data = array ();
		if (isset ($value))
		{
			if ($priority == 'tag')
				$result = $value;
			else
				$result['value'] = $value;
		}
		if (isset ($attributes) and $get_attributes)
		{
			foreach ($attributes as $attr => $val)
			{
				if ($priority == 'tag')
					$attributes_data[$attr] = $val;
				else
					$result['attr'][$attr] = $val;//Set all the attributes in a array called 'attr'
			}
		}
		if ($type == "open")
		{ 
			$parent[$level -1] = & $current;
			if (!is_array($current) or (!in_array($tag, array_keys($current))))
			{
				$current[$tag] = $result;
				if ($attributes_data)
					$current[$tag . '_attr'] = $attributes_data;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				$current = & $current[$tag];
			}
			else
			{
				if (isset ($current[$tag][0]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{ 
					$current[$tag] = array (
						$current[$tag],
						$result
					);
					$repeated_tag_index[$tag . '_' . $level] = 2;
					if (isset ($current[$tag . '_attr']))
					{
						$current[$tag]['0_attr'] = $current[$tag . '_attr'];
						unset ($current[$tag . '_attr']);
					}
				}
				$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
				$current = & $current[$tag][$last_item_index];
			}
		}
		elseif ($type == "complete")
		{
			if (!isset ($current[$tag]))
			{
				$current[$tag] = $result;
				$repeated_tag_index[$tag . '_' . $level] = 1;
				if ($priority == 'tag' and $attributes_data)
					$current[$tag . '_attr'] = $attributes_data;
			}
			else
			{
				if (isset ($current[$tag][0]) and is_array($current[$tag]))
				{
					$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
					if ($priority == 'tag' and $get_attributes and $attributes_data)
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
					}
					$repeated_tag_index[$tag . '_' . $level]++;
				}
				else
				{
					$current[$tag] = array (
						$current[$tag],
						$result
					);
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $get_attributes)
					{
						if (isset ($current[$tag . '_attr']))
						{ 
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset ($current[$tag . '_attr']);
						}
						if ($attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
					}
					$repeated_tag_index[$tag . '_' . $level]++;//0 and 1 index is already taken
				}
			}
		}
		elseif ($type == 'close')
		{
			$current = & $parent[$level -1];
		}
	}
	return ($xml_array);
}
}

/**
YM Conf
*/
$conf_fields = array(
	'ym_packs',
	'ym_package_types',

	'ym_custom_fields',

	'ym_res',
	'ym_sys',
);

function ym_check_for_ymconf($file = FALSE) {
	echo '<pre>';
	global $wpdb, $conf_fields;
	
	if (!$file) {
		$file = YM_PLUGIN_DIR_PATH . 'ym.conf';
	}

	if (is_file($file)) {
		exec('php -l ' . $file, $result);
		if ($result[0] != 'No syntax errors detected in ' . $file) {
			echo '<div id="message" class="error"><p>Import failed, Syntax Errors Detected</p></div>';
			return;
		}
		
		// conf file exists
		include($file);
		
		// reg code
		if ($file != YM_PLUGIN_DIR_PATH . 'ym.conf') {
			if (isset($data['registration_email'])) {
				$_POST['activate_plugin'] = isset($_POST['activate_plugin']) ? $_POST['activate_plugin'] : 'activate';
				// pass the registration email back
				$_POST['registration_email'] = $data['registration_email'];
			}
		}
		
		// whole objects
		// found in $data
		$fields = $conf_fields;
		foreach ($fields as $item) {
			if (isset($data[$item])) {
				$wpdb->query('UPDATE ' . $wpdb->options . ' SET option_value = \'' . $data[$item] . '\' WHERE option_name = \'' . $item . '\'');
			}
		}
		
		// module componenets
		// is there a $object of name $object in the conf file
		// like $ym_packs for example
		foreach ($fields as $object) {
			if (isset($$object)) {
				$theobject = get_option($object);
				foreach ($$object as $key => $item) {
					$theobject->$key = $item;
				}
				$wpdb->query('UPDATE ' . $wpdb->options . ' SET option_value = \'' . $theobject . '\' WHERE option_name = \'' . $object . '\'');
			}
		}
		
		// munch modules
		$modules = array();
		if ($gateways) {
			foreach ($gateways as $gateway => $module) {
				$modules[] = $gateway;
				$wpdb->query('UPDATE ' . $wpdb->options . ' SET option_value = \'' . $module . '\' WHERE option_name = \'' . $gateway . '\'');
			}
			update_option('ym_modules', $modules);
		}
		// module
		$modules = get_option('ym_modules');
		foreach ($modules as $object) {
			if (isset($$object)) {
				$theobject = get_option($object, TRUE);
				foreach ($$object as $key => $item) {
					$theobject->$key = $item;
				}
				$wpdb->query('UPDATE ' . $wpdb->options . ' SET option_value = \'' . $theobject . '\' WHERE option_name = \'' . $object . '\'');
			}
		}

		if (isset($data['disable_plugin_store'])) {
			update_option('disable_plugin_store', $data['disable_plugin_store']);
		} else {
			delete_option('disable_plugin_store');
		}
		if (isset($data['disable_versioning'])) {
			update_option('disable_versioning', $data['disable_versioning']);
		} else {
			delete_option('disable_versioning');
		}
	}
	echo '</pre>';
}
function ym_export_ymconf() {
	global $wpdb, $conf_fields;
	
	$data = array();
	foreach ($conf_fields as $field) {
		$data[$field] = $wpdb->get_var('SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = \'' . $field . '\'');
	}
	
	$content = '<' . '?' . 'php

/*
* 
* YourMembers ym.conf - Auto Gen Export File
* 
* ' . site_url() . '
* Auto Generated Conf Export
* ' . date('r', time()) . '
* 
*/

$data = array(
';

	foreach ($data as $key => $value) {
		$content .= '\'' . $key . '\' => \'' . $value . '\',' . "\n\n";
	}

	if (get_option('disable_plugin_store')) {
		$content .= '\'disable_plugin_store\' => ' . get_option('disable_plugin_store') . ',' . "\n\n";
	}
	if (get_option('disable_versioning')) {
		$content .= '\'disable_versioning\' => ' . get_option('disable_versioning') . ',' . "\n\n";
	}

	$content .= '
);

';

	$gateways = array();
	$modules = get_option('ym_modules');
	foreach ($modules as $module) {
		$gateways[$module] = $wpdb->get_var('SELECT option_value FROM ' . $wpdb->options . ' WHERE option_name = \'' . $module . '\'');
	}
	
	$content .= '
$gateways = array(
';
	
	foreach ($gateways as $way => $setup) {
		$content .= '\'' . $way . '\' => \'' . $setup . '\',' . "\n\n";
	}
	
	$content .= '
);
';

	header('Content-Description: File Transfer');
	header('Content-Type: text');
	header('Content-Disposition: attachment; filename=ym.conf');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Content-Length: ' . strlen($content));

	flush();
	echo $content;
	
	exit;
}
$test = isset($_POST['go_export']) ? $_POST['go_export'] : '';
if ($test == 'export') {
	ym_export_ymconf();
}

function ym_database_updater() {
	global $wpdb;

	$current_db_version = get_option('ym_db_version', 0);
	$current_db_version = apply_filters('ym_beta_db_check_current', $current_db_version);

	$target_db_version = YM_DATABASE_VERSION;
	$target_db_version = apply_filters('ym_beta_db_check_target', $target_db_version);

	$run = FALSE;

	if ($current_db_version < $target_db_version) {
		if ($current_db_version < $target_db_version) {
			// updates needed
			$current_db_version++;
			$file = YM_PLUGIN_DIR_PATH . 'sql/sql_update_' . $current_db_version . '.php';
			if (is_file($file)) {
				$queries = array();
				include($file);
				foreach ($queries as $query) {
					$wpdb->query($query);
				}
				echo '<div class="update-nag" style="display: block;">' . sprintf(__('Updated the Your Members database to Version %s', 'ym'), $current_db_version) . '</div>';
				$run = TRUE;
			}
		}
		update_option('ym_db_version', $current_db_version);
		
		if ($current_db_version < $target_db_version || $run) {
			// more to do
			echo '<meta http-equiv="refresh" content="5" />';
			echo '<p>One Moment</p>';
			exit;
		}
	}
}

// content process
function ym_apply_filter_the_content($content) {
	$content = wptexturize($content);
	$content = convert_smilies($content);
	$content = convert_chars($content);
	$content = wpautop($content);
	$content = shortcode_unautop($content);
	$content = prepend_attachment($content);
	$content = do_shortcode($content);
	return $content;
}

function ym_form_enctype() {
	echo ' enctype="multipart/form-data" ';
	return;
}

// Members admin filter and sorting
function ym_members_filters($filters) {
	global $ym_members_tasks;
	// filters

	ym_box_top(__('Search and Sort', 'ym'));
	echo '<table style="width: 100%;">';

	echo '
<tr><td colspan="10">
<table class="form-table" style="width: 100%;">
<tr><td>
<strong>'. __('Filter Members by:','ym').'</strong>

	<select name="filter_by_option" id="filter_by_option">
		<option value="">' . __('No Filter', 'ym') . '</option>
		<option value="username" ' . ($filters['by_option'] == 'username' ? 'selected="selected"' : '') . '>' . __('Username', 'ym') . '</option>
		<option value="user_email" ' . ($filters['by_option'] == 'user_email' ? 'selected="selected"' : '') . '>' . __('User Email', 'ym') . '</option>
		<option value="package" ' . ($filters['by_option'] == 'package' ? 'selected="selected"' : '') . '>' . __('Package', 'ym') . '</option>
		<option value="package_type" ' . ($filters['by_option'] == 'package_type' ? 'selected="selected"' : '') . '>' . __('Package Type', 'ym') . '</option>
		<option value="custom_field" ' . ($filters['by_option'] == 'custom_field' ? 'selected="selected"' : '') . '>' . __('Custom Field', 'ym') . '</option>
		<option value="status" ' . ($filters['by_option'] == 'status' ? 'selected="selected"' : '') . '>' . __('Status', 'ym') . '</option>
		';

		echo '		
		<option value="">--' . __('User Exposed Fields', 'ym') . '--</option>
		';
	$funbus = '';
	$available = new YourMember_User();
	$available = $available->api_expose();
	foreach ($available as $item) {
		if (
			$item != 'status'
			&&
			$item != 'account_type'
			) {
			$funbus .= 'filter_by_text_exposed_' . $item . ' ';
			echo '<option value="exposed_' . $item . '" ' . ($filters['by_option'] == 'exposed_' . $item ? 'selected="selected"' : '') . '>' . ucwords(str_replace('_', ' ', str_replace('account', 'package', $item))) . '</option>';
		}
	}

	echo '
	</select>
</td><td>
<select name="filter_by_text_package" class="filter_by_text filter_by_text_package">
<option value="">' . __('Select', 'ym') . '</option>
<option value="none" ';
	if ('none' == $filters['by_text']) {
		echo 'selected="selected"';
	}
	echo '>' . __('No Package', 'ym') . '</option>';

	global $ym_packs;
	foreach ($ym_packs->packs as $pack) {
		echo '<option value="' . $pack['id'] . '" ';
		if ($pack['id'] == $filters['by_text']) {
			echo 'selected="selected"';
		}
		echo '>' . ym_get_pack_label($pack) . '</option>';
	}
	echo '
</select>

<select name="filter_by_text_package_type" class="filter_by_text filter_by_text_package_type">
<option value="">' . __('Select', 'ym') . '</option>
<option value="none" ';
	if ('none' == $filters['by_text']) {
		echo 'selected="selected"';
	}
	echo '>' . __('No Package Type', 'ym') . '</option>';

	global $ym_package_types;
	foreach ($ym_package_types->types as $type) {
		echo '<option value="' . $type . '" ';
		if ($type == $filters['by_text']) {
			echo 'selected="selected"';
		}
		echo '>' . $type . '</option>';
	}
	echo '
</select>

<select name="filter_by_text_custom_field" class="filter_by_text filter_by_text_custom_field">
	<option value="">' . __('Select', 'ym') . '</option>
	';

	$customs = get_option('ym_custom_fields');
	$order = explode(';', $customs->order);
	foreach ($order as $id) {
		$custom = ym_get_custom_field_by_id($id);
		echo '<option value="' . $id  . '" ';
		if ($id == $filters['cf_field']) {
			echo 'selected="selected"';
		}
		echo '>' . $custom['label'] . '</option>';
	}

	echo '
</select>

<select name="filter_by_text_status" class="filter_by_text filter_by_text_status">
	<option value="">' . __('Select', 'ym') . '</option>
	';

	global $status_str;
	foreach ($status_str as $state) {
		echo '<option ';
		if ($state == $filters['by_text']) {
			echo 'selected="selected"';
		}
		echo '>' . $state . '</option>';
	}

	echo '
</select>
</td><td>
<input type="text" name="filter_by_text" class="filter_by_text filter_by_text_username filter_by_text_user_email filter_by_text_custom_field ' . $funbus . '" value="' . $filters['by_text'] . '" size="10">
</td><td style="width: 120px;">
<input type="submit" id="change_filters" name="task" class="button-primary" value="' . $ym_members_tasks['change_filters'] . '" />
</td></tr>
<tr><td style="width: 25%;"></td><td style="width: 25%;"></td><td style="width: 25%;">' . __('Use * to wildcard match', 'ym') . '</td><td style="width: 25%;"></td></tr>
<tr><td>
<strong>'. __('Sort Members by:','ym').'</strong>
<select name="filter_by_order_by">
	<option value="ID" ' . ($filters['order_by'] == 'ID' ? 'selected="selected"' : '') . '>' . __('User ID', 'ym') . '</option>
	<option value="login" ' . ($filters['order_by'] == 'login' ? 'selected="selected"' : '') . '>' . __('User Login', 'ym') . '</option>
	<option value="email" ' . ($filters['order_by'] == 'email' ? 'selected="selected"' : '') . '>' . __('Email Address', 'ym') . '</option>
	<option value="registered" ' . ($filters['order_by'] == 'registered' ? 'selected="selected"' : '') . '>' . __('Registration Date', 'ym') . '</option>
	';

	echo '		
	<option value="">--' . __('User Exposed Fields', 'ym') . '--</option>
	';
	foreach ($available as $item) {
		echo '<option value="exposed_' . $item . '" ' . ($filters['order_by'] == 'exposed_' . $item ? 'selected="selected"' : '') . '>' . ucwords(str_replace('_', ' ', str_replace('account', 'package', $item))) . '</option>';
	}

	echo '
</select>
<td>
</td>
</td><td>
<select name="filter_by_order_by_direction">
	<option value="ASC" ' . ($filters['order_by_direction'] == 'ASC' ? 'selected="selected"' : '') . '>' . __('Ascending', 'ym') . '</option>
	<option value="DESC" ' . ($filters['order_by_direction'] == 'DESC' ? 'selected="selected"' : '') . '>' . __('Descending', 'ym') . '</option>
</select>
</td><td>
<input type="submit" id="change_order" name="task" class="button-primary" value="' . $ym_members_tasks['change_order'] . '" />
</td></tr>

</table>

</td></tr>
';
	echo '</table>';
	ym_box_bottom();

	return;
}

