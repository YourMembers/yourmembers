<?php

/*
* $Id: ym-payment-gateway.class.php 2557 2013-01-23 16:41:06Z bcarlyon $
* $Revision: 2557 $
* $Date: 2013-01-23 16:41:06 +0000 (Wed, 23 Jan 2013) $
* Payment Gateway base file
*/

class ym_payment_gateway {
	var $name;
	var $code; // identical to sub class name
	var $description;
	var $logo;

	var $callback_script;

	// Buttons
	// $return return or echo
	function generateButtons($return = false) {
		$test = apply_filters('ym_filter_gateway', $this->code, 'generateButtons', FALSE);
		if ($test != $this->code) {
			return;
		}

		$html = '';

		$this->obj = get_option($this->code);

		global $ym_packs;
		$packs = $ym_packs->packs;

		$packs = apply_filters('ym_packs', $packs, $this->code);
		if (method_exists($this, 'pack_filter')) {
			$packs = $this->pack_filter($packs);
		}

		$user_id = ym_get_user_id();

		$html .= apply_filters('ym_generatebuttons_formstart', '');
		$html .= '<div class="like_form">';
		$html .= '<h3 class="ym_register_heading">' . __('Please choose a Membership Pack', 'ym') . '</h3>';

		if (count($packs) > 0) {
			foreach ($packs as $pack) {
				$html .= apply_filters('ym_generatebuttons_formabove', '');
				$html .= $this->getButton($pack['id']);
				$html .= apply_filters('ym_generatebuttons_formbelow', '');
			}
		}
		$html .= '</div>';
		$html .= apply_filters('ym_generatebuttons_formend', '');
		if (ym_get('sel', FALSE)) {
			$html .= '<a href="' . str_replace('sel=' . $this->code, '', $_SERVER['REQUEST_URI']) . '" style="float: right; margin-right: 20px;">' . __('Back to Gateway Select', 'ym') . '</a>';
		}

		if ($return) {
			return $html;
		} else {
			echo $html;
			return;
		}
	}

	// generate a form for a sub pack purchase (buy now essentially)
	function getButton($packID, $override_price = FALSE) {
		$test = apply_filters('ym_filter_gateway', $this->code, 'getbutton', $packID);
		if ($test != $this->code) {
			return;
		}

		$pack = ym_get_pack_by_id($packID);
		if (method_exists($this, 'pack_filter')) {
			$packs = array(
				$pack
			);
			$packs = $this->pack_filter($packs);
			$pack = isset($packs[0]) ? $packs[0] : FALSE;
		}

		if ($pack && FALSE !== array_search($this->code, $pack['gateway_disable'])) {
			// remove
			return;
		}

		$r = '';
		if ($pack) {
			if (method_exists($this, 'getButtonOverride')) {
				return $this->getButtonOverride($pack, ym_get_user_id(), $override_price);
			}

			$button_code = $this->get_button_code($pack, ym_get_user_id(), $override_price);
			$button_code = apply_filters('ym_additional_code', $button_code, $this->code, 'buy_subscription_' . $packID . '_' . ym_get_user_id());

			$button_code_html = '';
			foreach ($button_code as $item => $val) {
				$button_code_html .= '<input type="hidden" name="' . $item . '" value="' . $val . '" />' . "\n";
			}

			$r = '
<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form" name="' . $this->code . '_form" id="' . $this->code . '_form">
	<fieldset>
		<strong>' . ym_get_pack_label($pack['id']) . '
		';
		if ($override_price) {
			$r .= '<br />' . $override_price . ' ' . ym_get_currency($pack['id']) . ' ' . __('First Period', 'ym');
		}
		$r .= '</strong><br />';
		$r .= '
		' . $button_code_html . '
		<input type="image" class="' . $this->code . '_button" src="' . $this->logo . '" border="0" name="submit" alt="' . $this->membership_words . '" />
	</fieldset>
</form>
';
		}
		return $r;
	}

