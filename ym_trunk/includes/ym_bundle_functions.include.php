<?php

/*
* $Id: ym_bundle_functions.include.php 2480 2012-12-17 11:36:40Z tnash $
* $Revision: 2480 $
* $Date: 2012-12-17 11:36:40 +0000 (Mon, 17 Dec 2012) $
*/

/**

prefix ym_post_pack
id
name
cost
description
unixtime
additional
purchaseexpire // days viewable
purchaselimit // units available
saleend // off sale

cost is store in pence

*/

/**
save edit delete
*/
function ym_create_bundle($name, $description, $cost, $purchaseexpire, $purchaselimit, $endsale) {
	global $wpdb;

	$cost = ym_convert_to_currency($cost);

	$sql = 'SELECT id FROM ' . $wpdb->prefix . 'ym_post_pack WHERE name = \'' . $name . '\'';
	$wpdb->query($sql);
	if ($wpdb->num_rows) {
		echo '<div id="message" class="error"><p>' . sprintf(__('Bundle of name %s already exists', 'ym'), $name) . '</p></div>';
		return;
	}

	if ($endsale) {
		$endsale = strtotime($endsale);
	}

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_post_pack(name, description, cost, purchaseexpire, purchaselimit, saleend, unixtime) VALUES ("' . $name . '", "' . $description . '", "' . ($cost*100) . '", "' . $purchaseexpire . '", "' . $purchaselimit . '", "' . $endsale . '", UNIX_TIMESTAMP())';
	if ($wpdb->query($sql)) {
		ym_display_message(__('Successfully created new bundle: ', 'ym') . $name);
	}
	$ppp_id = $wpdb->insert_id;

	$has = FALSE;
	$additional = array();
	global $ym_active_modules;
	foreach ($ym_active_modules as $module) {
		$mod = new $module();
		if (method_exists($mod, 'additional_bundle_fields')) {
			$items = $mod->additional_bundle_fields();
			foreach ($items as $item) {
				$data = ym_post($item['name']);
				$additional[$item['name']] = $data;
				$has = TRUE;
			}
		}
	}
	if ($has) {
		$additional = serialize($additional);
		$sql = 'UPDATE ' . $wpdb->prefix . 'ym_post_pack SET additional = \'' . $additional . '\' WHERE id = ' . $ppp_id;
		$wpdb->query($sql);
		ym_display_message(__('Updated Bundle Additional Fields', 'ym'));
	}
}

function ym_edit_bundle($bundle_id, $name, $description, $cost, $purchaseexpire, $purchaselimit, $endsale) {
	global $wpdb;

	$cost = ym_convert_to_currency($cost);

	$query = 'SELECT name FROM ' . $wpdb->prefix . 'ym_post_pack WHERE name = \'' . $name . '\' AND id != ' . $bundle_id;
	$wpdb->query($query);
	if ($wpdb->num_rows) {
		ym_display_message(__('Could not updated bundle, new name in use', 'ym'));
		return;
	}

	if ($endsale) {
		$endsale = strtotime($endsale);
	}

	$query = 'UPDATE ' . $wpdb->prefix . 'ym_post_pack
		SET name = \'' . $name . '\',
			description = \'' . $description . '\',
			cost = \'' . ($cost * 100) . '\',
			purchaseexpire = \'' . $purchaseexpire . '\',
			purchaselimit = \'' . $purchaselimit . '\',
			saleend = \'' . $endsale . '\'
		WHERE id = ' . $bundle_id;
	if ($wpdb->query($query)) {
		ym_display_message(__('Successfully updated bundle: ', 'ym') . $name);
	}

	$has = FALSE;
	$additional = array();
	global $ym_active_modules;
	foreach ($ym_active_modules as $module) {
		$mod = new $module();
		if (method_exists($mod, 'additional_bundle_fields')) {
			$items = $mod->additional_bundle_fields();
			foreach ($items as $item) {
				$data = ym_post($item['name']);
				$additional[$item['name']] = $data;
				$has = TRUE;
			}
		}
	}

	if ($has) {
		$additional = serialize($additional);
		$sql = 'UPDATE ' . $wpdb->prefix . 'ym_post_pack SET additional = \'' . $additional . '\' WHERE id = ' . $bundle_id;
		$wpdb->query($sql);
		ym_display_message(__('Updated Bundle Additional Fields', 'ym'));
	}
}
function ym_delete_bundle($bundle_id) {
	global $wpdb;

	$query = 'DELETE FROM ' . $wpdb->prefix . 'ym_post_pack WHERE id = ' . $bundle_id;
	$wpdb->query($query);

	ym_display_message(__('Successfully deleted bundle of ID: ', 'ym') . $bundle_id);
}

