<?php

/*
* $Id: ym_free.php 2090 2012-04-11 15:31:48Z bcarlyon $
* $Revision: 2090 $
* $Date: 2012-04-11 16:31:48 +0100 (Wed, 11 Apr 2012) $
*/

class ym_free extends ym_payment_gateway {
	var $name = 'Free Memberships';
	var $code = 'ym_free';

	function __construct() {
		$this->version = '$Revision: 2090 $';
		$this->description = __('The Free gateway handles free subscriptions. And Coupons that result in Free/No Cost to user transactions', 'ym');
		$this->action_url = site_url('?ym_process=ym_free');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		}

		add_action('ym_gateway_return_ym_free', array($this, 'ym_free_redirect'), 10, 1);
	}

	function activate() {
		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/free.gif';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

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
			if ($cost_test != 0) {
				unset($packs[$key]);
			}
		}

		return $packs;
	}

	function get_button_code($pack, $user_id) {
		$custom = $this->build_custom($pack, $user_id);

		$data = array(
			'custom'	=> $custom,
			'return'	=> $this->redirectlogic($pack),
		);
		return $data;
	}

	// process
	function do_process() {
		$freebie_code = ym_request('freebie_code');
		if ($freebie_code) {
			$this->common_process($freebie_code, 'Free/Coupon', TRUE, FALSE);
			list($buy, $what, $id, $user_id) = explode('_', $freebie_code);
			if ($what == 'subscription') {
				$data = array(
					'id'			=> $id,
					'cost'			=> 'Free/Coupon',
					'duration'		=> 1,
					'item_name'		=> get_bloginfo() . ' ' . __('Subscription Purchase:', 'ym') . ' ' . $post_title
				);
			} else if ($what == 'post') {
				$data = array(
					'post_id'		=> $id,
					'ppp'			=> true,
					'cost'			=> 'Free/Coupon',
					'duration'		=> 1,
					'item_name'		=> get_bloginfo() . ' ' . __('Post Purchase:', 'ym') . ' ' . get_post_title($id)
				);
			} else {
				// assume bundle
				$bundle = ym_get_bundle($id);
				$data = array(
					'ppp_pack_id'	=> $id,
					'ppp'			=> true,
					'cost'			=> 'Free/Coupon',
					'duration'		=> 1,
					'item_name'		=> get_bloginfo() . ' ' . __('Bundle Purchase:', 'ym') . ' ' . $bundle->name
				);				
			}
			$this->redirectlogic($data, TRUE);
		}

		$custom = ym_request('custom');
		if (!$custom) {
			echo 'No Data Passed';
			return;
		}
		list($buy, $what, $pack_id, $user_id) = explode('_', $custom);

		// verify
		$safe = FALSE;
		global $ym_packs;
		foreach ($ym_packs->packs as $pack) {
			if ($pack['id'] == $pack_id) {
				$cost_test = $pack['cost'];
				if (strpos($cost_test, '.')) {
					$cost_test = $cost_test * 100;
				}
				if ($cost_test == 0) {
					$safe = TRUE;
				}
			}
		}
		if (!$safe) {
			// error
			print_r($_POST);
			echo 'Could not Find a pack match';
			return;
		}
		$this->do_buy_subscription($pack_id, $user_id, TRUE);
	}

	// user actually goes to mod process for ym_free hence redirect call here
	function ym_free_redirect($packet) {
		// as we have interruped and about to redirect with exit
		$this->notify_user($packet);

		$data = array();
		if (isset($packet['pack_id'])) {
			$data = $packet['pack_id'];
		} else {
			$data['ppp'] = 1;
			if (isset($packet['ppack_id']) && $packet['ppack_id']) {
				$data['ppp_pack_id'] = $packet['ppack_id'];
			} else {
				$data['post_id'] = $packet['post_id'];
			}
		}

		$url = (isset($_POST['return'])) ? $_POST['return'] : $this->redirectlogic($data);
		header('Location: ' . $url);
		exit;
	}

	// options
	// doesn't really load, just setups the array for fields
	function load_options() {
		$options = array();
		// no additional options
		return $options;
	}

	// special function to handle free purchase of stuff that occurs via coupon
	function free_purchase($what, $id, $user_id) {
		$code = 'buy_' . $what . '_' . $id . '_' . $user_id;
		$phrase = $what . '_purchase_words';

		return '
<form action="'. $this->action_url .'" method="post" class="ym_form ' . $this->code . '_form form-table" name="' . $this->code . '_form" id="' . $this->code . '_form"><fieldset>
	<input type="hidden" name="freebie_code" value="' . $code . '" />
	<input type="image" src="' . $this->logo . '" border="0" name="submit" alt="' . $this->$phrase . '" />
</fieldset></form>';
	}
}
