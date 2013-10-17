<?php

/*
* $Id: ym_individual_purchase_functions.include.php 2480 2012-12-17 11:36:40Z tnash $
* $Revision: 2480 $
* $Date: 2012-12-17 11:36:40 +0000 (Mon, 17 Dec 2012) $
*/

/**
Post Purchasable
*/
function ym_post_is_purchasable($post_id = false) {
	$return = false;
	if (!$post_id) {
		$post_id = get_the_ID();
	}

	if (get_post_meta($post_id, '_ym_post_purchasable', TRUE)) {
		$purchase_limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
		$purchased = ym_post_purchased_count($post_id);
		$left = $purchase_limit - $purchased;

		// check if still for sale
		if ($expiry = get_post_meta($post_id, '_ym_post_purchasable_expiry', TRUE)) {
			if ($expiry > time()) {
				$return = true;
			}
		// check if in stock
		} else if ($purchase_limit && ($left <= 0)) {
			$return = false;
		} else {
			$return = true;
		}
	}
	return $return;
}

function ym_post_purchased_count($post_id) {
	global $wpdb;

	$query = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'posts_purchased
		WHERE post_id = ' . $post_id;
	return $wpdb->get_var($query);
}

function ym_post_available_count($post_id) {
	$purchase_limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
	$purchased = ym_post_purchased_count($post_id);
	$left = $purchase_limit - $purchased;
	return $left;
}
/**
End Post Purchasable
*/

