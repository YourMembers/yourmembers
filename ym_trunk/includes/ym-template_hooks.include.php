<?php

/*
* $Id: ym-template_hooks.include.php 2224 2012-07-12 14:34:46Z bcarlyon $
* $Revision: 2224 $
* $Date: 2012-07-12 15:34:46 +0100 (Thu, 12 Jul 2012) $
*/

function ym_get_userdata($name='author_name') {
	get_currentuserinfo();
	global $current_user;

	if (isset($_GET[$name])) {
		$return = get_user_by('login', $_GET[$name]);
	} else {
		$return = get_userdata($current_user->ID);
	}

	return $return;
}

function ym_member_check($account_types = array()) {
	$user_ac = ym_get_user_account_type();
	return in_array($user_ac, $account_types);
}

function ym_membership_content_shortcode($args) {
	$show = isset($args['show']) ? $args['show'] : 'posts,bundles,premium';
	$with_snippets = isset($args['snippets']) ? $args['snippets'] : TRUE;
	$with_boxes = isset($args['boxes']) ? $args['boxes'] : FALSE;
	$with_expires = isset($args['expire']) ? $args['expire'] : FALSE;

	return ym_membership_content_page($with_boxes, $with_snippets, $with_expires, $show);
}
function ym_membership_content_page($with_boxes=false, $with_snippets = TRUE, $with_expires = FALSE, $show = 'posts,bundles,premium') {
	get_currentuserinfo();
	global $current_user, $wpdb, $ym_res;

	$snippet_length = 200;
	$max_loops = 30;    
	$html = '';

	$membership_level = ym_get_user_package_type($current_user->ID);

	$posts = false;

	$show = explode(',', $show);
	
	if (in_array('posts', $show)) {
		if ($pp = ym_render_my_purchased_posts($current_user->ID, false, true, $with_expires, $with_snippets)) {
			if ($with_boxes) {
				$html .= ym_start_box(__('My Purchased Posts', 'ym'));
			}
			
			$html .= $pp;
			
			if ($with_boxes) {
				$html .= ym_end_box();
			}
		}
	}

	if (in_array('bundles', $show)) {
		// bundle
		if ($bundles = ym_render_my_purchased_bundles($current_user->ID, $with_expires, TRUE, $with_snippets)) {
			if ($with_boxes) {
				$html .= ym_start_box(__('My Purchased Bundles', 'ym'));
			}
				
			$html .= $bundles;
				
			if ($with_boxes) {
				$html .= ym_end_box();
			}
		}
	}
	
	if (in_array('premium', $show)) {
		$sql = 'SELECT DISTINCT(ID), post_title, post_date, post_content
				FROM
					' . $wpdb->posts . ' p
					JOIN ' . $wpdb->postmeta . ' pm ON (
						p.ID = pm.post_id
						AND p.post_status = "publish"
						AND pm.meta_key = "_ym_account_type"
						AND pm.meta_value LIKE "%' . $membership_level . '%"
						AND post_type = "post"
					)
				ORDER BY post_date DESC';
		$results = $wpdb->get_results($sql);

		$loops = 0;
		if ($members_pages = count($results)) {
			foreach ($results as $id=>$obj) {
				if (!ym_user_has_access($obj->ID)) {
					$membership_pages--;
					continue;
				}
				
				$published = date(YM_DATEFORMAT, strtotime($obj->post_date));
				$full_content = apply_filters('the_content', $obj->post_content);

				$title = $obj->post_title;
				if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage')) {
					$title = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($title);
					$full_content = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($full_content);
				}

				$row = '<tr>
				<td style="border-top: 1px solid silver;">
					<div style="margin-bottom: 5px;"><a href="' . get_permalink($obj->ID) . '">' . $title . '</a></div>
					';
					if ($with_snippets) {
						$content = substr(strip_tags($full_content), 0, $snippet_length);
						//$content = preg_replace("'\[/?\s?private\s?\]'i",'', $content);
						$ending = (strlen($full_content) > strip_tags($snippet_length) ? '...':'');

						$row .= '<div style="font-size: 10px; margin-bottom: 5px;">' . $content . $ending . '</div>';
					}
					$row .= '
				</td>
				<td style="vertical-align: top; border-top: 1px solid silver;">' . $published . '</td>
			</tr>';

				$posts .= $row;

				$loops++;

				if ($loops >= $max_loops) {
					break;
				}
			}
		}

		$table_intro = 'Showing the most recent ' . $loops . ' posts of a total ' . $members_pages . ' available to you.';

		$html .= $ym_res->members_content_divider_html;

		if ($with_boxes) {
			$html .= ym_start_box(__('Premium Content', 'ym'));
		}

		$html .= '	<div class="premium_content_intro">' . __('Your membership level is:',"ym") . ' <strong>' . $membership_level . '</strong>. 	
				' . __('You have access to a total of',"ym") . ' ' . $members_pages . ' ' . __('premium', 'ym') . ' ' .  ($members_pages == 1 ? __('post', 'ym'):__('posts', 'ym')) . ' 
			</div>';

		if ($members_pages > 0) {
			$html .= $table_intro;

			$html .= '<div style="padding-top: 10px; margin-bottom: 10px;">
				<table style="width: 100%" cellspacing="0" cellpadding="2">
				<tr>
					<th style="text-align: left;">Post Title</th>
					<th style="width: 160px; text-align: left;">Published</th>
				</tr>
					' . $posts . '
			</table></div>';
		}
		
		if ($with_boxes) {
			$html .= ym_end_box();
		}
	}

	return $html;
}

