<?php

/*
* $Id: ym_paypal.php 2597 2013-02-07 16:47:41Z bcarlyon $
* $Revision: 2597 $
* $Date: 2013-02-07 16:47:41 +0000 (Thu, 07 Feb 2013) $
*/

class ym_paypal extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_paypal';

	var $action_url_test = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
	var $action_url_live = 'https://www.paypal.com/cgi-bin/webscr';

	function __construct() {
		$this->version = '$Revision: 2597 $';
		$this->name = __('Make payments with PayPal', 'ym');
		$this->description = __('PayPal is an electronic money service which allows you to make payment to anyone online. You can choose to pay using your credit card, debit card, bank account, or PayPal balance and make secure purchases without revealing your credit card number or financial information.', 'ym');
		
		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		if ($this->status == 'live') {
			$this->action_url = $this->action_url_live;
		} else {
			$this->action_url = $this->action_url_test;
		}

		if (!$this->locale) {
			$this->locale = 'US';
		}
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/paypal.jpg';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';
			$this->paypal_handle = 'you@example.com';
			$this->locale = 'US';

			$this->cancel_url = '/';

			$this->paypal_pixel = 'https://www.paypal.com/en_US/i/scr/pixel.gif';

			$this->encrypt_buttons = false;
			$this->cert_id = '';
			$this->cert_upload_path = YM_MODULES_DIR;
			$this->paypal_cert = '';
			$this->public_cert = '';
			$this->private_key = '';
			$this->openssl_path = '/usr/bin/openssl';

			$this->bypass_paypal_ipn_verfication = false;
			$this->bypass_paypal_ipn_ip_verification = false;

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
		global $ym_sys;

		$cost = $override_price ? $override_price : $pack['cost'];

		$data = array(
			'cmd'               => (isset($pack['num_cycles']) && $pack['num_cycles'] != 1 ? '_xclick-subscriptions':'_xclick'),
			'business'          => $this->paypal_handle,
			'item_name'         => ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),
			'no_shipping'       => 1,
			'no_note'           => 1,
			'currency_code'     => esc_html((isset($pack['id']) && $pack['id']) ? ym_get_currency($pack['id']) : ym_get_currency()),
			'lc'                => $this->locale,
			'notify_url'        => site_url('?ym_process=' . $this->code),
			'cancel_return'     => site_url($this->cancel_url),
			'rm'                => 2
		);

		if ((isset($pack['vat_applicable']) && $pack['vat_applicable']) || $ym_sys->global_vat_applicable) {
			if ($ym_sys->vat_rate) {
				$data['tax_rate'] = $ym_sys->vat_rate;
			}
		}

		if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
			$data['tax_rate'] = $vat_rate;
		}

		// calc redirect
		$data['return'] = esc_html($this->redirectlogic($pack));

		// addition per type
		if (isset($pack['id']) && $pack['id']) {
			if ($pack['num_cycles'] == 1) {
				$data = array_merge($data, array(
					'bn'            => 'PP-BuyNowBF',
					'item_number'   => 'buy_subscription_' . $pack['id'] . '_' . $user_id,
					'amount'        => $cost,
					'src'           => 1,
					'sra'           => 1
				));
			} else {
				// subscription payment
				$data = array_merge($data, array(
					'item_number'   => 'buy_subscription_' . $pack['id'] . '_' . $user_id,
					'a3'            => $cost,
					'p3'            => $pack['duration'],
					't3'            => strtoupper($pack['duration_type']),
					'src'           => 1,
					'sra'           => 1
				));
				if (isset($pack['num_cycles']) && $pack['num_cycles']) {
					$data['srt'] = $pack['num_cycles'];
				}
				if ($pack['trial_on']) {
					$data['a1'] = $pack['trial_cost'];
					$data['p1'] = $pack['trial_duration'];
					$data['t1'] = strtoupper($pack['trial_duration_type']);
				}
			}
		} else {
			// post
			$data['bn'] = 'PP-BuyNowBF';
			$data['amount'] = $cost;
			// post
			if (isset($pack['ppp_pack_id'])) {
				$data['item_number'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['item_number'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['item_number'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
			$data['src'] = 0;
			$data['sra'] = 0;
		}

		if ($this->encrypt_buttons) {
			$data['cert_id'] = $this->cert_id;

			$cmd = $this->openssl_path ." smime -sign -signer ". $this->public_cert ." -inkey " . $this->private_key . ' -outform der -nodetach -binary | ' . $this->openssl_path ." smime -encrypt -des3 -binary -outform pem " . $this->paypal_cert;
						$desc = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			);
	
			$process = proc_open($cmd, $desc, $pipes);
	
			if (is_resource($process)) {
				foreach ($data as $key => $value) {
					if ($value) {
						fwrite($pipes[0], $key . '=' . $value . "/n");
					}
				}
				
				fflush($pipes[0]);
				fclose($pipes[0]);
	
				$string = '';
				while (!feof($pipes[1])) {
					$string .= fgets($pipes[1]);
				}

				$string = str_replace("\r\n", "\n", $string);
				$string = str_replace("\n", "", $string);

				fclose($pipes[1]);
				proc_close($process);

				$new_data = array(
					'cmd'       => '_s-xclick',
					'encrypted' => $string
				);
				return $new_data;
			}
		}

		return $data;
	}

	// enable pay per post
	function pay_per_post($post_cost, $post_title, $return, $post_id) {
		$data = array(
			'post_id'       => $post_id,
			'ppp'           => true,
			'cost'          => $post_cost,
			'duration'      => 1,
			'item_name'     => get_bloginfo() . ' ' . __('Post Purchase:', 'ym') . ' ' . $post_title
		);
		return $data;
	}
	function pay_per_post_bundle($pack_cost, $pack_id, $title) {
		$data = array(
			'ppp_pack_id'   => $pack_id,
			'ppp'           => true,
			'cost'          => $pack_cost,
			'duration'      => 1,
			'item_name'     => get_bloginfo() . ' ' . __('Bundle Purchase:', 'ym') . ' ' . $title
		);
		return $data;
	}
	// enable trial
	function enable_trial() {
	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE, $text = '', $button = '') {
		$text = $text ? $text : __('If you wish to unsubscribe you can click the following link. You will be taken your subscriptions page on PayPal where you can cancel at any time.', 'ym');
		
		$html = '<div style="margin-bottom: 10px;">
			<h4>' . __('Paypal Unsubscribe', 'ym') . '</h4>
			<div style="margin-bottom: 10px;">' . $text . '</div>
			<div>
				<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=' . $this->paypal_handle . '">
					<img src="https://www.paypal.com/en_US/i/IntegrationCenter/scr/scr_ppAcceptMark_68x43.jpg" />
				</a>
			</div>
		</div>';
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}

	// process
	function do_process() {
		// IPN handler:
		echo 'IPN Processor'; //leave in to cause 200 OK status to be sent

		// pre process some states that don't return a payment_status
		if (!ym_post('payment_status')) {
			$txn_type = ym_post('txn_type');

			switch ($txn_type) {
				case 'subscr_cancel':
				case 'subscr_eot':
				case 'subscr_failed':
					$_POST['payment_status'] = 'Cancelled';
					break;
				case 'subscr_signup':
					$amount_1 = ym_post('amount1', FALSE);
					if ($amount_1 == '0.00') {
						// its a trial IPN
						$_POST['amount'] = $amount_1;
						$_POST['payment_status'] = 'Completed';
					} else {
						// allow ignore
						$_POST['payment_status'] = 'subscr_signup';
					}
					break;
				case 'subscr_payment':
					$_POST['payment_status'] = 'Completed';
			}
		}
		
		if (!ym_post('test_ipn') && !ym_post('payment_status')) {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Missing payment_status';
			exit;
		}

		$this->verify_callback();
			
		// this will cause errors in older ym versions
		list($buy, $what, $id, $user_id) = explode('_' , $_POST['item_number']);

		$exit_statuses = array('In-Progress', 'Partially-Refunded', 'subscr_signup');

		// handle cases that the system must ignore
		if (in_array($_POST['payment_status'], $exit_statuses)) {
			// ignored
			header('HTTP/1.1 200 OK');
			exit;
		}

		switch ($_POST['payment_status']) {
			case 'Completed':
				// woot
				$complete = TRUE;
				break;
			default:
				$complete = FALSE;                  
		}

		$failed = FALSE;

		// double packet?
		$test_id = isset($_REQUEST['txn_id']) ? strtolower($_REQUEST['txn_id']) : '';
		if ($test_id) {
			$last_id = strtolower(get_user_meta($user_id, 'ym_paypal_last_txn_id', TRUE));
			if ($test_id && $last_id == $test_id) {
				header('HTTP/1.1 200 OK');
				echo ' double packet';
				exit;
			}
			update_user_meta($user_id, 'ym_paypal_last_txn_id', $test_id);
		}

		// cancel abort step
		if ($what == 'subscription' && $_POST['payment_status'] == 'Cancelled') {
			// check for if cancelled last active
			$user = new YourMember_User($user_id);
			if ($user->pack_id != $id) {
				// cancelling something else
				header('HTTP/1.1 200 OK');
				echo ' cancel mismatch active product';
				exit;
			}
			unset($user);
		}

		if ($this->enable_ym10_legacy) {
			add_filter('ym_purchase_unknown', array($this, 'ym10_legacy'), 10, 5);
		}

//		if ($what == 'post' || $what == 'bundle' || $what == 'subscription') {
		// YM 11
		$cost = ym_post('amount', ym_post('mc_gross'));
		$this->common_process($_POST['item_number'], $cost, $complete);

		if ($failed) {
			// failed on what to buy/id/user_id missing
			header('HTTP/1.1 400 Bad Request');
			exit;
		}
		header('HTTP/1.1 200 OK');

		// bang bang you shot me down?
		exit;
	}
	function fail_process() {
		$data = array();
		switch ($_POST['payment_status']) {
			case 'Completed':
			case 'Processed':
				// nothing to do here
				break;
			case 'Reversed':
			case 'Refunded':
			case 'Denied':
				$data['new_status'] = YM_STATUS_NULL;
				$data['status_str'] = __('Last payment was refunded or denied','ym');
				$data['expiry'] = time();
				break;
			case 'Pending':
				$data['new_status'] = YM_STATUS_PENDING;
				$reason = $this->get_pending_reason();
				$data['status_str'] = sprintf(__('Last payment is pending. Reason: %s','ym'), $reason);
				break;
			case 'Cancelled':
				$data['new_status'] = YM_STATUS_CANCEL;
				$data['status_str'] = __('User Cancelled Subscription', 'ym');
				break;
			default:
				$data['new_status'] = YM_STATUS_ERROR;
				$data['status_str'] = sprintf(__('Last payment status: %s','ym'), $_POST['payment_status']);
		}

		return $data;
	}

	//@TODO: Move code to a failed catch//unknown purchase
	function ym10_legacy($failed, $item_field, $cost_field, $complete, $exit) {
		// catch a YM 10 packet, customs only come with YM10
		if (isset($_POST['custom']) && $_POST['custom']) {
			$id = FALSE;
			list($duration, $amount, $currency, $user_id, $account_type, $duration_type, $role, $client_ip, $hide_old_content) = explode('_', $_POST['custom']);
			global $ym_packs;
			foreach ($ym_packs->packs as $pack) {
				if (
					(
						md5($pack['account_type']) == strtolower($account_type)
						||
						md5(strtolower($pack['account_type'])) == strtolower($account_type)
					)
					&&
					(
						$pack['cost'] == $amount
						||
						number_format($pack['cost'], 2) == number_format($amount, 2)
					)
					&& $pack['duration'] == $duration && strtolower($pack['duration_type']) == strtolower($duration_type)) {
					$id = $pack['id'];
					break;
				}
			}
		}

		if ($id && $user_id) {
			//run the code to process a new/extended membership
			$cost = ym_post('amount', ym_post('mc_gross'));
			@ym_log_transaction(YM_PAYMENT, $cost, $user_id);
			$this->do_buy_subscription($id, $user_id, $complete);

			$failed = FALSE;
		} else {
			$failed = TRUE;

			$admin = get_userdata(1);
			$admin_email = $admin->user_email;
			ym_email($admin_email, 'YM 10 Packet failed', 'Could not determine what the user is buying after looping thru all packets Debug: <pre>' . print_r($_POST, TRUE)) . "\n\n\n" . print_r($ym_packs, TRUE) . '</pre>';
		}

		return $failed;
	}

	// options

	function load_options() {
		echo '<div id="message" class="updated"><p>' . __('For more information on SSL with Paypal, Please take a look at <a href="https://www.paypal.com/IntegrationCenter/ic_button-encryption.html">Securing your PayPal Buttons</a>', 'ym') . '</p></div>';

		$options = array();

		$options[] = array(
			'name'      => 'paypal_handle',
			'label'     => __('Your PayPal Handle', 'ym'),
			'caption'   => __('Email or Business ID', 'ym'),
			'type'      => 'text'
		);

		$options[] = array(
			'name'      => 'locale',
			'label'     => __('PayPal Locale to use', 'ym'),
			'caption'   => '',
			'type'      => 'select',
			'options'   => array(
				'AU' => __('Australia', 'ym'),
				'AT' => __('Austria', 'ym'),
				'BE' => __('Belgium', 'ym'),
				'CA' => __('Canada', 'ym'),
				'CN' => __('China', 'ym'),
				'FR' => __('France', 'ym'),
				'DE' => __('Germany', 'ym'),
				'IT' => __('Italy', 'ym'),
				'NL' => __('Netherlands', 'ym'),
				'PL' => __('Poland', 'ym'),
				'ES' => __('Spain', 'ym'),
				'CH' => __('Switzerland', 'ym'),
				'GB' => __('United Kingdom', 'ym'),
				'US' => __('United States', 'ym')
			)
		);

		$options[] = array(
			'name'      => 'status',
			'label'     => __('Mode', 'ym'),
			'caption'   => '',
			'type'      => 'status'
		);

		$options[] = array(
			'name'      => 'cancel_url',
			'label'     => __('Cancel URL', 'ym'),
			'caption'   => __('On Payment Cancel return to this URL', 'ym'),
			'type'      => 'url'
		);

		$options[] = array(
			'name'      => 'encrypt_buttons',
			'label'     => __('Encrypt Buttons', 'ym'),
			'caption'   => '',
			'type'      => 'yesno'
		);
		$options[] = array(
			'name'      => 'cert_upload_path',
			'label'     => __('Cert Upload Path', 'ym'),
			'caption'   => '',
			'type'      => 'file'
		);
		$options[] = array(
			'name'      => 'cert_id',
			'label'     => __('PayPal&#39;s Certificate ID', 'ym'),
			'caption'   => '',
			'type'      => 'text'
		);
		$options[] = array(
			'name'      => 'paypal_cert',
			'label'     => __('PayPal&#39;s Public Certificate', 'ym'),
			'caption'   => '',
			'type'      => 'file'
		);
		$options[] = array(
			'name'      => 'private_key',
			'label'     => __('Your Private Key', 'ym'),
			'caption'   => '',
			'type'      => 'file'
		);
		$options[] = array(
			'name'      => 'public_cert',
			'label'     => __('Your Public Certificate', 'ym'),
			'caption'   => '',
			'type'      => 'file'
		);
		$options[] = array(
			'name'      => 'openssl_path',
			'label'     => __('Open SSL Path', 'ym'),
			'caption'   => __('Usually /usr/bin/openssl', 'ym'),
			'type'      => 'text'
		);

		$options[] = array(
			'name'      => 'bypass_paypal_ipn_verfication',
			'label'     => __('Bypass IPN Verification', 'ym'),
			'caption'   => '',
			'type'      => 'yesno'
		);
		$options[] = array(
			'name'      => 'bypass_paypal_ipn_ip_verification',
			'label'     => __('Bypass IPN IP Verification', 'ym'),
			'caption'   => '',
			'type'      => 'yesno'
		);

		$options[] = array(
			'name'      => 'enable_ym10_legacy',
			'label'     => __('Enable YM10 Legacy', 'ym'),
			'caption'   => '',
			'type'      => 'yesno'
		);

		return $options;
	}


	// ADDITIONAL FUNCTIONS
	// PAYPAL SPECIFIC
	function verify_callback() {
		@set_time_limit(60);

		$admin = get_userdata(1);
		$admin_email = $admin->user_email;

		if (!$this->bypass_paypal_ipn_verfication) {
			$req = 'cmd=_notify-validate';
			$headers = array();
			$domain = 'https://www.sandbox.paypal.com';
				
			if ($this->status == 'live') {
				$domain = 'https://www.paypal.com';
			}
		
//          $domain = str_replace('https://', '', $domain);
		
			foreach ($_POST as $key=>$value) {
				if (get_magic_quotes_gpc()) {
					$value = stripslashes($value);
				}
	
				$req .= '&' . $key . '=' . $value;
			}
				
			$headers['Content-Type:'] = 'application/x-www-form-urlencoded\r\n';
			$headers['Content-length:'] = 'Content-length: '.strlen($req).'\r\n';
			$request = new WP_Http;
			$response = $request->request( $domain , array( 'method' => 'POST', 'body' => $req, 'headers' => $headers ) );

//          if (is_wp_error($response)) {
//              echo $response->get_error_message();
//          }

			if (is_wp_error($response) || FALSE === strpos('VERIFIED', $response['body'])) {
				//falls back to IP method. This will cut down the number of cases whereby turning on the override is necessary
				if (!$this->verify_paypal_ipn_ip($_SERVER['REMOTE_ADDR'])) {
					ym_email($admin_email, 'callback failed', "sent a request to host: '" . $domain . "'. \n\n <br />response was: \n\n <br />" . print_r($response, TRUE) . "\n\n <br />post vars: <br /><pre>\n" . print_r($_POST, true)) . '</pre>';
					header('HTTP/1.1 400 Bad Request');
					die;
				}
			}
		} else if (!$this->verify_paypal_ipn_ip($_SERVER['REMOTE_ADDR'])) {
			ym_email($admin_email, 'callback failed, could not verify request IP', "Client at " . $_SERVER['REMOTE_ADDR'] . " attempted to send an IPN. IP was not in known Paypal addresses and therefore the request was blocked.<br />< br/>post vars: <br /><pre>\n" . print_r($_POST, true)) . '</pre>';
			header('HTTP/1.1 400 Bad Request');
			die;
		}
	}

	function verify_paypal_ipn_ip($ip){
		if ($this->bypass_paypal_ipn_ip_verification) {
			return true;
		}

		//List of known Paypal IPN IPs
		$ips = array('216.113.188.202','216.113.188.203','216.113.188.204','66.211.170.66');
		$return = false;
		
		if (!in_array($ip,$ips)) {
			$host = gethostbyaddr($ip);
			
			//All IPNs should resolve here
			if ($host == 'notify.paypal.com') {
				$return = true;
			}
		} else {
			$return = true;
		}
		
		return $return;
	}

	function get_pending_reason() {
		$pending_str = array(
			'address' => __('Customer did not include a confirmed shipping address', 'ym'),
			'authorization' => __('Funds not captured yet', 'ym'),
			'echeck' => __('eCheck that has not cleared yet', 'ym'),
			'intl' => __('Payment waiting for approval by service provider', 'ym'),
			'multi_currency' => __('Payment waiting for service provider to handle multi-currency process', 'ym'),
			'unilateral' => __('Customer did not register or confirm his/her email yet', 'ym'),
			'upgrade' => __('Waiting for service provider to upgrade the PayPal account', 'ym'),
			'verify' => __('Waiting for service provider to verify his/her PayPal account', 'ym'),
			'*' => __('Unknown error', 'ym')
		);

		$reason = ym_post('pending_reason');
		$reason = (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);

		return $reason;
	}
}