/**
Has Bought post
*/
function ym_has_purchased_post($post_id, $user_id, $bypass_expiry = FALSE) {
	global $wpdb;
	$query = 'SELECT * FROM ' . $wpdb->prefix . 'posts_purchased
		WHERE user_id = ' . $user_id . '
		AND post_id = ' . $post_id;
	$rows = $wpdb->get_results($query);
	$count = count($rows);

	// check expiry
	if (!$bypass_expiry && $expiry = get_post_meta($post_id, '_ym_post_purchasable_duration', TRUE)) {
		$seconds = $expiry * 86400;// convert from days to seconds
		$expire = time() - $seconds;

		foreach ($rows as $row) {
			$purchased_at = $row->unixtime;
			if ($purchased_at > $expire) {
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
End Bought post
*/

/**
Magic Shortcode
*/
function ym_buy_button_content($atts) {
	if (!is_user_logged_in()) {
		return '';
	}

	global $ym_sys, $ym_res, $ym_user;
	$user_id = $ym_user->ID;

	$mode = isset($atts['post_id']) ? TRUE : FALSE;
	$post_id = isset($atts['post_id']) ? $atts['post_id'] : get_the_id();

	// check if purchased
	if (!ym_post_is_purchasable($post_id) || ym_has_purchased_post($post_id, $user_id) || ym_user_has_access($post_id)) {
		if ($mode) {
			return '<p><a href="' . get_permalink($post_id) . '">' . __('You have purchased: ', 'ym') . get_the_title($post_id) . '</a></p>';
		}
		return '';
	}

	// check purchase limit
	$purchase_limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
	$left = ym_post_available_count($post_id);

	if ($purchase_limit && ($left <= 0)) {
		$r = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->purchasable_at_limit . $ym_res->msg_footer . '</div>';
		return $r;
	}

	$cost = get_post_meta($post_id, '_ym_post_purchasable_cost', true);
	if (!$cost) {
		// bundle only
		$r = '<div style="margin-bottom:5px;width:100%;">' . $ym_res->msg_header . $ym_res->purchasable_pack_only . $ym_res->msg_footer . '</div>';
		return $r;
	}

	$selected_gateways = isset($atts['gateways']) ? explode('|', $atts['gateways']) : array();
	$hidecoupon = isset($atts['hidecoupon']) ? $atts['hidecoupon'] : FALSE;
	// default to TRUE is post id passed no post id default is false
	$title = isset($atts['showtitle']) ? $atts['showtitle'] : ($mode ? TRUE : FALSE);

	$url = get_permalink($post_id);

	if (isset($_POST['ym_buy_button_content'])) {

		$r = '<p>' . sprintf(__('You are purchasing post: %s', 'ym'), get_the_title($post_id)) . '</p>
<form action="" method="post" class="ym_buy_button_post">
<input type="hidden" name="ym_buy_button_content" value="1" />
<input type="hidden" name="ym_buy_button_args" value=\'' . json_encode($atts) . '\' />
<table>
	<tr><td>' . __('Purchasing: ', 'ym') . ' ' . get_the_title() . '</td><td>' . ym_get_currency() . ' ' . number_format($cost, 2) . '</td></tr>
	';

		$show_coupon = $hidecoupon ? FALSE : TRUE;
		$final_price = FALSE;
		if (isset($_POST['ym_buy_button_content_coupon'])) {
			$coupon = $_POST['ym_buy_button_content_coupon'];
			// validate
			if (FALSE !== ($value = ym_validate_coupon($coupon, 2))) {
				$type = ym_get_coupon_type($value);
				$r .= '<tr><td>' . sprintf(__('Valid Coupon Supplied: %s', 'ym'), $coupon) . '</td><td>';
 
				if ($type == 'sub_pack') {
					$r .= __('However it is invalid for a Post Purchase', 'ym');
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
					ym_register_coupon_use($coupon, $user_id, 'buy_post_' . $post_id . '_' . $user_id);
				}

				$r .= '</td></tr>';
			} else {
				$r .= '<tr><td></td><td>' . sprintf(__('Invalid Coupon Supplied: %s', 'ym'), $coupon) . '</td></tr>';
			}
		}
		if ($show_coupon) {
			$r .= '<tr><td>' . __('Apply Coupon', 'ym') . '</td><td><input type="text" name="ym_buy_button_content_coupon" value="" /><input type="submit" name="ym_buy_button_content_apply_coupon" value="' . __('Apply Coupon', 'ym') . '" /></td></tr>';
		}

		if (FALSE === $final_price) {
			$final_price = $cost;
		}
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
</form>
';

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
				if (method_exists($obj, 'pay_per_post')) {
					$r .= $obj->gen_buy_now_button($final_price, get_the_title($post_id), TRUE, $post_id);
				}
			}
		}

		return $r;
	}

	$r = '
<form action="' . $url . '" method="post" class="ym_buy_button_post_buynow">
	<fieldset>
	';

	$r .= '<p>';
	if ($title) {
		$r .= __('Purchase:', 'ym') . ' ' . get_the_title($post_id) . ' - ';
	}
	// show cost
	$r .= __('Cost:', 'ym') . ' ' . ym_get_currency() . ' ' . number_format($cost, 2);
	$r .= '</p>';

	$r .= '
		<input type="hidden" name="ym_buy_button_args" value=\'' . json_encode($atts) . '\' />
		<input type="submit" name="ym_buy_button_content" value="' . __('Buy Now', 'ym') . '" />
	</fieldset>
</form>
';
	return $r;
}

function ym_buy_button_content_filter($content) {
	if (!is_user_logged_in()) {
		return $content;
	}

	if (isset($_POST['ym_buy_button_content']) && isset($_POST['ym_buy_button_args'])) {
		$code = '[ym_buy_content ';
		$args = json_decode(stripslashes($_POST['ym_buy_button_args']));
		if ($args) {
			foreach ($args as $name => $val) {
				$code .= $name . '="' . $val . '" ';
			}
		}
		$code .= ' ]';
		return $code;
	} else {
		global $post_id;
		if (ym_user_has_access($post_id)) {
			return $content;
		}
		if (FALSE !== strpos($content, '[ym_buy_content ') || FALSE !== strpos($content, '[ym_buy_content]')) {
			if (FALSE !== ($start = strpos($content, '[private'))) {
				$mid = strpos($content, '[/private', $start);
				$end = strpos($content, ']', $mid);
				$end++;
				$bit_to_remove = substr($content, $start, ($end - $start));
				$content = str_replace($bit_to_remove, '', $content);
			}
		}
		return $content;
	}
}
/**
End Shortcode
*/

/**
Return Purchase Expiry Date/status
*/
function ym_post_purchased_expiry($post_id = FALSE) {
	$return = '';

	if (!$post_id) {
		$post_id = get_the_ID();
	}

	if (ym_post_is_purchasable($post_id)) {
		global $ym_user;

		$user_id = $ym_user->ID;

// TODO: Does NOT return the purchase DATE!
		if ($purchase = ym_has_purchased_post($post_id, $user_id)) {
			if ($days = get_post_meta($post_id, '_ym_post_purchasable_duration', true)) {
				$purchased = ym_post_last_purchase($post_id, $user_id);
				$purchased = $purchased->unixtime;
				$expiry_date = $purchased + ($days * 86400);

				if (time() > $expiry_date) {
					$return = sprintf(__('Access Expired on %s', 'ym'), date(YM_DATE, $expiry_date + (get_option('gmt_offset') * 3600)));
				} else {
					$return = sprintf(__('Access Expires on %s', 'ym'), date(YM_DATE, $expiry_date + (get_option('gmt_offset') * 3600)));
				}
			} else {
//				$return = __('Content does not expire', 'ym');
				$return = '-';
			}
		} else {
			$return = __('User has not Purchased this Content', 'ym');
		}
	} else {
		$return = __('Content is not Purchasable', 'ym');
	}

	return $return;
}

//ym_post_last_purchase_date_for_an_id
//ym_post_last_purchase_for_a_post_id_by_user
// logical Assumption the last purchase has the longest expiry
function ym_post_last_purchase($post_id, $user_id) {
	global $wpdb;

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'posts_purchased WHERE user_id = ' . $user_id . ' AND post_id = ' . $post_id . ' ORDER BY unixtime DESC LIMIT 1';
	return $wpdb->get_row($query);
}

function ym_post_last_purchased_date($post_id = FALSE) {
	if (!is_user_logged_in()) {
		return '';
	}
	global $wpdb;
	$return = '';

	if (!$post_id) {
		$post_id = get_the_ID();
	}

	global $ym_user;
	$user_id = $ym_user->ID;

	$query = 'SELECT * FROM ' . $wpdb->prefix . 'posts_purchased WHERE user_id = ' . $user_id . ' AND post_id = ' . $post_id . ' ORDER BY unixtime DESC LIMIT 1';
	$row = $wpdb->get_row($query);

	if ($wpdb->num_rows) {
		$date = $row->unixtime;
		return date(YM_DATE, $date);
	} else {
		return __('You have not Purchased this post', 'ym');
	}
}

/**
Dashboard Widget
*/
/**
Render bought posts
temp function
*/

/**
Import old functions
*/
function ym_render_posts_purchased($limit=false, $summary=false) {
	global $wpdb;

	$prefix = $wpdb->prefix;
	$sql = 'SELECT p.post_title AS title, COUNT(pp.id) AS count, pp.post_id, u.user_login
			FROM
				' . $prefix . 'posts_purchased pp
				JOIN ' . $wpdb->posts . ' p ON (p.id = pp.post_id)
				JOIN ' . $wpdb->users . ' u ON (p.post_author = u.id)
			GROUP BY pp.post_id
			ORDER BY count DESC, title';

	$results = $wpdb->get_results($sql,'ARRAY_A');

	echo '<table style="width:100%;" class="form-table widefat">';
	echo '<tr>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">Post Title</td>
				' . ($summary ? '':'<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">Author</td>') . '
				' . ($summary ? '':'<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">Cost</td>') . '
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">Total Profit</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold; text-align: right; width:20%;">Sold</td>
				';

	echo '</tr>';

	if ($wpdb->num_rows) {
		$loop = 1;
		foreach ($results as $result) {
			
			$price = (int)get_post_meta($result['post_id'], '_ym_post_purchasable_cost', true);
			
			echo '<tr>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $result['title'] . '</td>
					' . ($summary ? '':'<td style="border-bottom: 1px solid #EFEFEF;">' . $result['user_login'] . '</td>') . '
					' . ($summary ? '':'<td style="border-bottom: 1px solid #EFEFEF;">' . number_format($price, 2) . ' ' . ym_get_currency() . '</td>') . '
					<td style="border-bottom: 1px solid #EFEFEF;">' . number_format($price * $result['count'], 2) . ' ' . ym_get_currency() . '</td>
					<td style="border-bottom: 1px solid #EFEFEF; text-align:right;">' . $result['count'] . '</td>';
			echo '</tr>';

			$loop++;

			if ($limit && $loop == $limit) {
				break;
			}
		}
	} else {
		echo '<tr>
					<td colspan="2">' . __('No posts have been sold yet', 'ym') . '</td>
				</tr>';
	}

	echo '</table>';

	if ($summary) {
		echo '<p style="text-align: right;">';
		echo '<a href="#nowhere" onclick="jQuery(\'#yourmembers\').tabs({selected: 3});jQuery(\'#ym-top-content-options\').tabs({selected: 3});">';
		echo __('Individual Post Purchase Management &#0187;', 'ym') . '</a></p>';
	}

}


function ym_get_all_posts_purchased($limit=false, $author_id=false) {
	global $wpdb;
	
	$prefix = $wpdb->prefix;
	$sql = 'SELECT p.ID AS post_id, p.post_title, pp.unixtime, pp.user_id, u.user_login, a.user_login AS author_login, pp.id, pp.payment_method
			FROM
				' . $prefix . 'posts_purchased pp
				JOIN ' . $wpdb->posts . ' p ON (p.id = pp.post_id)
				LEFT JOIN ' . $wpdb->users . ' u ON (u.ID = pp.user_id)
				JOIN ' . $wpdb->users . ' a ON (a.ID = p.post_author)';
				
	if ($author_id) {
		$sql .= ' WHERE p.post_author = ' . $author_id;
	}
	
	$sql .= '	ORDER BY pp.unixtime DESC, u.user_login, p.post_title';

	if ($limit) {
		$sql .= ' LIMIT ' . $limit;
	}

	return $wpdb->get_results($sql,'ARRAY_A');	
}

function ym_render_all_posts_purchased($admin=false, $limit=false, $author_id=false) {
	global $wpdb;

	$this_page = YM_ADMIN_URL . (isset($_GET['ym_page']) ? '&ym_page=' . $_GET['ym_page'] : '');
	$totals = 0;

	echo '<script>
			function ym_confirm_ppp_delete(id) {
				if (confirm("' . __('Are you sure you want to delete this purchase?', 'ym') . '")) {
					document.location="' . $this_page . '&delete="+id;
				}
			}

		</script>';
	$results = ym_get_all_posts_purchased($limit, $author_id);
	

	echo '<table class="form-table widefat" cellspacing="0" style="width:100%;">';
	echo '<tr>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Member', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Post Title', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Purchase Expiry', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Price', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Post Author', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold; text-align: right; width:20%;">' . __('Date Purchased', 'ym') . '</td>
				<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold;">' . __('Payment Method', 'ym') . '</td>
				';

	if ($admin) {
		echo '<td style="border-bottom: 1px solid #EFEFEF; font-weight:bold; text-align: right;">' . __('Delete', 'ym') . '</td>';
	}

	echo '</tr>';

	if (isset($results[0])) {
		$last_user = 0;
		$last_post = 0;
		$last_time = 0;
		foreach ($results as $result) {
			if ($result['user_id']) {
				if ($last_user == $result['user_id']) {
					if ($last_post == $result['post_id']) {
						$sql = 'DELETE FROM ' . $wpdb->prefix . 'posts_purchased
							WHERE id = ' . $result['id'];
						$wpdb->query($sql);
						continue;
					} else {
						$last_post = $result['post_id'];
					}
				} else {
					$last_user = $result['user_id'];
					$last_post = $result['post_id'];
				}
			} else {
				if (!$last_user) {
					if ($last_post == $result['post_id']) {
						if ($last_time) {
							if ($result['unixtime'] >= ($last_time-30)) {
								$result['post_title'] = '<span style="color: red;">Duplicate?: ' . $result['post_title'] . '</div>';
							}
						}
					}
					
					$last_time = $result['unixtime'];
					$last_post = $result['post_id'];
				} else {
					$last_user = 0;
					$last_time = $result['unixtime'];
					$last_post = $result['post_id'];
				}
			}
			
			$expiry = (int)get_post_meta($result['post_id'], '_ym_post_purchasable_duration', 1);

			if (!$expiry) {
				$expiry = __('Indefinite', 'ym');
			} else {
				$expiry = date('d/m/Y',(86400*$expiry) + $result['unixtime']) . " (" . $expiry . ")";
			}
			
			if (!$result['user_login']) {
				$result['user_login'] = '<em>' . __('Non Registered User', 'ym') . '</em>';
			}
			
			$price = (int)get_post_meta($result['post_id'], '_ym_post_purchasable_cost', true);
			$totals += $price;
			
			$method = ($result['payment_method'] ? $result['payment_method']:'<em>Unknown</em>');

			echo '<tr>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $result['user_login'] . '</td>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $result['post_title'] . '</td>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $expiry . '</td>
					<td style="border-bottom: 1px solid #EFEFEF;">' . number_format($price,2) . ' ' . ym_get_currency() . '</td>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $result['author_login'] . '</td>
					<td style="border-bottom: 1px solid #EFEFEF; text-align:right;">' . date(YM_DATE, $result['unixtime']+ (get_option('gmt_offset') * 3600)) . '</td>
					<td style="border-bottom: 1px solid #EFEFEF;">' . $method . '</td>';

			if ($admin) {
				echo '<td style="border-bottom: 1px solid #EFEFEF; text-align: right;">
						<a onclick="ym_confirm_ppp_delete(' . $result['id'] . ');" style="cursor:pointer;">
							<img src="' . YM_IMAGES_DIR_URL . 'cross.png" alt="' . __('Delete', 'ym') . '"/>
						</a>
					</td>';
			}

			echo '</tr>';
		}
		
		
		echo '<tr>
				<td class="ym_admin_ppp_totals">&nbsp;</td>
				<td class="ym_admin_ppp_totals">&nbsp;</td>
				<td class="ym_admin_ppp_totals">&nbsp;</td>
				<td class="ym_admin_ppp_totals">' . number_format($totals,2) . ' ' . ym_get_currency() . '</td>
				<td class="ym_admin_ppp_totals">&nbsp;</td>
				<td class="ym_admin_ppp_totals">&nbsp;</td>
				<td class="ym_admin_ppp_totals">&nbsp;</td>';

		echo '</tr>';
	} else {
		echo '<tr>
					<td colspan=2>No posts have been sold yet</td>
				</tr>';
	}

	echo '</table>';

}

function ym_render_my_purchased_posts($user_id, $sidebar=true, $return=false, $show_expiries=false, $with_snippets = TRUE) {
	global $wpdb, $ym_sys, $ym_res;

	$html = '';
	
	$prefix = $wpdb->prefix;
	$sql = 'SELECT pp.post_id, p.post_title AS title, p.post_content, p.post_date
				FROM
						' . $prefix . 'posts_purchased pp
						JOIN ' . $prefix . 'posts p ON (p.id = pp.post_id)
						LEFT JOIN ' . $prefix . 'postmeta pm ON (
				p.id = pm.post_id
				AND pm.meta_key = "_ym_post_purchasable_expiry"
			)
				WHERE
			pp.user_id = ' . $user_id . '
			AND (
				pm.meta_value = \'\'
				OR pm.meta_value >= UNIX_TIMESTAMP()
			)
				ORDER BY RAND()';
	$results = $wpdb->get_results($sql,'ARRAY_A');

	if (!$sidebar) {
		if (isset($results[0]) && count($results[0])) {
					$snippet_length = 200;
					
			$html .= '<table cellspacing="0" class="ym_ppp_page"><tr><th>' . __('Post Title', 'ym') . '</th>' . ($show_expiries ? '<th>' . __('Access Expiry', 'ym') . '</th>':'') . '<th>' . __('Published', 'ym') . '</th></tr>';

			foreach ($results as $result) {
				$link = get_permalink($result['post_id']);
				$title = $result['title'];

				$published = date(YM_DATEFORMAT, strtotime($result['post_date']));
				
				if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
					$title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
				}

				$html .= '<tr><td><a href="' . $link . '">' . $title . '</a></td>' . ($show_expiries ? '<td>' . ym_post_purchased_expiry($result['post_id']) . '</td>':'') . '<td>' . $published . '</td></tr>';

				if ($with_snippets) {
					$full_content = apply_filters('the_content', $result['post_content']);
					$content = substr(strip_tags($full_content), 0, $snippet_length);
					$html .= '<tr><td colspan="' . ($show_expiries ? 3:2) . '" class="ym_ppp_page_excerpt">' . $content . '</td></tr>';
				}
			}

			$html .= '</table>';

		}
	} else {
		if (isset($results[0]) && count($results[0])) {
			//$html .= '<div style="border-bottom: 1px solid #EFEFEF; font-weight:bold; width: 100%;">Purchased Posts</div>';
			$html .= '<ul class="purchased_posts">';
			foreach ($results as $result) {
				$link = get_permalink($result['post_id']);

				$title = $result['title'];
				if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
					$title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
				}

				$html .= '<li class="ym_ppp_item"><a href="' . $link . '">' . $title . '</a> ' . ($show_expiries ? '- <span class="ym_ppp_item_expiry">' . ym_post_purchased_expiry($result['post_id']) . '</span>':'') . '</li>';
			}
			$html .= '</ul>';
		} else {
			if ($ym_res->ym_ppp_none_msg) {
				$html = '<p class="ym_ppp_none_msg">' . $ym_res->ym_ppp_none_msg . '</p>';
			} else {
				$html = '';
			}
		}
	}
	
	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}
