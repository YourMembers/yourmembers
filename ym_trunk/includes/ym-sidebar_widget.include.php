<?php

/*
* $Id: ym-sidebar_widget.include.php 2402 2012-11-02 14:19:01Z tnash $
* $Revision: 2402 $
* $Date: 2012-11-02 14:19:01 +0000 (Fri, 02 Nov 2012) $
*/

//Defaults for sidebar widgets
$ym_default_register_intro = '';
$ym_default_inactive_intro = __('<p>You can subscribe to this blog using the buttons below.</p><p>You will be taken to a payment gateway and then returned to the site as a subscribed member.</p><p style="font-weight:bold;">Choose From:</p>', 'ym');
$ym_default_active_intro = __('<p>You are a subscribed member.</p><div>Subscription Level: [account_type]</div><div style="margin-bottom: 5px;">Expiry Date: [expiry_date]</div>', 'ym');
$ym_default_logged_out_intro = __('<p>You need to be logged in to be able to subscribe to this blog or purchase any of its posts.</p><p>Use the link below to login or register.</p>', 'ym');
$ym_default_widget_template = __('<p>Welcome [display_name]</p><ul><li>[membership_details_link]</li><li>[logout_link]</li></ul>', 'ym');
//End defaults for sidebar widgets

function ym_widget_init() {
	$control_ops = array('width'=>400, 'height'=>350);
	wp_register_sidebar_widget('ym-login-widget', __('YourMember Login','ym'), 'ym_widget');
	wp_register_widget_control('ym-login-widget', __('YourMember Login','ym'), 'ym_widget_control', $control_ops);
}

function ym_register_sidebar_init() {
	$control_ops = array('width'=>400, 'height'=>350);
	wp_register_sidebar_widget('ym_register_sidebar_widget', __('YourMember Register','ym'), 'ym_register_sidebar_widget');
	wp_register_widget_control('ym_register_sidebar_widget', __('YourMember Register','ym'), 'ym_register_sidebar_widget_control', $control_ops);
}

function ym_sidebar_init() {
	$control_ops = array('width'=>400, 'height'=>350);
	wp_register_sidebar_widget('ym_sidebar_widget', __('YourMember Status','ym'), 'ym_sidebar_widget');
	wp_register_widget_control('ym_sidebar_widget', __('YourMember Status','ym'), 'ym_sidebar_widget_control', $control_ops);
}

function ym_widget($args = array()) {
	global $user_ID, $current_user, $ym_default_widget_template, $ym_sys;

	$before_widget = $after_widget = $before_title = $after_title = '';

	extract($args);

	$options = get_option('ym_login_widget');

	$widget_title = (isset($options['title_text']) ? $options['title_text']:__('YM Membership Details','ym'));
	$widget_title_logged_out = (isset($options['title_text_logged_out']) ? $options['title_text_logged_out']:__('Login','ym'));
	$membership_details_text = (isset($options['membership_details_text']) ? $options['membership_details_text']:__('Membership Details','ym'));
	$membership_content_text = (isset($options['membership_content_text']) ? $options['membership_content_text']:__('Members Content','ym'));
	$logout_text = (isset($options['logout_text']) ? $options['logout_text']:__('Logout','ym'));
	$register_text = (isset($options['register_text']) ? $options['register_text']:__('Register','ym'));
	$lostpassword_text = (isset($options['lostpassword_text']) ? $options['lostpassword_text']:__('Lost your Password?','ym'));
	$logged_out_intro = (isset($options['intro_logged_out']) ? stripslashes($options['intro_logged_out']):'');

	if ($user_ID) {
		if ($options['template']) {
		echo $before_widget;
		
		if (trim($widget_title)) {
			echo $before_title . $widget_title . $after_title;
		}
		
		$file = ym_superuser() ? 'users' : 'profile';
		$logout_url = wp_logout_url('/');

		$ym_profile = isset($ym_sys->membership_details_redirect_url) && $ym_sys->membership_details_redirect_url ? $ym_sys->membership_details_redirect_url : site_url('wp-admin/' . $file . '.php?page=ym-profile');

		$widget_template = (isset($options['template']) ? $options['template'] : $ym_default_widget_template);
		$widget_template = str_replace('[display_name]', $current_user->display_name, $widget_template);
		$widget_template = str_replace('[account_type]', ym_get_user_account_type(), $widget_template);
		$widget_template = str_replace('[membership_content_url]', site_url('wp-admin/' . $file . '.php?page=ym-membership_content'), $widget_template);
		$widget_template = str_replace('[membership_content_link]', '<a href="' . site_url('wp-admin/' . $file . '.php?page=ym-membership_content') . '">' . $membership_content_text . '</a>', $widget_template);		
		$widget_template = str_replace('[membership_details_url]', site_url('wp-admin/' . $file . '.php?page=ym-profile'), $widget_template);
		$widget_template = str_replace('[membership_details_link]', '<a href="' . $ym_profile . '">' . $membership_details_text . '</a>', $widget_template);
		$widget_template = str_replace('[logout_url]', $logout_url, $widget_template);
		$widget_template = str_replace('[logout_link]', '<a href="' . $logout_url . '">' . $logout_text . '</a>', $widget_template);

		if ($widget_template) {
			$widget_template = '<div class="ym_login_widget_body">' . $widget_template . '</div>';
		}

		echo $widget_template;

		echo $after_widget;
		}
	} else {
		echo $before_widget;
		
		if (trim($widget_title)) {
			echo $before_title . $widget_title_logged_out . $after_title;
		}		

		echo $logged_out_intro;

		echo '<div class="ym_login_widget_body">';
		echo ym_login_form($register_text, $lostpassword_text);
		echo '</div>';

		echo $after_widget;
	}
}