/**
Bundle Update/create/delete function
*/
function ym_bundle_update() {
	if (ym_post('ym_do_bundle') && !ym_post('bundle_id', FALSE)) {
		ym_create_bundle(ym_post('name'), ym_post('description'), ym_post('cost'), ym_post('purchaseexpire'), ym_post('purchaselimit'), ym_post('saleend'));
	}
	
	if (ym_post('ym_do_bundle') && ym_post('bundle_id', FALSE)) {
		ym_edit_bundle(ym_post('bundle_id'), ym_post('name'), ym_post('description'), ym_post('cost'), ym_post('purchaseexpire'), ym_post('purchaselimit'), ym_post('saleend'));
	}
	
	if (ym_post('save_pack_post')) {
		$post_id = ym_post('post_id');
		$pack_id = ym_get('pack_id');
		
		if (!ym_add_post_to_bundle($post_id, $pack_id)) {
			ym_display_message(__('That post is already in that pack', 'ym'), 'error');
		}
	}
	
	if ($id = ym_get('delete_pack_post')) {
		if ($bundle_id = ym_get('pack_id')) {
			ym_remove_post_from_bundle($id, $bundle_id);
		}
	}
	
	if (ym_post('delete_bundle') && ym_post('bundle_id')) {
		ym_delete_bundle(ym_post('bundle_id'));
	}

	if (ym_post('bundle_to_gift')) {
		$bundle = $_POST['bundle_to_gift'];
		$user = $_POST['user_to_gift'];

		if (ym_gift_bundle($bundle, $user)) {
			echo '<div id="message" class="updated"><p>' . __('Bundle Gifting Complete', 'ym') . '</p></div>';
		} else {
			echo '<div id="message" class="error"><p>' . __('An error occured whilst gifting', 'ym') . '</p></div>';
		}
	}

	if (ym_post('delete_bundle_purchase')) {
		echo '<div id="message" class="';
		if (ym_remove_bundle_purchase(ym_post('delete_bundle_purchase'))) {
			echo 'updated"><p>' . __('Bundle Purchase removed', 'ym');
		} else {
			echo 'error"><p>' . __('Bundle Purchase could not be removed', 'ym');
		}
		echo '</p></div>';
	}
}

/**
// Get Bundle(s)
*/
function ym_get_bundles() {
	global $wpdb;
	
	$sql = 'SELECT id, name, description, unixtime, (cost/100) AS cost, additional, purchaseexpire, purchaselimit, saleend
			FROM ' . $wpdb->prefix . 'ym_post_pack
			ORDER BY name';
	$return = $wpdb->get_results($sql);
	
	return $return;
}

function ym_get_bundle($bundle_id) {
	global $wpdb;

	$return = new stdClass();
	$return->id = $return->name = $return->description = $return->unixtime = $return->additional = $return->purchaseexpire = $return->purchaselimit = $return->saleend = '';
	$return->cost = '0.00';

	if (!$bundle_id) {
		return $return;
	}

	$sql = 'SELECT id, name, description, unixtime, (cost/100) AS cost, additional, purchaseexpire, purchaselimit, saleend
			FROM ' . $wpdb->prefix . 'ym_post_pack
			WHERE id = ' . $bundle_id;
	$data = $wpdb->get_row($sql);
	if ($wpdb->num_rows) {
		return $data;
	}

	return $return;
}

/**
Purchase display and gift
*/
function ym_get_bundle_purchases() {
	global $wpdb;

//	$purchases = array();
	$query = 'SELECT *,
			yppp.id AS purchase_id,
			(cost/100) AS cost,
			yppp.unixtime AS purchasetime
		FROM ' . $wpdb->prefix . 'ym_post_packs_purchased yppp
		LEFT JOIN ' . $wpdb->prefix . 'ym_post_pack ypp ON ypp.id = yppp.pack_id
		LEFT JOIN ' . $wpdb->users . ' u ON u.ID = yppp.user_id';
//	foreach ($wpdb->get_results($query) as $row) {
//		$id = $row->pack_id;

//		$expire = $row->purchaseexpire;
//	}

//	return $purchases;
	return $wpdb->get_results($query);
}
function ym_remove_bundle_purchase($id) {
	global $wpdb;
	$query = 'DELETE FROM ' . $wpdb->prefix . 'ym_post_packs_purchased WHERE id = ' . $id;
	if ($wpdb->query($query)) {
		return TRUE;
	} else {
		return FALSE;
	}
}
function ym_gift_bundle($bundle, $user_id) {
	global $wpdb;
	if (ym_has_purchased_bundle($bundle, $user_id)) {
		return TRUE;
	} else {
		$query = 'INSERT INTO ' . $wpdb->prefix . 'ym_post_packs_purchased(pack_id, user_id, payment_method, unixtime) VALUES (' . $bundle . ', ' . $user_id . ', \'' . __('Gift', 'ym') . '\', ' . time() . ')';
		if ($wpdb->query($query)) {
			do_action('ym_post_gift_bundle',$bundle,$user_id);
			return TRUE;
		} else {
			return FALSE;
		}
	}
}

