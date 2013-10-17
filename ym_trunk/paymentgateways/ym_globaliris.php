<?php

/*
* $Id: ym_hsbc.php 2181 2012-05-29 14:01:05Z bcarlyon $
* $Revision: 2181 $
* $Date: 2012-05-29 15:01:05 +0100 (Tue, 29 May 2012) $
*/

class ym_globaliris extends ym_payment_gateway {
	var $name = '';
	var $code = 'ym_globaliris';

	function __construct() {
		$this->version = '$Revision: 2181 $';
		$this->name = __('Make Payments with HSBC', 'ym');
		$this->description = __('HSBC Provides a CardHolder Payment Interface (CPI) for Global Iris', 'ym');

		if (get_option($this->code)) {
			$obj = get_option($this->code);
			foreach ($obj as $var => $val) {
				$this->$var = $val;
			}
		} else {
			return;
		}

		if ($this->status == 'live') {
			$this->mode = 'P';
		} else {
			$this->mode = 'T';
		}

		$this->action_url = 'https://www.cpi.globaliris.com/servlet';

		// check SSL
//		if (!is_ssl()) {
//			// we wont deactivate but stop future calls to us
//			global $ym_active_modules;
//			foreach ($ym_active_modules as $key => $module) {
//				if ($module == $this->code) {
//					unset($ym_active_modules[$key];
//					return;
//				}
//			}
//		}
	}