function ym_widget_control() {
	global $ym_default_widget_template;

	$options = $newoptions = get_option('ym_login_widget');

	if (!is_array($options)) {
		$options = $newoptions = array();
	}

	if (isset($_POST['ym-login-widget-submit'])) {
		$newoptions['title_text'] = stripslashes($_POST['ym-login-widget-widget-title']);
		$newoptions['title_text_logged_out'] = stripslashes($_POST['ym-login-widget-widget-title-logged-out']);
		$newoptions['membership_content_text'] = stripslashes($_POST['ym-login-widget-membership-content-text']);
		$newoptions['membership_details_text'] = stripslashes($_POST['ym-login-widget-membership-details-text']);
		$newoptions['logout_text'] = stripslashes($_POST['ym-login-widget-logout-text']);
		$newoptions['register_text'] = stripslashes($_POST['ym-login-widget-register-text']);
		$newoptions['intro_logged_out'] = stripslashes($_POST['ym-login-widget-logged-out-intro']);
		$newoptions['hide_logged_out'] = stripslashes($_POST['ym_sidebar_widget_hide_logged_out']);
		$newoptions['lostpassword_text'] = stripslashes($_POST['ym-login-widget-lostpassword-text']);
		$newoptions['template'] = stripslashes($_POST['ym-login-widget-template']);
	}

	if ($options != $newoptions) {
		$options = $newoptions;
		update_option('ym_login_widget', $options);
	}

	$widget_title = (isset($options['title_text']) ? stripslashes($options['title_text']):__('YM Membership Details','ym'));
	$widget_title_logged_out = (isset($options['title_text_logged_out']) ? stripslashes($options['title_text_logged_out']):__('Login','ym'));
	$membership_content_text = (isset($options['membership_content_text']) ? stripslashes($options['membership_content_text']):__('Members Content','ym'));
	$membership_details_text = (isset($options['membership_details_text']) ? stripslashes($options['membership_details_text']):__('Membership Details','ym'));
	$logout_text = (isset($options['logout_text']) ? stripslashes($options['logout_text']):__('Logout','ym'));
	$register_text = (isset($options['register_text']) ? stripslashes($options['register_text']):__('Register','ym'));
	$lostpassword_text = (isset($options['lostpassword_text']) ? stripslashes($options['lostpassword_text']):__('Lost your Password?','ym'));
	$logged_out_intro = (isset($options['intro_logged_out']) ? stripslashes($options['intro_logged_out']):'');
	$widget_template = (isset($options['template']) ? stripslashes($options['template']):$ym_default_widget_template);

	echo '<p>' . __('When logged out the user will see a login form. Removing the text from the "Register link text" or "Lost password link text" will subsequently remove the links they produce.', 'ym') . '</p>
	<input type="hidden" name="ym-login-widget-submit" id="ym-login-widget-submit" value="1" />
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-widget-title"><strong>' . __('Widget Title (Logged in):','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $widget_title . '" id="ym-login-widget-widget-title" name="ym-login-widget-widget-title" /></label>
	</div>
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-widget-title-logged-out"><strong>' . __('Widget Title (Logged out):','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $widget_title_logged_out . '" id="ym-login-widget-widget-title-logged-out" name="ym-login-widget-widget-title-logged-out" /></label>
	</div>
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-membership-content-text"><strong>' . __('Members Content link text:','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $membership_content_text . '" id="ym-login-widget-membership-content-text" name="ym-login-widget-membership-content-text" /></label>
	</div>	
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-membership-details-text"><strong>' . __('Membership Details link text:','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $membership_details_text . '" id="ym-login-widget-membership-details-text" name="ym-login-widget-membership-details-text" /></label>
	</div>
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-logout-text"><strong>' . __('Logout text:','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $logout_text . '" id="ym-login-widget-logout-text" name="ym-login-widget-logout-text" />
		</label>
	</div>
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-register-text"><strong>' . __('Register link text:','ym') . '</strong></div>
		<input style="width: 300px;" value="' . $register_text . '" id="ym-login-widget-register-text" name="ym-login-widget-register-text" />
		</label>
	</div>
	<div style="margin-bottom: 5px;">
		<div><label for="ym-login-widget-lostpassword-text"><strong>' . __('Lost password link text:','ym') . '</strong></div>
		<input style="width: 300px;" value="' .$lostpassword_text . '" id="ym-login-widget-lostpassword-text"	name="ym-login-widget-lostpassword-text" /></label>
	</div>
	<div style="margin-bottom: 5px;">				
		<label for="ym-login-widget-logged-out-intro">
			<div><strong>' . __('Logged Out Introduction','ym') . '</strong></div>
			<textarea rows="2" cols="50" id="ym-login-widget-logged-out-intro" name="ym-login-widget-logged-out-intro">' . esc_html($logged_out_intro) . '</textarea>
		</label>
	</div>
	<div style="margin-bottom: 5px;">				
		<label for="ym-login-widget-template">
			<div><strong>' . __('Logged In Template','ym') . '</strong> - Use the following hooks: [display_name], [account_type], [membership_content_url], [membership_content_link],[membership_details_url], [membership_details_link], [logout_url], [logout_link]</div>
			<textarea rows="6" cols="50" id="ym-login-widget-template" name="ym-login-widget-template">' . $widget_template . '</textarea>
		</label>
	</div>
	';
}

