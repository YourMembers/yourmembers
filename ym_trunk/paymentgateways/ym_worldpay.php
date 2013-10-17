<?php

/*
* $Id: ym_worldpay.php 2565 2013-01-25 12:19:58Z bcarlyon $
* $Revision: 2565 $
* $Date: 2013-01-25 12:19:58 +0000 (Fri, 25 Jan 2013) $
*/

/*
http://www.worldpay.com/support/bg/index.php?page=guides&c=WW
http://www.worldpay.com/support/kb/bg/pdf/rhtml.pdf 52
http://www.worldpay.com/support/kb/bg/pdf/payment_response.pdf 13
http://www.worldpay.com/support/kb/bg/pdf/tm.pdf 9
http://www.worldpay.com/support/kb/bg/pdf/rpfp.pdf 72

http://www.worldpay.com/support/kb/bg/paymentresponse/pr5501.html
http://www.worldpay.com/support/kb/bg/paymentresponse/pr5502.html

*/

class ym_worldpay extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_worldpay';

	var $action_url_test = 'https://secure-test.worldpay.com/wcc/transaction';
	var $action_url_live = 'https://secure.worldpay.com/wcc/transaction';

	function __construct() {
		$this->version = '$Revision: 2565 $';
		$this->name = __('Make payments with WorldPay', 'ym');
		$this->description = __('<p>Worldpay is much like paypal except no account is needed to buy anything. All major credit cards accepted.</p>','ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		if ($this->status == 'test') {
			$this->action_url = $this->action_url_test;
		} else {
			$this->action_url = $this->action_url_live;
		}
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/worldpay.jpg';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';
			$this->inst_id = '';

			$this->callbackPW = '';//payment response
			$this->md5_sig = '';//md5 secret
			$this->preauth = 'A';// preset preauth
			$this->iadmin_inst_id = '';//iAdmin install id
			$this->remotePW = '';// Remote Admin PW

			$this->cancel_url = '/';

			$this->callback_script = '';

			$this->save();
		}
	}
	function deactivate() {
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
		get_currentuserinfo();
		global $current_user, $ym_sys;

		$email = isset($current_user->user_email) ? $current_user->user_email : '';
		if (!$email) {
			if ($user = ym_get('username')) {
				$user = get_user_by('login', $user);
				$email = $user->user_email;
			}
		}

		$data = array(
			'instId' => $this->inst_id,
			'cartId' => $user_id,
			'amount' => $override_price ? $override_price : $pack['cost'],
			'currency' => esc_html(ym_get_currency($pack['id'])),
			'email' => $email,
			'hideContact' => 0,
			'M_payment_type' => 'one-time',
			'authMode' => $this->preauth,
			'MC_callback' => site_url('?ym_process=' . $this->code),
		);

		if ($this->status == 'test') {
			$data['testMode'] = 100;
		}

		if ($this->md5_sig) {
			$data['signatureFields'] = 'email:cartId:instId';
			$data['signature'] = md5($this->md5_sig . ':' . $data['email'] . ':' . $user_id . ':' . $this->inst_id);
		}

		// addition per type
		if (isset($pack['id'])) {
			// subscription recurring
			$data = array_merge($data, array(
				'M_item_number' => 'buy_subscription_' . $pack['id'] . '_' . $user_id,
				'M_payment_type' => 'subscription'
			));

			$duration_days = $pack['duration'];
			$interval_unit = 1;
			$word = 'days';
			switch ($pack['duration_type']) {
				case 'y':
					$duration_days = $duration_days * 365;
					$interval_unit = 4;
					$word = 'years';
					break;
				case 'm':
					$duration_days = $duration_days * 28;
					$interval_unit = 3;
					$word = 'months';
			}

			if (isset($pack['num_cycles']) && $pack['num_cycles'] != 1) {
				$data['futurePayType'] = 'regular';
				$time = strtotime('+' . $pack['duration'] . ' ' . $word);
				$data['startDate'] = date('Y-m-d', $time);

				$data['noOfPayments'] = ($pack['num_cycles'] ? ($pack['num_cycles'] - 1) : 0);//don't include initial payment
				$data['intervalUnit'] = $interval_unit;
				$data['intervalMult'] = $pack['duration'];
				$data['normalAmount'] = $pack['cost'];
				$data['option'] = 1;
			}


			if ($pack['trial_on']) {
				$interval_unit = 1;

				$duration_days = $pack['trial_duration'];
				switch ($pack['trial_duration_type']) {
					case 'y':
						$duration_days = $duration_days * 365;
						break;
					case 'm':
						$duration_days = $duration_days * 28;
				}
				
				$data['Amount'] = $pack['trial_cost'];
				$data['startDate'] = date('Y-m-d', time() + ($duration_days * 86400));
				$data['noOfPayments'] = $pack['num_cycles'];//dont need to not include
			}
		} else {
			// post or post pack
			if (isset($pack['ppp_pack_id'])) {
				$data['M_item_number'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['M_item_number'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['M_item_number'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id'] : get_the_ID()) . '_' . $user_id;
			}
		}
		$data['M_return'] = esc_html($this->redirectlogic($pack));

		return $data;
	}

	// enable pay per post
	function pay_per_post($post_cost, $post_title, $return, $post_id) {
		$data = array(
			'post_id'		=> $post_id,
			'ppp'			=> true,
			'cost'			=> $post_cost,
			'duration'		=> 1,
			'item_name'		=> get_bloginfo() . ' ' . __('Post Purchase:', 'ym') . ' ' . $post_title
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
	// enable trail
	function enable_trial() {
	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE, $text = '', $button = '') {
		if (!$this->remotePW || !$this->iadmin_inst_id) {
			return;
		}

		global $ym_user;
		$id = get_user_meta($ym_user->ID, 'ym_worldpay_futurepay_id', TRUE);
		if (!$id) {
			return;
		}

		$text = $text ? $text : __('If you wish to unsubscribe you can click the following link.', 'ym');
		$button = $button ? $button : __('Cancel Subscription', 'ym');

		if (ym_post('worldpay_cancel')) {
			$fields = array(
				'instId'		=> $this->iadmin_inst_id,
				'authPW'		=> $this->remotePW,
				'futurePayId'	=> $id,
				'op-cancelFP'	=> 1,
				'cancel'		=> 1
			);

			$url = 'https://secure';
			if ($this->status == 'test') {
				$url .= '-test';
			}
			$url .= '.wp3.rbsworldpay.com/wcc/iadmin';

			// request
			$request = new WP_Http;
			$result = $request->request($url, array('method' => 'POST', 'body' => $fields));

			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('WorldPay Unsubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">';
			if ($result['body'] == 'Y,Agreement cancelled') {
				$html .= '<p>' . __('You have unsubscribed successfully and Future Payments have been Cancelled', 'ym') . '</p>';

				// fire expire
				// set time to now
				$data = array(
					'expire_date' => (time() - 1),
					'status'		=> YM_STATUS_CANCEL
				);
				$ym_user->update($data);
				// do expire check (for drop down)
				$ym_user->expire_check();
				$ym_user->save();

				@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . __('User Unsubscribe', 'ym'), $ym_user->ID);
			} else {
				$html .= '<p>' . sprintf(__('An error occured unsubscribing you: %s', 'ym'), $result['body']) . '</p>';
			}
			$html .= '</div></div>';
		} else {
			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('WorldPay Unsubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">' . $text . '</div>
				<div>
					<form action="" method="post">
						<input type="submit" name="worldpay_cancel" value="' . $button . '" class="button-secondary" />
					</form>
				</div>
			</div>';
		}
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}

	// process
	function do_process() {
		// IPN Handler
		echo __('One Moment... Processing', 'ym');

		if (!ym_get('msgType') || !ym_get('installation') || !ym_post('M_item_number')) {
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Missing msgType, installation or item_number.';
			exit;
		}

		if (ym_get('installation') != ym_post('installation') || ym_post('installation') != $this->inst_id) {
			header('HTTP/1.1 401 Unauthorized');
			echo 'Error in IPN. Missing installation ID or Invalid';
			exit;
		}
		if ($this->callbackPW && (ym_post('callbackPW') != $this->callbackPW)) {
			header('HTTP/1.1 401 Unauthorized');
			echo 'Error in IPN. Missing callbackPW or invalid.';
			exit;
		}

		$array = array('Merchant Cancelled', 'Customer Cancelled');
		if (ym_post('futurePayStatusChange') && in_array(ym_post('futurePayStatusChange'), $array)) {
			// expired!
			$this->common_process(ym_post('M_item_number'), ym_post('cost'), FALSE, FALSE);
		}

		if (ym_post('rawAuthCode') == 'A') {// && ym_post('rawAuthMessage') == 'authorised') {
			// pre 11 catch
			if (isset($_POST['M_custom'])) {
				// is pre
				list($duration, $amount, $currency, $user_id, $account_type, $duration_type) = explode('_', $_POST['M_custom']);
				global $ym_packs;
				foreach ($ym_packs->packs as $pack) {
					if (
						(
							md5($pack['account_type']) == strtolower($account_type)
							||
							md5(strtolower($pack['account_type'])) == strtolower($account_type)
						)
						&& $pack['cost'] == $amount && $pack['duration'] == $duration && strtolower($pack['duration_type']) == strtolower($duration_type)) {
							$pack_id = $pack['id'];
						break;
					}
				}
				if ($pack_id) {
					$item = 'buy_subscription_' . $pack_id . '_' . $test[3];
				} else {
					$admin = get_userdata(1);
					$admin_email = $admin->user_email;
					ym_email($admin_email, 'YM 10 Packet failed', 'Could not determine what the user is buying after looping thru all packets Debug: <pre>' . print_r($_POST, TRUE)) . "\n\n\n" . print_r($ym_packs, TRUE) . '</pre>';
					header('HTTP/1.1 400 Bad Request');
					exit;
				}
			} else {
				$item = ym_post('M_item_number');
			}

			// success
			$this->common_process($item, ym_post('cost'), TRUE, FALSE);
			$url = ym_post('M_return');

			if (ym_post('futurePayId')) {
				list($buy, $what, $id, $user_id) = explode('_', $item);

				update_user_meta($user_id, 'ym_worldpay_futurepay_id', ym_post('futurePayId'));
			}
		} else {
			// must be C - cancelled payment
			// where go?
			if (isset($this->cancel_url) && $this->cancel_url) {
				$url = site_url($this->cancel_url);
			} else {
				$url = site_url('/');
			}
		}
		echo '<meta http-equiv="refresh" content="0;' . $url . '" />';
		exit;
	}
	function fail_process() {
		$data = array();

		$array = array('Merchant Cancelled', 'Customer Cancelled');
		if (ym_post('futurePayStatusChange') && in_array(ym_post('futurePayStatusChange'), $array)) {
			$data['new_status'] = YM_STATUS_CANCEL;
			$data['status_str'] = sprintf(__('Last payment was cancelled: %s','ym'), ym_post('futurePayStatusChange'));
			$data['expiry'] = time();
		}

		return $data;
	}

	// options
	function load_options() {
		ym_display_message(sprintf(__('You need to set the "<strong>Payment Response URL</strong>" in your WorldPay Installation Interface to one of the following: %s', 'ym'), '<ul><li><input type="text" value="<wpdisplay item=MC_callback>" style="width: 400px;" /></li><li><input type="text" value="' . site_url('?ym_process=' . $this->code) . '" style="width: 400px;" /></li></ul>'), 'updated');
		ym_display_message(__('For iAdmin you need WorldPay Support to create an installtion specifically for this, WorldPay Support will provide the Admin Password for this to Apply Below', 'ym'), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'inst_id',
			'label' 	=> __('Installation ID', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'callbackPW',
			'label' 	=> __('Payment Response Password', 'ym'),
			'caption'	=> __('Called: "Payment Response password" in the WorldPay Installation Editor, optional, use to increase security', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'md5_sig',
			'label' 	=> __('MD5 Secret for Transactions', 'ym'),
			'caption'	=> __('Called: "MD5 secret for transactions" in the WorldPay Installation Editor, optional, use to increase security', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'status',
			'label'		=> __('Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'status'
		);

		$options[] = array(
			'name'		=> 'preauth',
			'label'		=> __('Auth Mode', 'ym'),
			'caption'	=> __('Set the authMode this should match your settings in WorldPay and will be A or E'),
			'type'		=> 'select',
			'options'	=> array(
				'A'	=>	__('A - Full Auth'),
				'E'	=>	__('E - Pre Auth')
				)
		);


		$options[] = array(
			'name'		=> 'iadmin_inst_id',
			'label' 	=> __('iAdmin Installation ID', 'ym'),
			'caption'	=> __('This will allow, subscribers to cancel a subscription within YourMembers', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'remotePW',
			'label' 	=> __('Remote Admin PW', 'ym'),
			'caption'	=> __('When you create a iAdmin installation, you will also be given a Password, place that here', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'cancel_url',
			'label' 	=> __('Cancel URL', 'ym'),
			'caption'	=> __('On Payment Cancel return to this URL', 'ym'),
			'type'		=> 'url'
		);

		return $options;
	}
}
