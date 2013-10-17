<?php

function ym_fire_shortcode_parse($args, $content, $tag) {
	global $firetypes, $wpdb;
	
	switch ($tag) {
		case('app_counter'):
			$html = '';
//			$html = '<p>Pricing Models</p>';
			if (isset($args['pid'])) {
				// get pid counter
				$targets[] = ym_firesale_get_single($args['pid']);
			} else {
				$targets = ym_firesale_get_all_enabled();
			}
			// get all counters
			foreach ($targets as $fire) {
				if ($fire->fire_enable) {
					// get current tier
					$tier = ym_firesale_get_current_tier($fire->fire_id);
					$tier = $tier[0];
					// left till expire
					// ignore type 2 as type 2 on actovates nbecome types 1
					//what
					$what = $firetypes[$fire->fire_type];
					$which = $fire->fire_type_id;
					// how long
					if ($tier->fire_limit_by) {
						//$tier->fire_tier_started
						$left = $tier->fire_limit_var - time();
						// hours
						$hours = $left / 3600;
						list($hours, $left) = explode('.', $hours);
						$left = $hours . ' Hours Left';
					} else {
						// get sales
						if ($fire->fire_type) {
							// subs
							$left = $tier->fire_limit_var - ym_fire_sales_subscription_since($tier->fire_tier_started, $which);
						} else {
							// ppp
							$left = $tier->fire_limit_var - ym_fire_sales_ppp_since($tier->fire_tier_started, $which);
						}
						$left .= ' Sales Left';
					}
					
					if ($fire->fire_type) {
						$pack = ym_get_pack($which);
						$what = $pack['account_type'];
						$link = '?ym_subscribe=1&ud=1';
					} else {
						// what is the post title
						$what = 'SELECT post_title FROM ' . $wpdb->posts . ' WHERE ID = ' . $which;
						$what = $wpdb->get_var($what);
						
						$link = get_permalink($which);
					}
					
					$html .= '<p><a href="' . $link . '"><strong>' . $what . '</strong></a> ' . $left . '</p>';
				}
			}
			return $html;
		default:
			// not defined
	}
}