function ym_register_sidebar_widget_control() {
	global $ym_default_register_intro;

	$options = $newoptions = get_option('ym_register_sidebar_widget');
	if (!is_array($options)) {
		$options = $newoptions = array();
	}

	if (isset($_POST['ym_register_sidebar_widget_submit'])) {
		$newoptions['ym_register_sidebar_widget_title'] = $_POST['ym_register_sidebar_widget_title'];
		$newoptions['ym_register_sidebar_widget_intro'] = $_POST['ym_register_sidebar_widget_intro'];
		$newoptions['ym_register_sidebar_widget_use_custom_fields'] = isset($_POST['ym_register_sidebar_widget_use_custom_fields']);
	}

	if ($options != $newoptions) {
		$options = $newoptions;
		update_option('ym_register_sidebar_widget', $options);
	}

	$title = (isset($options['ym_register_sidebar_widget_title']) ? $options['ym_register_sidebar_widget_title'] : '');
	$intro = trim(isset($options['ym_register_sidebar_widget_intro']) ? $options['ym_register_sidebar_widget_intro'] : $ym_default_register_intro);
	$custom_fields = trim(isset($options['ym_register_sidebar_widget_use_custom_fields']) ? $options['ym_register_sidebar_widget_use_custom_fields'] : false);

	echo '	<input type="hidden" name="ym_register_sidebar_widget_submit" id="ym_register_sidebar_widget_submit" value="1" />
			<p>
				<div style="margin-bottom: 5px;">
				<label for="ym_register_sidebar_widget_title">
					<div><strong>' . __('Widget Title','ym') . '</strong></div>
					<input style="width: 300px;" type="text" value="' . $title . '" id="ym_register_sidebar_widget_title" name="ym_register_sidebar_widget_title" />
				</label>
				</div>
				<div style="margin-bottom: 5px;">
				<label for="ym_register_sidebar_widget_use_custom_fields">
				<strong>' . __('Use Custom Fields in form?','ym') . '</strong>
				<input style="width: 30px;" type="checkbox" ' . ($custom_fields ? 'checked="checked"':'') . ' value="1" id="ym_register_sidebar_widget_use_custom_fields" name="ym_register_sidebar_widget_use_custom_fields" />
				</label>
				</div>
				<div style="margin-bottom: 5px;">
				<label for="ym_register_sidebar_widget_active_intro">
					<div><strong>' . __('Introduction','ym') . '</strong></div>
					<textarea rows="6" cols="50" id="ym_register_sidebar_widget_intro" name="ym_register_sidebar_widget_intro">' . $intro . '</textarea>
				</label>
				</div>
			</p>';

}

