<?php

function ym_firesale_subs() {
	// get all subs
	$packs = ym_get_packs();

	foreach ($packs as $key => $pack) {
		$pack_id = $pack['id'];
		
		$base_price = $pack['cost'];
		$fire_original_cost = isset($pack['pre_firesale_cost']) ? $pack['pre_firesale_cost'] : FALSE;
		$is_currently_firesale = isset($pack['on_fire_sale']) ? $pack['on_fire_sale'] : FALSE;
		
		if ($ym_firesale_id = ym_firesale_exists($pack_id, YM_APP_TYPE_SUB)) {
			// a fire sale exists for this pack
			$tier_data = ym_firesale_get_current_tier($ym_firesale_id);
			$tier_data = $tier_data[0];
			
			if (!$fire_original_cost) {
				// starting a fire sale
				$pack['pre_firesale_cost'] = $base_price;
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
			// check for tier change
			if ($base_price != $tier_data->fire_price) {
				if ($tier_data) {
					$pack['cost'] = $tier_data->fire_price;
					$pack['on_fire_sale'] = TRUE;
					ym_firesale_log(
						array(
							'doing'			=> 'TierChange',
							'packId'		=> $pack_id,
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
					$pack['cost'] = $fire_original_cost;
					unset($pack['pre_firesale_cost']);
					unset($pack['on_fire_sale']);
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
				// firesale needs resetting as it has ended
				$pack['cost'] = $fire_original_cost;
				unset($pack['pre_firesale_cost']);
				unset($pack['on_fire_sale']);
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
		// store the pack
		$packs[$key] = $pack;
	}
	// store the packs
	$obj_packs = get_option('ym_packs');
	$obj_packs->packs = $packs;
	update_option('ym_packs', $obj_packs);
}

function ym_fire_sales_subscription_since($time, $pack_id) {
	global $wpdb;
	
	$pack_type = ym_get_pack($pack_id);
	$pack_type = $pack_type['account_type'];
	
	$sql = 'SELECT count(id) AS total_sales FROM ' . $wpdb->prefix . 'ym_transaction WHERE action_id = ' . YM_ACCOUNT_TYPE_ASSIGNATION . ' AND data = \'' . $pack_type . '\' AND unixtime >= ' . $time;
	return $wpdb->get_var($sql);
}
