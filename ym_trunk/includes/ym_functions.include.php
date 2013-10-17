<?php

/*
* $Id: ym_functions.include.php 2610 2013-03-01 09:44:13Z tnash $
* $Revision: 2610 $
* $Date: 2013-03-01 09:44:13 +0000 (Fri, 01 Mar 2013) $
*/

/**
SuperGlobals
*/

$ym_all_session_cache = '';
function ym_session($key, $default='', $strip_tags=false) {
	if (isset($_SESSION[$key]) && $_SESSION[$key]) {
		$default = $_SESSION[$key];
	}

	if ($strip_tags) {
		$default = strip_tags($default);
	}

	if (!is_array($default) && !is_object($default)) {
		$default = sanitize_text_field($default);
	}

	return $default;
}

$ym_all_cookie_cache = '';
function ym_cookie($key, $default='', $strip_tags=false) {
	global $ym_all_cookie_cache;
	if (!$ym_all_cookie_cache) {
		$ym_all_cookie_cache = $_COOKIE;
	}
	if (isset($ym_all_cookie_cache[$key])) {
		$default = $ym_all_cookie_cache[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	if (!is_array($default) && !is_object($default)) {
		$default = sanitize_text_field($default);
	}

	return $default;
}

$ym_all_request_cache = '';
function ym_request($key, $default='', $strip_tags=false) {
//	global $ym_all_request_cache;
//	if (!$ym_all_request_cache) {
		$ym_all_request_cache = $_REQUEST;
//	}
	if (isset($ym_all_request_cache[$key])) {
		$default = $ym_all_request_cache[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	if (!is_array($default) && !is_object($default)) {
		$default = sanitize_text_field($default);
	}

	return $default;
}

//$ym_all_post_cache = '';
function ym_post($key, $default='', $strip_tags=false) {
//	global $ym_all_post_cache;
//	if (!$ym_all_post_cache) {
		$ym_all_post_cache = $_POST;
//	}
	if (isset($ym_all_post_cache[$key])) {
		$default = $ym_all_post_cache[$key];

		if ($strip_tags) {
			$default = strip_tags($default,$strip_tags);
		}
	}

	if (!is_array($default) && !is_object($default)) {
		$default = sanitize_text_field($default);
	}

	return $default;
}

//$ym_all_get_cache = '';
function ym_get($key, $default='', $strip_tags=false) {
//	global $ym_all_get_cache;
//	if (!$ym_all_get_cache) {
		$ym_all_get_cache = $_GET;
//	}
	if (isset($ym_all_get_cache[$key])) {
		$default = $ym_all_get_cache[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	if (!is_array($default) && !is_object($default)) {
		$default = sanitize_text_field($default);
	}

	return $default;
}

/**
End SuperGlobals
*/

function ym_get_user_id() {
	global $wpdb;

	if (isset($_GET['username']) && $_GET['username']) {
		return ym_get_user_id_by_username($_GET['username']);
	} else if (isset($_REQUEST['user_id']) && $_REQUEST['user_id']) {
		return $_REQUEST['user_id'];
	} else {
		get_currentuserinfo();
		global $current_user;
		return $current_user->ID;
	}
}

function ym_get_user_by_username($username) {
	return get_user_by('login', $username);
}

function ym_get_user_id_by_username($username) {
	return ym_get_user_by_username($username)->ID;
}

function ym_is_active_module($module) {
	global $ym_active_modules;

	if (empty($ym_active_modules)) {
		return false;
	}

	return in_array($module, $ym_active_modules);
}

/**
Links
*/
function ym_get_login_register_links() {
	$html = '<div id="ym_login_register_links">';
	$html .= ym_get_login_link();
	$html .= '&nbsp;';
	$html .= ym_get_register_link();
	$html .= '</div>';
	return $html;
}

function ym_get_login_link() {
	global $ym_sys;
	if ($ym_sys->ym_get_login_link_url) {
		$link = site_url($ym_sys->ym_get_login_link_url);
		if (strpos($link, '?')) {
			$link .= '&';
		} else {
			$link .= '?';
		}
		$link .= 'redirect_to=' . rawurlencode(get_permalink());
	} else {
		$link = site_url('/wp-login.php?redirect_to=' . rawurlencode(get_permalink()));
	}
	return '<span id="ym_login_link"><a href="' . $link . '">' . __('[ Login ]', 'ym') . '</a></span>';
}

function ym_get_register_link() {
	global $ym_sys;
	if ($ym_sys->ym_get_register_link_url) {
		$link = site_url($ym_sys->ym_get_register_link_url);
		if (strpos($link, '?')) {
			$link .= '&';
		} else {
			$link .= '?';
		}
		$link .= 'ym_redirector=' . rawurlencode(get_permalink());
	} else {
		$link = site_url('/wp-login.php?action=register&ym_redirector=' . urlencode(get_permalink()));
	}
	return '<span id="ym_register_link"><a href="' . $link . '">' . __('[ Register ]', 'ym') . '</a></span>';
}
/**
End Links
*/

function ym_recursive_replace($from='', $to='', $array=false) { 
	if (get_class($array) || is_array($array)) {
		$return = array();
		
		foreach ($array as $key=>$value) {
			$return[$key] = ym_recursive_replace($from, $to, $value);
		}
	} else {
		$return  = str_replace($from, $to, $array);
	}
	
	return $return;
}

function ym_current_url() {
	return site_url($_SERVER['REQUEST_URI']);
}

/**
SSL Force
*/
function ym_go_ssl() {
	global $ym_sys;

	if (
		is_ssl()
		|| !$ym_sys->register_https_only
		|| substr($_SERVER['REQUEST_URI'], -28, 28) != 'wp-login.php?action=register'
		|| ym_get('ym_process', FALSE)
	) {
		return;
	}
	$_SERVER['HTTPS'] = 'on';
	$url = ym_current_url();
	header('Location: ' . $url);
	exit;
}
function ym_go_ssl_pages() {
	global $ym_sys, $wp_query;

	if (
		is_ssl()
		|| !$ym_sys->register_https_only
		|| !is_singular()
		|| !is_page()
		|| ym_get('ym_process', FALSE)
	) {
		return;
	}

	$pages = explode(',', $ym_sys->register_https_pages);
	$id = $wp_query->query_vars['page_id'];
	if (!$id || !count($pages)) {
		return;
	}
	if (in_array($id, $pages)) {
		$_SERVER['HTTPS'] = 'on';
		$url = ym_current_url();
		header('Location: ' . $url);
		exit;
	}
}
/**
End SSL Force
*/

/**
RSS Functions
*/
function ym_rss_stop_payments() {
	add_filter('ym_filter_gateway', 'ym_rss_stop_payments_filter');
}
function ym_rss_stop_payments_filter() {
	return '';
}

function ym_get_user_by_token($token) {
	$users = get_users(array(
		'meta_key'		=> 'ym_rss_token',
		'meta_value'	=> mysql_real_escape_string($token)
	));
	if ($users[0]) {
		// login user
		wp_set_current_user($users[0]->ID);
		return $users[0];
	}
	return false;
}

function ym_get_rss_token() {
	global $wpdb, $current_user;
	get_currentuserinfo();

	$token = false;

	if ($current_user->ID) {
		$token = get_user_meta($current_user->ID, 'ym_rss_token', TRUE);

		if (!$token) {
			$salt = 'rss token salt';
			$num = mt_rand(10000,15000);
			$string = $num . $salt . $num;

			$token = md5($string);
			update_user_meta($current_user->ID, 'ym_rss_token', $token);
		}
	}

	return $token;
}
function ym_use_rss_token() {
	global $ym_sys;

	$use_token = $ym_sys->use_rss_token;
	if ($use_token === false) {
		$use_token = 1; //defaults to on
	}

	return $use_token;
}

/**
Email
*/
function ym_email($to, $subject, $message) {
	ym_email_add_filters();
	wp_mail($to, $subject, $message);
	ym_email_remove_filters();
}
function ym_email_add_filters() {
	// Replaces the From Name and Address with custom info
	add_filter('wp_mail_from', 'ym_mail_from');
	add_filter('wp_mail_from_name', 'ym_mail_from_name');
	add_filter('wp_mail_content_type', 'ym_mail_content_type');
}
function ym_email_remove_filters() {
	// and remove those filters again
	remove_filter('wp_mail_from', 'ym_mail_from');
	remove_filter('wp_mail_from_name', 'ym_mail_from_name');
	remove_filter('wp_mail_content_type', 'ym_mail_content_type');
}
/**
Email overrides
*/
function ym_mail_from($current) {
	global $ym_sys;
	return $ym_sys->from_email;
}
function ym_mail_from_name($current) {
	global $ym_sys;
	return $ym_sys->from_name;
}
function ym_mail_content_type() {
	return "text/html";	
}

// checks if the visitor is a search engine spider/bot
function ym_is_spider() {
	$spiders = array('googlebot','google','msnbot','ia_archiver','lycos','jeeves','scooter','fast-webcrawler','slurp@inktomi','turnitinbot','technorati','yahoo','findexa','findlinks','gaisbo','zyborg','surveybot','bloglines','blogsearch','pubsub','syndic8','userland','gigabot','become.com');

	$useragent = $_SERVER['HTTP_USER_AGENT'];

	if (empty($useragent)) {
		return false;
	}

	// Check For Bot
	foreach ($spiders as $spider) {
		if (stristr($useragent, $spider) !== false) {
			return true;
		} else {
			return false;
		}
	}
}

function ym_login_form($register_text='', $lostpassword_text='', $redirect='') {
	global $ym_sys;
	
	$html = '<form id="ym_login_form" action="' . site_url('/wp-login.php') . '" method="post">
			<table>
				<tr>
					<td>' . __('Username:','ym') . '</td>
					<td>
						<input type="text" name="log" id="user_login" class="input" value="" size="12" />
					</td>
				</tr>
				<tr>
					<td>' . __('Password:','ym') . '</td>
					<td>
						<input type="password" name="pwd" id="user_pass" class="input" value="" size="12" />
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;">
						<span id="remember_me_container"><input id="rememberme" type="checkbox" value="forever" name="rememberme"/>'.__('Remember Me','ym').'</span>
						<input type="submit" name="wp-submit" id="wp-submit" value="' . __('Login &raquo;','ym') . '" />
						';

						if ($redirect) {
							if ($redirect == ':thispage:') {
								$redirect = get_permalink();
							} else {
								$redirect = site_url($redirect);
							}
							$html .= '<input type="hidden" name="redirect_to" value="' . $redirect . '" />';
						}
						$html .= '
					</td>
				</tr>
			</table>
		</form>';

	if ($register_text) {
		$html .= '<div id="ym_register_div">
			<a class="ym-register-link" href="' . get_bloginfo('wpurl') . '/wp-login.php?action=register">' . $register_text . '</a>
		</div>';
	}

	if ($lostpassword_text) {
		$html .= '<div id="ym_lost_pass_div">
			<a class="ym-lostpassword-link" href="' . get_bloginfo('wpurl') . '/wp-login.php?action=lostpassword">' . $lostpassword_text . '</a>
		</div>';
	}

	return $html;

}

function ym_user_unsubscribe($atts = array()) {
	get_currentuserinfo();
	global $current_user, $ym_res, $ym_user;
	
	if (!$current_user->ID) {
		return;
	}

	if (!$ym_user) {
		$ym_user = new YourMember_User($current_user->ID);
	}
	//var_dump($ym_user);

	$unsubscribe_text = isset($atts['unsubscribe_text']) ? $atts['unsubscribe_text'] : __('Are you sure you Wish to Unsubscribe', 'ym');
	$sure_button = isset($atts['sure_button']) ? $atts['sure_button'] : __('Yes', 'ym');
	$unsubscribe_button = isset($atts['unsubscribe_button']) ? $atts['unsubscribe_button'] : __('Unsubscribe', 'ym');
	
	$action = ym_post('ym_action');
	if ($action == 'unsubscribeyes') {
		
		// set user to inactive
		$user_status = YM_STATUS_EXPIRED;
		$ym_user->status_str = __('Manual UnSubscribe', 'ym');
		$ym_user->status = $user_status;
		//update_user_meta($current_user->ID, 'ym_user', $user_data);
		$ym_user->save();
		update_user_option($current_user->ID, 'ym_status', $user_status, true);

		@ym_log_transaction(YM_ACCESS_EXPIRY, time(), $current_user->ID);
		@ym_log_transaction(YM_USER_STATUS_UPDATE, $user_status . ' Manual Unsubscribe', $current_user->ID);
		// logout
		$html = '<p>' . $ym_res->unsubscribe_left_msg . '</p>';
		$html .= '<meta http-equiv="refresh" content="5;' . site_url() . ' " />';
		
		do_action('ym_user_self_unsubscribe');
		
		return $html;
	} else if ($action == 'unsubscribe') {
		$html = '<form action="" method="post">
	<p>' . $unsubscribe_text . '</p>
	<input type="hidden" name="ym_action" value="unsubscribeyes" />
	<input type="submit" value="' . $sure_button . '" />
	</form>';
	} else {
		$html = '<form action="" method="post">
	<input type="hidden" name="ym_action" value="unsubscribe" />
	<input type="submit" value="' . $unsubscribe_button . '" />
	</form>';
	}
	return $html;
}

function ym_user_password_form() {
	get_currentuserinfo();
	global $current_user;
//	wp_enqueue_script('password-strength-meter');
	
	$message = '';
	$action = ym_post('ym_action');
	if ($action == 'ym_user_password_update') {
		$result = ym_user_password_update();
		if ($result == 'ok') {
			$message = '<div id="message" class="updated"><p><strong>' . __('Password was updated', 'ym') . '</strong></div>';
		} else if ($result == 'empty') {
			$message = '<div class="error">' . __('Password was empty', 'ym') . '</div>';
		} else {
			$message = '<div class="error">' . __('Passwords do not match', 'ym') . '</div>';
		}
	}
	
	$html = '';
	if ($current_user->ID) {
		$html = '<form id="ym_password_form" action="" method="post">
			<input type="hidden" name="ym_action" value="ym_user_password_update" />
			';
			
			if ($message) {
				$html .= $message;
			}
			
			$html .= '
			<table>
				<tr>
					<td>' . __('New Password:', 'ym') . '</td>
					<td>
						<input type="password" name="pass1" id="pass1" value="" size="12" />
					</td>
				</tr>
				<tr>
					<td>' . __('Repeat Password:' , 'ym') . '</td>
					<td>
						<input type="password" name="pass2" id="pass2" value="" size="12" />					
					</td>
				</tr>
				<tr>
					<td colspan="2" style="text-align: right;">
						<input type="submit" name="wp-submit" id="wp-submit" value="' . __('Change Password','ym') . '" />
					</td>
				</tr>
			</table>
		</form>';
	}
	
	return $html;
}
function ym_user_password_update() {
	get_currentuserinfo();
	global $current_user;
	
	$new_password = ym_post('pass1');
	$new_password2 = ym_post('pass2');
	
	if (empty($new_password)) {
		return 'empty';
	} else if ($new_password == $new_password2) {
		wp_set_password($new_password, $current_user->ID);
		return 'ok';
	}
	return false;
}
function ym_user_profile_form() {
	get_currentuserinfo();
	global $current_user, $wpdb;
	
	$updated = false;
	
	$action = ym_post('ym_action');
	if ($action == 'ym_user_profile_update') {
		include('wp-admin/includes/user.php');
		include('wp-includes/registration.php');
		
		do_action('personal_options_update', $current_user->ID);
		$errors = edit_user($current_user->ID);
		
		if ( !is_wp_error( $errors ) ) {
			$html = '<p>' . __('Your Profile has been updated') . '</p>';
			$html .= '<meta http-equiv="refresh" content="3" />';
			return $html;
		}
	}
	
	$html = '';
	if ( isset( $errors ) && is_wp_error( $errors ) ) {
		$html .= '<div class="error"><p>' .  implode( "</p>\n<p>", $errors->get_error_messages() ) . '</p></div>';
	} else if (ym_get('updated')) {
		$html .= '<div id="message" class="updated"><p><strong>' . __('User updated.') . '</strong></p></div>';
	}
	
	if (!function_exists(_wp_get_user_contactmethods)) {
		function _wp_get_user_contactmethods() {
			$user_contactmethods = array(
				'aim' => __('AIM'),
				'yim' => __('Yahoo IM'),
				'jabber' => __('Jabber / Google Talk')
			);
			return apply_filters('user_contactmethods',$user_contactmethods);
		}
	}
	
	$html .= '
<form action="" method="post">
	<input type="hidden" name="ym_action" value="ym_user_profile_update" />
	
<table class="form-table">
	<tr><td colspan="2"><h3>' . __('Name') . '</h3></td></tr>
	<tr>
		<th><label for="first_name">' . __('First Name') . '</label></th>
		<td><input type="text" name="first_name" id="first_name" value="' . esc_attr($current_user->user_firstname) . '" class="regular-text" /></td>
	</tr>

	<tr>
		<th><label for="last_name">' . __('Last Name') . '</label></th>
		<td><input type="text" name="last_name" id="last_name" value="' . esc_attr($current_user->user_lastname) . '" class="regular-text" /></td>
	</tr>

	<tr>
		<th><label for="nickname">' . __('Nickname') . ' <span class="description">' . __('(required)') . '</span></label></th>
		<td><input type="text" name="nickname" id="nickname" value="' . esc_attr($current_user->nickname) . '" class="regular-text" /></td>
	</tr>

	<tr>
		<th><label for="display_name">' . __('Display name publicly as') . '</label></th>
		<td>
			<select name="display_name" id="display_name">
			';
				$public_display = array();
				$public_display['display_username']  = $current_user->user_login;
				$public_display['display_nickname']  = $current_user->nickname;
				if ( !empty($profileuser->first_name) )
					$public_display['display_firstname'] = $current_user->first_name;
				if ( !empty($profileuser->last_name) )
					$public_display['display_lastname'] = $current_user->last_name;
				if ( !empty($profileuser->first_name) && !empty($current_user->last_name) ) {
					$public_display['display_firstlast'] = $current_user->first_name . ' ' . $current_user->last_name;
					$public_display['display_lastfirst'] = $current_user->last_name . ' ' . $current_user->first_name;
				}
				if ( !in_array( $current_user->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
					$public_display = array( 'display_displayname' => $current_user->display_name ) + $public_display;
				$public_display = array_map( 'trim', $public_display );
				$public_display = array_unique( $public_display );
				foreach ( $public_display as $id => $item ) {
					$html .= '<option id="' .  $id . '" value="' . esc_attr($item) . '"' . selected( $current_user->display_name, $item, FALSE) . '>' . $item . '</option>';
				}
			$html .= '
			</select>
		</td>
	</tr>
	<tr><td colspan="2">
<h3>' . __('Contact Info') . '</h3>
	</td></tr>
<tr>
	<th><label for="email">' . __('E-mail') . ' <span class="description">' . __('(required)') . '</span></label></th>
	<td><input type="text" name="email" id="email" value="' . esc_attr($current_user->user_email) . '" class="regular-text" />
	';
	$new_email = get_option( $current_user->ID . '_new_email' );
	if ( $new_email && $new_email != $current_user->user_email ) {
		$html .= '
	<div class="updated inline">
	<p>' . sprintf( __('There is a pending change of your e-mail to <code>%1$s</code>. <a href="%2$s">Cancel</a>'), $new_email['newemail'], esc_url( admin_url( 'profile.php?dismiss=' . $current_user->ID . '_new_email' ) ) ) . '</p>
	</div>
		';
	}
	$html .= '
	</td>
</tr>

<tr>
	<th><label for="url">' . __('Website') . '</label></th>
	<td><input type="text" name="url" id="url" value="' . esc_attr($current_user->user_url) . '" class="regular-text code" /></td>
</tr>
';

	foreach (_wp_get_user_contactmethods() as $name => $desc) {
		$html .= '
<tr>
	<th><label for="' . $name . '">' . apply_filters('user_'.$name.'_label', $desc) . '</label></th>
	<td><input type="text" name="' . $name . '" id="' . $name . '" value="' . esc_attr($current_user->$name) . '" class="regular-text" /></td>
</tr>';
	}
	
	$html .= '
<tr><td colspan="2">
<h3>' . __('About Yourself') . '</h3>
</td></tr>
<tr>
	<th><label for="description">' . __('Biographical Info') . '</label></th>
	<td><textarea name="description" id="description" rows="5" cols="60">' . esc_html($current_user->description) . '</textarea><br />
	<span class="description">' . __('Share a little biographical information to fill out your profile. This may be shown publicly.') . '</span></td>
</tr>
<tr><td></td><td style="text-align: right;"><input type="submit" class="button-primary" value="' . __('Update Profile') . '" name="submit" /></td></tr>
</table>
</form>
';
	return $html;
}

// upgrade system/sales ann.
function ym_check_upgrade() {
	if (!isset($_GET['ym_page'])) {
		// for some reason some how, this function gets called during a auto upgrade
		// which means that people wont get the popups....
		// this will exit out until next page load
		return;
	}
	
	$old_version = get_option('ym_current_version');
	$action = FALSE;
	if ($old_version_object = explode(':', $old_version)) {
		if (isset($old_version_onbject[2])) {
			if ($old_version_id = (int)$old_version_object[2]) {
				$this_version = YM_PLUGIN_VERSION . ':' . strtoupper(YM_PLUGIN_PRODUCT) . ':' . YM_PLUGIN_VERSION_ID;

				if ($old_version_id < YM_PLUGIN_VERSION_ID) {
					$action = 'Upgrade';
				} else if ($old_version_id > YM_PLUGIN_VERSION_ID) {
					$action = 'Downgrade';
				}

				if ($action) {
//				    ym_email(YM_DEVELOPER_EMAIL, 'YM ' . $action . ' on: ' . $_SERVER['HTTP_HOST'], 'A copy of YM has been ' . $action . 'd on ' . $_SERVER['HTTP_HOST'] . ' at server time ' . date('d/m/Y H:i') . '.<br /><br />Pre ' . $action . ', the version string was "' . $old_version . '".<br />Post ' . $action . ', the version is "' . $this_version . '"<br /><br />The version string translates as Version:Product:YBUY Product ID.');
					update_option('ym_current_version', $this_version);
				}
			}
		}
	} else {
		// new install
	}
	return $action;
}

/**
Redirects
*/
/**
Defined a default place to go
I think its depricated
*/
function ym_login_redirect() {
	global $ym_sys;
	$run = true;

	$array = array(
		'redirect_to'	=> 1,
		'action'		=> 1,
		'username'		=> 1,
		'ym_subscribe'	=> 1,
		'subs'			=> 1,
		'ud'			=> 1,
//		'from_gateway'	=> 1,
	);

	foreach ($_REQUEST as $key=>$value) {
		if (isset($array[$key])) {
			$run = false;
			break;
		}
	}

	if ($run) {
		$redirect_to = FALSE;

		if ($ym_sys->login_redirect_url && !$redirect_to) {
			$redirect_to = $ym_sys->login_redirect_url;
		}

		if ($redirect_to) {
			$url = site_url('wp-login.php?redirect_to=' . rawurlencode(site_url($redirect_to)));

			if (ym_request('loggedout')) {
				$url .= '&loggedout=true';
			}
			
			if ($checkemail = ym_request('checkemail')) {
				$url .= '&checkemail=' . $checkemail;
			}

			if (ym_request('from_gateway')) {
				$append = array(
					'from_gateway',
					'item',
					'bundle_id',
					'post_id',
					'pack_id',
				);
				foreach ($append as $item) {
					if ($v = ym_request($item)) {
						$url .= '&' . $item . '=' . $v;
					}
				}
			}

			if (!headers_sent()) {
				header('Location: ' . $url);
			} else {
				echo '<script>window.location="' . $url . '";</script>';
			}
			exit;
		}
	}
}
function ym_wp_login($user_login, $user) {
	global $ym_user, $ym_sys;
	if (!$ym_user) {
		$ym_user = new YourMember_User($user->data->ID);
	}

	$firstlogin = false;
	if(!get_user_meta( $user->data->ID, 'ym_user_last_login')){
		$firstlogin = true;
	}

	$ym_user->is_logging_in();

	$redirect_to = FALSE;

	// Priority One: Request Redirect
	if (isset($_REQUEST['redirect_to']) && $_REQUEST['redirect_to']) {
		$redirect_to = $_REQUEST['redirect_to'];
	}

	//Sneaky not normal redirect
	if($firstlogin){
		$pack = ym_get_pack_by_id($ym_user->pack_id);
		if ($pack['login_redirect_url']) {
			$redirect_to = site_url($pack['first_login']);
		}
	}

	// Priority Two: Pack Login Redirect
	if (!$redirect_to && isset($ym_user->pack_id) && $ym_user->pack_id) {
		$pack = ym_get_pack_by_id($ym_user->pack_id);
		if ($pack['login_redirect_url']) {
			$redirect_to = site_url($pack['login_redirect_url']);
		}
	}

	// Priority Three: Default
	if (!$redirect_to && $ym_sys->login_redirect_url) {
		$redirect_to = site_url($ym_sys->login_redirect_url);
	}

	if ($redirect_to) {
		if (!headers_sent()) {
			header('Location: ' . $redirect_to);
		} else {
			echo '<script>window.location="' . $redirect_to . '";</script>';
		}
		exit;
	}

	// Priority Four: WP-Admin
}
function ym_stop_wp_admin() {
	if (current_user_can('edit_plugins') ) {
		return;
	}
	global $ym_sys, $ym_user;
	if (current_user_can($ym_sys->account_interface_admin_role)) {
		return;
	}

	$redirect_to = FALSE;

	// Priotiy Zero: If specifically goin to the ym-profile
	// where we going?
	if (ym_get('page') == 'ym-profile' && $ym_sys->membership_details_redirect_url) {
		$redirect_to = $ym_sys->membership_details_redirect_url;
	}

	// Priority One: Pack WP Admin Redirect
	if (isset($ym_user->pack_id) && $ym_user->pack_id && !$redirect_to) {
		$pack = ym_get_pack_by_id($ym_user->pack_id);
		if ($pack['wpadmin_disable_redirect_url']) {
			$redirect_to = $pack['wpadmin_disable_redirect_url'];
		}
	}
	
	// Priority Two: Default Admin Redirec
	if (@$ym_sys->wpadmin_disable_redirect_url && !$redirect_to) {
		$redirect_to = $ym_sys->wpadmin_disable_redirect_url;
	}

	if ($redirect_to) {
		// special bypass/allow code
		if ($redirect_to == 'ALLOW') {
			return;
		}
		$redirect_to = site_url($redirect_to);
		if (!headers_sent()) {
			header('Location: ' . $redirect_to);
		} else {
			echo '<script>document.location="' . $redirect_to . '";</script>';
		}
		exit;
	}

	// Priority Three: WP-Admin
}
function ym_wp_logout($return_url = FALSE) {
	global $ym_sys, $ym_user;

	$redirect_to = FALSE;

	if (isset($ym_user->pack_id) && $ym_user->pack_id) {
		$pack = ym_get_pack_by_id($ym_user->pack_id);
		if ($pack['logout_redirect_url']) {
			$redirect_to = $pack['logout_redirect_url'];
		}
	}

	// what a horrible line
	$redirect_to = $redirect_to ? site_url($redirect_to) : ($ym_sys->logout_redirect_url ? site_url($ym_sys->logout_redirect_url) : (isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : FALSE));
	if ($redirect_to) {
		if ($return_url) {
			return $redirect_to;
		}
		header('Location: ' . $redirect_to);
		exit;
	}
}

// css
function ym_styles() {
	wp_enqueue_script('jquery');
	wp_enqueue_style('yourmembers', YM_CSS_DIR_URL . 'ym.css', false, YM_PLUGIN_VERSION, 'all');
	wp_enqueue_style('yourmembers_icons', YM_CSS_DIR_URL . 'ym_icons.css', false, YM_PLUGIN_VERSION, 'all');
}

function ym_login_styles() {
	wp_enqueue_script('jquery');
	echo '
<link rel="stylesheet" id="ym-login-css"  href="' . YM_CSS_DIR_URL . 'ym.css" type="text/css" media="all" />
<link rel="stylesheet" id="ym-icons-css"  href="' . YM_CSS_DIR_URL . 'ym_icons.css" type="text/css" media="all" />
<link rel="stylesheet" id="jquery-ui-css"  href="https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.all.css" type="text/css" media="all" />
';
}

/**
Login
*/
/**
 * code to run on the login page.. could be custom feedback or some js.
 * add_action('ym_return_true','yourfunction'); will fire up here :)
 *
 */
function ym_login_js() {
	// gateways
	$gateways = get_option('ym_modules');
	
	foreach ($gateways as $way) {
		if (ym_get('from_gateway') == $way) {
			$js = new $way();
			if (isset($js->callback_script)) {
				$js = $js->callback_script;
				
				global $current_user;
				get_currentuserinfo();
				
				$js = str_replace('[user_id]', $current_user->ID, $js);
				$js = str_replace('[pack_id]', ym_get('pack_id'), $js);
				$js = str_replace('[post_id]', ym_get('post_id'), $js);
				$js = str_replace('[post_pack_id]', ym_get('post_pack_id'), $js);
				$js = str_replace('[item_code]', ym_get('item'), $js);
				
				$cost = 0;
				$account_type = '';
				if (ym_get('pack_id')) {
					foreach (ym_get_packs() as $pack) {
						if ($pack['id'] == ym_get('pack_id')) {
							$cost = $pack['cost'];
							$account_type = $pack['account_type'];
							break;
						}
					}
				} else if (ym_get('post_id')) {
					// post
					$cost = get_post_meta(ym_get('post_id'), '_ym_post_purchasable_cost', true);
				} else if (ym_get('post_pack_id')) {
					// bundle
					$bundle = ym_get_bundle(ym_get('post_pack_id'));
					$cost = $bundle->cost;
				}
				$js = str_replace('[cost]', $cost, $js);
				$js = str_replace('[account_type]', $account_type, $js);

				add_shortcode('if_cb_pack', 'ym_login_js_cb_pack');
				add_shortcode('if_cb_post', 'ym_login_js_cb_post');
				add_shortcode('if_cb_bundle', 'ym_login_js_cb_bundle');

				$js = do_shortcode($js);

				$js = apply_filters('ym_login_js', $js);

				echo $js;
			}
			do_action('ym_return_true');
		}
	}

	// enctype not on payment buttons please!
	if (isset($_REQUEST['ym_subscribe'])) {
		return;
	}

	// it only breaks zombaio.....
	// but payment gateways arn't specific classed.
	// check for zombaio
	

	echo '
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(\'form\').attr(\'enctype\', \'multipart/form-data\');
});
</script>
';
}

function ym_login_js_cb_pack($atts, $content) {
	if (ym_get('pack_id')) {
		return $content;
	}
	return '';
}
function ym_login_js_cb_post($atts, $content) {
	if (ym_get('post_id')) {
		return $content;
	}
	return '';
}
function ym_login_js_cb_bundle($atts, $content) {
	if (ym_get('post_pack_id')) {
		return $content;
	}
	return '';
}

/**
* Additional login messages
*/
function ym_login_message($message) {
	global $ym_res;

	if (ym_request('checkemail') == 'subscribed') {
		$message = '<p class="message">' . $ym_res->checkemail_subscribed . '</p>';
	}
	if (ym_request('checkemail') == 'bundle') {
		$message = '<p class="message">' . $ym_res->checkemail_bundle . '</p>';
	}
	if (ym_request('checkemail') == 'post') {
		$message = '<p class="message">' . $ym_res->checkemail_post . '</p>';
	}
	
	if (ym_request('checkemail') == 'loginneeded') {
		$message = '<p id="login_error">' . $ym_res->checkemail_loginneeded . '</p>';
	}
	if (ym_request('checkemail') == 'noaccess') {
		$message = '<p id="login_error">' . $ym_res->checkemail_noacccess . '</p>';
	}

	$message = apply_filters('ym_login_message', $message);

	return $message;
}

function ym_login_headerurl() {
	global $ym_sys;
	return site_url($ym_sys->wp_login_header_url);
}
function ym_login_headertitle() {
	return get_bloginfo();
}
function ym_login_header_logo() {
	global $ym_sys;
	echo '
<style type="text/css">' . "
	.login h1 a {
		background: url('" . site_url($ym_sys->wp_login_header_logo) . "') no-repeat;
		margin-bottom: 15px;
	}
</style>
";
}

/**
Login Redirect
*/
function ym_block_wp_login_action_register($rules) {
	global $ym_sys;

	if ($ym_sys->block_wp_login_action_register) {
		$url = site_url($ym_sys->block_wp_login_action_register);

		$char = strpos($rules, 'RewriteRule');
		$start = substr($rules, 0, $char);
		$middle = '

RewriteCond %{REQUEST_URI} wp-login\.php$
RewriteCond %{QUERY_STRING} ^action=register$
		RewriteRule . ' . $url . '? [R=302,L]

';
		$end = substr($rules, $char);
		return $start . $middle . $end; 
	}

	return $rules;
}
function ym_block_wp_login_action_register_flush() {
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}

/**
End Login
*/

function ym_get_all_users($level = 'Member') {
	global $wpdb;

	$return = array();

	$sql = 'SELECT u.ID
			FROM
				' . $wpdb->users . ' u
				JOIN ' . $wpdb->usermeta . ' um ON (
					u.ID = um.user_id
					AND um.meta_key = \'ym_user\'
					AND um.meta_value LIKE \'%"' . $level . '"%\'
				)
				JOIN ' . $wpdb->usermeta . ' um2 ON (
					u.ID = um2.user_id
					AND um2.meta_key = \'ym_status\'
					AND um2.meta_value = \'Active\'
				)
			ORDER BY u.ID DESC'; //very important to leave the "'s in around level
	$users = $wpdb->get_results($sql);

	foreach ($users as $user=>$id) {
		$return[] = $id->ID;
	}

	return $return;
}

function ym_is_user_active($user_name) {
	$user = get_user_by('login', $user_name);

	if ($user) {
		if (isset($user->ym_user)) {
			if (strtolower($user->ym_status) != 'active' && strtolower($user->ym_status) != 'grace') {
				$user = false;
			}
		} else {
			$user = false;
		}
	}

	return $user;
}

/**
shiny debug
*/
function ym_debug_mode_check() {
	$return = false;

	if (isset($_SESSION['ym_debug_mode'])) {
		$return = true;
	}

	return $return;
}

function ym_debug_mode_set() {

	if (isset($_GET['ym_debug_mode'])) {
		$status = $_GET['ym_debug_mode'];

		if ($status == 'on') {
			$_SESSION['ym_debug_mode'] = true;
			$_SESSION['ym_debug_mode_method'] = 'echo';
		} else {
			unset($_SESSION['ym_debug_mode']);
		}
	}

	if (isset($_GET['ym_debug_mode_method'])) {
		$method = $_GET['ym_debug_mode_method'];

		if ($method == 'email') {
			$_SESSION['ym_debug_mode_method'] = 'email';
		} else {
			$_SESSION['ym_debug_mode_method'] = 'echo';
		}
	}
}

function ym_debug($data=false) {

	if (ym_debug_mode_check()) {
		$html = '<div style="padding: 2px; margin: 2px; border: 1px solid black; background-color: white;">
					<!-- <strong>Backtrace: </strong>
						<pre>' . print_r(debug_backtrace(), true) . '</pre> -->
					<strong>Data: </strong>
						<pre>' . (is_array($data) || is_object($data) ? print_r($data, true):$data) . '</pre>
				</div>';

		if ($_SESSION['ym_debug_mode_method'] == 'echo') {
			echo $html;
		} else {
			ym_email(YM_DEBUG_EMAIL, 'Debug Email' . ($where_from ? ' from ' . $where_from:''), $html);
		}
	}
}
/**
End
*/

function ym_get_user_package_type($user_id=false, $to_lower=false) {
	if ($user_id) {
		$ym_user = new YourMember_User($user_id);
	} else {
		global $ym_user;
	}
	$acc = $ym_user->account_type;

	if (!$acc && $ym_user->parent_id) {
		// inherit
		$acc = ym_get_user_package_type($ym_user->parent_id, $to_lower);
	}

	if ($to_lower) {
		$acc = strtolower($acc);
	}
	return $acc;
}

function ym_is_post_protected($post_id = false) {
	if (!$post_id) {
		$post_id = get_the_id();
	}

	if (get_post_meta($post_id, '_ym_account_type', true) !='' || get_post_meta($post_id, '_ym_post_purchasable', true) == '1'){
		return true;
	}
}

function ym_generate_field($name, $type_id, $value=false, $return=true, $id=false) {
	switch ($type_id) {
		case 1:
			$html = '<input type="text" style="width: 500px;" name="' . $name . '" value="' . $value . '" ' . ($id ? 'id="' .$id . '"':'') . '/>';
			break;
		case 2:
			$html = '<textarea name="' . $name . '" rows="4" cols="60">' . $value . '</textarea>';
			break;
	}

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ym_get_currencies() {
	$currencies = array(
		 'AUD' => sprintf(__('%s - Australian Dollar', 'ym'), 'AUD'),
		 'CAD' => sprintf(__('%s - Canadian Dollar', 'ym'), 'CAD'),
		 'EUR' => sprintf(__('%s - Euro', 'ym'), 'EUR'),
		 'GBP' => sprintf(__('%s - Pound Sterling', 'ym'), 'GBP'),
		 'JPY' => sprintf(__('%s - Japanese Yen', 'ym'), 'JPY'),
		 'USD' => sprintf(__('%s - U.S. Dollar', 'ym'), 'USD'),
		 'NZD' => sprintf(__('%s - New Zealand Dollar', 'ym'), 'NZD'),
		 'CHF' => sprintf(__('%s - Swiss Franc', 'ym'), 'CHF'),
		 'HKD' => sprintf(__('%s - Hong Kong Dollar', 'ym'), 'HKD'),
		 'SGD' => sprintf(__('%s - Singapore Dollar','ym'), 'SGD'),
		 'SEK' => sprintf(__('%s - Swedish Krona', 'ym'), 'SEK'),
		 'DKK' => sprintf(__('%s - Danish Krone', 'ym'), 'DKK'),
		 'PLN' => sprintf(__('%s - Polish Zloty', 'ym'), 'PLN'),
		 'NOK' => sprintf(__('%s - Norwegian Krone', 'ym'), 'NOK'),
		 'HUF' => sprintf(__('%s - Hungarian Forint', 'ym'), 'HUF'),
		 'CZK' => sprintf(__('%s - Czech Koruna', 'ym'), 'CZK'),
		 'ILS' => sprintf(__('%s - Israeli New Skekel', 'ym'), 'ILS'),
		 'MXN' => sprintf(__('%s - Mexican Pseo', 'ym'), 'MXN'),
		 'BRL' => sprintf(__('%s - Brazilian Real', 'ym'), 'BRL'),
		 'MYR' => sprintf(__('%s - Malaysian Ringgit', 'ym'), 'MYR'),
		 'PHP' => sprintf(__('%s - Philippine Peso', 'ym'), 'PHP'),
		 'TWD' => sprintf(__('%s - New Taiwan Dollar', 'ym'), 'TWD'),
		 'THB' => sprintf(__('%s - Thai Baht', 'ym'), 'THB'),
		 'TRY' => sprintf(__('%s - Turkish Lira'), 'TRY'),
	);
	return $currencies;
}

function ym_get_hash() {
	if (!$hash = get_option('ym_site_hash')) {
		$hash = substr(md5((time() - mt_rand(1000, 9999))), 0, 5);
		
		update_option('ym_site_hash', $hash);
	}
	
	return $hash;
}

function ym_gen_hash($id) {
	$hash_base = ym_get_hash();
	$hash = md5($id .= $hash_base);
	
	return $hash;
}

/*
// Your Minder fold it
function ym_ymind_login($userdata, $password) {
	// user login
	$user_id = $userdata->ID;

	if (!$user_id) {
		return $userdata;//fail loging?
	}
	// currently locked?
//	$locked = get_user_meta($user_id, 'ym_locked_out', TRUE);
//	if ($locked == '1') {
		// what to do?
//		return new WP_Error('ym', __('Your Account is Currently Locked', 'ym'));
//	}
	$e = ym_ymind_lockout($user_id);
	if (is_wp_error($e)) {
		return $e;
	}

	$last_login = get_user_meta($user_id, 'ym_last_login');
	update_user_meta($user_id, 'ym_last_login', time());

	$limit = get_option('ym_ymind_limit');
	$limit = time() - $limit;
	if ($last_login > $limit) {
		// recent login do check
		echo 'DOING A CHECK';
	}

	return $userdata;
}
function ym_ymind_lockout($user_id) {
	$locked = get_user_meta($user_id, 'ym_locked_out', TRUE);
	if ($locked == '1') {
		return new WP_Error('ym', __('Your Account is Currently Locked', 'ym'));
	}
}
*/

/*
function ymind_ip_check() {
	get_currentuserinfo();
	global $current_user;

	$current_ip = $_SERVER['REMOTE_ADDR'];

	$last_ip = get_user_meta($current_user->ID, 'ym_current_ip');
	$last_time = get_user_meta($current_user->ID, 'ym_current_ip_time');

	// 30mins? 30 * 60 = 1800
	$limit = time() - 1800;
	if ($last_time > $limit) {
		// criteria met
		if ($current_ip != $last_ip) {
			// multiple login detected

		}
	}
}
// function fires on login
function ymind_ip_log() {
	get_currentuserinfo();
	global $current_user;
	
	$ip = $_SERVER['REMOTE_ADDR'];

	update_user_meta($current_user->ID, 'ym_current_ip', $ip);
	update_user_meta($current_user->ID, 'ym_current_ip_time', time());
	// historical
}
*/

function ym_get_currency($pack_id=false) {
	global $ym_res;
	$currency = $ym_res->currency;

	if($pack_id){
		$pack = ym_get_pack($pack_id);
		if (isset($pack['currency']) && $pack['currency']) {
			$currency = $pack['currency'];
		}
	}

	return $currency;
}

//Metered Access
function ym_check_metered_access(){
	global $ym_sys, $wp_query;

	if(!$ym_sys->enable_metered) return false;
	if(is_user_logged_in()) return false;
	//If Admin has enabled DNT and user has header block access don't go any further, don't set any cookies
	if($ym_sys->enable_dnt_metered){
		if(isset($_SERVER['HTTP_DNT']) && $_SERVER['HTTP_DNT'] == 1) return false;
	}

	$post_id = url_to_postid( "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] );
	if(!$post_id) return false;
	$types = strtolower(get_post_meta($post_id, '_ym_account_type', true));
	if($types){
		$act = strtolower($ym_sys->metered_account_types);
		$act =explode(';', $act);
		$types = explode(';', $types);
		$r = false;
		foreach ($types as $type) {
			if($type = 'guest') $r=true;
			elseif(in_array($type, $act)) $r = true;
		}

		if(!$r) return false;
	}
	
	//let's get the cookie
	$return = false;
	$cookie = stripslashes($_COOKIE['ymmeter']);
	$cookie = unserialize($cookie);
	
	//Cookie may have been tampered with
	if(!$cookie && $_COOKIE['ymmeter']) return false;
	//likewise it might have been deliberately removed
	if(!$_COOKIE['ymmeter'] && $_COOKIE['ymmeterexpiry']) return false;

	$posts = array();
	$posts = $cookie['posts'];
	$deined = array();
	$denied = $cookie['denied'];
	$check = 0;
	$check = $cookie['check'];
	$expiry = 0;
	$expiry = $_COOKIE['ymmeterexpiry'];
	if($post_id){
			if(!$posts) $posts = array();
			if(in_array($post_id, $posts)){
				$return = true;
			}
			else{
				if($ym_sys->metered_posts > $check){ 
					$posts[] = $post_id;
					$return = true;
				}
				else{
					if(!$denied) $denied = array();
					if(!in_array($post_id, $denied)) $denied[] = $post_id;		
				}
				$check = $check+1;
			}
		}
	
		if(!$expiry){
			$unit = $ym_sys->metered_duration;
			$type = $ym_sys->metered_duration_type;
			if($type = 'd') $m = 86400;
			if($type = 'm') $m = 86400*30;
			if($type = 'y') $m = 86400*365;
			$expiry = time()+($unit * $m);
			setcookie('ymmeterexpiry',$expiry,$expiry);
		}
		
		$ncookie = array();
		$ncookie['check'] = $check;
		$ncookie['posts'] = $posts;
		$ncookie['denied'] = $denied;
		$ncookie = serialize($ncookie);
		
		setcookie('ymmeter',$ncookie,$expiry);

		//Check they actually are cookied to avoid switching cookies off
		if(!$_COOKIE['ymmeter'] || !$_COOKIE['ymmeterexpiry']) $return = false;

		//Let them see the content
		return $return;
}

function ym_facebook_uncode($data) {
	list($encoded_sig, $payload) = explode('.', $data, 2);

	$sig	= base64_decode(strtr($encoded_sig, '-_', '+/'));
	$data	= base64_decode(strtr($payload, '-_', '+/'));
	$data	= json_decode($data);

	if (strtoupper($data->algorithm) !== 'HMAC-SHA256') {
		// bad alg
		return false;
	}

	// check sig
	$expected_sig = hash_hmac('sha256', $payload, get_option('ym_register_flow_fb_secret'), $raw = true);
	if ($sig !== $expected_sig) {
		// bad json
		return false;
	}

	if ($data) {
		return $data;
	}
	return;
}

