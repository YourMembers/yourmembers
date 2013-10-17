<?php

function ym_mm_invoice_init() {
	// only handling success
	add_action('ym_buy_post_transaction_success', 'ym_mm_invoice_ppp', 10, 1);
	add_action('ym_membership_transaction_success', 'ym_mm_invoice_subs', 10, 1);
}

$ym_mm_type = $ym_mm_title = $ym_mm_cost = '';

// user id is a frekin array.....
function ym_mm_invoice_ppp($data) {
	$user_id = $data['user_id'];
	$post_id = $data['post_id'];
	$user_data = $data['user_data'];

	global $ym_mm_type, $ym_mm_title, $ym_mm_cost;
	global $ym_res;

	$data = get_option('ym_mm_invoicing');
	
	if (strpos($post_id, ',') !== false) {
		if (!$data->bundle_enabled) {
			return;
		}
		
		$subject = $data->bundle_subject;
		$message = $data->bundle_message;

		$ym_mm_type = __('Bundle', 'ym_mailmanager');
		$ym_mm_title = __('Bundle Purchase', 'ym_mailmanager');
		
		global $wpdb;
		$post_ids = explode($post_id, ',');
		$first = array_pop($post_ids);

		$query = 'SELECT * FROM ' . $wpdb->prefix . 'ym_post_pack_post_assoc WHERE post_id = ' . $first;
		$results = $wpdb->get_results($query);
		
		if ($wpdb->num_rows == 1) {
			// yay
			$data = $results[0];
			$pack_id = $data->pack_id;
		} else {
			foreach ($results as $result) {
				$posts = ym_get_ppp_pack_posts($result->pack_id);
				$post_array = array();
				foreach ($posts as $post) {
					$post_array[] = $post->post_id;
				}
				if (true === array_identical($post_array, $post_ids)) {
					// found
					$pack_id = $post->pack_id;
					break;
				}
			}
		}
		
		// cost
		$query = 'SELECT cost FROM ' . $wpdb->prefix . 'ym_post_pack WHERE id = ' . $pack_id;
		$cost = $wpdb->get_var($query);
		
		// is stored in pence
		$ym_mm_cost = $ym_res->currency . ' ' . number_format($cost / 100);
	} else {
		if (!$data->post_enabled) {
			return;
		}
		
		$subject = $data->post_subject;
		$message = $data->post_message;

		$ym_mm_type = __('Post', 'ym_mailmanager');
		$ym_mm_title = get_the_title($post_id);
		$ym_mm_cost = $ym_res->currency . ' ' . get_post_meta($post_id, '_ym_post_purchasable_cost', TRUE);
	}
	
	ym_mm_parse_and_send($user_id, $subject, $message);
}

function ym_mm_invoice_subs($data) {
	$user_id = $data['user_id'];
	$pack_id = $data['pack_id'];
	$user_data = $data['user_data'];
	
	$data = get_option('ym_mm_invoicing');
	
	if (!$data->subscription_enabled) {
		return;
	}
	if(is_array($user_id)) $user_id = implode($user_id);
	global $ym_mm_type, $ym_mm_title, $ym_mm_cost;

	$ym_mm_type = __('Subscription', 'ym_mailmanager');
	$ym_mm_title = ym_get_pack_label($user_data->pack_id);
	$ym_mm_cost = $user_data->currency . ' ' . $user_data->amount;
	
	ym_mm_parse_and_send($user_id, $data->subscription_subject, $data->subscription_message);
}

function ym_mm_parse_and_send($user_id, $subject, $message) {
	$data = get_userdata($user_id);
	$user_email = $data->user_email;
	
	add_shortcode('blogname', 'mailmanager_shortcode_blogname');
	add_shortcode('blogurl', 'mailmanager_shortcode_blogurl');
	add_shortcode('loginurl', 'wp_login_url');
	add_shortcode('ym_mm_custom_field', 'mailmanager_custom_fields_shortcode');
	add_shortcode('ym_mm_if_custom_field', 'mailmanager_custom_fields_shortcode');

	add_shortcode('ym_mm_type', 'ym_mm_type_shortcode');
	add_shortcode('ym_mm_title', 'ym_mm_title_shortcode');
	add_shortcode('ym_mm_cost', 'ym_mm_cost_shortcode');

	$subject = do_shortcode($subject);
	$message = do_shortcode($message);

	// hook into send
	mailmanager_send_email($user_email, $subject, $message);
}

function ym_mm_type_shortcode() {
	global $ym_mm_type;
	return $ym_mm_type;
}
function ym_mm_title_shortcode() {
	global $ym_mm_title;
	return $ym_mm_title;
}
function ym_mm_cost_shortcode() {
	global $ym_mm_cost;
	return $ym_mm_cost;
}

function array_identical($a, $b) {
    return (is_array($a) && is_array($b) && array_diff_assoc($a, $b) === array_diff_assoc($b, $a));
}
