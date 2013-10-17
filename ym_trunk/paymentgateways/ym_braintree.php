<?php

/*
* $Id: ym_braintree.php 2599 2013-02-15 10:54:02Z bcarlyon $
* $Revision: 2599 $
* $Date: 2013-02-15 10:54:02 +0000 (Fri, 15 Feb 2013) $
*/

/*
* https://www.braintreepayments.com/docs/php
*/

$ym_braintree_magic_number = 0;

class ym_braintree extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_braintree';

	function __construct() {
		$this->version = '$Revision: 2599 $';
		$this->name = __('Make Payments with Braintree', 'ym');
		$this->description = __('A US based payment provider who provide a PCI-Compliant transparent redirect script', 'ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		$this->action_url = site_url('?ym_process=' . $this->code);
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/braintree.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->merchantid = '';
			$this->publickey = '';
			$this->privatekey = '';
			$this->encryptionkey = '';

			$this->status = 'test';

			$this->callback_script = '';

			$this->save();
		}
	}
	function deactivate() {
	}

	// drivers
	static function js_driver() {
		if (is_admin()) {
			return;
		}
		$braintree = new ym_braintree();
		wp_enqueue_script('ym_js_ym_braintree_js', YM_PLUGIN_DIR_URL . 'paymentgateways/lib/braintree/braintree-1.1.0.min.js', array('jquery'), preg_replace('/[^0-9]/', '', $braintree->version), array('jquery'));
		wp_enqueue_script('ym_js_ym_braintree_driver', site_url('?ym_process=' . $braintree->code . '&action=js'), array('jquery'), preg_replace('/[^0-9]/', '', $braintree->version), array('jquery'));
	}
	private function _braintree() {
		include_once(YM_MODULES_DIR . 'lib/braintree/Braintree.php');
		if ($this->status == 'test') {
			Braintree_Configuration::environment('sandbox');
		} else {
			Braintree_Configuration::environment('production');
		}
		Braintree_Configuration::merchantId($this->merchantid);
		Braintree_Configuration::publicKey($this->publickey);
		Braintree_Configuration::privateKey($this->privatekey);
	}

	/**
	Overrides
	*/
	function getButtonOverride($pack, $user_id, $override_price = false) {
		$button_code = $this->get_button_code($pack, $user_id, $override_price);
		$button_code = apply_filters('ym_additional_code', $button_code, $this->code, $button_code['item_number']);

		$this->_braintree();

		global $current_user;
		get_currentuserinfo();

		if ($current_user->ID) {
			$user_id = $current_user->ID;
		} else {
			$current_user = get_user_by('login', ym_request('username'));
			if ($current_user->ID) {
				$user_id = $current_user->ID;
			} else {
				echo 'bugger';
				return;
			}
		}

		$customer_id = get_user_meta($user_id, 'ym_braintree_customer_id', true);
		if ($customer_id) {
			// validate the customer is still in braintree
			try {
				$customer = Braintree_Customer::find($customer_id);
			} catch (Exception $e) {
				$customer_id = false;
			}
		}

		if (!$customer_id) {
			// create required
			// shunt the customer into braintree
			$result = Braintree_Customer::create(array(
				'id' => 'ym_' . $current_user->ID,
				'email' => $current_user->user_email,
			));
			if ($result->success) {
				$customer_id = $result->customer->id;
				update_user_meta($user_id, 'ym_braintree_customer_id', $customer_id);
			} else {
				echo 'bugger';
				return;
			}
		}

		$r = '';

		$passed = ym_request('code');
		if ($button_code['item_number'] == $passed) {
			$error = ym_request('errormessage');
			if ($error) {
				$r .= '<div class="error"><p>' . $error . '</p></div>';
			}
		}

		// has credit card??
		// get token and update that token?!

		// credit card form driver
		$trData = Braintree_TransparentRedirect::updateCustomerData(array(
			'redirectUrl' => site_url('?ym_process=' . $this->code . '&action=process&code=' . $button_code['item_number']),
			'customerId' => $customer_id,
		));

		$r .= '
<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form" name="' . $this->code . '_form" id="' . $this->code . '_form">
	<fieldset>
		';
		if (isset($pack['id'])) {
			$r .= '<strong>' . ym_get_pack_label($pack['id']) . '</strong><br />';
		}

		if ($override_price) {
			$r .= '<br />' . $override_price . ' ' . ym_get_currency() . ' ' . __('First Period', 'ym');
		}

		$key = 'customer';

		global $ym_braintree_magic_number;
		$ym_braintree_magic_number++;

		$r .= '
		<input type="image" class="' . $this->code . '_button" src="' . $this->logo . '" border="0" name="submit" alt="' . $this->membership_words . '" class="ym_braintree_button" data-unique="' . $ym_braintree_magic_number . '" />
	</fieldset>
</form>
<form action="' . Braintree_TransparentRedirect::url() . '" method="post" id="' . $this->code . '_cc_form_unique_' . $ym_braintree_magic_number . '" class="' . $this->code . '_cc_form" style="display: none;" autocomplete="off">
	<input type="hidden" name="tr_data" value="' . htmlentities($trData) . '" />
	<input type="hidden" name="code" value="' . $button_code['item_number'] . '" />
	<div>
		<label for="braintree_credit_card_number">' . __('Credit Card Number', 'ym') . '</label>
		<input type="text" name="' . $key . '[credit_card][number]" id="braintree_credit_card_number" value=""></input>

		<label for="braintree_credit_card_ccv">' . __('Credit Card CCV', 'ym') . '</label>
		<input type="text" name="' . $key . '[credit_card][cvv]" id="braintree_credit_card_ccv" value=""></input>

		<label for="braintree_credit_card_exp">' . __('Credit Card Expiry (mm/yyyy)', 'ym') . '</label>
		<input type="text" size="7" maxlength="7" name="' . $key . '[credit_card][expiration_date]" id="braintree_credit_card_exp" value=""></input>
	</div>
	<input class="submit-button" type="submit" />
	<span class="ym_braintree_icon">&nbsp;&nbsp;&nbsp;&nbsp;</span>
</form>
    	';

		return $r;
	}

	function gen_buy_now_button_override($post_cost, $post_title, $return, $post_id, $data) {
		get_currentuserinfo();
		global $current_user;
		return $this->getButtonOverride($data, $current_user->ID);
	}
	function gen_buy_ppp_pack_button_override($pack_cost, $pack_title, $return, $pack_id, $data) {
		get_currentuserinfo();
		global $current_user;
		return $this->getButtonOverride($data, $current_user->ID);
	}

	function abort_auto() {
		// if I have been called
		// then on a single gateway showing
		// so skip to credit card form
		$js = '
<script type="text/javascript">
jQuery(document).ready(function() {
	setTimeout(\'ym_braintree_pause\', 1);
});
function ym_braintree_pause() {
	jQuery(\'.ym_braintree_button\').trigger(\'click\');
}
</script>';
		return $js;
	}

	//button gen
	function pack_filter($packs) {
		if (ym_get_currency() != 'USD') {
			return array();
		}
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
		$new_packs = array();
		foreach ($packs as $key => $pack) {
			// allow
			// $pack['num_cycles'] == 1
			if ($pack['num_cycles'] == 1) {
				$new_packs[$key] = $pack;
			} else if (isset($pack['braintree_plan_id']) && $pack['braintree_plan_id']) {
				$new_packs[$key] = $pack;
			}
		}
		return $new_packs;
	}

	function get_button_code($pack, $user_id, $override_price = false) {
		global $ym_sys;

		$data = array();
		$data['item_name'] = ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name);

		if (isset($pack['id']) && $pack['id']) {
			$data['item_number'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			if (isset($pack['ppp_pack_id'])) {
				$data['item_number'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else {
				$data['item_number'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
		}

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

	// reg flow
	function register_payment_action($return = true) {
		global $post_data, $pack_data;
		$override_price = (isset($pack_data['coupon_value']) ? $pack_data['cost'] : false);
		return $this->getButtonOverride($pack_data, ym_get_user_id(), $override_price);
	}
	
	function register_auto_payment_action($pack_id, $override_price = false, $return = true) {
		$this->_braintree();

		global $current_user;
		get_currentuserinfo();

		if ($current_user->ID) {
			$user_id = $current_user->ID;
		} else {
			$current_user = get_user_by('login', ym_request('username'));
			if ($current_user->ID) {
				$user_id = $current_user->ID;
			} else {
				echo 'bugger';
				return;
			}
		}

		$customer_id = get_user_meta($user_id, 'ym_braintree_customer_id', true);
		if ($customer_id) {
			// validate the customer is still in braintree
			try {
				$customer = Braintree_Customer::find($customer_id);
			} catch (Exception $e) {
				$customer_id = false;
			}
		}

		if (!$customer_id) {
			// create required
			// shunt the customer into braintree
			$result = Braintree_Customer::create(array(
				'id' => 'ym_' . $current_user->ID,
				'email' => $current_user->user_email,
			));
			if ($result->success) {
				$customer_id = $result->customer->id;
				update_user_meta($user_id, 'ym_braintree_customer_id', $customer_id);
			} else {
				echo 'bugger';
				return;
			}
		}

		$code = 'buy_subscription_' . $pack_id . '_' . $user_id;

		// credit card form driver
		$trData = Braintree_TransparentRedirect::updateCustomerData(array(
			'redirectUrl' => site_url('?ym_process=' . $this->code . '&action=process&code=' . $code),
			'customerId' => $customer_id,
		));

		$key = 'customer';

		$html = '
<form action="' . Braintree_TransparentRedirect::url() . '" method="post" id="' . $this->code . '_cc_form_pack_' . $pack_id . '" class="' . $this->code . '_cc_form" autocomplete="off">
	<input type="hidden" name="tr_data" value="' . htmlentities($trData) . '" />
	<input type="hidden" name="code" value="' . $code . '" />
	<div>
		<label for="braintree_credit_card_number">' . __('Credit Card Number', 'ym') . '</label>
		<input type="text" name="' . $key . '[credit_card][number]" id="braintree_credit_card_number" value=""></input>

		<label for="braintree_credit_card_ccv">' . __('Credit Card CCV', 'ym') . '</label>
		<input type="text" name="' . $key . '[credit_card][cvv]" id="braintree_credit_card_ccv" value=""></input>

		<label for="braintree_credit_card_exp">' . __('Credit Card Expiry (mm/yyyy)', 'ym') . '</label>
		<input type="text" size="7" maxlength="7" name="' . $key . '[credit_card][expiration_date]" id="braintree_credit_card_exp" value=""></input>
	</div>
	<input class="submit-button" type="submit" />
</form>
';

		if ($return) {
			return $html;
		} else {
			echo $html;
			return;
		}
	}

	// process
	function do_process() {
		$action = ym_request('action');
		if ($action == 'js') {
			header('Content-Type: text/javascript');
?>
jQuery(document).ready(function() {
	jQuery('.ym_braintree_button').click(function(event) {
		event.preventDefault();

		jQuery('.ym_form').slideUp();
		jQuery('#<?php echo $this->code; ?>_cc_form_unique_' + jQuery(this).attr('data-unique')).slideDown();
	});

	var braintree = Braintree.create("<?php echo $this->encryptionkey; ?>");
	jQuery('.<?php echo $this->code; ?>_cc_form').submit(function(e) {
		e.preventDefault();

		jQuery('.ym_braintree_icon').addClass('ym_ajax_loading_image');

		var target = jQuery(this);
		target.find('.error').remove();

		var data = jQuery(this).clone();
		data.find('#braintree_credit_card_number').val(braintree.encrypt(jQuery(this).find('#braintree_credit_card_number').val()));
		data.find('#braintree_credit_card_ccv').val(braintree.encrypt(jQuery(this).find('#braintree_credit_card_ccv').val()));
		data.find('#braintree_credit_card_exp').val(braintree.encrypt(jQuery(this).find('#braintree_credit_card_exp').val()));

		target.find('input').attr('disabled', 'disabled');

		jQuery.post('<?php echo $this->action_url; ?>&action=ajax', data.serialize(), function(resp) {
			jQuery('.ym_braintree_icon').removeClass('ym_ajax_loading_image');

			resp = jQuery.parseJSON(resp);
			if (resp['ok']) {
				target.find('#braintree_credit_card_number').val('');
				target.find('#braintree_credit_card_ccv').val('');
				target.find('#braintree_credit_card_exp').val('');

				jQuery('<div class="success"><p>' + resp['message'] + '</p></div>').prependTo(target);

				document.location = resp['url'];
			} else {
				target.find('input').removeAttr('disabled');
				jQuery('<div class="error"><p>' + resp['message'] + '</p></div>').prependTo(target);
			}
		});
	});
});
<?php
			exit;
		} else if ($action == 'ajax') {
			ob_start();
			$this->_braintree();

			// issue sale or subscribe
			$code = $_POST['code'];
			list($buy, $what, $pack_id, $user_id) = explode('_', $code);

			// credit card update
			$result = Braintree_Customer::update(
				'ym_' . $user_id,
				array(
					'creditCard' => array(
						'number' => $_POST['customer']['credit_card']['number'],
						'cvv' => $_POST['customer']['credit_card']['cvv'],
						'expirationDate' => $_POST['customer']['credit_card']['expiration_date'],
					)
				)
			);

			if ($result->success) {
				// grab token and subscribe

//				if ($pack['num_cycles'] == 1 || $planId) {
				if ($what == 'subscription') {
					// above catches both kinds of package/subscription

					$pack = ym_get_pack_by_id($pack_id);
					$planId = isset($pack['braintree_plan_id']) ? $pack['braintree_plan_id'] : false;

					// initiate charge against just added credit card
					if ($planId) {
						$result = Braintree_Subscription::create(array(
							'planId' => $planId,
							'paymentMethodToken' => $result->customer->creditCards[0]->token,
						));
						$amount = $result->subscription->transactions[0]->amount;
					} else {
						$result = Braintree_Transaction::sale(array(
							'amount' => $pack['cost'],
							'options' => array(
								'submitForSettlement' => true,
							),
							'customerId' => $result->customer->id,
							'paymentMethodToken' => $result->customer->creditCards[0]->token,
						));
						$amount = $result->transaction->amount;
					}

					if ($result->success) {
						// common
						$this->common_process($code, $amount, true, false);
						// thanks
						$url = $this->redirectlogic($pack);
						$r = array(
							'ok' => true,
							'url' => $url,
							'message' => __('Payment Complete', 'ym'),
						);
					} else {
						$r = $this->_failedBraintree($result, true);
					}
				} else if ($what == 'bundle' || $what == 'post') {
					// post or bundle purchase
					if ($what == 'post') {
						$cost = get_post_meta($pack_id, '_ym_post_purchasable_cost', true);
					} else {
						$bundle = ym_get_bundle($pack_id);
						if (!$bundle) {
							$r = array(
								'ok' => false,
								'message' => __('Bundle Error', 'ym'),
							);
						} else {
							$cost = $bundle->cost;
						}
					}

					if ($cost) {
						$result = Braintree_Transaction::sale(array(
							'amount' => $cost,
							'options' => array(
								'submitForSettlement' => true,
							),
							'customerId' => $result->customer->id,
							'paymentMethodToken' => $result->customer->creditCards[0]->token,
						));
						$amount = $result->transaction->amount;

						if ($result->success) {
							// common
							$this->common_process($code, $amount, true, false);
							// thanks
							if ($what == 'subscription') {
								$url = $this->redirectlogic($pack);
							} else if ($what == 'post') {
								$url = $this->redirectlogic(array(
									'ppp' => true,
									'post_id' => $pack_id
								));
							} else {
								$url = $this->redirectlogic(array(
									'ppp' => true,
									'ppp_pack_id' => $pack_id
								));
							}
							$r = array(
								'ok' => true,
								'url' => $url,
								'message' => __('Payment Complete', 'ym'),
							);
						} else {
							$r = $this->_failedBraintree($result, true);
						}
					}
				} else {
					// unhandled purchase
					$r = $this->_failedBraintree($result, true);
				}
			} else {
				$r = $this->_failedBraintree($result, true);
			}
			ob_clean();
			echo json_encode($r);
			// bugger
			exit;

		// non ajax/primary js failed
		// transparent redirect handlers
		} else if ($action == 'process') {
			$this->_braintree();

			$queryString = $_SERVER['QUERY_STRING'];
			try {
				$result = Braintree_TransparentRedirect::confirm($queryString);
			} catch (Exception $e) {
				if (get_class($e) == 'Braintree_Exception_NotFound') {
					echo 'not found';
				} else {
					echo '<pre>';
					print_r($e);
					echo $e->getMessage();
				}
				exit;
			}

			if ($result->success) {
				$code = ym_request('code');

				// grab token and subscribe
				list($buy, $what, $pack_id, $user_id) = explode('_', $code);
				$pack = ym_get_pack_by_id($pack_id);
				$planId = isset($pack['braintree_plan_id']) ? $pack['braintree_plan_id'] : false;

				if ($pack['num_cycles'] == 1 || $planId) {
					// initiate charge against just added credit card
					if ($planId) {
						$result = Braintree_Subscription::create(array(
							'planId' => $planId,
							'paymentMethodToken' => $result->customer->creditCards[0]->token,
						));
						$amount = $result->subscription->transactions[0]->amount;
					} else {
						$result = Braintree_Transaction::sale(array(
							'amount' => $pack['cost'],
							'options' => array(
								'submitForSettlement' => true,
							),
							'customerId' => $result->customer->id,
							'paymentMethodToken' => $result->customer->creditCards[0]->token,
						));
						$amount = $result->transaction->amount;
					}

					if ($result->success) {
						// common
						$this->common_process($code, $amount, true, false);
						// thanks
						$this->redirectlogic($pack, true);
						exit;
					} else {
						$this->_failedBraintree($result);
					}
				} else {
					$this->_failedBraintree($result);
				}
				exit;
			}

			$this->_failedBraintree($result);
		} else {
			$this->_failedBraintree();
		}
	}

	private function _failedBraintree($result = false, $ajax = false) {
		// see if its something we can catch
		if ($result) {
			if (get_class($result) == 'Braintree_Result_Successful') {
				if ($ajax) {
					return array(
						'code' => $error->code,
						'message' => 'Successful Error',
						'ok' => false,
					);
				}
			}
			foreach ($result->errors->deepAll() as $error) {
				if ($ajax) {
					return array(
						'code' => $error->code,
						'message' => $error->message,
						'ok' => false,
					);
				}
				$url = $_SERVER['HTTP_REFERER'];
				if (strpos($url, '?')) {
					$url .= '&';
				} else {
					$url .= '?';
				}
				$url .= 'code=' . ym_request('code');
				$url .= '&errorcode=' . $error->code . '&errormessage=' . $error->message;
				header('Location: ' . $url);
				exit;
				echo $error->code . ' ' . $error->message;
			}
		}

		// default crash
		echo '<p>
		An Error Has Occured
		<br />
		And the Payment Flow has exited abnormally
		</p><p>Debug Information</p>';
		echo '<pre>';
		print_r($_REQUEST);
		print_r($_SERVER);
		if ($result) {
			print_r($result->errors->deepAll());
		}
		echo '</pre>';
		exit;
	}

	function load_options() {
		echo '<div id="message" class="updated"><p>' . __('Braintree Plans are created in the Control Panel. For recurring packages, you will need to create them on, and add the Braintree Plan ID to the Package', 'ym') . '</p></div>';

		$options = array();

		$options[] = array(
			'name'		=> 'merchantid',
			'label'		=> __('Your Merchant Id', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'publickey',
			'label'		=> __('Your Public Key', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'privatekey',
			'label'		=> __('Your Private Key', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'encryptionkey',
			'label'		=> __('Your Encryption Key', 'ym'),
			'caption'	=> __('Client Side Encryption Key', 'ym'),
			'type'		=> 'textarea'
		);

		$options[] = array(
			'name'		=> 'status',
			'label'		=> __('Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'status'
		);

		return $options;
	}

	// additional pack fields
	function additional_pack_fields() {
		$items = array();
		$items[] = array(
			'name' => 'braintree_plan_id',
			'label' => __('Braintree Plan ID', 'ym'),
			'caption' => '',
			'type' => 'text'
		);

		return $items;
	}
}

add_action('init', 'ym_braintree::js_driver');
add_action('login_head', 'ym_braintree::js_driver');