// Todo: see ym_bundle_get_some_posts
function ym_render_ppp_management() {
	global $wpdb, $ym_formgen;

	$user_sql = 'SELECT DISTINCT(ID) AS value, user_login AS label
			FROM ' . $wpdb->users . ' u
			ORDER BY user_login';

	$post_sql = 'SELECT DISTINCT(p.ID) AS value, p.post_title AS label
			FROM
				' . $wpdb->posts . ' p
				JOIN ' . $wpdb->postmeta . ' pm ON (
					p.ID = pm.post_id
					AND pm.meta_key = "_ym_post_purchasable"
					AND pm.meta_value = "1"
				)
			WHERE
				p.post_status = "publish"
			ORDER BY p.post_title';

	echo '<form method="post" action="">
			<table style="width:100%;" class="form-table widefat">
			<tr>
				<td>' . __('Select a user', 'ym') . '<br />';

	$ym_formgen->render_combo_from_query('user_id', $user_sql);

	echo '		</td>
				<td>' . __('Select a post/page', 'ym') . '<br />';

	$ym_formgen->render_combo_from_query('post_id', $post_sql);

	echo '		</td>
				<td>
					<input class="button" type="submit" name="submit" value="' . __('Submit', 'ym') . '" />
				</td>
			</tr>';
	echo '	</table>
		</form>';
}

