<?php

function ym_fbook_template() {
	global $facebook_settings, $facebook_client;

	//aborts
	if (is_single() && $facebook_settings->post_breakout) {
		add_action('wp_head', 'ym_fbook_iframe_breakout');
		return;
	}
	if (is_page() && $facebook_settings->page_breakout) {
		add_action('wp_head', 'ym_fbook_iframe_breakout');
		return;
	}
	
	// since inside init
	// call scripts
	wp_enqueue_style('jquery-ui', 'https://jquery-ui.googlecode.com/svn/tags/latest/themes/base/jquery.ui.all.css');
	wp_enqueue_script('ym-fb-nav', site_url('wp-content/plugins/ym_facebook/js/fb_nav.js'), array('jquery'), YM_FB_PLUGIN_VERSION);
	wp_enqueue_script('ym-fb', site_url('wp-content/plugins/ym_facebook/js/fb.js'), array('jquery'), YM_FB_PLUGIN_VERSION);

	wp_enqueue_style('ym-fb', site_url('wp-content/plugins/ym_facebook/css/ym_fbook.css'), array(), YM_FB_PLUGIN_VERSION);
	wp_enqueue_style('ym-fb-login', site_url('wp-content/plugins/ym_facebook/css/ym_fbook_login.css'), array(), YM_FB_PLUGIN_VERSION);

	wp_enqueue_script('jquery-ui-ymenu', site_url('wp-content/plugins/ym_facebook/js/jquery.ymenu.js'), array('jquery'));
	wp_enqueue_style('jquery-ui-ymenu', site_url('wp-content/plugins/ym_facebook/css/jquery.ymenu.css'));
	
	// aborts
	if (defined('YM_FBOOK_NO_LOOP') || defined('FBOOK_TEMPLATE_OVERRIDE')) {
		include(YM_FBOOK_BASE_DIR . '/template/head.php');

		if (defined('FBOOK_TEMPLATE_OVERRIDE')) {
			include(YM_FBOOK_BASE_DIR . '/template/' . FBOOK_TEMPLATE_OVERRIDE . '.php');
		} else if ($_GET['login']) {
			ym_fbook_wp_login_form();
		}
		
		include(YM_FBOOK_BASE_DIR . '/template/foot.php');
		exit;
	}
	
	if ($facebook_settings->enable_fb_php) {
		if (locate_template('fb.php', TRUE)) {
			exit;
		}
	}
	
	// catch non permalinks
	if ($_GET['p']) {
		query_posts('p=' . $_GET['p']);
	}
	if ($_GET['page_id']) {
		query_posts('page_id=' . $_GET['page_id']);
	}
	
	include(YM_FBOOK_BASE_DIR . '/template/head.php');
	
	include(YM_FBOOK_BASE_DIR . '/template/loop.php');
	
	// comments
	comments_template('', TRUE);
	
	include(YM_FBOOK_BASE_DIR . '/template/foot.php');
	exit;
}

$ym_fbook_messages = array();
function ym_fbook_messages() {
	global $ym_fbook_messages;
	
	$messagestring = '';
	
	if (sizeof($ym_fbook_messages) > 1) {
		$messagestring = '<ul>';
		foreach ($ym_fbook_messages as $message) {
			$messagestring .= '<li>' . $message . '</li>';
		}
		$messagestring .= '</ul>';
	} else if (sizeof($ym_fbook_messages)) {
		$messagestring = '<p>' . implode($ym_fbook_messages) . '</p>';
	}
	
	if ($messagestring) {
		echo '<div class="ym_message"><div class="ym_message_liner">' . $messagestring . '</div></div>';
		ym_fbook_message_reset();
	}
	return;
}

function ym_fbook_add_message($message) {
	global $ym_fbook_messages;
	$ym_fbook_messages[] = $message;
	return;
}
function ym_fbook_message_reset() {
	global $ym_fbook_messages;
	$ym_fbook_messages = array();
	return;
}

/********************************************************************/
// iFrame controller
function ym_fbook_iframe_breakout($location = FALSE) {
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
}

/********************************************************************/
// Comment Form both inside/outside
function ym_fbook_fbook_wall($url) {
	global $facebook_settings;
	
	if ($facebook_settings->use_facebook_comments && $_SESSION['in_facebook']) {
		$url = YM_FBOOK_BASE_DIR . '/template/comments.php';
	}
	if ($facebook_settings->use_facebook_comments_on_site && !$_SESSION['in_facebook']) {
		$url = YM_FBOOK_BASE_DIR . '/template/comments.php';
	}

	return $url;
}
/********************************************************************/


