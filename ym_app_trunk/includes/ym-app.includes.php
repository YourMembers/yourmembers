<?php

function ym_firesale_maintain_tiers() {
	global $wpdb;
	
	// both the next two foreachs make the assumption that two sales based tiers
	// won't invalidate at the same moment for a individual fire sale
	
	// do sales based ppp
	$fires = ym_firesale_get_all_enabled(false, YM_APP_TYPE_POST);
	// get each fire current tier
	foreach ($fires as $fire) {
		$sql = 'SELECT fire_tier_id, fire_limit_by, fire_limit_var, fire_tier_started, fire_type_id
			FROM ' . $wpdb->ym_app_models_tiers . ' t
			LEFT JOIN ' . $wpdb->ym_app_models . ' f
			ON f.fire_id = t.fire_id
			WHERE t.fire_id = ' . $fire->fire_id . '
			ORDER BY fire_order ASC LIMIT 1';
		$tier = $wpdb->get_results($sql);
		// one result
		if ($tier = $tier[0]) {
			$tier_id	= $tier->fire_tier_id;
			$limit		= $tier->fire_limit_var;
			$started	= $tier->fire_tier_started;
			// post id
			$post_id	= $tier->fire_type_id;
			
			// sales since
			$sales = ym_fire_sales_ppp_since($started, $post_id);
			if ($sales >= $limit) {
				ym_fire_tier_end($tier_id);
			}
		}
	}
	
	// do sales base ppp packs
	$fires = ym_firesale_get_all_enabled(false, YM_APP_TYPE_POST);
	foreach ($fires as $fire) {
		$sql = 'SELECT fire_tier_id, fire_limit_by, fire_limit_var, fire_tier_started, fire_type_id
			FROM ' . $wpdb->ym_app_models_tiers . ' t
			LEFT JOIN ' . $wpdb->ym_app_models . ' f
			ON f.fire_id = t.fire_id
			WHERE t.fire_id = ' . $fire->fire_id . '
			ORDER BY fire_order ASC LIMIT 1';
		$tier = $wpdb->get_results($sql);
		if ($tier = $tier[0]) {
			$tier_id	= $tier->fire_tier_id;
			$limit		= $tier->fire_limit_var;
			$started	= $tier->fire_tier_started;
			// pack id
			$pack_id	= $tier->fire_type_id;
			
			$sales = ym_fire_sales_ppp_packs_since($started, $pack_id);
			if ($sales >= $limit) {
				ym_fire_tier_end($tier_id);
			}
		}
	}
	
	// do sales based subs
	$fires = ym_firesale_get_all_enabled(false, YM_APP_TYPE_SUB);
	foreach ($fires as $fire) {
		$sql = 'SELECT fire_tier_id, fire_limit_by, fire_limit_var, fire_tier_started, fire_type_id
			FROM ' . $wpdb->ym_app_models_tiers . ' t
			LEFT JOIN ' . $wpdb->ym_app_models . ' f
			ON f.fire_id = t.fire_id
			WHERE t.fire_id = ' . $fire->fire_id . '
			ORDER BY fire_order ASC LIMIT 1';
		$tier = $wpdb->get_results($sql);
		if ($tier = $tier[0]) {
			$tier_id	= $tier->fire_tier_id;
			$limit		= $tier->fire_limit_var;
			$started	= $tier->fire_tier_started;
			// subscription id
			$subs_id	= $tier->fire_type_id;
			
			$sales = ym_fire_sales_subscription_since($started, $subs_id);
			if ($sales >= $limit) {
				ym_fire_tier_end($tier_id);
			}
		}
	}
	
	// do time based kills all tiers
	// both subs and sales based
	$tiers = ym_firesale_get_all_tiers();
	foreach ($tiers as $tier) {
		if ($tier->fire_limit_by == 1) {
			// its a time based type
			if ($tier->fire_limit_var < time()) {
				ym_fire_tier_end($tier->fire_tier_id);
			}
		}
	}
	
	// firesales without tiers
	// this is a garbage collection function
	// 
	// even tho the other end scripts will kill a firesale
	// they wont delete from the db
	// so they dont call sale end like we do here!
	//
	$fires = ym_firesale_get_all();
	foreach ($fires as $fire) {
		if ($fire->tiers == 0 && $fire->fire_id) {
			ym_fire_sale_end($fire->fire_id);
		}
	}
}

// logging
function ym_firesale_log($packet) {
	@ym_log_transaction(YM_APP, serialize($packet), 1);
}
function ym_firesale_tier_log($tier_id) {
	@ym_log_transaction(YM_APP_TIERCHANGE, $tier_id, 1);
}

function ym_fire_sale_start($fire_id) {
	do_action('ym_app_sale_start', $fire_id);
}