/**
Post/bundle association lookup
*/
// get posts in a bundle by id
function ym_get_bundle_posts($bundle_id) {
	global $wpdb;

	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc WHERE pack_id = ' . $bundle_id;

	return $wpdb->get_results($sql);
}

function ym_post_in_bundle($post_id, $bundle_id) {
	global $wpdb;

	$query = 'SELECT pack_id FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc
		WHERE pack_id = ' . $bundle_id . '
		AND post_id = ' . $post_id;
	$wpdb->query($query);
	return $wpdb->num_rows;
}

function ym_bundles_post_in($post_id) {
	global $wpdb;

	$query = 'SELECT pack_id FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc
		WHERE post_id = ' . $post_id;
	return $wpdb->get_results($query);
}

/**
Add/Remove Post from bundle
*/
function ym_add_post_to_bundle($post_id, $bundle_id) {
	global $wpdb;

	if (ym_post_in_bundle($post_id, $bundle_id)) {
		return FALSE;
	}

	$query = 'INSERT INTO ' . $wpdb->prefix . 'ym_post_pack_post_assoc(pack_id, post_id, unixtime)
		VALUES (' . $bundle_id . ', ' . $post_id . ', ' . time() . ')';
	$wpdb->query($query);
	return TRUE;
}
function ym_remove_post_from_bundle($post_id, $bundle_id) {
	global $wpdb;

	if (!ym_post_in_bundle($post_id, $bundle_id)) {
		return;
	}

	$query = 'DELETE FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc
		WHERE pack_id = ' . $bundle_id . ' AND post_id = ' . $post_id;
	$wpdb->query($query);
	return;
}

/**
Purchased a pack which the post is in
ym_has_purchased_a_bundle_that_the_post_is_in
*/
function ym_has_purchased_bundle_post_in($post_id, $user_id) {
	$bundles = ym_bundles_post_in($post_id);
	foreach ($bundles as $bundle_id) {
		$bundle_id = $bundle_id->pack_id;

		if (ym_has_purchased_bundle($bundle_id, $user_id)) {
			return TRUE;
		}
	}

	return FALSE;
}
/**
Purchased a bundle
*/
function ym_has_purchased_bundle($bundle_id, $user_id, $bypass_expiry = FALSE) {
	global $wpdb;

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_post_packs_purchased
		WHERE pack_id = ' . $bundle_id . '
		AND user_id = ' . $user_id;
	$rows = $wpdb->get_results($query);
	$count = count($rows);

	// check expiry
	$bundle = ym_get_bundle($bundle_id);
	if ($bundle->purchaseexpire && !$bypass_expiry) {
		$seconds = $bundle->purchaseexpire * 86400;// convert from days to seconds
		$expire = time() - $seconds;

		$count = FALSE;

		foreach ($rows as $row) {
			$time = $row->unixtime;
			if ($time > $expire) {
				// ok
				return TRUE;
			} else {
				$count = FALSE;
			}
		}
	}

	return $count;
}
/**
units and units left
*/
function ym_bundle_purchased_count($bundle_id) {
	global $wpdb;

	$query = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'ym_post_packs_purchased
		WHERE pack_id = ' . $bundle_id;
	return $wpdb->get_var($query);
}
function ym_bundle_available_count($bundle_id) {
	$bundle = ym_get_bundle($bundle_id);

	$limit = $bundle->purchaselimit;
	$purchased = ym_bundle_purchased_count($bundle_id);
	$left = $limit - $purchased;
	return $left;
}

