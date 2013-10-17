<?php

/********************************************************************/
// FORCE USE THE FORCE LUKE!
function ym_enter_facebook($args) {
	extract(shortcode_atts(array('page' => '0'), $args));
	
	define('DISABLE_LEAVE_FACEBOOK', TRUE);
	
	if ($_SESSION['in_facebook']) {
		$target = 'top.location.href';
	} else {
		$target = 'document.location';
	}
	
	if ($page == 1 && !$_SESSION['in_facebook_page']) {
		$_SESSION['in_facebook'] = 1;
		$_SESSION['in_facebook_page'] = 1;
		ym_fbook_oauth_go();
		echo '<script type="text/javascript">' . $target . ' = \'' . $_SESSION['ym_fb_auth_target'] . '\'</script>';
		return;
	}
	if (!$_SESSION['in_facebook'] || ($page == 0 && $_SESSION['in_facebook_page'])) {
		$_SESSION['in_facebook'] = 1;
		$_SESSION['in_facebook_page'] = 0;
		ym_fbook_oauth_go();
		echo '<script type="text/javascript">' . $target . ' = \'' . $_SESSION['ym_fb_auth_target'] . '\'</script>';
		return;
	}
}
function ym_leave_facebook() {
	if ($_SESSION['in_facebook']) {
		unset($_SESSION['in_facebook']);
		return ym_fbook_init_iframe_breakout();
	}
}

/********************************************************************/
// In or not in facebook
function ym_if_in_facebook($args, $content) {
	if ($_SESSION['in_facebook']) {
		return do_shortcode($content);
	} else {
		return '';
	}
}
function ym_if_not_in_facebook($args, $content) {
	if ($_SESSION['in_facebook']) {
		return '';
	} else {
		return do_shortcode($content);
	}
}
function ym_if_in_facebook_page($args, $content) {
	if ($_SESSION['in_facebook'] && $_SESSION['in_facebook_page']) {
		return do_shortcode($content);
	} else {
		return '';
	}
}
function ym_if_not_in_facebook_page($args, $content) {
	if ($_SESSION['in_facebook'] && $_SESSION['in_facebook_page']) {
		return '';
	} else {
		return do_shortcode($content);
	}
}

/********************************************************************/
// helper
function ym_fbook_like($like_id) {
	global $facebook_client;
	
	foreach ($facebook_client->likes as $like) {
		if ($like->id == $like_id) {
			return TRUE;
		}
	}
	return FALSE;
}

/********************************************************************/
// If like something
function ym_fbook_if_like($args, $content) {
	extract(shortcode_atts(array('id' => ''), $args));

	if (!$id) {
		return '';
	} else if (ym_fbook_like($id)) {
		return do_shortcode($content);
	} else {
		return '';
	}
}
function ym_fbook_if_not_like($args, $content) {
	extract(shortcode_atts(array('id' => ''), $args));
	
	if (!$id) {
		return do_shortcode($content);
	} else if (ym_fbook_like($id)) {
		return '';
	} else {
		return do_shortcode($content);
	}	
}

/********************************************************************/
// Likewall
function ym_fbook_like_wall_like($args, $content) {
	extract(shortcode_atts(array('id' => '', 'url' => ''), $args));
	
	global $facebook_client;
	if (!$facebook_client) {
		return '';
	}
	
	if (!$id && $url) {
		$id = $facebook_client->get_id_by_url($url);
	}
	
	if (!$id && !$url) {
		// no id what to share?
		$id = $facebook_client->get_id_by_url(get_permalink());
		if (!$id) {
			// don't know what the page is yet.....
			// and its not been shared
			return '';
		}
	}
	
	if (ym_fbook_like($id)) {
		return do_shortcode($content);
	} else {
		return '';
	}
}

function ym_fbook_like_wall_notlike($args, $content) {
	extract(shortcode_atts(array('id' => '', 'url' => ''), $args));
	
	global $facebook_client;
	if (!$facebook_client) {
		$noecho = TRUE;
		include(YM_FBOOK_BASE_DIR . '/template/login.php');
		return $login;
	}
	
	if (!$id && $url) {
		$id = $facebook_client->get_id_by_url($url);
	}
	
	if (!$id && !$url) {
		// no id what to share?
		$url = get_permalink();
		$id = $facebook_client->get_id_by_url($url);
		if (!$id) {
			// don't know what the page is yet.....
			// and its not been shared
			$content .= '[ym_fb_like type="likewall" shareurl="' . $url . '"]';
			return do_shortcode($content);
		}
	}
	
	if (ym_fbook_like($id)) {
		return '';
	} else {
		$content .= '[ym_fb_like type="likewall" shareurl="' . $url . '"]';
		return do_shortcode($content);
	}
}