function ym_sidebar_widget_control() {
	global $ym_default_active_intro, $ym_default_inactive_intro, $ym_default_logged_out_intro;

	$options = $newoptions = get_option('ym_sidebar_widget');
	if (!is_array($options)) {
		$options = $newoptions = array();
	}

	if (isset($_POST['ym_sidebar_widget_submit'])) {
		$newoptions['ym_sidebar_widget_title'] = stripslashes($_POST['ym_sidebar_widget_title']);
		$newoptions['ym_sidebar_widget_active_intro'] = stripslashes($_POST['ym_sidebar_widget_active_intro']);
		$newoptions['ym_sidebar_widget_inactive_intro'] = stripslashes($_POST['ym_sidebar_widget_inactive_intro']);
		$newoptions['ym_sidebar_widget_logged_out_intro'] = stripslashes($_POST['ym_sidebar_widget_logged_out_intro']);
		$newoptions['ym_sidebar_widget_hide_logged_out'] = stripslashes($_POST['ym_sidebar_widget_hide_logged_out']);
	}

	if ($options != $newoptions) {
		$options = $newoptions;
		update_option('ym_sidebar_widget', $options);
	}

	$title = (isset($options['ym_sidebar_widget_title']) ? stripslashes($options['ym_sidebar_widget_title']):'');
	$active_intro = trim(isset($options['ym_sidebar_widget_active_intro']) ? stripslashes($options['ym_sidebar_widget_active_intro']):$ym_default_active_intro);
	$inactive_intro = trim(isset($options['ym_sidebar_widget_inactive_intro']) ? stripslashes($options['ym_sidebar_widget_inactive_intro']):$ym_default_inactive_intro);
	$logged_out_intro = trim(isset($options['ym_sidebar_widget_logged_out_intro']) ? stripslashes($options['ym_sidebar_widget_logged_out_intro']):$ym_default_logged_out_intro);
	$hide_logged_out = trim(isset($options['ym_sidebar_widget_hide_logged_out']) ? stripslashes($options['ym_sidebar_widget_hide_logged_out']):false);

	echo '	<input type="hidden" name="ym_sidebar_widget_submit" id="ym_sidebar_widget_submit" value="1" />
			<p>
				<div style="margin-bottom: 5px;">
				<label for="ym_sidebar_widget_title">
					<div><strong>' . __('Widget Title','ym') . '</strong></div>
					<input style="width: 300px;" type="text" value="' . $title . '" id="ym_sidebar_widget_title" name="ym_sidebar_widget_title" />
				</label>
				</div>
				<div style="margin-bottom: 5px;">
				<label for="ym_sidebar_widget_active_intro">
					<div><strong>' . __('User Active Introduction','ym') . '</strong> - Use [username],[account_type], [expiry_date], [last_pay_date] [logout_link] and [logout_url]</div>
					<textarea rows="6" cols="50" id="ym_sidebar_widget_active_intro" name="ym_sidebar_widget_active_intro">' . $active_intro . '</textarea>
				</label>
				</div>
				<div style="margin-bottom: 5px;">				
				<label for="ym_sidebar_widget_inactive_intro">
					<div><strong>' . __('User Inactive Introduction','ym') . '</strong> - Use [username],[logout_link] or [logout_url]</div>
					<textarea rows="6" cols="50" id="ym_sidebar_widget_inactive_intro" name="ym_sidebar_widget_inactive_intro">' . $inactive_intro . '</textarea>
				</label>
				</div>
				<div style="margin-bottom: 5px;">				
				<label for="ym_sidebar_widget_logged_out_intro">
					<div><strong>' . __('User Logged Out Introduction','ym') . '</strong></div>
					<textarea rows="6" cols="50" id="ym_sidebar_widget_logged_out_intro" name="ym_sidebar_widget_logged_out_intro">' . $logged_out_intro . '</textarea>
				</label>
				</div>
				<div style="margin-bottom: 5px;">				
				<label for="ym_sidebar_widget_hide_logged_out">
					<div><strong>' . __('Hide widget when user logged out?','ym') . '</strong>
					<input type="checkbox" id="ym_sidebar_widget_hide_logged_out" name="ym_sidebar_widget_hide_logged_out" value="1" ' . ($hide_logged_out ? 'checked="checked"':'') . ' />
				</div></label>
				</div>				
			</p>';

}

