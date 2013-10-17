<?php

function ym_firesale_ppp($button_code, $code, $post_id) {
	if (ym_post_is_purchasable()) {
		// post cane be bought, continue
		$post_id = get_the_ID();
		// current post data
		$base_price = get_post_meta($post_id, '_ym_post_purchasable_cost', TRUE);
		$fire_original_cost = get_post_meta($post_id, '_ym_fire_original_cost', TRUE);
		$is_currently_firesale = get_post_meta($post_id, '_ym_fire_active', TRUE);
		
		if ($ym_firesale_id = ym_firesale_exists($post_id, YM_APP_TYPE_POST)) {
			// a fire sale exists for this ppp
			$tier_data = ym_firesale_get_current_tier($ym_firesale_id);
			$tier_data = $tier_data[0];
			// check the post meta is correct
			if (!get_post_meta($post_id, '_ym_fire_original_cost')) {
				// no base cost
				// post is starting to go on firesale
				// store the original cost
				update_post_meta($post_id, '_ym_fire_original_cost', $base_price);
				ym_firesale_log(
					array(
						'doing'			=> 'StartingPricingModel',
						'postId'		=> $post_id,
						'tierId'		=> $tier_data->fire_tier_id,
						'tierPacket'	=> $tier_data
					)
				);
				ym_fire_sale_start($tier_data->fire_id);
			}
			// check cost update if needed
			if ($base_price != $tier_data->fire_price) {
				if ($tier_data) {
					update_post_meta($post_id, '_ym_post_purchasable_cost', $tier_data->fire_price);
					update_post_meta($post_id, '_ym_fire_active', TRUE);
					ym_firesale_log(
						array(
							'doing'			=> 'TierChange',
							'postId'		=> $post_id,
							'newPrice'		=> $tier_data->fire_price,
							'currentPrice'	=> $base_price,
							'tierId'		=> $tier_data->fire_tier_id,
							'tierPacket'	=> $tier_data,
						)
					);
					ym_firesale_tier_log($tier_data->fire_tier_id);
					ym_fire_tier_start($tier_data->fire_tier_id);
				} else {
					// no tier
					update_post_meta($post_id, '_ym_post_purchasable_cost', $fire_original_cost);
					delete_post_meta($post_id, '_ym_fire_original_cost');
					update_post_meta($post_id, '_ym_fire_active', false);
					ym_firesale_log(
						array(
							'doing'			=> 'EndPricingModel',
							'postId'		=> $post_id,
							'newPrice'		=> $fire_original_cost,
							'currentPrice'	=> $base_price,
							'tierId'		=> $tier_data->fire_tier_id,
							'tierPacket'	=> $tier_data
						)
					);
				}
			}
		} else {
			if ($is_currently_firesale == 1) {
				// firesale needs resetting as it has ended
				update_post_meta($post_id, '_ym_post_purchasable_cost', $fire_original_cost);
				delete_post_meta($post_id, '_ym_fire_original_cost');
				update_post_meta($post_id, '_ym_fire_active', false);
				ym_firesale_log(
					array(
						'doing'			=> 'EndPricingModel',
						'postId'		=> $post_id,
						'newPrice'		=> $fire_original_cost,
						'currentPrice'	=> $base_price,
					)
				);
			}
		}
	}
	return($button_code);
}

function ym_fire_sales_ppp_since($time, $post_id) {
	global $wpdb;
	
	$sql = 'SELECT count(id) AS total_sales FROM ' . $wpdb->prefix . 'posts_purchased WHERE post_id = ' . $post_id . ' AND unixtime >= ' . $time;
	return $wpdb->get_var($sql);
}

// legacy functions
function ym_get_all_ppp_posts() {
	$args = array(
		'numberposts'	=> -1,
		'post_type'		=> 'any',

		'meta_key'		=> '_ym_post_purchasable',
		'meta_value'	=> '1',
	);
	$posts = get_posts($args);
	return $posts;
}
