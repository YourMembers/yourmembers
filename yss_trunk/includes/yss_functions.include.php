<?php

/*
* $Id: yss_functions.include.php 1779 2012-01-11 13:42:07Z BarryCarlyon $
* $Revision: 1779 $
* $Date: 2012-01-11 13:42:07 +0000 (Wed, 11 Jan 2012) $
*/

function yss_check_version() {
//	require_once(YM_INCLUDES_DIR . 'update_checker.php');
	$url = str_replace('version_check', 'metafile', YSS_VERSION_CHECK_URL);
	$yss_update_checker = new PluginUpdateChecker($url, YSS_META_BASENAME);
}

function yss_plugin_exists($plugin) {
	global $plugins;
	
	$return = false;
	$active_plugins = get_option('active_plugins');
	
	if (in_array($plugins[$plugin], $active_plugins)) {
		$return = true;
	}
	
	return $return;
}

function yss_generate_yss_url($s, $encode = TRUE) {
	// S3 Expire
	// convert the time in the db to seconds
	$expire = time() + (60 * YSS_EXPIRE_TIME_LIMIT);
	// two mins
	
	$auth = yss_generate_query_string_auth($s->bucket, $s->resource_path, $expire);
	$url = yss_generate_full_url($s->bucket, $s->resource_path, $expire, $auth, $encode);
	
	return $url;
}

function yss_generate_query_string_auth($bucket, $resource, $expire) {
	$path = 'GET' . "\n\n\n" . $expire . "\n/" . $bucket . '/' . $resource;
	$query_string = urlencode(base64_encode((hash_hmac('sha1', utf8_encode($path), get_option('yss_secret_key'), true))));
	
	return $query_string;
}

function yss_generate_full_url($bucket, $resource, $expire, $auth, $encode) {
	/*
	? = %3F
	& = %26
	= = %3D
	*/
	
	if ($encode) {
		$url = 'https://' . $bucket . '.s3.amazonaws.com/' . $resource . '%3FAWSAccessKeyId%3D' . get_option('yss_user_key') . '%26Expires%3D' . $expire . '%26Signature%3D' . $auth;
	} else {
		$url = 'https://' . $bucket . '.s3.amazonaws.com/' . $resource . '?AWSAccessKeyId=' . get_option('yss_user_key') . '&Expires=' . $expire . '&Signature=' . $auth;
	}
	return $url;
}

/*
function yss_s3_url() {
	$s_id = ym_get('yss_player_id');
	if ($s = yss_get($s_id)) {
		$auth_string = yss_generate_yss_url($s);
		header('Location: ' . $auth_string);
	}
	exit;
}
*/
