<?php

function ymind_check_version() {
	$version_string = ymind_remote_request(YMIND_VERSION_CHECK_URL);
	echo $version_string;
}

function ymind_get_messages() {
    $messages = ymind_remote_request(YMIND_MESSAGE_CHECK_URL, false);
    echo $messages;
}

function ymind_remote_request($url, $error_message=true) {
	$string = '';
	
	if (ini_get('allow_url_fopen')) {
		if (!$string = @file_get_contents($url)) {
			if ($error_message) {
				$string = 'Could not connect to the server to make the request.';
			}
		}
	} else if (extension_loaded('curl')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$string = curl_exec($ch);
		curl_close($ch);
	} else if ($error_message) {
		$string = 'This feature will not function until either CURL or fopen to urls is turned on.';
	}

	return $string;
}

function ymind_admin_page_setup() {
	global $ymind_auth, $ymind_pages;
	
	$admin_page = YMIND_ADMIN_DIR . 'ymind_admin.php';
	$access = 'manage_options';
	$plugin_name = __('Your Minder','ymind');
	
	$message = false;
	if (isset($_POST['activate_plugin_yourminder']) && $_POST['registration_email'] != '') {
		$method = ymind_post('method', YMIND_PLUGIN_LICENSING);
		$other = false;

		if ($method == 'other') {
			$other = true;
			$method = YMIND_PLUGIN_LICENSING_OLD;
		}

		update_option('ymind_licensing_site_used', $method);
		update_option('ymind_licensing_activation_date', time());

		$connection_string = $method . YMIND_LICENCE_CHECK_URL . '&email=' . rawurlencode($_POST['registration_email']);
		$activate = ymind_remote_request($connection_string);
		if ($activate == '1') {
			$ymind_auth->ymind_set_key($_POST['registration_email']);
			$this_version = YMIND_PLUGIN_VERSION . ':' . strtoupper(YMIND_PLUGIN_PRODUCT) . ':' . YMIND_PLUGIN_VERSION_ID;
			update_option('ymind_current_version', $this_version);

			echo '<script>window.location=\'' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '\';</script>';
		} else {
			$ymind_auth->message = $activate;
		}
	}
	
	if ($ymind_auth->check_key()) {
		add_menu_page($plugin_name, $plugin_name, $access, $admin_page);

		foreach ($ymind_pages as $label=>$page) {
			add_submenu_page($admin_page, $label, $label, $access, $page);
		}
	} else {
		add_menu_page($plugin_name, $plugin_name, $access, $admin_page, 'ymind_key_check');
	}

} // end_of ym_admin_page()

function ymind_key_check() {
	global $ymind_auth;	
//	$ymind_auth->render_activation_form();
	
	echo '<div class="wrap" id="poststuff">';
	
	echo '<div id="message" class="error fade ymind_auth">';
	if ($ymind_auth->message) {
		echo '<div style="margin: 5px 0px; color:red; font-weight:bold;">' . $ymind_auth->message . '</div>';
	}

	echo '<p>' .  __('Your Minder will not function until a valid License Key has been entered. Please enter the email address you used to purchase the plugin in the box below to activate it. We also ask for the site that you purchased YMIND at so that the license check can be performed on the correct database.','ymind') . '</p>';

	echo '<p>' . __('If you don\'t have a key then please visit <a href=\'http://www.yourmembers.co.uk/\'>http://www.yourmembers.co.uk/</a> to purchase one.','ymind') . '</p>';

	echo '</div>';
	
	echo ymind_start_box('Your Minder Activation');

	echo 	'<div style="margin-bottom:10px;">
			<form method="POST">
				<table class="form-table">
					<tr>
						<th style="width: 230px; vertical-align: top; text-align: left;">Where did you purchase YMIND?</th>
						<td>
							<ul>
								<li><label><input type="radio" name="method" checked="checked" value="' . YMIND_PLUGIN_LICENSING . '" /> http://www.yourmembers.co.uk</label></li>
								<li><label><input type="radio" name="method" value="' . YMIND_PLUGIN_LICENSING_OLD . '" /> http://www.newmedias.co.uk</label></li>
								<li><label><input type="radio" name="method" value="other" /> Other</label> <input type="text" name="other_site" value="Where from?" /></li>
							</ul>
						</td>
					</tr>
					<tr>
						<th style="text-align: left;">Email / Activation Code</th>
						<td>
							<input name="registration_email" value="' . $default_text . '" style="width:300px;" type="text" />
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: right;">
							<input name="activate_plugin_yourminder" value="Activate" type="submit" class="button"/>
						</td>
					</tr>
				</table>
			</form>
		</div>';

	echo ymind_end_box();
	echo '</div>';
}

