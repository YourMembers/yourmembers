<?php

/*
* $Id: ym_facebook_credits.php 2181 2012-05-29 14:01:05Z bcarlyon $
* $Revision: 2181 $
* $Date: 2012-05-29 15:01:05 +0100 (Tue, 29 May 2012) $
*/

/*
* TODO: FB needs enqueue on wp-login
* adjust/hack to match???
* need to think
*/

class ym_facebook_credits extends ym_payment_gateway {
	var $name = 'Facebook Credits';
	var $code = 'ym_facebook_credits';

	function __construct() {
		$this->version = '$Revision: 2181 $';
		$this->name = __('Memberships with Facebook Credits', 'ym');
		$this->description = __('Make payments with Facebook Credits for Non Recurring Subscriptions and Posts and Bundles', 'ym');

		$this->sane = FALSE;
		$this->sanity();

		$this->logo = '';
		$this->membership_words = $this->name;
		$this->post_purchase_words = $this->name;
		$this->bundle_purchase_words = $this->name;
		$this->callback_script = '';

		// load other settings
		$obj = get_option('ym_fbook_options', array());
		foreach ($obj as $var => $val) {
			$this->$var = $val;
		}
	}

	function activate() {
		if (!$this->sanity(TRUE)) {
			return;
		}
	}
	function deactivate() {
	}
	function sanity($message = FALSE) {
		global $ym_active_modules;
		
		if (!in_array($this->code, (array)$ym_active_modules)) {
			return;
		}
		if (!function_exists('ym_facebook_settings')) {
			$this->deactivate();

			if (in_array($this->code, (array)$ym_active_modules)) {
				$key = array_search($this->code, $ym_active_modules);
				
				delete_option($this->code); //clears all previous data for this module
				
				$ym_active_modules[$key] = null;
				unset($ym_active_modules[$key]);
			}

			update_option('ym_modules', $ym_active_modules);

			if ($message) {
				ym_display_message(__('Could not activate ym_facebook_credits, ym_facebook is not installed', 'ym'), 'error');
				return FALSE;
			}

			// redirect to the modules page
			$url = YM_ADMIN_URL . '&ym_page=' . ym_get('ym_page') . '&action=modules';
			echo '
<script type="text/javascript">
	window.location = "' . $url . '"
</script>
';
			return FALSE;
		}
		$this->sane = TRUE;
		return TRUE;
	}

	// button gen
	function pack_filter($packs) {
		foreach ($packs as $key => $pack) {
			$cost_test = $pack['cost'];
			if (strpos($cost_test, '.')) {
				$cost_test = $cost_test * 100;
			}
			if (strtolower($pack['account_type']) == 'free') {
				unset($packs[$key]);
			} else if ($cost_test == 0) {
				unset($packs[$key]);
			}
		}

		return $packs;
	}
	function get_button_code($pack, $user_id, $override_price = FALSE) {
		if (isset($pack['num_cycles']) && $pack['num_cycles'] != 1 && $pack['duration_type']) {
			// subs
			$data = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			// post/single
			if (isset($pack['id'])) {
				$data = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
			} else {
				// post
				if (isset($pack['ppp_pack_id'])) {
					$data = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
				} else {
					$data = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
				}
			}
		}
		return $data;
	}