/**
Start Shortcode
*/
function ym_buy_button_bundle($atts) {
	if (!is_user_logged_in()) {
		return '';
	}

	global $ym_sys, $ym_res, $ym_user;
	$user_id = $ym_user->ID;

	$bundle_id = isset($atts['bundle_id']) ? $atts['bundle_id'] : '';
	if (!$bundle_id) {
		return '';
	}

	// check if bundle exists
	$bundle = ym_get_bundle($bundle_id);
	if (!$bundle) {
		return '';
	}
	// check if bundle has content??
	// no allow a empty bundle

	$selected_gateways = isset($atts['gateways']) ? explode('|', $atts['gateways']) : array();
	$hidecoupon = isset($atts['hidecoupon']) ? $atts['hidecoupon'] : FALSE;
	$list_contents = isset($atts['list_contents']) ? $atts['list_contents'] : FALSE;
	$hide_purchased = isset($atts['hide_purchased']) ? $atts['hide_purchased'] : TRUE;
	$redirect_post = isset($atts['redirect']) ? $atts['redirect'] : FALSE;

	// check if purcahsed
	if (ym_has_purchased_bundle($bundle_id, $user_id)) {
		if ($hide_purchased) {
			return '';
		} else {
			$r = '<p>' . __('You have purchased: ', 'ym') . $bundle->name . '</p>';
			$r .= 'list contents is ' . $list_contents;
			if ($list_contents) {
				// show post titles of contents
				$posts = ym_get_bundle_posts($bundle_id);
				$r .= '<ul>';
				foreach ($posts as $post) {
					$r .= '<li>';
					$r .= '<a href="' . get_permalink($post->post_id) . '">';
					$r .= get_the_title($post->post_id);
					$r .= '</a>';
					$r .= '</li>';
				}
				$r .= '</ul>';
			}

			return $r;
		}
	}
	// check if stock left
	$limit = $bundle->purchaselimit;
	$left = ym_bundle_available_count($bundle_id);
	if ($limit && ($left <= 0)) {
		$r = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->purchasable_bundle_at_limit . $ym_res->msg_footer . '</div>';
		return $r;
	}

	$cost = $bundle->cost;
//	$cost = number_format($cost, 2);

	// get here
	$url = get_permalink();
	if (isset($_POST['ym_buy_button_bundle'])) {
		$r = '<p>' . sprintf(__('You are purchasing bundle: %s', 'ym'), $bundle->name) . '</p>
<form action="" method="post" class="ym_buy_button_bundle">
	<input type="hidden" name="ym_buy_button_bundle" value="1" />
	<input type="hidden" name="ym_buy_button_args" value=\'' . json_encode($atts) . '\' />
	<table>
		<tr><td>' . __('Purchasing: ', 'ym') . ' ' . $bundle->name . '</td><td>' . ym_get_currency() . ' ' . number_format($cost, 2) . '</td></tr>
		';

		$show_coupon = $hidecoupon ? FALSE : TRUE;
		$final_price = FALSE;
		if (isset($_POST['ym_buy_button_bundle_coupon'])) {
			$coupon = $_POST['ym_buy_button_bundle_coupon'];
			// validate
			if (FALSE !== ($value = ym_validate_coupon($coupon, 3))) {
				$type = ym_get_coupon_type($value);
				$r .= '<tr><td>' . sprintf(__('Valid Coupon Supplied: %s', 'ym'), $coupon) . '</td><td>';
 
				if ($type == 'sub_pack') {
					$r .= __('However it is invalid for a Bundle Purchase', 'ym');
				} else if ($type == 'percent') {
					$r .= __('Value: ', 'ym') . $value;
					$final_price = $cost - ((substr($value, 0, -1) / 100) * $cost);
				} else {
					$r .= __('Fixed Price: ', 'ym') . $value;
					$final_price = $cost - $value;
				}

				if (FALSE !== $final_price) {
					$show_coupon = FALSE;
					$r .= '</td></tr><tr><td>' . __('Cost after Coupon', 'ym') . '</td><td>' . number_format($final_price, 2);
					ym_register_coupon_use($coupon, $user_id, 'buy_bundle_' . $bundle_id . '_' . $user_id);
				}

				$r .= '</td></tr>';
			} else {
				$r .= '<tr><td></td><td>' . sprintf(__('Invalid Coupon Supplied: %s', 'ym'), $coupon) . '</td></tr>';
			}
		}
		if ($show_coupon) {
			$r .= '<tr><td>' . __('Apply Coupon', 'ym') . '</td><td><input type="text" name="ym_buy_button_bundle_coupon" value="" /><input type="submit" name="ym_buy_button_bundle_apply_coupon" value="' . __('Apply Coupon', 'ym') . '" /></td></tr>';
		}

		if (FALSE === $final_price) {
			$final_price = $cost;
		}
		//Add some logic check in case the discount reduces below 0
		if($final_price < 0){
			$final_price = 0;
		}

		$r .= '
	<tr><td>' . __('Tax', 'ym') . '</td><td>';

		$vat = FALSE;
		if ($ym_sys->global_vat_applicable) {
			if ($ym_sys->vat_rate) {
				$vat = $ym_sys->vat_rate;
			}
		}
		if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
			$vat = $vat_rate;
		}

		$cost_with_vat = $final_price;
		if ($vat) {
			$r .= $vat . '%';
			$cost_with_vat = (($vat / 100) * $final_price) + $final_price;
		} else {
			$r .= __('None', 'ym');
			$cost_with_vat = $final_price;
		}
		$cost_with_vat = number_format($cost_with_vat, 2);

		$r .= '</td></tr>
		<tr><td>' . __('Total Cost', 'ym') . '</td><td>' . ym_get_currency() . ' ' . $cost_with_vat . '</td></tr>
	</table>
</form>';

		$test = $cost_with_vat * 100;
		if ($test == 0) {
			// free
			require_once(YM_MODULES_DIR . 'ym_free.php');
			$obj = new ym_free();
			$r .= $obj->free_purchase('bundle', $bundle_id, $user_id);
			return $r;
		}

		//format
		$final_price = number_format($final_price, 2);

		// gateways
		global $ym_active_modules;

		if (sizeof($selected_gateways)) {
			$gateways = $selected_gateways;
		} else {
			$gateways = $ym_active_modules;
		}

		foreach ($gateways as $gateway) {
			if (in_array($gateway, $ym_active_modules)) {
				$obj = new $gateway();
				if (method_exists($obj, 'pay_per_post_bundle')) {
					$r .= $obj->gen_buy_ppp_pack_button($final_price, $bundle_id, $bundle->name, TRUE);
				}
			}
		}

		return $r;
	}

	$r = '