	// pay per post
	function gen_buy_now_button($post_cost, $post_title, $return = false, $post_id = false) {
		$test = apply_filters('ym_filter_gateway', $this->code, 'gen_buy_now_button', $post_id);
		if ($test != $this->code) {
			return;
		}

		$data = false;
		if (method_exists($this, 'pay_per_post')) {
			if (!$post_id) {
				$post_id = get_the_ID();
			}
			// pay per post is supported with this gateway
			$data = $this->pay_per_post($post_cost, $post_title, $return, $post_id);
		}

		if ($data) {
			if (method_exists($this, 'gen_buy_now_button_override')) {
				$html = $this->gen_buy_now_button_override($post_cost, $post_title, $return, $post_id, $data);
				if ($return) {
					return $html;
				} else {
					echo $html;
					return;
				}
			}
			$button_code = $this->get_button_code($data, ym_get_user_id());
			$button_code = apply_filters('ym_additional_code', $button_code, $this->code, 'buy_post_' . $post_id . '_' . ym_get_user_id());
			
			$button_code_html = '';
			foreach ($button_code as $item => $val) {
				$button_code_html .= '<input type="hidden" name="' . $item . '" value="' . $val . '" />' . "\n";
			}

			$html = '
			<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form form-table" name="' . $this->code . '_form" id="' . $this->code . '_form"><fieldset>
					' . $button_code_html . '
					<input type="image" class="' . $this->code . '_button" src="' . $this->logo . '" name="submit" alt="' . $this->post_purchase_words . '" />
				</fieldset></form>';

			if ($return) {
				return $html;
			} else {
				echo $html;
			}
		}
	}

	function gen_buy_ppp_pack_button($pack_cost, $pack_id, $pack_title, $return = FALSE) {
		$test = apply_filters('ym_filter_gateway', $this->code, 'gen_buy_ppp_pack_button', $pack_id);
		if ($test != $this->code) {
			return;
		}

		$data = FALSE;
		if (method_exists($this, 'pay_per_post_bundle')) {
			$data = $this->pay_per_post_bundle($pack_cost, $pack_id, $pack_title);
		}

		if ($data) {
			if (method_exists($this, 'gen_buy_ppp_pack_button_override')) {
				$html = $this->gen_buy_ppp_pack_button_override($pack_cost, $pack_title, $return, $pack_id, $data);
				if ($return) {
					return $html;
				} else {
					echo $html;
				}
			}
			$button_code = $this->get_button_code($data, ym_get_user_id());
			$button_code = apply_filters('ym_additional_code', $button_code, $this->code, 'buy_bundle_' . $pack_id . '_' . ym_get_user_id());
			
			$button_code_html = '';
			foreach ($button_code as $item => $val) {
				$button_code_html .= '<input type="hidden" name="' . $item . '" value="' . $val . '" />' . "\n";
			}

			$html = '
			<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form form-table" name="' . $this->code . '_form" id="' . $this->code . '_form"><fieldset>
					' . $button_code_html . '
					<input type="image" class="' . $this->code . '_button" src="' . $this->logo . '" name="submit" alt="' . $this->bundle_purchase_words . '" />
				</fieldset></form>';

			if ($return) {
				return $html;
			} else {
				echo $html;
			}
		}
	}

	// process
	function process() {
		$this->do_process();
		header('HTTP/1.1 404 File Not Found');
		echo 'ok';
		exit;
	}

	function common_process($item_field, $cost_field, $complete, $exit = TRUE) {
		list($buy, $what, $id, $user_id) = explode('_' , $item_field);
		$failed = FALSE;

		if ($buy == 'buy' && ($what == 'post' || $what == 'bundle')) {
			// post/page/pack
			if ($id && $user_id) {
				if ($what == 'bundle') {
					$id = 'bundle' . $id;
				}
				if ($cost_field) {
					@ym_log_transaction(YM_PAYMENT, $cost_field, $user_id);
				}
				$this->do_buy_post($id, $user_id, $complete);
			} else {
				$failed = TRUE;
			}
		} else if ($buy == 'buy' && $what == 'subscription') {
			// subs
			if ($id && $user_id) {
				if ($cost_field) {
					@ym_log_transaction(YM_PAYMENT, $cost_field, $user_id);
				}
				$this->do_buy_subscription($id, $user_id, $complete);
			} else {
				$failed = TRUE;
			}
		} else {
			$failed = TRUE;
			$failed = apply_filters('ym_purchase_unknown', $failed, $item_field, $cost_field, $complete, $exit);
		}
		if ($failed) {
			// failed on what to buy/id/user_id missing
			header('HTTP/1.1 400 Bad Request');
			$return = FALSE;
		} else {
			header('HTTP/1.1 200 OK');
			$return = TRUE;
		}

		if ($exit) {
			// bang bang you shot me down?
			exit;
		}
		return $return;
	}