	function activate() {
		global $ym_sys;

		if (!get_option($this->code)) {
			$this->logo = YM_IMAGES_DIR_URL . 'pg/hsbc.png';
			$this->membership_words = $this->name;
			$this->post_purchase_words = $this->name;
			$this->bundle_purchase_words = $this->name;

			$this->status = 'test';

			$this->store_front_id = '';
			$this->cpi_hash_key = '';

			$this->cancel_url = '/';
			$this->error_url = '/';

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
		global $ym_sys;

		$cost = $override_price ? $override_price : $pack['cost'];
		$cost = $cost * 100;//convert to pence

		$data = array(
			'CpiDirectResultUrl'	=> site_url('?ym_process=' . $this->code),
			'CpiReturnUrl'			=> site_url('?ym_process=' . $this->code),
			'MerchantData' => '',
			'Mode'					=> $this->mode,
			'OrderDesc'				=> ((isset($pack['item_name']) && $pack['item_name']) ? $pack['item_name'] : $ym_sys->item_name),
			'OrderId' => '',
			'PurchaseAmount'		=> $cost,
			'PurchaseCurrency'		=> $this->currency_to_code(ym_get_currency()),
			'StorefrontId'			=> $this->store_front_id,
			'TimeStamp'				=> (time() * 1000),
			'TransactionType'		=> 'Capture',
			'UserId'				=> $user_id,
		);


		if ($this->map_billingaddress1) {
			$data = array_merge($data, array(
				'BillingAddress1' => '',
				'BillingCity' => '',
				'BillingCountry' => '',
				'BillingFirstName'		=> get_user_by('id', $user_id)->user_firstname,
				'BillingLastName'		=> get_user_by('id', $user_id)->user_lastname,
				'BillingPostal' => '',
				'ShopperEmail'			=> get_user_by('id', $user_id)->user_email,
				'ShippingAddress1' => '',
				'ShippingCity' => '',
				'ShippingCountry' => '',
				'ShippingFirstName' => '',
				'ShippingLastName' => '',
				'ShippingPostal' => '',
			));
			$data['ShippingFirstName'] = $data['BillingFirstName'];
			$data['ShippingLastName'] = $data['BillingLastName'];
		}

		// custom
		if (isset($pack['id']) && $pack['id']) {
			$data['MerchantData'] = 'buy_subscription_' . $pack['id'] . '_' . $user_id;
		} else {
			if (isset($pack['ppp_pack_id'])) {
				$data['MerchantData'] = 'buy_bundle_' . $pack['ppp_pack_id'] . '_' . $user_id;
			} else {
				$data['MerchantData'] = 'buy_post_' . ($pack['post_id'] ? $pack['post_id'] : get_the_ID()) . '_' . $user_id;
			}
		}

		$data['OrderId'] = $data['MerchantData'] . '_' . time();

		wp_set_current_user($user_id);
		$customs = ym_get_custom_fields($user_id);

		// MAP
		if ($this->map_billingaddress1) {
			$data['BillingAddress1']	= ym_shortcode_user(array('name' => $this->map_billingaddress1), '', 'ym_user_custom');
			$data['BillingCity']		= ym_shortcode_user(array('name' => $this->map_billingcity), '', 'ym_user_custom');
			$data['BillingCountry']		= ym_shortcode_user(array('name' => $this->map_billingcountry), '', 'ym_user_custom');
			$data['BillingPostal']		= ym_shortcode_user(array('name' => $this->map_billingpostal), '', 'ym_user_custom');
			$data['ShippingAddress1']	= ym_shortcode_user(array('name' => $this->map_shippingaddress1), '', 'ym_user_custom');
			$data['ShippingCity']		= ym_shortcode_user(array('name' => $this->map_shippingcity), '', 'ym_user_custom');
			$data['ShippingCountry']	= ym_shortcode_user(array('name' => $this->map_shippingcountry), '', 'ym_user_custom');
			$data['ShippingPostal']		= ym_shortcode_user(array('name' => $this->map_shippingpostal), '', 'ym_user_custom');

			// code convert
			$data['BillingCountry'] = $this->country_to_code($data['BillingCountry']);
			$data['ShippingCountry'] = $this->country_to_code($data['ShippingCountry']);
		}

		// all fields for hash present
		$data['OrderHash'] = $this->generateHash($data, $this->cpi_hash_key);

		if ($this->map_billingaddress1) {
			// add optional fields
			if ($this->map_billingaddress2) {
				$data['BillingAddress2']	= ym_shortcode_user(array('name' => $this->map_billingaddress2), '', 'ym_user_custom');
			}
			if ($this->map_billingcounty) {
				$data['BillingCounty']		= ym_shortcode_user(array('name' => $this->map_billingcounty), '', 'ym_user_custom');
			}
			if ($this->map_shippingaddress2) {
				$data['ShippingAddress2']	= ym_shortcode_user(array('name' => $this->map_shippingaddress2), '', 'ym_user_custom');
			}
			if ($this->map_shippingcounty) {
				$data['ShippingCounty']		= ym_shortcode_user(array('name' => $this->map_shippingcounty), '', 'ym_user_custom');
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

	// process
	function do_process() {
		// IPN handler:
//		echo '<p>One Moment... Processing</p>';

		$code = ym_post('CpiResultsCode', FALSE);
		$what = ym_post('MerchantData', FALSE);
		$hash = ym_post('OrderHash', FALSE);

		if (!isset($_POST['CpiResultsCode']) || !$what || !$hash) {
			echo '<p>Missing Data cannot process</p>';
			exit;
		}

		$amount = ym_post('PurchaseAmount');

		$complete = FALSE;
		if  ($code > 0) {
			// error
			if ($code == 1) {
				// use cancel
				header('Location: ' . site_url($this->cancel_url));
				exit;
			}
		} else {
			// code is 0 which is hurrah
			$complete = TRUE;
		}

		// validate Hash
		$calcHash = array(
			$code,
			ym_post('PurchaseDate'),
			$what,
			ym_post('OrderId'),
			$amount,
			ym_post('PurchaseCurrency'),
			ym_post('ShopperEmail'),
			ym_post('StorefrontId')
		);
		$calcHash = $this->generateHash($calcHash, $this->cpi_hash_key);

		if ($calcHash != $hash) {
			echo '<p>Hash Mis Match - Security Error</p>';
			exit;
		}
		// convert to primary units
		$amount = $amount / 100;

		$this->common_process($what, $amount, $complete, FALSE);

		if ($complete) {
			list($buy, $what, $id, $user_id) = explode('_', $what);
			if ($what == 'subscription') {
				$url = $this->redirectlogic($id);
			} else if ($what == 'post') {
				$pack = $this->pay_per_post($amount, '', '', $id);
				$url = $this->redirectlogic($pack);
			} else {
				$pack = $this->pay_per_post_bundle($amount, $id, '');
				$url = $this->redirectlogic($pack);
			}

			header('Location: ' . $url);
//			echo '<meta http-equiv="refresh" content="0;' . $url . '" />';
		} else {
//			echo '<p><a href="' . site_url('/') . '">Return Home</a></p>';
			header('Location: ' . site_url($this->error_url));
		}
		exit;

		header('HTTP/1.1 200 OK');
		exit;
	}
	function fail_process() {
		$data = array();
		$code = $_POST['CpiResultsCode'];

		if ($code < 1) {
			return $data;
		}

		$data['new_status'] = YM_STATUS_ERROR;
		$data['status_str'] = __('Last payment was refunded or denied','ym');
		$data['expiry'] = time();

		switch ($code) {
			case 1:
				$data['status_str'] = 'You cancelled the transaction';
				break;
			case 2:
				$data['status_str'] = 'An Unknown Error Occured';
				break;
			case 3:
				$data['status_str'] = 'The Card is invalid/was rejected';
				break;
			case 4:
				$data['status_str'] = 'The Processor did not return a response';
				break;
			case 5:
				$data['status_str'] = 'The payment amount is invalid';
				break;
			case 6:
				$data['status_str'] = 'The Currency is unsupported';
				break;
			case 7:
				$data['status_str'] = 'The Order is invalid as the Order ID is a duplicated';
				break;
			case 8:
				$data['status_str'] = 'The transaction was rejected by FraudSheild';
				break;
			case 9:
				$data['status_str'] = 'The transaction has been placed in the Review state by FraudSheild';
				$data['new_status'] = YM_STATUS_PENDING;
				unset($data['expiry']);
				break;
			case 10:
				$data['status_str'] = 'The transaction failed due to invalid/missing input data';
				break;
			case 11:
				$data['status_str'] = 'The transaction failed as the CPI was configured incorrectly';
				break;
			case 12:
				$data['status_str'] = 'The transaction failed as the Storefront is configured incorrectly';
				break;
			case 13:
				$data['status_str'] = 'The connection timed out';
				break;
			case 14:
				$data['status_str'] = 'The transaction failed becuase the cardholder&#39;s brwoser rejected cookies';
				break;
			case 15:
				$data['status_str'] = 'The customer&#39;s browser does not support 128-bit encyption';
				break;
			case 16:
				$data['status_str'] = 'The CPI cannot communicate with the Payment Engine';
		}

//		echo '<p>' . $data['status_str'] . '</p>';

		return $data;
	}

	// options

	function load_options() {
		echo '<div id="message" class="updated"><p>' . __('HSBC CPI Gateway Requires the Site to run on HTTPS/SSL', 'ym') . '</p></div>';
		if (!is_ssl()) {
			echo '<div id="message" class="error"><p>' . __('You are not using SSL', 'ym') . '</p></div>';
		} else {
			echo '<div id="message" class="updated"><p>' . __('And you are!', 'ym') . '</p></div>';
		}
		echo '<div id="message" class="updated"><p>' . __('<strong>NOTE</strong> the Hash Key is Specfic to the Currency inuse. Please make sure to set the Correct Currency', 'ym') . '</p></div>';

		$options = array();

		$options[] = array(
			'name'		=> 'store_front_id',
			'label' 	=> __('Your StoreFront ID', 'ym'),
			'caption'	=> __('ClientID sent via Email', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'cpi_hash_key',
			'label' 	=> __('Your Hash Key', 'ym'),
			'caption'	=> __('Hash Key sent in a Letter', 'ym'),
			'type'		=> 'text'
		);

		$options[] = array(
			'name'		=> 'cancel_url',
			'label' 	=> __('Cancel URL', 'ym'),
			'caption'	=> __('On Payment Cancel return to this URL', 'ym'),
			'type'		=> 'url'
		);
		$options[] = array(
			'name'		=> 'error_url',
			'label' 	=> __('Error URL', 'ym'),
			'caption'	=> __('On Payment Error return to this URL', 'ym'),
			'type'		=> 'url'
		);

		$options[] = array(
			'name'		=> 'status',
			'label'		=> __('Mode', 'ym'),
			'caption'	=> '',
			'type'		=> 'status'
		);

		$customs = array();
		$fld_obj = get_option('ym_custom_fields');
		if (strpos($fld_obj->order, ';') !== false) {
			$orders = explode(';', $fld_obj->order);
		} else {
			$orders = array($fld_obj->order);
		}
		foreach ($orders as $order) {
			$entry = ym_get_custom_field_by_id($order);
			if ($entry) {
				$customs[$entry['name']] = $entry['label'];
			}
		}

		// field map
		$options[] = array(
			'name'		=> 'map_billingaddress1',
			'label'		=> __('Custom Field Map: BillingAddress1', 'ym'),
			'caption'	=> __('If Enabled all *Billing Required* Fields are required', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_billingaddress2',
			'label'		=> __('Custom Field Map: BillingAddress2', 'ym'),
			'caption'	=> __('*Billing Optional*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_billingcity',
			'label'		=> __('Custom Field Map: BillingCity', 'ym'),
			'caption'	=> __('*Billing Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_billingcountry',
			'label'		=> __('Custom Field Map: BillingCountry', 'ym'),
			'caption'	=> __('*Billing Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_billingcounty',
			'label'		=> __('Custom Field Map: BillingCounty', 'ym'),
			'caption'	=> __('*Billing Optional*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_billingpostal',
			'label'		=> __('Custom Field Map: BillingPostal', 'ym'),
			'caption'	=> __('*Billing Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);

		$options[] = array(
			'name'		=> 'copybillingshipping',
			'label'		=> __('Copy Billing to Shipping', 'ym'),
			'caption'	=> __('Copy the Custom Field Map from above to below', 'ym'),
			'type'		=> 'yesno',
		);
		
		$options[] = array(
			'name'		=> 'map_shippingaddress1',
			'label'		=> __('Custom Field Map: ShippingAddress1', 'ym'),
			'caption'	=> __('If Enabled all *Shipping Required* Fields are required', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_shippingaddress2',
			'label'		=> __('Custom Field Map: ShippingAddress2', 'ym'),
			'caption'	=> __('*Shipping Optional*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_shippingcity',
			'label'		=> __('Custom Field Map: ShippingCity', 'ym'),
			'caption'	=> __('*Shipping Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_shippingcountry',
			'label'		=> __('Custom Field Map: ShippingCountry', 'ym'),
			'caption'	=> __('*Shipping Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_shippingcounty',
			'label'		=> __('Custom Field Map: ShippingCounty', 'ym'),
			'caption'	=> __('*Shipping Optional*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);
		$options[] = array(
			'name'		=> 'map_shippingpostal',
			'label'		=> __('Custom Field Map: ShippingPostal', 'ym'),
			'caption'	=> __('*Shipping Required*', 'ym'),
			'type'		=> 'select',
			'options'	=> $customs,
		);

		return $options;
	}
	function save_options() {
		if ($_POST['copybillingshipping'] == '1') {
			$_POST['map_shippingaddress1'] = $_POST['map_billingaddress1'];
			$_POST['map_shippingaddress2'] = $_POST['map_billingaddress2'];
			$_POST['map_shippingcity'] = $_POST['map_billingcity'];
			$_POST['map_shippingcountry'] = $_POST['map_billingcountry'];
			$_POST['map_shippingcounty'] = $_POST['map_billingcounty'];
			$_POST['map_shippingpostal'] = $_POST['map_billingpostal'];
		}
		unset($_POST['copybillingshipping']);
		$this->buildnsave();
	}

	// gatewya functions
	function generateHash($vector, $s) {
		// convert from NAME to ID
		$new_vector = array();
		foreach ($vector as $item) {
			$new_vector[] = $item;
		}
		$vector = $new_vector;

		$vector1 = array();
		for($i = 0; $i < sizeof($vector); $i++)
		{
			$flag = false;
			$s2 = $vector[$i];
			$vSize= sizeof($vector1);
			for($k = 0; $k < $vSize && !$flag; $k++)
			{
				$s4 = $vector1[$k];
				$l = strcmp($s2, $s4);
				if($l <= 0)
				{
					array_push($vector1, '');
					for($r = sizeof($vector1)-2; $r >= $k; $r--)
						$vector1[$r+1] = $vector1[$r];

					$vector1[$k] = $s2;
					$flag = true;
				}
			}

			if(!$flag) array_push($vector1, $s2);
		}

		$s1 = '';
		for($j = 0; $j < sizeof($vector1); $j++)
		{
			$s3 = $vector1[$j];
			$s1 = $s1 . $s3;
		}

		$orderCrypto = new ym_globaliris_HSBCorderCrypto();
		$abyte0 = $orderCrypto->decryptToBinary($s);
		$ret = base64_encode(mhash(MHASH_SHA1, $s1.$abyte0, $abyte0));
		return $ret;
	}
	
	function currency_to_code($currency) {
		$currency_codes=array(
			'AED'=>'784',
			'AFA'=>'4',
			'ALL'=>'8',
			'AMD'=>'51',
			'ANG'=>'532',
			'AON'=>'24',
			'ARS'=>'32',
			'ATS'=>'40',
			'AUD'=>'36',
			'AWG'=>'533',
			'AZM'=>'31',
			'BAM'=>'977',
			'BBD'=>'52',
			'BDT'=>'50',
			'BEF'=>'56',
			'BGL'=>'100',
			'BHD'=>'48',
			'BIF'=>'108',
			'BMD'=>'60',
			'BND'=>'96',
			'BRL'=>'986',
			'BSD'=>'44',
			'BWP'=>'72',
			'BYR'=>'974',
			'BZD'=>'84',
			'CAD'=>'124',
			'CDF'=>'976',
			'CHF'=>'756',
			'CLP'=>'152',
			'CNY'=>'156',
			'COP'=>'170',
			'CRC'=>'188',
			'CUP'=>'192',
			'CVE'=>'132',
			'CYP'=>'196',
			'CZK'=>'203',
			'DEM'=>'276',
			'DJF'=>'262',
			'DKK'=>'208',
			'DOP'=>'214',
			'DZD'=>'12',
			'ECS'=>'218',
			'EEK'=>'233',
			'EGP'=>'818',
			'ERN'=>'232',
			'ESP'=>'724',
			'ETB'=>'230',
			'EUR'=>'978',
			'FIM'=>'246',
			'FJD'=>'242',
			'FKP'=>'238',
			'FRF'=>'250',
			'GBP'=>'826',
			'GEL'=>'981',
			'GHC'=>'288',
			'GIP'=>'292',
			'GMD'=>'270',
			'GNF'=>'324',
			'GRD'=>'300',
			'GTQ'=>'320',
			'GWP'=>'624',
			'GYD'=>'328',
			'HKD'=>'344',
			'HNL'=>'340',
			'HRK'=>'191',
			'HTG'=>'332',
			'HUF'=>'348',
			'IDR'=>'360',
			'IEP'=>'372',
			'ILS'=>'376',
			'INR'=>'356',
			'IQD'=>'368',
			'IRR'=>'364',
			'ISK'=>'352',
			'ITL'=>'380',
			'JMD'=>'388',
			'JOD'=>'400',
			'JPY'=>'392',
			'KES'=>'404',
			'KGS'=>'417',
			'KHR'=>'116',
			'KMF'=>'174',
			'KPW'=>'408',
			'KRW'=>'410',
			'KWD'=>'414',
			'KYD'=>'136',
			'KZT'=>'398',
			'LAK'=>'418',
			'LBP'=>'422',
			'LKR'=>'144',
			'LRD'=>'430',
			'LSL'=>'426',
			'LTL'=>'440',
			'LUF'=>'442',
			'LVL'=>'428',
			'LYD'=>'434',
			'MAD'=>'504',
			'MDL'=>'498',
			'MGF'=>'450',
			'MKD'=>'807',
			'MMK'=>'104',
			'MNT'=>'496',
			'MOP'=>'446',
			'MRO'=>'478',
			'MTL'=>'470',
			'MUR'=>'480',
			'MVR'=>'462',
			'MWK'=>'454',
			'MXN'=>'484',
			'MXV'=>'979',
			'MYR'=>'458',
			'MZM'=>'508',
			'NAD'=>'516',
			'NGN'=>'566',
			'NIO'=>'558',
			'NLG'=>'528',
			'NOK'=>'578',
			'NPR'=>'524',
			'NZD'=>'554',
			'OMR'=>'512',
			'PAB'=>'590',
			'PEN'=>'604',
			'PGK'=>'598',
			'PHP'=>'608',
			'PKR'=>'586',
			'PLN'=>'985',
			'PTE'=>'620',
			'PYG'=>'600',
			'QAR'=>'634',
			'ROL'=>'642',
			'RUB'=>'643',
			'RUR'=>'810',
			'RWF'=>'646',
			'SAR'=>'682',
			'SBD'=>'90',
			'SCR'=>'690',
			'SDD'=>'736',
			'SEK'=>'752',
			'SGD'=>'702',
			'SHP'=>'654',
			'SIT'=>'705',
			'SKK'=>'703',
			'SLL'=>'694',
			'SOS'=>'706',
			'SRG'=>'740',
			'STD'=>'678',
			'SVC'=>'222',
			'SYP'=>'760',
			'SZL'=>'748',
			'THB'=>'764',
			'TJR'=>'762',
			'TJS'=>'972',
			'TMM'=>'795',
			'TND'=>'788',
			'TOP'=>'776',
			'TPE'=>'626',
			'TRL'=>'792',
			'TTD'=>'780',
			'TWD'=>'901',
			'TZS'=>'834',
			'UAH'=>'980',
			'UGX'=>'800',
			'USD'=>'840',
			'UYU'=>'858',
			'UZS'=>'860',
			'VEB'=>'862',
			'VND'=>'704',
			'VUV'=>'548',
			'WST'=>'882',
			'XAF'=>'950',
			'XCD'=>'951',
			'XDR'=>'960',
			'XOF'=>'952',
			'XPF'=>'953',
			'YER'=>'886',
			'YUM'=>'891',
			'ZAL'=>'991',
			'ZAR'=>'710',
			'ZMK'=>'894',
			'ZRN'=>'180',
			'ZWD'=>'716'
		);
		return $currency_codes[$currency];
	}
	function country_to_code($code) {
		$code = strtoupper($code);
		$country_codes = array(
			'AF'=>'004',
			'AL'=>'008',
			'DZ'=>'012',
			'AS'=>'016',
			'AD'=>'020',
			'AO'=>'024',
			'AI'=>'660',
			'AQ'=>'010',
			'AG'=>'028',
			'AR'=>'032',
			'AM'=>'051',
			'AW'=>'533',
			'AU'=>'036',
			'AT'=>'040',
			'AZ'=>'031',
			'BS'=>'044',
			'BH'=>'048',
			'BD'=>'050',
			'BB'=>'052',
			'BY'=>'112',
			'BE'=>'056',
			'BZ'=>'084',
			'BJ'=>'204',
			'BM'=>'060',
			'BT'=>'064',
			'BO'=>'068',
			'BA'=>'070',
			'BW'=>'072',
			'BV'=>'074',
			'BR'=>'076',
			'IO'=>'086',
			'BN'=>'096',
			'BG'=>'100',
			'BF'=>'854',
			'BI'=>'108',
			'KH'=>'116',
			'CM'=>'120',
			'CA'=>'124',
			'CV'=>'132',
			'KY'=>'136',
			'CF'=>'140',
			'TD'=>'148',
			'CL'=>'152',
			'CN'=>'156',
			'CX'=>'162',
			'CC'=>'166',
			'CO'=>'170',
			'KM'=>'174',
			'CG'=>'178',
			'CK'=>'184',
			'CR'=>'188',
			'CI'=>'384',
			'HR'=>'191',
			'CU'=>'192',
			'CY'=>'196',
			'CZ'=>'203',
			'DK'=>'208',
			'DJ'=>'262',
			'DM'=>'212',
			'DO'=>'214',
			'TP'=>'626',
			'EC'=>'218',
			'EG'=>'818',
			'SV'=>'222',
			'GQ'=>'226',
			'ER'=>'232',
			'EE'=>'233',
			'ET'=>'231',
			'FK'=>'238',
			'FO'=>'234',
			'FJ'=>'242',
			'FI'=>'246',
			'FR'=>'250',
			'GF'=>'254',
			'PF'=>'258',
			'TF'=>'260',
			'GA'=>'266',
			'GM'=>'270',
			'GE'=>'268',
			'DE'=>'276',
			'GH'=>'288',
			'GI'=>'292',
			'GR'=>'300',
			'GL'=>'304',
			'GD'=>'308',
			'GP'=>'312',
			'GU'=>'316',
			'GT'=>'320',
			'GN'=>'324',
			'GW'=>'624',
			'GY'=>'328',
			'HT'=>'332',
			'HM'=>'334',
			'HN'=>'340',
			'HK'=>'344',
			'HU'=>'348',
			'IS'=>'352',
			'IN'=>'356',
			'ID'=>'360',
			'IR'=>'364',
			'IQ'=>'368',
			'IE'=>'372',
			'IL'=>'376',
			'IT'=>'380',
			'JM'=>'388',
			'JP'=>'392',
			'JO'=>'400',
			'KZ'=>'398',
			'KE'=>'404',
			'KI'=>'296',
			'KP'=>'408',
			'KW'=>'414',
			'KG'=>'417',
			'LA'=>'418',
			'LV'=>'428',
			'LB'=>'422',
			'LS'=>'426',
			'LR'=>'430',
			'LY'=>'434',
			'LI'=>'438',
			'LT'=>'440',
			'LU'=>'442',
			'MO'=>'446',
			'MK'=>'807',
			'MG'=>'450',
			'MW'=>'454',
			'MY'=>'458',
			'MV'=>'462',
			'ML'=>'466',
			'MT'=>'470',
			'MH'=>'584',
			'MQ'=>'474',
			'MR'=>'478',
			'MU'=>'480',
			'YT'=>'175',
			'MX'=>'484',
			'MD'=>'498',
			'MC'=>'492',
			'MN'=>'496',
			'MS'=>'500',
			'MA'=>'504',
			'MZ'=>'508',
			'MM'=>'104',
			'NA'=>'516',
			'NR'=>'520',
			'NP'=>'524',
			'AN'=>'530',
			'NL'=>'528',
			'NC'=>'540',
			'NZ'=>'554',
			'NI'=>'558',
			'NE'=>'562',
			'NG'=>'566',
			'NU'=>'570',
			'NF'=>'574',
			'MP'=>'580',
			'NO'=>'578',
			'OM'=>'512',
			'PK'=>'586',
			'PW'=>'585',
			'PA'=>'591',
			'PG'=>'598',
			'PY'=>'600',
			'PE'=>'604',
			'PH'=>'608',
			'PN'=>'612',
			'PL'=>'616',
			'PT'=>'620',
			'PR'=>'630',
			'QA'=>'634',
			'RE'=>'638',
			'RO'=>'642',
			'RU'=>'643',
			'RW'=>'646',
			'WS'=>'882',
			'SM'=>'674',
			'ST'=>'678',
			'SA'=>'682',
			'SN'=>'686',
			'SC'=>'690',
			'SL'=>'694',
			'SG'=>'702',
			'SK'=>'703',
			'SI'=>'705',
			'SB'=>'090',
			'SO'=>'706',
			'ZA'=>'710',
			'GS'=>'239',
			'ES'=>'724',
			'LK'=>'144',
			'SH'=>'654',
			'KN'=>'659',
			'LC'=>'662',
			'PM'=>'666',
			'VC'=>'670',
			'SD'=>'736',
			'SR'=>'740',
			'SJ'=>'744',
			'SZ'=>'748',
			'SE'=>'752',
			'CH'=>'756',
			'SY'=>'760',
			'TW'=>'158',
			'TJ'=>'762',
			'TZ'=>'834',
			'TH'=>'764',
			'TG'=>'768',
			'TK'=>'772',
			'TO'=>'776',
			'TT'=>'780',
			'TN'=>'788',
			'TR'=>'792',
			'TM'=>'795',
			'TC'=>'796',
			'TV'=>'798',
			'VI'=>'850',
			'UG'=>'800',
			'UA'=>'804',
			'AE'=>'784',
			'GB'=>'826',
			'UM'=>'581',
			'US'=>'840',
			'UY'=>'858',
			'UZ'=>'860',
			'VU'=>'548',
			'VA'=>'336',
			'VE'=>'862',
			'VN'=>'704',
			'WF'=>'876',
			'EH'=>'732',
			'YE'=>'887',
			'YU'=>'891',
			'ZM'=>'894',
			'ZW'=>'716'
		);
		return (isset($country_codes[$code]) ? $country_codes[$code] : 826);
	}
}

/**
* This script is used to generate the hash order key
* which is required by HSBC CPI interface.
*/
/**
* Order information encryption class
* @author Shelley Shyan
* @copyright http://phparch.cn
*/
class ym_globaliris_HSBCorderCrypto {
	private $_fldif;
	private $a;

	function __construct() {
		$s = 'KmJTwzVPwjoxQdWJb1BxbuhBSa2RuM05+/aUdgYoGdFWWf04CKIQTxtxLeKCp+5J';
		$s1 = 'y8YhmjsAoMUW9RxfXBSos0A6LwGd+5pXv/MRAKCYFLG';
		$s2 = 'BqRkPAG8DFFAdeN5SMAArktCYuUGXi2q88EDoOs3Ykw0k';
		$this->a = chr(98).chr(84).chr(120).chr(114).chr(66).chr(87).chr(80).chr(112);

		$this->_fldif = $this->initKey($s, $s1, $s2);
		$this->_fldif = substr($this->_fldif,0,44);
	}

	private function rot13(&$abyte0)
	{
		for($i = 0; $i < strlen($abyte0); $i++)
		{
			$c = ord($abyte0[$i]);
			if ($c >= ord('a') && $c <= ord('m') || $c >= ord('A') && $c <= ord('M'))
				$abyte0[$i] = chr($c + 13);
			else
			if ($c >= ord('n') && $c <= ord('z') || $c >= ord('N') && $c <= ord('Z'))
				$abyte0[$i] = chr($c - 13);
		}
	}

	private function encode($abyte0)
	{
		return base64_encode($abyte0);
	}

	private function decode($s)
	{
		return base64_decode($s);
	}

	private function encrypt($abyte0, $abyte1)
	{
		$td = mcrypt_module_open (MCRYPT_DES, '', MCRYPT_MODE_CBC, '');
		$iv = $this->a;
		$ks = mcrypt_enc_get_key_size ($td);
		$key = substr($abyte1, 0, $ks);

		/* Intialize encryption */
		mcrypt_generic_init ($td, $key, $iv);
		return mcrypt_generic ($td, $abyte0);
	}

	private function decrypt($abyte0, $abyte1)
	{
		$td = mcrypt_module_open (MCRYPT_DES, '', MCRYPT_MODE_CBC, '');
		$iv = $this->a;
		$ks = mcrypt_enc_get_key_size ($td);
		$key = substr($abyte1, 0, $ks);

		/* Intialize encryption */
		mcrypt_generic_init ($td, $key, $iv);
		$ret = mdecrypt_generic($td, $abyte0);

		while($ret[strlen($ret)-1] == "\4" && strlen($ret) > 0){
			$ret=substr($ret, 0, strlen($ret)-1);
		}
		return $ret;
	}

	private function encryptEncode($abyte0, $abyte1)
	{
		return $this->encode($this->encrypt($abyte0, $abyte1));
	}

	private function decodeDecrypt($s, $abyte0)
	{
		return $this->decrypt($this->decode($s), $abyte0);
	}

	private function initKey($s, $s1, $s2)
	{
		$abyte0 = chr(0);
		$abyte1 = $s1;
		$abyte2 = $s2;
		$byte0 = 4;
		$i = $byte0 + 9;
		$j = rand(0, 30);
		$j = 0;
		if($j > $byte0 * $i) 
			$j -= $byte0 * $i;

		$k = 0;
		for($l = 0; $l < $byte0 * $i; $l++)
		{
			switch(($j + $l) % $i)
			{
				case 0: // '\0'
					if($k == 2)
					{
						$abyte0 = $this->encrypt($abyte1, $abyte2);
						$k++;
					}
				break;

				case 1: // '\001'
					if($k == 1)
					{
						$abyte2 = $abyte1;
						$this->rot13($abyte2);
						$k++;
					}
				break;

				case 2: // '\002'
					if($k == 0)
					{
						$i1 = 48 + (ord($abyte1[0]) + 10) % 10;
						$abyte1[0] = chr($i1);
						$k++;
					}
				break;

				case 3: // '\003'
					if($k == 3) $k++;
				break;

				case 5: // '\005'
				case 7: // '\007'
				case 10: // '\n'
					if($k < 2)
						$abyte0 = $this->encrypt($abyte1, $abyte2);
				break;

				case 4: // '\004'
				case 6: // '\006'
				case 8: // '\b'
				case 9: // '\t'
				default:
				break;
			}
		}
		return $this->decodeDecrypt($s, $abyte0);
	}

	public function decryptToBinary($s)
	{
		if ($s == NULL)
			return NULL;
		else
			return $this->decodeDecrypt($s, $this->_fldif);
	}

}
