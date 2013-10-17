<?php

function ym_facebook_settings($gen_basic = FALSE) {
	global $facebook_settings;
	$facebook_settings = get_option('ym_fbook_options');
	
	if (!$facebook_settings && !$gen_basic) {
		return false;
	}
	
	// basic
	if (!isset($facebook_settings->enabled)) {
		$facebook_settings->enabled = 0;
	}
	if (!$facebook_settings->enabled && !$gen_basic) {
		return false;
	}

	if (!isset($facebook_settings->iframe_size)) {
		$facebook_settings->iframe_size = 'scrollbars';
	}
	
	// PERMISSIONS
	if (!isset($facebook_settings->permission_likewall)) {
		$facebook_settings->permission_likewall = 0;
	}
	if (!isset($facebook_settings->permission_email)) {
		$facebook_settings->permission_email = 0;
	}
	if (!isset($facebook_settings->permission_offline_access)) {
		$facebook_settings->permission_offline_access = 0;
	}
	
	// access
	if (!isset($facebook_settings->force_facebook)) {
		$facebook_settings->force_facebook = 0;
	}
	if (!isset($facebook_settings->force_facebook_auth)) {
		$facebook_settings->force_facebook_auth = 0;
	}
	if (!isset($facebook_settings->force_wordpress_auth)) {
		$facebook_settings->force_wordpress_auth = 0;
	}
	if (!isset($facebook_settings->require_link)) {
		$facebook_settings->require_link = 0;
	}
	if (!isset($facebook_settings->disable_link_message)) {
		$facebook_settings->disable_link_message = 1;
	}
	
	// Register
	if (!isset($facebook_settings->register_with_facebook)) {
		$facebook_settings->register_with_facebook = 0;
	}
	if (!isset($facebook_settings->register_with_facebook_hidden)) {
		$facebook_settings->register_with_facebook_hidden = 0;
	}
	
	// content
	if (!isset($facebook_settings->enable_fb_php)) {
		$facebook_settings->enable_fb_php = 0;
	}
	if (!isset($facebook_settings->enable_leave_facebook)) {
		$facebook_settings->enable_leave_facebook = 1;
	}
	
	if (!isset($facebook_settings->post_breakout)) {
		$facebook_settings->post_breakout = 0;
	}
	if (!isset($facebook_settings->page_breakout)) {
		$facebook_settings->page_breakout = 0;
	}
	if (!isset($facebook_settings->use_excerpt)) {
		$facebook_settings->use_excerpt = 0;
	}
	
	// comments
	if (!isset($facebook_settings->use_facebook_comments)) {
		$facebook_settings->use_facebook_comments = 0;
	}
	if (!isset($facebook_settings->use_facebook_comments_on_site)) {
		$facebook_settings->use_facebook_comments_on_site = 0;
	}
	
	// like/send
	if (!isset($facebook_settings->enable_share)) {
		$facebook_settings->enable_share = 0;
	}
	if (!isset($facebook_settings->enable_send)) {
		$facebook_settings->enable_send = 1;
	}
	if (!isset($facebook_settings->share_box)) {
		$facebook_settings->share_box = 'standard';
	}
	if (!isset($facebook_settings->show_faces)) {
		$facebook_settings->show_faces = 0;
	}
	if (!isset($facebook_settings->verb)) {
		$facebook_settings->verb = 'like';
	}
	if (!isset($facebook_settings->color_scheme)) {
		$facebook_settings->color_scheme = 'light';
	}
	if (!isset($facebook_settings->font)) {
		$facebook_settings->font = 'verdana';
	}
	
	if (!isset($facebook_settings->enable_share_footer)) {
		$facebook_settings->enable_share_footer = 0;
	}
	if (!isset($facebook_settings->enable_send_footer)) {
		$facebook_settings->enable_send_footer = 1;
	}
	if (!isset($facebook_settings->share_box_footer)) {
		$facebook_settings->share_box_footer = 'standard';
	}
	if (!isset($facebook_settings->show_faces_footer)) {
		$facebook_settings->show_faces_footer = 0;
	}
	if (!isset($facebook_settings->verb_footer)) {
		$facebook_settings->verb_footer = 'like';
	}
	if (!isset($facebook_settings->color_scheme_footer)) {
		$facebook_settings->color_scheme_footer = 'light';
	}
	if (!isset($facebook_settings->font_footer)) {
		$facebook_settings->font_footer = 'verdana';
	}
	
	if (!isset($facebook_settings->enable_share_shortcode)) {
		$facebook_settings->enable_share_shortcode = 1;
	}
	if (!isset($facebook_settings->enable_share_auto_nonfb)) {
		$facebook_settings->enable_share_auto_nonfb = 1;
	}
	if (!isset($facebook_settings->enable_send_shortcode)) {
		$facebook_settings->enable_send_shortcode = 1;
	}
	if (!isset($facebook_settings->share_box_shortcode)) {
		$facebook_settings->share_box_shortcode = 'standard';
	}
	if (!isset($facebook_settings->show_faces_shortcode)) {
		$facebook_settings->show_faces_shortcode = 0;
	}
	if (!isset($facebook_settings->verb_shortcode)) {
		$facebook_settings->verb_shortcode = 'like';
	}
	if (!isset($facebook_settings->color_scheme_shortcode)) {
		$facebook_settings->color_scheme_shortcode = 'light';
	}
	if (!isset($facebook_settings->font_shortcode)) {
		$facebook_settings->font_shortcode = 'verdana';
	}
	
	$facebook_settings->enable_share_likewall = 1;
	$facebook_settings->enable_send_likewall = 0;
	if (!isset($facebook_settings->share_box_likewall)) {
		$facebook_settings->share_box_likewall = 'standard';
	}
	if (!isset($facebook_settings->show_faces_likewall)) {
		$facebook_settings->show_faces_likewall = 0;
	}
	if (!isset($facebook_settings->verb_likewall)) {
		$facebook_settings->verb_likewall = 'like';
	}
	if (!isset($facebook_settings->color_scheme_likewall)) {
		$facebook_settings->color_scheme_likewall = 'light';
	}
	if (!isset($facebook_settings->font_likewall)) {
		$facebook_settings->font_likewall = 'verdana';
	}
	
	// og:
	if (!isset($facebook_settings->open_graph_image)) {
		$facebook_settings->open_graph_image = YM_IMAGES_DIR_URL . 'ym_thumb.gif';
	} else if (empty($facebook_settings->open_graph_image)) {
		$facebook_settings->open_graph_image = YM_IMAGES_DIR_URL . 'ym_thumb.gif';
	}
	if (!isset($facebook_settings->open_graph_type)) {
		$facebook_settings->open_graph_type = 'blog';
	}
	
	// credits
	if (!isset($facebook_settings->logo)) {
		$facebook_settings->logo = YM_IMAGES_DIR_URL . 'pg/facebook_credits_a.png';
	}

	if (!isset($facebook_settings->credits_purchase_sub_image)) {
		$facebook_settings->credits_purchase_sub_image = YM_IMAGES_DIR_URL . 'wordpress_blog.png';
	}
	if (!isset($facebook_settings->credits_purchase_post_image)) {
		$facebook_settings->credits_purchase_post_image = YM_IMAGES_DIR_URL . 'document_image_ver.png';
	}
	if (!isset($facebook_settings->credits_purchase_bundle_image)) {
		$facebook_settings->credits_purchase_bundle_image = YM_IMAGES_DIR_URL . 'document_copies.png';
	}
	if (!isset($facebook_settings->credits_exclusive)) {
		$facebook_settings->credits_exclusive = 0;
	}
	if (!isset($facebook_settings->exchange_rate)) {
		$facebook_settings->exchange_rate = 1;
	}
	if (!isset($facebook_settings->exchange_round)) {
		$facebook_settings->exchange_round = 'round_up';
	}
	if (!isset($facebook_settings->minimum_price)) {
		$facebook_settings->minimum_price = 0;
	}

	return TRUE;
}