	function do_buy_post($postId, $userId, $complete = FALSE) {
		global $wpdb, $ym_sys;

		@ym_log_transaction(YM_IPN, $_POST, $userId);

		$posts = false;
		$pack = false;

		if (substr($postId, 0, 6) == 'bundle') {
			$pack = substr($postId, 6);
			$postId = false;
			$posts = array();
		}

		if ($complete) {
			if ($pack) {
				@ym_log_transaction(YM_PPP_PACK_PURCHASED, $pack, $userId);

				if (!ym_has_purchased_bundle($pack, $userId)) {
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_post_packs_purchased(user_id, pack_id, unixtime, payment_method)
						VALUES
						(' . $userId . ', \'' . $pack . '\', UNIX_TIMESTAMP(), \'' . addslashes($this->code) . '\')
						';
					$wpdb->query($sql);
				}
			} else if (strpos($postId, ',') !== false) {
				// Todo: remove ppp
				// support old system for the moment
				// This should switch over to ad hoc?/cart
				@ym_log_transaction(YM_PPP_PACK_PURCHASED, $postId, $userId);

				$posts = explode(',', $postId);
			} else {
				@ym_log_transaction(YM_PPP_PURCHASED, $postId, $userId);
				$posts = array($postId);
			}

			$posts = array_unique($posts);

			foreach ($posts as $post_id) {
				if (!ym_has_purchased_post($post_id, $userId)) {
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'posts_purchased(user_id, post_id, unixtime, payment_method)
							VALUES
							(' . $userId . ', \'' . $post_id . '\', UNIX_TIMESTAMP(), \'' . addslashes($this->code) . '\')
							';
					$wpdb->query($sql);

					// logged in logged out email?
				}
			}
		}

		//Do Return Action
		$packet = array(
			'user_id' => $userId,
			'post_id' => $postId,
			'ppack_id' => $pack,
			'status' => $complete
		);
		if ($complete) {
			do_action('ym_post_transaction_success', $packet);
		} else {
			do_action('ym_post_transaction_failed', $packet);
		}
		do_action('ym_gateway_return', $packet);
		do_action('ym_gateway_return_' . $this->code, $packet);

		$this->notify_user($packet);
	}
	function do_buy_subscription($subId, $userId, $complete = FALSE) {
		global $ym_sys;
		// assumes complete
		@ym_log_transaction(YM_IPN, $_REQUEST, $userId);

		if ($complete) {
			@ym_log_transaction(YM_PACKAGE_PURCHASED, $subId, $userId);

			$pack = ym_get_pack_by_id($subId);
			if (!$pack) {
				// unknown pack
				$complete = 'FALSE';
			} else {
				$user = new YourMember_User($userId);
				// get current
				$current = $user->pack_id;
				$extend = FALSE;

				// extend
				// ONLY extend if same package type (ie better pack for the same type)
				//   SO different Pack IDs
				// - like a switch from a monthly sub to a yearly sub
				// and current status is active
				// if been set inactivate then new sub
				if ($user->account_type == $pack['account_type'] && 
					$user->pack_id != $subId &&
					($user->status == YM_STATUS_ACTIVE || $user->status == YM_STATUS_GRACE)
					) {
					$extend = $user->expire_date;
				}
				// check for pack ID's the same
				// and extend allow
				// make sure expire date in the future
				if ($user->pack_id == $subId
					&& $ym_sys->allow_upgrade_to_same
					&& $user->expire_date > time()
					) {
					$extend = $user->expire_date;
				}

				// patch :-P
				$pack['amount'] = $pack['cost'];

				// use magic
				// use an array so can pass to update
				// other wise direct calls to object....
				$data = array();
				// this is crap
				// TODO: takes the whole pack and stores it in the user object.....
				foreach ($user as $key => $value) {
					if (isset($pack[$key])) {
						$data[$key] = $pack[$key];
					}
				}
				// end crap

				// additonal
				$data['pack_id'] = $subId;
				$data['status'] = YM_STATUS_ACTIVE;
				$data['reminder_email_sent'] = FALSE;

				if ($this->code == 'ym_gift') {
					$data['status_str'] = __('Gift Giving was Successful', 'ym');
				} else if ($this->code == 'ym_dropdown') {
					$data['status_str'] = __('DropDown was Successful','ym');

				} else if ($extend) {
					$data['status_str'] = __('Subscription Extension Successful', 'ym');
				} else {
					$data['status_str'] = __('Last payment was successful', 'ym');
				}
				$data['account_type'] = ucwords($pack['account_type']);
				$data['reminder_email_sent'] = FALSE;
				$data['gateway_used'] = $this->code;

				if (!$extend) {
					$data['account_type_join_date'] = time();
				}
				$data['last_pay_date'] = time();

				// log
				@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION, $data['account_type'], $userId);
				@ym_log_transaction(YM_USER_STATUS_UPDATE, YM_STATUS_ACTIVE . ' - ' . $data['status_str'], $userId);

				// apply trial?
				$apply = FALSE;

				// if trial enabled and user not taken
				if ($pack['trial_on'] && $user->trial_taken != $subId) {
					// trial not taken yet then apply trial

					// does the Gateway Used Support a Trial?
					if (method_exists($this, 'enable_trial')) {
						$apply = TRUE;
					}
				}

				if ($apply) {
					$data['trial_on'] = TRUE;
					$data['expire_date'] = $user->expiry_time($data['trial_duration'], $data['trial_duration_type']);

					$data['trial_taken'] = $subId;
				} else {
					$data['trial_on'] = FALSE;
					// most important
					$data['expire_date'] = $user->expiry_time($data['duration'], $data['duration_type'], $extend);
				}

				@ym_log_transaction(YM_ACCESS_EXTENSION, $data['expire_date'], $userId);

				// check for force end
				if (isset($pack['force_end_date'])) {
					$force_end_date = $pack['force_end_date'];
					if ($force_end_date > time()) {
						// greater than now
						@ym_log_transaction(YM_ACCESS_EXTENSION, 'Adjustment (Force End Date): ' . $force_end_date, $userId);
						$data['expire_date'] = $force_end_date;
					}
				}

				// group membership
				$data['child_accounts_allowed'] = $pack['child_accounts_allowed'];
				$data['child_accounts_package_types'] = $pack['child_accounts_package_types'];
				$data['child_accounts_packages'] = $pack['child_accounts_packages'];

				// admin bar control
				$data['hide_admin_bar'] = $pack['hide_admin_bar'];

				$user->update($data);
				$user->save();

				$user->updaterole($pack['role']);
			}
		}

		if (!$complete) {
			$data = array(
				'new_status' => FALSE
			);

			if (method_exists($this, 'fail_process')) {
				$data = $this->fail_process();
			} else {
				$new_status = YM_STATUS_ERROR;
				$status_str = sprintf(__('Last Payment Errored and No Handler Found for the Payment Gateway Response', 'ym'));
				$data = array(
					'new_status' => $new_status,
					'status_str' => $status_str
				);
			}

			if (isset($data['new_status']) && $data['new_status']) {
				@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['new_status'] . ' - ' . $data['status_str'], $userId);

				if (isset($data['expiry']) && $data['expiry']) {
					@ym_log_transaction(YM_ACCESS_EXPIRY, $data['expiry'], $userId);
				}

				$data['status'] = $data['new_status'];
				unset($data['new_status']);

				$user = new YourMember_User($userId);
				$user->update($data);
				$user->save();
			}
		}

		$packet = array(
			'user_id' => $userId,
			'pack_id' => $subId,
			'status' => $complete
		);

		if ($complete) {
			do_action('ym_membership_transaction_success', $packet);
			do_action('ym_membership_transaction_success_' . $this->code, $packet);
		} else {
			do_action('ym_membership_transaction_failed', $packet);
			do_action('ym_membership_transaction_failed_' . $this->code, $packet);
		}
		do_action('ym_gateway_return', $packet);
		do_action('ym_gateway_return_' . $this->code, $packet);

		$this->notify_user($packet);
	}