// FanGate
function ym_fbook_fan_gate_like($args, $content) {
	$data = $_SESSION['facebook_page'];
	
	if ($data) {
		if ($data->liked) {
			return do_shortcode($content);
		}
	}
	return '';
}
function ym_fbook_fan_gate_notlike($args, $content) {
	$data = $_SESSION['facebook_page'];
	
	if ($data) {
		if ($data->liked) {
			return '';
		}
	}
	return do_shortcode($content);
}

/********************************************************************/
// Status
function ym_fbook_wp_logged_in($args, $content) {
	if (is_user_logged_in()) {
		return do_shortcode($content);
	}
	return '';
}
function ym_fbook_wp_not_logged_in($args, $content) {
	if (!is_user_logged_in()) {
		return do_shortcode($content);
	}
	return '';
}
function ym_fbook_fb_logged_in($args, $content) {
	global $facebook_client;
	if ($facebook_client) {
		return do_shortcode($content);
	}
	return '';
}
function ym_fbook_fb_not_logged_in($args, $content) {
	global $facebook_client;
	if (!$facebook_client) {
		return do_shortcode($content);
	}
	return '';
}

// User status string
function ym_fbook_user_status() {
	global $facebook_client, $current_user;
	
	if (!$_SESSION['in_facebook']) {
		return '';
	}
	
	$r = '
	<div id="ym_fbook_user_status">';

	$r .= '<p>';
	if ($facebook_client) {
		$r .= __('You are logged into Facebook as', 'ym_facebook') . ': ' . $facebook_client->user_data->name;
	} else {
		$r .= __('You are not logged into Facebook', 'ym_facebook');
	}
	$r .= ' &amp; ';
	if (is_user_logged_in()) {
		get_currentuserinfo();
		$r .= __('logged into WordPress as' , 'ym_facebook') . ': ' . $current_user->user_email . '/' . $current_user->user_login;
	} else {
		$r .= __('not logged into WordPress', 'ym_facebook');
	}
	$r .= ' <a href="?ym_fb_profile=1">' . __('Profile Status', 'ym_facebook') . '</a></p>';

	$r .= '
	</div>
	';

	$r .= '
			</div>
		</div>
	';
	return $r;
}

// Leave facebook link
function ym_fbook_leave_facebook() {
	global $facebook_settings;
	
	$r = '';
	if ($facebook_settings->enable_leave_facebook && !$facebook_settings->force_facebook && !$facebook_settings->force_facebook_auth && !defined('DISABLE_LEAVE_FACEBOOK') && $_SESSION['in_facebook']) {
		$r = '<div class="ym_leave_facebook_link"><a href="#nowhere">' . sprintf(__('Leave <strong>%s</strong> in Facebook', 'ym_facebook'), get_bloginfo('name')) . '</a></div>';
	}
	return $r;
}
function ym_fb_app_string($args) {
	extract(shortcode_atts(array('both' => ''), $args));
	
	global $facebook_settings;
	
	$render = FALSE;
	if ($_SESSION['in_facebook'] && $_SESSION['in_facebook_page']) {
		$render = TRUE;
	} else if ($both && $_SESSION['in_facebook']) {
		$render = TRUE;
	}
	if ($render) {
		return ' <a href="http://www.facebook.com/apps/application.php?id=' . $facebook_settings->app_id . '" target="_parent">Report/Contact/Remove</a> ';
	}
}

/********************************************************************/
// Like buttons

$ym_fbook_rendered_likes = array();