function ym_fbook_login_logout($echo = FALSE) {
	global $facebook_user_id;
	
	$r = '';
	
	if ($facebook_user_id) {
		$r = '<a href="#nowhere" class="ym_fbook_logout">Logout</a>';
	} else {
//		$r = '<fb:login-button show-faces="false" width="200" max-rows="1"></fb:login-button>';
//		$r = '<a href="#nowhere" id="ym_fbook_login">Login</a>';
	}
	
	if ($echo) {
		echo $r;
	} else {
		return $r;
	}
}

// javascript SDK
function ym_fbook_fbook_init() {
	$_SESSION['locale'] = $_SESSION['locale'] ? $_SESSION['locale'] : 'en_US';
	
	$r = '
<div id="fb-root"></div>
<script type="text/javascript">
	var loadtime = 0;
	window.fbAsyncInit = function() {
		FB.init({
			appId: "' . YM_FBOOK_APPID . '",
			status: true,
			cookie: true,
			xfbml: true,
			channelURL: "' . site_url('wp-content/plugins/ym_facebook/channel.html') . '"
		});
		FB.Canvas.setDoneLoading(function foo(result) {
				loadtime = result.time_delta_ms;
				';
	if ($_SESSION['in_facebook']) {
		$r .= YM_FBOOK_HEIGHT;
	}
	if ($_SESSION['ym_facebook_me_cache']) {
		$r .= '
			FB.getLoginStatus(function(response) {
				if (response.status == \'connected\') {
					// nothing to do
				} else {
					document.location = \'?loggedout=1\';
				}
			});
			';
	}
	$r .= '
		});
		FB.Event.subscribe(\'auth.logout\', function(response) {
			document.location = \'?loggedout=1\';
		});
		';
	$r .= like_wall_event_subscribe();
	$r .= '
	};
	(function(d){
    	var js, id = \'facebook-jssdk\'; if (d.getElementById(id)) {return;}
    	js = d.createElement(\'script\'); js.id = id; js.async = true;
    	js.src = "//connect.facebook.net/' . $_SESSION['locale'] . '/all.js";
    	d.getElementsByTagName(\'head\')[0].appendChild(js);
	}(document));
</script>
';
	echo $r;
	return;
}
function ym_fb_channel() {
	echo '<script src="://connect.facebook.net/en_US/all.js"></script>';
	exit;
}

// event subscribe controllers
function like_wall_event_subscribe() {
	return "
	FB.Event.subscribe('edge.create', function(response) {
		window.location.reload();
	});
	FB.Event.subscribe('edge.remove', function(response) {
		window.location.reload();
	});
	";
}

// HEADER
function ym_fbook_og() {
	global $facebook_settings, $title, $post;
	
	if (!$title) {
		$title = wp_title('|', false, 'right') . get_bloginfo('name');
	}
	
	// do we need open graph tags?
	$image = '<meta property="og:image" content="' . $facebook_settings->open_graph_image . '" />';

	$og = '';
	if (is_singular()) {
		global $post;
		$og .= '
	<meta property="og:title" content="' . $title . '" />
	<meta property="og:type" content="' . $facebook_settings->canvas_url . ':post" />
	<meta property="og:url" content="' . get_permalink($post->ID) . '" />
	' . $image . '
	<meta property="og:site_name" content="' . get_bloginfo('name') . '" />
	<meta property="og:description" content="' . get_bloginfo('description', 'display') . '" />
	';
	} else {
		$og .= '
	<meta property="og:title" content="' . $title . '" />
	<meta property="og:type" content="' . $facebook_settings->open_graph_type . '" />
	<meta property="og:url" content="' . site_url() . '" />
	' . $image . '
	<meta property="og:site_name" content="' . get_bloginfo('name') . '" />
	<meta property="og:description" content="' . get_bloginfo('description', 'display') . '" />
	';
	}

	$og .= '
	<meta property="fb:app_id" content="' . YM_FBOOK_APPID . '" />
	';
	if ($facebook_settings->open_graph_admins) {
		$og .= '
	<meta property="fb:admins" content="' . $facebook_settings->open_graph_admins . '" />
	';
	}
	
	echo $og;
}
// FOOTER
function ym_fbook_analytics() {
	global $facebook_settings;
	
	if ($facebook_settings->analytics_tracking_code) {
		echo $facebook_settings->analytics_tracking_code;
	} else if ($facebook_settings->google_analytics_profile_id) {
		$script = '
<script type="text/javascript">' . "

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '" . $facebook_settings->google_analytics_profile_id . "']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
";
		echo $script;
	}
}