	function redirectlogic($pack, $go = FALSE) {
		get_current_user();
		global $current_user;
		
		$post = FALSE;
		// redirect logic
		if (!is_array($pack)) {
			// assume packid
			$pack = preg_replace('/[^\d\.]/', '', $pack);
			$pack = ym_get_pack_by_id($pack);
		}

		// pack redirect?
		$red = '';
		$additional = '';
		if (isset($pack['success_redirect']) && $pack['success_redirect']) {
 			$red = site_url($pack['success_redirect']);
		}
		if (isset($pack['ppp'])) {
			$post = TRUE;
			// lifted and merged
			if (isset($pack['ppp_pack_id'])) {
				// bundle
				$additional = 'bundle_id=' . $pack['ppp_pack_id'];
				$item = 'bundle_' . $pack['ppp_pack_id'];
				$word = 'bundle';
			} else {
				// post
				$additional = 'post_id=' . $pack['post_id'];
				$item = 'post_' . $pack['post_id'];
				$word = 'post';
			}
			$red = get_permalink(isset($pack['post_id']) ? $pack['post_id'] : get_the_ID());
			if (!$current_user->ID) {
				$red = get_option('siteurl') . '/wp-login.php?checkemail=registered&redirect_to=' . $red;
			}
		} else {
			$item = 'pack_id_' . $pack['id'];
			$word = 'subscribed';
		}

		$red = $red ? $red : ((isset($this->thanks_url) && $this->thanks_url) ? $this->thanks_url : '');
		$red = $red ? $red : ((isset($this->return_url) && $this->return_url) ? $this->return_url : '');
		$red = $red ? $red : ((isset($this->return) && $this->return) ? $this->return : '');
		$red = $red ? $red : site_url('/wp-login.php?checkemail=' . $word);

		// cord for callback script to fire
		if (strpos($red, '?')) {
			$red .= '&';
		} else {
			$red .= '?';
		}
		$red .= 'from_gateway=' . $this->code . '&';
		if (!$post) {
			$red .= 'pack_id=' . $pack['id'];
		} else {
			$red .= $additional;
		}
		$red .= '&item=' . $item;

		$red = apply_filters('ym_payment_gateway_redirectlogic', $red, $pack, $go);

		if ($go) {
			$this->redirect($red);
		} else {
			return $red;
		}
	}

