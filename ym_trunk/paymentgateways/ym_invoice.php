<?php

/*
* $Id: ym_invoice.php 2575 2013-01-29 15:48:23Z bcarlyon $
* $Revision: 2575 $
* $Date: 2013-01-29 15:48:23 +0000 (Tue, 29 Jan 2013) $
*/

class ym_invoice extends ym_payment_gateway {
	var $name = 'Invoice';
	var $code = 'ym_invoice';

	function __construct() {
		$this->version = '$Revision: 2575 $';
		$this->description = __('The Invoice Gateways handles sending of a invoice and honouring there of', 'ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		}
	}

	function activate() {
		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/invoice.png';
			$this->action_url = site_url('?ym_process=' . $this->code);
			$this->membership_words = __('Invoice Account', 'ym');
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->new_grace = FALSE;
			$this->invoice_limit = '28';
			$this->invoice_email_subject = __('[[blogname]] - Subscription Invoice', 'ym');
			$this->invoice_email_message = 'Hello [user_name],' . "\n\n" . 'You are being invoiced for [pack_name] costing [pack_cost] the terms are [pay_days] days the Invoice ID is: [ym_invoice_id]';

			$this->subscribed = __('You have been Invoiced, in order to continue you may need to Pay before Access is permitted', 'ym');

			$this->notify_admin_on_grace = true;

			$this->save();
		}
	}
	function deactivate() {
	}

	// button gen
	function build_custom($data, $user_id) {
		if (isset($data['duration'])) {
			// pack
			$custom = 'buy_subscription_' . $data['id'] . '_' . $user_id;
			return $custom;
		} else {
			// post
		}
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
		$custom = $this->build_custom($pack, $user_id);

		$data = array(
			'custom' => $custom,
			'cost' => $override_price ? $override_price : $pack['cost']
		);
		return $data;
	}

	// process
	function do_process() {
		$custom = ym_post('custom');

		list($buy, $what, $pack_id, $user_id) = explode('_', $custom);

		// verify
		$safe = FALSE;
		global $ym_packs;
		foreach ($ym_packs->packs as $pack) {
			if ($pack['id'] == $pack_id) {
				$safe = TRUE;
			}
		}
		if (!$safe) {
			// error
			echo 'Could not Find a pack match';
			return;
		}

		if ($what != 'subscription') {
			// abort
			$failed = TRUE;
			$failed = apply_filters('ym_purchase_unknown', $failed, $custom, ym_post('cost'), TRUE, FALSE);

			if ($failed) {
				// failed on what to buy/id/user_id missing
				header('HTTP/1.1 400 Bad Request');
				echo '<p>Unknown Purchase String, an error has occured ' . $custom . '</p>';
				exit;
			} else {
				header('HTTP/1.1 200 OK');
				// what to pass????
				$this->redirectlogic(null, true);
			}
		}

		// disable email
		$this->nomore_email = TRUE;
		// buy
		$this->do_buy_subscription($pack_id, $user_id, TRUE);

		// done
		$user = new YourMember_User($user_id);
		// get reg data
		$info = get_userdata($user_id);
		$reg_date = strtotime($info->user_registered);

		$new = FALSE;
		if ($reg_date > (time() - 86400)) {
			// reg today
			$new = TRUE;
		}

		if (($this->new_grace && $new) || !$new) {//($this->old_grace)) {
			$data['status'] = YM_STATUS_GRACE;
			$data['status_str'] = __('Grace Entered, Invoice Payment Pending', 'ym');
			$data['expire_date'] = time() + (86400 * $this->invoice_limit);
		} else {
			$data['status'] = YM_STATUS_PENDING;
			$data['status_str'] = __('Invoice Payment Pending', 'ym');
		}

		$data['payment_type'] = 'invoice';
//		$data['last_pay_date'] = time();
		$data['invoiced_date'] = time();
		@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . $data['status_str'], $user_id);

		$user->update($data);
		$user->save();

		// invoice
		$this->generate_invoice($user, $this);//, $cost);

		// actions
		do_action('ym_gateway_ym_invoice_after_user_update', $user_id, $custom);

