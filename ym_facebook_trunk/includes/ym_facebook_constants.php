<?php

$settings = array(
	'enabled',
	
	'app_id',
	'app_key',
	'app_secret',
	
	'canvas_url',
	'canvas_landing',
	
	'page_url',
	'page_landing',

	'iframe_size',
	'iframe_size_height',
	
	'permission_likewall',
	'permission_email',
	'permission_offline_access',
	'permission_publish_actions',
	
	'force_facebook',
	'force_facebook_auth',
	'force_wordpress_auth',
	'require_link',
	'disable_link_message',
	
	'register_with_facebook',
	'register_with_facebook_hidden',
	'register_with_facebook_hidden_subid',
	'register_with_facebook_hidden_redirect',

	'enable_fb_php',
	'enable_leave_facebook',
	'post_breakout',
	'page_breakout',
	'use_excerpt',
	'menu',
	
	'use_facebook_comments',
	'use_facebook_comments_on_site',
	
	'google_analytics_profile_id',
	'analytics_tracking_code',
	
	'enable_share',
	'enable_send',
	'share_box',
	'show_faces',
	'verb',
	'color_scheme',
	'font',
	'ref',
	
	'enable_share_footer',
	'enable_send_footer',
	'share_box_footer',
	'show_faces_footer',
	'verb_footer',
	'color_scheme_footer',
	'font_footer',
	'ref_footer',
	
	'enable_share_shortcode',
	'enable_send_shortcode',
	'share_box_shortcode',
	'show_faces_shortcode',
	'verb_shortcode',
	'color_scheme_shortcode',
	'font_shortcode',
	'ref_shortcode',
	
	'enable_share_likewall',
	'enable_send_likewall',
	'share_box_likewall',
	'show_faces_likewall',
	'verb_likewall',
	'color_scheme_likewall',
	'font_likewall',
	'ref_likewall',
	
	'enable_shortcode_nonfb',
	'enable_share_auto_nonfb',
	
	'open_graph_image',
	'open_graph_type',
	'open_graph_admins',
	
	'logo',
	'credits_exclusive',
	'exchange_rate',
	'exchange_round',
	'minimum_price',
);

$images = array(
	'credits_purchase_sub_image',
	'credits_purchase_post_image',
	'credits_purchase_bundle_image',
);

$iframe_options = array(
	'scrollbars'	=> 'Show Scrollbars',
	'autoresize'	=> 'Auto Resize',
);

$share_options = array(
	'site_url'	=> 'Site URL',
	'facebook'	=> 'Facebook App'
);
$sharebox_options = array(
	'standard'		=> 'Just the buttons',
	'button_count'	=> 'Standard Thin buttons with a small horizontal Speech Bubble Count',
	'box_count'		=> 'A large box with a large vertical Speech Bubble Count'
);
$verbs = array(
	'like'		=> 'Like',
	'recommend'	=> 'Recommend'
);
$color_schemes = array(
	'light'	=> 'Light',
	'dark'	=> 'Dark'
);
$fonts = array(
	'none'			=> 'Default',
	'arial'			=> 'Arial',
	'lucida grande'	=> 'Lucida Grande',
	'segoe ui'		=> 'Segoe UI',
	'tahoma'		=> 'Tahoma',
	'trebuchet ms'	=> 'Trebuchet MS',
	'verdana'		=> 'Verdana'
);

$types = array(
	'activity',
	'actor',
	'album',
	'article',
	'athlete',
	'author',
	'band',
	'bar',
	'blog',
	'book',
	'cafe',
	'cause',
	'city',
	'company',
	'country',
	'director',
	'drink',
	'food',
	'game',
	'government',
	'hotel',
	'landmark',
	'movie',
	'musician',
	'non_profit',
	'politician',
	'product',
	'public_figure',
	'restaurant',
	'school',
	'song',
	'sport',
	'sports_league',
	'sports_team',
	'state_province',
	'tv_show',
	'university',
	'website'
);
foreach ($types as $key => $type) {
	unset($types[$key]);
	$types[$type] = $type;
}

$round_options = array(
	'round_up'		=> 'Round to nearest 1 Credits',
	'round_5'		=> 'Round to nearest 5 Credits',
	'round_10'		=> 'Round to nearest 10 Credits',
	'round_up_5'	=> 'Round up to nearest 5 Credits',
	'round_up_10'	=> 'Round up to nearest 10 Credits',
);