	function redirect($url) {
		if (!headers_sent()) {
			header('Location: ' . $url);
		} else {
			echo sprintf(__('Sorry your browser can&#39;t redirect. Click <a href="%s">here</a> to continue.', 'ym'), $url);
			echo '<script>window.location="' . $url . '";</script>';
		}
		exit;
	}

	function notify_user($packet, $nomore = false) {
		if (isset($this->nomore_email) && $this->nomore_email) {
			return;
		}
		$this->nomore_email = $nomore;

		global $ym_res;

		$user = get_userdata($packet['user_id']);

		$message_id = '';

		// make sure its an int
		if ($packet['status']) {
			$packet['status'] = 1;
		} else {
			$packet['status'] = 0;
		}

		if (isset($packet['post_id']) && $packet['post_id']) {
			$message_id = 'post_' . $packet['status'];
		} else if (isset($packet['ppack_id']) && $packet['ppack_id']) {
			$message_id = 'ppack_' . $packet['status'];
		} else {
			$message_id = 'pack_' . $packet['status'];
		}

		$target = $subject = $message = $target_scan = '';
		$do = FALSE;
		if (method_exists($this, 'messages')) {
			// if data returned then send email here
			// otherwise no send or payment gateway handles
			// data returns message to send
			$data = $this->messages($message_id, $user, $packet);

			if ($data) {
				$target = $data['to'];
				$subject = $data['subject'];
				$message = $data['message'];
				$target_scan = $data['target_scan'];
				$do = TRUE;
			}
		} else {
			$target = $user->display_name . ' <' . $user->user_email . '>';
			$display_name = $user->display_name;
			// use default messages
			switch ($message_id) {
				case 'post_1':
					if ($ym_res->payment_gateway_enable_post_success) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_post_success';

					$posttitle = get_the_title($packet['post_id']);
					$posttitle = strip_tags($posttitle);
					$postlink = get_permalink($packet['post_id']);

					$subject = $ym_res->payment_gateway_subject_post_success;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[post_title]', $posttitle, $subject);

					$message = $ym_res->payment_gateway_message_post_success;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[post_title]', $posttitle, $message);
					$message = str_replace('[post_link]', $postlink, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);

					break;
				case 'post_0':
					if ($ym_res->payment_gateway_enable_post_failed) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_post_failed';

					$posttitle = get_the_title($packet['post_id']);
					$posttitle = strip_tags($posttitle);

					$subject = $ym_res->payment_gateway_subject_post_failed;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[post_title]', $posttitle, $subject);

					$message = $ym_res->payment_gateway_message_post_failed;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[post_title]', $posttitle, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);
					break;
				case 'ppack_1':
					if ($ym_res->payment_gateway_enable_ppack_success) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_ppack_success';

					$pack_data = ym_get_bundle($packet['ppack_id']);
					$posts = ym_get_bundle_posts($packet['ppack_id']);

					$post_urls = '';
					foreach ($posts as $post) {
						$post_urls .= '<a href="' . get_permalink($post->post_id) . '">' . get_the_title($post->post_id) . '</a><br />';
					}

					$subject = $ym_res->payment_gateway_subject_ppack_success;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[pack_title]', $pack_data->name, $subject);

					$message = $ym_res->payment_gateway_message_ppack_success;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[pack_name]', $pack_data->name, $message);
					$message = str_replace('[posts_in_pack]', $post_urls, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);
					break;
				case 'ppack_0':
					if ($ym_res->payment_gateway_enable_ppack_failed) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_ppack_failed';

					$pack_data = ym_get_bundle($packet['ppack_id']);

					$subject = $ym_res->payment_gateway_subject_ppack_failed;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[pack_title]', $pack_data->name, $subject);

					$message = $ym_res->payment_gateway_message_ppack_failed;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[pack_name]', $pack_data->name, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);
					break;
				case 'pack_1':
					if ($ym_res->payment_gateway_enable_subscription_success) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_subscription_success';

					$label = ym_get_pack_label($packet['pack_id']);
					$label = strip_tags($label);

					$user = new YourMember_User($packet['user_id']);
					$expire = $user->expire_date;
					$f = YM_DATE;
					$expire = date($f, $expire);

					$subject = $ym_res->payment_gateway_subject_subscription_success;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[pack_label]', $label, $subject);

					$message = $ym_res->payment_gateway_message_subscription_success;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[pack_label]', $label, $message);
					$message = str_replace('[pack_expire]', $expire, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);
					break;
				case 'pack_0':
					if ($ym_res->payment_gateway_enable_subscription_failed) {
						$do = TRUE;
					}
					$target_scan = 'payment_gateway_email_subscription_failed';

					$label = ym_get_pack_label($packet['pack_id']);
					$label = strip_tags($label);

					$subject = $ym_res->payment_gateway_subject_subscription_failed;
					$subject = str_replace('[blogname]', get_option('blogname'), $subject);
					$subject = str_replace('[pack_label]', $label, $subject);

					$message = $ym_res->payment_gateway_message_subscription_failed;
					$message = str_replace('[display_name]', $display_name, $message);
					$message = str_replace('[pack_label]', $label, $message);
					$message = str_replace('[blogname]', get_option('blogname'), $message);
					break;
			}
		}

		remove_all_shortcodes();
		// login user to alow shortcodes to behave
		wp_set_current_user($packet['user_id']);
		// apply ym_user_custom shortcode
		add_shortcode('ym_user_is', 'ym_user_is');
		add_shortcode('ym_user_is_not', 'ym_user_is_not');
		add_shortcode('ym_user_custom', 'ym_shortcode_user');
		$message = do_shortcode($message);
		// transaction log ID
		global $ym_this_transaction_id;
		$log_id = 'YMTRANS_' . $ym_this_transaction_id;
		$message = str_replace('[ym_log_id]', $log_id, $message);

		$message_prepend = __('This is a copy of a Message not sent', 'ym');
		if ($do) {
			ym_email($target, $subject, $message);
			$message_prepend = __('This is a copy of a Message sent', 'ym');
		}
		// additional targets?
		if (is_array($ym_res->$target_scan)) {
			$subject = 'CC: ' . $subject;
			$message = $message_prepend . '<br /><br />' . $message;
			foreach ($ym_res->$target_scan as $target) {
				if ($target) {
					ym_email($target, $subject, $message);
				}
			}
		}
	}