		$this->redirectlogic(ym_get_pack_by_id($pack_id), TRUE);
	}

	// options
	// doesn't really load, just setups the array for fields
	function load_options() {
		echo '<div id="message" class="updated"><p>' . __('The Invoice Gateway uses the Global Payment Settings Grace options to control user expiry', 'ym') . '</p></div>';

		$options = array();

		$options[] = array(
			'name'		=> 'new_grace',
			'label'		=> __('Put new User into Grace instead of Pending', 'ym'),
			'caption'	=> __('This could mean a user has free access, without paying', 'ym'),
			'type'		=> 'yesno'
		);

		$days =array();
		for ($x=1;$x<=128;$x++) {
			$days[$x] = $x;
		}

		$options[] = array(
			'name'		=> 'invoice_limit',
			'label'		=> __('Days to request a User pay in (days)', 'ym'),
			'caption'	=> __('This is also the Grace Limit Value used for Initial and Renewal Grace', 'ym'),
			'type'		=> 'select',
			'options'	=> $days
		);

		$options[] = array(
			'name'		=> 'invoice_email_subject',
			'label'		=> __('Email Subject', 'ym'),
			'caption'	=> __('When sending a Invoice Email use this subject, supports [blogname]', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'invoice_email_message',
			'label'		=> __('Email Message', 'ym'),
			'caption'	=> __('Use this message, supports [user_name], [ym_invoice_id], [pack_name], [pack_cost], [pack_cost_inc_tax], [pack_cost_ex_tax], [pack_cost_tax], (includes Currency Code), [pay_days], [ym_user_custom], [ym_user_is], [ym_user_is_not], [ym_user_custom_is] and [ym_user_custom_is_not]', 'ym'),
			'caption'	=> __('Use this message, supports [user_name], [ym_invoice_id], [pack_name], [pack_cost], [pack_cost_inc_tax], (includes Currency Code), [pay_days], [ym_user_custom], [ym_user_is], [ym_user_is_not], [ym_user_custom_is] and [ym_user_custom_is_not]', 'ym'),
			'type'		=> 'wp_editor'
		);

		$options[] = array(
			'name'		=> 'notify_admin_on_grace',
			'label'		=> __('Notify the admin when a user expires and enters grace', 'ym'),
			'caption'	=> __('This occurs when a User is sent a new invoice at the end of a subscription period and renewal occurs', 'ym'),
			'type'		=> 'yesno'
		);

		$options[] = array(
			'name'		=> 'subscribed',
			'label'		=> __('Message to show users on Succesful Registration/Upgrade', 'ym'),
			'caption'	=> __('This option is also on Advanced -> Messages -> Login Messages', 'ym'),
			'type'		=> 'text'
		);

		return $options;
	}

	// invoice generate and send
	function generate_invoice($user, $obj, $cost = FALSE) {
		$message = $obj->invoice_email_message;
		$pay_days = $obj->invoice_limit;

		$user_name = $user->data->user_login;
		$pack_data = ym_get_pack_by_id($user->pack_id);

		$cost = $cost ? $cost : $pack_data['cost'];
		$pack_name = ym_get_pack_label($user->pack_id);

		$message = str_replace('[user_name]', $user_name, $message);
		$message = str_replace('[pack_name]', $pack_name, $message);
		$message = str_replace('[pack_cost]', $cost . ' ' . ym_get_currency($pack_data['id']), $message);
		$message = str_replace('[pay_days]', $pay_days, $message);

		$email = $user->data->user_email;
		
		$subject = $obj->invoice_email_subject;
		$subject = str_replace('[blogname]', get_bloginfo(), $subject);

		remove_all_shortcodes();
		add_shortcode('ym_user_is', 'ym_user_is');
		add_shortcode('ym_user_is_not', 'ym_user_is_not');
		add_shortcode('ym_user_custom', 'ym_shortcode_user');
		add_shortcode('ym_user_custom_is', 'ym_user_is');
		add_shortcode('ym_user_custom_is_not', 'ym_user_is_not');

		add_shortcode('ym_invoice_id', array($this, 'getinvoiceid'));

		global $ym_this_transaction_id;
		if ($ym_this_transaction_id) {
			$user->invoice_id = $ym_this_transaction_id;
		} else {
			$ym_this_transaction_id = $user->invoice_id;
			$r = __('Resent Invoice', 'ym');
			if (!strpos($user->status_str, $r)) {
				$user->status_str = $user->status_str . ' ' . $r;
			}
		}
		$user->save();

//		add_shortcode('pack_cost_inc_tax', array($this, 'pack_cost_inc_tax'));
//		add_shortcode('pack_cost_ex_tax', array($this, 'pack_cost_ex_tax'));
//		add_shortcode('pack_cost_tax', array($this, 'pack_cost_tax'));
//		$message = str_replace('[pack_cost_inc_tax]', $this->pack_cost_inc_tax($user->pack_id, $cost), $message);
//		$message = str_replace('[pack_cost_ex_tax]', $this->pack_cost_ex_tax($user->pack_id, $cost), $message);
//		$message = str_replace('[pack_cost_tax]', $this->pack_cost_tax($user->pack_id, $cost), $message);
		$message = str_replace('[pack_cost_inc_tax]', $this->pack_cost_inc_tax($user->pack_id, $cost), $message);

//		$message = ym_apply_filter_the_content($message);
		$message = do_shortcode($message);

		ym_email($email, $subject, $message);
		return;
	}

	// shortcodes
	function getinvoiceid() {
		global $ym_this_transaction_id;
		return $ym_this_transaction_id;
	}
	function pack_cost_inc_tax($pack_id, $cost) {
		// get sys tax
		global $ym_sys;
		$rate = ($ym_sys->vat_rate) / 100;//eg 17.5 / 100 = 0.175
		$cost = number_format($cost, 2, '.', '');
		$amount = $cost * $rate;
		$total = $amount + $cost;
		$total = number_format($total, 2, '.', '') . ' ' . ym_get_currency($pack_id);
		return $total;
	}

	function ym_user_api_expose($extras) {
		$extras[] = 'invoice_id';
		$extras[] = 'invoiced_date';
		return $extras;
	}

	// hackage/filter functions
	function inject_tab($navigation) {

		$navigation[__('Members', 'ym')][__('Invoice', 'ym')] = 'ym-hook-invoice_tab';

		return $navigation;
	}
	function invoice_tab() {
		$invoice = new ym_invoice();
		global $wpdb;

		if (ym_post('user_id')) {
			$user_id = ym_post('user_id');

			$op = ym_post('op', '');
			$undo = ym_post('undo', FALSE);

			$user = new YourMember_User($user_id);

			if ($undo) {
				$data = array(
					'status'		=> YM_STATUS_PENDING,
					'status_str'	=> __('Invoice Undo', 'ym')
				);
				$user->update($data);
				$user->save();

				$packet = array(
					'user_id' => $user_id,
					'status' => FALSE
				);

				do_action('ym_invoice_status_update', $packet);
			} else if ($op == 'resend') {
				$invoice->generate_invoice($user, $invoice);

				echo '<div id="message" class="updated"><p>' . __('Inovice Resent', 'ym') . '</p></div>';

				@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Invoice Resent', 'ym'), $user_id);
			} else if ($op == 'active') {
				$data = array(
					'status'		=> YM_STATUS_ACTIVE,
					'status_str'	=> __('Invoice Paid', 'ym'),
					'amount'		=> intval(ym_post('amount', 0)),
					'last_pay_date'	=> time()
				);

				$current_status = $user->status;
				if ($current_status == YM_STATUS_GRACE) {
					$extend = $user->last_pay_date;
					$packdata = ym_get_pack_by_id($user->pack_id);
					$data['expire_date'] = $user->expiry_time($packdata['duration'], $packdata['duration_type'], $extend);
				}

				// check for force end
				if (isset($packdata['force_end_date'])) {
					$force_end_date = $packdata['force_end_date'];
					if ($force_end_date > time()) {
						// greater than now
						@ym_log_transaction(YM_ACCESS_EXTENSION, 'Adjustment (Force End Date): ' . $force_end_date, $user_id);
						$data['expire_date'] = $force_end_date;
					}
				}

				$data['amount'] = preg_replace('/[^\d\.]/', '', $data['amount']);
				$data['amount'] = number_format($data['amount'], 2, '.', '');

				$user->update($data, TRUE);

				$optional = ym_post('optional');
				if (!$optional) {
					$optional = __('Invoice Paid', 'ym');
				}
				@ym_log_transaction(YM_IPN, $optional, $user_id);

				@ym_log_transaction(YM_PAYMENT, $data['amount'], $user_id);
				@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'] . ' - ' . $data['status_str'], $user_id);

				echo '<div id="message" class="updated"><p>' . __('Updated and Activated the User', 'ym') . '</p></div>';

				$packet = array(
					'user_id' => $user_id,
					'pack_id' => $user->pack_id,
					'status' => TRUE
				);

				$invoice = new ym_invoice();
				$invoice->notify_user($packet);

				do_action('ym_invoice_status_update', $packet);
			}
		}

		echo '<div id="poststuff" class="wrap">';
		ym_box_top(__('Invoice Management', 'ym'));

		$search = ym_post('ym_invoice_search', false);
		if ($search) {
			$query = 'SELECT u.user_id AS ID FROM ' . $wpdb->usermeta . ' u
				LEFT JOIN ' . $wpdb->usermeta . ' s ON s.user_id = u.user_id
				LEFT JOIN ' . $wpdb->users . ' us ON us.id = u.user_id
				WHERE
				u.meta_key = \'ym_payment_type\' AND u.meta_value = \'invoice\' 
				AND s.meta_key = \'ym_status\'
				AND (
					us.user_login LIKE \'%' . $search . '%\'
					OR
					us.user_email LIKE \'%' . $search . '%\'
				)
				ORDER BY ID DESC
				';
		} else {
			$query = 'SELECT u.user_id AS ID FROM ' . $wpdb->prefix . 'usermeta u
				LEFT JOIN ' . $wpdb->prefix . 'usermeta s ON s.user_id = u.user_id
				WHERE
				u.meta_key = \'ym_payment_type\' AND u.meta_value = \'invoice\' 
				AND s.meta_key = \'ym_status\'
				ORDER BY ID DESC
				';
		}
		$results = $wpdb->get_results($query);

		// quick search
		if ($wpdb->num_rows != 0 || $search) {
			// render search form
			echo '
<form action="" method="post" style="float: right;">
<fieldset>
	' . __('Username/Email Search:', 'ym') . '
	<input type="text" name="ym_invoice_search" value="' . $search . '" />
	<input type="submit" value="' . __('Search', 'ym') . '" />
</fieldset>
</form>';
		}

		echo '<p>' . __('Here you can update users based on the honouring of their invoice, you can use the Info to store field to store extra IPN style info such as a Cheque Number', 'ym') . '</p>';

		if ($wpdb->num_rows == 0) {
			echo ym_display_message(__('No Users are Invoice Pending', 'ym'), 'error');
		} else {
			echo '<table class="widefat">';

			echo '<tr>
				<th>' . __('Member', 'ym') . '</th>
				<th>' . __('Invoice Ref', 'ym') . '</th>
				<th>' . __('Purchasing', 'ym') . '</th>
				<th>' . __('Paid/Invoiced On Date', 'ym') . '</th>
				<th>' . __('Member Status', 'ym') . '</th>
				<th>' . __('Payment', 'ym') . '</th>
			</tr>';

			foreach ($results as $row) {
				$user = new YourMember_User($row->ID);
				echo '<tr>';
				echo '<td>(' . $row->ID . ') ' . $user->data->user_email;
				echo '<br />';
				if ($user->data->user_email != $user->data->user_login) {
					echo $user->data->user_login . ' ';
				}
				echo $user->data->display_name;
				echo '</td>';

				echo '<td>#' . $user->invoice_id . '</td>';
				echo '<td>' . ym_get_pack_label($user->pack_id) . '</td>';

				echo '<td nowrap="nowrap" style="';

				// go red if overdue
				$limit = $user->invoiced_date + (86400 * $invoice->invoice_limit);
				// last pay date is invoiced on date
				// limit is due date for this invoice
				if (time() > $limit && $user->status != YM_STATUS_ACTIVE) {
					echo 'background: red;';
				} else if (time() < $limit && $user->status != YM_STATUS_ACTIVE) {
					echo 'background: #EFEFEF;';
				}

				echo '">';
				if ($user->status != YM_STATUS_ACTIVE) {
					echo __('Invoiced', 'ym') . ' ' . date(YM_DATE, $user->invoiced_date);
					echo '<br />' . __('Due', 'ym') . ' ' . date(YM_DATE, $limit);
				} else {
					echo date(YM_DATE, $user->last_pay_date);
				}
				echo '</td>';

				echo '<td>' . $user->status . ' - ' . $user->status_str . '</td>';
				echo '<td>
				<form action="" method="post">
				<table><tr><td nowrap="nowrap">
					<input type="hidden" name="search" value="' . $search . '" />
					<input type="hidden" name="user_id" value="' . $row->ID . '" />
					';
				if ($user->status == YM_STATUS_ACTIVE) {
					echo $user->amount;
					// last ipn
					$query = 'SELECT data FROM ' . $wpdb->prefix . 'ym_transaction WHERE action_id = ' . YM_IPN . ' AND user_id = ' . $row->ID . ' ORDER BY id DESC LIMIT 1';
					$data =  $wpdb->get_var($query);
					if (substr($data, 0, 2) != 'a:') {
						echo ' - ';
						echo $data;
					}
					echo '</td><td>';
					echo '</td><td>';
					echo '
					<input type="hidden" name="undo" value="1" />
					<input type="submit" class="button-secondary deletelink" style="float: right;" value="' . __('Undo Active', 'ym') . '" />
					';
				} else {
					echo '
					<label for="amount">' . __('Payment Amount', 'ym') . '</label> 
					<br />
					<label for="optional">' . __('Info to Store', 'ym') . ' 
					</td><td>
					<input type="text" name="amount" id="amount" value="" size="4" />
					<br />
					<input type="text" name="optional" id="optional" value="" size="4" /></label> 
					';
					echo '</td><td>';
					echo '
					<input type="submit" class="button-secondary deletelink" style="float: right;" value="' . __('Payment Recieved - Make Active', 'ym') . '" onclick="jQuery(\'#op_' . $row->ID . '\').val(\'active\');" />
					';
					echo '</td><td>';
					echo '
					<input type="submit" class="button-secondary" style="float: right;" value="' . __('Resend Invoice', 'ym') . '" onclick="jQuery(\'#op_' . $row->ID . '\').val(\'resend\');" />
					';
				}
				echo '
					<input type="hidden" name="op" id="op_' . $row->ID . '" value="" />
				</td></tr></table>
				</form>
					</td>';
				echo '</tr>';
			}
			echo '</table>';
		}

		ym_box_bottom();
		echo '</div>';
	}

	function expire_interrupt($data, $ymuser) {
		// interrupt and send to grace?
		// this is fired the moment they expire (ish)
		global $ym_sys;
		if (!$ym_sys->grace_enable) {
			// grace not on
			return $data;
		}

		// instantiate
		$invoice = new ym_invoice();
		if ($ymuser->gateway_used != $invoice->code) {
			return $data;
		}

		// check package
		$package = ym_get_pack_by_id($ymuser->pack_id);
		if ($package->num_cycles == 1) {
			// single occurance
			return $data;
		}

		// lets put them into grace
		$data = array(
			'status'		=> YM_STATUS_GRACE,
			'status_str'	=> __('User is entering Invoice Grace', 'ym'),
			'expire_date'	=> (time() + (86400 * $invoice->invoice_limit)),
		);

		@ym_log_transaction(YM_ACCESS_EXPIRY, $data['expire_date'], $ymuser->userId);
		@ym_log_transaction(YM_USER_STATUS_UPDATE, YM_STATUS_GRACE, $ymuser->userId);

		// trigger invoice email
		$this->generate_invoice($ymuser, $invoice);

		// notify admin
		if ($invoice->notify_admin_on_grace) {
			$email = get_bloginfo('admin_email');
			$subject = '[' . get_bloginfo() . '] ' . __('Invoice notification', 'ym');
			$message = __('The user ' . $ymuser->data->user_login . ' is entering Invoice Grace and has been sent a invoice', 'ym');

			ym_email($email, $subject, $message);
		}

		return $data;
	}
	function grace_limit_adjust($cur_grace, $ymuser) {
		$invoice = new ym_invoice();
		if ($ymuser->gateway_used != $invoice->code) {
			return $cur_grace;
		}
		$cur_grace = $invoice->invoice_limit;
		return $cur_grace;
	}

	function ym_login_message($message) {
		if (ym_request('checkemail') == 'subscribed' && ym_request('from_gateway') == 'ym_invoice') {
			$invoice = new ym_invoice();
			$message = '<p class="message">' . $invoice->subscribed . '</p>';
		}
		return $message;
	}

	function ym_login_message_admin() {
		global $ym_formgen;

		$invoice = new ym_invoice();
		$ym_formgen->render_form_table_text_row('checkemail=subscribed, and invoice gateway used', 'checkemail_subscribed_invoice', $invoice->subscribed, '');
	}
	function ym_login_message_admin_save() {
		$invoice = new ym_invoice();
		$invoice->subscribed = ym_post('checkemail_subscribed_invoice');
		$invoice->save();
	}

	function ym_email_message_save() {
		$invoice = new ym_invoice();
		$invoice->invoice_email_subject = ym_post('invoice_email_subject');
		$invoice->invoice_email_message = ym_post('invoice_email_message');
		$invoice->save();
	}
	function ym_email_message_tabs() {
		echo '<li><a href="#ym_payment_gateway_invoice">' . __('Invoice Gateway Emails', 'ym') . '</a></li>';
	}
	function ym_email_message_content() {
		$invoice = new ym_invoice();
		echo '<div id="ym_payment_gateway_invoice">';
		echo ym_start_box(__('Invoice Gateway Emails', 'ym'));
		echo '<table class="form-table">';

		global $ym_formgen;
		$ym_formgen->render_form_table_text_row(__('Invoice Email Subject', 'ym'), 'invoice_email_subject', $invoice->invoice_email_subject, __('When sending a Invoice Email use this subject, supports [blogname]', 'ym'));
		$ym_formgen->render_form_table_wp_editor_row(__('Invoice Email Message', 'ym'), 'invoice_email_message', $invoice->invoice_email_message, __('Use this message, supports [user_name], [pack_name], [pack_cost] (includes Currency Code), [pay_days], [ym_user_custom], [ym_user_is], [ym_user_is_not], [ym_user_custom_is] and [ym_user_custom_is_not]', 'ym'));

		echo '</table>';
		echo ym_end_box();
		echo '</div>';
	}
}

add_filter('ym_navigation', array('ym_invoice', 'inject_tab'));
add_action('ym-hook-invoice_tab', array('ym_invoice', 'invoice_tab'));

add_filter('ym_user_expire_check_into_expire', array('ym_invoice', 'expire_interrupt'), 10, 2);
add_filter('ym_user_grace_limit_adjust', array('ym_invoice', 'grace_limit_adjust'), 10, 2);

add_filter('ym_login_message', array('ym_invoice', 'ym_login_message'));

add_action('ym_login_messages_extra_messages', array('ym_invoice', 'ym_login_message_admin'));
add_action('ym_login_messages_extra_messages_save', array('ym_invoice', 'ym_login_message_admin_save'));

add_action('ym-advanced-payment_gateway_email_save', array('ym_invoice', 'ym_email_message_save'));
add_action('ym-advanced-payment_gateway_email_tabs', array('ym_invoice', 'ym_email_message_tabs'));
add_action('ym-advanced-payment_gateway_email_tab_content', array('ym_invoice', 'ym_email_message_content'));

add_filter('ym_user_api_expose', array('ym_invoice', 'ym_user_api_expose'));