function ym_check_for_gift_sub() {
	global $wpdb;
	// gift sub

	if (ym_post('submit')) {
		if (ym_post('post_id')) {
				$sql = 'SELECT COUNT(id)
								FROM ' . $wpdb->prefix . 'posts_purchased
								WHERE
										user_id = ' . ym_post('user_id') . '
										AND post_id = ' . ym_post('post_id');
		
				if (!$wpdb->get_var($sql)) {
						$sql = 'INSERT INTO ' . $wpdb->prefix . 'posts_purchased (user_id, post_id, unixtime)
								VALUES (' . $_POST['user_id'] . ', ' . $_POST['post_id'] . ', UNIX_TIMESTAMP())';
						$wpdb->query($sql);
		
						ym_display_message(__('Post has been successfully gifted', 'ym'));
				} else {
						ym_display_message(__('User has already purchased that post', 'ym'), 'error');
				}
		} else {
				ym_display_message(__('Please select a post to gift before submitting the form', 'ym'), 'error');
		}
	}
	if (ym_get('delete')) {
			$sql = 'DELETE FROM ' . $wpdb->prefix . 'posts_purchased
							WHERE id=' . $_GET['delete'];
			
			if ($wpdb->query($sql)) {
					ym_display_message(__('Purchased post has been successfully deleted', 'ym'));
			}
	}

	// end
}