	function buy_email($userId) {
		$user = 'SELECT user_email FROM ' . $wpdb->users . ' WHERE ID = ' . $userId;
		foreach ($wpdb->get_results($user) as $row) {
			$email = $row->user_email;
		}
		return $email;
	}

	// options

	function options() {
		global $ym_upload_url, $ym_upload_root;

		if ($_POST && isset($_POST['submit'])) {
			// call save
			if (method_exists($this, 'save_options')) {
				$this->save_options();
			} else {
				$this->buildnsave();
			}
		}

		echo '<div class="wrap" id="poststuff">';
		if (method_exists($this, 'my_options')) {
			// use class methid
			$this->my_options();
		} else if (method_exists($this, 'load_options')) {
			ym_box_top('&nbsp');
			echo '<form action="" method="post" enctype="multipart/form-data">';
			// use generator
			$options = $this->load_options();
//			if (!$options) {
			if (!is_array($options)) {
				return;
			}

			if (method_exists($this, 'pay_per_post_bundle')) {
				array_unshift($options,
					array(
						'name'		=> 'bundle_purchase_words',
						'label'		=> __('Bundle Purchase', 'ym'),
						'caption'	=> __('You can change the Gateway Name/Bundle Purchase Words display to something more customer Friendly', 'ym'),
						'type'		=> 'text'
					)
				);
			}
			if (method_exists($this, 'pay_per_post')) {
				array_unshift($options,
					array(
						'name'		=> 'post_purchase_words',
						'label'		=> __('Post Purchase', 'ym'),
						'caption'	=> __('You can change the Gateway Name/Post Purchase Words display to something more customer Friendly', 'ym'),
						'type'		=> 'text'
					)
				);
			}
			array_unshift($options,
				array(
					'name'		=> 'membership_words',
					'label'		=> __('Gateway Name', 'ym'),
					'caption'	=> __('You can change the Gateway Name/Membership Words display to something more customer Friendly', 'ym'),
					'type'		=> 'text'
				)
			);

			$options[] = array(
				'name'		=> 'logo',
				'label'		=> __('Button/Logo', 'ym'),
				'caption'	=> '',
				'type'		=> 'image'
			);
			$options[] = array(
				'name'		=> 'callback_script',
				'label'		=> __('Callback Script', 'ym'),
				'caption'	=> __('Javascript to run on a successfully return from PayPal, useful for integration with affiliate schemes or other tracking. This is a Raw HTML field and is applied to the HTML Head. You can use these short codes to represent certain vaiables: [user_id], [pack_id], [post_id], [post_pack_id], [cost], [account_type], [item_code], [if_cb_pack][/if_cb_pack] show content if a pack, [if_cb_post][/if_cb_post] show content if a post, [if_cb_bundle][/if_cb_bundle] show content if a bundle', 'ym'),
				'type'		=> 'textarea'
			);

			foreach ($options as $option) {
				echo '<div class="ym_option">';
				echo '<label for="' . $option['name'] . '">' . $option['label'] . '</label>';

				if ($option['caption']) {
					echo '<p class="caption">' . $option['caption'] . '</p>';
				}

				$var = $option['name'];
				$val = isset($this->$var) ? $this->$var : '';

				if ($option['type'] == 'image') {
					echo '<p class="caption">' . __('Current:', 'ym') . '<br /><img src="' . $val . '" alt="Image" /></p>';
				}

				echo '<span class="input">';
				switch ($option['type']) {
					case 'yesno':
						echo '<select name="' . $option['name'] . '">';
						echo '<option value="1" ' . (($val == 1) ? 'selected="selected"': '') . ' >' . __('Yes', 'ym') . '</option>';
						echo '<option value="0"' . (($val == 1) ? '': 'selected="selected"') . '>' . __('No', 'ym') . '</option>';
						echo '</select>';
						break;
					case 'status':
						$option['options'] = array(
							'test' => 'test',
							'live' => 'live'
						);
					case 'select':
						echo '<select name="' . $option['name'] . '">';
						if ($option['type'] == 'select') {
							echo '<option value="">' . __('--Select--', 'ym') . '</option>';
						}

						foreach ($option['options'] as $optionkey => $optionval) {
							echo '<option value="' . $optionkey . '"';
							if ($val == $optionkey) {
								echo ' selected="selected" ';
							}
							echo '>' . ucwords($optionval) . '</option>';
						}

						echo '</select>';
						break;
					case 'image':
					case 'file':
						echo '<input type="file" name="' . $option['name'] . '" id="' . $option['name'] . '" />';
						break;
					case 'textarea':
						echo '<textarea name="' . $option['name'] . '" id="' . $option['name'] . '" cols="50" rows="5">' . $val . '</textarea>';
						break;
					case 'wp_editor':
						echo '<div style="width: 650px;">';
						wp_editor($val, $option['name'], array('media_buttons' => FALSE));
						echo '</div>';
						break;
					case 'url':
						echo site_url();
					case 'text':
					default:
						echo '<input type="text" name="' . $option['name'] . '" id="' . $option['name'] . '" value="' . $val . '" />';
				}
				echo '</span>';
				echo '</div>';
			}
			echo '<input type="submit" class="button-primary" style="float: right;" name="submit" value="' . sprintf(__('Save Settings for %s', 'ym'), $this->name) . '" />';
			echo '</form>';
			ym_box_bottom();

			if (method_exists($this, 'options_additional')) {
				$this->options_additional();
			}
		} else {
			echo '<div id="message" class="error"><p>' . sprintf(__('There are no user settable options for this gateway, %s', 'ym'), $this->name) . '</p></div>';
		}
		echo '</div>';
		return;
	}

