<?php

/*
* $Id: yss_shortcode.includes.php 2618 2013-10-17 08:52:26Z tnash $
* $Revision: 2618 $
* $Date: 2013-10-17 09:52:26 +0100 (Thu, 17 Oct 2013) $
*/

function yss_shortcode($atts) {
	global $codes;
	
	$return = '';
	$allow_play = false;
	
	$id = isset($atts['id']) ? $atts['id'] : '';
	$player = isset($atts['plr']) ? $atts['plr'] : get_option('yss_playerofchoice');
	$width = isset($atts['width']) ? $atts['width'] : 640;
	$height = isset($atts['height']) ? $atts['height'] : 380;
	
	if (!$id) {//error out as no Video ID
		return '<p>' . __('No video ID specified', 'yss') . '</p>';
	}

	$video = yss_get($id);
	if (!$video) {
		return '<p>' . __('The video requested could not be found', 'yss') . '</p>';
	}
	var_dump($video);
	//access_check
	if (!$video->members && !$video->account_types) {
		$allow_play = true; //no access restriction
	} else {
		//priority given to ac check
		if ($video->account_types) {
			if ($acs = explode('||', $video->account_types)) {
				$ac = strtolower(ym_get_user_account_type());
				
				if (in_array($ac, $acs)) {
					$allow_play = true;
				}
			}
		}
		
		//if ac check fails or is not used and post comparison is then use this...
		if ($video->members && !$allow_play) {
			$posts = yss_get_video_post_assoc($id);

			foreach ($posts as $post) {
				if (ym_user_has_access($post->post_id)) {
					$allow_play = true;
					break;
				}
			}
		}
	}
	
	//if the logic above deems access should be granted then...
	if ($allow_play) {
		$return = yss_generate_player($player, $video, $width, $height);
	} else if ($message = get_option('yss_no_access_message')) {
		//else if the admin has put in a no access message this appears
		$return = $message;
	}
	
	return $return;
}