/**
Content Index/Cart Style pages
Running in non cart mode
*/
function ym_get_all_content_buttons($args, $featured_only = FALSE) {
	if (!is_user_logged_in()) {
		global $ym_res;
		return $ym_res->msg_header . ym_filter_message($ym_res->all_content_not_logged_in) . $ym_res->msg_footer;
	}

	$category = isset($args['category']) ? $args['category'] : '';
	$hide_purchased = isset($args['hide_purchased']) ? $args['hide_purchased'] : TRUE;
	$max = isset($args['max']) ? $args['max'] : -1;

	if (!empty($category) && !is_int($category)) {
		// get ID
		$term = get_term_by('name', $category, 'category');
		$category = $term->name;
	}

	$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;

	$supported_args = array(
		'gateways',
		'hidecoupon',
	);
	$arg_string = '';
	foreach ($supported_args as $arg) {
		if (isset($args[$arg])) {
			$arg_string .= $arg . '=' . $args[$arg] . ' ';
		}
	}

//	[ym_buy_content]
	// get purchaseable posts using stated restrcitions
	$args = array(
		'numberposts'	=> -1,
		'post_type'		=> 'any',

		'category'		=> $category,

		'meta_key'		=> '_ym_post_purchasable',
		'meta_value'	=> '1',
	);

	if ($featured_only) {
		// shaun did it on this key only.... even if he did it with a query
		$args['meta_key'] = '_ym_post_purchasable_featured';
	}

	$posts = get_posts($args);
	$total = count($posts);

	$args['numberposts'] = $max;
	$args['offset'] = $offset;
	$posts = get_posts($args);

	$content = '';

	global $ym_user;
	$user_id = $ym_user->ID;

	// rinse and repeat
	$count = 0;
	foreach ($posts as $post) {
		$post_id = $post->ID;

		if ($hide_purchased && ym_has_purchased_post($post_id, $user_id)) {
			// skip if purchased
			continue;
		}

		$content .= '[ym_buy_content post_id="' . $post_id . '" ' . $arg_string . ']' . "\n";
		$count++;
	}

	// pagniate?
	if ($max != -1) {
		// paginate
		$content .= '<p style="overflow: hidden;">';
		if ($offset) {
			$url = get_permalink();
			if (FALSE === strpos($url, '?')) {
				$url .= '?';
			} else {
				$url .= '&';
			}
			$url .= 'offset=' . ($offset - $max);
			$content .= '<a href="' . $url . '">' . __('Back', 'ym') . '</a> ';
		}

		if (($count + $offset) < $total) {
			$url = get_permalink();
			if (FALSE === strpos($url, '?')) {
				$url .= '?';
			} else {
				$url .= '&';
			}
			$url .= 'offset=' . ($offset + $max);
			$content .= ' <a href="' . $url . '" class="ym_forward_link">' . __('Forward', 'ym') . '</a>';
		}

		$content .= '</p>';
	}

	// and spit
	return do_shortcode($content);
}