	function buildnsave() {
		global $ym_upload_url, $ym_upload_root;

		foreach ($_POST as $var => $val) {
			$this->$var = $val;
		}
		foreach ($_FILES as $name => $file) {
			$tmp = $file['tmp_name'];
			if (is_uploaded_file($tmp)) {
				// use the upload class
				$ym_upload = new ym_dl_file_upload;
				$ym_upload->upload_dir = $ym_upload_root;
				$ym_upload->max_length_filename = 100;
				$ym_upload->rename_file = false;

				$ym_upload->the_temp_file = $file['tmp_name'];
				$ym_upload->the_file = $file['name'];
				$ym_upload->http_error = $file['error'];
				$ym_upload->replace = "y";
				$ym_upload->do_filename_check = "n";

				if ($ym_upload->upload()) {
					$filename = $ym_upload_url . $ym_upload->file_copy;
					$this->$name = $filename;
				} else {
					ym_display_message(sprintf(__('Unable to move file to %s', 'ym'), $ym_upload->upload_dir), 'error');
				}
				unlink($tmp);
			}
		}
		$this->callback_script = stripslashes($this->callback_script);
		// trigger object save
		$this->save();
	}

	function save() {
		if (isset($this->version)) {
			unset($this->version);//never store the version
		}
		update_option($this->code, $this);
		if (ym_get('ym_page')) {
			echo '<div id="message" class="updated fade"><p>' . sprintf(__('Updated Settings for %s', 'ym'), $this->name) . '</div>';
		}
	}
}

