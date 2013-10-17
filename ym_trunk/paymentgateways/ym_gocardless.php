<?php

/*
* $Id: ym_gocardless.php 2556 2013-01-23 11:34:47Z bcarlyon $
* $Revision: 2556 $
* $Date: 2013-01-23 11:34:47 +0000 (Wed, 23 Jan 2013) $
*/

class ym_gocardless extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_gocardless';

	function __construct() {
		$this->version = '$Revision: 2556 $';
		$this->name = __('Make Payments with GoCardless', 'ym');
		$this->description = __('GoCardless is a way to accept Direct Debit Payments in the UK', 'ym');

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
			$this->logo = YM_IMAGES_DIR_URL . 'pg/gocardless.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';
			$this->cancel_url = '/';

			$this->callback_script = '';

			$this->new_grace = FALSE;
			$this->grace_limit = '7';

			$this->merchant_id = '';
			$this->application_id = '';
			$this->application_secret = '';
			$this->access_token = '';
			$this->magical_word = 'ym';

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
		$this->action_url = site_url('?ym_process=' . $this->code . '&action=go');

		$pack['user_id'] = $user_id;
		$pack['cost'] = $override_price ? $override_price : $pack['cost'];

		return $pack;
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
//	function enable_trial() {
//	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE) {
		if (!$this->access_token) {
			return;
		}

		global $ym_user;

		$id = get_user_meta($ym_user->ID, 'ym_gocardless_active_subscription', TRUE);
		if (!$id) {
			return;
		}

		if (ym_post('gocardless_cancel')) {
			$data = $this->subscriptionCancel($id);

			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('GoCardless UnSubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">';
			if ($data) {
				$html .= '<p>' . __('You have UnSubscribed Successfully', 'ym');

				// fire expire
				$user = new YourMember_User($current_user->ID);
				// set time to now
				$data = array(
					'expire_date' => (time() - 1)
				);
				$user->update($data);
				// do expire check (for drop down)
				$user->expire_check();
				$user->save();
			} else {
				$html .= '<p>' . __('An error occured whilst attempting to UnSubscribe you', 'ym') . '</p>';
			}
			$html .= '</div></div>';
		} else {
			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('GoCardless UnSubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">' . __('If you wish to unsubscribe you can click the following link.', 'ym') . '</div>
				<div>
					<form action="" method="post">
						<input type="submit" name="gocardless_cancel" value="' . __('Cancel Subscription', 'ym') . '" class="button-secondary" />
					</form>
				</div>
			</div>
			';
		}
		if ($return) {
			return $html;
		} else {
			echo $html;
		}
	}

	// process
	function do_process() {
		global $ym_sys;

		$mode = $this->status == 'test' ? TRUE : FALSE;
		$gc = new GoCardless($this->merchant_id, $this->application_id, $this->application_secret, $this->access_token, $mode, $this->magical_word);
		if (!$gc->valid) {
			echo 'An Error Occured. Please contact Site Admin: Invalid Keys';
			exit;
		}

		$action = ym_request('action');

		if ($action == 'go') {
			// redirecting to gocardless
			$pack = $_POST;

			if (isset($pack['num_cycles']) && $pack['num_cycles'] != 1) {
				// subscription

				// convert pack values to something gocardless can understand
				switch ($pack['duration_type']) {
					case 'y':
						// convert to month
						$interval_unit = 'month';
						$interval_length = $pack['duration'] * 12;
						$expire_length = $interval_length * $pack['num_cycles'];
						$expire = mktime(23, 59, 59, date('n', time()) + $expire_length, date('j', time()), date('Y', time()));
						break;
					case 'm':
						$interval_unit = 'month';
						$interval_length = $pack['duration'];
						$expire_length = $interval_length * $pack['num_cycles'];
						$expire = mktime(23, 59, 59, date('n', time()) + $expire_length, date('j', time()), date('Y', time()));
						break;
					case 'd':
						$interval_unit = 'day';
						$interval_length = $pack['duration'];
						$expire_length = $interval_length * $pack['num_cycles'];
						$expire = mktime(23, 59, 59, date('n', time()), date('j', time()) + $expire_length, date('Y', time()));
				}

				$subscription = array(
					'amount'			=> number_format($pack['cost'], 2),
					'interval_length'	=> $interval_length,
					'interval_unit'		=> $interval_unit,//day week month
					'merchant_id'		=> $this->merchant_id,
					'name'				=> get_bloginfo() . ' ' . __('Subscription', 'ym'),
					'description'		=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name)
				);
				if ($subscription['name'] == $subscription['description']) {
					unset($subscription['description']);
				}
				if ($pack['num_cycles'] > 1) {
					$subscription['expire'] = date('c', $expire);
				}
			} else {
				// single purchase
				// bill
				$bill = array(
					'amount'		=> number_format($pack['cost'], 2),
					'merchant_id'	=> $this->merchant_id,
					'name'			=> get_bloginfo() . ' ' . __('Purchase', 'ym'),
					'description'	=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name)
				);
			}

			$data = array();
			// common fields
			$data['redirect_uri'] = site_url('?ym_process=' . $this->code . '&action=confirm');
			$data['cancel_uri'] = site_url($this->cancel_url);

			// state AKA custom
			if (isset($pack['ppp_pack_id'])) {
				$data['state'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $pack['user_id'];
			} else if (isset($pack['post_id'])) {
				$data['state'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id'] : get_the_ID()) . '_' . $pack['user_id'];
			} else {
				$data['state'] = 'buy_subscription_' . $pack['id'] . '_' . $pack['user_id'];
			}

			// user fields
			$user = array();
			if ($first = get_user_meta($pack['user_id'], 'first_name', true)) {
				$user['first_name'] = $first;
			}
			if ($last = get_user_meta($pack['user_id'], 'last_name', true)) {
				$user['last_name'] = $last;
			}
			$user['email'] = get_user_by('id', $pack['user_id']);
			$user['email'] = $user['email']->user_email;

			// generate and go to URL
			if (isset($bill)) {
				$gc->NewPayment($bill, $data, $user);
			} else {
				echo 'sub';
				$gc->NewSubscription($subscription, $data, $user);
			}
			exit;
		}

		if ($action == 'confirm') {
			// perform confirm and redirect
			$state = ym_get('state');
			if (!$state) {
				header('HTTP/1.1 400 Bad Request');
				echo 'Missing State';
				exit;
			}
			$r = $gc->catchReturn();
			if ($r) {
				// update the user and set then to pending or grace
				// cost is 0 as no money yet

				// deny receipt email
				$this->nomore_email = TRUE;
				// process
				$this->common_process($state, '0', FALSE, FALSE);// technically true and Don't exit

				list($buy, $what, $id, $user_id) = explode('_', $state);

				// we need to store the bill/subscription ID in order to track the user
				// state is not returned with webhooks
				$key = ym_get('resource_id');
				$data = array(
					'state'		=> $state,
					'user_id'	=> $user_id,
					'amount'	=> ym_get('amount')
				);
				update_option('ym_gocardless_states_' . $key, $data);

				if ($what == 'post') {
					$pack = array('ppp' => 1, 'post_id' => $id);
				} else if ($what == 'bundle') {
					$pack = array('ppp' => 1, 'ppp_pack_id' => $id);
				} else {
					// subscriptiom
					update_user_meta('ym_gocardless_active_subscription', $key, $user_id);
					$pack = $id;
				}

				$this->redirectlogic($pack, TRUE);
			} else {
				echo 'An Error Occured, you should contact the Site Admin';
				exit;
			}
		}

		// assume webhook
		$data = $gc->catchWebHook();
		if (!$data) {
			header('HTTP/1.1 403 Unauthorised');
			echo 'Signature Invalid';
			exit;
		} else {
			// post or sub?
			// status
			// created failed paid cancelled expired withdrawn

			// abort cases
			// widthdrawn jsut means money has moved from the GC account to the merchant account.
			$aborts = array('created', 'withdrawn');
			if (in_array($data['action'], $aborts)) {
				// ignore created packets
				header('HTTP/1.1 200 OK');
				echo 'ok';
				exit;
			}

			$success_states = array('paid');
			$failed_states = array('failed', 'cancelled', 'expired');

			foreach ($data['resources'] as $packet) {
				$id = $packet->id;
				$status = $packet->status;
				$uri = $packet->uri;

				$source_type = isset($packet->source_type) ? $packet->source_type : '';
				if ($source_type == 'subscription') {
					$id = $packet->source_id;
				}

				$state_data = get_option('ym_gocardless_states_' . $id, FALSE);
				if ($state_data) {
					// packet found
					$state = $state_data['state'];
					$user_id = $state_data['user_id'];
					$amount = $state_data['amount'];

					// store for trans log
					$_POST = $state_data;

					$complete = FALSE;
					if (in_array($status, $success_states)) {
						$complete = TRUE;
					}

					$this->common_process($state, $amount, $complete, FALSE);
				} else {
					$admin = get_userdata(1);
					$admin_email = $admin->user_email;
					ym_email($admin_email, 'GC PAYLOAD STATE FAIL', print_r($packet, TRUE));
				}
			}
			exit;
		}
	}

	function fail_process() {
		$action = ym_request('action');
		$state = ym_get('state');

		if ($action == 'confirm' && $state) {
			list($buy, $what, $id, $user_id) = explode('_', $state);
			// get reg data
			$info = get_userdata($user_id);
			$reg_date = strtotime($info->user_registered);

			$new = FALSE;
			if ($reg_date > (time() - 86400)) {
				// reg today
				$new = TRUE;
			}
			
			// return from gateway
			// go pending
			if (($this->new_grace && $new) || !$new) {
				// apply subscription
				$data['new_status'] = YM_STATUS_GRACE;
				$data['status_str'] = __('Grace Entered, GoCardless Payment Pending', 'ym');
				$data['expire_date'] = time() + (86400 * $this->grace_limit);
			} else {
				$data['new_status'] = YM_STATUS_PENDING;
				$data['status_str'] = __('GoCardless Payment Pending', 'ym');
			}
			return $data;
		}
	}

	// options
	function save_options() {
		// validate
		$mode = $this->status == 'test' ? TRUE : FALSE;
		$gc = new GoCardless($this->merchant_id, $this->application_id, $this->application_secret, $this->access_token, $mode, $this->magical_word);
		if (!$gc->valid) {
			ym_display_message(__('GoCardless indicated your Keys are invalid', 'ym'), 'error');
		} else {
			ym_display_message(__('GoCardless indicated your Keys are valid', 'ym'), 'updated');
		}
		// end
		$this->buildnsave();
	}

	function load_options() {
		ym_display_message(sprintf(__('You need to set the "<strong>Webhook URI</strong>" in the Developer Interface to <strong>%s</strong>', 'ym'), site_url('?ym_process=' . $this->code)), 'updated');
		ym_display_message(sprintf(__('You need to set the "<strong>Cancel URI</strong>" in the Developer Interface to <strong>%s</strong>', 'ym'), site_url('?ym_process=' . $this->code . '&action=cancel')), 'updated');
		ym_display_message(sprintf(__('You need to set the "<strong>Redirect URI</strong>" in the Developer Interface to <strong>%s</strong>', 'ym'), site_url('?ym_process=' . $this->code . '&action=confirm')), 'updated');
		ym_display_message(__('You need to set the "<strong>WebHook Data Type</strong>" in the Developer Interface to <strong>JSON</strong>', 'ym'), 'updated');

		$options = array();
		$options[] = array(
			'name'      => 'status',
			'label'     => __('Live/Sandbox Keys', 'ym'),
			'caption'   => '',
			'type'      => 'status'
		);
		$options[] = array(
			'name'		=> 'merchant_id',
			'label' 	=> __('Merchant ID', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'application_id',
			'label' 	=> __('Application Identifier', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'application_secret',
			'label' 	=> __('Application Secret', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'access_token',
			'label' 	=> __('Access Token', 'ym'),
			'caption'	=> __('Optional - We only use this to allow users to cancel their Subscription from your site', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'magical_word',
			'label' 	=> __('Nonce Phrase', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'cancel_url',
			'label' 	=> __('Cancel URL', 'ym'),
			'caption'	=> __('On Payment Cancel return to this URL', 'ym'),
			'type'		=> 'url'
		);

		$options[] = array(
			'name'		=> 'new_grace',
			'label'		=> __('Put new User into Grace instead of Pending', 'ym'),
			'caption'	=> __('This could mean a user has free access, without paying', 'ym'),
			'type'		=> 'yesno'
		);

		$days =array();
		for ($x=1;$x<=28;$x++) {
			$days[$x] = $x;
		}

		$options[] = array(
			'name'		=> 'grace_limit',
			'label'		=> __('Days to allow payment to clear (days)', 'ym'),
			'caption'	=> __('This is also the Grace Limit Value used for Initial and Renewal Grace', 'ym'),
			'type'		=> 'select',
			'options'	=> $days
		);

		return $options;
	}
}

/**
GoCardless Class
*/
/**
fields array can consist of
redirect_uri
cancel_uri
state -> like custom
Requires but is generated by function
* client_id
* nonce
* timestamp date('c', time())

User array can containt any number of
first_name
last_name
email
billing_address1
billing_address2
billing_town
billing_county
billing_postcode
*/

/*
redirect_uri 
receives via _GET
resource_uri
resource_id
resource_type (bill/sub/pre_auth)
signature - hash hmac of the other 3 or 4 is start included
state
*/

class GoCardless {
	var $valid = FALSE;
	var $filters = FALSE;

	function __construct($merchant_id, $application_id, $application_secret, $access_token = FALSE, $sandbox = TRUE, $magical_word = 'cake') {
		$this->merchant_id = $merchant_id;
		$this->application_id = $application_id;
		$this->application_secret = $application_secret;
		$this->access_token = $access_token;
		$this->magical_word = $magical_word;

		$this->mode = $sandbox ? 'sandbox.' : '';

		// validate
		$this->valid = FALSE;
		if ($this->validate()) {
			$this->valid = TRUE;
		}
		return;
	}

	/**
	Create a One Off Payment Link
		$bill fields
			* Amount
			currency (GBP)
			name
			description
			setup_fee *not implemented*
	*/
	function NewPayment($bill, $fields = array(), $user = array(), $go = TRUE) {
		$data = array();
		foreach ($bill as $key => $item) {
			$data['bill[' . $key . ']'] = $item;
		}
		$data['bill[merchant_id]'] = $this->merchant_id;
		$fields = $this->generateFields($fields);
		foreach ($fields as $key => $item) {
			$data[$key] = $item;
		}
		foreach ($user as $key => $item) {
			$data['bill[user][' . $key . ']'] = $item;
		}
		$data['signature'] = $this->generateSignature($data);
		$url = http_build_query($data);
		$url = 'https://' . $this->mode . 'gocardless.com/connect/bills/new?' . $url;
		if ($go) {
			if (headers_sent()) {
				echo '<meta http-equiv="refresh" content="0;' . $url . '" />';
			} else {
				header('Location: ' . $url);
			}
			exit;
		}
		return $url;
	}

	/**
	Create a Subscription Link
		$sub fields
			* amount
			* interval_length
			* interval_unit
			currency (GBP)
			name
			description
			setup_fee *not implemented*
			trial_length
			trial_unit
			trial_amount *not implemented*
			* merchant_id added automatically
			expires_at ISO 8601 date date('c');
	*/
	function NewSubscription($sub, $fields = array(), $user = array(), $go = TRUE) {
		$data = array();
		foreach ($sub as $key => $item) {
			$data['subscription[' . $key . ']'] = $item;
		}
		$data['subscription[merchant_id]'] = $this->merchant_id;
		$fields = $this->generateFields($fields);
		foreach ($fields as $key => $item) {
			$data[$key] = $item;
		}
		foreach ($user as $key => $item) {
			$data['subscription[user][' . $key . ']'] = $item;
		}
		$data['signature'] = $this->generateSignature($data);

		$url = http_build_query($data);
		$url = 'https://' . $this->mode . 'gocardless.com/connect/subscriptions/new?' . $url;
		if ($go) {
			if (headers_sent()) {
				echo '<meta http-equiv="refresh" content="0;' . $url . '" />';
			} else {
				header('Location: ' . $url);
			}
			exit;
		}
		return $url;
	}

	/**
	Create a pre_authorization
	*/

	/**

	*/
	private function generateFields($fields) {
		$fields['client_id']	= $this->application_id;
		$fields['nonce']		= $this->magical_word . '_' . time();
		$fields['timestamp']	= date('c', time());
		return $fields;
	}

	private function generateSignature($data) {
		ksort($data);
		$string = array();
		foreach ($data as $key => $item) {
			$string[] = urlencode($key) . '=' . urlencode($item); 
		}
		$string = implode('&', $string);
		$string = hash_hmac('sha256', $string, $this->application_secret);
		return $string;
	}
	private function generateSignatureFromString($string) {
		$string = hash_hmac('sha256', $string, $this->application_secret);
		return $string;
	}

	/**
	CURL OPS
	*/
	private function validate() {
		$ch = curl_init();

		$url = 'https://' . $this->mode . 'gocardless.com/api/v1/utils/validate_app_details';

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $this->application_id . ':' . $this->application_secret);

		$r = curl_exec($ch);
		$i = curl_getinfo($ch);
		curl_close($ch);

		if ($i['http_code'] == 200) {
			return TRUE;
		}
		return FALSE;
	}

	private function confirmResource($data = FALSE) {
		if (!$data) {
			return;
		}
		$ch = curl_init();

		$url = 'https://' . $this->mode . 'gocardless.com/api/v1/confirm';

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

		curl_setopt($ch, CURLOPT_USERPWD, $this->application_id . ':' . $this->application_secret);

		$r = curl_exec($ch);
		$i = curl_getinfo($ch);
		curl_close($ch);

		if ($i['http_code'] == 200) {
			$r = json_decode($r);
			if ($r->success == '1') {
				return TRUE;
			}
		}
		return FALSE;
	}

	private function apiCall($command, $method = 'GET', $data = FALSE) {
		$url = 'https://' . $this->mode . 'gocardless.com/api/v1/';
		$url .= $command;

		if ($this->filters) {
			$filters = implode('&', $this->filters);
			if ($filters) {
				$url .= '?' . $filters;
			}
		}
		$this->filters = FALSE;

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if ($data) {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		} else if ($method == 'PUT') {
			curl_setopt($ch, CURLOPT_PUT, TRUE);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . $this->access_token, 'Content-Type: application/json'));

		$r = curl_exec($ch);
		$i = curl_getinfo($ch);
		curl_close($ch);

		if ($i['http_code'] == 200) {
			$r = json_decode($r);
			return $r;
		}
		return FALSE;
	}

	/**
	when a user comes back from the gateway
	we need to send a confirm packet to GC to start the payment
	*/
	function catchReturn() {
		$resource_uri = isset($_GET['resource_uri']) ? $_GET['resource_uri'] : '';
		$resource_id = isset($_GET['resource_id']) ? $_GET['resource_id'] : '';
		$resource_type = isset($_GET['resource_type']) ? $_GET['resource_type'] : '';
		$signature = isset($_GET['signature']) ? $_GET['signature'] : '';

		if (!$resource_uri || !$resource_id || !$resource_uri || !$signature) {
			return FALSE;
		}

		$state = isset($_GET['state']) ? $_GET['state'] : '';

		$data = array(
			'resource_uri' => $resource_uri,
			'resource_id' => $resource_id,
			'resource_type' => $resource_type
		);
		if ($state) {
			$data['state'] = $state;
		}

		$my_signature = $this->generateSignature($data);
		if ($my_signature != $signature) {
			// sig invalid
			return FALSE;
		}

		$data = json_encode(array(
			'resource_id' => $resource_id,
			'resource_type' => $resource_type
		));
		$r = $this->confirmResource($data);

		return $r;
	}

	/**
	Handle a webhook
	*/
	function catchWebHook() {
		if (function_exists('http_get_request_body')) {
			$data = http_get_request_body();
		} else {
			$data = @file_get_contents('php://input');
		}

		$data = json_decode($data);
		$gc_signature_string = $data->payload->signature;
		unset($data->payload->signature);
		$my_signature_string = $this->generateSignatureFromString($this->to_query($data->payload));

		if ($my_signature_string != $gc_signature_string) {
			return FALSE;
		}

		$resource_type = $data->payload->resource_type;
		$load = $resource_type . 's';

		return array(
			'action'	=> $data->payload->action,
			'resource'	=> $resource_type,
			'resources'	=> $data->payload->$load
		);
	}

	/**
	helper function
	came from the Campfire chat
	To remove the [0] indexes/keys from the array
	https://gocardless.campfirenow.com/room/447138/paste/490714382
	*/
	function to_query($obj, $ns = null) {
		if (is_array($obj) || is_object($obj)) {
			$pairs = array();
			foreach ((array)$obj as $k => $v) {
				if (is_int($k)) {
					$pairs[] = $this->to_query($v, $ns . "[]");
				} else {
					$pairs[] = $this->to_query($v, $ns !== null ? $ns . "[$k]" : $k);
				}
			}
			$pairs = array_reduce($pairs, array($this, 'array_merge'), array());
			if ($ns !== NULL) return $pairs;
			sort($pairs);
			$strs = array_map("implode", array_fill(0, count($pairs), "="), $pairs);
			return implode("&", $strs);
		} else {
			return array(array(rawurlencode($ns), rawurlencode($obj)));
		}
	}
	// php pre 5.3 catch
	function array_merge($data, $item) {
		// its not an array becuase its blank!
		// and pre 5.3 passes a integer not the stated intial item
		if (!is_array($data)) {
			$data = array();
		}
		return array_merge($data, $item);
	}

	/**
	API Helper functions
	private function apiCall($command, $method = 'GET', $data = FALSE)
	if data then POST
	*/
	/**
	Merchant Details
	*/
	function merchant() {
		return $this->apiCall('merchants/' . $this->merchant_id);
	}

	/**
	Customers of a Merchant
	*/
	function customers($filters) {
		return $this->merchantUsers($filters);
	}
	function merchantUsers($filters) {
		$this->filters = $filters;
		return $this->apiCall('merchants/' . $this->merchant_id . '/users');
	}

	/**
	A Subscription
	*/
	function subscription($id) {
		return $this->apiCall('subscriptions/' . $id);
	}

	/**
	Subscriptions of a merchant
	*/
	function subscriptions($filters) {
		$this->filters = $filters;
		return $this->apiCass('merchants/' . $this->merchant_id . '/subscriptions');
	}

	/**
	Cancel a subscription
	*/
	function subscriptionCancel($id) {
		return $this->apiClass('subscriptions/' . $id . '/cancel', 'PUT');
	}

	/**
	Show a pre auth
	*/
	function preAuthorization($id) {
		return $this->preAuth($id);
	}
	function preAuth($id) {
		return $this->apiClass('pre_authorizations/' . $id);
	}

	/**
	Show a merchants pre auths
	*/
	function preAuthorizations($filters) {
		return $this->preAuths($filters);
	}
	function preAuths($filters) {
		$this->filters = $filters;
		return $this->apiClass('merchants/' . $this->merchant_id . '/pre_authorizations');
	}

	/**
	cancel a pre autho
	*/
	function preAuthorizationCancel($id) {
		return $this->preAuthCancel($id);
	}
	function preAuthCancel($id) {
		return $this->apiClass('pre_authorizations/' . $id . '/cancel' , 'PUT');
	}

	/**
	Show a bill
	*/
	function bill($id) {
		return $this->apiClass('bills/' . $id);
	}

	/**
	Show a merchants bills
	*/
	function bills($filters) {
		$this->filters = $filters;
		return $this->apiClass('merchants/' . $this->merchant_id . '/bills');
	}

	/**
	Create a bill for a pre auth
	$data requires
	= array('bill' => array(
		'amount' => 'x',
		'pre_authorization_id' => $id
	));
	recommended to include a name and description in the bill
	*/
	function createBill($data) {
		return $this->apiClass('bills', 'POST', $data);
	}
}