function ym_fire_tier_start($tier_id) {
	global $wpdb;
	$wpdb->query('UPDATE ' . $wpdb->ym_app_models_tiers . ' SET fire_tier_started = ' . time() . ' WHERE fire_tier_id = ' . $tier_id);
	// is this tier of type 2?
	$sql = 'SELECT fire_limit_var FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_tier_id = ' . $tier_id . ' AND fire_limit_by = 2';
	if ($hours = $wpdb->get_var($sql)) {
		// it is type 2
		$ends = time() + (3600 * $hours);
		$wpdb->query('UPDATE ' . $wpdb->ym_app_models_tiers . ' SET fire_limit_by = 1, fire_limit_var = ' . $ends . ' WHERE fire_tier_id = ' . $tier_id);
	}
	
	do_action('ym_app_tier_start', $tier_id);
}
function ym_fire_tier_end($tier_id) {
	global $wpdb;

	$sql = 'DELETE FROM ' . $wpdb->ym_app_models_tiers . ' WHERE fire_tier_id = ' . $tier_id;
	$wpdb->query($sql);

	
	/*
	* tier
	$sql = 'SELECT fire_tier_option, fire_type_id
		FROM ' . $wpdb->ym_app_models_tiers . ' t
		LEFT JOIN ' . $wpdb->ym_app_models . ' f
		ON f.fire_id = t.fire_id
		WHERE fire_tier_id = ' . $tier_id . '
		AND fire_type = 0';
	$option = $wpdb->get_var($sql);
	if ($option) {
		// if 1 we need to kill the post from being purchaseable
		$post_id = $wpdb->get_var($sql, 1);
		// take post off sale
		update_post_meta($post_id, '_ym_post_purchasable', '0');
	}
	*/
	
	do_action('ym_app_tier_end', $tier_id);
}
function ym_fire_sale_end($fire_id) {
	global $wpdb;

	$sql = 'SELECT fire_end_option, fire_type_id FROM ' . $wpdb->ym_app_models . ' WHERE fire_id = ' . $fire_id;
	$option = $wpdb->get_var($sql);
	if ($option) {
		// if 1 we need to kill the post from being purchaseable
		$post_id = $wpdb->get_var($sql, 1);
		// take post off sale
		update_post_meta($post_id, '_ym_post_purchasable', '0');
	}
	
	$sql = 'DELETE FROM ' . $wpdb->ym_app_models . ' WHERE fire_id = ' . $fire_id;
	$wpdb->query($sql);
	
	do_action('ym_app_sale_end', $fire_id);
}




function ym_firesale_get_all($with_tiers = FALSE, $type = FALSE) {
	global $wpdb;
	if ($with_tiers) {
		$sql = 'SELECT *, f.fire_id AS fire_id
			FROM ' . $wpdb->ym_app_models . ' f
			LEFT JOIN ' . $wpdb->ym_app_models_tiers . ' t
			ON t.fire_id = f.fire_id';
			if ($type) {
				$sql .= ' WHERE fire_type = ' . $type;
			}
			$sql .= ' ORDER BY fire_enable DESC, f.fire_id ASC';
	} else {
		$sql = 'SELECT *, count(t.fire_tier_id) AS tiers, f.fire_id AS fire_id
			FROM ' . $wpdb->ym_app_models . ' f
			LEFT JOIN ' . $wpdb->ym_app_models_tiers . ' t
			ON t.fire_id = f.fire_id';
			if ($type) {
				$sql .= ' WHERE fire_type = ' . $type;
			}
			$sql .= ' GROUP BY f.fire_id
			ORDER BY fire_enable DESC, f.fire_id ASC';
	}
	return $wpdb->get_results($sql);
}

function ym_firesale_get_all_enabled($with_tiers = FALSE, $type = FALSE) {
	global $wpdb;
	if ($with_tiers) {
		$sql = 'SELECT *, f.fire_id AS fire_id
			FROM ' . $wpdb->ym_app_models . ' f
			LEFT JOIN ' . $wpdb->ym_app_models_tiers . ' t
			ON t.fire_id = f.fire_id
			WHERE fire_enable = 1';
			if ($type) {
				$sql .= ' AND fire_type = ' . $type;
			}
			$sql .= ' ORDER BY f.fire_id ASC';
	} else {
		$sql = 'SELECT *, count(t.fire_tier_id) AS tiers, f.fire_id AS fire_id
			FROM ' . $wpdb->ym_app_models . ' f
			LEFT JOIN ' . $wpdb->ym_app_models_tiers . ' t
			ON t.fire_id = f.fire_id
			WHERE fire_enable = 1';
			if ($type) {
				$sql .= ' AND fire_type = ' . $type;
			}
			$sql .= ' GROUP BY f.fire_id
			ORDER BY f.fire_id ASC';
	}
	return $wpdb->get_results($sql);
}
function ym_firesale_get_single($id) {
	global $wpdb;
	$sql = 'SELECT * FROM ' . $wpdb->ym_app_models . ' WHERE fire_id = ' . $id;
	$r = $wpdb->get_results($sql);
	return $r[0];
}

function ym_firesale_exists($fire_type_id, $fire_type) {
	global $wpdb;
	$sql = 'SELECT fire_id
		FROM ' . $wpdb->ym_app_models . '
		WHERE fire_type = ' . $fire_type . '
		AND fire_type_id = ' . $fire_type_id . '
		AND fire_enable = 1';
	return $wpdb->get_var($sql);
}

function ym_firesale_get_all_tiers($firesale_id = false) {
	global $wpdb;
	// get all tiers
	$sql = 'SELECT *
		FROM ' . $wpdb->ym_app_models_tiers;
	if ($firesale_id) {
		$sql .= ' WHERE fire_id = ' . $firesale_id . ' ORDER BY fire_order ASC';
	}
	return $wpdb->get_results($sql);
}
function ym_firesale_get_current_tier($firesale_id) {
	global $wpdb;
	$sql = 'SELECT *
		FROM ' . $wpdb->ym_app_models_tiers . '
		WHERE fire_id = ' . $firesale_id . '
		ORDER BY fire_order ASC LIMIT 1';
	return $wpdb->get_results($sql);
}
