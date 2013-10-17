<?php

/*
* $Id: ym_stripe.php 2604 2013-02-20 13:36:45Z bcarlyon $
* $Revision: 2604 $
* $Date: 2013-02-20 13:36:45 +0000 (Wed, 20 Feb 2013) $
*/

/*
https://github.com/stripe/stripe-php
https://stripe.com/docs/api?lang=curl
*/

class ym_stripe extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_stripe';

	function __construct() {
		$this->version = '$Revision: 2604 $';
		$this->name = __('Make payments with Stripe', 'ym');
		$this->description = __('Stripe makes it easy to start accepting credit cards on the web today', 'ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		$this->action_url = site_url('?ym_process=' . $this->code);
		add_filter('ym_additional_code', array($this, 'inject_coupon'), 10, 3);
	}

	function activate() {
		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/stripe.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';

			$this->secret_key = '';
			$this->api_key = '';
			$this->prorate = 'true';

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
		$stripe = new ym_stripe();
		if (!$stripe->api_key) {
			return;
		}
		wp_enqueue_script('ym_js_ym_stripe', 'https://js.stripe.com/v1/', array('jquery'), '1.0');
		wp_enqueue_script('ym_js_ym_stripe_driver', site_url('?ym_process=' . $stripe->code . '&action=js'), array('jquery'), preg_replace('/[^0-9]/', '', $stripe->version), array('jquery'));
	}
	function inject_coupon($button_code, $code, $pack_id) {
		if ($code != $this->code) {
			return $button_code;
		} else {
			$ym_custom_fields = ym_get_custom_field_array(ym_get_user_id());
			$coupon = isset($ym_custom_fields['coupon']) ? $ym_custom_fields['coupon'] : '';

			$button_code['coupon'] = '';

			if ($coupon) {
				if ($id = ym_get_coupon_id_by_name($coupon)) {
					$button_code['coupon'] = 'ym_' . $id;
				}
			}
			return $button_code;
		}
	}

	/**
	Overrides
	*/
	function getButtonOverride($pack, $user_id, $override_price = FALSE) {
		if (!$this->api_key) {
			return '<p>' . __('Stripe Requires configuration', 'ym') . '</p>';
		}
		$button_code = $this->get_button_code($pack, $user_id, $override_price);
		$button_code = apply_filters('ym_additional_code', $button_code, $this->code, $button_code['item_number']);

		$button_code_html = '';
		foreach ($button_code as $item => $val) {
			$button_code_html .= '<input type="hidden" name="' . $item . '" value="' . $val . '" />' . "\n";
		}

		$r = '
<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form" name="' . $this->code . '_form" id="' . $this->code . '_form">
	<fieldset>
		<strong>
		';
		if (isset($pack['id'])) {
			$r .= ym_get_pack_label($pack);
		}

		if ($override_price) {
			$r .= '<br />' . $override_price . ' ' . ym_get_currency() . ' ' . __('First Period', 'ym');
		}

		$r .= '</strong><br />
		' . $button_code_html . '
		<input type="image" class="' . $this->code . '_button" src="' . $this->logo . '" border="0" name="submit" alt="' . $this->membership_words . '" class="ym_stripe_button" />
	</fieldset>
</form>
<div id="' . $this->code . '_error_handler" style="display: none;"></div>
<form action="" method="post" id="' . $this->code . '_cc_form" style="display: none;" autocomplete="off">
	<fieldset>
		<label for="credit_card_number">' . __('Credit Card Number', 'ym') . '<br /><input type="text" name="credit_card_number" id="credit_card_number" class="ym_wipeme" /></label><br />
		<label for="credit_card_cvc">' . __('Credit Card CCV', 'ym') . '<br /><input type="text" name="credit_card_cvc" id="credit_card_cvc" class="ym_wipeme" /></label><br />
		<label for="expiration">' . __('Expiration (MM/YYYY)', 'ym') . '<br /><input type="text" size="2" maxlength="2" name="expire_number_month" id="expire_number_month" class="ym_wipeme" style="width: 40px;"/> / <input type="text" size="4" maxlength="4" name="expire_number_year" id="expire_number_year" class="ym_wipeme" style="width: 80px;" /></label><br />
		<br /><input type="submit" id="' . $this->code . '_submit_button" value="' . __('Pay', 'ym') . '" />
	</fieldset>
</form>
<form action="' . site_url('?ym_process=' . $this->code . '&action=start') . '" method="post" id="' . $this->code . '_submit_form" style="display: none;">
	<fieldset>
		<input type="hidden" name="purchase_code" value="" />
		<input type="hidden" name="email" value="" />
		<input type="hidden" name="cost" value="" />
		<input type="hidden" name="return_to" value="" />
		<input type="hidden" name="coupon" value="" />
		' . __('Loading...', 'ym') . '
	</fieldset>
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
	setTimeout(\'ym_stripe_pause()\', 1);
});
function ym_stripe_pause() {
	jQuery(\'.ym_stripe_button\').trigger(\'click\');
}
</script>';
		return $js;
	}

	// button gen
	function pack_filter($packs) {
		if (ym_get_currency() != 'USD') {
			return array();
		}
		if (!$this->api_key) {
			// drop to fail allow render single type to allow error message
			return array(array_pop($packs));
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
			} else if ($pack['duration'] != 1) {
				unset($packs[$key]);
			} else if ($pack['duration_type'] == 'd') {
				unset($packs[$key]);
			}
		}

		return $packs;
	}

	function get_button_code($pack, $user_id, $override_price = FALSE) {
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

		get_currentuserinfo();
		global $current_user;
		$email = isset($current_user->user_email) ? $current_user->user_email : '';
		if (!$email) {
			if ($user = ym_get('username')) {
				$user = get_user_by('login', $user);
				$email = $user->user_email;
			}
		}

		$data['email'] = $email;
		$data['cost'] = $override_price ? $override_price : $pack['cost'];
		$data['return_to'] = $this->redirectlogic($pack);

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
	// enable trial
	function enable_trial() {
	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE, $text = '', $button = '') {
		if (!$this->api_key || !$this->secret_key) {
			return;
		}

		global $ym_user;
		$id = get_user_meta($ym_user->ID, 'ym_stripe_customer_id', TRUE);
		if (!$id) {
			return;
		}

		$text = $text ? $text : __('If you wish to unsubscribe you can click the following link.', 'ym');
		$button = $button ? $button : __('Cancel Subscription', 'ym');

		if (ym_post('stripe_cancel')) {
			list($r_code, $response) = $this->stripe_api_request('customers/' . $id . '/subscription', 'DELETE');
			if ($r_code == 200) {
				$html .= '<p>' . __('You have unsubscribed successfully and your Subscription Terminated', 'ym') . '</p>';

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
			} else {
				$response = json_decode($response);
				$html .= '<p>' . sprintf(__('An error Occurred unsubscribing you: %s', 'ym'), $response->error->message) . '</p>';
			}
		} else {
			$html = '<div style="margin-bottom: 10px;">
				<h4>' . __('Stripe Unsubscribe', 'ym') . '</h4>
				<div style="margin-bottom: 10px;">' . $text . '</div>
				<div>
					<form action="" method="post">
						<input type="submit" name="stripe_cancel" value="' . $button . '" class="button-secondary" />
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

	// reg flow
	function register_payment_action($return = TRUE) {
		global $post_data, $pack_data;
		$override_price = (isset($pack_data['coupon_value']) ? $pack_data['cost'] : false);
		return $this->getButtonOverride($pack_data, ym_get_user_id(), $override_price);
	}
	
	function register_auto_payment_action($pack_id, $override_price = false, $return = TRUE) {
		$html = $this->abort_auto();

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
	jQuery('.ym_stripe_button').click(function(event) {
		event.preventDefault();

		jQuery('#<?php echo $this->code; ?>_submit_form').find('input[name="purchase_code"]').val(jQuery(this).parents('form').find('input[name="item_number"]').val());
		jQuery('#<?php echo $this->code; ?>_submit_form').find('input[name="email"]').val(jQuery(this).parents('form').find('input[name="email"]').val());
		jQuery('#<?php echo $this->code; ?>_submit_form').find('input[name="cost"]').val(jQuery(this).parents('form').find('input[name="cost"]').val());
		jQuery('#<?php echo $this->code; ?>_submit_form').find('input[name="return_to"]').val(jQuery(this).parents('form').find('input[name="return_to"]').val());
		jQuery('#<?php echo $this->code; ?>_submit_form').find('input[name="coupon"]').val(jQuery(this).parents('form').find('input[name="coupon"]').val());

		jQuery('.ym_form').slideUp();
		jQuery('#ym_stripe_cc_form').slideDown();
	});

	Stripe.setPublishableKey('<?php echo $this->api_key; ?>');
	jQuery('#<?php echo $this->code; ?>_cc_form').live('submit', function(event) {
		event.preventDefault();
		jQuery('#<?php echo $this->code ?>_submit_button').attr('disabled', 'disabled');
		jQuery('#<?php echo $this->code; ?>_error_handler').slideUp();
		Stripe.createToken({
			number: jQuery('#credit_card_number').val(),
			cvc: jQuery('#credit_card_cvc').val(),
			exp_month: jQuery('#expire_number_month').val(),
			exp_year: jQuery('#expire_number_year').val()
		}, stripeResponseHandler);
	});
});
function stripeResponseHandler(status, response) {
	if (response.error) {
		jQuery('#<?php echo $this->code ?>_submit_button').removeAttr('disabled');
		jQuery('#<?php echo $this->code; ?>_error_handler').html(response.error.message + '<br />').slideDown();
	} else {
		var token = response['id'];
		jQuery('.ym_wipeme').each(function() {
			jQuery(this).val('');
		});
		jQuery('#<?php echo $this->code; ?>_cc_form').slideUp();
		jQuery('#<?php echo $this->code; ?>_status').slideDown();
		jQuery('#<?php echo $this->code ?>_submit_button').attr('disabled', 'disabled');
		jQuery('<input type="hidden" name="stripeToken" value="' + token + '" />').appendTo('#<?php echo $this->code; ?>_submit_form');
		jQuery('#<?php echo $this->code; ?>_submit_form').submit();
	}
}

jQuery(document).ready(function() {
	var stripebuynowtarget = '.<?php echo $this->code; ?>_post_purchase_button';
	var stripedoforms = false;
	if (!jQuery(stripebuynowtarget).size()) {
		stripebuynowtarget = '.<?php echo $this->code; ?>_bundle_purchase_button';
		stripedoforms = true;
	}
	jQuery(stripebuynowtarget).click(function(event) {
		event.preventDefault();

		if (stripedoforms) {
			jQuery('.ym_form').slideUp();
		}

		jQuery('<?php echo $this->code; ?>_cc_form').slideUp(function() {
			jQuery(this).remove();
		});

		var purchase_code = jQuery(this).parents('form').find('input[name="item_number"]').val();
		var email = jQuery(this).parents('form').find('input[name="email"]').val();
		var cost = jQuery(this).parents('form').find('input[name="cost"]').val();
		var return_to = jQuery(this).parents('form').find('input[name="return_to"]').val();

		jQuery(this).parents('.<?php echo $this->code; ?>_ppp_holder').slideUp(function() {
			jQuery(this).html(
				'<form action="" method="post" id="<?php echo $this->code; ?>_cc_form" class="like_form" autocomplete="off">'
				+ '<fieldset>'
				+ '<div id="<?php echo $this->code; ?>_error_handler" style="display: none;"></div>'
				+ '<label for="credit_card_number"><?php _e('Credit Card Number', 'ym'); ?><br /><input type="text" name="credit_card_number" id="credit_card_number" class="ym_wipeme" /></label><br />'
				+ '<label for="credit_card_cvc"><?php _e('Credit Card CCV', 'ym'); ?><br /><input type="text" name="credit_card_cvc" id="credit_card_cvc" class="ym_wipeme" /></label><br />'
				+ '<label for="expiration">Expiration (MM/YYYY)<br /><input type="text" size="2" maxlength="2" name="expire_number_month" id="expire_number_month" class="ym_wipeme" /> / <input type="text" size="4" maxlength="4" name="expire_number_year" id="expire_number_year" class="ym_wipeme" /></label><br />'
				+ '<br /><input type="submit" id="<?php echo $this->code ?>_submit_button" value="<?php _e('Pay', 'ym'); ?>" />'
				+ '</fieldset>'
				+ '</form>'
				+ '<form action="<?php echo site_url('?ym_process=' . $this->code . '&action=start'); ?>" method="post" id="<?php echo $this->code; ?>_submit_form" style="display: none;">'
				+ '<input type="hidden" name="purchase_code" value="' + purchase_code + '" />'
				+ '<input type="hidden" name="email" value="' + email + '" />'
				+ '<input type="hidden" name="cost" value="' + cost + '" />'
				+ '<input type="hidden" name="return_to" value="' + return_to + '" />'
				+ '<div id="<?php echo $this->code; ?>_status" style="display: none;"><?php _e('Loading', 'ym'); ?></div>'
				).slideDown();
		});
	});
});
<?php
			exit;
		} else if ($action == 'start') {

			$charge = FALSE;

			$code = ym_post('purchase_code', FALSE);
			$token = ym_post('stripeToken', FALSE);
			$email = ym_post('email');

			if (!$token || !$code) {
				echo '<p>An Error Occurred (a)</p>';
				exit;
			} else {
				list($buy, $what, $id, $user_id) = explode('_', $code);
				// plan ID Not to contain user ID
				$plan_id = $buy . '_' . $what . '_' . $id;
				if ($what == 'subscription') {
					$pack = ym_get_pack_by_id($id);

					if ($pack['num_cycles'] != 1) {
						// test plan exist

						$r = $this->sync_packages($plan_id);
						if (!$r) {
							echo '<p>An Error Occurred in Sync</p>';
							exit;
						}

						// got this far Go for Subscribe
						$subscribe = array(
							'card' => $token,
							'plan' => $plan_id,
							'email' => $email
						);

						// trial check
						if ($pack['trial_on'] == 1) {
							// trial
							$cost_check = $pack['trial_cost'] * 100;
							if ($cost_check) {
								// paid for trial
								// issue charge
								$charge = array(
									'amount' => $cost_check,
									'currency' => 'usd',
									'description' => 'Trial: ' . $plan_id
								);
							}
						}

						// coupon?
						if ($pack['cost'] != ym_post('cost')) {
							// coupon in use

							// nasty
							$coupon = ym_post('coupon', FALSE);
							if ($coupon) {
								$test = $this->sync_coupons(substr($coupon, 3));
								if ($test) {
									$subscribe['coupon'] = $coupon;
								} else {
									echo '<p>Coupon Sync Failed</p>';
									exit;
								}
							} else {
								// or hacking attempt
								echo '<p>Unable to Match Coupon</p>';
								exit;
							}
						}

						// check for customer exist
						$create = TRUE;
						$customer_id = get_user_meta($user_id, 'ym_stripe_customer_id', TRUE);
						if ($customer_id) {
							list($r_code, $response) = $this->stripe_api_request('customers/' . $customer_id);
							if ($r_code == 200) {
								// check response
								if (isset($response->deleted) && $response->deleted == 1) {
									// deleted
								} else {
									// exists
									$create = FALSE;
								}
							}
						}
						if ($create) {
							list($r_code, $response) = $this->stripe_api_request('customers', 'POST', $subscribe);
							// store ID
							if ($r_code == 200) {
								update_user_meta($user_id, 'ym_stripe_customer_id', $response->id);
							}
						} else {
							// update
							$subscribe['prorate'] = $this->prorate;

							unset($subscribe['email']);//email not accepted for sub change
							list($r_code, $response) = $this->stripe_api_request('customers/' . $customer_id . '/subscription', 'POST', $subscribe);
						}

						if ($r_code == 200) {
							// leave to IPN for Prorate and/or start
							if (ym_post('return_to', FALSE)) {
								header('Location: ' . ym_post('return_to'));
								exit;
							}
							$this->redirectlogic($pack, TRUE);
							exit;
						} else {
							echo '<p>An Error Occurred (d: ' . $r_code . ': ' . $response->error->message . ')</p>';
						}

						exit;
					} else {
						// single occurrence subscription
						$charge = array(
//							'card' => $token,
							'amount' => ym_post('cost', 0) * 100,
							'currency' => 'usd',
							'description' => $plan_id
						);
					}
				} else {//if ($what == 'post' || $what == 'bundle') {
					// post
					// TODO: temporary hack
					$charge = array(
//						'card' => $token,
						'amount' => ym_post('cost', 0) * 100,
						'currency' => 'usd',
						'description' => $plan_id
					);
//				} else {
					// unknown purchase!!!!
				}

				// single charge
				if ($charge) {
					// customer exist?
					$create = TRUE;
					$customer_id = get_user_meta($user_id, 'ym_stripe_customer_id', TRUE);
					if ($customer_id) {
						list($r_code, $response) = $this->stripe_api_request('customers/' . $customer_id);
						if ($r_code == 200) {
							// check response
							if (isset($response->deleted) && $response->deleted == 1) {
								// deleted
							} else {
								// exists
								$create = FALSE;
							}
						}
					}
					if ($create) {
						$customer = array(
							'card' => $token,
							'email' => $email
						);
						list($r_code, $response) = $this->stripe_api_request('customers', 'POST', $customer);
						// store ID
						if ($r_code == 200) {
							update_user_meta($user_id, 'ym_stripe_customer_id', $response->id);
							$customer_id = $response->id;
						}
					}

					if ($customer_id) {
						// commence charge
						$charge['customer'] = $customer_id;
						list($r_code, $response) = $this->stripe_api_request('charges', 'POST', $charge);
						if ($r_code == 200) {
							if ($response->paid == 1) {
								$this->common_process($code, $charge['amount'], TRUE, FALSE);
								if ($what == 'post') {
									$pack = array('ppp' => 1, 'post_id' => $id);
								} else if ($what == 'bundle') {
									$pack = array('ppp' => 1, 'ppp_pack_id' => $id);
								} else {
									$pack = $id;
								}
								if (ym_post('return_to', FALSE)) {
									header('Location: ' . ym_post('return_to'));
									exit;
								}
								$this->redirectlogic($pack, TRUE);
							} else {
								echo 'Failed';
							}
						} else {
							echo '<p>An Error Occurred (f: ' . $r_code . ': ' . $response->error->message . ')</p>';
						}
					} else {
						echo '<p>An Error Occurred (e: ' . $r_code . ': ' . $response->error->message . ')</p>';
					}
				}
			}
			exit;
		} else if ($action == 'process') {
			// process a web hook
			if (function_exists('http_get_request_body')) {
				$payload = http_get_request_body();
			} else {
				$payload = @file_get_contents('php://input');
			}
			$_REQUEST = json_decode($payload, TRUE);// stash for YM_IPN Array
			$payload = json_decode($payload);

			if (!$payload) {
				header('HTTP/1.1 400 Bad Request');
				echo 'Error in IPN. No Data Recieved';
			} else {
				$this->packet = $payload;

				list($type, $result) = explode('.', $payload->type, 2);

				$escape_types = array(
					'ping',
					'plan',
				);
				$escape_results = array(
					'created',
					'customer.updated'
				);

				if (in_array($type, $escape_types) || in_array($result, $escape_results)) {
					echo 'ohai';
					exit;
				}

				$complete = FALSE;

				$customer_id = isset($payload->data->object->customer) ? $payload->data->object->customer : '';
				$email = isset($payload->data->object->email) ? $payload->data->object->email : '';
				$code = isset($payload->data->object->lines->subscriptions[0]->plan->id) ? $payload->data->object->lines->subscriptions[0]->plan->id : '';
				$cost = isset($payload->data->object->lines->subscriptions[0]->amount) ? $payload->data->object->lines->subscriptions[0]->amount : '';

				global $wpdb;
				$user_id = $wpdb->get_var('SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'ym_stripe_customer_id\' AND meta_value = \'' . $customer_id . '\'');
				if (!$user_id && $email) {
					$user = get_user_by('email', $email);
					$user_id = $user->ID;
				}
				if (!$user_id) {
					// fail user match
					echo 'OK';
					exit;
				}


				if ($type == 'invoice') {
					$cost = $payload->data->object->lines->subscriptions[0]->amount / 100;//fron cents to dollars
					$invoice_id = $payload->data->object->id;

					if ($result == 'payment_succeeded') {
						$complete = TRUE;
					}

					if ($complete) {
						$code = $payload->data->object->lines->subscriptions[0]->plan->id;
						list($buy, $what, $id) = explode('_', $code);
						$last_invoice_id = get_user_meta($user_id, 'ym_last_stripe_id', TRUE);
						if ($last_invoice_id == $invoice_id) {
							// double complete packet.....
							header('HTTP/1.1 200 OK');
							echo 'Double Packet';
							exit;
						}
						update_user_meta($user_id, 'ym_last_stripe_id', $invoice_id);
						update_user_meta($user_id, 'ym_stripe_customer_id', $customer_id);
					}
					// append User ID to the code
					$code .= '_' . $user_id;
				} else if ($type == 'customer' && $result == 'deleted') {
					// customer deleted
					$cost = 0;
					$complete = FALSE;

					$code = $code ? $code . '_' . $user_id : 'buy_subscription_cancel_' . $user_id;
					delete_user_meta($user_id, 'ym_stripe_customer_id');
				}

				// ignore anything else

				if ($code && strlen($cost)) {
					$this->common_process($code, $cost, $complete);
				} else {
					// skippy the bush kagaroo
					header('HTTP/1.1 200 OK');
					echo 'ok';
					exit;
				}
			}
		} else {
			echo '<p>
			An Error Has Occured
			<br />
			And the Payment Flow has exited abnormally
			</p><p>Debug Information</p>';
			echo '<pre>' . print_r($_REQUEST) . '</pre>';
			exit;
		}
	}
	function fail_process() {
		$packet = $this->packet;

		$data = array();

		list($charge, $status) = explode('.', $packet->type, 2);

		if ($packet->type == 'invoice.created') {
			// pending
			// look for paid flag
			if ($packet->data->object->paid == 1) {
				// paid but leave till succeeded comes in
				// don't go pending
				$data['status_str'] = __('Last payment is coming.', 'ym');
			} else {
				$data['new_status'] = YM_STATUS_PENDING;
				$data['status_str'] = __('Last payment is pending.', 'ym');
			}
		} else if ($charge == 'invoice' && $status != 'payment_succeeded') {
			$data['new_status'] = YM_STATUS_ERROR;
			$data['status_str'] = sprintf(__('Last payment is error: %s', 'ym'), $status);

		} else if ($charge == 'customer' && $status == 'deleted') {
			$data['new_status'] = YM_STATUS_EXPIRED;
			$data['status_str'] = __('User Deleted their account', 'ym');
			$data['expire_date'] = time() - 1;
		}

		return $data;
	}

	// options
	function load_options() {
		if (ym_get('stripe_sync', FALSE)) {
			// SYNC
			if ($this->sync_packages()) {
				echo '<div id="message" class="updated"><p>' . __('Package Sync Complete', 'ym') . '</p></div>';
			} else {
				echo '<div id="message" class="updated"><p>' . __('Package Sync Complete, but an error Occurred', 'ym') . '</p></div>';
			}
		}
		echo '<div id="message" class="updated"><p>' . sprintf(__('Stripe requires packages to be created on their site. These are done on the fly at purchase, but you can <a href="%s">Manually Sync now</a>', 'ym'), YM_ADMIN_URL . 'index.php&ym_page=ym-payment-gateways&action=modules&mode=options&sel=ym_stripe.php&stripe_sync=1') . '</p></div>';
		echo '<div id="message" class="updated"><p>' . __('Stripe currently only Accepts Payments in USD', 'ym') . '</p></div>';
		echo '<div id="message" class="updated"><p>' . sprintf(__('You will need to add a <strong>webhook</strong> in the Stripe interface.<br />Select Your Account -> Account Settings -> Webhooks -> Add URL.<br />Then in the URL use: <strong>%s</strong>, and Select the Required mode', 'ym'), site_url('?ym_process=ym_stripe&action=process')) . '</p></div>';

		$options = array();

		$options[] = array(
			'name'		=> 'secret_key',
			'label'		=> __('Your Secret Key', 'ym'),
			'caption'	=> __('Use your Test Key for Test Mode', 'ym'),
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'api_key',
			'label'		=> __('Your Publishable Key', 'ym'),
			'caption'	=> __('Use your Test Key for Test Mode', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'prorate',
			'label'		=> __('Prorate', 'ym'),
			'caption'	=> __('On User Subscription change, "No" Charges Now, "Yes" Charges at next Recur adding/subtracting as needed', 'ym'),
			'type'		=> 'select',
			'options'	=> array(
				'true'	=> __('Yes', 'ym'),
				'false'	=> __('No', 'ym')
			)
		);

		return $options;
	}

	// helpers
	private function stripe_api_request($uri, $method = 'GET', $data = array()) {
		// TODO: convert to WP HTTP??
		$url = 'https://api.stripe.com/v1/' . $uri;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 80);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		// lift shiny things from their class
		$ua = array('bindings_version' => preg_replace('/[^0-9]/', '', $this->version),
			'lang' => 'php',
			'lang_version' => phpversion(),
			'publisher' => 'YourMembers',
			'uname' => php_uname());
		$headers = array('X-Stripe-Client-User-Agent: ' . json_encode($ua),
			'User-Agent: Stripe/YM PhpBindings/' . preg_replace('/[^0-9]/', '', $this->version));

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERPWD, $this->secret_key . ':');
		// !!
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		switch ($method) {
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				if ($data) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, null, '&'));
				}
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, null, '&'));
			case 'GET':
			default:
				// nothing
		}

		$r = curl_exec($ch);
		$i = curl_getinfo($ch);
		return array($i['http_code'], json_decode($r));
	}

	private function sync_packages($plan_id = FALSE) {
		if ($plan_id) {
			list($buy, $sub, $pack_id) = explode('_', $plan_id);
			$packages = array(ym_get_pack_by_id($pack_id));
		} else {
			global $ym_packs;
			$packages = $this->pack_filter($ym_packs->packs);
		}

		$r = TRUE;

		foreach ($packages as $pack) {
			$plan_id = 'buy_subscription_' . $pack['id'];

			if (strtolower($pack['duration_type']) == 'm') {
				$dt = 'month';
			} else {
				$dt = 'year';
			}

			list($r_code, $response) = $this->stripe_api_request('plans/' . $plan_id);

			$plan = array(
				'id'			=> $plan_id,
				'amount'		=> $pack['cost'] * 100,//cents
				'currency'		=> 'usd',
				'interval'		=> $dt,//month/year
				'name'			=> ym_get_pack_label($pack['id'], false, false)
			);
			if ($pack['trial_on']) {
				// add free trial
				// not free causes additional charge on plan sub
				$days = 0;
				if ($pack['trial_duration_type'] == 'd') {
					$days = $pack['trial_duration'];
				} else if ($pack['trial_duration_type'] == 'm') {
					$days = $pack['trial_duration'] * 28;
				} else {
					$days = $pack['trial_duration'] * 365;
				}
				$plan['trial_period_days'] = $days;
			}


			if ($r_code == 404) {
				// create
				list($r_code, $response) = $this->stripe_api_request('plans', 'POST', $plan);
				if ($r_code != 200) {
					$r = FALSE;
				}
			} else if ($r_code == 200) {
				// update?
				$call_update = FALSE;
				foreach ($plan as $key => $value) {
					if ($response->$key != $value) {
						$call_update = TRUE;
					}
				}
				if ($call_update) {
					// delete recreate
					list($r_code, $response) = $this->stripe_api_request('plans/' . $plan_id, 'DELETE');
					if ($r_code != 200) {
						$r = FALSE;
					} else {
						list($r_code, $response) = $this->stripe_api_request('plans', 'POST', $plan);
						if ($r_code != 200) {
							$r = FALSE;
						}
					}
				}
			} else {
				$r = FALSE;
			}
		}

		return $r;
	}

	private function sync_coupons($coupon_name = FALSE, $type = 'name') {
		$r = TRUE;

		$coupons = array();
		if ($coupon_name && $type == 'name') {
			if ($id = ym_get_coupon_id_by_name($coupon_name)) {
				$coupons = array(ym_get_coupon($id));
			}
		} else if ($coupon_name && $type == 'id') {
			$coupons = array(ym_get_coupon($coupon_name));
		} else {
			$coupons = ym_get_coupons();
		}

		foreach ($coupons as $coupon) {
			$value = $coupon->value;
			if (ym_get_coupon_type($value) == 'percent') {
				// only support percentages
				$allowed = str_split($coupon->allowed);
				if ($allowed[0] == 1 || $allowed[1] == 1) {
					// sub enabled
					$value = str_replace('%', '', $value);

					$create = FALSE;

					$id = 'ym_' . $coupon->id;
					$mycoupon = array(
						'id'			=> $id,
						'percent_off'	=> $value,
						'duration'		=> 'once'
					);

					list($r_code, $response) = $this->stripe_api_request('coupons/' . $id);
					if ($r_code == 200) {
						// check ok
						if (
							$response->percent_off != $value ||
							$response->duration != 'once'
							) {
							// update needed
							list($r_code, $response) = $this->stripe_api_request('coupons', 'DELETE', array('id' => $id));
							if ($r_code == 200) {
								$create = TRUE;
							} else {
								$r = FALSE;
							}
						}
					} else {
						$create = TRUE;
					}
					if ($create) {
						// doesn't exist
						list($r_code, $response) = $this->stripe_api_request('coupons', 'POST', $mycoupon);
						if ($r_code != 200) {
							$r = FALSE;
						}
					}
				}
			}
		}

		return $r;
	}
}

add_action('init', 'ym_stripe::js_driver');
add_action('login_head', 'ym_stripe::js_driver');
