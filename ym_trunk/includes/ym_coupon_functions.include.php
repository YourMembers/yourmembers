<?php

/*
* $Id: ym_coupon_functions.include.php 2480 2012-12-17 11:36:40Z tnash $
* $Revision: 2480 $
* $Date: 2012-12-17 11:36:40 +0000 (Mon, 17 Dec 2012) $
*/

function ym_render_coupons() {
		$this_page = YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page');
	
	echo '<script>
			function ym_confirm_coupon_delete(id) {
				if (confirm("' . __('Are you sure you want to delete this coupon?', 'ym') . '")) {
					document.location="' . $this_page . '&delete_coupon="+id;
				}
			}
			</script>';

	echo '<div style="margin-bottom: 10px;">
				<table style="width:100%;" class="widefat">
				<thead>';
	echo '  <tr>
					<th>ID</th>
					<th>' . __('Code', 'ym') . '</th>
					<th>' . __('Value', 'ym') . '</th>
					<th>' . __('Description', 'ym') . '</th>

					<th>' . __('New Subscriber', 'ym') . '</th>
					<th>' . __('Subscription Upgrade', 'ym') . '</th>
					<th>' . __('Post Purchase', 'ym') . '</th>
					<th>' . __('Pack Purchase', 'ym') . '</th>

					<th>' . __('Usage Limit/Uses Left', 'ym') . '</th>

					<th>' . __('Date Created', 'ym') . '</th>
					<th style="width: 180px;">' . __('Action', 'ym') . '</th>
				</tr>
				</thead>';

	if ($coupons = ym_get_coupons()) {
		foreach ($coupons as $i=>$coupon) {

					echo '<tr ' . ($i%2 ? 'class="alternate"':'') . '>
							<td>' . $coupon->id . '</td>
							<td>' . $coupon->name . '</td>
							<td>' . $coupon->value . '</td>
							<td>' . $coupon->description . '</td>
							';

							$uses = str_split($coupon->allowed);
							foreach ($uses as $use) {
								echo '<td>' . $use . '</td>';
							}

							$left = ym_coupon_get_uses_left($coupon->id, FALSE);

							echo '
							<td>' . $coupon->usage_limit . '/' . $left . ' ';

							if ($coupon->usage_limit == 0 && $left == 1) {
								echo __('(Unlimited Usage)', 'ym');
							}

							echo '
							</td>
							<td>' . date(YM_DATE, $coupon->unixtime + (get_option('gmt_offset') * 3600)) . '</td>
							<td>
								<form method="POST" action="' . $this_page . '&coupon_id=' . $coupon->id . '" style="margin: 0px; padding: 0px;">
									<input class="button" name="edit" type="submit" value="' . __('Edit', 'ym') . '" />
									<input onclick="ym_confirm_coupon_delete(' . $coupon->id . ');" class="button" type="button" value="' . __('Delete', 'ym') . '" />
								  <input class="button" name="view" type="submit" value="' . __('Users', 'ym') . '" />
									
								</form>
							  
								</td>
						</tr>';
		}
	} else {
		echo '<tr>
							<td colspan="5">' . __('There are currently no coupons in the database.', 'ym') . '</td>
					</tr>';
	}

	echo '</table></div>';
		
		echo '<div>
				<form method="POST" style="margin: 0px; pading: 0px;">
					<table class="widefat form-table">
						<thead>
							<tr>
								<th colspan="4">' . __('Add a new Coupon', 'ym') . '</th>
							</tr>
						</thead>
						<tr>
							<td>' . __('Code', 'ym') . ': <input name="name" style="width: 240px;" /></td>
							<td>' . __('Value', 'ym') . ': <input name="value" style="width: 50px;" /></td>
							<td>' . __('Description', 'ym') . ': <input name="description" style="width: 400px;" /></td>
							<td style="text-align: right;">
								<input class="button" type="submit" name="save_coupon" value="' . __('Save', 'ym') . '" />
							</td>
						</tr>
						<tr>
							<td style="text-align: right;">' . __('Coupon is Valid for', 'ym') . '</td>
							<td colspan="2">
								<label>
									' . __('New Subscriber', 'ym') .' <input type="checkbox" name="new_sub" />
								</label>
								<label>
									' . __('Subscription Upgrade', 'ym') . ' <input type="checkbox" name="upgrade" />
								</label>
								<label>
									' . __('Post Purchase', 'ym') . ' <input type="checkbox" name="post" />
								</label>
								<label>
									' . __('Pack Purchase', 'ym') . ' <input type="checkbox" name="pack" />
								</label>
							</td>
							<td>
								<label>
									' . __('Usage Limit, (0 for Unlimited)', 'ym') . '<input type="text" name="usage_limit" size="2" value="0" />
								</label>
							</td>
						</tr>
					</table>
					<p>
' . __('In value you can specify a percentage, fixed value or hidden package.<br />To specify a percentage append % to the end of your value, eg 22%, for a hidden package prepend # to the value eg #1.<br />The package id can be found on the packages tab.', 'ym') . '
					</p>
				</form>
			</div>';
  
}
  