// active onky
function ym_spawn_gateways() {
	global $ym_active_modules;

	$units = array();
	foreach ($ym_active_modules as $module) {
		$units[$module] = new $module();
	}
	return $units;
}

function ym_packs_gateways_extra_fields_display($pack) {
	global $ym_active_modules, $ym_formgen;

	foreach ($ym_active_modules as $module) {
		$class = new $module();
		if (method_exists($class, 'additional_pack_fields')) {
			$fields = $class->additional_pack_fields();
			foreach ($fields as $field) {
				$ym_formgen->render_form_table_text_row($field['label'], $field['name'], (isset($pack[$field['name']]) ? $pack[$field['name']] : ''), $field['caption']);
			}
		}
	}

	do_action('ym_packs_gateways_extra_fields_display', $pack);

	return $pack;
}
function ym_packs_gateways_extra_fields_post($data) {
	global $ym_active_modules;

	foreach ($ym_active_modules as $module) {
		$class = new $module();
		if (method_exists($class, 'additional_pack_fields')) {
			$fields = $class->additional_pack_fields();
			foreach ($fields as $field) {
				$var = $field['name'];
				$data[$var] = ym_post($var);
			}
		}
	}

	$data = apply_filters('ym_packs_gateways_extra_fields_post', $data);

	return $data;
}

function ym_packs_gateways_trial_on() {
	global $ym_active_modules;

	$trial = FALSE;

	foreach ($ym_active_modules as $module) {
		$class = new $module();
		if (method_exists($class, 'enable_trial')) {
			$trial = TRUE;
		}
	}

	return $trial;
}

// combo function, removes hidden packs and packs that are not available for this gateway
add_filter('ym_packs', 'ym_packs_filter_hidden_packs', 10, 2);
function ym_packs_filter_hidden_packs($packs, $gateway_code = FALSE) {
	foreach ($packs as $index => $pack) {
		// hidden?
		if ($pack['hide_subscription']) {
			unset($packs[$index]);
			continue;
		}
		if ($gateway_code) {
			// unavilable
			if (FALSE !== array_search($gateway_code, $pack['gateway_disable'])) {
				unset($packs[$index]);
			}
		}
	}

	return $packs;
}

// gw unsub
function ym_get_user_unsub_button_gateway($atts = array()) {
	global $ym_user;
	
	if ($ym_user->gateway_used) {
		$class = $ym_user->gateway_used;
		if (class_exists($class)) {
			$obj = new $class();
			if (method_exists($obj, 'ym_profile_unsubscribe_button')) {
				$text = isset($atts['text']) ? $atts['text'] : '';
				$button = isset($atts['button']) ? $atts['button'] : '';
				return $obj->ym_profile_unsubscribe_button(TRUE, $text, $button);
			}
		}
	}
}