// filter is for post like facebook
function ym_fbook_render_like_button_filter($content) {
	if (isset($_SESSION['in_facebook']) && $_SESSION['in_facebook']) {
		// hadled elsewhere
		return $content;
	}
	global $facebook_settings;
	if (!$facebook_settings->enable_share_shortcode) {
		return $content;
	}
	if (!$facebook_settings->enable_share_auto_nonfb) {
		return $content;
	}
	
	$content .= ym_fbook_render_like_button(get_permalink(), 'shortcode');
	return $content;
}
// short code
function ym_fbook_render_like_button_shortcode($args) {
	global $facebook_settings;
	
	if (!$facebook_settings->enable_share_shortcode) {
		return;
	}
	
	extract(shortcode_atts(array('shareurl' => get_permalink(), 'type' => 'shortcode'), $args));
	
	if (substr($type, 0, 1) != '_' && $type) {
		$type = '_' . $type;
	} else {
		$type = '';
	}
	
	$return = ym_fbook_render_like_button($shareurl, $type);
	return $return;
}

$ym_fb_valid_types = array(
	'_footer',
	'_shortcode',
	'_likewall'
);

// the generator
function ym_fbook_render_like_button($share_url, $type = '', $echo = FALSE, $force_send_off = FALSE) {
	global $facebook_settings, $ym_fbook_rendered_likes, $ym_fb_valid_types;
	
	if (in_array($share_url, $ym_fbook_rendered_likes)) {
		return '';
	}
	$ym_fbook_rendered_likes[] = $share_url;
	
	if (!in_array($type, $ym_fb_valid_types)) {
		$type = '';
	}

	$enable_share	= 'enable_share' . $type;
	$enable_send	= 'enable_send' . $type;
	$show_faces		= 'show_faces' . $type;
	$verb			= 'verb' . $type;
	$color_scheme	= 'color_scheme' . $type;
	$font			= 'font' . $type;
	$ref			= 'ref' . $type;
	
	if ($facebook_settings->$enable_share) {
		if ($force_send_off) {
			$facebook_settings->$enable_send = FALSE;
		}
		
		$share = 'data-send="' . $facebook_settings->$enable_send 
			. '" data-width="' . do_shortcode('[ym_fb_width]') . '" '
			. ' data-show-faces="' . $facebook_settings->$show_faces 
			. '" data-action="' . $facebook_settings->$verb 
			. '" data-colorscheme="' . $facebook_settings->$color_scheme 
			. '" data-font="' . $facebook_settings->$font . '"';
		
		if ($facebook_settings->$ref) {
			$share .= ' data-ref="' . $facebook_settings->$ref . '"';
		}
		
	 	$string = '
<div class="facebook_share_control ' . ((isset($_SESSION['in_facebook_page']) && $_SESSION['in_facebook_page']) ? 'facebook_share_control_post' : 'facebook_share_control_post') . '">
	<div class="fb-like" data-href="' . $share_url . '" ' . $share . ' ></div>
</div>
';
		if ($echo) {
			echo $string;
		} else {
			return $string;
		}
	}
	return '';
}

/********************************************************************/
// profile and login
function ym_fbook_wp_login_form($echo = TRUE) {
	global $facebook_settings;
	
	add_filter('login_form_top', 'ym_fbook_wp_login_form_top');
	
	$form = '<div id="wordpress_login_dialog">';//' style="display: none;">';
	$form .= wp_login_form(array(
		'echo'		=> false,
		'redirect'	=> ($facebook_settings->require_link ? '?dolink=1' : site_url())
	));
	$form .= '</div>';
	$form .= '<p>';
	$form .= '<a href="' . site_url('wp-login.php?action=register') . '" target="_parent">' . __('Register') . '</a> | ';
	$form .= '<a href="' . site_url('wp-login.php?action=lostpassword') . '" target="_parent">' . __('Lost your Password?') . '</a>';
	$form .= '</p>';
	
	$form = str_replace('<form', '<form target="_parent"', $form);
	$form = str_replace('</form>', '<p class="right"><a id="ym_fb_login_button" href="#nowhere" onclick="jQuery(this).parents(\'form\').submit();">' . __('Login') . '</a></p></form>', $form);
	
	if ($echo) {
		echo $form;
		return;
	} else {
		return $form;
	}
}

function ym_fbook_wp_login_form_top() {
	$img = '<div class="center"><img src="' . YM_FBOOK_BASE_URL . 'images/ym_facebook.png" alt="YourMembers in Facebook" title="YourMembers in Facebook" style="width: 300px;"/></div>';
	
	$img = apply_filters('ym_fbook_login_image', $img);
	
	return $img;
}

