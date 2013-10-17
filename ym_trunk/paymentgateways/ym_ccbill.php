<?php

/*
* $Id: ym_ccbill.php 2585 2013-02-04 21:44:13Z bcarlyon $
* $Revision: 2585 $
* $Date: 2013-02-04 21:44:13 +0000 (Mon, 04 Feb 2013) $
*/

/*
* http://www.ccbill.com/cs/MG.php
* http://www.ccbill.com/cs/manuals/CCBill_SMS_Users_Guide.pdf
* http://www.ccbill.com/cs/manuals/CCBill_Data_Link_Extract_Users_Guide.pdf
* http://www.ccbill.com/cs/manuals/CCBill_Dynamic_Pricing.pdf
* http://ccbill.com/cs/wiki/tiki-index.php?page=Webhooks+User+Guide
*/

class ym_ccbill extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_ccbill';

	var $action_url = 'https://bill.ccbill.com/jpost/signup.cgi';
	var $datalink = 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi';

	function __construct() {
		$this->version = '$Revision: 2585 $';
		$this->name = __('Subscribe with CCBill', 'ym');
		$this->description = __('ccBill is a subscription management service. Your Members integrates with ccBill to check Subscription Status at User Login, PayPerPost is not supported thru this gateway', 'ym');

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
			$this->logo = YM_IMAGES_DIR_URL . 'pg/ccbill.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->clientAccnum = '';
			$this->clientSubacc = '';
			$this->md5salt = '';
			$this->formname = '105cc';

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

	function get_button_code($pack, $user_id) {
		get_currentuserinfo();
		global $current_user;

		if ($current_user->ID == 0) {
			$current_user = ym_get_user_by_username(ym_get('username'));
		}

		$currency_codes = array(
			'AUD'	=> '036',
			'CAD'	=> '124',
			'JPY'	=> '392',
			'GBP'	=> '826',
			'USD'	=> '840',
			'EUR'	=> '978',
		);
		if (array_key_exists(ym_get_currency($pack['id']), $currency_codes)) {
			$code = $currency_codes[ym_get_currency($pack['id'])];
		} else {
			return;
		}

		$data = array(
			'clientAccnum'			=> $this->clientAccnum,
			'clientSubacc'			=> $this->clientSubacc,

			'formName'				=> $this->formname,

			'customer_fname'		=> get_user_meta($current_user->ID, 'first_name', TRUE),
			'customer_lname'		=> get_user_meta($current_user->ID, 'last_name', TRUE),
			'email'					=> $current_user->user_email,
//			'username'				=> $current_user->user_login,
//			'password'				=> 'cake'
		);

		if (isset($pack['id']) && $pack['id']) {
			// convert to days
			switch ($pack['duration_type']) {
				case 'y':
					$duration = $pack['duration'] * 365;
					break;
				case 'm':
					$duration = $pack['duration'] * 30;
					break;
				default:
					$duration = $pack['duration'];
			}
			$data = array_merge($data, array(
				'formPrice'				=> $pack['cost'],// initial price
				'formPeriod'			=> $duration,// no of days of initial billing period
				'currencyCode'			=> $code
			));

			if ($pack['trial_on']) {
				$data['formPrice'] = $pack['trial_cost'];
				// convert to days
				switch ($pack['trial_duration_type']) {
					case 'y':
						$duration = $pack['trial_duration'] * 365;
						break;
					case 'm':
						$duration = $pack['trial_duration'] * 30;
						break;
					default:
						$duration = $pack['trial_duration'];
				}
				$data['formPeriod'] = $duration;
			}

			if (isset($pack['num_cycles']) && $pack['num_cycles'] != 1) {
				// recur
//				unset($data['formPrice'], $data['formPeriod']);

				$data['formRecurringPrice'] = $pack['cost'];
				$data['formRecurringPeriod'] = $duration;
				$data['formRebills'] = $pack['num_cycles'] == 0 ? 99 : $pack['num_cycles'];

				// gen formdigest
				$data['formDigest'] = md5($data['formPrice'] . $data['formPeriod'] . $data['formRecurringPrice'] . $data['formRecurringPeriod'] . $data['formRebills'] . $code . $this->md5salt);
			} else {
				// gen formdigest
				$data['formDigest'] = md5($data['formPrice'] . $data['formPeriod'] . $code . $this->md5salt);
			}

			$data['custom'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		}

		return $data;
	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE, $text = '', $button = '') {
		global $ym_user;
		$id = get_user_meta($ym_user->ID, 'ym_ccbill_subscription_id', TRUE);
		if (!$id) {
			return;
		}

		$text = $text ? $text : __('If you wish to unsubscribe you can click the following link.', 'ym');
		$button = $button ? $button : __('Cancel Subscription', 'ym');

		$html = '<div style="margin-bottom: 10px;">
			<h4>' . __('CCBill Unsubscribe', 'ym') . '</h4>
			<div style="margin-bottom: 10px;">' . $text . '</div>
			<div style="margin-bottom: 10px;">' . sprintf(__('You will need your Subscription ID which is: <strong>%s</strong>', 'ym'), $id) . '</div>
			<div>
				<form action="https://support.ccbill.com/" method="post">
					<input type="submit" name="ccbill_cancel" value="' . $button . '" class="button-secondary" />
				</form>
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
		// IPN Handler
		$eventType = ym_request('eventType');
		$acc_num = ym_request('clientAccnum');
		$sub_num = ym_request('clientSubacc');

		if (!$eventType) {
			header('HTTP/1.1 403 Forbidden');
			echo 'Error in IPN. No Idea what the hell your trying to do';
			exit;
		}
		if ($acc_num != $this->clientAccnum || $sub_num != $this->clientSubacc) {
			header('HTTP/1.1 403 Forbidden');
			echo 'Error in IPN. Client Account Numbers';
			exit;
		}

		global $wpdb;
		$subscriptionId = ym_post('subscriptionId');
		
		switch ($eventType) {
			case 'NewSaleSuccess':
			case 'NewSaleFailure':
				$digest = ym_post('dynamicPricingValidationDigest', false);
				// got something to validate?
				if (ym_post('failureCode')) {
					// failed
					$validate = md5(ym_post('subscriptionId') . 0 . $this->md5salt);
					$complete = false;
				} else {
					// complete
					$validate = md5(ym_post('subscriptionId') . 1 . $this->md5salt);
					$complete = true;
				}
				// validate
				if ($digest != $validate) {
					header('HTTP/1.1 403 Forbidden');
					echo 'Error in IPN. Bad Digest';
					exit;
				}
				// initial purchase
				list($buy, $what, $id, $user_id) = explode('_', ym_post('X-custom'));

				update_user_meta($user_id, 'ym_ccbill_subscription_id', ym_post('subscriptionId'));

				$this->common_process(ym_post('X-custom'), ym_post('billedInitialPrice'), $complete);
				break;
			case 'Cancellation':
				// cancellation

				// load user by sub id
				$user_id = $wpdb->get_var('SELECT user_id FROM ' . $wpdb->usermeta . '
				WHERE meta_key = \'ym_ccbill_subscription_id\'
				AND meta_value = \'' . $subscriptionId . '\'');
				if ($user_id) {
					$ym_user = new YourMember_User($user_id);
					$data = array(
						'expire_date'	=> (time() - 1),
						'status'		=> YM_STATUS_CANCEL,
						'status_str'	=> ym_post('reason')
					);
					$ym_user->update($data);
					// do expire check (for drop down)
					$ym_user->expire_check();
					$ym_user->save();

					@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . $data['status_str'] . ' - ' . __('User Unsubscribe', 'ym'), $ym_user->ID);
				} else {
					// ought to error but the ccbill does nothing with the response
					@ym_log_transaction(YM_IPN, $_REQUEST, 0);
				}
				break;
			case 'RenewalSuccess':
				// success renewal

				// load user by sub id
				$user_id = $wpdb->get_var('SELECT user_id FROM ' . $wpdb->usermeta . '
				WHERE meta_key = \'ym_ccbill_subscription_id\'
				AND meta_value = \'' . $subscriptionId . '\'');
				if ($user_id) {
					$pack = new YourMember_User($user_id);
					$code = 'buy_subscription_' . $pack->pack_id . '_' . $user_id;
					$this->common_process($code, ym_post('billedRecurringPrice'), true);
				} else {
					// ought to error but the ccbill does nothing with the response
					@ym_log_transaction(YM_IPN, $_REQUEST, 0);
				}
				break;
			case 'RenewalFailure':
				// fail renewal
				$user_id = $wpdb->get_var('SELECT user_id FROM ' . $wpdb->usermeta . '
				WHERE meta_key = \'ym_ccbill_subscription_id\'
				AND meta_value = \'' . $subscriptionId . '\'');
				if ($user_id) {
					$ym_user = new YourMember_User($user_id);
					$data = array(
						'expire_date'	=> (time() - 1),
						'status'		=> YM_STATUS_ERROR,
						'status_str'	=> ym_post('failureReason')
					);
					$ym_user->update($data);
					// do expire check (for drop down)
					$ym_user->expire_check();
					$ym_user->save();

					@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . $data['status_str'] . ' - ' . __('User Unsubscribe', 'ym'), $ym_user->ID);
				} else {
					// ought to error but the ccbill does nothing with the response
					@ym_log_transaction(YM_IPN, $_REQUEST, 0);
				}
				break;
			default:
				// something we dont want to handle
				@ym_log_transaction(YM_IPN, $_REQUEST, 0);
		}
	}

	function fail_process() {

		$data = array();

		if (ym_post('reasonForDecline', FALSE)) {
			$data['new_status'] = YM_STATUS_NULL;
			$data['status_str'] = sprintf(__('Payment Declined: %s', 'ym'), ym_post('failureReason'));
			$data['expiry'] = time();
		}

		return $data;
	}

	function load_options() {
		ym_display_message(__('You need to have Dynamic Pricing Enabled on your Account, and have obtained the MD5 Salt Value, both can be done via Client Support.', 'ym'), 'updated');
		ym_display_message(__('You need to have User Management Off and PostBack Disabled, we use WebHooks', 'ym'), 'updated');
		ym_display_message(sprintf(__('Under Webhooks for your Sub Account, add a Webhook of URL %s with NewSaleSuccess NewSaleFailiure,Cancellation,RenewalSuccess,RenewalFailiure checked', 'ym'), site_url()), 'updated');
		ym_display_message(__('Form IDs can be obtained from the Form Admin->View All Forms->Select Form Column inside CCBill or from the Forms box from the General Sub Account Info Area, the Name Column', 'ym'), 'updated');
		ym_display_message(__('Without special intervention from CCBill Client Support, <strong>you can only use packages of 1 2 or 3 month periods</strong>, periods are in days, so a month is am multple of 30 days. If you choose years the multiple is 365, but CCBill may allow yearly subscriptions but not more than 1 year.', 'ym'), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'clientAccnum',
			'label' 	=> __('Client Account Number', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'clientSubacc',
			'label' 	=> __('Sub Account Number', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'md5salt',
			'label' 	=> __('MD5 Salt', 'ym'),
			'caption'	=> __('This can be obtained from Client Support and is required', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'formname',
			'label' 	=> __('Form Name', 'ym'),
			'caption'	=> __('The form template code to use', 'ym'),
			'type'		=> 'text'
		);

		return $options;
	}
}

// we get called/required at boot
if (isset($_GET['clientAccnum']) && isset($_GET['clientSubacc']) && isset($_GET['eventType'])) {
	$_GET['ym_process'] = 'ym_ccbill';
}
