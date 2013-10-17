<?php

/*
* $Id: ym_skrill.php 2398 2012-10-24 15:30:10Z tnash $
* $Revision: 2398 $
* $Date: 2012-10-24 16:30:10 +0100 (Wed, 24 Oct 2012) $
*/

/*
* http://www.moneybookers.com/merchant/en/moneybookers_gateway_manual.pdf
*/

class ym_skrill extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_skrill';

	var $action_url = 'https://www.moneybookers.com/app/payment.pl';

	function __construct() {
		$this->version = '$Revision: 2398 $';
		$this->name = __('Make payments with Skrill (Moneybookers)', 'ym');
		$this->description = __('Skrill (Moneybookers) is the cheaper way to send and receive money worldwide. Secure and convenient online payments', 'ym');
		
		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/skrill.gif';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->pay_to_email = '';
			$this->secretword = '';
			$this->secretword_secret = '';
			$this->merchantid = '';

			$this->cancel_url = '/';

			$this->language = 'EN';
			$this->gateway_logo = '';
			$this->slim_gateway = 0;

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

		$data = array(
			'pay_to_email'		=> $this->pay_to_email,
			'cancel_url'		=> site_url($this->cancel_url),
			'status_url'		=> site_url('?ym_process=' . $this->code),
			'language'			=> $this->language,
			'hide_login'		=> $this->slim_gateway,

			'pay_from_email'	=> $current_user->user_email,
			'currency'			=> esc_html(ym_get_currency($pack['id'])),
			'merchant_fields'	=> 'packdata',
		);
		$data['rec_status_url'] = $data['status_url'];
		$cost = $override_price ? $override_price : $pack['cost'];

		if ($this->gateway_logo) {
			$data['logo_url'] = site_url($this->gateway_logo);
		}

		// calc redirect
		$data['return_url'] = esc_html($this->redirectlogic($pack));

		// pricing
		$vat = FALSE;
		if ((isset($pack['vat_applicable']) && $pack['vat_applicable']) || $ym_sys->global_vat_applicable) {
			if ($ym_sys->vat_rate) {
				$vat = $ym_sys->vat_rate;
			}
		}
		if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
			$vat = $vat_rate;
		}
		if ($vat) {
			$vat = ($data['amount'] / 100) * $vat;
			$cost = ($cost + $vat);
			$data = array_merge($data, array(
				'amount2'				=> $vat,
				'amount2_description'	=> __('Tax/VAT', 'ym')
			));
		}

		// addition per type
		if (isset($pack['id']) && $pack['id']) {
			$types = array(
				'd' => 'day',
				'm' => 'month',
				'y' => 'year',
			);

			// subscription payment
			$start = time();
			$dt = getdate($start);
			if (strtolower($pack['duration_type']) == 'm') {
				$end = mktime(0, 0, 0, $dt['mon'] + ($pack['duration'] * $pack['num_cycles']), $dt['mday'], $dt['year']);
			} elseif (strtolower($pack['duration_type']) == 'd') {
				$end = mktime(0, 0, 0, $dt['mon'], $dt['mday'] + ($pack['duration'] * $pack['num_cycles']), $dt['year']);
			} else {
				$end = mktime(0, 0, 0, $dt['mon'], $dt['mday'], $dt['year'] + ($pack['duration'] * $pack['num_cycles']));
			}

			if ($pack['trial_on']) {
				$data['amount'] = $pack['trial_cost'];

				$dtstart = getdate($start);
				$dtend = getdate($end);
				if (strtolower($pack['trial_duration_type']) == 'm') {
					$start = mktime(0, 0, 0, $dtstart['mon'] + $pack['trial_duration'], $dtstart['mday'], $dtstart['year']);
					$end = mktime(0, 0, 0, $dtend['mon'] + $pack['trial_duration'], $dtend['mday'], $dtend['year']);
				} elseif (strtolower($pack['trial_duration_type']) == 'd') {
					$start = mktime(0, 0, 0, $dtstart['mon'], $dtstart['mday'] + $pack['trial_duration'], $dtstart['year']);
					$end = mktime(0, 0, 0, $dtend['mon'], $dtend['mday'] + $pack['trial_duration'], $dtend['year']);
				} else {
					$start = mktime(0, 0, 0, $dtstart['mon'], $dtstart['mday'], $dtstart['year'] + $pack['trial_duration']);
					$end = mktime(0, 0, 0, $dtend['mon'], $dtend['mday'], $dtend['year'] + $pack['trial_duration']);
				}
			}

			$data = array_merge($data, array(
				'detail1_description'	=> __('Subscription:', 'ym'),
				'detail1_text'			=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),
				'packdata'				=> 'buy_subscription_' . $pack['id'] . '_' . $user_id,
			));
			if ($pack['num_cycles']) {
				// it has end date
				$data['rec_end_date'] = date('d/m/Y', $end);
			}
			if ($pack['num_cycles'] != 1) {
				// it recurs
//				$data['amount'] = $override_price ? $override_price ? $data['amount'];
				$data = array_merge($data, array(
					'rec_amount'			=> $cost,
					'rec_start_date'		=> date('d/m/Y', $start),
					'rec_period'			=> $pack['duration'],
					'rec_cycle'				=> $types[$pack['duration_type']]
				));
			} else {
				$data['amount'] = $cost;
			}
		} else {
			$data['amount'] = $cost;
			// post
			$data = array_merge($data, array(
				'detail1_description'	=> __('Post:', 'ym'),
				'detail1_text'			=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name)
			));
			if (isset($pack['ppp_pack_id'])) {
				$data['packdata'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['packdata'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['packdata'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
		}

		return $data;
	}

	// enable per per post
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
	// enable trial
	function enable_trial() {
	}

	// process
	function do_process() {
		echo 'IPN Handler';

		if (!ym_post('status') || !ym_post('merchant_id') || !ym_post('pay_to_email') || !ym_post('packdata')|| !ym_post('transaction_id')) {
			// Did not find expected POST variables. Possible access attempt from a non Skrill site.
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Missing status or packdata.';
			exit;
		}

		if ($this->merchantid) {
			if (ym_post('merchant_id') != $this->merchantid) {
				header('HTTP/1.1 401 Unauthorized');
				echo 'Error in IPN. Merchant ID Check Failed.';
				exit;
			}
		}

		if ($this->secretword_secret) {
			if (!ym_post('md5sig')) {
				header('HTTP/1.1 401 Unauthorized');
				echo 'Error in IPN.  Security Check Failed, no sig.';
				exit;
			}

			$string = ym_post('merchant_id') . ym_post('transaction_id') . $this->secretword_secret . ym_post('mb_amount') . ym_post('mb_currency') . ym_post('status');
			$string = strtoupper(md5($string));

			if ($string != ym_post('md5sig')) {
				header('HTTP/1.1 401 Unauthorized');
				echo 'Error In IPN. Security Check Failed, invalid sig';
				exit;
			}
		}

		$complete = FALSE;
		if (ym_post('status') == 2) {
			$complete = TRUE;
		}
		$this->common_process(ym_post('packdata'), ym_post('amount'), $complete);
	}
	function fail_process() {
		$data = array();

		switch ($_POST['status']) {
			case 0:
				// pending
				$data['new_status'] = YM_STATUS_PENDING;
				$data['status_str'] = __('Last payment is pending.','ym');
				break;
			case -1:
				// cancelled
			case -2:
				// failed
				$data['status_str'] = __('Last payment was refunded or denied','ym');
			case -3:
			default:
				// chargeback/error
				$data['new_status'] = YM_STATUS_ERROR;
				if ($_POST['status'] == -3) {
					$data['status_str'] = __('Last payment was a chargeback','ym');
				}
				$data['expiry'] = time();
		}

		return $data;
	}

	// options
	function load_options() {
		ym_display_message(__('There is no Test Mode for Skrill', 'ym'), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'pay_to_email',
			'label'		=> __('Your Skrill Email', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'secretword',
			'label'		=> __('Your Skrill SecretWord', 'ym'),
			'caption'	=> __('Helps to Secure your transactions (optional)', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'merchantid',
			'label'		=> __('Your Skrill Merchant ID', 'ym'),
			'caption'	=> __('We use this to help secure your transactions (optional)', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'language',
			'label'		=> __('Language', 'ym'),
			'caption'	=> '',
			'type'		=> 'select',
			'options'	=> array(
				'DA' => __('Belarus', 'ym'),
				'CN' => __('China','ym'),
				'CZ' => __('Czech Republic', 'ym'),
				'FI' => __('Finland', 'ym'),
				'FR' => __('France','ym'),
				'DE' => __('Germany','ym'),
				'GR' => __('Greece', 'ym'),
				'IT' => __('Italy','ym'),
				'NL' => __('Netherlands','ym'),
				'PL' => __('Poland','ym'),
				'RO' => __('Romania', 'ym'),
				'RU' => __('Russia', 'ym'),
				'ES' => __('Spain','ym'),
				'SV' => __('Sweden', 'ym'),
				'TR' => __('Turkey', 'ym'),
				'EN' => __('English','ym')
			)
		);

		$options[] = array(
			'name'		=> 'gateway_logo',
			'label'		=> __('Gateway Logo', 'ym'),
			'caption'	=> __('You can pick a logo to show to Users on the Gateway Payment Page, at most it can be 200px wide and 50px tall', 'ym'),
			'type'		=> 'url'
		);
		$options[] = array(
			'name'		=> 'slim_gateway',
			'label'		=> __('Slim Gateway', 'ym'),
			'caption'	=> __('You can hide the Prominent Login Options', 'ym'),
			'type'		=> 'yesno'
		);

		$options[] = array(
			'name'		=> 'cancel_url',
			'label' 	=> __('Cancel URL', 'ym'),
			'caption'	=> __('On Payment Cancel return to this URL', 'ym'),
			'type'		=> 'url'
		);

		return $options;
	}
	function save_options() {
		$_POST['secretword_secret'] = ''; 
		// stop the blank string md5 error 
		if (ym_post('secretword')) { 
			$_POST['secretword_secret'] = strtoupper(md5($_POST['secretword'])); 
		}
		$this->buildnsave();
	}
}
