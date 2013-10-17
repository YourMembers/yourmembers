<?php

/*
* $Id: ym-packs.class.php 2467 2012-12-06 15:50:33Z bcarlyon $
* $Revision: 2467 $
* $Date: 2012-12-06 15:50:33 +0000 (Thu, 06 Dec 2012) $
*/

class YourMember_Packs {
	var $packs;

	function initialise() {
		// ym install in progress
		$new_packs = array(
			array(
				'id'=>1,
				'admin_name'=>'',
				'trial_on'=>0,
				'trial_cost'=>0.00,
				'trial_duration'=>0,
				'trial_duration_type'=>'d',
				'cost'=>0.00,
				'role'=>'subscriber',
				'default'=>0,
				'duration'=>10,
				'duration_type'=>'y',
				'country'=>'',
				'currency'=>'',
				'success_redirect'=>'',
				'first_login'=>'',
				'description'=>'',
				'num_cycles'=>'',
				'product_id'=>'',
				'zombaio_price_id'=>'',
				'account_type'=>'free',
				'hide_old_content'=>0,
				'hide_subscription'=>0,
				'gateway_disable'=>array(),
				'login_redirect_url'=>'',
				'wpadmin_disable_redirect_url'=>'',
				'logout_redirect_url'=>'',

				'child_accounts_allowed'=>0,
				'child_accounts_package_types'=>array(),
				'child_accounts_packages'=>array(),

				'hide_admin_bar'=>false,
			),
			array(
				'id'=>2,
				'admin_name'=>'',
				'trial_on'=>0,
				'trial_cost'=>0.00,
				'trial_duration'=>0,
				'trial_duration_type'=>'d',
				'cost'=>5.00,
				'role'=>'subscriber',
				'default'=>0,
				'duration'=>3,
				'duration_type'=>'m',
				'country'=>'',
				'currency'=>'',
				'success_redirect'=>'',
				'first_login'=>'',
				'description'=>'',
				'num_cycles'=>'',
				'product_id'=>'',
				'zombaio_price_id'=>'',
				'account_type'=>'member',
				'hide_old_content'=>0,
				'hide_subscription'=>0,
				'gateway_disable'=>array(),
				'login_redirect_url'=>'',
				'wpadmin_disable_redirect_url'=>'',
				'logout_redirect_url'=>'',

				'child_accounts_allowed'=>0,
				'child_accounts_package_types'=>array(),
				'child_accounts_packages'=>array(),

				'hide_admin_bar'=>false,
			)
		);

		$packs = new YourMember_Packs();
		$packs->packs = $new_packs;
		add_option('ym_packs', $packs);
	}

	function update($vars) {
		$this->packs = $vars;
	}
}

/*
class YourMember_Pack {
	var $id;
	var $admin_name;
	var $description;
	var $hide_subscription;

	var $trial_on, $trial_cost, $trial_duration, $trial_duration_type;

	var $cost, $duration, $duration_type, $num_cycles;

	var $account_type, $role;

	var $hide_old_content;

	var $success_redirect, $login_redirect_url, $wpadmin_disable_redirect_url, $logout_redirect_url

	var $child_accounts_allowed, $child_accounts_package_types, $child_accounts_packages;

	function __construct($pack) {
		if (!is_array($pack)) {
			$pack =ym_get_pack_by_id($pack);
		}
		foreach ($pack as $i => $v) {
			$this->$i = $v;
		}
	}
}
*/

function ym_get_packs() { 
	global $ym_packs;
	if (isset($ym_packs->packs)) {
		return $ym_packs->packs;
	} else {
		return new YourMember_Packs();
	}
}

function ym_get_pack_by_id($id) {
	foreach (ym_get_packs() as $pack) {
		if ($pack['id'] == $id) {
			return $pack;
		}
	}
	return FALSE;
}

function ym_get_pack_label($pack, $cost_override=false, $admin = TRUE) {
	global $duration_str, $ym_packs;

	if (!is_array($pack)) {
		$pack_id = $pack;
		$pack_data = FALSE;
		$packs = $ym_packs->packs;
		foreach ($packs as $pack) {
			if ($pack['id'] == $pack_id) {
				$pack_data = $pack;
				break;
			}
		}
		if ($pack_data) {
			$pack = $pack_data;
		} else {
			return '';
		}
	}

	if ($admin && is_admin() && (isset($pack['admin_name']) && $pack['admin_name'])) {
		return $pack['admin_name'];
	}

	$cost = $pack['cost'];
	if ($cost_override) {
		$cost = $cost_override;
	}
	// format for diaply
	$cost = number_format($cost, 2, '.', ',');

	$dur_type = $duration_str[$pack['duration_type']];
	$dur_str = ($pack['duration'] == 1 ? rtrim($dur_type, 's'):$dur_type);

	return ym_get_pack_string(ucwords($pack['account_type']), $cost, ym_get_currency($pack['id']), $pack['duration'], $dur_str, @$pack['num_cycles'], $pack['trial_on'], $pack['trial_cost'], $pack['trial_duration'], $pack['trial_duration_type'], $pack['description']);
}

