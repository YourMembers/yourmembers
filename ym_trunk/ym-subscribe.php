<?php

/*
* $Id: ym-subscribe.php 2529 2013-01-17 11:58:50Z bcarlyon $
* $Revision: 2529 $
* $Date: 2013-01-17 11:58:50 +0000 (Thu, 17 Jan 2013) $
*/

if (!get_option('users_can_register')) {
	header('Location: ' . site_url('wp-login.php'));
	exit;
}
define('subscribe.php', TRUE);

$header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>WordPress &raquo; ' . __('Subscription') . '</title>
	<meta http-equiv="Content-Type" content="' . get_bloginfo('html_type') . '; charset=' . get_option('blog_charset') . '" />
	<link rel=\'stylesheet\' href=\'' . site_url('/wp-admin/css/wp-admin.css') . '\' type=\'text/css\' media=\'all\' />
	<link rel=\'stylesheet\' href=\'' . site_url('/wp-admin/css/colors-fresh.css') . '\' type=\'text/css\' media=\'all\' />';

ob_start();
do_action('login_enqueue_scripts');
do_action('login_head');
$header .= ob_get_clean();

$header .= '</head>
<body class="login">
<div id="login">
	<h1><a href="' . apply_filters('login_headerurl', 'http://wordpress.org/') . '" title="' .apply_filters('login_headertitle', esc_attr__('Powered by WordPress')) . '">' . apply_filters('login_headertitle', esc_attr__('Powered by WordPress')) . '<span class="hide">WordPress &raquo; ' . __('Subscription') . '</span></a></h1>
	<p class="message register">' . __('Subscribe', 'ym') . '</p>
<div>
<p id="backtoblog"><a href="' . get_bloginfo('url') . '/">' . __('&larr; Back to ') . get_bloginfo('title') . '</a></p>
<br />
';

$footer = '
</div>
</div>';
ob_start();
do_action('login_footer');
$footer .= ob_get_clean();
$footer .= '
<div class="clear"></div>
</body>
</html>';

$user = (isset($_GET['username']) ? get_user_by('login', $_GET['username']) : false);
global $ym_user;
if (!$ym_user) {
	$ym_user = new YourMember_User($user->ID);
}
$user_status = $ym_user->status;

$page = ym_request('ym_page');

if ($upgrade = ym_get('ym_upgrade')) {
	global $ym_home;

	//http://www.yoursite.com/?ym_upgrade=email@user.com&pack_id=1&get_fields=true - pack_id and get_fields optional
	if ($user_id = ym_get_user_id_by_email($upgrade)) {
		$user = get_userdata($user_id);
		
		$url = $ym_home . '?ym_subscribe=1&username=' . $user->user_login;
		
		if ($pack_id = ym_request('pack_id')) {
			$url .= '&pack_id=' . $pack_id;
		}
		
		if (ym_get('get_fields')) {
			$url .= '&ym_page=1&another_page_needed=2';
		}
		
		if (!headers_sent()) {
			@header('location: ' . $url);
		} else {
			echo '<script>window.location="' . $url . '";</script>';
		}
	} else {
		global $ym_home;
		header('location: ' . $ym_home);
	}
} else if ($another_page_needed = ym_request('another_page_needed')) {
//	echo 'using another page';exit;
	$html = $header;
	$html .= ym_get_additional_registration_form_page($another_page_needed, $page);
	$html .= $footer;
} else if (isset($_GET['ud']) && $_GET['ud'] == 1) {
	$html = $header;
	
	$user_id = false;
	if ($username = ym_get('username')) {
		$user_id = ym_get_user_id_by_username($username);
	} else if ($email = ym_get('email')) {
		$user_id = ym_get_user_id_by_email($email);
	}
	$pack_id = ym_post('ym_subscription', FALSE);
	
	$html .= ym_upgrade_buttons(true, $pack_id, $user_id);
	
	$html .= $footer;

} else if (ym_request('username')) {
	$errors = false;
	
	$html = $header;
	
	if ($page > 1) {
		$wp_error = new WP_Error();
		ym_register_post(ym_request('username'), '', $wp_error, $page); //error checking
		
		if ($wp_error->get_error_code()) {
			$errors = true;
			$additional_page_needed = ($page+1);
			$html .= ym_get_additional_registration_form_page($additional_page_needed, $page);
		}
	}
	
	if (!$errors) {
		$html .= ym_available_modules(ym_request('username'), true);
	}
	
	$html .= $footer;
} else if ($user_status == YM_STATUS_PENDING) {
	$html = $header . '<p>' . __('Error - Your subscription status is pending. Please contact an administrator for more information.', 'ym') . '</p>' . $footer;
} else {
	$html = $header . '<p>' . __('You are already subscribed or an error occurred. Please contact an administrator for more information.', 'ym') . '</p>' . $footer;
}

echo $html;