function ym_get_user_profile($args = array(), $content = '') {
	global $current_user, $ym_user, $ym_sys;
	get_current_user();

	$format = get_option('date_format');

	$amount = ($ym_user->amount > 0) ? sprintf(__('%1$s %2$s','ym'), $ym_user->amount, $ym_user->currency):'-';
	$last_pay = $ym_user->last_pay_date ? date($format, $ym_user->last_pay_date) : '-';
	$expiry = $ym_user->expire_date ? date($format, $ym_user->expire_date) : '-';

	if (isset($args['show'])) {
		switch($args['show']) {
			case 'last_pay_date':
				return $last_pay;
			case 'amount':
				return $amount;
			case 'expiry':
				return $expiry;
			case 'package_type':
				return ym_get_user_package_type();
			case 'upgrade':
				$html = '<a href="'. add_query_arg(array('ym_subscribe' => 1, 'ud' => 1, 'username' => $current_user->user_login), get_bloginfo('url')) . '">';
				$html .= $ym_sys->upgrade_downgrade_string;
				$html .= '</a>';
				return $html;
		}
	}

	// check for parent
	if ($ym_user->parent_id) {

		$parent_user = get_user_by('id', $ym_user->parent_id);
		$parent_user = '<a href="mailto:' . $parent_user->email . '">' . $parent_user->display_name . '</a>';

		$html = '<p>' . sprintf(__('Your Account is managed by %s', 'ym'), $parent_user) . '</p>';
		return $html;
	}
	
	$html = '<table width="100%" cellpadding="0" cellspacing="0" border="0" class="form-table widefat">
			<tr>
				<td height="30"><strong>' . __('Last Payment Date:','ym') . '</strong></td>
				<td align="left">' . esc_html($last_pay) . '</td>
			</tr>
			<tr class="alternate">
				<td height="30"><strong>' . __('Expiry Date:','ym') . '</strong></td>
				<td align="left">' . esc_html($expiry) . '</td>
			</tr>
			<tr>
				<td height="30"><strong>' . __('Package Cost:','ym') . '</strong></td>
				<td align="left">' . esc_html($amount) . '</td>
			</tr>
			<tr class="alternate">
				<td height="30"><strong>' . __('Package Type:','ym') . '</strong></td>
				<td align="left">' . ym_get_user_package_type() .' (<a href="'. add_query_arg(array('ym_subscribe' => 1, 'ud' => 1, 'username' => $current_user->user_login), get_bloginfo('url')) . '">';
					$html .= $ym_sys->upgrade_downgrade_string;
					$html .= '</a>)</td>
			</tr>
		</table>';
		
	return $html;
}

function ym_get_user_purchase_history_shortcode($atts) {
	$limit = isset($atts['limit']) ? $atts['limit'] : 5;
	global $current_user;
	get_current_user();
	$userID = $current_user->ID;
	return ym_get_user_purchase_history($userID, $limit);
}
function ym_get_user_purchase_history($userID = FALSE, $limit = 5) {
	if (!$userID) {
		global $current_user;
		get_current_user();
		$userID = $current_user->ID;
	}
	global $wpdb;

	$html = '';

	// get log
	$query = 'SELECT DISTINCT(transaction_id) FROM ' . $wpdb->prefix . 'ym_transaction
		WHERE user_id = ' . $userID . '
		ORDER BY unixtime DESC
		LIMIT ' . $limit;
	foreach ($wpdb->get_results($query) as $trans) {
		$id = $trans->transaction_id;

		$sub = 'SELECT * FROM ' . $wpdb->prefix . 'ym_transaction WHERE transaction_id = ' . $id . ' AND user_id = ' . $userID;

		$data = array();
		foreach ($wpdb->get_results($sub) as $log) {
			$data[$log->action_id] = $log->data;
		}

		if (isset($data[YM_PPP_PURCHASED])) {
			// Pay Per Post
			$html .= '<li>' . __('Content Purchase: ', 'ym') . get_the_title($data[YM_PPP_PURCHASED]) . '</li>';
		} else if (isset($data[YM_PPP_PACK_PURCHASED])) {
			// bundle
			$html .= '<li>' . __('Content Purchase: ', 'ym');
			$bundle = ym_get_bundle($data[YM_PPP_PACK_PURCHASED]);
			$html .= $bundle->name . '</li>';
		} else if (isset($data[YM_PACKAGE_PURCHASED])) {
			$pack = $data[YM_PACKAGE_PURCHASED];
			$html .= '<li>';
			$html .= ym_get_pack_label($pack, FALSE, FALSE);
			$html .= '</li>';
		} else if (isset($data[YM_PAYMENT])) {
			$html .= '<li>' . __('Unknown Payment', 'ym') . ' ' . $data[YM_PAYMENT] . '</li>';
		}
	}

	if (empty($html)) {
		return FALSE;
	}

	$html = '<ul>' . $html . '</ul>';

	return $html;
}
