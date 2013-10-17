<?php

/*
* $Id: ym_authorize_net.php 2211 2012-07-09 22:01:15Z bcarlyon $
* $Revision: 2211 $
* $Date: 2012-07-09 23:01:15 +0100 (Mon, 09 Jul 2012) $
*/

/*
* http://www.authorize.net/support/ARB_guide.pdf
* http://www.authorize.net/support/CIM_XML_guide.pdf
* 
* http://www.authorize.net/support/SIM_guide.pdf
* http://www.authorize.net/support/AIM_guide.pdf
* 
*/

class ym_authorize_net extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_authorize_net';

	var $action_url_test_sim = 'https://test.authorize.net/gateway/transact.dll';
	var $action_url_live_sim = 'https://secure.authorize.net/gateway/transact.dll';

	var $action_url_test_aim = 'https://test.authorize.net/gateway/transact.dll';
	var $action_url_live_aim = 'https://secure.authorize.net/gateway/transact.dll'; 

	var $action_url_test_arb = 'https://apitest.authorize.net/xml/v1/request.api';
	var $action_url_live_arb = 'https://api.authorize.net/xml/v1/request.api';

	function __construct() {
		$this->version = '$Revision: 2211 $';
		$this->name = __('Memberships with Authorize.net', 'ym');
		$this->description = __('Authorize.Net. AIM and SIM integration for Post/Content Purchase.', 'ym');// And ARB for Subscriptions', 'ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		if ($this->status == 'live') {
			$var = 'action_url_live_sim';
			if ($this->mode == 'aim') {
				$var = 'action_url_live_aim';
			} else if ($this->mode == 'arb') {
				$var = 'action_url_live_arb';
			}
		} else {
			$var = 'action_url_test_sim';
			if ($this->mode == 'aim') {
				$var = 'action_url_test_aim';
			} else if ($this->mode == 'arb') {
				$var = 'action_url_test_arb';
			}
		}
		$this->action_url = $this->$var;
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/authorize_net.gif';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';
			$this->mode = 'sim';

			$this->loginid = '';
			$this->transkey = '';
			$this->md5hash = '';

			$this->callback_script = '';

			$this->save();
		}
	}
	function deactivate() {
	}

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
		
		$data = array(
			'x_version' 			=> YM_PLUGIN_VERSION,
			'x_type'				=> 'AUTH_CAPTURE',
			'x_login'				=> $this->loginid,

			'x_amount'				=> $override_price ? $override_price : $pack['cost'],

			'x_show_form'			=> 'PAYMENT_FORM',
			'x_delim_data'			=> ($this->mode == 'aim') ? 'TRUE' : 'FALSE',//AIM needs true SIM needs FALSE
			'x_method'				=> 'CC',
			'x_recurring_billing'	=> (isset($pack['num_cycles']) && $pack['num_cycles'] != 1 ? 'TRUE':'FALSE'),
			'x_description'			=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),

			'x_first_name'			=> (isset($current_user->first_name) ? $current_user->first_name : $current_user->display_name),
			'x_last_name'			=> (isset($current_user->last_name) ? $current_user->last_name : $current_user->display_name),
			'x_email'				=> $current_user->user_email,
			'x_cust_id'				=> $user_id,

			// SIM Specific
			'x_receipt_link_method'	=> 'POST',
			'x_receipt_link_text'	=> sprintf(__('Return to %s', 'ym'), get_bloginfo()),

			// SIM Specific
			'x_relay_response'		=> ($this->mode == 'sim') ? 'TRUE' : 'FALSE',
			'x_relay_url'			=> site_url('?ym_process=' . $this->code),

			// both
			'x_email_customer'		=> 'TRUE',
			'x_test_request'		=> ($this->status == 'live' ? 'FALSE' : 'TRUE')
		);
		$data['x_amount'] = number_format($data['x_amount'], 2, '.', '');

		// tax
		if ((isset($pack['vat_applicable']) && $pack['vat_applicable']) || $ym_sys->global_vat_applicable) {
			if ($ym_sys->vat_rate) {
				$data['x_tax'] = $ym_sys->vat_rate;
				$data['x_tax_exempt'] = 'FALSE';
			}
		}
		if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
			$data['x_tax'] = $vat_rate;
			$data['x_tax_exempt'] = 'FALSE';
		}

		// Both
		if (isset($pack['id'])) {
			$data['x_custom'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			if (isset($pack['ppp_pack_id'])) {
				$data['x_custom'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['x_custom'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['x_custom'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
		}

//		$data['x_receipt_link_url'] = $this->redirectlogic($pack);
		$data['x_receipt_link_url'] = $data['x_relay_url'];
		$data['x_custom_2'] = $this->redirectlogic($pack);

		// Fingerprint both
		$data = array_merge(
			$data,
			$this->generate_finger_print($data['x_amount'])
		);

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

	// process
	function do_process() {
		if (!ym_post('x_response_code') || !ym_post('x_custom') || !isset($_POST['x_trans_id'])) {
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Missing Details.';
			exit;
		}

		if ($this->md5hash) {
			if (!ym_post('x_MD5_Hash')) {
				header('HTTP/1.1 401 Unauthorized');
				echo 'Error In IPN. Security Check Failed, no sig';
				exit;
			}			
			// hash verify
			$my_hash = strtoupper(md5($this->md5hash . $this->loginid . ym_post('x_trans_id') . ym_post('x_amount')));

			if ($my_hash != ym_post('x_MD5_Hash')) {
				header('HTTP/1.1 401 Unauthorized');
				echo 'Error In IPN. Security Check Failed, no sig match';
				exit;
			}
		}

		$complete = FALSE;
		if (ym_post('x_response_code') == 1) {
			$complete = TRUE;
		}

		$this->common_process(ym_post('x_custom'), ym_post('x_amount'), $complete, FALSE);
		echo __('Payment Processing', 'ym');
		echo '<meta http-equiv="refresh" content="5;' . ym_post('x_custom_2') . '" />';
		exit;
	}

	function fail_process() {
		$data = array();

		switch(ym_post('x_response_code')) {
			case 4:
				// pending/review
				$data['new_status'] = YM_STATUS_PENDING;
				$data['status_str'] = sprintf(__('Last payment is pending/held in review %s.','ym'), $_POST['x_response_reason_text']);
			case 3:
				// error
				$data['new_status'] = YM_STATUS_ERROR;
				$data['status_str'] = sprintf(__('Last payment status: %s','ym'), $_POST['x_response_reason_text']);
			case 2:
				// declined
				$data['new_status'] = YM_STATUS_NULL;
				$data['status_str'] = sprintf(__('Last payment was refunded or denied %s','ym'), $_POST['x_response_reason_text']);
				$data['expiry'] = time();
		}

		return $data;
	}

	// options
	function load_options() {
		ym_display_message(__('Authorize.net can only handle transactions in USD', 'ym'), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'loginid',
			'label'		=> __('Your Login ID', 'ym'),
			'caption'	=> __('The API access login ID provided by Authorize.Net', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'transkey',
			'label'		=> __('Your Transaction Key', 'ym'),
			'caption'	=> __('The transaction key provided by Authorize.Net', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'md5hash',
			'label'		=> __('Your MD5 Hash Word', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'mode',
			'label'		=> __('Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'select',
			'options'	=> array(
				'sim'	=> __('SIM - Simple Integration Method', 'ym'),
				'aim'	=> __('AIM - Advanced Integration Method', 'ym')
			)
		);
		$options[] = array(
			'name'		=> 'status',
			'label'		=> __('Test Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'status'
		);

		return $options;
	}

	// helpder functions
	function generate_finger_print($cost) {
		$sequence = rand(1, 1000);
		$timestamp = time();
		
		// The following lines generate the SIM fingerprint.  PHP versions 5.1.2 and
		// newer have the necessary hmac function built in.  For older versions, it
		// will try to use the mhash library.
		if (phpversion() >= '5.1.2') {
			$fingerprint = hash_hmac("md5", $this->loginid . "^" . $sequence . "^" . $timestamp . "^" . $cost . "^", $this->transkey);
		} else {
			$fingerprint = bin2hex(mhash(MHASH_MD5, $this->loginid . "^" . $sequence . "^" . $timestamp . "^" . $cost . "^", $this->transkey));
		}
		
		$data = array(
			'x_fp_hash'			=> $fingerprint,
			'x_fp_timestamp'	=> $timestamp,
			'x_fp_sequence'		=> $sequence
		);
		
		return $data;
	}
}