function ym_render_coupon_edit($coupon_id) {
	$coupon = ym_get_coupon($coupon_id);
	$this_page = YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page');
	
	$allowed = str_split($coupon->allowed);

		echo '<div>
			<form method="POST" style="margin: 0px; pading: 0px;">
				<input type="hidden" name="edit" value="1" />
				<table class="widefat form-table">
					<tr>
						<th>' . __('Code', 'ym') . '</th>
						<td><input name="name" style="width: 450px;" value="' . $coupon->name . '" /></td>
					</tr>
					<tr>
						<th>' . __('Cost', 'ym') . '</th>
						<td><input name="value" style="width: 50px;" value="' . $coupon->value . '" /></td>
					</tr>
					<tr>
						<th>' . __('Description', 'ym') . '</th>
						<td><input name="description" style="width: 700px;" value="' . $coupon->description . '"/></td>
					</tr>

					<tr>
						<th>
							<label>' . __('Can be used for New Subscriber', 'ym') . '</label>
						</th><td>
							<input type="checkbox" name="new_sub" ' . ($allowed[0] ? 'checked="checked"' : '') . ' />
						</td>
					</tr>
					<tr>
						<th>
							<label>' . __('Can be used for Subscription Upgrade', 'ym') . '</label>
						</th><td>
							<input type="checkbox" name="upgrade" ' . ($allowed[1] ? 'checked="checked"' : '') . ' />
						</td>
					</tr>
					<tr>
						<th>
							<label>' . __('Can be used for Post Purchase', 'ym') . '</label>
						</th><td>
							<input type="checkbox" name="post" ' . ($allowed[2] ? 'checked="checked"' : '') . ' />
						</td>
					</tr>
					<tr>
						<th>
							<label>' . __('Can be used for Pack Purchase', 'ym') . '</label>
						</th><td>
							<input type="checkbox" name="pack" ' . ($allowed[3] ? 'checked="checked"' : '') . ' />
						</td>
					</tr>

					<tr>
						<th>
							<label>' . __('Coupon Usage Limit (0 for Unlimited)', 'ym') . '</label>
						</th><td>
							<input type="text" name="usage_limit" size="2" value="' . $coupon->usage_limit . '" />
						</td>
					</tr>
					<tr>
						<td>
							<input class="button" type="button" onclick="document.location=\'' . $this_page . '\';" value="' . __('&#0171; Back to coupons', 'ym') . '" />
						</td>
						<td style="text-align: right;">
							<input class="button" type="submit" name="update_coupon" value="' . __('Save', 'ym') . '" />
						</td>
					</tr>				</table>
			</form>
		</div>';
}