	function getButtonOverride($pack, $user_id, $override_price = FALSE) {
		// facebook test:
		if (!$_SESSION['in_facebook']) {
			return '';
		}
		$button_code = $this->get_button_code($pack, $user_id, $override_pric);

		$function_name = 'ym_fbook_purchase_' . $user_id . '_' . $pack['id'];
		$cost = $override_price ? $override_price : $pack['cost'];

		$cost = $this->credits_convert($cost, 'pack_' . $post_id);

		$html = '
<script type="text/javascript">
	function ' . $function_name . '() {
		var ' . $function_name . '_obj = {
			method: \'pay\',
			order_info: \'' . $button_code . '_' . number_format($cost, 0) . '\',
			purchase_type: \'item\'
		};
		FB.ui(' . $function_name . '_obj, ' . $function_name . '_callback);
	}
	var ' . $function_name . '_callback = function(data) {
		if (data[\'order_id\']) {
			location.href = \'' . $this->redirectlogic($pack) . '\';
			return true;
		} else {
			return false;
		}
	}
</script>
<form action="" method="post" class="ym_form ' . $this->code . '_form form-table" name="' . $this->code . '_form" id="' . $this->code . '_form">
	<fieldset>
		<strong>Cost: ' . number_format($cost, 0) . ' Credits</strong><br />
		<input type="image" src="' . $this->logo . '" name="submit" alt="' . $this->membership_words . '" onclick="' . $function_name . '();return false;" />
	</fieldset>
</form>
';

		return $html;
	}
	function gen_buy_now_button_override($post_cost, $post_title, $return, $post_id, $data) {
		// facebook test:
		if (!$_SESSION['in_facebook']) {
			return '';
		}

		// bypass standard form generation
		$user_id = ym_get_user_id();

		$function_name = 'ym_fbook_purchase_' . $user_id . '_' . $post_id;
		$cost = $data['cost'];

		list($pack, $cost) = $this->gen_buy_now_button_override_packcost($post_cost, $post_title, $return, $post_id, $data);
		$script = $this->gen_buy_now_button_override_script($function_name, $pack, $cost, $data);
		$form = $this->gen_buy_now_button_override_form($function_name, $cost, $data);

		return $script . $form;
	}

	// api functions
	function gen_buy_now_button_override_packcost($post_cost, $post_title, $return, $post_id, $data) {
		if (isset($data['post_id'])) {
			$pack = $this->pay_per_post($post_cost, $post_title, $return, $post_id);
			$cost = $this->credits_convert($post_cost, 'post_' . $post_id);
		} else {
			$pack = $this->pay_per_post_bundle($post_cost, $post_id, $post_title);
			$cost = $this->credits_convert($post_cost, 'bundle_' . $post_id);
		}
		return array($pack, $cost);
	}

	function gen_buy_now_button_override_script($function_name, $pack, $cost, $data) {
		// facebook test:
		if (!$_SESSION['in_facebook']) {
			return '';
		}
		$html = '
<script type="text/javascript">
	function ' . $function_name . '() {
		var ' . $function_name . '_obj = {
			method: \'pay\',
			order_info: \'' . $this->get_button_code($data, $user_id) . '_' . $cost . '\',
			purchase_type: \'item\'
		};
		FB.ui(' . $function_name . '_obj, ' . $function_name . '_callback);
	}
	var ' . $function_name . '_callback = function(data) {
		if (data[\'order_id\']) {
			location.href = \'' . $this->redirectlogic($pack) . '\';
			return true;
		} else {
			return false;
		}
	}
</script>
		';

		return $html;
	}
	function gen_buy_now_button_override_form($function_name, $cost, $data) {
		// facebook test:
		if (!$_SESSION['in_facebook']) {
			return '';
		}
		$html = '
<form action="" method="post" class="ym_form ' . $this->code . '_form form-table" name="' . $this->code . '_form" id="' . $this->code . '_form">
	<fieldset>
		<strong>Cost: ' . $cost . ' Credits</strong><br />
		<input type="image" src="' . $this->logo . '" name="submit" alt="' . $this->membership_words . '" onclick="' . $function_name . '();return false;" />
	</fieldset>
</form>
';

		return $html;
	}
	// end 
	
	function gen_buy_ppp_pack_button_override($pack_cost, $pack_title, $return, $pack_id, $data) {
		return $this->gen_buy_now_button_override($pack_cost, $pack_title, $return, $pack_id, $data);
	}

	// enable pay per post
	function pay_per_post($post_cost, $post_title, $return, $post_id) {
		$data = array(
			'post_id'			=> $post_id,
			'ppp'				=> true,
			'cost'				=> $post_cost,
			'duration'			=> 1,
			'item_name'			=> get_bloginfo() . ' ' . __('Post Purchase:', 'ym') . ' ' . $post_title
		);
		return $data;
	}
	function pay_per_post_bundle($pack_cost, $pack_id, $title) {
		$data = array(
			'ppp_pack_id'	=> $pack_id,
			'ppp'			=> true,
			'cost'			=> $pack_cost,
			'duration'		=> 1,
			'item_name'		=> get_bloginfo() . ' ' . __('Bundle Purchase:', 'ym') . ' ' . $title
		);
		return $data;
	}

	// process
	function do_process() {
		global $ym_sys;

		$order_id = ym_get('order_id', FALSE);
		if ($order_id) {
			$url = get_option('ym_fbook_order_' . $order_id);
			delete_option('ym_fbook_order_' . $order_id);
			header('Location: ' . $url);
			exit;
		}

		$resp = array();

		$data = array();
		foreach ($_POST as $key => $item) {
			$data[$key] = stripslashes($item);
		}
		if (isset($data['order_info'])) {
			$data['order_info'] = str_replace('"', '', $data['order_info']);
		}

		$facebook_data = ym_post('signed_request');
		if ($facebook_data = $this->facebook_uncode($facebook_data)) {
			$resp['method'] = $data['method'];

			switch ($data['method']) {
				case 'payments_get_items':
					$product_id = $data['order_info'];

					list($buy, $what, $id, $user_id, $cost) = explode('_', $product_id);
					$failed = FALSE;

					switch ($what) {
						case 'post':
							$title = __('Post', 'ym');
							$description = get_the_title($id);
							$image_url = $this->credits_purchase_post_image ? $this->credits_purchase_post_image : YM_IMAGES_DIR_URL . 'document_image_ver.png';
							$pricingid = 'post_' . $id;
							$pack = array('ppp' => TRUE, 'post_id' => $id);
							break;
						case 'bundle':
							$title = __('Bundle', 'ym');
							$description = 'Bundle';
							$image_url = $this->credits_purchase_bundle_image ? $this->credits_purchase_bundle_image : YM_IMAGES_DIR_URL . 'document_copies.png';
							$pricingid = 'bundle_' . $id;
							$pack = array('ppp' => TRUE, 'ppp_pack_id' => $id);
							break;
						case 'subscription':
							$title = __('Subscription', 'ym');
							$description = (isset($pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name);
							$image_url = $this->credits_purchase_sub_image ? $this->credits_purchase_sub_image : YM_IMAGES_DIR_URL . 'wordpress_blog.png';
							$pricingid = 'subscription_' . $id;
							$pack = $id;
							break;
					}

					// :-P
					wp_set_current_user($user_id);

					$item = array(
						'item_id'		=> $product_id,
						'title'			=> $title,
						'description'	=> $description,
						'image_url'		=> str_replace('https', 'http', $image_url),
						'product_url'	=> $this->redirectlogic($pack),
						'price'			=> $cost,//already converted $this->credits_convert($cost, $pricing_id),
						'data'			=> $data['order_info']
					);

					// build response
					$resp['content'][] = $item;
					break;
				case 'payments_status_update':
					$status = $data['status'];
					if ($status == 'placed') {
						// user has selected check out the cart/purchase

						// accept the transaction
						$resp['content']['status'] = 'settled';
					} else if ($status == 'settled') {
						// transaction has completed
						// true IPN occurs now

						// process details
//						$details = $data['order_details'];
//						$details = json_decode($details);
						// reprocess the data packet
						// to deal with large integers
						$details = $this->largeint($data['order_details']);

						$data = $details->items[0]->data;
						$data = explode('_', $data);
						$cost = array_pop($data);
						$data = implode('_', $data);

						$product_url = $details->items[0]->product_url;

						// store stuff for the IPN trans log
						$_POST = array_merge(
							array(
								'mod' => $this->code,
								'gateway' => $this->code,
								'product_url' => $product_url,
							), $_POST);
						unset($_POST['signed_request']);
						unset($details->signed_request);
						foreach ($details as $detail => $val) {
							$_POST['fb_' . $detail] = $val;
						}
						
						$this->common_process($data, $cost, TRUE, FALSE);
						
						// store the product URL
						update_option('ym_fbook_order_' . $_POST['order_id'], $product_url);
					} else {
						$resp['error'] = 'error';
					}

					break;
				default:
					$resp['error'] = 'error';
			}
		} else {
			$resp['error'] = 'error';
		}

		$resp = json_encode($resp);
		echo $resp;
		exit;
	}
	
	// options
	function load_options() {
		if ($this->sane) {
			echo '<p>Loading</p><meta http-equiv="refresh" content="0;' . YM_ADMIN_URL . '&ym_page=other_ymfacebook&ym_fb_tab_select=3" />';
		} else {
			ym_display_message(__('Could not activate ym_facebook_credits, ym_facebook is not installed', 'ym'), 'error');
		}
		return FALSE;
	}

	// additional pack fields
	function additional_pack_fields() {
		$items = array();
		$items[] = array(
			'name' => 'facebook_credits_enable',
			'label' => __('Purchasable Facebook Credits', 'ym'),
			'caption' => '',
			'type' => 'checkbox'
		);
		return $items;
	}

	// additional function
	function facebook_uncode($data) {
		list($encoded_sig, $payload) = explode('.', $data, 2);

		$sig	= base64_decode(strtr($encoded_sig, '-_', '+/'));
		$data	= base64_decode(strtr($payload, '-_', '+/'));
		$data	= json_decode($data);

		if (strtoupper($data->algorithm) !== 'HMAC-SHA256') {
			// bad alg
			return false;
		}

		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $this->app_secret, $raw = true);
		if ($sig !== $expected_sig) {
			// bad json
			return false;
		}

		if ($data) {
			return $data;
		}
		return;
	}

	// JSON Large Int
	function largeint($rawjson) {
		$rawjson = substr($rawjson, 1, -1);
		$rawjson = explode(',', $rawjson);
		array_walk($rawjson, array($this, 'strfun'));
		$rawjson = implode(',', $rawjson);
		$rawjson = '{' . $rawjson . '}';

		$json = json_decode($rawjson);
		return $json;
	}

	function strfun(&$entry, $key) {
		$data = explode(':', $entry);
		if (FALSE === strpos($data[1], '"')) {
			$data[1] = '"' . $data[1] . '"';
			$entry = implode(':', $data);
		}
	}
	// End JSON large int

	/*
	* USD -> Facebook
	* 0.10 -> 1
	* 1 -> 10
	*/
	function credits_convert($price, $id = '') {
		global $ym_res;

		// check for override and return it
		if ($id) {
			$pricing_data = get_option('ym_fbook_pricing');
			
			if ($pricing_data->$id) {
				return $pricing_data->$id;
			}
			
			list($type, $id) = explode('_', $id);
			$id = $type . '_override';
			
			if ($pricing_data->$id) {
				return $pricing_data->$id;
			}
		}
		
		if ($price == 0) {
			return $price;
		}
		
		// use dollar
		if ($ym_res->currency == 'USD') {
			// nothing to do
		} else {
			$price = $price * $this->exchange_rate;
			//$price = bcmul($price, $this->facebook_settings->exchange_rate);
		}
		// USD to Credits
		// 0.1 USD is 1 credit
		// 1 USD is 10 credits
		$price = $price * 10;
		
		// handle rounding
		switch ($this->exchange_round) {
			default:
			case 'round_up':
				$price = round($price, 0);//, PHP_ROUND_HALF_UP);
				break;
			case 'round_5':
				$price = $price * 2;
				$price = $price / 10;
				$price = round($price, 0);//, PHP_ROUND_HALF_UP);
				$price = $price * 10;
				$price = $price / 2;
				break;
			case 'round_10':
				$price = $price / 10;
				$price = round($price, 0);//, PHP_ROUND_HALF_UP);
				$price = $price * 10;
				break;
			case 'round_up_5':
				$price = round($price, 0);//, PHP_ROUND_HALF_UP);
				$end_char = substr($price, -1, 1);
				if ($end_char <= 5) {
					$end_char = 5 - $end_char;
					$price = $price + $end_char;
				} else if ($end_char <= 9) {
					$end_char = 10 - $end_char;
					$price = $price + $end_char;
				}
				break;
			case 'round_up_10':
				$price = round($price, 0);//, PHP_ROUND_HALF_UP);
				$end_char = substr($price, -1, 1);
				if ($end_char <= 9) {
					$end_char = 10 - $end_char;
					$price = $price + $end_char;
				}
		}

		$price = number_format($price, 0);
		
		return $price;
	}

}

add_filter('ym_filter_gateway', 'ym_facebook_credits_filter', 10, 3);
function ym_facebook_credits_filter($code, $function, $id) {
	global $ym_active_modules;
	$mycode = 'ym_facebook_credits';

	if (!$_SESSION['in_facebook']) {
		return $code;
	}

	if (!in_array($mycode, $ym_active_modules)) {
		return $code;
	}
	$obj = get_option('ym_fbook_options');
	if (!$obj->credits_exclusive) {
		return $code;
	}

	if ($code != $mycode) {
		return 'disable';
	} else {
		return $code;
	}
}

