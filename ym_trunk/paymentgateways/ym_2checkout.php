<?php

/*
* $Id: ym_2checkout.php 2520 2013-01-14 16:37:03Z bcarlyon $
* $Revision: 2520 $
* $Date: 2013-01-14 16:37:03 +0000 (Mon, 14 Jan 2013) $
*/

/*
http://www.2checkout.com/blog/knowledge-base/merchants/tech-support/3rd-party-carts/parameter-sets/pass-through-product-parameter-set/
http://www.2checkout.com/blog/knowledge-base/merchants/tech-support/3rd-party-carts/md5-hash-checking/how-do-i-use-the-md5-hash/
http://www.2checkout.com/blog/knowledge-base/merchants/tech-support/recurring-charges/selling-recurring-products-with-2checkout/
http://developers.2checkout.com/

https://www.2checkout.com/support/documentation/
http://www.2checkout.com/documentation/api/
http://developers.2checkout.com/echo/topic/api
https://github.com/craigchristenson/2checkout-ins-demo
https://www.2checkout.com/documentation/api/sales-stop_lineitem_recurring/

*/

class ym_2checkout extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_2checkout';

	var $action_url = 'https://www.2checkout.com/checkout/spurchase/';

	function __construct() {
		$this->version = '$Revision: 2520 $';
		$this->name = __('Memberships with 2Checkout', 'ym');
		$this->description = __('2Checkout.com is a worldwide leader in payment services', 'ym');

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
			$this->logo = YM_IMAGES_DIR_URL . 'pg/2checkout.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';
			$this->lang = 'en';

			$this->merchant_sid = '';
			$this->secret_word = '';
			$this->api_user = '';
			$this->api_pass = '';

			$this->skip_landing = '1';

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
			'sid' => $this->merchant_sid,
			'mode' => '2CO',
			'demo' => ($this->status == 'live' ? 'N' : 'Y'),
			'return_url' => site_url($this->cancel_url),
			'skip_landing' => $this->skip_landing,

			'li_1_type' => 'product',
			'li_1_name' => ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),
			'li_1_quantity' => 1,
			'li_1_price' => $override_price ? $override_price : $pack['cost'],

			'email' => $email,
		);

		$vat = FALSE;
		if ((isset($pack['vat_applicable']) && $pack['vat_applicable']) || $ym_sys->global_vat_applicable) {
			if ($ym_sys->vat_rate) {
				$data = array_merge($data, array(
					'li_2_product_id'	=> 'tax',
					'li_2_description'	=> 'Tax for Product',
					'li_2_type' 		=> 'tax',
					'li_2_name' 		=> 'Tax',
					'li_2_quantity'		=> 1,
					'li_2_price'		=> $ym_sys->vat_rate
				));
				$vat = TRUE;
			}
		}

		if ($vat_rate = apply_filters('ym_vat_override', false, $user_id)) {
			$data = array_merge($data, array(
				'li_2_product_id'	=> 'tax',
				'li_2_description'	=> 'Tax for Product',
				'li_2_type' 		=> 'tax',
				'li_2_name' 		=> 'Tax',
				'li_2_quantity'		=> 1,
				'li_2_price'		=> $vat_rate
			));
			$vat = TRUE;
		}

		// addition per type
		if (isset($pack['num_cycles']) && $pack['num_cycles'] != 1 && $pack['duration_type']) {
			// subscription
			$data['li_1_product_id'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;

			// start up feed
			if (isset($pack['2checkout_startupfee']) && $pack['2checkout_startupfee']) {
				$data['li_1_startup_fee'] = $pack['2checkout_startupfee'];
			}

			// recurring
			// patch
			if ($pack['duration_type'] == 'd') {
				$pack['duration'] = number_format(($pack['duration'] / 7), 0);
				$pack['duration_type'] == 'w';
			}

			$duration_str = array(
				'w' => 'Week',
				'm' => 'Month',
				'y' => 'Year'
			);

			$data = array_merge($data, array(
				'li_1_duration' => ($pack['num_cycles'] ? $pack['num_cycles'] : 'forever'),
				'li_1_recurrence' =>  $pack['duration'] . ' ' . $duration_str[$pack['duration_type']],
			));
			if ($vat) {
				$data['li_2_duration'] = $data['li_1_duration'];
				$data['li_2_recurrence'] = $data['li_1_recurrence'];
			}
		} else {
			// post/single
			if (isset($pack['id'])) {
				$data['li_1_product_id'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
			} else {
				// post
				if (isset($pack['ppp_pack_id'])) {
					$data['li_1_product_id'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;

				} else if (isset($pack['ppp_adhoc_posts'])) {
					$data['li_1_product_id'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
				} else {
					$data['li_1_product_id'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
				}
			}
		}

		$data['x_receipt_link_url'] = esc_html(site_url('?ym_process=' . $this->code));
		$data['custom_return_url'] = $this->redirectlogic($pack);

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

	function ym_profile_unsubscribe_button($return = FALSE, $text = '', $button = '') {
		if (!$this->api_user || !$this->api_pass) {
			return '';
		}

		// last invoice ID
		global $ym_user;
		$last_invoice_id = get_user_meta($ym_user->ID, 'ym_2_checkout_invoice_id', TRUE);

		if (!$last_invoice_id) {
			// no recurrring
			return '';
		}

		if (ym_post('2checkout_cancel')) {
			$data = array('invoice_id' => $last_invoice_id);
			$result = $this->curl_it('detail_sale', $data);
			if ($lineitem_id = $result->sale->invoices[0]->lineitems[0]->lineitem_id) {
				$data = array('lineitem_id' => $lineitem_id);
				$result = $this->curl_it('stop_lineitem_recurring', $data, 'POST');
				// done
				$html = '<div style="margin-bottom: 10px;">
					<h4>' . __('2Checkout Unsubscribe', 'ym') . '</h4>
					<div style="margin-bottom: 10px;">';
				if ($result->response_code == 'OK') {
					$html .= '<p>' . __('You have unsubscribed successfully and your Recurring subscription has been Cancelled', 'ym') . '</p>';

					// fire expire
					// set time to now
					$data = array(
						'expire_date'	=> (time() - 1),
						'status'		=> YM_STATUS_CANCEL
					);
					$ym_user->update($data);
					$ym_user->save();

					@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . __('User Unsubscribe', 'ym'), $ym_user->ID);
				} else {
					$html .= '<p>' . sprintf(__('An error occured unsubscribing you: %s', 'ym'), $result->error[0]->message) . '</p>';
				}
			} else {
				$html .= '<p>' . sprintf(__('An error occured unsubscribing you: %s', 'ym'), $result->errors[0]->message) . '</p>';
			}
			$html .= '</div></div>';
		} else {
			$text = $text ? $text : __('If you wish to unsubscribe you can click the following link.', 'ym');
			$button = $button ? $button : __('Cancel Subscription', 'ym');

			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('2Checkout Unsubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">' . $text . '</div>
				<div>
					<form action="" method="post">
						<input type="submit" name="2checkout_cancel" value="' . $button . '" class="button-secondary" />
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
	// helper
	function curl_it($url, $data, $method = 'GET') {
		if ($method == 'GET') {
			$url .= '?';
			foreach ($data as $item => $value) {
				$url .= $item . '=' . $value . '&';
			}
		}
		$ch = curl_init('https://www.2checkout.com/api/sales/' . $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		if ($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($ch, CURLOPT_USERPWD, $this->api_user . ':' . $this->api_pass);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		$response = curl_exec($ch);
		curl_close($ch);
		$response = json_decode($response);
 		return $response;
	}

	// process
	function do_process() {
		if ($_REQUEST['credit_card_processed']) {
			// yellow button hit
			// echo __('One Moment... Processing', 'ym');

			if (ym_request('sid') != $this->merchant_sid) {
				header('HTTP/1.1 400 Bad Request');
				echo 'Error in IPN. Invalid Vendor ID.';
				exit;
			}

			if ($this->secret_word && !$_REQUEST['demo']) {
				if (!ym_request('key')) {
					header('HTTP/1.1 401 Unauthorized');
					echo 'Error In IPN. Security Check Failed, no sig';
					exit;
				}

				$md5_hash = ym_request('key');

				$secret_word = $this->secret_word;
				$vendor_id = $this->merchant_sid;
				$order_number = ym_request('order_number');
				$total = ym_request('total');

				if (strtolower(ym_request('demo')) == 'y') {
					$order_number = 1;
				}

				$calculated = strtoupper(md5($secret_word . $vendor_id . $order_number . $total));
				if ($calculated != $md5_hash) {
					header('HTTP/1.1 401 Unauthorized');
					echo 'Error In IPN. Security Check Failed, invalid sig';
					exit;
				}
			}

			$complete = FALSE;
			if ($_REQUEST['credit_card_processed'] == 'Y') {
				$complete = TRUE;
			}

			$data = ym_request('li_0_product_id', ym_request('li_1_product_id'));

			if (ym_request('li_0_recurrence', FALSE) && ym_request('invoice_id', FALSE)) {
				// it recurs
				list($buy, $what, $id, $user_id) = explode('_' , $data);
				if ($what == 'subscription') {
					// its a sub
					// store invoice ID to allow cancel
					update_user_meta($user_id, 'ym_2_checkout_invoice_id', ym_request('invoice_id'));
				}
			}
			$r = $this->common_process($data, ym_request('total'), $complete, FALSE);
			if ($r) {
				header('Location: ' . ym_request('custom_return_url'));
			} else {
				echo '<p>' . __('An Error Occured Completing the Transaction', 'ym') . '</p>';
			}
			exit;
		}
		echo 'IPN Processor';

		if (!ym_post('message_type') || !ym_post('item_id_1')) {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Missing message_type or item_id.';
			exit;
		}

		if (ym_post('vendor_id') != $this->merchant_sid) {
			header('HTTP/1.1 400 Bad Request');
			echo 'Error in IPN. Invalid Vendor ID.';
			exit;
		}

		if ($this->secret_word) {
			if (!ym_post('md5_hash')) {
				header('HTTP/1.1 400 Bad Request');
				echo 'Error In IPN. Security Check Failed';
				exit;
			}

			//UPPERCASE(MD5_ENCRYPTED(sale_id + vendor_id + invoice_id + Secret Word))
			$md5_hash = ym_post('md5_hash');
			
			$sale_id = ym_post('sale_id');
			$vendor_id = $this->merchant_sid;
			$invoice_id = ym_post('invoice_id');
			$secret_word = $this->secret_word;

			if (strtolower(ym_post('demo')) == 'y') {
				$order_number = 1;
			}
			
			$calculated = strtoupper(md5($sale_id . $vendor_id . $invoice_id . $secret_word));
			
			if ($calculated != $md5_hash) {
				header('HTTP/1.1 400 Bad Request');
				echo 'Error In IPN. Security Check Failed (b)';
				exit;
			}
		}

		//(ORDER_CREATED, FRAUD_STATUS_CHANGED, SHIP_STATUS_CHANGED, INVOICE_STATUS_CHANGED, REFUND_ISSUED, RECURRING_INSTALLMENT_SUCCESS, RECURRING_INSTALLMENT_FAILED, RECURRING_STOPPED, RECURRING_COMPLETE, or RECURRING_RESTARTED )
		$exit_statuses = array('ORDER_CREATED', 'FRAUD_STATUS_CHANGED', 'SHIP_STATUS_CHANGED', 'RECURRING_STOPPED', 'RECURRING_COMPLETE', 'RECURRING_RESTARTED');
		// handle cases that the system must ignore
		if (ym_post('message_type') && in_array($_POST['message_type'], $exit_statuses)) {
			header('HTTP/1.1 200 OK');
			exit;
		}

		// adjust addition message types
		//approved, pending, deposited, or refunded/declined
		switch ($_POST['message_type']) {
			case 'RECURRING_INSTALLMENT_FAILED':
				$_POST['invoice_status'] = 'declined';
				break;
			case 'RECURRING_INSTALLMENT_SUCCESS':
				$_POST['invoice_status'] = 'deposited';
				break;
			case 'REFUND_ISSUED':
				$_POST['invoice_status'] = 'refunded';
				break;
			case 'INVOICE_STATUS_CHANGED':
			default:
				// no change
		}

		$complete = FALSE;
		switch ($_POST['invoice_status']) {
			case 'deposited':
				$complete = TRUE;
		}

		$data = ym_request('item_id_1');
		if (ym_request('item_recurrence_1', FALSE) && ym_request('invoice_id', FALSE)) {
			// it recurs
			list($buy, $what, $id, $user_id) = explode('_' , $data);
			if ($what == 'subscription' && ym_request('invoice_id')) {
				// its a sub
				// store invoice ID to allow cancel
				update_user_meta($user_id, 'ym_2_checkout_invoice_id', ym_request('invoice_id'));
			}
		}

		$this->common_process(ym_post('item_id_1'), ym_post('item_list_amount_1'), $complete, TRUE);
	}
	function fail_process() {
		$data = array();

		switch ($_POST['invoice_status']) {
			case 'refunded':
			case 'declined':
				$data['new_status'] = YM_STATUS_NULL;
				$data['status_str'] = __('Last payment was refunded or denied','ym');
				$data['expiry'] = time();
				break;
			case 'approved':
			case 'pending':
				$data['new_status'] = YM_STATUS_PENDING;
				$data['status_str'] = __('Last payment is pending.','ym');
				break;
			default:
				$data['new_status'] = YM_STATUS_ERROR;
				$data['status_str'] = sprintf(__('Last payment status: %s','ym'), $_POST['invoice_status']);
		}

		return $data;
	}

	// options
	function load_options() {
		ym_display_message(sprintf(__('IPN Required: Login to <a href="https://www.2checkout.com/va/" target="_blank">https://www.2checkout.com/va/</a> click notifications, use this for the Global URL <strong>%s</strong> select <strong>Apply</strong> and then <strong>Enable All Notifications</strong> and <strong>Save Settings</strong>', 'ym'), site_url('?ym_process=' . $this->code)), 'updated');
		ym_display_message(__('We cannot send over the <strong>Currency</strong>, please make sure you set the Correct Currency on the 2Checkout Admin under <strong>Account</strong>, <strong>Site Management</strong>', 'ym'), 'updated');
		ym_display_message(__('You can use either of the three <strong>Direct Return</strong> Options, the first option shows a Receipt style page, the other two auto return', 'ym'), 'updated');
		ym_display_message(__('You can allow people to cancel their subscription from your site by Providing a API Username and Password, created under <strong>User Management</strong>', 'ym'), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'merchant_sid',
			'label'		=> __('Your Merchant ID', 'ym'),
			'caption'	=> __('2CO Account #', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'secret_word',
			'label'		=> __('Your Secret Word', 'ym'),
			'caption'	=> __('Apply a Secret Word to help verify response from 2Checkout are Valid. Secret Word can be set in the <strong>Site Management</strong> under Account', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'api_user',
			'label'		=> __('Your API User Name', 'ym'),
			'caption'	=> __('If Present Cancel from Site is enabled', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'api_pass',
			'label'		=> __('Your API User Password', 'ym'),
			'caption'	=> __('If Present Cancel from Site is enabled', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'lang',
			'label'		=> __('Language', 'ym'),
			'caption'	=> '',
			'type'		=> 'select',
			'options'	=> array(
				'zh' => __('Chinese', 'ym'),
				'da' => __('Danish', 'ym'),
				'fr' => __('French', 'ym'),
				'gr' => __('German', 'ym'),
				'el' => __('Greek', 'ym'),
				'it' => __('Italian', 'ym'),
				'jp' => __('Japanese', 'ym'),
				'no' => __('Norwegian', 'ym'),
				'pt' => __('Portuguese', 'ym'),
				'sl' => __('Slovenian', 'ym'),
				'es_ib' => __('Spanish (European)', 'ym'),
				'es_la' => __('Spanish (Latin)', 'ym'),
				'sv' => __('Swedish', 'ym'),
				'en' => __('English', 'ym')
			)
		);

		$options[] = array(
			'name'		=> 'skip_landing',
			'label'		=> __('Skip Cart Page', 'ym'),
			'caption'	=> '',
			'type'		=> 'yesno'
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

		return $options;
	}

	// additional pack fields
	function additional_pack_fields() {
		$items = array();
		$items[] = array(
			'name' => '2checkout_startupfee',
			'label' => __('2Checkout StartUp Fee', 'ym'),
			'caption' => __('2Checkout Supports a StartUp Fee. You can set this for this package here', 'ym'),
			'type' => 'text'
		);

		if (ym_post('2checkout_startupfee')) {
			$_POST['2checkout_startupfee'] = preg_replace('/[^\d\.]/', '', $_POST['2checkout_startupfee']);
			$_POST['2checkout_startupfee'] = number_format($_POST['2checkout_startupfee'], 2, '.', '');
		}

		return $items;
	}
}
