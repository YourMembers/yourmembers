<?php

/*
* $Id: ym_deprecated.php 2385 2012-09-24 10:18:55Z bcarlyon $
* $Revision: 2385 $
* $Date: 2012-09-24 11:18:55 +0100 (Mon, 24 Sep 2012) $
*/

define('YM_ADMIN_INDEX_URL', YM_ADMIN_URL);

function ym_depricated_replace_tag($function, $matches, $argument = false ) {
	get_currentuserinfo();
	global $current_user, $user_data, $ym_user;
	$return = '';

	switch ($function) {
		//Checks User Account usage: [user_account_is#Member]
		// TODO: 11.0.8 deprecate in favour of ym_user_is
		case 'user_account_is':
			_doing_it_wrong(__FUNCTION__, 'Use ym_user_is shortcode', '11.0.6');
			$argument = str_replace('+', ' ', $argument);
			$user_id = false;
			if (ym_get('token') && ym_use_rss_token()) {
				$user = ym_get_user_by_token(ym_get('token'));
				$user_id = $user->ID;
			}

			if (strtolower(ym_get_user_account_type($user_id)) == strtolower($argument) || current_user_can('edit_posts')) {
				$return = $matches;
			}
			break;
	}

	return do_shortcode(stripslashes($return));
}
function ym_loaded_complete_depricated() {
	// TODO: Deprecate 11.0.6
	add_shortcode('user_account_is', 'ym_shortcode_parse');
}
add_action('ym_loaded_complete', 'ym_loaded_complete_depricated');

function ym_get_user_account_type($user_id=false, $to_lower=false) {
	_deprecated_function( __FUNCTION__, '11.2', 'ym_get_user_package_type' );
	return ym_get_user_package_type($user_id, $to_lower);
}

// TODO: dpericated user get_user_by
function ym_get_user_id_by_email($email) {
	_deprecated_function( __FUNCTION__, '11.0.8', 'get_user_by' );
	$user = get_user_by('email', $email);
	return $user->ID;
}

function ym_selected($val1, $val2) {
	_deprecated_function( __FUNCTION__, '11.2.0', 'none' );
	$return = '';
	if ($val1 == $val2) {
	$return = ' selected="selected" ';
	}
	
	return $return;
}

// depricated ym10
function ym_protect_with_magic($content) {
	_deprecated_function( __FUNCTION__, '10.0.0', 'none' );
	if (FALSE === (strpos($content, '[private]'))) {
		$post_id = get_the_id();
		
		$account_types = get_post_meta($post_id, '_ym_account_type', TRUE);// ; sep
		if ($account_types) {
			// use magic protect
			$content = '[private]' . $content . '[/private]';
			$content = do_shortcode($content);
		}
	}
	return $content;
}

function ym_get_select_from_array($name, $array, $value=false) {
	_deprecated_function( __FUNCTION__, '11.2.0', 'FormGen' );
	$html = '<select name="' . $name . '">';
	foreach ($array as $field=>$label) {
		$html .= '<option value="' . $field . '" ' . ($field == $value ? 'selected="selected"':'') . '>' . $label . '</option>';
	}
	$html .= '</select>';

	return $html;
}


function ym_validate_account_type($account_type, $md5=false) {
	_deprecated_function( __FUNCTION__, '11.2.0', 'None' );
	
	$packs = get_option('ym_packs');
	foreach ($packs as $i=>$pack) {
		foreach ($pack as $j=>$apack) {
			$raw_ac = $apack['account_type'];
			
			if ($md5) {
				$apack['account_type'] = md5($apack['account_type']);
			}
			
			if (strtolower($apack['account_type']) == strtolower($account_type)) {
				$match = $raw_ac;
				break;
			}
		}

		if ($match) {
			break;
		}
	}
	
	return $match;
}

add_filter('pre_option_ym_account_types', 'ym_deprecated_account_types');
function ym_deprecated_account_types($types) {
	_doing_it_wrong( 'ym_account_types', '11.2.0', 'ym_package_types' );
	return get_option('ym_package_types');
}

/**
 * Provides a count of users
 */
function ym_count_users() {
	_deprecated_function( __FUNCTION__, '11.2.0', 'None' );

	global $wpdb;
	if (!$user_count = wp_cache_get('ym_user_count', 'users')) {
		$user_count = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->users WHERE ID != 1");
		wp_cache_set('ym_user_count', $user_count, 'users');
	}
	return (int) $user_count;
}