function ym_sidebar_widget($args) {
	global $wpdb, $user_ID, $current_user, $ym_default_active_intro, $ym_default_inactive_intro, $ym_default_logged_out_intro, $ym_user;
	
	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	$options = get_option('ym_sidebar_widget');

	$title = (isset($options['ym_sidebar_widget_title']) ? $options['ym_sidebar_widget_title']:__('Your Members','ym'));
	$logged_out_intro = (isset($options['ym_sidebar_widget_logged_out_intro']) ? stripslashes($options['ym_sidebar_widget_logged_out_intro']):$ym_default_logged_out_intro);
	$hide_logged_out = (isset($options['ym_sidebar_widget_hide_logged_out']) ? stripslashes($options['ym_sidebar_widget_hide_logged_out']):false);

	extract($args);

	if ($user_ID) {
		echo $before_widget;

		if (trim($title)) {
			echo $before_title . $title . $after_title;
		}
	
		$user_obj = 'ym_user';
		$uat = $ym_user->account_type;
		
		$logout_url = wp_logout_url(ym_wp_logout(TRUE));// grab url to use

		$user_status = $ym_user->status;
		$expiry = $ym_user->expire_date;
		$username = $ym_user->data->user_login;
		

		if (strtolower($user_status) != 'active') {

			$inactive_intro = (isset($options['ym_sidebar_widget_inactive_intro']) ? $options['ym_sidebar_widget_inactive_intro']:$ym_default_inactive_intro);
			$inactive_intro = str_replace('[logout_url]', $logout_url, $inactive_intro);
			$inactive_intro = str_replace('[username]', $username, $inactive_intro);
			$inactive_intro = str_replace('[logout_link]', '<a href="' . $logout_url . '">' . __('Logout', 'ym') . '</a>', $inactive_intro);
			
			echo $inactive_intro;
			ym_upgrade_links();

		} else {
			if ($expiry) {
				
				$expiry = date(get_option('date_format'), $expiry);
			} else {
				$expiry = __('None', 'ym');
			}

			$active_intro = $ym_default_active_intro;
			if (isset($options['ym_sidebar_widget_active_intro'])) {
				$active_intro = $options['ym_sidebar_widget_active_intro'];
			}
			$last_pay_date = date(get_option('date_format'), $ym_user->last_pay_date);
			$active_intro = str_replace('[username]', $username, $active_intro);	
			$active_intro = str_replace('[account_type]', $uat, $active_intro);
			$active_intro = str_replace('[expiry_date]', $expiry, $active_intro);
			$active_intro = str_replace('[last_pay_date]', $last_pay_date, $active_intro);
			$active_intro = str_replace('[logout_url]', $logout_url, $active_intro);
			$active_intro = str_replace('[logout_link]', '<a href="' . $logout_url . '">' . __('Logout', 'ym') . '</a>', $active_intro);

			echo $active_intro;
			ym_render_my_purchased_posts($user_ID);
		}
		
		echo $after_widget;
	} else {
		if (!$hide_logged_out) {
			echo $before_widget;
			
			if (trim($title)) {
				echo $before_title . $title . $after_title;
			}
		
			echo $logged_out_intro;
			echo ym_get_login_register_links();
			echo $after_widget;
		}
	}
}