function ym_get_pack_string($ac_type, $cost, $currency, $duration, $dur_str, $num_cycles=false, $trial_on=false, $trial_cost=false, $trial_duration=false, $trial_duration_type=false, $description=false) {
	global $duration_str, $ym_res;
	
	$string = false;
	
	if (!$cost && strtolower($ac_type) == 'free') {
		$string = __('Free Account', 'ym');
	} else {
		$dur_str = rtrim($dur_str, 's');
		if ($duration != 1) {
			$dur_str .= 's';
		}
		
		$trial_dur_str = 's';
		if ($trial_duration_type) {
			$trial_dur_str = rtrim($duration_str[$trial_duration_type], 's');
			if ($trial_duration != 1) {
				$trial_dur_str .= 's';
			}
		}
		
		if (!$cost) {
			$cost = __('Free', 'ym');
			$currency = '';
			$cost_units = $cost;
			$trial_cost_units = $trial_cost;
		} else {
			$cost_units = number_format($cost, 0);
			$trial_cost_units = number_format($trial_cost, 0);
		}
	
		if ($template = $ym_res->pack_string_template) {
			$string = str_replace('[account_type]', $ac_type, $template);
			$string = str_replace('[currency]', $currency, $string);
			$string = str_replace('[cost]', $cost, $string);
			$string = str_replace('[cost_units]', $cost_units, $string);
			$string = str_replace('[duration]', $duration, $string);
			$string = str_replace('[duration_period]', $dur_str, $string);
			$string = str_replace('[num_cycles]', $num_cycles, $string);
			$string = str_replace('[trial_cost]', $trial_cost, $string);
			$string = str_replace('[trial_cost_units]', $trial_cost_units, $string);
			$string = str_replace('[trial_duration]', $trial_duration, $string);
			$string = str_replace('[trial_duration_period]', $trial_dur_str, $string);
			$string = str_replace('[description]', $description, $string);
	
			if ($num_cycles) {
				$string = preg_replace("'\[/?\s?if_num_cycles\s?\]'i",'', $string);
			} else {
				$string = preg_replace("'\[if_num_cycles\s?\](.*)\[/if_num_cycles\s?\]'i",'', $string);
			}
	
			if ($trial_on) {
				$string = preg_replace("'\[/?\s?if_trial_on\s?\]'i",'', $string);
			} else {
				$string = preg_replace("'\[if_trial_on\s?\](.*)\[/if_trial_on\s?\]'i",'', $string);
			}
		}
	
		if (!$string) {
			$string = $ac_type . ' - ' . $cost . ' ' . $currency . ' per ' . $duration . ' ' . $dur_str;
		}
	}

	return $string;
}

function ym_get_pack($id) {
	$return = false;
	$obj_packs = get_option('ym_packs');
	$packs = $obj_packs->packs;

	foreach ($packs as $i=>$pack) {
		if ($pack['id'] == $id) {
			$return = $pack;
			break;
		} else if ($pack['account_type'] == $id) {
			$return = $pack['id'];
			break;
		}
	}
	return $return;
}

/**
Tax Controller
Returns Tax Percentage
*/
function ym_get_pack_tax($pack) {
	if (is_numeric($pack)) {
		// got pack ID
		$pack = ym_get_pack_by_id($pack);
	}

	$tax = FALSE;
	global $ym_sys;

	if ((isset($pack['vat_applicable']) && $pack['vat_applicable']) || $ym_sys->global_vat_applicable) {
		if ($ym_sys->vat_rate) {
			$tax = $ym_sys->vat_rate;
		}
	}

	if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
		$tax = $vat_rate;
	}

	return $tax;
}
/*
function ym_get_pack_cost_with_tax($pack) {
	if (is_numeric($pack)) {
		// got pack ID
		$pack = ym_get_pack_by_id($pack);
	}
	$cost = $pack['cost'];

	if ($tax = ym_get_pack_tax($pack)) {
		$cost += $tax;
	}
	$cost = number_format($cost, 2, '.', '');

	return $cost;
}
*/
