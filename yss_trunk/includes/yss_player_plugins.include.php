<?php

/*
* $Id: yss_player_plugins.include.php 2386 2012-09-27 08:32:59Z bcarlyon $
* $Revision: 2386 $
* $Date: 2012-09-27 09:32:59 +0100 (Thu, 27 Sep 2012) $
*/

$yss_plugins = array(
	'fv-wordpress-flowplayer' => array(
		'name'		=> 'FV Wordpress Flowplayer',
		'slug'		=> 'fv-wordpress-flowplayer',
		'default'	=> 'fv-wordpress-flowplayer/flowplayer.php',
		'encode'	=> true,
		'code'		=> '[flowplayer src="%s" width=%d height=%d]',
	)
	, 'jw-player-plugin-for-wordpress' => array(
		'name'		=> 'JW Player Plugin for WordPress',
		'slug'		=> 'jw-player-plugin-for-wordpress',
		'default'	=> 'jw-player-plugin-for-wordpress/jwplayermodule.php',
		'encode'	=> false,
		'code'		=> '[jwplayer file="%s" width="%d" height="%d"]'
	)
);

$codes = array();

foreach ($yss_plugins as $ref => $data) {
	$codes[$data['slug']] = $data['code'];
}

$yss_master_counter = 0;

function yss_generate_player($player, $video, $width, $height) {
	global $codes, $yss_plugins, $yss_master_counter;
	$yss_master_counter ++;
	
	$distribution = $video->distribution;
	$distribution = json_decode($distribution);
	
	$stream = FALSE;
	$url = '';
	
	$valid = time() + YSS_EXPIRE_TIME_LIMIT;
	
	$return = '';
	
	$yss_cloudfront = new CloudFront();
	
	if (isset($distribution->streaming) && $distribution->streaming) {
		$streamer = $distribution->streaming;
		// streamer
		// resource magic
		
		// FLV?
		$resource = str_replace('.flv', '', $video->resource_path);
		
		$url = $yss_cloudfront->generateSecureUrl($resource, $valid);
		// streamer
		$streamer = 'rtmp://' . $streamer . '/cfx/st';
		
		// non standard code required
		switch ($player) {
			case 'custom':
				$url = yss_generate_yss_url($video, FALSE);
				$return = yss_custom_player($url, $height, $width, $streamer);
				break;
			case 'fv-wordpress-flowplayer':
				if (strpos($video->resource_path, '.flv')) {
					$url = str_replace(array('=', '&'), array('%3D', '%26'), $url);
				
					$return = '
<div id="streams_' . str_replace('.', '_', $resource) . '" style="display:block;width:' . $width . 'px;height:' . $height . 'px;"></div>
<script type="text/javascript">' . "
flowplayer('streams_" . str_replace('.', '_', $resource) . "', '" . PLAYER . " ', {
	clip:  {
		autoPlay: false,
		autoBuffering: true,
		
		url: '" . $url . "',
		provider: 'rtmp'
	},
	plugins: {
		rtmp: {
			url: '" . YSS_RESOURCES . "/flowplayer.rtmp-3.2.3.swf',
			netConnectionUrl: '" . $streamer . "'
		}
	}
});
</script>";
				} else {
					echo 'YSS does not support non FLV files in a streaming distribution under FlowPlayer. It just does not work!';
				}
				break;
			case 'jw-player-plugin-for-wordpress':
				if (isset($distribution->download) && $distribution->download) {
					$download = $distribution->download;
					$durl = 'https://' . $download . '/' . $video->resource_path;
					$durl = $yss_cloudfront->generateSecureUrl($durl, $valid);
				} else {
					$durl = yss_generate_yss_url($video, FALSE);
				}

				$return = '
<div id="mediaspace_' . str_replace('.', '_', $resource) . '_' . $yss_master_counter . '">This text will be replaced</div>
<script type="text/javascript">' . "
	jwplayer('mediaspace_" . str_replace('.', '_', $resource) . '_' . $yss_master_counter . "').setup({
		flashplayer:	'" . JWPLAYER_FILES_URL . "/player/player.swf',
		controlbar:		'bottom',
		width:			'" . $width . "',
		height:			'" . $height . "',
		config:			'" . JWPLAYER_FILES_URL . '/configs/' . get_option('jwplayermodule_default') . ".xml',
		modes: [
			{
				type: 'flash',
				src: '" . JWPLAYER_FILES_URL . "/player/player.swf',
				config: {
					file: '" . $url . "',
					streamer: '" . $streamer . "',
					provider: 'rtmp'
				}
			},
			{
				type: 'html5',
				config: {
					file: '" . $durl . "'
				}
			},
			{
				type: 'flash',
				src: '" . JWPLAYER_FILES_URL . "/player/player.swf',
				config: {
					file: '" . $durl . "'
				}
			}
		]
	});
</script>
";
				break;
			default:
				$return = '<p>' . __('No Player Selected', 'yss') . '</p>';
		}
	} else if (isset($distribution->download) && $distribution->download) {
		$download = $distribution->download;
		// download dist
		$url = 'https://' . $download . '/' . $video->resource_path;
		$url = $yss_cloudfront->generateSecureUrl($url, $valid);

		if ($yss_plugins[$player]['encode']) {
			$url = explode('?', $url);
			$url[1] = urlencode($url[1]);
			$url = implode('?', $url);
		}

		$return = sprintf($codes[$player], $url, $width, $height);
	} else {
		// s3
		if ($player == 'custom') {
			$url = yss_generate_yss_url($video, FALSE);
			$return = yss_custom_player($url, $height, $width);
		} else {
			$url = yss_generate_yss_url($video);
			
			$return = sprintf($codes[$player], $url, $width, $height);
		}
	}
	
	return $return;
}

function yss_custom_player($url, $height, $width, $streamer = '') {
	$data = get_option('yss_custom_player');
	$data = str_replace('[yss_video_url]', $url, $data);
	$data = str_replace('[yss_video_height]', $height, $data);
	$data = str_replace('[yss_video_width]', $width, $data);
	$data = str_replace('[yss_streamer]', $streamer, $data);

	return $data;
}