function ym_render_coupon_view($coupon_id) {
	// who used this coupon and for what
	$data = ym_coupon_get_uses($coupon_id);
	
    echo '<table>';
    foreach ($data as $item) {
        echo '<tr ' . ($i%2 ? 'class="alternate"':'') . '>';
        echo '<td>(' . $item->id->ID . ') ' . $item->login . ' (' . $item->email . ')</td>';
        echo '<td>' . $item->id->account_type . '</td>';
        echo '<td>' . $item->id->expire_date . '</td>';
        echo '<td>' . $item->id->purchased . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
function ym_coupon_get_uses($coupon_id) {
	global $wpdb;

    $data = array();

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_coupon_use WHERE coupon_id = ' . $coupon_id;
    foreach ($wpdb->get_results($query) as $row) {
        $item = new StdClass();

        $user = new YourMember_User($row->user_id);
        $item->id = $user;
        $item->login = $user->data->user_login;
        $item->email = $user->data->user_email;
        $item->account_type = $user->account_type;
        $item->expire_date = $user->expire_date;

        $item->purchase = $row->purchased;

        $data[] = $item;
    }

    return $data;
}

function ym_get_coupon($coupon_id = false) {
	global $wpdb;
	
	$return = new stdClass();
	$return->id = $return->name = $return->description = $return->unixtime = $return->value = $return->allowed = $return->usage_limit = false;
	
	if ($coupon_id) {
		$sql = 'SELECT id, name, description, unixtime, value, allowed, usage_limit
				FROM ' . $wpdb->prefix . 'ym_coupon
				WHERE id =  ' . $coupon_id;
		$return = $wpdb->get_row($sql);
	}
	
	return $return;
}

function ym_get_coupons() {
	global $wpdb;
	
	$sql = 'SELECT *
			FROM ' . $wpdb->prefix . 'ym_coupon
			ORDER BY name';
	$return = $wpdb->get_results($sql);
	
	return $return;
}


function ym_edit_coupon($coupon_id, $name, $value, $description, $allowed, $usage_limit) {
	global $wpdb;
	
	$sql = 'UPDATE ' . $wpdb->prefix . 'ym_coupon
			SET
				name = "' . $name . '"
				, description = "' . $description . '"
				, value = "' . $value . '"
				, allowed = "' . $allowed . '"
				, usage_limit = "' . $usage_limit . '"
			WHERE id = ' . $coupon_id;
	
	if ($wpdb->query($sql)) {
		ym_display_message(__('Successfully updated coupon: ', 'ym') . $name);
	}
}

function ym_delete_coupon($coupon_id) {
	global $wpdb;
	
	$sql = 'DELETE FROM ' . $wpdb->prefix . 'ym_coupon
			WHERE id = ' . $coupon_id;
 
	if ($wpdb->query($sql)) {
		ym_display_message(__('Successfully deleted coupon: ', 'ym'));
	}
}

function ym_save_coupon($name, $value, $description=false, $allowed, $usage_limit = 0) {
	global $wpdb;

	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ym_coupon WHERE name = \'' . $name . '\'';
	$wpdb->query($sql);
	if ($wpdb->num_rows) {
		ym_display_message(__('Coupon Code already exists: ', 'ym') . $name, 'error');
		return;
	}
	
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_coupon (name, description, value, allowed, usage_limit, unixtime)
			VALUES ("' . $name . '", "' . $description . '", "' . $value . '", "' . $allowed . '", "' . $usage_limit . '", UNIX_TIMESTAMP())';

	if ($wpdb->query($sql)) {
		ym_display_message(__('Successfully created new coupon: ', 'ym') . $name);
	}
}

function ym_coupon_update(){
	$coupon_id = ym_get('coupon_id');
	$name = ym_post('name');
	$value = ym_post('value');
	$description = ym_post('description');
	$allowed = (ym_post('new_sub') ? '1' : '0')
		. (ym_post('upgrade') ? '1' : '0')
		. (ym_post('post') ? '1' : '0')
		. (ym_post('pack') ? '1' : '0' );
	$usage_limit = ym_post('usage_limit');
	
	if (ym_post('save_coupon')) {
		ym_save_coupon($name, $value, $description, $allowed, $usage_limit);
	}
		
	if (ym_post('update_coupon')) {
		ym_edit_coupon($coupon_id, $name, $value, $description, $allowed, $usage_limit);
	}
		
	if (ym_get('delete_coupon')){
		$coupon_id = ym_get('delete_coupon');
		ym_delete_coupon($coupon_id);
	}
}

function ym_get_coupon_data($coupon_name){
	global $wpdb;

	$coupon= new stdClass();

	$sql = 'SELECT id,name,value,allowed,usage_limit
	FROM ' . $wpdb->prefix . 'ym_coupon
	WHERE name = "' . $coupon_name . '"';
		$coupon = $wpdb->get_row($sql);	
	return $coupon;
}
function ym_get_coupon_id_by_name($coupon_name) {
	global $wpdb;

	$sql = 'SELECT id
	FROM ' . $wpdb->prefix . 'ym_coupon
	WHERE name = "' . $coupon_name . '"';
	return $wpdb->get_var($sql);
}
function ym_coupon_get_usecount($coupon_id) {
	global $wpdb;

	$query = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'ym_coupon_use WHERE coupon_id = ' . $coupon_id;
	return $wpdb->get_var($query);
}
function ym_coupon_get_uses_left($coupon_id, $neg = TRUE) {
	global $wpdb;

	$uses = ym_coupon_get_usecount($coupon_id);

	$sql = 'SELECT usage_limit
	FROM ' . $wpdb->prefix . 'ym_coupon
	WHERE id = ' . $coupon_id;

	$total = $wpdb->get_var($sql);
	if ($total > 0) {
		$left = $total - $uses;
		if (!$neg) {
			return $left;
		}
		if ($left > 0) {
			return $left;
		} else {
			// handle negatives
			return 0;
		}
	} else {
		// 0 unlim
		return 1;
	}
}

function ym_get_coupon_type($coupon_value) {
	//What type of Coupon do we have
	if(strpos($coupon_value, '%') !== false){
		//Percentage
		$return = 'percent';
	}
	elseif(strpos($coupon_value, '#') !== false){
		//subscription pack id
		$return = 'sub_pack';
	}
	else {
		//everything else
		$return = 'other';
	}
	//return this back don't forget to strip out the crap before use
	return $return;
}

// type 0 user subscribe 1 user upgrade 2 post purchase 3 bundle purhcase
function ym_validate_coupon($coupon_code, $type) {
	global $wpdb;

	$sql = 'SELECT value, allowed FROM ' . $wpdb->prefix . 'ym_coupon WHERE name = \'' . $coupon_code . '\'';
	$wpdb->query($sql);
	if ($wpdb->num_rows) {
		$id = ym_get_coupon_id_by_name($coupon_code);
		$value = $wpdb->get_var($sql);
		$allowed = $wpdb->get_var($sql, 1);

		$allowed = str_split($allowed);
		if ($allowed[$type] == 1) {
			if (ym_coupon_get_uses_left($id) > 0) {
				return $value;
			}
		}
	}
	return FALSE;
}
function ym_register_coupon_use($coupon_code, $user_id, $purchased) {
	global $wpdb;

	$data = ym_get_coupon_data($coupon_code);

	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_coupon_use(coupon_id, user_id, purchased) VALUES (\'' . $data->id . '\', \'' . $user_id . '\', \'' . $purchased . '\')';
	$wpdb->query($sql);
	return;
}

// generally for register and upgrade
function ym_apply_coupon($coupon_name, $type, $cost) {
	if ($value = ym_validate_coupon($coupon_name, $type)) {
		// is valid coupon for this type
		$type = ym_get_coupon_type($value);
		switch ($type) {
			case 'percent':
				$value = substr($value, 0, -1);// remove %
				$value = $value / 100;
				$value = $cost * $value;
			case 'other':
				// fixed price reduction
				$cost = $cost - $value;
				if ($cost < 0) {
					$cost = 0;
				}
				return $cost;
			case 'sub_pack':
				if ($type == 2 || $type == 3) {
					// invalid
					return $cost;
				}
				return 'pack_' . str_replace('#', '', $value);
		}
	}
	return $cost;
}