<form action="' . $url . '" method="post" class="ym_buy_button_bundle_buynow">
	<fieldset>
		<p>' . __('Purchase Bundle:', 'ym') . ' ' . $bundle->name . ' - ' . __('Cost:', 'ym') . ' ' . ym_get_currency() . ' ' . number_format($cost, 2) . '</p>
		';

		if ($list_contents) {
			// show post titles of contents
			$posts = ym_get_bundle_posts($bundle->id);
			$r .= '<ul>';
			foreach ($posts as $post) {
				$r .= '<li>' . get_the_title($post->post_id) . '</li>';
			}
			$r .= '</ul>';
		}

		$r .= '
		<input type="hidden" name="ym_buy_button_args" value=\'' . json_encode($atts) . '\' />
		<input type="submit" name="ym_buy_button_bundle" value="' . __('Buy Now', 'ym') . '" />
	</fieldset>
</form>
';
	return $r;
}

function ym_buy_button_bundle_filter($content) {
	if (!is_user_logged_in()) {
		return $content;
	}

	if (isset($_POST['ym_buy_button_bundle']) && isset($_POST['ym_buy_button_args'])) {
		$code = '[ym_buy_bundle ';
		$args = json_decode(stripslashes($_POST['ym_buy_button_args']));
		if ($args) {
			foreach ($args as $name => $val) {
				$code .= $name . '="' . $val . '" ';
			}
		}
		$code .= ' ]';
		return $code;
	}

	return $content;
}

/**
Admin Form
*/

function ym_bundle_form($bundle, $submit) {
	echo '
<form action="" method="post">
	<input type="hidden" name="bundle_id" value="' . $bundle->id . '" />
<table style="width: 100%;">
	<tr><th>Bundle Name:</th><td><input type="text" name="name" value="' . $bundle->name . '" /></td></tr>
	<tr><th>Bundle Cost:</th><td><input type="text" name="cost" value="' . number_format($bundle->cost, 2) . '" size="6" /> ' . ym_get_currency() . '</td></tr>
	<tr><th>Bundle Description:</th><td><input type="text" name="description" value="' . $bundle->description . '" /></td></tr>

	<tr><th>' . __('Buyer can see Bundle contents for, blank for forever', 'ym') . '</th><td><input type="text" name="purchaseexpire" size="2" value="' . $bundle->purchaseexpire . '" /> ' . __('Day(s)', 'ym') . '</td></tr>
	<tr><th>' . __('Number of Bundles Available, blank for unlimited', 'ym') . '</th><td><input type="text" name="purchaselimit" size="2" value="' . $bundle->purchaselimit . '" /></td></tr>
	<tr><th>' . __('Sale End, date bundle stops being on sale', 'ym') . '</th><td><input type="text" name="saleend" size="8" id="saleend" class="ym_datepicker" value="' . ($bundle->saleend ? date(YM_DATE_ONLY, $bundle->saleend) : '') . '" /> <a href="#nowhere" onclick="ym_clear_target(\'saleend\')">' . __('Clear Date', 'ym') . '</a></td></tr>
';

	$data = unserialize($bundle->additional);
	global $ym_active_modules;
	foreach ($ym_active_modules as $module) {
		$mod = new $module();
		if (method_exists($mod, 'additional_bundle_fields')) {
			$items = $mod->additional_bundle_fields();
			foreach ($items as $item) {
				echo '<tr><th>' . $item['label'] . '</th><td><input type="text" name="' . $item['name'] . '" value="' . $data[$item['name']] . '" /></td></tr>';
			}
		}
	}

	echo '
	<tr><td></td><td><input class="button-primary" type="submit" name="ym_do_bundle" value="' . $submit . '" /></td></tr>
</table>
</form>
';
}