function ym_register_sidebar_widget($args=false, $pack_id=false, $hide_custom_fields=false, $hide_further_pages=false, $autologin=false) {
	global $wpdb, $user_ID, $current_user;
	$html = '';

	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	if ($args) {
		extract($args);
		$options = get_option('ym_register_sidebar_widget');
	} else {
		$args = array();
	}

	$title = (isset($options['ym_register_sidebar_widget_title']) ? $options['ym_register_sidebar_widget_title']:__('Your Members - Register','ym'));
	$intro = (isset($options['ym_register_sidebar_widget_intro']) ? $options['ym_register_sidebar_widget_intro']:'');
	$custom_fields = (isset($options['ym_register_sidebar_widget_use_custom_fields']) ? $options['ym_register_sidebar_widget_use_custom_fields']:true);

	if (!$user_ID) {
		if ($args) {
			$html .= $before_widget;
		} else {
			$html .= '<div id="ym_page_register_form">';
		}
		
		if (trim($title) && $args) {
			$html .= $before_title . $title . $after_title;
		}
		
		if ($intro) {
			$html .= $intro;
		}
		
		$user_email = ym_request('user_email');
		
//register_new_user($userlogin, useremail);
		global $errors;
		if ( is_wp_error($errors) ) {
login_header(__('Registration Form'), '<p class="message register">' . __('Register For This Site') . '</p>', $errors);
		}

		$html .= '<form ' . (!$args ? 'class="ym_register_form"':'') . ' name="registerform" id="registerform" action="' . site_url('wp-login.php?action=register', 'login_post') . '" method="post" enctype="multipart/form-data">';
//		$html .= '<form ' . (!$args ? 'class="ym_register_form"':'') . ' name="registerform" id="registerform" action="" method="post">
//		<input type="hidden" name="ym_register_form_posting" value="1" />';
		
		if (!ym_post('user_login')) {
		$html .= '<div class="ym_register_form_row">
				<label class="ym_label">' . __('Username') . '</label>
				<p>
					<input type="text" name="user_login" id="user_login" class="ym_reg_input input" value="" size="20" />
				</p>
				<div class="ym_clear">&nbsp;</div>
			</div>';
		} else {
			$html .= '<input type="hidden" name="user_login" value="' . esc_attr(stripslashes($user_login)) . '" />';
		}
		
		if (!ym_post('user_email')) {	
		$html .= '<div class="ym_register_form_row">
				<label class="ym_label">' . __('Email') . '</label>
				<p>				
					<input type="text" name="user_email" id="user_email" class="ym_reg_input input" value="' . esc_attr(stripslashes($user_email)) . '" size="25" />
				</p>
				<div class="ym_clear">&nbsp;</div>
			</div>';
		} else {
			$html .= '<input type="hidden" name="user_email" value="' . esc_attr(stripslashes($user_email)) . '" />';
			
		}
		if ($custom_fields) {
			ob_start();
			$html .= do_action('register_form', false, 1, $pack_id, $hide_custom_fields, $hide_further_pages, $autologin);
			$html .= ob_get_clean();
		}
		
		$html .= '<p id="reg_passmail">' . __('A password will be e-mailed to you.') . '</p>
			<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" value="' . __('Register') . '" /></p>
		</form>';
		
		if ($args) {
			$html .= $after_widget;
		} else {
			$html .= '</div>';
		}
	} else {
		$html .= '<div class="ym_message" id="ym_page_register_form_already_registered"><div class="ym_message_liner">' .  __('You are already registered for the site and can\'t do so again.', 'ym') . '</div></div>';
	}

	if ($args) {
		echo $html;
	} else {
		return $html;
	}
	
}

