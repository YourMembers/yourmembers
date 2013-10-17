<?php

function ym_firesale_ppp_packs() {
	global $wpdb;
	
	// do all packs!
	foreach (ym_get_bundles() AS $pack) {
		$pack_id = $pack->id;
		
		// get base data from the table
		$sql = 'SELECT original_cost FROM ' . $wpdb->ym_app_ppp_pack . '
			WHERE pack_id = ' . $pack_id;
		$fire_original_cost = $wpdb->get_var($sql);
		
		$base_cost = number_format($pack->cost, 2);
		$fire_original_cost = $fire_original_cost ? $fire_original_cost : 0;
		$is_currently_firesale = $fire_original_cost ? TRUE : FALSE;
		
		if ($ym_firesale_id = ym_firesale_exists($pack_id, YM_APP_TYPE_PACK)) {
			// a fire sale exists
			$tier_data = ym_firesale_get_current_tier($ym_firesale_id);
			$tier_data = $tier_data[0];
			
			if (!$fire_original_cost) {
				// starting a firesale
				$sql = 'INSERT INTO ' . $wpdb->ym_app_ppp_pack . '(pack_id, original_cost) VALUES (' . $pack_id . ', ' . $fire_original_cost . ')';
				$wpdb->query($sql);
				ym_firesale_log(
					array(
						'doing'			=> 'StartingPricingModel',
						'packId'		=> $pack_id,
						'tierId'		=> $tier_data->fire_tier_id,
						'tierPacket'	=> $tier_data
					)
				);
				ym_fire_sale_start($tier_data->fire_id);
			}
			// cost update
			if ($base_price != $tier_data->fire_price) {
				if ($tier_data) {
					// packs stores in pence/cents base unit
					$pack->cost = str_replace('.', '', $tier_data->fire_price);
					ym_firesale_log(
						array(
							'doing'			=> 'TierChange',
							'packId'		=> $pack_id,
							'newPrice'		=> $tier_data->fire_price,
							'currentPrice'	=> $base_price,
							'tierId'		=> $tier_data->fire_tier_id,
							'tierPacket'	=> $tier_data
						)
					);
					ym_firesale_tier_log($tier_data->fire_tier_id);
					ym_fire_tier_start($tier_data->fire_tier_id);
				} else {
					// no tier
					$pack->cost = str_replace('.', '', $fire_original_cost);
					$sql = 'DELETE FROM ' . $wpdb->ym_app_ppp_pack . ' WHERE pack_id = ' . $pack_id;
					$wpdb->query($sql);
					ym_firesale_log(
						array(
							'doing'			=> 'EndPricingModel',
							'packId'		=> $pack_id,
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
				// firesale needs resetting
				$pack->cost = $fire_original_cost;
				ym_firesale_log(
					array(
						'doing'			=> 'EndPricingModel',
						'packId'		=> $pack_id,
						'newPrice'		=> $fire_original_cost,
						'currentPrice'	=> $base_price,
					)
				);
			}
		}
		// update the ppp pack
//		$all_packs[$key] = $pack;
		$pack->cost = number_format($pack->cost, 2, '.', '') * 100;//store in pence
		$sql = 'UPDATE ' . $wpdb->prefix . 'ym_post_pack SET cost = ' . $pack->cost . ' WHERE id = ' . $pack->id;
		$wpdb->query($sql);
	}
}

function ym_fire_sales_ppp_packs_since($time, $pack_id) {
	global $wpdb;
	
	$sql = 'SELECT count(id) AS total_sales FROM ' . $wpdb->prefix . 'ym_transaction WHERE action_id = ' . YM_PPP_PACK_PURCHASED . ' AND data = ' . $pack_id . ' AND unixtime >= ' . $time;
	return $wpdb->get_var($sql);
}