/**
Bundle Content Editor
*/
function ym_bundle_edit_content($bundle) {
	if (isset($_POST['ym_remove_post_id']) && $_POST['ym_remove_post_id']) {
		ym_remove_post_from_bundle($_POST['ym_remove_post_id'], $bundle->id);
		echo '<div id="message" class="updated"><p>' . __('Removed Post from Bundle', 'ym') . '</p></div>';
		ym_box_bottom();
		ym_box_top();
	}
	if (isset($_POST['add_posts']) && $_POST['add_posts']) {
		foreach ($_POST['post_ids'] as $id) {
			if (ym_add_post_to_bundle($id, $bundle->id)) {
				echo '<p>' . sprintf(__('Added post ID: %s to bundle ID: %s', 'ym'), $id, $bundle->id) . '</p>';
			}
		}
		ym_box_bottom();
		ym_box_top();
	}

	$posts = ym_get_bundle_posts($bundle->id);
	echo '<table style="width: 100%;">';
	$post_ids = array();
	foreach ($posts as $post) {
		$post_ids[] = $post->post_id;
		echo '<tr>';
		echo '<td>(' . $post->post_id . ') ' . get_the_title($post->post_id) . '</td>';
		echo '<td>
		<form action="" method="post">
			<input type="hidden" name="bundle_id" value="' . $bundle->id . '" />
			<input type="hidden" name="posts" value="1" />
			<input type="hidden" name="ym_remove_post_id" value="' . $post->post_id . '" />
			<input type="submit" class="button-secondary deletelink" value="' . __('Remove Post from Bundle', 'ym') . '" />
		</form>
		</td>';
		echo '</tr>';
	}
	echo '</table>';

	ym_box_bottom();
	ym_box_top(__('Add new Item(s) to bundle', 'ym'));

	echo '<form action="" method="post">
		<input type="hidden" name="bundle_id" value="' . $bundle->id . '" />
		<input type="hidden" name="posts" value="1" />
		<input type="hidden" name="add_posts" value="1" />
	<table id="ym_posts_space" style="width: 100%;">
	';

	$max = 15;

	$posts = ym_bundle_get_some_posts(0, $post_ids, $max);
	$total = count($posts);

	foreach ($posts as $post) {
		echo '<tr>';
		echo '<td>(' . $post->ID . ' - ' . $post->post_type . ') ' . $post->post_title . '</td>';
		echo '<td><input type="checkbox" name="post_ids[]" value="' . $post->ID . '" /></td>';
		echo '</tr>';
	}
	echo '
	</table>
	';
	if ($total == $max) {
		echo '<a href="#nowhere" id="ym_next_page" class="button-secondary" style="float: right;">' . __('Load More Posts', 'ym') . '</a>';
	}
	echo '
<input type="hidden" name="offset" id="offset" value="5" />
<input type="hidden" name="current_post_ids" id="current_post_ids" value="' . implode(',', $post_ids) . '" />
	<br />
<input type="submit" class="button-primary" value="' . __('Add Selected Item(s) to Bundle', 'ym') . '" />
	</form>

<script type="text/javascript">' . "
jQuery(document).ready(function() {
	jQuery('#ym_next_page').click(function() {
		var count = 0;
		jQuery(this).html('" . __('Loading...', 'ym') . "');
		jQuery.getJSON('" . YM_ADMIN_URL . "&ym_page=ym-content-bundles&do_munch=1&offset=' + jQuery('#offset').val() + '&post_ids=' + jQuery('#current_post_ids').val() + '&max=" . $max . "', function(data) {
				jQuery('#ym_posts_space input').each(function() {
					if (!jQuery(this).is(':checked')) {
						jQuery(this).parents('tr').remove();
					}
				});
				jQuery.each(data, function(key, val) {
					count++;
					jQuery(val).appendTo('#ym_posts_space');
				});
				if (count == " . $max . ") {
					jQuery('#ym_next_page').html('" . __('Load More Posts', 'ym') . "');
				} else {
					jQuery('#ym_next_page').remove();
				}
				jQuery('#offset').val(jQuery('#offset').val() + " . $max . ");
		});
	});
});
</script>
";
}

