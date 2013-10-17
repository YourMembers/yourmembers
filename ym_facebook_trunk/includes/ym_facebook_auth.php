<?php

$scopes = array(
	'permission_likewall'			=> 'user_likes',
	'permission_email'				=> 'email',
	'permission_offline_access'		=> 'offline_access',
	'permission_publish_actions'	=> 'publish_actions',
);

$location = '';
function ym_fbook_init() {
	global $facebook_settings, $location;
//	session_start();// wp does this
	
	if (ym_request('destroy')) {
		session_destroy();
		header('Location: ' . site_url());
		exit;
	}
	
	$location = get_permalink() ? get_permalink() : 'http' . (is_ssl() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	
	/********************************************/
	/* AWAKE?
	/********************************************/
	if (!ym_facebook_settings()) {
		// no settings
		// iframe break out if iframe present
		add_action('wp_head', 'ym_fbook_init_iframe_breakout');
		// abort
		return;
	}
	if (!$facebook_settings->enabled) {
		// not enabled
		// iframe break out if iframe present
		add_action('wp_head', 'ym_fbook_init_iframe_breakout');
		// abort
		return;
	}
	
	/********************************************/
	/* Aborts
	/********************************************/
	if (is_admin()) {
		// in the admin system
		return;
	}
	// abort login
	if (FALSE !== strpos($_SERVER['REQUEST_URI'], 'wp-login')) {
		return;
	}
	// IPN
	if (ym_request('ym_process')) {
		return;
	}
	// Cron
	if (ym_request('doing_wp_cron')) {
		return;
	}
	/********************************************/
	/* basic defines
	/********************************************/
	define('YM_FBOOK_APPID', $facebook_settings->app_id);
	define('YM_FBOOK_SECRET', $facebook_settings->app_secret);

	$Location = str_replace('?logged_out=1', '', $location);
	define('YM_FBOOK_IN_HERE', $location);
	// app target
	define('YM_FBOOK_APP_TARGET', 'https://apps.facebook.com/' . $facebook_settings->canvas_url . str_replace(site_url(), '', $location));
	define('YM_FBOOK_PAGE_TARGET', 'https://facebook.com/' . $facebook_settings->page_url . '?sk=app_' . YM_FBOOK_APPID);// can't take args
	if (isset($_SESSION['facebook_last_page']) && $_SESSION['facebook_last_page']) {
		define('YM_FBOOK_LAST_PAGE', $_SESSION['facebook_last_page']);
	} else {
		// TODO: use location for now, but should be defined FB Root URL based on page/app
		define('YM_FBOOK_LAST_PAGE', $location);
	}
	$_SESSION['facebook_last_page'] = $location;

	/********************************************/
	/* Linter
	/********************************************/
	// come back to this if needed
	$test = 'facebookexternalhit';
	if (substr($_SERVER['HTTP_USER_AGENT'], 0, strlen($test)) == $test) {
		return;
	}
	
	/********************************************/
	/* Scope
	/********************************************/
	$scope = array();
	global $scopes;
	foreach ($scopes as $fbsetting => $entry) {
		if (isset($facebook_settings->$fbsetting) && $facebook_settings->$fbsetting) {
			$scope[] = $entry;
		}
	}
	if (count($scope)) {
		$scope = '&scope=' . implode(',', $scope);
	} else {
		$scope = '';
	}
	
	/********************************************/
	/* Defines
	/********************************************/
	// oauth target
	$base = 'http://www.facebook.com/dialog/oauth/?client_id=' . YM_FBOOK_APPID . $scope . '&redirect_uri=';
	define('YM_FBOOK_AUTH_APP', $base . urlencode(YM_FBOOK_APP_TARGET));
	define('YM_FBOOK_AUTH_PAGE', $base . urlencode(YM_FBOOK_PAGE_TARGET));
	define('YM_FBOOK_AUTH_NO', $base . urlencode($location));
	
	/********************************************/
	/* Exceptions
	/********************************************/
	if (isset($_SESSION['in_facebook']) && $_SESSION['in_facebook'] == 1 && ym_request('leavefacebook') == 1 && $facebook_settings->enable_leave_facebook) {
		// leaving :-(
		unset($_SESSION['in_facebook']);
		// route to last page, not current page (aka location)
		// as that is leavefacebook = 1
		echo '<script type="text/javascript">top.location.href="' . $_SESSION['facebook_last_page'] . '";</script>';
		exit;
	}
	if (ym_request('loggedout') == 1) {
		wp_logout();
		$in_facebook = isset($_SESSION['in_facebook']) ? $_SESSION['in_facebook'] : FALSE;
		$in_facebook_page = isset($_SESSION['in_facebook_page']) ? $_SESSION['in_facebook_page'] : FALSE;
		session_destroy();
		session_start();
		$_SESSION['in_facebook'] = $in_facebook;
		$_SESSION['in_facebook_page'] = $in_facebook_page;
		$r = ym_fbook_oauth_go();
//		echo $r . '<br />';
		$r = str_replace(array('loggedout=1&', 'loggedout=1'), '', $r);
		$r = str_replace(array(urlencode('loggedout=1&'), urlencode('loggedout=1')), '', $r);
//		$_SESSION['ym_fb_auth_target'] = $r;
		$_SESSION['ym_fb_auth_target'] = str_replace(array(urlencode('loggedout=1&'), urlencode('loggedout=1'), 'loggedout=1'), '', $_SESSION['ym_fb_auth_target']);
//		echo $r;
//echo $_SESSION['ym_fb_auth_target'];
		//echo '<script type="text/javascript">top.location.href="' . $_SESSION['ym_fb_auth_target'] . '";</script>';
		echo '<script type="text/javascript">top.location.href="' . $r . '";</script>';
		exit;
	}
	
	// Ping check to see if facebook exists and is alive
	// Most commonly analytics
	if (ym_get('ymfbook')) {
		$_SESSION['in_facebook'] = 1;
	}

	if ($_SESSION['in_facebook']) {
		wp_enqueue_script('ym-fb', site_url('wp-content/plugins/ym_facebook/js/fb.js'), array('jquery'), YM_FB_PLUGIN_VERSION);
		wp_enqueue_style('ym-fb-login', site_url('wp-content/plugins/ym_facebook/css/ym_fbook_login.css'), array(), YM_FB_PLUGIN_VERSION);
	}
	
	// height controls
	if ($facebook_settings->iframe_size == 'scrollbars') {
		if ($facebook_settings->iframe_size_height) {
			define('YM_FBOOK_HEIGHT', 'FB.Canvas.setSize({height: ' . $facebook_settings->iframe_size_height . '});');
		} else {
			define('YM_FBOOK_HEIGHT', '');// height of window-ish
		}
	} else {
		define('YM_FBOOK_HEIGHT', 'FB.Canvas.setAutoResize();');
	}
	// width controls
	if (isset($_SESSION['in_facebook_page']) && $_SESSION['in_facebook_page']) {
		$width = 450;
	} else {
		$width = 600;
	}
	define('YM_FBOOK_WIDTH', $width);
	
	/********************************************/
	/* post or session
	/********************************************/
	if (ym_post('signed_request', false)) {
		// landed in facebook from the outside world
		
		// store the request
		$_SESSION['facebook_signed_request'] = $_POST['signed_request'];
		
		// set in facebook here as we are defo. in facebook
		// cant do it on data uncode as we could be on the main site
		// using a wordpress side facebook like wall
		// for example
		$_SESSION['in_facebook'] = TRUE;
		// if in_facebook then redirect there
		// if in_facebook and in_facebook_page then go to page
		// if in_facebook_page only do nothing (as not in facebook)
		
		$_SESSION['facebook_signed_request'] = $_POST['signed_request'];
	}
	
	/********************************************/
	/* force
	/********************************************/
	if ($facebook_settings->force_facebook && !$_SESSION['in_facebook']) {
		// force
		$_SESSION['in_facebook'] = 1;
		if ($facebook_settings->page_url) {
			$_SESSION['in_facebook_page'] = 1;
		}
		header('Location: ' . ym_fbook_oauth_go());
//header('Location: ' . ($facebook_settings->page_url ? YM_FBOOK_PAGE_TARGET : YM_FBOOK_APP_TARGET));
		exit;
	}
	
	/********************************************/
	/* interupt for auth
	/********************************************/
	// check for a get code
	if (ym_get('code')) {
		// landed with a code
		// oAuth return, validate
		// get token

		if ($_SESSION['in_facebook_page'] && $facebook_settings->page_url) {
			$url = 'https://facebook.com/' . $facebook_settings->page_url . '/';
		} else {
			$url = 'https://apps.facebook.com/' . $facebook_settings->canvas_url . '/';
		}

		
		if (!$_SESSION['ym_fb_auth_target']) {
			// no target
			ym_fbook_oauth_go();
		}
		
		// generate auth code
//		$_SESSION['ym_fb_auth_target'] = str_replace(array(urlencode('loggedout=1&'), urlencode('loggedout=1'), 'loggedout=1&', 'loggedout=1'), '', $_SESSION['ym_fb_auth_target']);

//			'&redirect_uri=' . urlencode($_SESSION['ym_fb_auth_target']) .
		$auth_code = '?client_id=' . YM_FBOOK_APPID .
			'&redirect_uri=' . urlencode($url) .
			'&client_secret=' . YM_FBOOK_SECRET .
			'&code=' . $_GET['code'];
//		echo $auth_code;//exit;
		
		// exchange
		$facebook_auth = new Facebook('', $auth_code);
		// decode
		$auth = $facebook_auth->auth;
		$test = json_decode($auth);
		if ($test->error->message) {
			echo $test->error->message;
			ym_fbook_add_message(str_replace('_', ' ', $test->error->message));
			return;
		}
		parse_str($auth, $query);
		$_SESSION['facebook_oauth_token'] = $query['access_token'];
		$_SESSION['facebook_oauth_start'] = time();
		$_SESSION['facebook_oauth_expires'] = $query['expires'];
		// clean
		$_SESSION['ym_fb_auth_target'] = str_replace('code=' . ym_get('code'), '', $_SESSION['ym_fb_auth_target']);
		echo '<script type="text/javascript">top.location.href="' . $_SESSION['ym_fb_auth_target'] . '"</script>';
		unset($_SESSION['ym_fb_auth_target']);
		// and GO BABY GO!!!!!!!!!!!
		exit;
	}
	/********************************************/
	/* munch
	/********************************************/
	if (isset($_SESSION['facebook_signed_request']) && $_SESSION['facebook_signed_request']) {
		// exisiting session
		// validate
		$data = facebook_uncode($_SESSION['facebook_signed_request']);
		
		if ($data) {
			// last control
			if (isset($_SESSION['facebook_use_last_page']) && $_SESSION['facebook_use_last_page']) {
				unset($_SESSION['facebook_use_last_page']);
				header('Location: ' . YM_FBOOK_LAST_PAGE);
				exit;
			}
			
			if (isset($data->page) && $data->page) {
				$_SESSION['facebook_page'] = $data->page;
				// defo in a page
				// should only occur on landing on the page
				$_SESSION['in_facebook_page'] = TRUE;
				// ALERT LANDING TRIGGER LANDING CONTROLLER
				if ($facebook_settings->page_landing && $_SERVER['REQUEST_URI'] != $facebook_settings->page_landing && $_POST['signed_request']) {
					// somewhere to land
					header('Location: /' . $facebook_settings->page_landing);
					exit;
				}
				define('ym_fbphp_dev_in_page', TRUE);
			} else {
				define('ym_fbphp_dev_in_page', TRUE);
			}
			// landing control needed?
			if ($facebook_settings->canvas_landing && $_SERVER['REQUEST_URI'] != $facebook_settings->canvas_landing && $_POST['signed_request'] && !$_SESSION['facebook_has_landed'] && !$_SESSION['in_facebook_page']) {
				// landing control if the Root Page is requested
				header('Location: /' . $facebook_settings->canvas_landing);
				exit;
			}
			$_SESSION['facebook_has_landed'] = TRUE;
			
			// oauth
			$_SESSION['facebook_oauth_token']	= $data->oauth_token;
			$_SESSION['facebook_oauth_start']	= $data->issued_at;
			$_SESSION['facebook_oauth_expires']	= $data->expires;
		}
	} else if (isset($_SESSION['facebook_oauth_token']) && $_SESSION['facebook_oauth_token']) {
		// we have a facebook session
	} else {
		// no session
		ym_fbook_do_template();
		return;
	}
	
	// GIVE ME A CLIENT
	global $facebook_client;
	$facebook_client = new Facebook($_SESSION['facebook_oauth_token']);
	if (!$facebook_client->initok) {
		$facebook_client = FALSE;
	} else {
		// permissions check
		// check oauth granted match what we need
		// in case the game has changed.
		$permissions = $facebook_client->permissions();
		global $scopes;
		$scope_copy = $scopes;
		foreach ($scope_copy as $fbsetting => $entry) {
			if (isset($facebook_settings->$fbsetting) && $facebook_settings->$fbsetting) {
				// check presence
				if (isset($permissions->data[0]->$entry) && $permissions->data[0]->$entry == 1) {
					unset($scope_copy[$fbsetting]);
				}
			} else {
				// not requested
				unset($scope_copy[$fbsetting]);
			}
		}
		if (count($scope_copy)) {
			add_action('ym_fbook_messages', 'ym_fbook_messages');
			add_action('template_redirect', 'ym_fbook_template');
			define('FBOOK_TEMPLATE_OVERRIDE', 'login');
			return;
		}
	}
	if ($facebook_client && !$data) {
		// build data
		$data = new stdClass();
		
		$data->user_id = $facebook_client->user_data->id;
		$data->locale = $facebook_client->user_data->locale;
	}

	// facebook user ID
	$_SESSION['facebook_user_id'] = $data->user_id;
	$_SESSION['locale'] = isset($data->locale) ? $data->locale : 'en_GB';
	
	// check facebook login
	if ($facebook_settings->force_facebook_auth && !$data->user_id) {
		// require login
		// redirect to facebook login
		// use template login template
		add_action('ym_fbook_messages', 'ym_fbook_messages');
		add_action('template_redirect', 'ym_fbook_template');
		define('FBOOK_TEMPLATE_OVERRIDE', 'login');
		return;
	}
	
	if (isset($data->id) && $data->id) {
		ym_fbook_has_oauth_expired();
	}
	
	// check wordpress login
	ym_fbook_maintain_wordpress();
	
	/*
	if ($_REQUEST['register'] == 1 && !$_SESSION['facebook_registering']) {
		// need login.....
		$_SESSION['facebook_use_last_page'] = 1;
		$_SESSION['facebook_registering'] = 1;
		unset($_SESSION['ym_facebook_me_cache']);
		echo '<script type="text/javascript">top.location.href="' . ym_fbook_oauth_go() . '"</script>';
		exit;
	}
	if ($_REQUEST['register'] == 1 && $_SESSION['facebook_registering']) {
		// go register dammit
		add_action('ym_fbook_messages', 'ym_fbook_messages');
		add_action('template_redirect', 'ym_fbook_template');
		define('FBOOK_TEMPLATE_OVERRIDE', 'register');
		return;
	}
	*/
	
	// Hidden Reg
	if ($facebook_settings->register_with_facebook_hidden && $_SESSION['facebook_user_id'] && !$_SESSION['wordpress_user_id']) {
		// not logged into WP
		// go hidden reg
		ym_fbook_hidden_register();
	}
	
	// action cases
	if (ym_request('dolink', false)) {
		// go for link
		$_SESSION['dolink'] = 1;
		echo '<script type="text/javascript">top.location.href="' . ym_fbook_oauth_go() . '"</script>';
		exit;
	}
	if (ym_session('dolink')) {
		if (!$_SESSION['facebook_user_id']) {
			header('Location: ' . ym_fbook_oauth_go());
			exit;
		}
		ym_fbook_dolink();
	}
	if (ym_request('dounlink')) {
		// unlink
		ym_fbook_dounlink();
	}
	
	ym_fbook_do_template();
}

function ym_fbook_do_template() {
	// spew the template if needed
	if (isset($_SESSION['in_facebook']) && $_SESSION['in_facebook']) {
		add_action('ym_fbook_messages', 'ym_fbook_messages');
		add_action('template_redirect', 'ym_fbook_template');
	}
}

function ym_fbook_init_iframe_breakout($location = FALSE) {
	if (!$location) {
		global $location;
		if (!$location) {
			$location = site_url();
		}
	}
	echo '
<script type="text/javascript">' . "
	if (window.parent != window.self) {
		window.parent.location = '" . $location . "';
	}
</script>
";
	exit;
}

function ym_fbook_oauth_go() {
	// page or app
	if ($_SESSION['in_facebook_page'] == 1) {
		// go for the page
		$r = YM_FBOOK_AUTH_PAGE;
		$_SESSION['ym_fb_auth_target'] = YM_FBOOK_PAGE_TARGET;
//		$r = YM_FBOOK_AUTH_APP;
//		$_SESSION['ym_fb_auth_target'] = YM_FBOOK_APP_TARGET;
	} else if ($_SESSION['in_facebook'] == 1) {
		// go for the app
		$r = YM_FBOOK_AUTH_APP;
		$_SESSION['ym_fb_auth_target'] = YM_FBOOK_APP_TARGET;
	} else {
		// site url
		$r = YM_FBOOK_AUTH_NO;
		global $location;
		$_SESSION['ym_fb_auth_target'] = $location;
	}
	return $r;
}

function ym_fbook_maintain_wordpress() {
	global $wpdb, $facebook_settings, $current_user;
	get_currentuserinfo();
	
	if ($current_user->ID) {
		$_SESSION['wordpress_user_id'] = $current_user->ID;
	} else if (isset($_SESSION['facebook_user_id']) && $_SESSION['facebook_user_id']) {
		// not logged in
		$query = 'SELECT user_id FROM ' . $wpdb->prefix . 'usermeta WHERE meta_key = \'ym_facebook_user_id\' AND meta_value = ' . $_SESSION['facebook_user_id'];
		$rows = $wpdb->get_results($query);
		if ($wpdb->num_rows > 1) {
			// found more than one
			ym_fbook_add_message(sprintf('Your Facebook account is linked to Multiple <strong>%s</strong> accounts', get_bloginfo('name')));
//			ym_fbook_add_message('UM WTF');
		} else if ($wpdb->num_rows == 1) {
			// found just one
			$_SESSION['wordpress_user_id'] = $rows[0]->user_id;
			ym_fbook_dowplogin();
		} else {
			unset($_SESSION['wordpress_user_id']);
			// found nothing
			// is facebook registration available?
			if (!$facebook_settings->disable_link_message) {
				ym_fbook_add_message(sprintf('Your Facebook account is not linked to any <strong>%s</strong> accounts', get_bloginfo('name')));
				ym_fbook_add_message('Would you like to Link it?');
			}
		}
	} else {
		// not logged in to either
	}
}

function ym_fbook_hidden_register() {
	global $facebook_client, $wpdb;
	
	$username = $facebook_client->user_data->username;
	$email = $facebook_client->user_data->email;
	
	if (!$email) {
		// logical assumption
		$email = $username . '@facebook.com';
	}
	
	// see if user exists by email
	$query = 'SELECT ID FROM ' . $wpdb->prefix . 'users WHERE user_email = \'' . $email . '\'';
	if ($id = $wpdb->get_var($query)) {
		// user exists under this email
		$_SESSION['wordpress_user_id'] = $id;
		ym_fbook_dolink();
		return;
	}
	// see if user name exists
	$query = 'SELECT ID FROM ' . $wpdb->prefix . 'users WHERE user_login = \'' . $username . '\'';
	if ($id = $wpdb->get_var($query)) {
		// user exists under this email
		$_SESSION['wordpress_user_id'] = $id;
		ym_fbook_dolink();
		return;
	}
	
	// not found go user create
	$target_sub = $facebook_settings->register_with_facebook_hidden_subid;
	// passwordless
	$user = new YourMember_User();
	$user_id = $user->create($email, $target_sub, FALSE, $username);

	if (is_int($user_id)) {
		$_SESSION['wordpress_user_id'] = $user_id;
		// ok
		ym_fbook_dolink();
		ym_fbook_dowplogin();
		
		if ($facebook_settings->register_with_facebook_hidden_redirect) {
			header('Location: ' . $facebook_settings->register_with_facebook_hidden_redirect);
			exit;
		}
	} else {
		// fail
	}
}

function ym_fbook_dolink() {
	global $wpdb;
	$query = 'DELETE FROM ' . $wpdb->prefix . 'usermeta WHERE meta_key = \'ym_facebook_user_id\' AND meta_value = ' . $_SESSION['facebook_user_id'];
	$wpdb->query($query);

	update_user_meta($_SESSION['wordpress_user_id'], 'ym_facebook_user_id', $_SESSION['facebook_user_id']);
	
	do_action('ym_fbook_dolink');
}
function ym_fbook_dounlink() {
	delete_user_meta($_SESSION['wordpress_user_id'], 'ym_facebook_user_id');
	
	do_action('ym_fbook_dounlink');
}
function ym_fbook_dowplogin() {
	wp_set_current_user($_SESSION['wordpress_user_id']);
	global $ym_user;
	$ym_user = new YourMember_User($_SESSION['wordpress_user_id']);
	
	do_action('ym_fbook_wplogin');
}
function ym_fbook_is_linked() {
	return get_user_meta($_SESSION['wordpress_user_id'], 'ym_facebook_user_id', TRUE);
}

function ym_fbook_has_oauth_expired() {
	if ($_SESSION['facebook_oauth_expires'] < time() && $_SESSION['facebook_oauth_expires']) {
		// ARG EXPIRED :-(
		// send to auth to go get a new token
		$_SESSION['facebook_use_last_page'] = 1;
		echo '<script type="text/javascript">top.location.href="' . ym_fbook_oauth_go() . '"</script>';
		exit;
	}
}