add_action('widgets_init', create_function('', 'return register_widget("ym_upgrade");'));

class ym_upgrade extends WP_Widget {

	function ym_upgrade() {
		$widget_ops = array('classname' => 'widget_ym_upgrade', 'description' => __('Shows a list of packs available to the user.'));
		$control_ops = array();
		$this->WP_Widget('ym_upgrade', __('YM Upgrade Options'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		get_currentuserinfo();
		global $current_user;
		
		extract($args);
		
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$text = trim(apply_filters( 'widget_text', $instance['text'], $instance ));
		$pack_id = $instance['pack_id'];
		$hide_pack_string = $instance['hide_pack_string'];

		if ($current_user->ID) {
			echo $before_widget;
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
		
			if ($text) {
				echo '<div class="ym_upgrade_widget_intro">' . $text . '</div>';
			}
			
			echo '<div class="ym_upgrade_widget">';
			ym_upgrade_links('sidebar', $pack_id, $hide_pack_string);
			echo '</div>';
			
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		global $ym_formgen;
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'pack_id'=>'', 'hide_pack_string'=>'') );
		$title = strip_tags($instance['title']);
		$text = format_to_edit($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Intro:'); ?></label>
		<textarea class="widefat" rows="3" cols="10" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><label for="<?php echo $this->get_field_id('pack_id'); ?>"><?php _e('Specific Pack:'); ?></label>
		<?php echo ym_get_pack_dropdown($this->get_field_name('pack_id'), $instance['pack_id']); ?>
		</p>
		
		<p><label for="<?php echo $this->get_field_id('hide_pack_string'); ?>"><?php _e('Hide Pack Name (for upsell pages):'); ?></label>
		<?php echo $ym_formgen->render_yesno_radio($this->get_field_name('hide_pack_string'), $instance['hide_pack_string']); ?>
		</p>
		
<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("ym_purchased_posts");'));

class ym_purchased_posts extends WP_Widget {

	function ym_purchased_posts() {
		$widget_ops = array('classname' => 'widget_ym_ppp', 'description' => __('Shows a list of a users YM Purchased Posts'));
		$control_ops = array();
		$this->WP_Widget('ym_purchased_posts', __('YM Purchased Posts'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		get_currentuserinfo();
		global $current_user;
		
		extract($args);
		
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$text = trim(apply_filters( 'widget_text', $instance['text'], $instance ));

		if ($current_user->ID) {
			echo $before_widget;
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
		
			if ($text) {
				echo '<p>' . $text . '</p>';
			}
			
			echo '<div class="ym_ppp_widget">';
			ym_render_my_purchased_posts($current_user->ID, true, false, @$instance['show_expiry']);
			echo '</div>';
			
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '') );
		$title = strip_tags($instance['title']);
		$text = format_to_edit($instance['text']);
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Intro:'); ?></label>
		<textarea class="widefat" rows="3" cols="10" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('show_expiry'); ?>" name="<?php echo $this->get_field_name('show_expiry'); ?>" type="checkbox" <?php echo (isset($instance['show_expiry']) ? 'checked="checked"':''); ?> />&nbsp;<label for="<?php echo $this->get_field_id('show_expiry'); ?>"><?php _e('Show Expiries?'); ?></label></p>
<?php

	}
}

add_action('widgets_init', create_function('', 'return register_widget("ym_text");'));

class ym_text extends WP_Widget {

	function ym_text() {
		$widget_ops = array('classname' => 'widget_ym_text', 'description' => __('Arbitrary text or HTML protected by YM package type. Now with PHP! Just use &lt;?php and ?&gt;'));
		$control_ops = array('width' => 400, 'height' => 350);
		$this->WP_Widget('ym_text', __('YM Text'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$text = apply_filters( 'widget_text', $instance['text'], $instance );
		
		$available_to = explode(';', $instance['account_types']);
		$account_type = strtolower(ym_get_user_account_type());
	
		$access = false;
		foreach ($available_to as $available) {
			if ($account_type == strtolower($available)) {
				$access = true;
				break;
			}
		}
	
		//if (in_array($account_type, $available_to)) { //-had to remove for above because of case sensitivity
		if ($access) {
			echo $before_widget;
			if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
			
			ob_start();
			eval('?>'.$text);
			$text = ob_get_contents();
			ob_end_clean();
			
			$text = str_replace('[account_type]', ym_get_user_account_type(), $text);
			?>
			
			<div class="ym_textwidget"><?php echo ($instance['filter'] ? wpautop($text):$text); ?></div>			
			<?php
			echo $after_widget;
		}
	}

	function update( $new_instance, $old_instance ) {
		global $ym_package_types;
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		
		if ( current_user_can('unfiltered_html') )
			$instance['text'] =  $new_instance['text'];
		else
			$instance['text'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['text']) ) ); // wp_filter_post_kses() expects slashed
		
		$instance['filter'] = isset($new_instance['filter']);
		
		$account_types = array();
		foreach ($ym_package_types->types as $type) {
			$prefix_type = 'ym_ac_' . str_replace(' ', '_', $type);
			if (isset($new_instance[$prefix_type])) {
				$account_types[] = $type;
			}
		}
		$instance['account_types'] = implode(';', $account_types);
		
		return $instance;
	}

	function form( $instance ) {
		global $ym_package_types;
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '', 'account_types'=>'' ) );
		$title = strip_tags($instance['title']);
		$selected = strip_tags($instance['account_types']);
		$text = format_to_edit($instance['text']);
?>
		<p>This widget will only show to those package types that are selected below. Enter your text below using PHP code if necessary within &lt;?php ?&gt; tags.</p>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

		<textarea class="widefat" rows="16" cols="20" id="<?php echo $this->get_field_id('text'); ?>" name="<?php echo $this->get_field_name('text'); ?>"><?php echo $text; ?></textarea>

		<p><input id="<?php echo $this->get_field_id('filter'); ?>" name="<?php echo $this->get_field_name('filter'); ?>" type="checkbox" <?php checked(isset($instance['filter']) ? $instance['filter'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Automatically add paragraphs'); ?></label></p>
<?php

		echo '<p>Available to:<br />';
	
		$checked = array();
		if (empty($selected) || strpos($selected, ';') === false) {
			$checked[] = $selected;
		} else {
			$checked = explode(';', $selected);
		}
	
		foreach ($ym_package_types->types as $type) {
			$label = $type;
			$c = (in_array($type, $checked) ? 'checked="checked"':'');
			$type = 'ym_ac_' . str_replace(' ', '_', $type);
	
			echo '<input type="checkbox" id="' . $this->get_field_id($type) . '" class="checkbox" name="' . $this->get_field_name($type) . '" value="' . $type . '" ' . $c . ' />&nbsp;';
			echo '<label style="font-style:italic;" for="' . __($label) . '">' . __($label) . '</label><br />';
		}
	
		echo '</p>';

	}
}
add_action('widgets_init', create_function('', 'return register_widget("ym_register_flow");'));

class ym_register_flow extends WP_Widget {

	function ym_register_flow() {
		$widget_ops = array('classname' => 'widget_ym_rf', 'description' => __('Shows Custom Registration Flow'));
		$control_ops = array();
		$this->WP_Widget('ym_register_flow', __('YM Custom Registration Flow'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		get_currentuserinfo();
		global $current_user;
		
		extract($args);

		echo ym_register_flow($instance['flow_id'], $instance['pack_id'],TRUE);
	}

	function update( $new_instance, $old_instance ) {
		return $new_instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '') );
		$flow_id = strip_tags($instance['flow_id']);
?>
		<p><label for="<?php echo $this->get_field_id('pack_id'); ?>"><?php _e('Specific Pack:'); ?></label>
		<?php echo ym_get_pack_dropdown($this->get_field_name('pack_id'), $instance['pack_id'],true,false); ?>
		</p>
		<p><label for="<?php echo $this->get_field_id('flow_id'); ?>"><?php _e('Flow:'); ?></label>
			<?php echo ym_get_flows_dropdown($this->get_field_name('flow_id'), $instance['flow_id'],false); ?>
		</p>

<?php

	}
}
