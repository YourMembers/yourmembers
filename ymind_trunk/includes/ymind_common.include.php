<?php

//returns the path of the url like parse_url but means that this code will
//work with pre php 5.1.2 when the second argument of parse_url was released
function ymind_get_filename($url) {

	$url = str_replace('//','', $url);
	$url = strstr($url, '/');
	$question = strpos($url, '?');
	if ($question > 0) {
		$url = substr($url, 0, $question);
	}

	$url = str_replace('/', '', strrchr($url, '/'));

	return $url;
}

function ymind_log_ip($user) {
	global $wpdb;

	if (get_usermeta($user->ID, 'ymind_locked_out') > 1) {
		$lockout_expiry = ymind_check_lockout_expiry($user);

		if ($lockout_expiry < time()) {
			update_usermeta($user->ID, 'ymind_locked_out', 0); //clear the lockout
		} else {
			$error = true;
		}
	} else if (get_usermeta($user->ID, 'ymind_locked_out') == 1) {
		$error = true;
	} else if (ymind_ip_is_blocked(ymind_current_user_ip())) {
		$error = true;
	} else {
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'ymind_ip_log (
		user_id
		, ip_address
		, unixtime
		) VALUES (
		' . $user->ID . '
		, "' . $_SERVER['REMOTE_ADDR'] . '"
		, UNIX_TIMESTAMP()
		)';
		$wpdb->query($sql);
	}

	if ($error) {
		$message = get_option('ymind_locked_out_error');
		$user = new WP_Error();
		$user->add('locked_out', $message);
	}

	return $user;
}

function ymind_email_user() {
	get_currentuserinfo();
	global $current_user;

	$to = $current_user->user_email;
	$subject = get_option('ymind_email_subject');
	$message = get_option('ymind_mail_message');

	$key = md5('user_' . $current_user->ID) . mt_rand(1,999);

	$url = get_bloginfo('url') . '/?ymind_activate=' . $key;
	$link = '<a href="' . $url . '">' . $url . '</a>';
	$message = str_replace('[activation_link]',$link, $message);

	//set key
	update_usermeta($current_user->ID, 'ymind_activation_key', $key);

	//spam away...
	wp_mail($to, $subject, $message);
}

function ymind_check_lockout() {
	get_currentuserinfo();
	global $wpdb, $current_user;

	$return = false;
	$lockout = get_usermeta($current_user->ID, 'ymind_locked_out');

	if ($lockout == 0) {

		$time_offset = time() - (get_option('ymind_timeout_minutes') * 60); //minutes into seconds (ago)
		$max_logins = get_option('ymind_timeout_logins'); //max logins from different ips before lockout

		$sql = 'SELECT COUNT(DISTINCT(ip_address))
				FROM ' . $wpdb->prefix . 'ymind_ip_log
				WHERE
					user_id = ' . $current_user->ID . '
					AND unixtime > ' . $time_offset;
		$logins = $wpdb->get_var($sql);

		if ($logins >= $max_logins) {
			$return = true;
		}
	} else if ($lockout == 1) {
		$return = true;
	} else if ($lockout > 1) {
		$lockout_expiry = ymind_check_lockout_expiry($current_user);

		if ($lockout_expiry > time()) {
			$return = true;
		}
	}

	return $return;
}

function ymind_check_lockout_expiry($user) {
	$lockout_expiry = 0;

	if ($locked_out_since = get_usermeta($user->ID, 'ymind_locked_out')) {
		$logout_secs = get_option('ymind_lockout_minutes') * 60;
		$lockout_expiry = ($locked_out_since + $logout_secs);
	}

	return $lockout_expiry;
}

function ymind_ip_check() {
	get_currentuserinfo();
	global $current_user;

	$path = ymind_get_filename($_SERVER['HTTP_REFERER']);

	if ($current_user->ID && $path != 'wp-login.php') {
		if (ymind_check_lockout()) {

			$lockout = get_option('ymind_lockout_option');
			$send_email = get_option('ymind_email_offender');

			if ($lockout == 1) {
				if ($send_email) {
					$value = 1;
				} else {
					$value = time();
				}

				update_usermeta($current_user->ID, 'ymind_locked_out', $value);
			}

			if ($send_email == 1){
				ymind_email_user();
			}

			wp_clearcookie();

			$url = get_option('ymind_redirect_url');
			echo '<script>document.location="' . $url . '";</script>';
		}
	}
}

function ymind_check_activate_account() {
	global $wpdb;

	if (ymind_get('ymind_activate')) {
		$sql = 'SELECT user_id
				FROM ' . $wpdb->usermeta . '
				WHERE
					meta_key = "ymind_activation_key"
					AND meta_value = "' . mysql_real_escape_string(ymind_get('ymind_activate')) . '"';

		if ($id = $wpdb->get_var($sql)) {
			update_usermeta($id, 'ymind_locked_out', 0);
			delete_usermeta($id, 'ymind_activation_key');

			$redirect = get_option('ymind_activate_redirect');
			if ($redirect) {
				echo '<script>document.location="' . $redirect . '";</script>';
			}
		}
	}
}

function ymind_current_user_ip() {
	return $_SERVER['REMOTE_ADDR'];
}

function ymind_user_last_ip($user=false) {
	global $user_ID, $wpdb;
	
	$return = false;

	if (!$user) {
		$user = $user_ID;
	}

	if ($user) {

		$sql = 'SELECT `ip_address`
				FROM `' . $wpdb->prefix . 'ymind_ip_log`
				WHERE `user_id` = "' . $user . '"
				ORDER BY `unixtime` DESC
				LIMIT 1';
		$return = $wpdb->get_var($sql);
	}

	return $return;
}

function ymind_user_last_login($user=false) {
	global $user_ID, $wpdb;

	if (!$user) {
		$user = $user_ID;
	}

	if ($user) {
		$time = false;

		$sql = 'SELECT `ip_address`, `unixtime`
				FROM `' . $wpdb->prefix . 'ymind_ip_log`
				WHERE `user_id` = '.$user.'
				ORDER BY `unixtime` DESC
				LIMIT 1';
		if ($r = $wpdb->get_row($sql)) {
			$time = date(get_option('date_format'), $r->unixtime);
		}
	}

	return $time;
}

function ymind_filter_post($content) {

	$pattern = "'\[\[user_ip]\]'is";
	$content = preg_replace_callback($pattern, "ymind_current_user_ip", $content);

	$pattern = "'\[\[user_last_ip]\]'is";
	$content = preg_replace_callback($pattern, "ymind_user_last_ip", $content);

	$pattern = "'\[\[user_last_login]\]'is";
	$content = preg_replace_callback($pattern, "ymind_user_last_login", $content);

	return $content;
}

function ymind_ip_is_blocked($ip) {
	global $wpdb;

	$sql = 'SELECT COUNT(id)
			FROM `' . $wpdb->prefix . 'ymind_block_list` 
			WHERE `ip` = "'.$ip.'"';

	return $wpdb->get_var($sql);

}

function ymind_request($key, $default='', $strip_tags=false) {
	if (isset($_POST[$key])) {
		$default = $_POST[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	return $default;
}

function ymind_post($key, $default='', $strip_tags=false) {
	if (isset($_POST[$key])) {
		$default = $_POST[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	return $default;
}

function ymind_get($key, $default='', $strip_tags=false) {
	if (isset($_GET[$key])) {
		$default = $_GET[$key];

		if ($strip_tags) {
			$default = strip_tags($default);
		}
	}

	return $default;
}

?>