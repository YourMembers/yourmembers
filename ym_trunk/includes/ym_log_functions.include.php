<?php

function ym_get_transaction_actions($limit_to=false) {
	global $wpdb;
	
	$sql = 'SELECT id, name, description
		FROM ' . $wpdb->prefix . 'ym_transaction_action';
		
	if ($limit_to) {
		$sql .= ' WHERE id = ' . $limit_to;
		
	}
	return $wpdb->get_results($sql);
}

function ym_get_transaction_action($id) {
	global $wpdb;
	
	$sql = 'SELECT id, name, description
		FROM ' . $wpdb->prefix . 'ym_transaction_action
		WHERE id = ' . $id;
	return $wpdb->get_row($sql);
}

function ym_get_transaction_action_select($name='log_type_id', $value=false, $all_option=true) {
	global $ym_formgen;
	
	$html = '<select name="' . $name . '">';
	
	if ($all_option) {
		$html .= '<option value="0" ' . (!$value ? 'selected="selected"':'') . '>' . __('All Log Types', 'ym') . '</option>';
	}
	
	if ($actions = ym_get_transaction_actions()) {
		foreach ($actions as $i=>$action) {
			$html .= '<option value="' . $action->id . '" ' . ($value == $action->id ? 'selected="selected"':'') . '>' . $action->name . '</option>';
		}
	}
	
	$html .= '</select>';
	
	return $html;
}

function ym_add_transaction_action($name,$description,$strict=false) {
	global $wpdb;

	//if strict is true validate against name first
	if($strict){
		$actions = ym_get_transaction_actions();
		foreach ($actions as $action) {
			if(strtolower($action->name) == strtolower($name)) return false;
		}
	}
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_transaction_action (name,description) VALUES ("'.$name.'","'.$description.'")';
	$wpdb->query($sql);
	$id = $wpdb->insert_id;

	if($id) return $id;
	return false;

}

function ym_create_log_constants() {
	global $wpdb;
	
	if ($constants = ym_get_transaction_actions()) {
		foreach ($constants as $i=>$constant) {
			$name = 'YM_' . strtoupper(str_replace(' ', '_', $constant->name));
			define($name, $constant->id);
		}
	}
}

$ym_this_transaction_id = 0;
function ym_log_transaction($action_id=1, $data=false, $user_id=0) {
	get_currentuserinfo();
	global $wpdb, $current_user;

	global $ym_this_transaction_id;
	
	if (is_array($data)) {
		$data = serialize($data);
	}
	
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_transaction (
			transaction_id
			, user_id
			, action_id
			, data
			, unixtime
		)
		VALUES (
			' . $ym_this_transaction_id . '
			, "' . (int)$user_id . '"
			, "' . $action_id . '"
			, "' . mysql_real_escape_string($data) . '"
			, UNIX_TIMESTAMP()
		)';
	$wpdb->query($sql);
	$id = $wpdb->insert_id;

	if (!$ym_this_transaction_id) {
		$ym_this_transaction_id = $id;
		$sql = 'UPDATE ' . $wpdb->prefix . 'ym_transaction SET transaction_id = ' . $id . ' WHERE id = ' . $id;
		$wpdb->query($sql);
	}

	return $id;
}