function ym_bundle_get_some_posts($offset, $post_ids, $max) {
	// this is wrong need to be posts that can be bought!!!!

	// this only grabs published posts......
	// http://codex.wordpress.org/Template_Tags/get_posts
	// get future posts too
	$args = array(
		'numberposts'	=> $max,
		'exclude'		=> $post_ids,
		'offset'		=> $offset,
		'post_type'		=> 'any',

		'meta_key'		=> '_ym_post_purchasable',
		'meta_value'	=> '1',
	);
	$posts = get_posts($args);

	return $posts;
}

/**
Render purchases
show_content is whether to show the bundle (false) or the bundle and the posts in it TRUE
*/
function ym_render_my_purchased_bundles($user_id, $show_expiries = FALSE, $show_content = TRUE, $with_snippets = TRUE) {
	global $wpdb;
	$html = '';

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_post_packs_purchased yppp
		LEFT JOIN ' . $wpdb->prefix . 'ym_post_pack ypp ON ypp.id = yppp.pack_id
		WHERE user_id = ' . $user_id;
	
	$results = $wpdb->get_results($query);

	if ($results) {
		$html .= '<table cellspacing="0" class="ym_ppp_page"><tr><th>' . __('Bundle Title', 'ym') . '</th>' . ($show_expiries ? '<th>' . __('Access Expiry', 'ym') . '</th>':'') . ($show_content ? '<th></th>' : '') . '</tr>';
	
		foreach ($results as $row) {
			$html .= '<tr><td>' . $row->name . '</td>' . ($show_expiries ? '<td>' . ym_get_bundle_expiry_date($row->pack_id, $user_id) . '</td>':'') . '</tr>';

			if ($show_content) {
				$posts = ym_get_bundle_posts($row->pack_id);

				$html .= '<tr><td></td><td colspan="4"><table><tr><th>' . __('Post Title', 'ym') . '</th><th>' . __('Published', 'ym') . '</th></tr>';

				foreach ($posts as $post) {
					$post = get_post($post->post_id);
					$link = get_permalink($post->ID);
					$title = $post->post_title;

					$published = date(YM_DATE, strtotime($post->post_date));
					
					if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
						$title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
					}

					$html .= '<tr><td><a href="' . $link . '">' . $title . '</a></td><td>' . $published . '</td></tr>';

					if ($with_snippets) {
						$full_content = apply_filters('the_content', $post->post_content);
						$content = substr(strip_tags($full_content), 0, $snippet_length);
						$html .= '<tr><td></td><td colspan="' . ($show_expiries ? 3:2) . '" class="ym_ppp_page_excerpt">' . $content . '</td></tr>';
					}
				}

				$html .= '</table></td></tr>';
			}
		}

		$html .= '</table>';
	}

	return $html;
}

/**
Return the bundle access expiry
*/
function ym_get_bundle_expiry_date($bundle_id, $user_id) {
	$return = '';

	global $ym_user;

	$user_id = $ym_user->ID;

	if ($purchase = ym_has_purchased_bundle($bundle_id, $user_id)) {
		$bundle = ym_get_bundle($bundle_id);
		if ($days = $bundle->purchaseexpire) {
			$purchased = ym_bundle_last_purchase($bundle_id, $user_id);
			$expiry_date = $purchased->unixtime + ($days * 86400);

			if (time() > $expiry_date) {
				$return = sprintf(__('Access Expired on %s', 'ym'), date(YM_DATE, $expiry_date + (get_option('gmt_offset') * 3600)));
			} else {
				$return = sprintf(__('Access Expires on %s', 'ym'), date(YM_DATE, $expiry_date + (get_option('gmt_offset') * 3600)));
			}
		} else {
			$return = '-';
		}
	} else {
		$return = __('Bundle is not Purchasable', 'ym');
	}

	return $return;
}

// logical Assumption the last purchase has the longest expiry
function ym_bundle_last_purchase($bundle_id, $user_id) {
	global $wpdb;

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_post_packs_purchased WHERE user_id = ' . $user_id . ' AND pack_id = ' . $bundle_id . ' ORDER BY unixtime DESC LIMIT 1';
	return $wpdb->get_row($query);
}