function ym_get_featured_content_buttons($args) {
	return ym_get_all_content_buttons($args, TRUE);
}

/**
Limit Shortcodes
*/
function ym_content_units_left($args) {
	$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();

	if (!$post_id) {
		return '';
	}

	$purchase_limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
	$left = ym_post_available_count($post_id);

	if ($purchase_limit) {
		return $left;
	} else {
		return __('No Purchase Limit', 'ym');
	}
}

function ym_content_units_sold($args) {
	$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();

	if (!$post_id) {
		return '';
	}
	
	return ym_post_purchased_count($post_id);
}

function ym_content_units_limit($args) {
	$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();

	if (!$post_id) {
		return '';
	}

	$limit = get_post_meta($post_id, '_ym_post_purchasable_limit', true);
	if ($limit) {
		return $limit;
	} else {
		return __('No Purchase Limit', 'ym');
	}
}
//Helper function needed for standardisation with bundles
function ym_gift_post($post_id,$user_id){
	global $wpdb;
	//check if post already purchased
	if(ym_has_purchased_post($post_id,$user_id)){
		return false;
	}
	//Insert into Table
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'posts_purchased (user_id, post_id, unixtime)
								VALUES (' . $user_id . ', ' . $post_id . ', UNIX_TIMESTAMP())';
	if ($wpdb->query($sql)) {
		do_action('ym_post_gift_post',$post_id,$user_id);
			return true;
	}
	else{
		return false;
	}
}
