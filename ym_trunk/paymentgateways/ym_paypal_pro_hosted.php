<?php

/*
* $Id: ym_paypal_pro_hosted.php 2398 2012-10-24 15:30:10Z tnash $
* $Revision: 2398 $
* $Date: 2012-10-24 16:30:10 +0100 (Wed, 24 Oct 2012) $
*/

class ym_paypal_pro_hosted extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_paypal_pro_hosted';

	var $action_url_test = 'https://securepayments.sandbox.paypal.com/cgi-bin/acquiringweb';
	var $action_url_live = 'https://securepayments.paypal.com/cgi-bin/acquiringweb';

	function __construct() {
		$this->version = '$Revision: 2398 $';
		$this->name = __('Make Payments with PayPal Pro Hosted', 'ym');
		$this->description = __('PayPal Pro Hosted is a way to take credit card transactions without leaving the site', 'ym');

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
			$this->bypass_paypal_ipn_verification = false;
			$this->bypass_paypal_ipn_ip_verification = false;

			$this->callback_script = '';

			$this->save();
		}
	}
	function deactivate() {
	}

	// drivers
	function sslcheck() {
		$url = site_url();
		$test = substr($url, 0, 5);
		if ($test == 'https') {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	function getButtonOverride($pack, $user_id, $override_price = FALSE) {
		if (!$this->sslcheck()) {
			return;
		}
		$button_code = $this->get_button_code($pack, $user_id, $override_price);
		$button_code = apply_filters('ym_additional_code', $button_code, $this->code, $pack['id']);

		$button_code_html = '';
		foreach ($button_code as $item => $val) {
			$button_code_html .= '<input type="hidden" name="' . $item . '" value="' . $val . '" />' . "\n";
		}

		$r = '
<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form" name="' . $this->code . '_form" id="' . $this->code . '_form" target="ym_pro_iframe">
	<fieldset>
		<strong>' . ym_get_pack_label($pack['id']) . '</strong><br />
		';
		if ($override_price) {
			$r .= '<strong>' . $override_price . ' ' . ym_get_currency($pack['id']) . ' ' . __('First Period', 'ym') . '</strong><br />';
		}
		$r .= '
		' . $button_code_html . '
		<input type="image" src="' . $this->logo . '" border="0" name="submit" alt="' . $this->membership_words . '" id="ym_paypal_pro_button" />
	</fieldset>
</form>
';

		$r .= '
<script type="text/javascript">' . "
	jQuery('#ym_paypal_pro_button').click(function() {
		event.preventDefault();
		jQuery('." . $this->code . "_form').submit();
		jQuery('.ym_form').slideUp();
		jQuery('#ym_pro_iframe_control').slideDown();
	});
</script>
";

		$r .= '
<br />
<div id="ym_pro_iframe_control" style="display: none; text-align: center;">
	<iframe name="ym_pro_iframe" id="ym_pro_iframe" style="width: 580px; height: 550px;';

//	if ($margin) {
	if (defined('subscribe.php')) {
		$r .= ' margin-left: -130px;';
	}

	$r .= ' border: 0px;" scrolling="no">Loading...</iframe>
</div>
<br />
';
		return $r;
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

		$data = array(
			'cmd'			=> '_hosted-payment',
			'subtotal'		=> $override_price ? $override_price : $pack['cost'],
			'business'		=> $this->paypal_handle,
			'paymentaction'	=> 'sale',
			'template'		=> 'templateD',

			'lc'			=> $this->locale,
			'notify_url'	=> site_url('?ym_process=ym_paypal_pro_hosted'),
			'cancel_return'	=> site_url($this->cancel_url),
			'rm'			=> 2,

			'no_shipping'	=> 1,
			'no_note'		=> 1,

			'item_name'		=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),
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

		if (isset($pack['id']) && $pack['id']) {
			$data['item_number'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			if (isset($pack['ppp_pack_id'])) {
				$data['item_number'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['item_number'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['item_number'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
		}

		return $data;
	}

	// enable pay per post
	function pay_per_post($post_cost, $post_title, $return, $post_id) {
		if (!$this->sslcheck()) {
			return FALSE;
		}

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
		if (!$this->sslcheck()) {
			return FALSE;
		}

		$data = array(
			'ppp_pack_id'	=> $pack_id,
			'ppp'			=> true,
			'cost'			=> $pack_cost,
			'duration'		=> 1,
			'item_name'		=> get_bloginfo() . ' ' . __('Bundle Purchase:', 'ym') . ' ' . $title
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

		list($buy, $what, $id, $user_id) = explode('_' , $_POST['custom']);

		// double packet?
		$test_id = isset($_REQUEST['txn_id']) ? strtolower($_REQUEST['txn_id']) : '';
		if ($test_id) {
			$last_id = strtolower(get_user_meta($user_id, 'ym_paypal_pro_hosted_last_txn_id', TRUE));
			if ($test_id && $last_id == $test_id) {
				header('HTTP/1.1 200 OK');
				echo ' double packet';
				exit;
			}
			update_user_meta($user_id, 'ym_paypal_pro_hosted_last_txn_id', $test_id);
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

		$this->common_process(ym_post('custom'), ym_post('payment_gross'), $complete, FALSE);

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

	// options

	function load_options() {
		echo '<div id="message" class="updated"><p>' . __('PayPal Pro Hosted Requires your WordPress install to server over SSL as an additional security precaution', 'ym') . '</p></div>';

		$options = array();

		$options[] = array(
			'name'		=> 'paypal_handle',
			'label' 	=> __('Your PayPal Handle', 'ym'),
			'caption'	=> __('Email or Business ID', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'locale',
			'label'		=> __('PayPal Locale to use', 'ym'),
			'caption'	=> '',
			'type'		=> 'select',
			'options'	=> array(
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
			'name'		=> 'status',
			'label'		=> __('Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'status'
		);

		$options[] = array(
			'name'		=> 'cancel_url',
			'label' 	=> __('Cancel URL', 'ym'),
			'caption'	=> __('On Payment Cancel return to this URL', 'ym'),
			'type'		=> 'url'
		);

		$options[] = array(
			'name'		=> 'bypass_paypal_ipn_verification',
			'label'		=> __('Bypass IPN Verification', 'ym'),
			'caption'	=> '',
			'type'		=> 'yesno'
		);
		$options[] = array(
			'name'		=> 'bypass_paypal_ipn_ip_verification',
			'label'		=> __('Bypass IPN IP Verification', 'ym'),
			'caption'	=> '',
			'type'		=> 'yesno'
		);

		return $options;
	}

	// ADDITIONAL FUNCTIONS
	// PAYPAL SPECIFIC
	function verify_callback() {
		@set_time_limit(60);

		$admin = get_userdata(1);
		$admin_email = $admin->user_email;

		if (!$this->bypass_paypal_ipn_verification) {
			$req = 'cmd=_notify-validate';
			$headers = array();
				
			$domain = $this->action_url;
			$domain = str_replace('https://', '', $domain);
		
			foreach ($_POST as $key=>$value) {
				if (get_magic_quotes_gpc()) {
					$value = stripslashes($value);
				}
		
				$req .= '&' . $key . '=' . $value;
			}
				
			$headers['Content-Type:'] = 'application/x-www-form-urlencoded\r\n';
			$headers['Content-length:'] = 'Content-length: '.strlen($req).'\r\n';
			$request = new WP_Http;
			$reponse = $request->request( $domain , array( 'method' => 'POST', 'body' => $req, 'headers' => $headers ) );
		
			if (!eregi("VERIFIED",$response)) {
				//falls back to IP method. This will cut down the number of cases whereby turning on the override is necessary
				if (!$this->verify_paypal_ipn_ip($_SERVER['REMOTE_ADDR'])) {
					ym_email($admin_email, 'callback failed', "sent a request to host: '" . $domain . "'. \n\n <br />response was: \n\n <br />" . $response . "\n\n <br />post vars: <br /><pre>\n" . print_r($_POST, true)) . '</pre>';
					echo 'IP Verify Fail a';
					header('HTTP/1.1 400 Bad Request');
					die;
				}
			}
		} else if (!$this->verify_paypal_ipn_ip($_SERVER['REMOTE_ADDR'])) {
			ym_email($admin_email, 'callback failed, could not verify request IP', "Client at " . $_SERVER['REMOTE_ADDR'] . " attempted to send an IPN. IP was not in known Paypal addresses and therefore the request was blocked.<br />< br/>post vars: <br /><pre>\n" . print_r($_POST, true)) . '</pre>';
			echo 'IP Verify Fail b';
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
			'multi-currency' => __('Payment waiting for service provider to handle multi-currency process', 'ym'),
			'unilateral' => __('Customer did not register or confirm his/her email yet', 'ym'),
			'upgrade' => __('Waiting for service provider to upgrade the PayPal account', 'ym'),
			'verify' => __('Waiting for service provider to verify his/her PayPal account', 'ym'),
			'*' => __('Unknown error', 'ym')
		);

		$reason = @$_POST['pending_reason'];
		$reason = (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);

		return $reason;
	}
}