function ym_fbook_versioncheck() {
	if (!is_admin()) {
		return;
	}
	global $ym_auth;
	//update checker
	require_once(YM_INCLUDES_DIR . 'update_checker.php');
	$url = str_replace('version_check', 'metafile', YM_FB_VERSION_CHECK_URL);
	$url = $url . '&key=' . $ym_auth->ym_get_key();
	$ym_update_checker = new PluginUpdateChecker($url, YM_FB_META_BASENAME);
}
add_action('ym_loaded_complete', 'ym_fbook_versioncheck');

function ym_fbook_sanity_check() {
	if (!function_exists('ym_loaded') && !@$_GET['plugin']) {
		// ym gone self kill
		$current = get_option('active_plugins');

		$key = array_search(YM_FB_META_BASENAME, $current);
		unset($current[$key]);
		update_option('active_plugins', $current);

		do_action('deactivate_' . YM_FB_META_BASENAME);

		return;
	}
}
function ym_fbook_activate_check() {
	if (!function_exists('ym_loaded')) {
		// errror
		echo '<strong>Your Members - Facebook Integration</strong>';
		echo '<p>YourMembers does not appear to be installed. <a href="http://yourmembers.co.uk/">YourMembers</a> is required to use Your Members - Facebook Integration, visit <a href="http://yourmembers.co.uk/">YourMembers</a> to purchase</p>';
		die();
	}
}