/**
Content Index/Cart Style pages
Running in non cart mode
*/
function ym_get_all_bundle_buttons($args) {
	if (!is_user_logged_in()) {
		global $ym_res;
		return $ym_res->msg_header . ym_filter_message($ym_res->all_bundles_not_logged_in) . $ym_res->msg_footer;
	}

	$hide_purchased = isset($args['hide_purchased']) ? $args['hide_purchased'] : TRUE;
	$max = isset($args['max']) ? $args['max'] : FALSE;

	$bundleoffset = isset($_REQUEST['bundleoffset']) ? $_REQUEST['bundleoffset'] : 0;

//	[ym_buy_bundle]

	$supported_args = array(
		'gateways',
		'hidecoupon',
		'list_contents',
		'hide_purchased'
	);
	$arg_string = '';
	foreach ($supported_args as $arg) {
		if (isset($args[$arg])) {
			$arg_string .= $arg . '=' . $args[$arg] . ' ';
		}
	}

	$content = '';

	global $ym_user;
	$user_id = $ym_user->ID;

	// rinse and repeat
	$bundles = ym_get_bundles();
	$count = 0;
	$bundleoffsetcount = 0;
	foreach ($bundles as $bundle) {
		if ($hide_purchased && ym_has_purchased_bundle($bundle->id, $user_id)) {
			// skip if purchased
			continue;
		}
		$bundleoffsetcount ++;
		if ($bundleoffset && $bundleoffsetcount <= $bundleoffset) {
			continue;
		}

		$content .= '[ym_buy_bundle bundle_id="' . $bundle->id . '" ' . $arg_string . ']' . "\n";

		$count++;
		if ($max && $count >= $max) {
			// exit it max reached
			break;
		}
	}

	$total = count($bundles);

	// pagniate?
	if ($max) {
		// paginate
		$content .= '<p style="overflow: hidden;">';
		if ($bundleoffset) {
			$url = get_permalink();
			if (FALSE === strpos($url, '?')) {
				$url .= '?';
			} else {
				$url .= '&';
			}
			$url .= 'bundleoffset=' . ($bundleoffset - $max);
			$content .= '<a href="' . $url . '">' . __('Back', 'ym') . '</a> ';
		}

//		if ($count == $max) {
		if (($count + $bundleoffset) < $total) {
			$url = get_permalink();
			if (FALSE === strpos($url, '?')) {
				$url .= '?';
			} else {
				$url .= '&';
			}
			$url .= 'bundleoffset=' . ($bundleoffset + $max);
			$content .= ' <a href="' . $url . '" class="ym_forward_link">' . __('Forward', 'ym') . '</a>';
		}
	}

	// and spit
	return do_shortcode($content);
}

/**
Limit Shortcodes
*/
function ym_bundle_units_left($args) {
	$bundle_id = isset($args['bundle_id']) ? $args['bundle_id'] : FALSE;

	if ($bundle_id) {
		return '';
	}

	$bundle = ym_get_bundle($bundle_id);
	$purchase_limit = $bundle->purchaselimit;
	$left = ym_bundle_available_count($bundle_id);
	if ($purchase_limit) {
		return $left;
	} else {
		return __('No Purchase Limit', 'ym');
	}
}

function ym_bundle_units_sold($args) {
	$bundle_id = isset($args['bundle_id']) ? $args['bundle_id'] : FALSE;

	if ($bundle_id) {
		return '';
	}
	
	return ym_bundle_purchased_count($bundle_id);
}

function ym_bundle_units_limit($args) {
	$bundle_id = isset($args['bundle_id']) ? $args['bundle_id'] : FALSE;

	if ($bundle_id) {
		return '';
	}

	$bundle = ym_get_bundle($bundle_id);

	if (!$bundle) {
		return '';
	}

	$limit = $bundle->purchaselimit;

	if ($limit) {
		return $limit;
	} else {
		return __('No Purchase Limit', 'ym');
	}
}

function ym_bundle_purchased_expiry($args) {
	if (!is_user_logged_in()) {
		return '';
	}

	$bundle_id = isset($args['bundle_id']) ? $args['bundle_id'] : FALSE;

	if ($bundle_id) {
		return '';
	}

	global $ym_user;
	$user_id = $ym_user->ID;

	return ym_get_bundle_expiry_date($bundle_id, $user_id);
}

function ym_bundle_last_purchased_date($args) {
	if (!is_user_logged_in()) {
		return '';
	}

	$bundle_id = isset($args['bundle_id']) ? $args['bundle_id'] : FALSE;

	if ($bundle_id) {
		return '';
	}

	global $ym_user;
	$user_id = $ym_user->ID;

	$purchased = ym_bundle_last_purchase($bundle_id, $user_id);
	if ($purchased->unixtime) {
		return date(YM_DATE, $purchased->unixtime + (get_option('gmt_offset') * 3600));
	} else {
		return '';
	}
}