function ym_get_all_logs($type_id=false, $user_id=false, $range_end=false, $range_start=false, $order_by='id DESC', $with_deleted = TRUE) {
	global $wpdb;
	
	$sql = 'SELECT yt.id AS id, user_id, action_id, data, unixtime, user_email, user_login
		FROM ' . $wpdb->prefix . 'ym_transaction yt
		LEFT JOIN ' . $wpdb->users . ' u ON u.ID = yt.user_id';
		
	//Start WHERE conditions
	$where = array();
	
	if ($type_id) {
		$where[] = 'action_id = ' . $type_id;
	}
	
	if ($user_id) {
		$where[] = 'user_id = ' . $user_id;
	}
	
	if (!$with_deleted) {
		$where[] = 'user_login != \'\'';
	}

	if (count($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	///End WHERE conditions
	
	if ($order_by) {
		$sql .= ' ORDER BY ' . $order_by . ' ';
	}
	
	if($range_end) {
			if(!$range_start){
				$range_start = 0;
			}
		$sql .= ' LIMIT '.$range_start.','.$range_end;
	}
	
	return $wpdb->get_results($sql);
}

function ym_show_timeline_log($log_type_id=false, $user_id=false, $range_end=false, $range_start=false, $order_by='id DESC', $with_deleted = TRUE) {
	echo ym_start_box('Timeline View');
		
	if ($transactions = ym_get_all_logs($log_type_id, $user_id, $range_end, $range_start, $order_by, $with_deleted)) {
		echo '<table class="ym_table form-table">
		<thead>
			<tr>
				<th>' . __('Log ID', 'ym') . '</th>';
			
		if (!$user_id) {
			echo '	<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>';
		}
				
		echo '		<th>' . __('Log Type', 'ym') . '</th>
				<th>' . __('Data', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
			</tr>
		</thead>
		<tbody>';

		$log_date = FALSE;
		
		foreach ($transactions as $transaction) {
			if ($mod = @unserialize($transaction->data)) {
				
			} else {
				$mod = $transaction->data;
			}
			
			if (!$user_id) {
				$user = get_userdata($transaction->user_id);
			}
			
			$log_type = ym_get_transaction_action($transaction->action_id);
			
			echo '<tr>';
			echo '<td>' . $transaction->id . '</td>';
				
			if (!$user_id) {
				echo '	<td>'.$transaction->user_id.'</td>
					<td><a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a></td>
					<td>'.$user->user_email.'</td>';
			}

			if (is_object($log_type)) {
				echo '	<td>'.$log_type->name.'</td>';
			} else {
				echo '<td>' . __('Unknown', 'ym') . ' (' . $transaction->action_id . ') <span style="display: none;">' . $transaction->id . '</span></td>';
			}

			if ($transaction->action_id == YM_ACCESS_EXTENSION || $transaction->action_id == YM_ACCESS_EXPIRY) {
				// TODO: for YM 11 only
				if (strpos($transaction->data, ' ') || strpos($transaction->data, '-')) {
					$transaction->data = strtotime($transaction->data);
				}
				echo '<td>';
				if ($transaction->data) {
					echo date(YM_DATE, $transaction->data + (get_option('gmt_offset') * 3600));
				} else {
					echo 'No Data';
				}
				echo '</td>';
			} else {
				echo '<td>'.(is_array($mod) ? '<pre>' . print_r($mod, true) . '</pre>':$mod).'</td>';
			}

			echo '
				<td>'.date(YM_DATE, $transaction->unixtime + (get_option('gmt_offset') * 3600)).'</td>
			</tr>';

			if (!$log_date) {
				$log_date = date('d-m-Y', $transaction->unixtime);
			} else {
				$test = date('d-m-Y', $transaction->unixtime);
				if ($test != $log_date) {
					$log_date = $test;
					echo '<tr><td colspan="4">&nbsp;</td></tr>';
				}
			}
		}
		
		echo '</tbody>
		</table>';
	} else {
		echo __('<em>There are no logs for this type.</em>','ym');
	}
	
	echo ym_end_box();
}

function ym_show_generic_log($log_type_id=false, $user_id=false) {
	if ($actions = ym_get_transaction_actions($log_type_id)) {
		foreach ($actions as $i=>$log_type) {
			echo ym_start_box($log_type->name);
			echo '<p>' . $log_type->description . '</p>';
				
			if ($transactions = ym_get_all_logs($log_type->id, $user_id)) {
				echo '<table class="ym_table form-table">
				<thead>
					<tr>';
					
		if (!$user_id) {
			echo '	<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>';
		}
				
		echo '		<th>' . __('Log Type', 'ym') . '</th>
				<th>' . __('Data', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
					</tr>
				</thead>
				<tbody>';
				
				foreach ($transactions as $transaction) {
					$mod = unserialize($transaction->data);
					
					if (!$user_id) {
						$user = get_userdata($transaction->user_id);
					}
					
					echo '<tr>';
						
					if (!$user_id) {
						echo '	<td>'.$transaction->user_id.'</td>
							<td><a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a></td>
							<td>'.$user->user_email.'</td>';
					}
						
					echo '	<td>'.(is_array($mod) ? '<pre>' . print_r($mod, true) . '</pre>':$mod).'</td>
						<td>'.date(YM_DATE, $transaction->unixtime + (get_option('gmt_offset') * 3600)).'</td>
					</tr>';	
				}
				
				echo '</tbody>
				</table>';
			} else {
				echo __('<em>There are no logs for this type.</em>','ym');
			}
			
			echo ym_end_box();
		}
	}
}

function ym_show_timeline_log_summary() {
	ym_show_timeline_log();
}

/*
function ym_show_generic_log_summary() {
	echo ym_start_box('IPN Information');
	echo '<p>IPN stands for Instant Payment Notification. It means something of note has happened at a payment processor and they will make a "call" to your site to let you know. Things that happen are usually payments and refunds. YM can then deal with each request appropriately.</p>';
		
	if ($transactions = ym_get_all_logs(YM_IPN)) {
		
		echo '<table class="ym_table form-table">
		<thead>
			<tr>
				<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>
				<th>' . __('Module', 'ym') . '</th>
				<th>' . __('Data', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
			</tr>
		</thead>
		<tbody>';
		
		foreach ($transactions as $transaction) {
			$mod = unserialize($transaction->data);
			$user = get_userdata($transaction->user_id);
			if (!$user) {
				$user = new stdClass();
				$user->user_email = '';
				$user->profile = 'Deleted User';
			} else {
				$user->profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a>';
			}

			$mod['mod'] = isset($mod['mod']) ? $mod['mod'] : 
				(isset($mod['ym_process']) ? $mod['ym_process'] : 
					(isset($mod['gateway']) ? $mod['gateway'] : 'unknown'));
			
			if (isset($mod['gift_sub'])) {
				$mod['custom'] = __('Gifted Subscription', 'ym');
			}

			if (!isset($mod['custom']) && isset($mod['freebie_code'])) {
				$mod['mod'] = __('Free Coupon', 'ym');
				$mod['custom'] = $mod['freebie_code'];
			} else if ($mod['mod'] == 'ym_facebook_credits') {
				$mod['custom'] = $mod['fb_items'][0]->item_id;
			} else if (!isset($mod['custom'])) {
				// bust guest
				foreach ($mod as $key => $item) {
					if (!is_array($item) && substr($item, 0, 4) == 'buy_') {
						$mod['custom'] = $mod[$key];
						break;
					}
				}
			}

			$module = ucwords(str_replace('ym_', '', $mod['mod']));
			echo '<tr>
				<td>'.$transaction->user_id.'</td>
				<td>' . $user->profile . '</td>
				<td>'.$user->user_email.'</td>
				<td>'.$module.'</td>
				<td>'.$mod['custom'].'</td>
				<td>'.date(YM_DATE, $transaction->unixtime).'</td>
			</tr>';	
		}
		
		echo '</tbody>
		</table>';
	} else {
		echo __('<em>There are no Transactions logged.</em>','ym');
	}
	
	echo ym_end_box();
	echo ym_start_box('Status Updates');
	echo '<p>' . __('This simply means when a user status/account type has been updated. For instance an account activated or expired.', 'ym') . '</p>';
	
	if ($transactions = ym_get_all_logs(YM_USER_STATUS_UPDATE)) {
		echo '<table class="ym_table form-table">
		<thead>
			<tr>
				<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>			
				<th>' . __('New Status', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
			</tr>
		</thead>
		<tbody>';
		
		foreach ($transactions as $transaction) {
			$mod = $transaction->data;
			$user = get_userdata($transaction->user_id);
			if (!$user) {
				$user = new stdClass();
				$user->user_email = '';
				$user->profile = 'Deleted User';
			} else {
				$user->profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a>';
			}
			
			echo '<tr>
				<td>'.$transaction->user_id.'</td>
				<td>' . $user->profile . '</td>
				<td>'.$user->user_email.'</td>			
				<td>'.$transaction->data.'</td>
				<td>'.date(YM_DATE, $transaction->unixtime).'</td>
			</tr>';	
		}
		
		echo '</tbody>
		</table>';
	} else {
		echo __('<em>There Have been no status updates.</em>','ym');
	}
	
	echo ym_end_box();
	echo ym_start_box('Individual Content Purchases');
	echo '<p>This section shows the details of posts purchased limited to what it was and when it was bought by whom. Extended information on Posts Purchased can be seen on the PPP admin page in the Content Management menu above.</p>';
	
	if ($transactions = ym_get_all_logs(YM_PPP_PURCHASED)) {
		echo '<table class="ym_table form-table">
		<thead>
			<tr>
				<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>			
				<th>' . __('Individual Content Purchased', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
			</tr>
		</thead>
		<tbody>';
		
		foreach ($transactions as $transaction) {
			$post_id = $transaction->data;
			$title = get_the_title($post_id);
			$user = get_userdata($transaction->user_id);
			if (!$user) {
				$user = new stdClass();
				$user->user_email = '';
				$user->profile = 'Deleted User';
			} else {
				$user->profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a>';
			}
			
			echo '<tr>
				<td>'.$transaction->user_id.'</td>
				<td>'.$user->profile.'</td>
				<td>'.$user->user_email.'</td>			
				<td>' . '(' . $post_id . ') ' . $title . '</td>
				<td>'.date(YM_DATE, $transaction->unixtime).'</td>
			</tr>';	
		}
		
		echo '</tbody>
		</table>';
	} else {
		echo __('<em>There are no Purchases logged.</em>','ym');
	}
	
	echo ym_end_box();
	echo ym_start_box('Bundles Purchases');
	echo '<p>Once again this section shows a log of all the Bundles that have been purchased and by whom. Use the Content Management menu above to get more data on Bundles sold.</p>';
		
	if ($transactions = ym_get_all_logs(YM_PPP_PACK_PURCHASED)) {
		echo '<table class="ym_table form-table">
		<thead>
			<tr>
				<th>' . __('User ID', 'ym') . '</th>
				<th>' . __('User', 'ym') . '</th>
				<th>' . __('User Email', 'ym') . '</th>			
				<th>' . __('Bundles Purchased', 'ym') . '</th>
				<th>' . __('Date', 'ym') . '</th>
			</tr>
		</thead>
		<tbody>';
		
		foreach ($transactions as $transaction) {
			$user = get_userdata($transaction->user_id);
			if (!$user) {
				$user = new stdClass();
				$user->user_email = '';
				$user->profile = 'Deleted User';
			} else {
				$user->profile = '<a alt="View user profile" title="View user profile" href="'.YM_ADMIN_URL.'user-edit.php?user_id=' . $transaction->user_id . '" target="_top">'.$user->user_login.'</a>';
			}
			$bundle = ym_get_bundle($transaction->data);

			echo '<tr>
				<td>'.$transaction->user_id.'</td>
				<td>' . $user->profile . '</td>
				<td>'.$user->user_email.'</td>			
				<td>'. '(' . $bundle->id . ') ' . $bundle->name .'</td>
				<td>'.date(YM_DATE, $transaction->unixtime).'</td>
			</tr>';	
		}
		
		echo '</tbody>
		</table>';
	} else {
		echo __('<em>There are no Purchases logged.</em>','ym');
	}
	
	echo ym_end_box();
}
*/

function ym_show_timeline_log_home($limit = 20) {
	if ($transactions = ym_get_all_logs(false, false, false, false, 'unixtime DESC LIMIT ' . $limit)) {
		echo '<table class="form-table widefat">';
		foreach ($transactions as $transaction) {
			$user = get_userdata($transaction->user_id);
			echo '<tr>';
			$action = ym_get_transaction_action($transaction->action_id);
			if (is_object($action)) {
				echo '<td>' . $action->name . ' <span style="display: none;">' . $action->id . '</span></td>';
			} else {
				echo '<td>' . __('Unknown', 'ym') . ' (' . $transaction->action_id . ') <span style="display: none;">' . $transaction->id . '</span></td>';
			}
			echo '<td>';
			if (isset($transaction->user_id) && isset($user->user_login)) {
				echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=user-edit&amp;user_id=' . $transaction->user_id . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox">';
				echo $user->user_login . '</a>';
			} else {
				echo 'DeletedUser';
			}
			echo '</td>';

			echo '<td>';
			echo '<a href="?page=' . YM_ADMIN_FUNCTION . '&amp;ym_page=ym-logs&amp;user_id=' . $transaction->user_id . '&amp;TB_iframe=true&amp;height=700&amp;width=800" class="thickbox ym_node_tree"></a></td>';
			echo '</td>';

			if (is_serialized($transaction->data)) {
				$data = unserialize($transaction->data);
				echo '<td><a href="#nowhere" class="ym_packetize_show">' . __('Click to Show Packet', 'ym') . '<div class="ym_packetize_packet"><table class="ym_packetize_data">';
				foreach ($data as $name => $value) {
					echo '<tr><th>' . $name . '</th><td>';
					$is = gettype($value);
					if ($is == 'object' || $is == 'array') {
//						echo print_r($value, TRUE);
						echo '<table>';
						foreach ($value as $k => $v) {
							echo '<tr><td>' . $k . '</td><td><pre>' . print_r($v, true) . '</pre></td></tr>';
						}
						echo '</table>';
					} else {
						echo $value;
					}
					echo '</td></tr>';
				}
				echo '</table></div></a></td>';
			} else {
				if ($transaction->action_id == YM_ACCESS_EXTENSION || $transaction->action_id == YM_ACCESS_EXPIRY) {
					// TODO: for YM 11 only
					if (strpos($transaction->data, ' ') || strpos($transaction->data, '-')) {
						$transaction->data = strtotime($transaction->data);
					}
					echo '<td>';
					if ($transaction->data) {
						echo date(YM_DATE, $transaction->data + (get_option('gmt_offset') * 3600));
					} else {
						echo 'No Data';
					}
					echo '</td>';
				} else {
					echo '<td>' . $transaction->data . '</td>';
				}
			}
			echo '<td>' . date(YM_DATE, $transaction->unixtime + (get_option('gmt_offset') * 3600)) . '</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo '<p>' . __('There is nothing to report at the moment. Check back soon though!', 'ym') . '</p>';
	}
}