function ymind_admin_update() {
	$return = false;

	if (isset($_GET['unlock'])) {
		update_usermeta($_GET['unlock'], 'ymind_locked_out', 0);
		delete_usermeta($_GET['unlock'], 'ymind_activation_key');

		$msg = ''.__('User has now been unlocked', 'ymind').'.';
		ymind_display_message($msg);
	}

	if (isset($_POST['submit'])) {

		$error = array();

		$numerical_fields = array(
		'timeout_logins'=>''.__('Lockout Login Count', 'ymind').''
		, 'timeout_minutes'=>''.__('Lockout Login Minutes', 'ymind').''
		, 'lockout_minutes'=>''.__('Lockout Minutes', 'ymind').''
		);

		$textual_fields = array(
		'new_url'=>''.__('Redirect URL', 'ymind').''
		, 'mail_subject'=>''.__('Email Subject', 'ymind').''
		, 'mail_message'=>''.__('Email Message', 'ymind').''
		, 'login_error'=>''.__('Login Error', 'ymind').''
		, 'activate_redirect'=>''.__('Activation Redirect', 'ymind').''
		);

		if ($_POST['email_offender'] == 0 && $_POST['timeout_minutes'] > $_POST['lockout_minutes']) {
			$error[] = ''.__('Locked out minutes must be greater than the number of minutes it takes to lock someone out.', 'ymind').'';
		}

		foreach ($numerical_fields as $key=>$label) {
			if (!is_numeric($_POST[$key])) {
				$error[] = ''.__('Expecting a number for', 'ymind').' ' . $label;
			}
		}

		foreach ($textual_fields as $key=>$label) {
			if (is_numeric($_POST[$key])) {
				$error[] = ''.__('Expecting a string for', 'ymind').' ' . $label;
			}
		}

		if (!count($error)) {
			update_option('ymind_redirect_url', $_POST['new_url']);
			update_option('ymind_email_offender',$_POST['email_offender']);
			update_option('ymind_mail_subject',$_POST['mail_subject']);
			update_option('ymind_mail_message',$_POST['mail_message']);
			update_option('ymind_lockout_minutes',$_POST['lockout_minutes']);
			update_option('ymind_timeout_minutes',$_POST['timeout_minutes']);
			update_option('ymind_timeout_logins',$_POST['timeout_logins']);
			update_option('ymind_lockout_option',$_POST['lockout_option']);
			update_option('ymind_locked_out_error',$_POST['login_error']);
			update_option('ymind_activate_redirect',$_POST['activate_redirect']);

			$msg = ''.__('Options have been successfully updated.', 'ymind').'';
			ymind_display_feedback($msg);
		} else {
			$msg = '<ul style="margin:5px; padding-left: 10px;">';
			foreach ($error as $err) {
				$msg .= '<li>' . $err . '</li>';
			}
			$msg .= '</ul>';

			ymind_display_error($msg);
		}
	}
}

function ymind_block_update($id, $ip) {
	global $wpdb;


	$sql = 'UPDATE `' . $wpdb->prefix . 'ymind_block_list` 
			SET `ip` = "'.$ip.'"
			WHERE `id` = "'.$id.'"';

	$r = $wpdb->query($sql);
	return $r;
}

function ymind_block_insert($ip) {
	global $wpdb;

	$sql = 'INSERT INTO `' . $wpdb->prefix . 'ymind_block_list` 
			SET `ip` = "'.$ip.'"';

	$r = $wpdb->query($sql);
	return $r;
}

function ymind_get_block($id=false) {
	global $wpdb;
	
	$r = new stdClass();
	$r->id = $r->ip = false;

	if ($id) {
		$sql = 'SELECT `ip`,`id`
				FROM `' . $wpdb->prefix . 'ymind_block_list` 
				WHERE `id` = "'.$id.'"';
		$r = $wpdb->get_row($sql);
	}
	
	return $r;
}

function ymind_block_delete($id) {
	global $wpdb;

	$sql = 'DELETE FROM `' . $wpdb->prefix . 'ymind_block_list` 
			WHERE `id` = "'.$id.'"';

	$r = $wpdb->query($sql);
	return $r;
}

function ymind_get_all_blocks() {
	global $wpdb;

	$sql = 'SELECT `ip`,`id`
			FROM `' . $wpdb->prefix . 'ymind_block_list` 
			ORDER BY `ip`';

	$r = $wpdb->get_results($sql);
	return $r;
}

?>