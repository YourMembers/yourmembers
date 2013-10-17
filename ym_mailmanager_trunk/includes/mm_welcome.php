<?php

$ym_mm_wp_new_user_notification_login = '';
$ym_mm_wp_new_user_notification_password = '';

// on plugin activation the function exists!
if (!function_exists('wp_new_user_notification')) {
	function wp_new_user_notification($user_id, $plaintext_pass = '') {
		$user = new WP_User($user_id);

		global $ym_mm_wp_new_user_notification_login;
		$ym_mm_wp_new_user_notification_login = stripslashes($user->user_login);
		$user_email = stripslashes($user->user_email);

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
		$message .= sprintf(__('Username: %s'), $ym_mm_wp_new_user_notification_login) . "\r\n\r\n";
		$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

		@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

		if ( empty($plaintext_pass) )
			return;

		$current_welcome = get_option('ym_other_mm_welcome');
		
		$subject = $current_welcome->subject;
		$message = $current_welcome->message;
		add_shortcode('blogname', 'mailmanager_shortcode_blogname');
		add_shortcode('blogurl', 'mailmanager_shortcode_blogurl');
		
		$subject = do_shortcode($subject);
		
		add_shortcode('loginurl', 'wp_login_url');
		add_shortcode('login', 'mailmanager_shortcode_user');

		global $ym_mm_wp_new_user_notification_password;
		$ym_mm_wp_new_user_notification_password = $plaintext_pass;

		global $ym_mm_custom_field_user_id;
		$ym_mm_custom_field_user_id = $user_id;

		add_shortcode('password', 'mailmanager_shortcode_pass');
		add_shortcode('ym_mm_custom_field', 'mailmanager_custom_fields_shortcode');
		add_shortcode('ym_mm_if_custom_field', 'mailmanager_custom_fields_shortcode');
		
		$message = do_shortcode($message);
		// hook into send
		mailmanager_send_email($user_email, $subject, $message);
	}
}

function mailmanager_shortcode_blogname() {
	return wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
}
function mailmanager_shortcode_blogurl() {
	return wp_specialchars_decode(home_url(), ENT_QUOTES);
}
function mailmanager_shortcode_user() {
	global $ym_mm_wp_new_user_notification_login;
	return $ym_mm_wp_new_user_notification_login;
}
function mailmanager_shortcode_pass() {
	global $ym_mm_wp_new_user_notification_password;
	return $ym_mm_wp_new_user_notification_password;
}