function ym_fbook_profile($echo = FALSE) {
	global $facebook_settings, $facebook_client;
	$return = '';
	
	// messages
	
	$target = ym_fbook_oauth_go();

	// messages
	if ($facebook_settings->force_facebook_auth && !$_SESSION['facebook_user_id']) {
		ym_fbook_add_message(__('In order to continue you must login to the Facebook Application', 'ym_facebook'));
	}
	if ($facebook_settings->force_wordpress_auth && !$_SESSION['wordpress_user_id']) {
		ym_fbook_add_message(__('In order to continue you must login to WordPress', 'ym_facebook'));
	}
	if ($facebook_settings->require_link && !ym_fbook_is_linked()) {
		ym_fbook_add_message(__('In order to continue you must Link your accounts', 'ym_facebook'));
	}
	ym_fbook_messages();
	
	$return .= '
<table id="ym_fbook_status">
	<tr>
		<th>' . __('WordPress Status', 'ym_facebook') . '</th><td>';
		
		if (is_user_logged_in()) {
			global $current_user;
			get_currentuserinfo();
			$return .= __(sprintf('Logged in as <strong>%s</strong>', $current_user->user_login), 'ym_facebook') . ' </td><td>';
			
//			if ($_SESSION['in_facebook']) {
				$return .= '<form action="" method="post" id="ym_fb_loggedoutform"><input type="hidden" name="loggedout" value="1" /><a href="#nowhere" onclick="jQuery(this).parents(\'form\').submit()">' . __('Logout') . '</a></form>';
//			} else {
//				$return .= '<a href="' . wp_logout_url($_SERVER['REQUEST_URI']) . '">' . __('Logout') . '</a>';
//			}
		} else {
			$return .= __('Not Logged In', 'ym_facebook') . '</td><td><a href="';
			
			if ($_SESSION['in_facebook']) {
				$return .= '?login=1';
			} else {
				$return .= site_url('wp-login.php?redirect_to=' . urlencode($_SERVER['REQUEST_URI']));
			}
			
			$return .= '">' . __('Login') . '</a>';
			
			/*
			if ($facebook_settings->register_with_facebook) {
				$return .= ' | ';
				$return .= '<a href="?register=1">Register Account</a>';
			}
			*/
		}
		
		$return .= '</td>
		</tr><tr>
		<th>' . __('Facebook Status', 'ym_facebook') . '</th><td>';
		
		if ($facebook_client) {
			$return .= __(sprintf('Logged in as <strong>%s</strong>', $facebook_client->user_data->name), 'ym_facebook') . '</td><td>';
			$return .= '<a href="#nowhere" class="ym_fbook_logout">' . __('Logout') . '</a>';
		} else {
			$return .= __('Not Logged In', 'ym_facebook') . '</td><td><a href="';
			$return .= $target; 
			$return .= '" target="_parent">' . __('Login') . '</a>';
		}
		
		$return .= '</td>
		</tr><tr>
		<th>' . __('Link Status', 'ym_facebook') . '</th><td>';

		if (is_user_logged_in()) {
			$id = ym_fbook_is_linked();
			if ($id) {
				$return .= __('Linked', 'ym_facebook') . '</td><td>';
				if (!$facebook_settings->require_link) {
					$return .= '<form action="" method="post"><input type="hidden" name="dounlink" value="1" /><a href="#nowhere" onclick="jQuery(this).parents(\'form\').submit();">' . __('UnLink', 'ym_facebook') . '</a></form>';
				}
			} else {
				$return .= __('Not Linked', 'ym_facebook') . '</td><td><form action="" method="post"><input type="hidden" name="dolink" value="1" /><a href="#nowhere" onclick="jQuery(this).parents(\'form\').submit();">' . __('Link', 'ym_facebook') . '</a></form>';
			}
		} else {
			$return .= __('Login to Lnk', 'ym_facebook');
		}

		$return .= '
		</td>
	</tr>
</table>
';
	
	if ($echo) {
		echo $return;
	} else {
		return $return;
	}
}

/********************************************************************/
// template stuff
function ym_fbook_width() {
	return YM_FBOOK_WIDTH;
}
function ym_fbook_hide_nav() {
	return '
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'#ym_fbook_nav\').remove();
	});
</script>
<style type="text/css">
	#ym_fbook_nav { display: none; }
</style>
';
}

function ym_fbook_no_comments() {
	// stop comments
	define('YM_FBOOK_NO_COMMENTS', TRUE);
}
