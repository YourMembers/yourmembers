<?php

/*
* $Id: ym_zombaio.php 2586 2013-02-05 11:09:03Z bcarlyon $
* $Revision: 2586 $
* $Date: 2013-02-05 11:09:03 +0000 (Tue, 05 Feb 2013) $
*/

/*
* https://secure.zombaio.com/zoa/PDF/Zombaio_PWMGM_20070926.pdf
*/

class ym_zombaio extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_zombaio';

	var $action_url_base = 'https://secure.zombaio.com/?';

	function __construct() {
		$this->version = '$Revision: 2586 $';
		$this->name = __('Make payments with Zombaio', 'ym');
		$this->description = __('Zombaio is the only IPSP totally customized for the adult entertainment industry. Zombaio&#39;s technology enables the webmaster to accept card payment within hours. No startup fees, daily payouts and low transaction rates', 'ym');
		
		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		}
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/zombaio.gif';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$othisthisbj->status = 'test';

			$this->site_id = '';
			$this->gw_pass = '';
			$this->language = 'ZOM';
			$this->use_password = 1;
			$this->seal_code = '';

			$this->decline_url = '/';

			$this->bypass_ipn_ip_verification = false;

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

			if (!isset($pack['zombaio_price_id']) || !$pack['zombaio_price_id']) {
				unset($packs[$key]);
			}
		}

		return $packs;
	}

	function get_button_code($pack, $user_id, $override_price = FALSE) {
		// to be sure
		if (!isset($pack['zombaio_price_id']) || !$pack['zombaio_price_id']) {
			return FALSE;
		}

		get_currentuserinfo();
		global $current_user;

		// determine action_url
		$this->action_url = $this->action_url_base . $this->site_id . '.' . $pack['zombaio_price_id'] . '.ZOM';
		// end

		$email = isset($current_user->user_email) ? $current_user->user_email : '';
		$login = isset($current_user->user_login) ? $current_user->user_login : ym_get('username');
		if (!$email) {
			if ($user = ym_get('username')) {
				$email = get_user_by('login', $user);
				$email = $email->user_email;
			}
		}
		
		$data = array(
			'Email'					=> $email,
			'Username'				=> $login,
			'return_url_approve'	=> $this->redirectlogic($pack),
			'return_url_decline'	=> site_url($this->decline_url),
//			'return_url_error'		=> site_url($this->cancel_url)
		);

		// build extra
		if (isset($pack['id']) && $pack['id']) {
			$data['extra'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			if (isset($pack['ppp_pack_id'])) {
				$data['extra'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else if (isset($pack['ppp_adhoc_posts'])) {
				$data['extra'] = 'buy_post_' . implode(',', $pack['ppp_adhoc_posts']) . '_' . $user_id;
			} else {
				$data['extra'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id']:get_the_ID()) . '_' . $user_id;
			}
		}

		return $data;
	}

	// enable pay per post
	function pay_per_post($post_cost, $post_title, $return, $post_id) {
		$zom = get_post_meta($post_id, '_ym_post_purchasable_zombaio_price_id', TRUE);

		if (!$zom) {
			// not configured in Zombaio
			return FALSE;
		}

		$data = array(
			'post_id'			=> $post_id,
			'ppp'				=> true,
			'cost'				=> $post_cost,
			'duration'			=> 1,
			'item_name'			=> get_bloginfo() . ' ' . __('Post Purchase:', 'ym') . ' ' . $post_title,
			'zombaio_price_id'	=> $zom
		);
		return $data;
	}
	function pay_per_post_bundle($pack_cost, $pack_id, $title) {
		$zom = FALSE;
		global $wpdb;
		$query = 'SELECT additional FROM ' . $wpdb->prefix . 'ym_post_pack WHERE id = ' . $pack_id;
		if ($data = $wpdb->get_var($query)) {
			$data = unserialize($data);
			$zom = $data['zombaio_price_id'];
		}

		if (!$zom) {
			return FALSE;
		}

		$data = array(
			'ppp_pack_id'	=> $pack_id,
			'ppp'			=> true,
			'cost'			=> $pack_cost,
			'duration'		=> 1,
			'item_name'		=> get_bloginfo() . ' ' . __('Bundle Purchase:', 'ym') . ' ' . $title,
			'zombaio_price_id'			=> $zom
		);
		return $data;
	}
	// enable trial
	function enable_trial() {
	}

	// user interaction
	function ym_profile_unsubscribe_button($return = FALSE) {
		// TODO: Use API instead
	    $html = '<div style="margin-bottom: 10px;">
		    <h4>' . __('Zombaio Unsubscribe', 'ym') . '</h4>
		    <div style="margin-bottom: 10px;">' . __('If you wish to unsubscribe you can click the following link. You will be taken to Zomabio Support.', 'ym') . '</div>
		    <div>
			    <form action="http://support.zombaio.com/" method="post">
			    	<input type="submit" name="zombaio_cancel" value="' . __('Cancel Subscription', 'ym') . '" class="button-secondary" />
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
		$action = strtolower(ym_request('Action'));

		if (!ym_get('ZombaioGWPass')) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>No pass';
			exit;
		}

		$gw_pass = ym_get('ZombaioGWPass');
		if ($gw_pass != $this->gw_pass) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>Mismatch';
			exit;
		}

		if (!$this->verify_ipn_ip()) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed, you are not Zombaio.</h3>';
			exit;
		}

		// test hit from zombaio
		$test = substr(ym_request('username'), 0, 4);
		if ($test == 'Test' && !ym_request('extra')) {
			// test mode
			echo 'OK';
			exit;
		}

		// verify site ID, first catch user.add/delete second credits
		$site_id = ym_request('SITE_ID', ym_request('SiteID'));

		if ($site_id && $site_id != $this->site_id) {
			header('HTTP/1.0 401 Unauthorized');
			echo '<h1>Zombaio Gateway 1.1</h1><h3>Authentication failed.</h3>site id';
			exit;
		}

		// verify price ID exists
		$data = ym_request('extra');

		$complete = FALSE;
		switch ($action) {
			case 'user.add':
				$complete = TRUE;

				list($buy, $subscription, $pack_id, $user_id) = explode('_', $data);

				if ($this->use_password && ym_get('password')) {
					// use password
					$user_pass = wp_hash_password(ym_get('password'));

					global $wpdb;
					$query = 'UPDATE ' . $wpdb->users . ' SET user_pass = \'' . $user_pass . '\' WHERE ID = \'' . $user_id . '\'';
					$wpdb->query($query);
				}

				// store SUBSCRIPTION_ID
				$subscription_id = ym_get('SUBSCRIPTION_ID');
				update_user_meta($user_id, 'ym_zombaio_subscription_id', $subscription_id);

				break;
			case 'rebill':
				// renewal

				$subscription_id = ym_get('SUBSCRIPTION_ID');
				if (!$subscription_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>Missing Subscription ID.</h3>';
					exit;
				}
				//get user ID by subscription ID
				global $wpdb;
				$query = 'SELECT user_id FROM ' . $wpdb->usermeta . ' WHERE meta_key = \'ym_zombaio_subscription_id\' AND meta_value = \'' . $subscription_id . '\'';

				$user_id = $wpdb->get_var($query);
				if (!$user_id) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>User Not Known.</h3>';
					exit;
				}

				$success = ym_request('Success', 0);
				// 0 FAIL 2 FAIL retry in 5 days
				if ($success == 1) {
					$complete = TRUE;
				}

				$data = new YourMember_User($user_id);
				$pack_id = $data->pack_id;

				$data = 'buy_subscription_' . $pack_id . '_' . $user_id;
				break;

			case 'user.delete':
				$user = get_user_by('username', ym_get('username'));
				if (!$user) {
					header('HTTP/1.0 401 Unauthorized');
					echo '<h1>Zombaio Gateway 1.1</h1><h3>User Not Known.</h3>';
					exit;
				}
				$user_id = $user->ID;

				$data = new YourMember_User($user_id);
				$pack_id = $data->pack_id;

				$data = 'end_subscription_' . $pack_id . '_' . $user_id;

				break;
			case 'user.addcredits':
				$complete = FALSE;
				// no support
				$data = 'buy_credits_1_1';
		}

		$this->common_process($data, $this->code, $complete, FALSE);
		echo 'OK';

		exit;
	}
	function fail_process() {
		$data = array();

		switch (strtolower(ym_request('Action'))) {
			case 'user.add':
			case 'rebill':
				// just in case
				return;
			case 'user.delete':
				$data['new_status'] = YM_STATUS_CANCEL;
				$data['status_str'] = __('Zombaio user deleted', 'ym');
				break;
			case 'declined':
				$data['new_status'] = YM_STATUS_ERROR;
				$data['status_str'] = sprintf(__('Zombaio card declined, Code %s', 'ym'), ym_request('ReasonCode'));
				break;
			case 'user.addcredits':
				echo 'ERROR';
				exit;
				break;
			default:
				$data['new_status'] = YM_STATUS_NULL;
				$data['status_str'] = sprintf(__('Zombaio Unknown Action: %s', 'ym'), $ym_request('Action'));
		}

		return $data;
	}

	// options
	function load_options() {
		ym_display_message(__('You will need to create a site in <strong>Website Management</strong>, select Manual Installtion as the Installation Option', 'ym'), 'updated');
		ym_display_message(sprintf(__('After creation, you will be able to update/create the ZScript URL, use <strong>%s</strong> do not <strong>Validate</strong>, you need to copy the <strong>Site ID</strong> and <strong>Zombaio GW Pass</strong> into the relevant fields below, then you can validate and save the ZScript URL', 'ym'), site_url()), 'updated');

		$options = array();

		$options[] = array(
			'name'		=> 'site_id',
			'label'		=> __('Zombaio Site ID', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'gw_pass',
			'label'		=> __('Gateway Password', 'ym'),
			'caption'	=> '',
			'type'		=> 'text'
		);
		$options[] = array(
			'name'		=> 'language',
			'label'		=> __('Language', 'ym'),
			'caption'	=> '',
			'type'		=> 'select',
			'options'	=> array(
				'ZOM'	=> __('Default Language, based on IP', 'ym'),
				'US'	=> __('United States', 'ym'),
				'FR'	=> __('French', 'ym'),
				'DE'	=> __('German', 'ym'),
				'IT'	=> __('Italian', 'ym'),
				'JP'	=> __('Japanese', 'ym'),
				'ES'	=> __('Spanish', 'ym'),
				'SE'	=> __('Swedish', 'ym'),
				'KR'	=> __('Korean', 'ym'),
				'CH'	=> __('Traditional Chinese', 'ym'),
				'HK'	=> __('Simplified Chinese', 'ym')
			)
		);
		$options[] = array(
			'name'		=> 'use_password',
			'label'		=> __('Use Returned Password', 'ym'),
			'caption'	=> __('Users can create a password on the Zombaio interface which we can then use as the users WordPress password', 'ym'),
			'type'		=> 'select',
			'options'	=> array(
				0	=> __('No', 'ym'),
				1	=> __('Yes', 'ym')
			)
		);

		$options[] = array(
			'name'		=> 'seal_code',
			'label'		=> __('Seal Code', 'ym'),
			'caption'	=> sprintf(__('Put your Seal Code here, and then use the shortcode [ym_zombaio_seal] to display your Seal. To find your Seal see: <a href="%s" target="_blank">Our Guide</a>', 'ym'), 'http://www.yourmembers.co.uk/the-support/guides-tutorials/zombaio-payment-gateway/'),
			'type'		=> 'textarea'
		);

		$options[] = array(
			'name'		=> 'decline_url',
			'label' 	=> __('Decline URL', 'ym'),
			'caption'	=> __('On Payment Declined return to this URL', 'ym'),
			'type'		=> 'url'
		);

		$options[] = array(
			'name'		=> 'bypass_ipn_ip_verification',
			'label'		=> __('Bypass IPN IP Verification', 'ym'),
			'caption'	=> '',
			'type'		=> 'yesno'
		);

		return $options;
	}

	// additional pack fields
	function additional_pack_fields() {
		$items = array();
		$items[] = array(
			'name' => 'zombaio_price_id',
			'label' => __('Zombaio Price ID', 'ym'),
			'caption' => __('If unsure, just put the <strong>Join Form URL</strong> here', 'ym'),
			'type' => 'text'
		);

		// catch
		if (ym_post('zombaio_price_id')) {
			$entry = ym_post('zombaio_price_id');
			if (FALSE !== strpos($entry, 'zombaio.com')) {
				//https://secure.zombaio.com/?287653677.1384296.ZOM
				list($crap, $zombaio, $com_crap, $id, $zom) = explode('.', $entry);
				$_POST['zombaio_price_id'] = $id;
			}
		}

		return $items;
	}
	function additional_bundle_fields() {
		return $this->additional_pack_fields();
	}

	// Zombaio Specific
	function verify_ipn_ip() {
		if ($this->bypass_ipn_ip_verification) {
			return true;
		}
		$ip = $_SERVER['REMOTE_ADDR'];

		$data = 'http://www.zombaio.com/ip_list.txt';
		$data = ym_remote_request($data);
		if ($data) {
			$ips = explode('|', $data);

			if (in_array($ip, $ips)) {
				return true;
			}
		}
		return false;
	}
}

add_shortcode('ym_zombaio_seal', 'shortcode_zombaio_seal');
// seal code
function shortcode_zombaio_seal() {
	$align = isset($args['align']) ? 'align' . $args['align'] : 'aligncenter';
	$data = new ym_zombaio();
	return '<div class="' . $align . '" style="width: 130px;">' . $data->seal_code . '</div>';
}

// we get called/required at boot
if (isset($_GET['ZombaioGWPass'])) {
	$_GET['ym_process'] = 'ym_zombaio';
} else if (isset($_GET['Action']) 
	&& isset($_GET['TransactionID']) 
	&& isset($_GET['SiteID']) 
	&& isset($_GET['Hash'])) {
	// if its not zombaio GWPass and we have these four keys
	// its a credits test hit
	header('HTTP/1.0 200  OK');
	echo 'OK';
	exit;
}
